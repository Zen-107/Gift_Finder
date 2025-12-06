<?php
require_once "config.php";

// ถ้ายังไม่ล็อกอิน → ส่งกลับไปหน้า login
if (!isset($_SESSION["user_id"])) {
    header("Location: authenticate.php");
    exit;
}

$name = $_SESSION["user_name"] ?? $_SESSION["user_email"];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Gift Finder – Home</title>
</head>
<body>
    <h1>ยินดีต้อนรับ, <?= htmlspecialchars($name) ?></h1>
    <p>นี่คือหน้า Home </p>

    <p><a href="logout.php">Logout</a></p>
</body>
</html>
