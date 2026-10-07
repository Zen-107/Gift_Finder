<?php
// ให้ /H3/ เปิด index.html ได้บนเซิร์ฟเวอร์ที่หา index.php ก่อน (เช่น Caddy ของ CAMPP)
readfile(__DIR__ . '/index.html');
