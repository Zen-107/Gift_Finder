function getQueryParam(name) {
  const urlParams = new URLSearchParams(window.location.search);
  return urlParams.get(name);
}

document.addEventListener('DOMContentLoaded', () => {
  const id = Number(getQueryParam('id'));
  
  if (!id) {
    document.getElementById('product').innerHTML = '<div class="empty">Product not found.</div>';
    return;
  }

  fetch(`api/get_product.php?id=${id}`)
    .then(response => {
      if (!response.ok) throw new Error('ไม่พบสินค้านี้');
      return response.json();
    })
    .then(p => {
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
        // fallback: ใช้ external_url ในตาราง products
        buyButtons = `
          <a class="btn secondary" href="${p.external_url}" target="_blank" style="text-decoration:none; display:inline-block;">
            ซื้อเลย
          </a>
        `;
      }

      root.innerHTML = `
        <div class="grid" style="grid-template-columns: 1fr 1fr; gap:24px">
          <div>
            <img src="${p.image_url}" alt="${p.name}" style="max-width:100%;">
          </div>
          <div>
            <div class="badge">${p.categories[0] || 'Gift'}</div>
            <h1>${p.name}</h1>
            <div class="price" style="font-size:20px">${p.price.toLocaleString()} ${p.currency}</div>
            <p>${p.description || ''}</p>
            <div class="stack">
              ${buyButtons}
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
    })
    .catch(error => {
      console.error('Error:', error);
      document.getElementById('product').innerHTML = 
        `<div class="empty">เกิดข้อผิดพลาด: ${error.message}</div>`;
    });
});