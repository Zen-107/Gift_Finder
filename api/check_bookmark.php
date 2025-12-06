<?php
include 'config.php';

if (!isset($_SESSION["user_id"])) {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "กรุณาล็อกอินก่อน"]);
    exit;
}

$user_id = $_SESSION["user_id"];

$product_id = intval($_GET['product_id'] ?? 0);

if ($product_id <= 0) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "ID สินค้าไม่ถูกต้อง"]);
    exit;
}

// ตรวจสอบว่ามีบุ๊กมาร์กในสินค้านี้หรือไม่ (ทุก folder)
$stmt = $pdo->prepare("SELECT id FROM bookmarks WHERE user_id = ? AND product_id = ?");
$stmt->execute([$user_id, $product_id]);
$isBookmarked = $stmt->fetch() ? true : false;

echo json_encode(["success" => true, "isBookmarked" => $isBookmarked]);