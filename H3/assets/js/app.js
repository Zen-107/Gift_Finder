function qs(selector, scope = document) { return scope.querySelector(selector) }
function qsa(selector, scope = document) { return Array.from(scope.querySelectorAll(selector)) }

// Simple router helpers
function getQueryParam(name) {
  const params = new URLSearchParams(window.location.search);
  return params.get(name);
}

// Cart in localStorage
const CART_KEY = "gf_cart";
function readCart() {
  try { return JSON.parse(localStorage.getItem(CART_KEY) || "[]"); } catch { return [] }
}
function writeCart(items) { localStorage.setItem(CART_KEY, JSON.stringify(items)); }
function addToCart(productId, quantity = 1) {
  const cart = readCart();
  const existing = cart.find(i => i.productId === productId);
  if (existing) { existing.quantity += quantity; }
  else { cart.push({ productId, quantity }); }
  writeCart(cart);
}
function removeFromCart(productId) {
  const cart = readCart().filter(i => i.productId !== productId);
  writeCart(cart);
}
function updateQty(productId, quantity) {
  const cart = readCart();
  const it = cart.find(i => i.productId === productId);
  if (it) { it.quantity = Math.max(1, quantity | 0); writeCart(cart); }
}

//document.addEventListener('DOMContentLoaded', renderLayout);

// Slideshow Functionality
document.addEventListener('DOMContentLoaded', () => {
  const slides = document.querySelectorAll('.slide');
  const dots = document.querySelectorAll('.dot');
  const prevBtn = document.querySelector('.prev');
  const nextBtn = document.querySelector('.next');
  const slidesContainer = document.querySelector('.slides-container');

  if (!slidesContainer || slides.length === 0) {
    return;
  }

  let currentIndex = 0;
  const totalSlides = slides.length;

  // ฟังก์ชันแสดงสไลด์
  function showSlide(index) {
    if (index >= totalSlides) {
      currentIndex = 0;
    } else if (index < 0) {
      currentIndex = totalSlides - 1;
    } else {
      currentIndex = index;
    }

    slidesContainer.style.transform = `translateX(-${currentIndex * 100}%)`;

    // อัปเดต dot
    dots.forEach((dot, i) => {
      dot.classList.toggle('active', i === currentIndex);
    });
  }

  // ปุ่ม Next
  if (nextBtn) {
    nextBtn.addEventListener('click', () => {
      showSlide(currentIndex + 1);
    });
  } else { console.warn("No next button found"); }


  // ปุ่ม Prev
  if (prevBtn) {
    prevBtn.addEventListener('click', () => {
      showSlide(currentIndex - 1);
    });
  } else { console.warn("No previous button found"); }

  // คลิกที่ dot
  dots.forEach(dot => {
    dot.addEventListener('click', () => {
      const index = parseInt(dot.getAttribute('data-index'));
      showSlide(index);
    });
  });

  // Auto-play ทุก 5 วินาที
  setInterval(() => {
    showSlide(currentIndex + 1);
  }, 5000);

  // เริ่มต้น
  showSlide(0);
});
