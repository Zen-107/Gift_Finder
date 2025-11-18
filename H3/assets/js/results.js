// results.js — แสดงสินค้าทั้งหมดจาก API
document.addEventListener('DOMContentLoaded', () => {
  const critEl = document.getElementById('criteria');
  const grid = document.getElementById('results');
  const empty = document.getElementById('empty');

  // ตั้งค่าหัวเรื่อง
  critEl.textContent = 'แสดงสินค้าทั้งหมด';

  // เรียก API เพื่อดึงข้อมูลสินค้า
  fetch('api/get_all_product.php')
    .then(response => {
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }
      return response.json();
    })
    .then(products => {
      console.log('Raw API Response:', products);

      // ตรวจสอบว่า products เป็น array และมีข้อมูล
      if (!Array.isArray(products) || products.length === 0) {
        console.warn('ไม่พบข้อมูลสินค้า');
        empty.style.display = 'block';
        return;
      }

      // ซ่อน element ที่แสดงว่าไม่มีข้อมูล
      empty.style.display = 'none';

      // สร้าง HTML ของสินค้าทั้งหมด
      grid.innerHTML = products.map(p => {
        let priceText = '—';
        if (p.min_price !== null && p.max_price !== null) {
          const min = parseFloat(p.min_price);
          const max = parseFloat(p.max_price);
          const currency = p.currency || 'THB';
          if (min === max) {
            priceText = `${min.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })} ${currency}`;
          } else {
            priceText = `${min.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })} – ${max.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })} ${currency}`;
          }
        }

        return `
          <div class="card">
            <img src="${p.image_url || 'assets/images/placeholder.png'}" alt="${p.name}" onerror="this.onerror=null; this.src='assets/images/placeholder.png';">
            <div class="card-body">
              <div class="badge">${p.categories?.[0] || 'Gift'}</div>
              <strong>${p.name}</strong>
              <div class="price">${priceText}</div>
              <div>${p.description ? p.description.substring(0, 100) + '...' : ''}</div>
              <div class="stack">
                <a class="btn" href="product.html?id=${p.id}">View details</a>
              </div>
            </div>
          </div>
        `;
      }).join('');
    })
    .catch(error => {
      console.error('Error loading products:', error);
      critEl.textContent = 'เกิดข้อผิดพลาดในการโหลดสินค้า';
      empty.textContent = 'ไม่สามารถโหลดข้อมูลสินค้าได้';
      empty.style.display = 'block';
    });
});