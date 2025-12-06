<?php
include 'config.php';

if (!isset($_SESSION["user_id"])) {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "กรุณาล็อกอินก่อน"]);
    exit;
}

$user_id = $_SESSION["user_id"];

try {
    $stmt = $pdo->prepare("SELECT id, name FROM bookmark_folders WHERE user_id = :user_id ORDER BY created_at DESC");
    $stmt->execute(["user_id" => $user_id]);
    $folders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(["success" => true, "folders" => $folders]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "เกิดข้อผิดพลาด: " . $e->getMessage()]);
}