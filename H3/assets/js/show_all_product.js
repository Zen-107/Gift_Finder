// assets/js/show_all_product.js

const FORM_KEY = "gf_criteria";

document.addEventListener("DOMContentLoaded", () => {
  const critEl = document.getElementById("criteria");
  const grid = document.getElementById("results");
  const empty = document.getElementById("empty");

  // ---------------------------
  // helper: แสดงสินค้า
  // ---------------------------
  function renderProducts(products) {
    if (!Array.isArray(products) || products.length === 0) {
      empty.style.display = "block";
      grid.innerHTML = "";
      return;
    }

    empty.style.display = "none";

    grid.innerHTML = products
      .map((p) => {
        // แสดงช่วงราคา ถ้ามี
        let priceText = "—";
        if (p.min_price !== null && p.max_price !== null) {
          const min = parseFloat(p.min_price);
          const max = parseFloat(p.max_price);
          const currency = p.currency || "THB";
          if (!Number.isNaN(min) && !Number.isNaN(max)) {
            if (min === max) {
              priceText =
                min.toLocaleString(undefined, {
                  minimumFractionDigits: 2,
                  maximumFractionDigits: 2,
                }) +
                " " +
                currency;
            } else {
              priceText =
                min.toLocaleString(undefined, {
                  minimumFractionDigits: 2,
                  maximumFractionDigits: 2,
                }) +
                " – " +
                max.toLocaleString(undefined, {
                  minimumFractionDigits: 2,
                  maximumFractionDigits: 2,
                }) +
                " " +
                currency;
            }
          }
        }

        const badge =
          (Array.isArray(p.categories) && p.categories[0]) || "Gift";

        return `
          <div class="card">
            <img src="${p.image_url || "assets/images/placeholder.png"}"
                 alt="${p.name}"
                 onerror="this.onerror=null; this.src='assets/images/placeholder.png';">
            <div class="card-body">
              <div class="badge">${badge}</div>
              <strong>${p.name}</strong>
              <div class="price">${priceText}</div>
              <div>${p.description ? p.description.substring(0, 100) + "..." : ""
          }</div>
              <div class="stack">
                <a class="btn" href="product.php?id=${p.id}">View details</a>
              </div>
            </div>
          </div>
        `;
      })
      .join("");
  }

  // ---------------------------
  // 1) อ่าน criteria จาก sessionStorage
  // ---------------------------
  let criteria = null;
  try {
    const raw = sessionStorage.getItem(FORM_KEY);
    if (raw) {
      criteria = JSON.parse(raw);
      console.log("🔍 criteria from sessionStorage:", criteria);
    }
  } catch (e) {
    console.warn("Cannot parse criteria JSON:", e);
  }

  // ---------------------------
  // 2) ดูว่า URL มี ?filtered=1 มั้ย
  // ---------------------------
  const params = new URLSearchParams(window.location.search);
  const fromFilter = params.get("filtered") === "1";

  // ---------------------------
  // 3) ถ้ามาจากการกรอง → เรียก search_products
  //    ถ้าไม่ใช่ → แสดงสินค้าทั้งหมด
  // ---------------------------
  if (
    fromFilter &&
    criteria &&
    (Array.isArray(criteria.categories) ||
      Array.isArray(criteria.interests))
  ) {
    const selectedCategories =
      criteria.categories && criteria.categories.length
        ? criteria.categories
        : criteria.interests || [];

    critEl.textContent = "ผลลัพธ์ที่กรองตามหมวดหมู่ที่เลือก";

    fetch("api/search_products.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        budget: criteria.budget || "",
        gender: criteria.gender || "",
        age: criteria.age || "",
        relationship: criteria.relationship || "",
        categories: selectedCategories,
      }),
    })
      .then((response) => {
        if (!response.ok) {
          throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
      })
      .then((products) => {
        console.log("Filtered products:", products);
        renderProducts(products);
        // ใช้เสร็จแล้ว จะล้างเกณฑ์ทิ้งก็ได้ กันค้าง
        // sessionStorage.removeItem(FORM_KEY);
      })
      .catch((error) => {
        console.error("Error loading filtered products:", error);
        critEl.textContent = "เกิดข้อผิดพลาดในการโหลดสินค้าที่กรองแล้ว";
        empty.textContent = "ไม่สามารถโหลดข้อมูลสินค้าได้";
        empty.style.display = "block";
      });
  } else {
    // กรณีไม่ได้มาจากการกรอง → แสดงสินค้าทั้งหมด
    critEl.textContent = "แสดงสินค้าทั้งหมด";

    fetch("api/get_all_product.php")
      .then((response) => {
        if (!response.ok) {
          throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
      })
      .then((products) => {
        console.log("All products:", products);
        renderProducts(products);
      })
      .catch((error) => {
        console.error("Error loading products:", error);
        critEl.textContent = "เกิดข้อผิดพลาดในการโหลดสินค้า";
        empty.textContent = "ไม่สามารถโหลดข้อมูลสินค้าได้";
        empty.style.display = "block";
      });
  }
});
