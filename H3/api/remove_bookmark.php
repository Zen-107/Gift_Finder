<?php
include 'config.php'; // แน่ใจว่าไฟล์นี้ไม่มี error และไม่พิมพ์อะไร

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'กรุณาล็อกอินก่อน']);
    exit; // ต้องมี exit หลังจาก echo json และก่อนส่วนอื่น
}

$user_id = $_SESSION['user_id'];

// แก้ไขตรงนี้เพื่ออ่าน JSON
$input = json_decode(file_get_contents('php://input'), true);
$product_id = intval($input['product_id'] ?? 0);

if ($product_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID สินค้าไม่ถูกต้อง']);
    exit; // ต้องมี exit หลังจาก echo json และก่อนส่วนอื่น
}

$stmt = $pdo->prepare("DELETE FROM bookmarks WHERE user_id = ? AND product_id = ?");
$stmt->execute([$user_id, $product_id]);

if ($stmt->rowCount() > 0) {
    echo json_encode(['success' => true, 'message' => 'ลบบุ๊กมาร์กสำเร็จ']);
} else {
    echo json_encode(['success' => false, 'message' => 'ไม่พบรายการบุ๊กมาร์ก']);
}
// ห้ามมีโค้ดใด ๆ ด้านหลัง echo json_encode นี้
?>