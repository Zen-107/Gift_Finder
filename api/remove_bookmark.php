<?php
include 'config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'กรุณาล็อกอินก่อน']);
    exit;
}

$user_id = $_SESSION['user_id'];
$folder_id = intval($_POST['folder_id'] ?? 0);

if ($folder_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID โฟลเดอร์ไม่ถูกต้อง']);
    exit;
}

// ดึงชื่อโฟลเดอร์
$stmt = $pdo->prepare("SELECT name FROM bookmark_folders WHERE id = ? AND user_id = ?");
$stmt->execute([$folder_id, $user_id]);
$folder = $stmt->fetch();

if (!$folder) {
    echo json_encode(['success' => false, 'message' => 'โฟลเดอร์ไม่พบหรือคุณไม่มีสิทธิ์']);
    exit;
}

if (strtolower($folder['name']) === 'favorite') {
    echo json_encode(['success' => false, 'message' => 'ไม่สามารถลบโฟลเดอร์ Favorite ได้']);
    exit;
}

// ลบ folder และ bookmarks ที่อยู่ใน folder นี้
$stmt = $pdo->prepare("DELETE FROM bookmarks WHERE folder_id = ?");
$stmt->execute([$folder_id]);

$stmt = $pdo->prepare("DELETE FROM bookmark_folders WHERE id = ? AND user_id = ?");
$stmt->execute([$folder_id, $user_id]);

echo json_encode(['success' => true, 'message' => 'ลบโฟลเดอร์สำเร็จ']);
?>