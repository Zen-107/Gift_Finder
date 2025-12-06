// assets/js/story-view.js

document.addEventListener('DOMContentLoaded', () => {
  const card = document.querySelector('.story-card');
  if (!card) return;

  // อ่าน story id จาก URL เช่น story-view.html?id=1
  const params = new URLSearchParams(window.location.search);
  const storyId = params.get('id') || params.get('story_id');

  if (!storyId) {
    console.error('No story id in URL');
    return;
  }

  card.dataset.storyId = storyId;

  // ดึงข้อมูล story หลัก + more stories จาก API
  fetch('api/story_detail_api.php?story_id=' + encodeURIComponent(storyId))
    .then(res => res.json())
    .then(data => {
      if (!data.success) {
        console.error(data.message || 'Error loading story');
        return;
      }

      const s = data.story;

      // ---------- Title ----------
      const titleEl = document.querySelector('.story-title');
      if (titleEl) titleEl.textContent = s.title;

      // ---------- Cover ----------
      const coverImg = card.querySelector('.story-cover img');
      if (coverImg && s.cover_image) {
        coverImg.src = s.cover_image;
      }

      // ---------- Author ----------
      const authorLink = document.querySelector('.story-author');
      if (authorLink) {
        authorLink.textContent = s.author_name;
        // ปรับปลายทางตามโปรเจกต์จริงได้
        authorLink.href = 'stories.html?author_id=' + encodeURIComponent(s.author_id);
      }

      // ---------- Date ----------
      const dateEl = document.querySelector('.story-date');
      if (dateEl && s.created_at) {
        const d = new Date(s.created_at.replace(' ', 'T'));
        dateEl.textContent = d.toLocaleDateString('th-TH', {
          year: 'numeric',
          month: 'short',
          day: 'numeric'
        });
      }

      // ---------- Body ----------
      const bodyEl = document.querySelector('.story-body');
      if (bodyEl) {
        bodyEl.innerHTML = s.body_html;
      }

      // ---------- Tags ----------
      const tagsListEl = document.querySelector('.tags-list');
      if (tagsListEl) {
        tagsListEl.innerHTML = '';
        if (Array.isArray(s.tags) && s.tags.length) {
          s.tags.forEach(tag => {
            const a = document.createElement('a');
            a.className = 'tag-chip';
            a.href = 'stories.html?tag=' + encodeURIComponent(tag);
            a.textContent = tag;
            tagsListEl.appendChild(a);
          });
        } else {
          const section = document.querySelector('.story-tags');
          if (section) section.style.display = 'none';
        }
      }

      // ---------- Like UI + event ----------
      const likeBtn = document.querySelector('.js-like-btn');
      if (likeBtn) {
        const likeCountEl = likeBtn.querySelector('.js-like-count');
        const likeIconEl = likeBtn.querySelector('.js-like-icon');

        if (likeCountEl) likeCountEl.textContent = s.like_count;
        if (likeIconEl) likeIconEl.textContent = data.liked ? '♥' : '♡';

        likeBtn.addEventListener('click', () => {
          fetch('api/toggle_like.php', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: 'story_id=' + encodeURIComponent(storyId)
          })
            .then(res => res.json())
            .then(result => {
              if (!result.success) {
                if (result.code === 'NOT_LOGGED_IN') {
                  alert('กรุณาเข้าสู่ระบบก่อนกดถูกใจ ❤️');
                } else {
                  alert(result.message || 'ไม่สามารถกดถูกใจได้');
                }
                return;
              }

              if (likeIconEl) {
                likeIconEl.textContent = result.liked ? '♥' : '♡';
              }
              if (likeCountEl && typeof result.like_count !== 'undefined') {
                likeCountEl.textContent = result.like_count;
              }
            })
            .catch(err => {
              console.error('Like error:', err);
            });
        });
      }

      // ---------- Save UI + event ----------
      const saveBtn = document.querySelector('.js-save-btn');
      if (saveBtn) {
        const saveIconEl = saveBtn.querySelector('.js-save-icon');
        if (saveIconEl) saveIconEl.textContent = data.saved ? '★' : '☆';

        saveBtn.addEventListener('click', () => {
          fetch('api/toggle_save.php', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: 'story_id=' + encodeURIComponent(storyId)
          })
            .then(res => res.json())
            .then(result => {
              if (!result.success) {
                if (result.code === 'NOT_LOGGED_IN') {
                  alert('กรุณาเข้าสู่ระบบก่อนบันทึกเรื่องนี้ ✨');
                } else {
                  alert(result.message || 'ไม่สามารถบันทึกเรื่องนี้ได้');
                }
                return;
              }

              if (saveIconEl) {
                saveIconEl.textContent = result.saved ? '★' : '☆';
              }
            })
            .catch(err => {
              console.error('Save error:', err);
            });
        });
      }

      // ---------- More stories ----------
      const grid = document.getElementById('suggestions-grid');
      if (grid && Array.isArray(data.suggestions)) {
        grid.innerHTML = '';
        data.suggestions.forEach(item => {
          const link = document.createElement('a');
          link.href = 'story-view.html?id=' + encodeURIComponent(item.story_id);
          link.className = 'suggestion-card';

          link.innerHTML = `
            <figure class="suggestion-cover">
              <img src="${item.cover_image}" alt="${item.story_title}">
            </figure>
            <h4 class="suggestion-title">${item.story_title}</h4>
          `;

          grid.appendChild(link);
        });
      }

      // ---------- Share ----------
      const shareBtn = document.querySelector('.js-share-btn');
      if (shareBtn) {
        shareBtn.addEventListener('click', async () => {
          const shareData = {
            title: s.title,
            text: s.excerpt || '',
            url: window.location.href
          };

          if (navigator.share && window.isSecureContext) {
            try {
              await navigator.share(shareData);
            } catch (e) {
              console.error(e);
            }
          } else if (navigator.clipboard) {
            try {
              await navigator.clipboard.writeText(shareData.url);
              alert('คัดลอกลิงก์แล้ว ✔');
            } catch (e) {
              alert(shareData.url);
            }
          } else {
            alert(shareData.url);
          }
        });
      }
    })
    .catch(err => {
      console.error('Fetch error:', err);
    });
});
