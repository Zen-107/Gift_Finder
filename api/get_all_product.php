<?php
header("Content-Type: application/json; charset=utf-8");

// ปิดการแสดง error
ini_set('display_errors', 1);
error_reporting(E_ALL);

$mysqli = new mysqli("localhost", "root", "", "gift_finder");
$mysqli->set_charset("utf8mb4");

if ($mysqli->connect_error) {
    http_response_code(500);
    echo json_encode(["error" => "เชื่อมต่อฐานข้อมูลล้มเหลว"]);
    exit;
}


$result = $mysqli->query("
    SELECT id, name, description, image_url, created_at
    FROM products
    ORDER BY id DESC
");

$products = [];
while ($row = $result->fetch_assoc()) {
    $productId = $row['id'];

    // ดึงหมวดหมู่
    $categories = [];
    $catStmt = $mysqli->prepare("
        SELECT c.name 
        FROM categories c
        JOIN product_categories pc ON c.id = pc.category_id
        WHERE pc.product_id = ?
    ");
    $catStmt->bind_param("i", $productId);
    $catStmt->execute();
    $catResult = $catStmt->get_result();
    while ($cat = $catResult->fetch_assoc()) {
        $categories[] = $cat['name'];
    }

    // ✅ ดึง external URLs + ช่วงราคา
    $externalUrls = [];
    $minPriceOverall = null;
    $maxPriceOverall = null;
    $currency = 'THB';

    $urlStmt = $mysqli->prepare("SELECT url, source_name, min_price, max_price, currency FROM product_external_urls WHERE product_id = ?");
    $urlStmt->bind_param("i", $productId);
    $urlStmt->execute();
    $urlResult = $urlStmt->get_result();
    while ($urlRow = $urlResult->fetch_assoc()) {
        $urlRow['min_price'] = (float)$urlRow['min_price'];
        $urlRow['max_price'] = (float)$urlRow['max_price'];
        $externalUrls[] = $urlRow;

        if ($minPriceOverall === null || $urlRow['min_price'] < $minPriceOverall) {
            $minPriceOverall = $urlRow['min_price'];
        }
        if ($maxPriceOverall === null || $urlRow['max_price'] > $maxPriceOverall) {
            $maxPriceOverall = $urlRow['max_price'];
        }
        if ($currency === 'THB' && isset($urlRow['currency'])) {
            $currency = $urlRow['currency'];
        }
    }

    $products[] = array_merge($row, [
        'categories' => $categories,
        'external_urls' => $externalUrls,
        'min_price' => $minPriceOverall,
        'max_price' => $maxPriceOverall,
        'currency' => $currency
    ]);
}

echo json_encode($products, JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK);
$mysqli->close();
?>