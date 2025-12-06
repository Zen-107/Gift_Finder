<?php
include 'config.php';

if (!isset($_SESSION["user_id"])) {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "กรุณาล็อกอินก่อน"]);
    exit;
}

$user_id = $_SESSION["user_id"];

$folder_id = intval($_GET['folder_id'] ?? 0);

if ($folder_id <= 0) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "ID โฟลเดอร์ไม่ถูกต้อง"]);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT b.product_id, p.name, p.image_url
        FROM bookmarks b
        JOIN products p ON b.product_id = p.id
        WHERE b.user_id = ? AND b.folder_id = ?
    ");
    $stmt->execute([$user_id, $folder_id]);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(["success" => true, "products" => $products]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "เกิดข้อผิดพลาด: " . $e->getMessage()]);
}