<?php
include  '../../Add_Product_web_for_back/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'กรุณาล็อกอินก่อน']);
    exit;
}

$user_id = $_SESSION['user_id'];
$product_id = intval($_POST['product_id'] ?? 0);

if ($product_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID สินค้าไม่ถูกต้อง']);
    exit;
}

$stmt = $pdo->prepare("DELETE FROM bookmarks WHERE user_id = ? AND product_id = ?");
$stmt->execute([$user_id, $product_id]);

if ($stmt->rowCount() > 0) {
    echo json_encode(['success' => true, 'message' => 'ลบบุ๊กมาร์กสำเร็จ']);
} else {
    echo json_encode(['success' => false, 'message' => 'ไม่พบรายการบุ๊กมาร์ก']);
}