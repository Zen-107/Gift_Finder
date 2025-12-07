// assets/js/stories.js
document.addEventListener('DOMContentLoaded', () => {
  const gridEl = document.getElementById('stories-feed-container');
  const loadingMessage = document.getElementById('loading-message');

  if (!gridEl) return;

  // ฟังก์ชันสำหรับสร้าง HTML ของ Story Card (medium size)
  const createStoryCard = (s) => {
    // ใช้ article ที่มี class "story-card medieum" เพื่อให้เข้ากับ CSS เดิม
    const card = document.createElement('article');
    card.className = 'story-card medieum';
    
    // สร้าง Tags HTML
    let tagsHtml = '';
    if (Array.isArray(s.tags) && s.tags.length) {
        s.tags.forEach(tag => {
            // ลิงก์ไปยังหน้า Stories พร้อม Filter tag
            tagsHtml += `<a href="stories.html?tag=${encodeURIComponent(tag)}" class="tag-chip-small">${tag}</a>`;
        });
    }

    // แปลงวันที่ให้อ่านง่าย
    const date = new Date(s.created_at.replace(' ', 'T')).toLocaleDateString('th-TH', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });

    card.innerHTML = `
        <a href="story-view.html?id=${encodeURIComponent(s.id)}" class="story-link-wrapper">
          <figure class="story-cover">
            <img src="${s.cover_image || ''}" alt="${s.title}">
          </figure>
          <div class="story-content">
            <h3 class="story-title">${s.title}</h3>
            <p class="story-excerpt">${s.excerpt || ''}</p>
            <div class="story-meta">
                <span>by <a href="stories.html?author_id=${s.author_id}" class="story-author-link">${s.author_name}</a></span>
                <span class="story-date">• ${date}</span>
            </div>
            <div class="story-tags-list">${tagsHtml}</div>
          </div>
        </a>

        <div class="story-actions-footer">
            <span class="pill-btn like-count-display">
                <span class="pill-count">${s.like_count}</span>
                <span class="pill-icon">♡</span>
            </span>
            </div>
    `;

    return card;
  };

  // ดึงข้อมูล Story List จาก API
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

      // ลบ Popular Stories Placeholder ออก (ถ้าคุณต้องการใช้ API แสดง Popular Story ด้วย)
      // ณ จุดนี้ โค้ดจะแสดงผลในส่วน 'Feed' เท่านั้น
      gridEl.innerHTML = ''; 

      if (data.stories.length === 0) {
        gridEl.innerHTML = '<p>ไม่พบ Stories ในระบบ</p>';
        return;
      }

      data.stories.forEach(story => {
        const card = createStoryCard(story);
        gridEl.appendChild(card);
      });
    })
    .catch(err => {
      if (loadingMessage) loadingMessage.remove();
      console.error('Fetch error:', err);
      gridEl.innerHTML = '<p class="error-message">เกิดข้อผิดพลาดในการเชื่อมต่อ (โปรดตรวจสอบไฟล์ PHP)</p>';
    });
});