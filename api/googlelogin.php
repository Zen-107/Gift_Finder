<?php
session_start();
require_once "config.php";

// token ที่ Google ส่งมาในฟอร์มชื่อ credential
$idToken = $_POST["credential"] ?? null;
if (!$idToken) {
    // ❗ เดิม: login.php อยู่ในโฟลเดอร์ api (ซึ่งไม่มี)
//  header("Location: login.php?error=google");
    // 👉 แก้ให้ชี้ไปที่หน้า login จริง: /H3/login.html
    header("Location: /H3/login.html?error=google");
    exit;
}

// ยิงไปหา Google เพื่อตรวจสอบความถูกต้องของ token
$verifyUrl = "https://oauth2.googleapis.com/tokeninfo?id_token=" . urlencode($idToken);
$response = @file_get_contents($verifyUrl);

if (!$response) {
    header("Location: /H3/login.html?error=google");
    exit;
}

$data = json_decode($response, true);

// ตรวจว่า aud (Audience) ตรงกับ client id ของเราไหม
$clientId = "606004835290-q0s9uhm0jgicbh2l2d4tfrnasp6q5fn5.apps.googleusercontent.com";
if (($data["aud"] ?? "") !== $clientId) {
    header("Location: /H3/login.html?error=google");
    exit;
}

// ดึงข้อมูลจาก token
$googleId = $data["sub"];             // id เฉพาะของ user ใน Google
$email    = $data["email"] ?? null;
$name     = $data["name"] ?? $email;

if (!$email) {
    header("Location: /H3/login.html?error=google");
    exit;
}

// หา user ตาม google_id หรือ email
$stmt = $pdo->prepare(
    "SELECT * FROM users WHERE google_id = :gid OR email = :email LIMIT 1"
);
$stmt->execute(["gid" => $googleId, "email" => $email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user) {
    // ถ้ามีอยู่แล้วแต่ยังไม่มี google_id → ผูกให้
    if (empty($user["google_id"])) {
        $upd = $pdo->prepare("UPDATE users SET google_id = :gid WHERE id = :id");
        $upd->execute(["gid" => $googleId, "id" => $user["id"]]);
    }
    $userId = $user["id"];
} else {
    // ยังไม่เคยมี user นี้ → สมัครอัตโนมัติ
    $ins = $pdo->prepare(
        "INSERT INTO users (email, name, google_id) VALUES (:email, :name, :gid)"
    );
    $ins->execute(["email" => $email, "name" => $name, "gid" => $googleId]);
    $userId = $pdo->lastInsertId();
}

// ตั้ง session
$_SESSION["user_id"]    = $userId;
$_SESSION["user_email"] = $email;
$_SESSION["user_name"]  = $name;


/////////////////////////////////////////////////// just checking ///////////////////////////////////////////////////
/*
echo "<pre>";
print_r($_SESSION);
echo "</pre>";
exit;
*/
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////





header("Location: /H3/index.html");
exit;
