<?php
// toggle_save.php
require_once 'config.php';

header('Content-Type: application/json; charset=utf-8');
session_start();

// ต้อง login ก่อน
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Please login first.',
        'code'    => 'NOT_LOGGED_IN'
    ]);
    exit;
}

$user_id  = (int)$_SESSION['user_id'];
$story_id = isset($_POST['story_id']) ? (int)$_POST['story_id'] : 0;

if ($story_id <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid story_id'
    ]);
    exit;
}

try {
    // เช็คว่า story มีอยู่จริงไหม
    $stmt = $pdo->prepare("SELECT 1 FROM stories WHERE story_id = :id LIMIT 1");
    $stmt->execute([':id' => $story_id]);
    if (!$stmt->fetchColumn()) {
        echo json_encode([
            'success' => false,
            'message' => 'Story not found'
        ]);
        exit;
    }

    // เช็คว่าผู้ใช้นี้เคย save แล้วหรือยัง
    $stmt = $pdo->prepare("
        SELECT 1
        FROM story_saves
        WHERE story_id = :sid AND user_id = :uid
        LIMIT 1
    ");
    $stmt->execute([
        ':sid' => $story_id,
        ':uid' => $user_id
    ]);

    $alreadySaved = (bool)$stmt->fetchColumn();

    if ($alreadySaved) {
        // unsave
        $stmt = $pdo->prepare("
            DELETE FROM story_saves
            WHERE story_id = :sid AND user_id = :uid
            LIMIT 1
        ");
        $stmt->execute([
            ':sid' => $story_id,
            ':uid' => $user_id
        ]);

        $saved = false;
    } else {
        // save
        $stmt = $pdo->prepare("
            INSERT INTO story_saves (story_id, user_id, created_at)
            VALUES (:sid, :uid, NOW())
        ");
        $stmt->execute([
            ':sid' => $story_id,
            ':uid' => $user_id
        ]);

        $saved = true;
    }

    echo json_encode([
        'success' => true,
        'saved'   => $saved
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error'
    ]);
}
