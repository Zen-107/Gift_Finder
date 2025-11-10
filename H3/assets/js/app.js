function qs(selector, scope = document) {
  return scope.querySelector(selector);
}
function qsa(selector, scope = document) {
  return Array.from(scope.querySelectorAll(selector));
}

// Simple router helpers
function getQueryParam(name) {
  const params = new URLSearchParams(window.location.search);
  return params.get(name);
}

// Common header/footer (ปรับแล้ว — ไม่มี Cart)
function renderLayout() {
  const header = document.createElement('header');
  header.innerHTML = `
    <div class="container">
      <div class="nav">
        <a class="brand" href="index.html">Gift Finder</a>
        <nav>
          <a href="form.html">Find Gift</a>
          <a href="results.html">Results</a>
          <a href="about.html">About</a>
          <a href="contact.html">Contact</a>
        </nav>
      </div>
    </div>`;
  document.body.prepend(header);

  const footer = document.createElement('div');
  footer.className = 'footer';
  footer.innerHTML = `<div class="container">© ${new Date().getFullYear()} Gift Finder</div>`;
  document.body.appendChild(footer);
}

document.addEventListener('DOMContentLoaded', renderLayout);