<?php
session_start();
$user_id = $_SESSION["user_id"] ?? null;
$user_name = $_SESSION["user_name"] ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1"/>
  <title>Gift Finder – Product</title>
  <link rel="stylesheet" href="assets/css/global.css"/>
  <link rel="stylesheet" href="assets/css/product.css"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

  <!-- ✅ เพิ่ม header แบบเต็ม -->
  <header class="navbar">
    <div class="container nav-content">
      <a class="logo" href="index.html">🎁 Gift Finder</a>
      <nav class="nav-links">
        <a href="form.html">Find Gifts</a>
        <a href="blog.html">Blog</a>
        <a href="show_all_product.html">Product</a>
        <!-- Login / Profile -->
        <a id="login-entry" href="login.html">Login</a>
        <div class="profile-menu" id="profileMenu" style="display: none;">
          <button class="profile-toggle" id="profileToggle">Profile ▾</button>
          <div class="profile-dropdown" id="profileDropdown">
            <a href="#" id="fav-link">Favorite</a>
            <a href="#" id="friend-link">Friend</a>
            <button id="logoutBtn" type="button">Logout</button>
            <div class="sub-dropdown" id="favDropdown" style="display: none;"></div>
            <div class="sub-dropdown" id="friendDropdown" style="display: none;"></div>
          </div>
        </div>
      </nav>
    </div>
  </header>

  <main class="container section" id="product"></main>
<footer class="footer">
    <div class="footer-container">

      <div class="footer-columns">
        <div class="footer-col">
          <h4>ข้อมูล</h4>
          <a href="#">เกี่ยวกับเรา</a>
          <a href="#">การเข้าร่วม</a>
          <a href="#">นโยบายความเป็นส่วนตัว</a>
          <a href="#">เงื่อนไขการใช้งาน</a>
        </div>

        <div class="footer-col">
          <h4>ช่วยเหลือ</h4>
          <a href="#">การจัดส่ง</a>
          <a href="#">การคืนสินค้า</a>
          <a href="#">คำถามที่พบบ่อย</a>
        </div>

        <div class="footer-col">
          <h4>ฝ่ายบริการ</h4>
          <a href="#">ติดต่อเรา</a>
          <a href="#">ศูนย์ช่วยเหลือ</a>
        </div>

        <div class="footer-col">
          <h4>ติดตามเรา</h4>
          <div class="social-icons">
            <i>📘</i>
            <i>📷</i>
            <i>▶️</i>
            <i>🐦</i>
          </div>
        </div>
      </div>

      <div class="footer-copy">
        © 2025 Gift Finder
      </div>

    </div>
  </footer>


  <script src="assets/js/app.js"></script>
  <script>
    const USER_ID = <?php echo $user_id ? json_encode($user_id) : 'null'; ?>;
    const USER_NAME = <?php echo $user_name ? json_encode($user_name) : 'null'; ?>;
  </script>
  <script src="assets/js/product.js"></script>
  <script src="assets/js/header.js"></script>
</body>
</html>