<?php
// api/search_products.php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/config.php'; // ให้แน่ใจว่า $pdo พร้อมใช้งาน

if (!isset($pdo) || !($pdo instanceof PDO)) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'DB not ready']);
    exit;
}

// --------------------------------------------------
// 1) รับ JSON criteria จากฝั่ง JS
// --------------------------------------------------
$raw  = file_get_contents('php://input');
$data = json_decode($raw, true);

if (!is_array($data)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid JSON']);
    exit;
}

// criteria ที่ส่งมาจาก show_all_product.js / form.js
$budgetId   = isset($data['budget']) && $data['budget'] !== '' ? (int)$data['budget'] : null;
$categories = isset($data['categories']) && is_array($data['categories']) ? $data['categories'] : [];

// (ตอนนี้ยังไม่ใช้ gender / age / relationship ในการกรองสินค้า
//  แต่ยังส่งมาได้ เผื่อไว้ใช้ในอนาคต)
$genderId   = isset($data['gender']) && $data['gender'] !== '' ? (int)$data['gender'] : null;
$ageRangeId = isset($data['age']) && $data['age'] !== '' ? (int)$data['age'] : null;
$relId      = isset($data['relationship']) && $data['relationship'] !== '' ? (int)$data['relationship'] : null;

// --------------------------------------------------
// 2) สร้าง SQL ดึงสินค้าตาม budget + หมวดหมู่
//    ใช้โครงจาก schema.sql:
//    - products
//    - product_budgets (product_id, budget_id)
//    - product_categories (product_id, category_id)
//    - categories (id, name)
// --------------------------------------------------
// --------------------------------------------------
// 3) สร้าง SQL ดึงสินค้าตามหมวดหมู่ (ยังไม่ใช้ budget)
// --------------------------------------------------
$sql = "
    SELECT DISTINCT p.*
    FROM products p
    LEFT JOIN product_categories pc
        ON p.id = pc.product_id
    LEFT JOIN categories c
        ON pc.category_id = c.id
    WHERE 1 = 1
";

$params = [];

// ❌ ตอนนี้ยังไม่ filter ตาม budget เพราะยังไม่มีตาราง product_budgets
// if ($budgetId !== null) {
//     $sql      .= " AND pb.budget_id = ? ";
//     $params[] = $budgetId;
// }

// ✅ กรองตามหมวดหมู่ (ชื่อ category จาก checkbox)
if (!empty($categories)) {
    $categories = array_map('trim', $categories);

    $placeholders = implode(',', array_fill(0, count($categories), '?'));
    $sql .= " AND c.name IN ($placeholders) ";

    foreach ($categories as $catName) {
        $params[] = $catName;
    }
}

$sql .= " ORDER BY p.created_at DESC ";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($products);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'status'  => 'error',
        'message' => $e->getMessage(),
    ]);
}
