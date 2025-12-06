Gift Finder (Static Frontend)

Overview
- Pure HTML/CSS/JS frontend you can edit later with GrapesJS.
- Uses mock data on the client; cart stored in localStorage.
- MySQL schema is provided for future backend use (you will run only MySQL server in XAMPP). No Apache/PHP is required for this static demo.

How to run locally
1) Open the HTML files directly in your browser (double-click `index.html`).
2) Everything runs client-side; no server is required for the demo.

Create MySQL database in XAMPP
- Open phpMyAdmin from XAMPP (or MySQL CLI) and run the SQL in `sql/schema.sql`.

Files
- `index.html` – Homepage
- `form.html` – Gift Finder form
- `results.html` – Recommended gifts
- `product.html` – Product detail
- `cart.html` – Cart/Checkout
- `stories.html`, `blog-post.html` – Stories list and article
- `about.html`, `contact.html` – About/Contact
- `assets/css/styles.css` – Styles
- `assets/js/data.js` – Mock catalog data
- `assets/js/app.js` – Common UI and cart helpers
- `assets/js/form.js`, `results.js`, `product.js`, `cart.js`, `blog.js` – Page scripts
- `sql/schema.sql` – MySQL schema with English table names

Limitations
- Without a backend, the app cannot query MySQL directly from the browser. The provided schema prepares you for a future server/API.


