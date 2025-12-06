<<?php
// api/authenticate.php
require_once "config.php";

$email    = trim($_POST["email"] ?? "");
$password = $_POST["password"] ?? "";

if (!$email || !$password) {
    header("Location: ../login.html?error=invalid");
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
$stmt->execute(["email" => $email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    header("Location: ../login.html?error=invalid");
    exit;
}

// ใช้ password_verify เทียบกับ password_hash ในฐานข้อมูล
if (!empty($user["password_hash"]) && password_verify($password, $user["password_hash"])) {
    // ตั้ง session แล้วเด้งไปหน้า index
    $_SESSION["user_id"]    = $user["id"];
    $_SESSION["user_email"] = $user["email"];
    $_SESSION["user_name"]  = $user["name"];

    header("Location: ../index.html");
    exit;
} else {
    header("Location: ../login.html?error=invalid");
    exit;
}
