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

async function getOrCreateFavoriteFolder(userId) {
  try {
    const res = await fetch('/H3/api/get_folder_by_name.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ user_id: userId, name: 'Favorites' })
    });
    const data = await res.json();
    if (data.success && data.folder) {
      return data.folder.id;
    } else {
      const createRes = await fetch('/H3/api/create_folder.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ user_id: userId, name: 'Favorites' })
      });
      const createData = await createRes.json();
      if (createData.success) {
        return createData.folder_id;
      } else {
        throw new Error(createData.message || 'ไม่สามารถสร้างโฟลเดอร์ Favorites ได้');
      }
    }
  } catch (e) {
    console.error('Error in getOrCreateFavoriteFolder:', e);
    throw e;
  }
}

/////////////////////////////////////////////////////////////// for folder selection ///////////////////////////////////////////////////////
// ✅ ฟังก์ชันดึงโฟลเดอร์ของผู้ใช้
async function getUserFolders() {
  try {
    const res = await fetch('/H3/api/get_user_folders.php');
    const data = await res.json();
    if (data.success) {
      return data.folders;
    } else {
      throw new Error(data.message || 'ไม่สามารถดึงข้อมูลโฟลเดอร์ได้');
    }
  } catch (e) {
    console.error('Error fetching folders:', e);
    alert('⚠️ เกิดข้อผิดพลาดในการดึงข้อมูลโฟลเดอร์');
    return [];
  }
}

// ✅ ฟังก์ชันแสดง modal สำหรับจัดการบุ๊กมาร์ก
async function showManageBookmarkModal(productId, buttonElement) {
  // ลบ modal เก่า (ถ้ามี)
  const existingModal = document.getElementById('bookmark-modal');
  if (existingModal) existingModal.remove();

  // สร้าง modal
  const modal = document.createElement('div');
  modal.id = 'bookmark-modal';
  modal.style.cssText = `
    position: fixed; top: 0; left: 0; width: 100%; height: 100%;
    background: rgba(0,0,0,0.5); display: flex; justify-content: center; align-items: center; z-index: 1000;
  `;
  modal.innerHTML = `
    <div style="background: white; padding: 20px; border-radius: 8px; width: 600px; max-width: 90%;">
      <h3>จัดการบุ๊กมาร์ก</h3>
      <div id="modal-content">
        <!-- โหลดข้อมูลโดย JavaScript -->
      </div>
      <div style="margin-top: 16px;">
        <button onclick="closeBookmarkModal()" style="margin-right: 8px;">ปิด</button>
      </div>
    </div>
  `;
  document.body.appendChild(modal);

  // โหลดข้อมูล
  loadManageBookmarkContent(productId, buttonElement);
}

