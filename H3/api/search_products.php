<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

header('Content-Type: application/json; charset=utf-8');

// =======================
// 1) อ่านข้อมูลจาก JSON หรือ $_POST
// =======================
$data = [];

// ลองอ่านจาก JSON ก่อน (กรณี fetch แบบ application/json)
$contentType = $_SERVER['CONTENT_TYPE'] ?? '';
$rawBody     = file_get_contents('php://input');

if (stripos($contentType, 'application/json') !== false && !empty($rawBody)) {
    $decoded = json_decode($rawBody, true);
    if (is_array($decoded)) {
        $data = $decoded;
    }
}

// ถ้ามี $_POST ให้ merge ทับ (เผื่ออนาคตใช้ FormData)
if (!empty($_POST)) {
    $data = array_merge($data, $_POST);
}

// helper ดึง int จากหลาย key
function getInt(array $src, array $keys): ?int {
    foreach ($keys as $k) {
        if (isset($src[$k]) && $src[$k] !== '' && $src[$k] !== null) {
            return (int)$src[$k];
        }
    }
    return null;
}

// =======================
// 2) แปลงค่า criteria
// =======================
$budgetId       = getInt($data, ['budget_id', 'budget']);
$genderId       = getInt($data, ['gender_id', 'gender']);
$ageRangeId     = getInt($data, ['age_range_id', 'age']);
$relationshipId = getInt($data, ['relationship_id', 'relationship']);

// หมวดหมู่แบบ id (ถ้าเผื่อมีส่งมาเป็นตัวเลข)
$categoryIds = [];
if (isset($data['category_ids']) && is_array($data['category_ids'])) {
    foreach ($data['category_ids'] as $v) {
        if ($v === '' || $v === null) continue;
        $categoryIds[] = (int)$v;
    }
}

// หมวดหมู่แบบชื่อ (ที่มาจาก form.js → criteria.categories)
$categoryNames = [];
if (isset($data['categories']) && is_array($data['categories'])) {
    foreach ($data['categories'] as $name) {
        $name = trim((string)$name);
        if ($name === '') continue;
        $categoryNames[] = $name;
    }
}

// ถ้าไม่มี filter เลย → ส่ง status อธิบายกลับ
if (
    $budgetId === null &&
    $genderId === null &&
    $ageRangeId === null &&
    $relationshipId === null &&
    empty($categoryIds) &&
    empty($categoryNames)
) {
    echo json_encode([
        'status'  => 'no_filters',
        'message' => 'ยังไม่ได้ส่งเงื่อนไขงบ / เพศ / อายุ / ความสัมพันธ์ / หมวดหมู่มาเลย เลยจะได้สินค้าทั้งหมด',
    ]);
    exit;
}

// =======================
// 3) สร้าง SQL ตามเงื่อนไข
//   - รวมราคาจาก product_external_urls
//   - รวมหมวดหมู่เป็น string แล้วไปแตกเป็น array ก่อนส่งออก
// =======================
$sql = "
    SELECT
        p.id,
        p.name,
        p.description,
        p.image_url,
        MIN(pe.min_price) AS min_price,
        MAX(pe.max_price) AS max_price,
        MAX(pe.currency)  AS currency,
        GROUP_CONCAT(DISTINCT c.name ORDER BY c.name SEPARATOR ',') AS category_list
    FROM products p
    LEFT JOIN product_budgets pb
        ON pb.product_id = p.id
    LEFT JOIN product_target_audiences pta
        ON pta.product_id = p.id
    LEFT JOIN product_categories pc
        ON pc.product_id = p.id
    LEFT JOIN categories c
        ON c.id = pc.category_id
    LEFT JOIN product_external_urls pe
        ON pe.product_id = p.id
    WHERE 1 = 1
";

$params = [];

// --- งบประมาณ ---
if ($budgetId !== null) {
    $sql .= " AND pb.budget_id = :budget_id";
    $params[':budget_id'] = $budgetId;
}

// --- ความสัมพันธ์ (1 = any) ---
if ($relationshipId !== null && $relationshipId !== 1) {
    $sql .= " AND (pta.relationship_id = :relationship_id OR pta.relationship_id = 1)";
    $params[':relationship_id'] = $relationshipId;
}

// --- เพศ (1 = any) ---
if ($genderId !== null && $genderId !== 1) {
    $sql .= " AND (pta.gender_id = :gender_id OR pta.gender_id = 1)";
    $params[':gender_id'] = $genderId;
}

// --- อายุ (1 = any) ---
if ($ageRangeId !== null && $ageRangeId !== 1) {
    $sql .= " AND (pta.age_range_id = :age_range_id OR pta.age_range_id = 1)";
    $params[':age_range_id'] = $ageRangeId;
}

// --- หมวดหมู่ (id หรือชื่อ อย่างใดอย่างหนึ่งหรือทั้งคู่) ---
if (!empty($categoryIds) || !empty($categoryNames)) {
    $conds = [];

    if (!empty($categoryIds)) {
        $placeholders = [];
        foreach ($categoryIds as $i => $cid) {
            $key = ":cat_id_{$i}";
            $placeholders[] = $key;
            $params[$key] = $cid;
        }
        $conds[] = "pc.category_id IN (" . implode(',', $placeholders) . ")";
    }

    if (!empty($categoryNames)) {
        $placeholders = [];
        foreach ($categoryNames as $i => $name) {
            $key = ":cat_name_{$i}";
            $placeholders[] = $key;
            $params[$key] = $name;
        }
        $conds[] = "c.name IN (" . implode(',', $placeholders) . ")";
    }

    // ต้อง match อย่างน้อยหนึ่งเงื่อนไขของหมวดหมู่
    $sql .= " AND (" . implode(' OR ', $conds) . ")";
}

$sql .= "
    GROUP BY
        p.id, p.name, p.description, p.image_url
    ORDER BY p.id DESC
";

// =======================
// 4) รัน Query และส่ง JSON กลับ
// =======================
try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // แตก category_list เป็น array ชื่อ categories ให้ JS ใช้งานง่าย
    foreach ($products as &$p) {
        if (!empty($p['category_list'])) {
            $p['categories'] = explode(',', $p['category_list']);
        } else {
            $p['categories'] = [];
        }
        unset($p['category_list']);
    }
    unset($p);

    echo json_encode($products);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'status'  => 'error',
        'message' => $e->getMessage(),
    ]);
}
