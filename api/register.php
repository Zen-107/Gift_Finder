<?php
// api/register.php
require_once "config.php";

// รับข้อมูลจากฟอร์ม
$name             = trim($_POST["name"] ?? "");
$email            = trim($_POST["email"] ?? "");
$password         = $_POST["password"] ?? "";
$passwordConfirm  = $_POST["password_confirm"] ?? "";

// ถ้าข้อมูลไม่ครบ ให้เด้งกลับ (กันกรณีส่งตรงโดยไม่ผ่านฟอร์ม)
if (!$name || !$email || !$password || !$passwordConfirm) {
    header("Location: ../register.html?error=invalid");
    exit;
}

// เช็ครหัสผ่านว่าตรงกันไหม
if ($password !== $passwordConfirm) {
    header("Location: ../register.html?error=password_mismatch");
    exit;
}

// ถ้าอยากบังคับความยาวอย่างน้อย 6 ตัว
if (strlen($password) < 6) {
    header("Location: ../register.html?error=invalid");
    exit;
}

try {
    // เช็คว่าอีเมลซ้ำหรือยัง
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email LIMIT 1");
    $stmt->execute(["email" => $email]);
    $existingUser = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existingUser) {
        // ถ้ามี email นี้แล้ว
        header("Location: ../register.html?error=email_exists");
        exit;
    }

    // เข้ารหัส password ก่อนเก็บ (ปลอดภัยกว่ามาก)
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    // บันทึกลงฐานข้อมูล
    $insert = $pdo->prepare(
        "INSERT INTO users (email, name, password_hash) 
         VALUES (:email, :name, :password_hash)"
    );
    $insert->execute([
        "email"         => $email,
        "name"          => $name,
        "password_hash" => $passwordHash
    ]);

    // จะให้ login ให้อัตโนมัติเลยก็ได้ หรือให้กลับไปหน้า login พร้อมข้อความ success
    // แบบนี้จะส่ง success=1 ไปที่ register.html
    header("Location: ../login.html?success=1");
    exit;

} catch (PDOException $e) {
    // ถ้า error อะไรไม่คาดคิด ให้ redirect กลับไปพร้อม error
    // (ในงานจริงควร log error ไว้ แต่อันนี้เอาสั้น ๆ)
    header("Location: ../register.html?error=db");
    exit;
}