// ✅ โหลดเนื้อหาใน modal
async function loadManageBookmarkContent(productId, buttonElement) {
  try {
    const res = await fetch(`/H3/api/get_bookmarks_by_product.php?product_id=${productId}`);
    const data = await res.json();
    if (!data.success) {
      throw new Error(data.message || 'ไม่สามารถดึงข้อมูลบุ๊กมาร์กได้');
    }

    const bookmarkedFolders = data.bookmarks;

    const content = document.getElementById('modal-content');
    content.innerHTML = `
      <div style="display: flex; gap: 16px; margin-bottom: 16px;">
        <div style="flex: 1; border: 1px solid #ddd; padding: 12px; border-radius: 4px;">
          <h4>เลือกโฟลเดอร์</h4>
          <div id="folder-selection" style="max-height: 200px; overflow-y: auto;">
            ${bookmarkedFolders.map(f => `
              <label style="display: block; margin: 8px 0;">
                <input type="checkbox" name="folder" value="${f.folder_id}" data-name="${f.folder_name}" checked> ${f.folder_name}
                <button onclick="removeFolderFromSelection(this)" style="margin-left: 8px; padding: 2px 6px; font-size: 12px;">❌</button>
              </label>
            `).join('')}
            <label style="display: block; margin: 8px 0;">
              <input type="checkbox" name="folder" value="new" id="new-folder-checkbox"> สร้างโฟลเดอร์ใหม่
            </label>
            <input type="text" id="new-folder-name" placeholder="ชื่อโฟลเดอร์ใหม่" style="width: 100%; padding: 8px; margin-top: 8px; display: none;" />
          </div>
          <div style="margin-top: 16px;">
            <button onclick="confirmAddToFolders(${productId})" style="margin-right: 8px;">เพิ่มไปยังโฟลเดอร์ที่เลือก</button>
            <button onclick="confirmRemoveFromFolders(${productId})">ลบออกจากโฟลเดอร์ที่เลือก</button>
          </div>
        </div>
        <div style="flex: 1; border: 1px solid #ddd; padding: 12px; border-radius: 4px;">
          <h4>รายการบุ๊กมาร์ก</h4>



      <div id="bookmark-list" style="max-height: 200px; overflow-y: auto;">
        ${bookmarkedFolders.map(f => `
          <div style="border: 1px solid #eee; padding: 8px; margin: 8px 0; border-radius: 4px;">
            <strong>${f.folder_name}</strong>
            <button onclick="viewFolderContents(${f.folder_id}, '${f.folder_name}')" style="margin-left: 8px; padding: 2px 6px; font-size: 12px;">👁️</button>
            ${f.folder_name.toLowerCase() === 'favorite' || f.folder_name.toLowerCase() === 'favorites' ? '' : `<button onclick="removeBookmarkFromFolder(${productId}, ${f.folder_id})" style="margin-left: 8px; padding: 2px 6px; font-size: 12px;">❌</button>`}
          </div>
        `).join('')}
      </div>


<div id="folder-selection" style="max-height: 200px; overflow-y: auto;">
  ${bookmarkedFolders
        .filter(f => f.folder_name.toLowerCase() !== 'favorite' && f.folder_name.toLowerCase() !== 'favorites')
        .map(f => `
      <label style="display: block; margin: 8px 0;">
        <input type="checkbox" name="folder" value="${f.folder_id}" data-name="${f.folder_name}" checked> ${f.folder_name}
        <button onclick="removeFolderFromSelection(this)" style="margin-left: 8px; padding: 2px 6px; font-size: 12px;">❌</button>
      </label>
    `).join('')}



          </div>
        </div>
      </div>
    `;

    // ซ่อน/แสดงช่องพิมพ์ชื่อโฟลเดอร์ใหม่
    document.getElementById('new-folder-checkbox').addEventListener('change', () => {
      const newFolderInput = document.getElementById('new-folder-name');
      if (document.getElementById('new-folder-checkbox').checked) {
        newFolderInput.style.display = 'block';
      } else {
        newFolderInput.style.display = 'none';
      }
    });

  } catch (e) {
    console.error('Error loading bookmark data:', e);
    content.innerHTML = `<p style="color: red;">${e.message}</p>`;
  }
}

// ✅ ฟังก์ชันลบโฟลเดอร์ออกจาก selection
function removeFolderFromSelection(btn) {
  const checkbox = btn.previousElementSibling;
  checkbox.checked = false;
  btn.parentElement.remove(); // ลบ label ทั้งหมด
}

// ✅ ฟังก์ชันยืนยันเพิ่มไปยังโฟลเดอร์
async function confirmAddToFolders(productId) {
  const selectedCheckboxes = document.querySelectorAll('input[name="folder"]:checked');
  const selectedFolders = [];
  let newFolderName = null;

  selectedCheckboxes.forEach(cb => {
    if (cb.value === 'new') {
      newFolderName = document.getElementById('new-folder-name').value.trim();
      if (!newFolderName) {
        alert('กรุณากรอกชื่อโฟลเดอร์ใหม่');
        return;
      }
    } else {
      selectedFolders.push({
        id: parseInt(cb.value),
        name: cb.dataset.name
      });
    }
  });

  if (selectedFolders.length === 0 && !newFolderName) {
    alert('กรุณาเลือกโฟลเดอร์อย่างน้อย 1 รายการ');
    return;
  }

  if (newFolderName) {
    try {
      const createRes = await fetch('/H3/api/create_folder.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ user_id: USER_ID, name: newFolderName.trim() })
      });
      const createData = await createRes.json();
      if (createData.success) {
        selectedFolders.push({ id: createData.folder_id, name: newFolderName });
      } else {
        alert('⚠️ ' + createData.message);
        return;
      }
    } catch (e) {
      console.error('Error creating folder:', e);
      alert('⚠️ เกิดข้อผิดพลาดในการสร้างโฟลเดอร์');
      return;
    }
  }

  // ✅ เพิ่มไปยังแต่ละโฟลเดอร์
  for (const folder of selectedFolders) {
    const url = '/H3/api/add_bookmark.php';
    const res = await fetch(url, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ product_id: productId, folder_id: folder.id })
    });
    const data = await res.json();
    if (!data.success) {
      alert('⚠️ ' + data.message);
      return;
    }
  }

  alert('เพิ่มบุ๊กมาร์กสำเร็จ');
  loadManageBookmarkContent(productId); // รีเฟรชหน้า
}

