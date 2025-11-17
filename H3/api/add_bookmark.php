<?php
include  '../../Add_Product_web_for_back/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'กรุณาล็อกอินก่อน']);
    exit;
}

$user_id = $_SESSION['user_id'];
$product_id = intval($_POST['product_id'] ?? 0);
$folder_id = intval($_POST['folder_id'] ?? 0); // อาจเป็น 0 หรือ NULL
$custom_name = trim($_POST['custom_name'] ?? '');
$note = trim($_POST['note'] ?? '');

if ($product_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID สินค้าไม่ถูกต้อง']);
    exit;
}

// ตรวจสอบว่ามีสินค้านี้จริงไหม
$stmt = $pdo->prepare("SELECT id FROM products WHERE id = ?");
$stmt->execute([$product_id]);
if (!$stmt->fetch()) {
    echo json_encode(['success' => false, 'message' => 'ไม่พบสินค้า']);
    exit;
}

// ป้องกัน bookmark ซ้ำ (ตามที่เราแนะนำ)
$stmt = $pdo->prepare("SELECT id FROM bookmarks WHERE user_id = ? AND product_id = ?");
$stmt->execute([$user_id, $product_id]);
if ($stmt->fetch()) {
    echo json_encode(['success' => false, 'message' => 'คุณเคยบุ๊กมาร์กสินค้านี้แล้ว']);
    exit;
}

// เพิ่ม bookmark
$stmt = $pdo->prepare("
    INSERT INTO bookmarks (user_id, product_id, folder_id, custom_name, note)
    VALUES (?, ?, ?, ?, ?)
");
$stmt->execute([$user_id, $product_id, $folder_id ?: null, $custom_name, $note]);

echo json_encode(['success' => true, 'message' => 'บุ๊กมาร์กสำเร็จ']);