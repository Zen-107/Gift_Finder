// assets/js/header.js

document.addEventListener('DOMContentLoaded', async () => {
  // ✅ เคลียร์ค่าการกรองทุกครั้งที่กดจาก Navbar ไปหน้า show_all_product
  const navAllProductLinks = document.querySelectorAll('a[href="show_all_product.html"]');

  navAllProductLinks.forEach(link => {
    link.addEventListener("click", () => {
      console.log("🔄 Clear gf_criteria from navbar");
      sessionStorage.removeItem("gf_criteria");
    });
  });

  const loginLink = document.getElementById("login-entry");
  const profileMenu = document.getElementById("profileMenu");
  const profileToggle = document.getElementById("profileToggle");
  const profileDropdown = document.getElementById("profileDropdown");
  const logoutBtn = document.getElementById("logoutBtn");
  loadFriendDropdown();

  // ถ้าไม่มีองค์ประกอบพวกนี้ในหน้านี้ (เช่น หน้า login.html) ให้ออกเลย
  if (!loginLink || !profileMenu || !profileToggle || !profileDropdown || !logoutBtn) {
    return;
  }

  const favLink = document.getElementById("fav-link");
  const friendLink = document.getElementById("friend-link");
  const favDropdown = document.getElementById("favDropdown");
  const friendDropdown = document.getElementById("friendDropdown");

  // ฟังก์ชันซ่อน dropdown ย่อยทั้งหมด
  function hideSubDropdowns() {
    favDropdown.style.display = "none";
    friendDropdown.style.display = "none";
  }

  // === จัดการเมนูโปรไฟล์หลัก ===
  profileToggle.addEventListener("click", (e) => {
    e.stopPropagation();
    profileDropdown.classList.toggle("open");
  });

  document.addEventListener("click", (e) => {
    if (!profileMenu.contains(e.target)) {
      profileDropdown.classList.remove("open");
      hideSubDropdowns();
    }
  });

  // === Favorite Dropdown ===
  favLink?.addEventListener("click", async (e) => {
    e.preventDefault();
    hideSubDropdowns();
    try {
      const response = await fetch('api/get_all_bookmarks.php');
      const data = await response.json();
      if (data.success && data.folders?.length > 0) {
        const folderList = data.folders.map(folder => `
        <a href="folder.php?id=${folder.folder_id}">${folder.folder_name}</a>
      `).join('');
        favDropdown.innerHTML = `<h4 class="dropdown-header">Folders</h4>${folderList}`;
      } else {
        favDropdown.innerHTML = '<h4 class="dropdown-header">Folders</h4><p class="dropdown-message">No folders yet.</p>';
      }
      favDropdown.style.display = "block";
    } catch (err) {
      console.error("Failed to load folders:", err);
      favDropdown.innerHTML = '<h4 class="dropdown-header">Folders</h4><p class="dropdown-message error-message">Error loading folders.</p>';
      favDropdown.style.display = "block";
    }
  });

  // === Friend Dropdown (ตัวอย่าง) ===
  friendLink?.addEventListener("click", async (e) => {
    e.preventDefault();
    hideSubDropdowns();

    try {
      const res = await fetch("api/get_recipients.php");
      const raw = await res.text();
      console.log("get_recipients RAW (header):", raw);

      let friends;
      try {
        friends = JSON.parse(raw);
      } catch (err) {
        console.error("get_recipients ไม่ใช่ JSON:", err);
        friendDropdown.innerHTML = `
        <h4>Friends</h4>
        <p class="dropdown-message">Error loading friends.</p>
      `;
        friendDropdown.style.display = "block";
        return;
      }

      if (!Array.isArray(friends) || friends.length === 0) {
        friendDropdown.innerHTML = `
        <h4>Friends</h4>
        <p class="dropdown-message">No friends yet.</p>
      `;
        friendDropdown.style.display = "block";
        return;
      }

      const friendList = friends
        .map(
          (f) => `
          <a href="form.html?recipient_id=${f.id}" class="dropdown-item">
            ${f.name || "(No name)"}
          </a>
        `
        )
        .join("");

      friendDropdown.innerHTML = `
      <h4>Friends</h4>
      ${friendList}
    `;
      friendDropdown.style.display = "block";
    } catch (err) {
      console.error("Failed to load friends:", err);
      friendDropdown.innerHTML = `
      <h4>Friends</h4>
      <p class="dropdown-message error-message">Error loading friends.</p>
    `;
      friendDropdown.style.display = "block";
    }
  });

  // === Logout ===
  logoutBtn.addEventListener("click", async () => {
    try {
      await fetch("api/logout.php", { method: "POST" });
    } catch (err) {
      console.error("Logout error:", err);
    }
    localStorage.removeItem("user");
    window.location.reload(); // หรือ redirect ไปหน้าหลัก
  });

  // === เช็คสถานะล็อกอิน ===
  try {
    const res = await fetch("api/check_session.php");
    const data = await res.json();
    if (data.loggedIn) {
      loginLink.style.display = "none";
      profileMenu.style.display = "inline-block";
    } else {
      loginLink.style.display = "inline-block";
      loginLink.textContent = "Login";
      loginLink.href = "login.html";
      profileMenu.style.display = "none";
    }
  } catch (err) {
    console.error("check_session error:", err);
    // ถ้า API ล้มเหลว ให้แสดงเป็นยังไม่ล็อกอิน
    loginLink.style.display = "inline-block";
    loginLink.textContent = "Login";
    loginLink.href = "login.html";
    profileMenu.style.display = "none";
  }
  async function loadFriendDropdown() {
    try {
      const res = await fetch("api/get_recipients.php");
      const raw = await res.text();
      console.log("HEADER get_recipients RAW:", raw);

      let list = [];
      try {
        list = JSON.parse(raw);
      } catch (e) {
        console.error("parse JSON error:", e);
        return;
      }

      if (!Array.isArray(list)) list = [];

      const select = document.getElementById("friend-select");
      if (!select) return;

      // เคลียร์ของเก่า
      select.innerHTML = `
      <option value="">-- เลือกบุคคลสำคัญ --</option>
    `;

      // เติมรายชื่อใหม่จาก DB
      select.innerHTML += list
        .map(
          (r) => `
        <option value="${r.id}">
          ${r.name || "(ไม่มีชื่อ)"}
        </option>
      `
        )
        .join("");
    } catch (err) {
      console.error("Error loading friend dropdown:", err);
    }
  }

});