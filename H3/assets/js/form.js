// helper query
const qsa = (sel, parent = document) => Array.from(parent.querySelectorAll(sel));

const FORM_KEY = "gf_criteria";
const RECIPIENTS_KEY = "gf_recipients";

// เก็บว่า user คลิกเลือกเพื่อนคนไหน (สำหรับแก้ไข)
let currentFriendId = null;

// ดึงรายชื่อบุคคลสำคัญจาก localStorage
function loadRecipients() {
  try {
    return JSON.parse(localStorage.getItem(RECIPIENTS_KEY)) || [];
  } catch (e) {
    return [];
  }
}

// เซฟ list บุคคลสำคัญลง localStorage
function saveRecipients(list) {
  localStorage.setItem(RECIPIENTS_KEY, JSON.stringify(list));
}

// ---------------------------------------------------------
// สร้างปุ่ม interests ให้กดได้จริง
// ---------------------------------------------------------
function renderInterests() {
  const target = document.getElementById("interests");
  const unique = [

    "Sports & Outdoors",

    "Toys & Kids",

    "Beauty & Personal Care",

    "Pets",

    "Food, Drinks & Cooking",

    "Electronics",

    "Gaming & Accessories",

    "Fashion & Jewelry",

    "Stationery & Books",

    "Home & Lifestyle",

    "Health & Supplements",

    "Art & Music",

    "DIY & Crafts",
  ];

  target.innerHTML = unique
    .map(
      (v) => `
      <label class="pill">
        <input type="checkbox" value="${v}" />
        ${v}
      </label>
    `
    )
    .join("");

  target.addEventListener("click", (e) => {
    const pill = e.target.closest(".pill");
    if (pill) pill.classList.toggle("active");
  });
}

// ---------------------------------------------------------
// เวลา user คลิกชื่อเพื่อน → เติมข้อมูลลงฟอร์ม
// ---------------------------------------------------------
function applyFriendToForm(friend) {
  currentFriendId = friend.id || null;

  const nameInput = document.querySelector('input[name="name"]');
  const genderSel = document.querySelector('select[name="gender"]');
  const ageSel = document.querySelector('select[name="age"]');
  const relSel = document.querySelector('select[name="relationship"]');

  if (nameInput) nameInput.value = friend.name || "";
  if (genderSel && friend.gender) genderSel.value = friend.gender;
  if (ageSel && friend.age) ageSel.value = friend.age;
  if (relSel && friend.relationship) relSel.value = friend.relationship;
}

// ---------------------------------------------------------
// บันทึกข้อมูลโปรไฟล์ไปยัง server (php)
// ---------------------------------------------------------
async function saveProfileToServer(criteria, extraFields = {}) {
  const formData = new FormData();

  formData.append("name", criteria.name || "");
  formData.append("gender", criteria.gender || "");
  formData.append("age", criteria.age || "");
  formData.append("relationship", criteria.relationship || "");

  // interest[]
  if (Array.isArray(criteria.interests)) {
    criteria.interests.forEach((i) => formData.append("interests[]", i));
  }

  // personality[]
  if (Array.isArray(criteria.personality)) {
    criteria.personality.forEach((p) => formData.append("personality[]", p));
  }

  // ถ้าแก้เพื่อนเดิม → ส่ง id ไปด้วย
  if (currentFriendId) {
    formData.append("recipient_id", currentFriendId);
  }

  // extra fields (ถ้ามี)
  Object.entries(extraFields).forEach(([key, value]) => {
    formData.append(key, value ?? "");
  });

  try {
    const res = await fetch("api/save_recipient.php", {
      method: "POST",
      body: formData,
    });
    const json = await res.json();
    console.log("save_recipient result", json);
  } catch (err) {
    console.error("Error saving recipient to server", err);
  }
}

// ---------------------------------------------------------
// โหลดรายชื่อเพื่อนจาก server → ใส่ dropdown
// ---------------------------------------------------------
async function loadRecipientsFromServer() {
  const res = await fetch("api/get_recipients.php");
  const list = await res.json();

  const container = document.getElementById("recipient-list");
  container.innerHTML = list
    .map(
      (r) => `
    <a class="friend-tab"
       data-id="${r.id}"
       data-name="${r.name || ''}"
       data-gender="${r.gender || ''}"
       data-age="${r.age_range || ''}"
       data-relationship="${r.relationship || ''}">
       <img src="assets/img/default-avatar.png">
       <span>${r.name || "(No name)"} </span>
    </a>
  `
    )
    .join("");

  // ผูก event → คลิกแล้วเติมฟอร์ม
  container.querySelectorAll(".friend-tab").forEach((tab) => {
    tab.addEventListener("click", () => {
      const d = tab.dataset;
      applyFriendToForm({
        id: d.id,
        name: d.name,
        gender: d.gender,
        age: d.age,
        relationship: d.relationship,
      });
    });
  });
}

// ---------------------------------------------------------
// Event: ตอนโหลดหน้า
// ---------------------------------------------------------
document.addEventListener("DOMContentLoaded", () => {
  renderInterests();
  loadRecipientsFromServer();

  const form = document.getElementById("gift-form");

  // 🎯 submit form
  form.addEventListener("submit", async (e) => {
    e.preventDefault();

    const data = new FormData(form);

    const selectedInterests = qsa("#interests input:checked").map(
      (i) => i.value
    );
    const selectedPersonality = qsa("#personality input:checked").map(
      (i) => i.value
    );

    const criteria = {
      budget: data.get("budget") || "",
      name: data.get("name") || "",
      gender: data.get("gender") || "",
      age: data.get("age") || "",
      relationship: data.get("relationship") || "",
      interests: selectedInterests,
      personality: selectedPersonality,
      reason: data.get("reason") || "",
    };

    // ต้องบันทึกโปรไฟล์ไหม?
    const saveProfile = data.get("save_profile") === "on";

    if (saveProfile) {
      const recipients = loadRecipients();
      recipients.push({
        id: Date.now(),
        name: criteria.name,
        gender: criteria.gender,
        age: criteria.age,
        relationship: criteria.relationship,
        interests: criteria.interests,
        personality: criteria.personality,
        created_at: new Date().toISOString(),
      });
      saveRecipients(recipients);

      await saveProfileToServer(criteria);
    }

    // ส่ง criteria ไปรัน results.html
    sessionStorage.setItem(FORM_KEY, JSON.stringify(criteria));
    window.location.href = "results.html";
  });
});
