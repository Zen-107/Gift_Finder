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

try {
    $stmt = $pdo->prepare("
        SELECT b.id, b.folder_id, f.name as folder_name
        FROM bookmarks b
        LEFT JOIN bookmark_folders f ON b.folder_id = f.id
        WHERE b.user_id = ? AND b.product_id = ?
    ");
    $stmt->execute([$user_id, $product_id]);
    $bookmarks = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(["success" => true, "bookmarks" => $bookmarks]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "เกิดข้อผิดพลาด: " . $e->getMessage()]);
}