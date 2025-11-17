function getQueryParam(name) {
  const urlParams = new URLSearchParams(window.location.search);
  return urlParams.get(name);
}

// ✅ ฟังก์ชันแปลงคำอธิบายให้มีย่อหน้า
function formatDescription(text) {
  if (!text) return '';
  // แยกย่อหน้าโดยใช้ \n\n (2 บรรทัดว่าง) → สร้าง <p>
  return text
    .split('\n\n')
    .map(para => {
      // ลบช่องว่างที่หัว-ท้าย และแปลง \n เดี่ยว → <br>
      const trimmed = para.trim();
      return trimmed ? `<p>${trimmed.replace(/\n/g, '<br>')}</p>` : '';
    })
    .join('');
}

async function loadBookmarkStatus(productId) {
  try {
    const res = await fetch(`/H3/api/check_bookmark.php?product_id=${productId}`);
    const data = await res.json();
    return data.isBookmarked;
  } catch (e) {
    console.warn('ไม่สามารถตรวจสอบ bookmark ได้');
    return false;
  }
}

async function toggleBookmark(productId, buttonElement) {
  const isCurrentlyBookmarked = buttonElement.classList.contains('bookmarked');
  const url = isCurrentlyBookmarked ? '/H3/api/remove_bookmark.php' : '/H3/api/add_bookmark.php';
  const res = await fetch(url, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ product_id: productId })
  });
  const data = await res.json();
  if (data.success) {
    if (isCurrentlyBookmarked) {
      buttonElement.classList.remove('bookmarked');
      buttonElement.innerHTML = '<i class="far fa-bookmark"></i> บุ๊กมาร์ก';
    } else {
      buttonElement.classList.add('bookmarked');
      buttonElement.innerHTML = '<i class="fas fa-bookmark"></i> ลบบุ๊กมาร์ก';
    }
  } else {
    alert('⚠️ ' + data.message);
  }
}


document.addEventListener('DOMContentLoaded', async () => { // ✅ เพิ่ม async ที่นี่
  const id = Number(getQueryParam('id'));
  
  if (!id) {
    document.getElementById('product').innerHTML = '<div class="empty">Product not found.</div>';
    return;
  }

  try {
    const response = await fetch(`api/get_product.php?id=${id}`);
    if (!response.ok) throw new Error('ไม่พบสินค้านี้');
    const p = await response.json();

    const root = document.getElementById('product');

    // สร้างปุ่มร้านค้าจาก external_urls
    let buyButtons = '';
    if (p.external_urls && p.external_urls.length > 0) {
      p.external_urls.forEach(link => {
        const source = link.source_name || 'ซื้อเลย';
        buyButtons += `
          <a class="btn secondary" href="${link.url}" target="_blank" style="text-decoration:none; display:inline-block; margin-right:8px;">
            ${source}
          </a>
        `;
      });
    } else if (p.external_url) {
      buyButtons = `
        <a class="btn secondary" href="${p.external_url}" target="_blank" style="text-decoration:none; display:inline-block;">
          ซื้อเลย
        </a>
      `;
    }

    // ✅ สร้างข้อความช่วงราคาจาก price_range
    let priceText = '—';
    if (p.price_range && p.price_range.min !== null && p.price_range.max !== null) {
      const min = parseFloat(p.price_range.min);
      const max = parseFloat(p.price_range.max);
      const currency = p.price_range.currency || 'THB';
      if (min === max) {
        priceText = `${min.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })} ${currency}`;
      } else {
        priceText = `${min.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })} – ${max.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })} ${currency}`;
      }
    }

    const formattedDescription = formatDescription(p.description);

    // ✅ โหลดสถานะ bookmark
    const isBookmarked = await loadBookmarkStatus(p.id); // ✅ ตอนนี้ใช้ await ได้แล้ว

    // สร้าง HTML
    root.innerHTML = `
      <div class="grid" style="grid-template-columns: 1fr 1fr; gap:24px">
        <div>
          <img src="${p.image_url || ''}" alt="${p.name}" style="max-width:100%; height:auto;">
        </div>
        <div>
          <div class="badge">${p.categories && Array.isArray(p.categories) && p.categories.length > 0 ? p.categories[0] : 'Gift'}</div>
          <h1>${p.name}</h1>
          <div class="price" style="font-size:20px">${priceText}</div>
          <div class="product-description">${formattedDescription}</div>
          <div class="stack">
            ${buyButtons}
            <button class="btn bookmark-btn ${isBookmarked ? 'bookmarked' : ''}">
              ${isBookmarked ? '<i class="fas fa-bookmark"></i> ลบบุ๊กมาร์ก' : '<i class="far fa-bookmark"></i> บุ๊กมาร์ก'}
            </button>
          </div>
        </div>
      </div>

      <div class="section">
        <h2>รีวิว</h2>
        <div class="card">
          <div class="card-body">“คุณภาพดี คุ้มค่ากับราคา” — ผู้ใช้งาน</div>
        </div>
      </div>
    `;

    // ✅ เพิ่ม event listener
    const bookmarkBtn = root.querySelector('.bookmark-btn');
    if (bookmarkBtn) {
      bookmarkBtn.addEventListener('click', () => {
        toggleBookmark(p.id, bookmarkBtn);
      });
    }

  } catch (error) {
    console.error('❌ Error:', error);
    document.getElementById('product').innerHTML = 
      `<div class="empty">เกิดข้อผิดพลาด: ${error.message}</div>`;
  }
});
