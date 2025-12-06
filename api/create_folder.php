<?php
include 'config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'กรุณาล็อกอินก่อน']);
    exit;
}

$user_id = $_SESSION['user_id'];
$name = trim($_POST['name'] ?? $_GET['name'] ?? (json_decode(file_get_contents('php://input'), true)['name'] ?? ''));

if (!$name) {
    echo json_encode(['success' => false, 'message' => 'ชื่อโฟลเดอร์ไม่สามารถเว้นว่างได้']);
    exit;
}

// ห้ามสร้างชื่อว่า Favorite (ถ้ามีอยู่แล้ว)
if (strtolower($name) === 'favorite') {
    echo json_encode(['success' => false, 'message' => 'ไม่สามารถสร้างโฟลเดอร์ชื่อ Favorite ได้']);
    exit;
}

try {
    $pdo->beginTransaction();

    // ตรวจสอบว่ามีโฟลเดอร์ชื่อนี้แล้วหรือไม่สำหรับผู้ใช้นี้
    $stmt = $pdo->prepare("SELECT id FROM bookmark_folders WHERE user_id = ? AND name = ?");
    $stmt->execute([$user_id, $name]);
    $existing = $stmt->fetch();

    if ($existing) {
        // ถ้ามีแล้ว คืน ID เดิม
        echo json_encode(['success' => true, 'message' => 'โฟลเดอร์มีอยู่แล้ว', 'folder_id' => $existing['id']]);
    } else {
        // ถ้ายังไม่มี สร้างใหม่
        $stmt = $pdo->prepare("INSERT INTO bookmark_folders (user_id, name) VALUES (?, ?)");
        $stmt->execute([$user_id, $name]);
        $folder_id = $pdo->lastInsertId();
        echo json_encode(['success' => true, 'message' => 'สร้างโฟลเดอร์สำเร็จ', 'folder_id' => $folder_id]);
    }

    $pdo->commit();
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'เกิดข้อผิดพลาดในการสร้างโฟลเดอร์: ' . $e->getMessage()]);
}
?>