<?php
// api/save_recipient.php

require_once "config.php";

// ให้แน่ใจว่าเริ่ม session แล้ว
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

// ต้องล็อกอินก่อนถึงจะผูกกับ user_id ได้
if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode([
        "status"  => "error",
        "message" => "Not logged in",
    ]);
    exit;
}

$user_id = $_SESSION['user_id'];

// รับข้อมูลจากฟอร์ม
$name         = $_POST["name"] ?? "";
$gender       = $_POST["gender"] ?? "";
$age_range    = $_POST["age"] ?? "";          // map เป็น age_range
$relationship = $_POST["relationship"] ?? "";

// interests / personality ตอนนี้ยังไม่ใช้ใน DB
$interests   = $_POST["interests"]   ?? [];
$personality = $_POST["personality"] ?? [];

if ($name === "" && $relationship === "" && $gender === "" && $age_range === "") {
    echo json_encode([
        "status"  => "error",
        "message" => "no data",
    ]);
    exit;
}

try {
    // เก็บเฉพาะข้อมูลคนลงตาราง gift_recipients
    $stmt = $pdo->prepare("
        INSERT INTO gift_recipients (user_id, name, relationship, gender, age_range)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $user_id,
        $name,
        $relationship,
        $gender,
        $age_range
    ]);

    $recipient_id = $pdo->lastInsertId();

    echo json_encode([
        "status" => "ok",
        "id"     => $recipient_id,
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        "status"  => "error",
        "message" => $e->getMessage(),
    ]);
}