// ✅ ฟังก์ชันยืนยันลบออกจากโฟลเดอร์
async function deleteFolder(folderId, folderName) {
  if (folderName.toLowerCase() === 'favorite' || folderName.toLowerCase() === 'favorites') {
    alert('ไม่สามารถลบโฟลเดอร์นี้ได้');
    return;
  }

  if (!confirm(`คุณต้องการลบทั้งโฟลเดอร์ "${folderName}" ใช่หรือไม่?\n(สินค้าทั้งหมดในโฟลเดอร์นี้จะถูกลบออก)`)) {
    return;
  }

  const url = '/H3/api/delete_folder.php';
  const res = await fetch(url, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ folder_id: folderId })
  });
  const data = await res.json();
  if (data.success) {
    alert('ลบทั้งโฟลเดอร์สำเร็จ');
    location.reload();
  } else {
    alert('⚠️ ' + data.message);
  }


  alert('ลบบุ๊กมาร์กสำเร็จ');
  loadManageBookmarkContent(productId); // รีเฟรชหน้า
}

// ✅ ฟังก์ชันดูสินค้าในโฟลเดอร์
async function viewFolderContents(folderId, folderName) {
  try {
    const res = await fetch(`/H3/api/get_products_in_folder.php?folder_id=${folderId}`);
    const data = await res.json();
    if (!data.success) {
      throw new Error(data.message || 'ไม่สามารถดึงข้อมูลสินค้าได้');
    }

    const products = data.products;

    const content = document.getElementById('modal-content');
    content.innerHTML = `
      <h3>สินค้าในโฟลเดอร์: ${folderName}</h3>
      <div style="max-height: 300px; overflow-y: auto;">
        ${products.map(p => `
          <div style="border: 1px solid #eee; padding: 8px; margin: 8px 0; border-radius: 4px;">
            <img src="${p.image_url}" alt="${p.name}" style="width: 50px; height: 50px; object-fit: cover; margin-right: 8px;" />
            <span>${p.name}</span>
            <button onclick="removeBookmarkFromFolder(${p.product_id}, ${folderId})" style="margin-left: 8px; padding: 2px 6px; font-size: 12px;">❌</button>
          </div>
        `).join('')}
      </div>
      <div style="margin-top: 16px;">
        <button onclick="loadManageBookmarkContent(${products[0]?.product_id || 0})">กลับไปจัดการ</button>
      </div>
    `;
  } catch (e) {
    console.error('Error viewing folder contents:', e);
    content.innerHTML = `<p style="color: red;">${e.message}</p>`;
  }
}

// ✅ ฟังก์ชันลบบุ๊กมาร์กจากโฟลเดอร์
async function removeBookmarkFromFolder(productId, folderId) {
  const url = '/H3/api/remove_bookmark.php';
  const res = await fetch(url, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ product_id: productId, folder_id: folderId })
  });
  const data = await res.json();
  if (data.success) {
    alert('ลบบุ๊กมาร์กสำเร็จ');
    // ✅ รีเฟรชหน้า
    location.reload();
  } else {
    alert('⚠️ ' + data.message);
  }
}

// ✅ ปิด modal
function closeBookmarkModal() {
  const modal = document.getElementById('bookmark-modal');
  if (modal) modal.remove();
}

//////////////////////////////////////////////////////////////////////// end for folder selection ///////////////////////////////////////////////////////


