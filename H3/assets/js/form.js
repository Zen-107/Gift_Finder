// helper query สั้น ๆ
const qsa = (sel, parent = document) => Array.from(parent.querySelectorAll(sel));

const FORM_KEY = "gf_criteria";
const RECIPIENTS_KEY = "gf_recipients";

// อ่านรายชื่อบุคคลสำคัญจาก localStorage (เก็บสำรองใน browser)
function loadRecipients() {
  try {
    return JSON.parse(localStorage.getItem(RECIPIENTS_KEY)) || [];
  } catch (e) {
    return [];
  }
}

// เซฟรายชื่อบุคคลสำคัญลง localStorage
function saveRecipients(list) {
  localStorage.setItem(RECIPIENTS_KEY, JSON.stringify(list));
}

// ✅ ฟังก์ชันใหม่: ส่ง profile ไปเก็บในดาต้าเบสผ่าน PHP
async function saveProfileToServer(criteria, extraFields = {}) {
  const formData = new FormData();

  // ข้อมูลพื้นฐานของโปรไฟล์
  formData.append("name", criteria.name || "");
  formData.append("gender", criteria.gender || "");
  formData.append("age", criteria.age || "");
  formData.append("relationship", criteria.relationship || "");

  // interests เป็น array → ต้อง append แบบ interests[]
  if (Array.isArray(criteria.interests)) {
    criteria.interests.forEach((i) => formData.append("interests[]", i));
  }

  // personality ก็เหมือนกัน
  if (Array.isArray(criteria.personality)) {
    criteria.personality.forEach((p) => formData.append("personality[]", p));
  }

  // ถ้ามี field เสริม เช่นสีที่ชอบ / ตัวละครที่ชอบ ก็ส่งเพิ่มได้
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
    // เกิด error ฝั่ง server ก็ยังใช้ localStorage ต่อไปได้
  }
}

function renderInterests() {
  const target = document.getElementById("interests");
  const unique = [
    "Music", "Nature", "Minimalist", "Pets", "Cooking", "Tech", "Fitness"];

  target.innerHTML = unique
    .map(
      (v) =>
        `<label class="pill"><input type="checkbox" value="${v}">${v}</label>`
    )
    .join("");
  target.addEventListener("click", (e) => {
    const pill = e.target.closest(".pill");
    if (pill) pill.classList.toggle("active");
  });
}

document.addEventListener("DOMContentLoaded", () => {
  renderInterests();
  const form = document.getElementById("gift-form");

  // 🔁 เปลี่ยนให้ callback เป็น async เพื่อจะได้ await fetch()
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
      // ⬇ อันนี้คือข้อมูลรวมที่ใช้หา gift รอบนี้
      name: data.get("name") || "",
      gender: data.get("gender") || "",
      age: data.get("age") || "",
      relationship: data.get("relationship") || "",
      interests: selectedInterests,
      personality: selectedPersonality,
      // TODO: ถ้าเพิ่ม occasion, color, character ใน form.html แล้ว
      // ก็อ่านมาจาก data.get() แล้วส่งไปด้วยใน extraFields
    };

    // ถ้าติ๊ก "บันทึกข้อมูลลงในบุคคลสำคัญ"
    const saveProfile = data.get("save_profile") === "on";

    if (saveProfile) {
      // 1) เก็บใน localStorage เหมือนเดิม (optional แต่ช่วยให้ใช้งานออฟไลน์/โหลดเร็ว)
      const recipients = loadRecipients();
      const profile = {
        id: Date.now(), // id ง่าย ๆ ก่อน
        name: criteria.name,
        gender: criteria.gender,
        age: criteria.age,
        relationship: criteria.relationship,
        interests: criteria.interests,
        personality: criteria.personality,
        created_at: new Date().toISOString(),
      };
      recipients.push(profile);
      saveRecipients(recipients);

      // 2) ส่งไปเก็บในดาต้าเบสผ่าน PHP
      // ถ้ามี field เสริม เช่น favorite_color, favorite_character ให้เพิ่มใน extraFields ตรงนี้ได้
      await saveProfileToServer(criteria, {
        // favorite_color: data.get("favorite_color") || "",
        // favorite_character: data.get("favorite_character") || "",
      });
    }

    // 3) เก็บ criteria รอบนี้ลง sessionStorage แล้วไปหน้า results เหมือนเดิม
    sessionStorage.setItem(FORM_KEY, JSON.stringify(criteria));
    window.location.href = "results.html";
  });
});
async function loadRecipientsFromServer() {
  const res = await fetch("api/get_recipients.php");

  const list = await res.json();

  const container = document.getElementById("recipient-list");
  container.innerHTML = list.map(r => `
    <a class="friend-tab">
      <img src="assets/img/default-avatar.png">
      <span>${r.name || '(No name)'}</span>
    </a>
  `).join("");
}

document.addEventListener("DOMContentLoaded", loadRecipientsFromServer);

