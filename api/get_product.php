<?php
header("Content-Type: application/json; charset=utf-8");

// ปิดการแสดง error บน production (แต่ควรแก้ error จริงดีกว่า)
ini_set('display_errors', 0);
error_reporting(E_ALL);

$mysqli = new mysqli("localhost", "root", "", "gift_finder");

// ตั้ง charset ให้ตรงกับฐานข้อมูล
$mysqli->set_charset("utf8mb4");

if ($mysqli->connect_error) {
    http_response_code(500);
    echo json_encode(["error" => "เชื่อมต่อฐานข้อมูลล้มเหลว"]);
    exit;
}

$id = $_GET['id'] ?? null;
if (!$id || !is_numeric($id)) {
    http_response_code(400);
    echo json_encode(["error" => "ID ไม่ถูกต้อง"]);
    exit;
}

$product_id = (int)$id;

// ✅ ดึงข้อมูลสินค้า
$stmt = $mysqli->prepare("SELECT id, name, description, image_url, created_at FROM products WHERE id = ?");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    http_response_code(404);
    echo json_encode(["error" => "ไม่พบสินค้า"]);
    exit;
}

$product = $result->fetch_assoc();

// ✅ ดึงหมวดหมู่ (ใช้ prepared statement ทั้งหมด)
$categories = [];
$catStmt = $mysqli->prepare("
    SELECT c.name 
    FROM categories c
    INNER JOIN product_categories pc ON c.id = pc.category_id
    WHERE pc.product_id = ?
");
$catStmt->bind_param("i", $product_id);
$catStmt->execute();
$catResult = $catStmt->get_result();
while ($row = $catResult->fetch_assoc()) {
    $categories[] = $row['name'];
}

// ✅ ดึงลิงก์ร้านค้า + ช่วงราคา
$externalUrls = [];
$urlStmt = $mysqli->prepare("SELECT url, source_name, min_price, max_price, currency FROM product_external_urls WHERE product_id = ?");
$urlStmt->bind_param("i", $product_id);
$urlStmt->execute();
$urlResult = $urlStmt->get_result();
while ($row = $urlResult->fetch_assoc()) {
    $row['min_price'] = (float)$row['min_price'];
    $row['max_price'] = (float)$row['max_price'];
    $externalUrls[] = $row;
}

// ✅ คำนวณช่วงราคาโดยรวม
$overall_min = null;
$overall_max = null;
$currency = 'THB';

if (!empty($externalUrls)) {
    $min_prices = array_column($externalUrls, 'min_price');
    $max_prices = array_column($externalUrls, 'max_price');
    $overall_min = min($min_prices);
    $overall_max = max($max_prices);
    $currency = $externalUrls[0]['currency'] ?? 'THB';
}

$response = [
    'id' => (int)$product['id'],
    'name' => $product['name'],
    'description' => $product['description'],
    'image_url' => $product['image_url'],
    'created_at' => $product['created_at'],
    'categories' => $categories,
    'external_urls' => $externalUrls,
    'price_range' => [
        'min' => $overall_min,
        'max' => $overall_max,
        'currency' => $currency
    ]
];

echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK);
$mysqli->close();
?>