<?php
header("Content-Type: application/json; charset=utf-8");

$mysqli = new mysqli("localhost", "root", "", "gift_finder"); // ปรับชื่อ DB ให้ตรง

if ($mysqli->connect_error) {
    http_response_code(500);
    echo json_encode(["error" => "เชื่อมต่อฐานข้อมูลล้มเหลว"]);
    exit;
}

// รับ id จาก query parameter
$id = $_GET['id'] ?? null;

if (!$id || !is_numeric($id)) {
    http_response_code(400);
    echo json_encode(["error" => "ID ไม่ถูกต้อง"]);
    exit;
}

// ดึงข้อมูลสินค้า
$stmt = $mysqli->prepare("SELECT * FROM products WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    http_response_code(404);
    echo json_encode(["error" => "ไม่พบสินค้า"]);
    exit;
}

$product = $result->fetch_assoc();

// ดึงหมวดหมู่ของสินค้านี้ (ถ้ามี)
$categoryIds = [];
$catResult = $mysqli->query("SELECT category_id FROM product_categories WHERE product_id = $id");
while ($row = $catResult->fetch_assoc()) {
    $categoryIds[] = $row['category_id'];
}

// ดึงชื่อหมวดหมู่
$categories = [];
if (!empty($categoryIds)) {
    $placeholders = str_repeat('?,', count($categoryIds) - 1) . '?';
    $stmt = $mysqli->prepare("SELECT name FROM categories WHERE id IN ($placeholders)");
    $stmt->bind_param(str_repeat('i', count($categoryIds)), ...$categoryIds);
    $stmt->execute();
    $catResult = $stmt->get_result();
    while ($row = $catResult->fetch_assoc()) {
        $categories[] = $row['name'];
    }
}

// ✅ ดึงลิงก์ร้านค้าภายนอก
$externalUrls = [];
$urlStmt = $mysqli->prepare("SELECT url, source_name FROM product_external_urls WHERE product_id = ?");
$urlStmt->bind_param("i", $id);
$urlStmt->execute();
$urlResult = $urlStmt->get_result();
while ($row = $urlResult->fetch_assoc()) {
    $externalUrls[] = $row;
}
$product['external_urls'] = $externalUrls;



// รวมข้อมูลก่อนส่งกลับ
$product['categories'] = $categories;
echo json_encode($product, JSON_UNESCAPED_UNICODE);

$mysqli->close();
?>