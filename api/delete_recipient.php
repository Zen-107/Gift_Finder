<?php
require_once "config.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Not logged in',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$userId = (int)$_SESSION['user_id'];
$rid    = !empty($_POST['recipient_id']) ? (int)$_POST['recipient_id'] : 0;

if (!$rid) {
    echo json_encode([
        'status'  => 'error',
        'message' => 'Missing recipient_id',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    if ($pdo instanceof PDO) {
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    $stmt = $pdo->prepare("
        DELETE FROM gift_recipients
        WHERE id = :id AND user_id = :uid
    ");
    $stmt->execute([
        ':id'  => $rid,
        ':uid' => $userId,
    ]);

    echo json_encode(['status' => 'ok'], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'status'  => 'error',
        'message' => $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE);
}
