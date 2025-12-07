alert("FORM.JS FILE A LOADED");


// helper query
const qsa = (sel, parent = document) => Array.from(parent.querySelectorAll(sel));

const FORM_KEY = "gf_criteria";
// const RECIPIENTS_KEY = "gf_recipients";

// เก็บว่า user คลิกเลือกเพื่อนคนไหน (สำหรับแก้ไข)
let currentFriendId = null;

// ดึงรายชื่อบุคคลสำคัญจาก localStorage
// function loadRecipients() {
//   try {
//     return JSON.parse(localStorage.getItem(RECIPIENTS_KEY)) || [];
//   } catch (e) {
//     return [];
//   }
// }

// เซฟ list บุคคลสำคัญลง localStorage
// function saveRecipients(list) {
//   localStorage.setItem(RECIPIENTS_KEY, JSON.stringify(list));
// }

// ---------------------------------------------------------
// สร้างปุ่ม interests ให้กดได้จริง
// ---------------------------------------------------------
function renderInterests() {
  const target = document.getElementById("interests");

  const categories = [
    { value: "travel & Outdoors", label: "Sports & Outdoors" }, // 👈 ค่าตรงกับ DB
    { value: "Toys & Kids", label: "Toys & Kids" },
    { value: "Beauty & Personal Care", label: "Beauty & Personal Care" },
    { value: "Pets", label: "Pets" },
    { value: "Food, Drinks & Cooking", label: "Food, Drinks & Cooking" },
    { value: "Electronics", label: "Electronics" },
    { value: "Gaming & Accessories", label: "Gaming & Accessories" },
    { value: "Fashion & Jewelry", label: "Fashion & Jewelry" },
    { value: "Stationery & Books", label: "Stationery & Books" },
    { value: "Home & Lifestyle", label: "Home & Lifestyle" },
    { value: "Health & Supplements", label: "Health & Supplements" },
    { value: "Art & Music", label: "Art & Music" },
    { value: "DIY & Crafts", label: "DIY & Crafts" },
  ];

  target.innerHTML = categories
    .map(
      (cat) => `
      <label class="pill">
        <input type="checkbox" value="${cat.value}" />
        ${cat.label}
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
function applyFriendToForm(friend = {}) {
  currentFriendId = friend.id || null;

  const nameInput = document.querySelector('input[name="name"]');
  const genderSel = document.querySelector('select[name="gender"]');
  const ageSel = document.querySelector('select[name="age"]');
  const relSel = document.querySelector('select[name="relationship"]');
  const budgetSel = document.querySelector('select[name="budget"]');
  const recIdInput = document.getElementById("recipient_id");
  const deleteBtn = document.getElementById("deleteFriendBtn");

  if (nameInput) nameInput.value = friend.name || "";
  if (genderSel) genderSel.value = friend.gender ? String(friend.gender) : "";
  if (ageSel) ageSel.value = friend.age ? String(friend.age) : "";
  if (relSel) relSel.value = friend.relationship ? String(friend.relationship) : "";
  if (budgetSel) budgetSel.value = friend.budget ? String(friend.budget) : "";
  if (recIdInput) recIdInput.value = currentFriendId || "";

  if (deleteBtn) {
    deleteBtn.style.display = currentFriendId ? "inline-block" : "none";
  }
}
// ---------------------------------------------------------
// โหลดข้อมูลเพื่อน 1 คนจาก server แล้วเติมลงฟอร์ม
// ---------------------------------------------------------
async function loadRecipientFromServer(id) {
  try {
    const res = await fetch(`api/get_recipient.php?id=${encodeURIComponent(id)}`);
    const raw = await res.text();
    console.log("get_recipient RAW:", raw);

    let data;
    try {
      data = JSON.parse(raw);
    } catch (e) {
      console.error("get_recipient not JSON", e, raw);
      return;
    }

    if (!data) return;

    applyFriendToForm({
      id: data.id,
      name: data.name,
      gender: data.gender,
      age: data.age_range,
      relationship: data.relationship,
      budget: data.budget,
    });
  } catch (err) {
    console.error("loadRecipientFromServer error", err);
  }
}



// ---------------------------------------------------------
// บันทึกข้อมูลโปรไฟล์ไปยัง server (php)
// return true  = บันทึกสำเร็จ
// return false = ไม่สำเร็จ (เช่น ชื่อซ้ำ หรือ error อื่น)
// ---------------------------------------------------------
async function saveProfileToServer(criteria, extraFields = {}) {
  const formData = new FormData();

  formData.append("name", criteria.name || "");
  formData.append("gender", criteria.gender || "");
  formData.append("age", criteria.age || "");
  formData.append("relationship", criteria.relationship || "");

  // categories[]
  if (Array.isArray(criteria.categories)) {
    criteria.categories.forEach((c) => formData.append("categories[]", c));
  }


  // personality[] (ถ้าใช้ในอนาคต)
  // if (Array.isArray(criteria.personality)) {
  //   criteria.personality.forEach((p) => formData.append("personality[]", p));
  // }

  // extra fields (เช่น budget)
  Object.entries(extraFields).forEach(([key, value]) => {
    formData.append(key, value ?? "");
  });
  if (currentFriendId) {
    formData.append("recipient_id", currentFriendId);
  }

  try {
    const res = await fetch("api/save_recipient.php", {
      method: "POST",
      body: formData,
    });

    const raw = await res.text();
    console.log("save_recipient RAW:", raw);

    let json;
    try {
      json = JSON.parse(raw);
    } catch (e) {
      alert("❌ เซิร์ฟเวอร์ตอบกลับไม่ใช่ JSON\n\n" + raw);
      return false;
    }

    console.log("save_recipient result", json);

    if (!json) return false;

    if (json.status === "duplicate") {
      alert("⚠️ มีเพื่อนชื่อนี้อยู่แล้ว");
      return false;
    }

    if (json.status !== "ok") {
      alert("❌ บันทึกบุคคลสำคัญไม่สำเร็จ: " + (json.message || "unknown error"));
      return false;
    }

    return true;
  } catch (err) {
    console.error("Error saving recipient to server", err);
    alert("❌ มีปัญหาในการเชื่อมต่อเซิร์ฟเวอร์");
    return false;
  }
}


// ---------------------------------------------------------
// โหลดรายชื่อเพื่อนจาก server → ใส่ dropdown
// ---------------------------------------------------------
async function loadRecipientsFromServer() {
  try {
    const res = await fetch("api/get_recipients.php");
    const raw = await res.text();
    console.log("get_recipients RAW:", raw);

    let list;
    try {
      list = JSON.parse(raw);
    } catch (e) {
      alert("❌ get_recipients.php ส่งกลับมาไม่ใช่ JSON\n\n" + raw);
      return;
    }

    // ✅ กัน error: ถ้าไม่ใช่ array ให้เปลี่ยนเป็น []
    if (!Array.isArray(list)) {
      console.warn("get_recipients: expected array but got", list);
      list = [];
    }

    const container = document.getElementById("recipient-list");
    if (!container) return;

    container.innerHTML = list
      .map(
        (r) => `
      <a class="friend-tab"
         data-id="${r.id}"
         data-name="${r.name || ""}"
         data-gender="${r.gender_id || ""}"
         data-age="${r.age_range_id || ""}"
         data-relationship="${r.relationship_id || ""}">
         <img src="assets/img/default-avatar.png">
         <span>${r.name || "(No name)"} </span>
      </a>
    `
      )
      .join("");

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
  } catch (err) {
    console.error("Error loading recipients:", err);
  }
}







// ---------------------------------------------------------
// Event: ตอนโหลดหน้า
// ---------------------------------------------------------
document.addEventListener("DOMContentLoaded", () => {
  renderInterests();
  loadRecipientsFromServer();
  const params = new URLSearchParams(window.location.search);
  const ridFromUrl = params.get("recipient_id");
  if (ridFromUrl) {
    loadRecipientFromServer(ridFromUrl);
  }

  const deleteBtn = document.getElementById("deleteFriendBtn");
  if (deleteBtn) {
    deleteBtn.addEventListener("click", async () => {
      if (!currentFriendId) {
        alert("ยังไม่ได้เลือกบุคคลสำคัญ");
        return;
      }
      if (!confirm("ต้องการลบบุคคลสำคัญคนนี้หรือไม่?")) return;

      try {
        const fd = new FormData();
        fd.append("recipient_id", currentFriendId);
        const res = await fetch("api/delete_recipient.php", {
          method: "POST",
          body: fd,
        });
        const raw = await res.text();
        console.log("delete_recipient RAW:", raw);

        let json;
        try {
          json = JSON.parse(raw);
        } catch (e) {
          alert("เซิร์ฟเวอร์ตอบกลับไม่ใช่ JSON\n\n" + raw);
          return;
        }

        if (json.status === "ok") {
          alert("ลบบุคคลสำคัญเรียบร้อยแล้ว ✅");

          // ✅ กลับไปหน้า index ทันที
          window.location.href = "index.html";
        }
        else {
          alert("ลบไม่สำเร็จ: " + (json.message || "ไม่ทราบสาเหตุ"));
        }

      } catch (err) {
        console.error("delete_recipient error", err);
        alert("ลบไม่สำเร็จ (ปัญหาการเชื่อมต่อ)");
      }
    });
  }


  const form = document.getElementById("gift-form");

  // 🎯 submit form
  form.addEventListener("submit", async (e) => {
    e.preventDefault();
    console.log("🔔 SUBMIT HANDLER RUN");

    const data = new FormData(form);

    const selectedInterests = qsa("#interests input:checked").map(
      (i) => i.value
    );

    const criteria = {
      budget: data.get("budget") || "",
      name: data.get("name") || "",
      gender: data.get("gender") || "",
      age: data.get("age") || "",
      relationship: data.get("relationship") || "",
      categories: selectedInterests,

    };
    const saveProfile = data.get("save_profile") === "on";

    if (saveProfile) {
      // ไม่ต้องเก็บ localStorage แล้วก็ได้ ถ้าใช้ DB อย่างเดียว
      // const recipients = loadRecipients();
      // recipients.push({ ... });
      // saveRecipients(recipients);

      const ok = await saveProfileToServer(criteria, {
        budget: criteria.budget || "",
      });

      if (!ok) {
        // ถ้าบันทึกไม่สำเร็จ (เช่น ชื่อซ้ำ) → ไม่ต้องไปหน้า results
        return;
      }

      // ถ้าบันทึกสำเร็จ reset currentFriendId
      currentFriendId = null;
      alert("✅ บันทึกบุคคลสำคัญเรียบร้อยแล้ว");
    }

    // ส่ง criteria ไปหน้า results ตามปกติ
    sessionStorage.setItem(FORM_KEY, JSON.stringify(criteria));
    // ➜ ใส่พารามิเตอร์ filtered=1 บอกว่ามาจาก "การค้นหา"
    window.location.href = "show_all_product.html?filtered=1";
  });


});
