<?php
include 'config.php';

header('Content-Type: application/json');

if (!isset($_SESSION["user_id"])) {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "กรุณาล็อกอินก่อน"]);
    exit;
}

$user_id = $_SESSION["user_id"];

try {

    $stmt = $pdo->prepare("
        SELECT DISTINCT
            f.id as folder_id,
            f.name as folder_name
        FROM bookmarks b
        JOIN bookmark_folders f ON b.folder_id = f.id
        WHERE b.user_id = ? AND b.folder_id IS NOT NULL
        ORDER BY f.name
    ");
    $stmt->execute([$user_id]);
    $folders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "success" => true,
        "folders" => $folders
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "เกิดข้อผิดพลาด: " . $e->getMessage()
    ]);
}
?>