function showFolderSelectionModal(folders, onConfirm) {
  // ลบ modal เก่า (ถ้ามี)
  const existingModal = document.getElementById('folder-modal');
  if (existingModal) existingModal.remove();

  // สร้าง modal
  const modal = document.createElement('div');
  modal.id = 'folder-modal';
  modal.style.cssText = `
    position: fixed; top: 0; left: 0; width: 100%; height: 100%;
    background: rgba(0,0,0,0.5); display: flex; justify-content: center; align-items: center; z-index: 1000;
  `;
  modal.innerHTML = `
    <div style="background: white; padding: 20px; border-radius: 8px; width: 400px; max-width: 90%;">
      <h3>เลือกโฟลเดอร์</h3>



<div style="max-height: 300px; overflow-y: auto;">
  ${folders.map(f => `
    <label style="display: block; margin: 8px 0;">
      <input type="checkbox" name="folder" value="${f.id}" data-name="${f.name}"> ${f.name}
      ${f.name.toLowerCase() === 'favorite' || f.name.toLowerCase() === 'favorites' ? '' : `<button onclick="removeFolderFromSelection(this)" style="margin-left: 8px; padding: 2px 6px; font-size: 12px;">❌</button>`}
    </label>
  `).join('')}



        <label style="display: block; margin: 8px 0;">
          <input type="checkbox" name="folder" value="new" id="new-folder-checkbox"> สร้างโฟลเดอร์ใหม่
        </label>
        <input type="text" id="new-folder-name" placeholder="ชื่อโฟลเดอร์ใหม่" style="width: 100%; padding: 8px; margin-top: 8px; display: none;" />
      </div>
      <div style="margin-top: 16px;">
        <button onclick="confirmFolderSelection()" style="margin-right: 8px;">ยืนยัน</button>
        <button onclick="closeFolderModal()">ยกเลิก</button>
      </div>
    </div>
  `;
  document.body.appendChild(modal);

  // ซ่อน/แสดงช่องพิมพ์ชื่อโฟลเดอร์ใหม่
  document.getElementById('new-folder-checkbox').addEventListener('change', () => {
    const newFolderInput = document.getElementById('new-folder-name');
    if (document.getElementById('new-folder-checkbox').checked) {
      newFolderInput.style.display = 'block';
    } else {
      newFolderInput.style.display = 'none';
    }
  });

  window.confirmFolderSelection = () => {
    const selectedCheckboxes = document.querySelectorAll('input[name="folder"]:checked');
    const selectedFolders = [];
    let newFolderName = null;

    selectedCheckboxes.forEach(cb => {
      if (cb.value === 'new') {
        newFolderName = document.getElementById('new-folder-name').value.trim();
        if (!newFolderName) {
          alert('กรุณากรอกชื่อโฟลเดอร์ใหม่');
          return;
        }
      } else {
        selectedFolders.push({
          id: parseInt(cb.value),
          name: cb.dataset.name
        });
      }
    });

    if (selectedFolders.length === 0 && !newFolderName) {
      alert('กรุณาเลือกโฟลเดอร์อย่างน้อย 1 รายการ');
      return;
    }

    onConfirm(selectedFolders, newFolderName);
    closeFolderModal();
  };

  window.closeFolderModal = () => {
    document.body.removeChild(modal);
  };

  // ฟังก์ชันลบโฟลเดอร์ออกจาก selection
  window.removeFolderFromSelection = (btn) => {
    const checkbox = btn.previousElementSibling;
    checkbox.checked = false;
    btn.parentElement.remove(); // ลบ label ทั้งหมด
  };
}

////////////////////////////////////////////////////////////////////////////////////////////////
async function toggleBookmark(productId, buttonElement) {
  const isCurrentlyBookmarked = buttonElement.classList.contains('bookmarked');

  if (isCurrentlyBookmarked) {
    // ✅ แสดง modal จัดการบุ๊กมาร์ก
    showManageBookmarkModal(productId, buttonElement);
  } else {
    // ✅ แสดง modal เลือกโฟลเดอร์ (แบบเดิม)
    if (!USER_ID) {
      alert('⚠️ กรุณาล็อกอินก่อน');
      return;
    }

    const folders = await getUserFolders();
    showFolderSelectionModal(folders, async (selectedFolders, newFolderName) => {
      if (selectedFolders.length === 0 && !newFolderName) {
        alert('กรุณาเลือกโฟลเดอร์อย่างน้อย 1 รายการ');
        return;
      }

      if (newFolderName) {
        try {
          const createRes = await fetch('/H3/api/create_folder.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ user_id: USER_ID, name: newFolderName.trim() })
          });
          const createData = await createRes.json();
          if (createData.success) {
            selectedFolders.push({ id: createData.folder_id, name: newFolderName });
          } else {
            alert('⚠️ ' + createData.message);
            return;
          }
        } catch (e) {
          console.error('Error creating folder:', e);
          alert('⚠️ เกิดข้อผิดพลาดในการสร้างโฟลเดอร์');
          return;
        }
      }

      // ✅ เพิ่มไปยังแต่ละโฟลเดอร์
      for (const folder of selectedFolders) {
        const url = '/H3/api/add_bookmark.php';
        const res = await fetch(url, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ product_id: productId, folder_id: folder.id })
        });
        const data = await res.json();
        if (!data.success) {
          alert('⚠️ ' + data.message);
          return;
        }
      }

      alert('บุ๊กมาร์กสำเร็จ');
      buttonElement.classList.add('bookmarked');
      buttonElement.innerHTML = '<i class="fas fa-bookmark"></i> จัดการ';
    });
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
              ${isBookmarked ? '<i class="fas fa-bookmark"></i> จัดการ' : '<i class="far fa-bookmark"></i> บุ๊กมาร์ก'}
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