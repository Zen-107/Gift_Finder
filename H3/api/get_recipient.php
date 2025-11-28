<?php
require_once "config.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

// ยังไม่ได้ล็อกอิน → ส่ง null กลับไป
if (empty($_SESSION['user_id'])) {
    echo json_encode(null);
    exit;
}

$userId = (int) $_SESSION['user_id'];
$id     = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if (!$id) {
    echo json_encode(null);
    exit;
}

try {
    if ($pdo instanceof PDO) {
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    $sql = "
        SELECT
            gr.id,
            gr.name,
            gr.gender_id      AS gender,
            gr.age_range_id   AS age_range,
            gr.relationship_id AS relationship,
            gr.budget_id      AS budget
        FROM gift_recipients gr
        WHERE gr.id = :id AND gr.user_id = :uid
        LIMIT 1
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':id'  => $id,
        ':uid' => $userId,
    ]);

    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode($row ?: null, JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'status'  => 'error',
        'message' => $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE);
}
