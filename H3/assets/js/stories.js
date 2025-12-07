// assets/js/stories.js
document.addEventListener('DOMContentLoaded', () => {
  const gridEl = document.getElementById('stories-feed-container');
  const staffPicksEl = document.getElementById('staff-picks-container'); // *NEW*
  const loadingMessage = document.getElementById('loading-message');

  const authorListEl = document.getElementById('author-list');

  const staffContainer = document.getElementById('staff-picks-container');
  document.getElementById('staff-left').addEventListener('click', () => {
    staffContainer.scrollBy({ left: -260, behavior: 'smooth' });
  });
  document.getElementById('staff-right').addEventListener('click', () => {
    staffContainer.scrollBy({ left: 260, behavior: 'smooth' });
  });

  // 👇 ดึง container ของ tags แนะนำ (อยู่ใน sidebar ขวา)
  const tagListEl = document.querySelector('.tag-panel .tag-list'); // 👈 NEW

  if (!gridEl || !staffPicksEl) return; // *MODIFIED* ตรวจสอบ element ทั้งสอง

  // ฟังก์ชันวาด "Tags แนะนำ" ลงใน sidebar
  const renderRecommendedTags = (tags) => {         // 👈 NEW
    if (!tagListEl || !Array.isArray(tags)) return;

    tagListEl.innerHTML = '';

    tags.forEach(tag => {
      const el = document.createElement('button');
      el.type = 'button';
      el.className = 'tag-pill';       // ไว้ไปตกแต่งใน CSS
      el.textContent = `#${tag}`;

      // ถ้าอยากให้กดแล้วไปหน้า filter ตาม tag ก็ใส่ event ได้ทีหลัง
      // el.addEventListener('click', () => { ... });

      tagListEl.appendChild(el);
    });
  };

  // *** NEW FUNCTION: ฟังก์ชันสำหรับสร้าง HTML ของ Staff Pick Card (รูปปกเป็นพื้นหลัง) ***
  const createStaffPickCard = (s) => {
    // <a> ครอบทั้งกล่อง เอาไว้กดไปหน้า story-view
    const card = document.createElement('a');
    card.href = `story-view.html?id=${encodeURIComponent(s.id)}`;
    card.className = 'staff-pick-card';

    // ชั้นที่เป็นพื้นหลังรูป
    const bg = document.createElement('div');
    bg.className = 'card-bg';
    bg.style.backgroundImage = `url("${s.cover_image}")`;

    // ชั้นที่เป็น overlay ข้อความ
    const overlay = document.createElement('div');
    overlay.className = 'card-overlay';

    const title = document.createElement('h3');
    title.className = 'card-title';
    title.textContent = s.title;

    const author = document.createElement('p');
    author.className = 'card-author';
    author.textContent = `by ${s.author_name}`;

    overlay.appendChild(title);
    overlay.appendChild(author);

    bg.appendChild(overlay);
    card.appendChild(bg);

    return card;
  };

  // ฟังก์ชันไว้ทำ excerpt จาก body text
  const makeExcerptFromBody = (text, limit = 150) => {
    if (!text) return '';

    // ตัด HTML tag ทิ้ง ถ้า body เป็น HTML
    const plainText = text.replace(/<[^>]*>/g, '').trim();

    if (plainText.length <= limit) return plainText;

    // ตัดไม่ให้ขาดกลางคำสักหน่อย
    const sliced = plainText.slice(0, limit);
    return sliced.replace(/\s+\S*$/, '') + '...';
  };

  const createStoryCard = (s) => {
    const card = document.createElement('article');
    card.className = 'story-card-horizontal';

    const date = new Date(s.created_at.replace(' ', 'T')).toLocaleDateString('th-TH', {
      year: 'numeric',
      month: 'short',
      day: 'numeric'
    });

    // ✅ ดึงจาก body text (ถ้าไม่มี ค่อย fallback ไป excerpt)
    const bodyText = s.body || s.content || s.excerpt || '';
    const excerpt = makeExcerptFromBody(bodyText, 150);

    card.innerHTML = `
      <!-- ซ้าย: รูป + meta ใต้รูป -->
      <div class="story-left">
        <div class="story-image">
          <img src="${s.cover_image}" alt="${s.title}" loading="lazy">
        </div>
        <div class="story-meta-under">
          <span class="author-name">by ${s.author_name}</span>
          <span class="post-date">${date}</span>
        </div>
      </div>

      <!-- ขวา: หัวเรื่อง + เนื้อเรื่องสั้นๆ + ปุ่ม View -->
      <div class="story-right">
        <h3 class="story-title">${s.title}</h3>
        <p class="story-excerpt">${excerpt}</p>
        <a href="story-view.html?id=${encodeURIComponent(s.id)}" class="btn-view-story">
          View
        </a>
      </div>
    `;

    return card;
  };

  const renderAuthors = (authors) => {
  if (!authorListEl) return;

  authorListEl.innerHTML = '';

  if (!Array.isArray(authors) || authors.length === 0) {
    authorListEl.innerHTML = '<p class="empty-authors">ยังไม่มีข้อมูลผู้เขียน</p>';
    return;
  }

  authors.forEach(author => {
    const item = document.createElement('a');
    item.className = 'author-item';
    // เผื่ออนาคต filter ตามผู้เขียน ก็อ่านค่า ?author_id= จาก URL นี้ได้เลย
    item.href = `stories.html?author_id=${encodeURIComponent(author.id)}`;

    item.innerHTML = `
      <span class="author-name">${author.name}</span>
      <span class="author-count">${author.post_count} stories</span>
    `;

    authorListEl.appendChild(item);
  });
};


  // ดึงข้อมูล Story List และ Staff Picks จาก API
  fetch('api/stories_api.php')
    .then(res => {
      if (!res.ok) {
        throw new Error('Network response was not ok.');
      }
      return res.json();
    })
    .then(data => {
      if (loadingMessage) loadingMessage.remove();

      if (!data.success || !Array.isArray(data.stories)) {
        console.error(data.message || 'Error loading stories list');
        gridEl.innerHTML = '<p class="error-message">ไม่สามารถโหลดรายการ Stories ได้</p>';
        return;
      }

      // 1. แสดง Staff Picks (5 เรื่องสุ่ม)
      if (Array.isArray(data.staff_picks)) {
        staffPicksEl.innerHTML = ''; // เคลียร์ "กำลังโหลด..." ทิ้งก่อน
        data.staff_picks.forEach(story => {
          const card = createStaffPickCard(story);
          staffPicksEl.appendChild(card);
        });
      }

      // 2. แสดง Stories ใน Feed (ทั้งหมด)
      gridEl.innerHTML = ''; // ล้าง Feed Placeholder

      if (data.stories.length === 0) {
        gridEl.innerHTML = '<p>ไม่พบ Stories ในระบบ</p>';
        return;
      }

      data.stories.forEach(story => {
        const card = createStoryCard(story);
        gridEl.appendChild(card);
      });

      // 3. แสดง Tags แนะนำ (สุ่มจากทุกโพสต์)
if (Array.isArray(data.recommended_tags) && tagListEl) {
  tagListEl.innerHTML = '';
  data.recommended_tags.forEach(tag => {
    const el = document.createElement('button');
    el.type = 'button';
    el.className = 'tag-pill';
    el.textContent = `#${tag}`;
    tagListEl.appendChild(el);
  });
}

// 4. แสดง Authors แนะนำ (20 คน)
if (Array.isArray(data.recommended_authors)) {
  renderAuthors(data.recommended_authors);
}


    })
    .catch(error => {
      if (loadingMessage) loadingMessage.remove();
      console.error('Fetch error:', error);
      gridEl.innerHTML = '<p class="error-message">เกิดข้อผิดพลาดในการดึงข้อมูล Stories</p>';
    });
});
