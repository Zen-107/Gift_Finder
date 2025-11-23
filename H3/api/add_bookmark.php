<?php
include 'config.php'; // แน่ใจว่าไฟล์นี้ไม่มี error และไม่พิมพ์อะไร

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'กรุณาล็อกอินก่อน']);
    exit; // ต้องมี exit หลังจาก echo json และก่อนส่วนอื่น
}

$user_id = $_SESSION['user_id']; // เพิ่มบรรทัดนี้ ไม่ได้กำหนดไว้ก่อนหน้านี้

// แก้ไขตรงนี้เพื่ออ่าน JSON
$input = json_decode(file_get_contents('php://input'), true);
$product_id = intval($input['product_id'] ?? 0);
$folder_id = intval($input['folder_id'] ?? 0); // อาจต้องส่งจาก JS ด้วย
$custom_name = trim($input['custom_name'] ?? '');
$note = trim($input['note'] ?? '');

if ($product_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID สินค้าไม่ถูกต้อง']);
    exit; // ต้องมี exit หลังจาก echo json และก่อนส่วนอื่น
}

// ตรวจสอบว่ามีสินค้านี้จริงไหม
global $pdo; // ใช้ global เพื่อให้แน่ใจว่า $pdo ใช้งานได้
$stmt = $pdo->prepare("SELECT id FROM products WHERE id = ?");
$stmt->execute([$product_id]);
if (!$stmt->fetch()) {
    echo json_encode(['success' => false, 'message' => 'ไม่พบสินค้า']);
    exit; // ต้องมี exit หลังจาก echo json และก่อนส่วนอื่น
}

// ป้องกัน bookmark ซ้ำ
$stmt = $pdo->prepare("SELECT id FROM bookmarks WHERE user_id = ? AND product_id = ?");
$stmt->execute([$user_id, $product_id]);
if ($stmt->fetch()) {
    echo json_encode(['success' => false, 'message' => 'คุณเคยบุ๊กมาร์กสินค้านี้แล้ว']);
    exit; // ต้องมี exit หลังจาก echo json และก่อนส่วนอื่น
}

// เพิ่ม bookmark
$stmt = $pdo->prepare("
    INSERT INTO bookmarks (user_id, product_id, folder_id, custom_name, note)
    VALUES (?, ?, ?, ?, ?)
");
$stmt->execute([$user_id, $product_id, $folder_id ?: null, $custom_name, $note]);

echo json_encode(['success' => true, 'message' => 'บุ๊กมาร์กสำเร็จ']);
// ห้ามมีโค้ดใด ๆ ด้านหลัง echo json_encode นี้
?>