<?php
include 'config.php';

if (!isset($_SESSION["user_id"])) {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "กรุณาล็อกอินก่อน"]);
    exit;
}

$user_id = $_SESSION["user_id"];

$data = json_decode(file_get_contents('php://input'), true);
$product_id = intval($data['product_id'] ?? 0);
$folder_id = intval($data['folder_id'] ?? 0);
$custom_name = trim($data['custom_name'] ?? '');
$note = trim($data['note'] ?? '');

if ($product_id <= 0) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "ID สินค้าไม่ถูกต้อง"]);
    exit;
}

// ตรวจสอบว่ามีสินค้านี้จริงไหม
$stmt = $pdo->prepare("SELECT id FROM products WHERE id = ?");
$stmt->execute([$product_id]);
if (!$stmt->fetch()) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "ไม่พบสินค้า"]);
    exit;
}

if ($folder_id > 0) {
    // ตรวจสอบว่า folder นี้เป็นของผู้ใช้จริงไหม
    $stmt = $pdo->prepare("SELECT id FROM bookmark_folders WHERE id = ? AND user_id = ?");
    $stmt->execute([$folder_id, $user_id]);
    if (!$stmt->fetch()) {
        http_response_code(403);
        echo json_encode(["success" => false, "message" => "โฟลเดอร์ไม่ถูกต้อง"]);
        exit;
    }

    // ตรวจสอบว่าซ้ำใน folder เดิมหรือไม่
    $stmt = $pdo->prepare("SELECT id FROM bookmarks WHERE user_id = ? AND product_id = ? AND folder_id = ?");
    $stmt->execute([$user_id, $product_id, $folder_id]);
    if ($stmt->fetch()) {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "สินค้านี้อยู่ในโฟลเดอร์นี้แล้ว"]);
        exit;
    }
}

// เพิ่มบุ๊กมาร์ก
$stmt = $pdo->prepare("
    INSERT INTO bookmarks (user_id, product_id, folder_id)
    VALUES (?, ?,  ?)
");
$stmt->execute([$user_id, $product_id, $folder_id ?: null]);

echo json_encode(["success" => true, "message" => "บุ๊กมาร์กสำเร็จ"]);

