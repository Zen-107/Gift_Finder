<?php
// toggle_like.php
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
    // เช็คว่ามี story นี้จริงไหม
    $stmt = $pdo->prepare("SELECT like_count FROM stories WHERE story_id = :id LIMIT 1");
    $stmt->execute([':id' => $story_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        echo json_encode([
            'success' => false,
            'message' => 'Story not found'
        ]);
        exit;
    }

    $pdo->beginTransaction();

    // เช็คว่าผู้ใช้คนนี้เคยไลก์รึยัง
    $stmt = $pdo->prepare("
        SELECT 1 
        FROM story_likes 
        WHERE story_id = :sid AND user_id = :uid 
        LIMIT 1
    ");
    $stmt->execute([
        ':sid' => $story_id,
        ':uid' => $user_id
    ]);
    $alreadyLiked = (bool)$stmt->fetchColumn();

    if ($alreadyLiked) {
        // ยกเลิกไลก์
        $stmt = $pdo->prepare("
            DELETE FROM story_likes 
            WHERE story_id = :sid AND user_id = :uid
            LIMIT 1
        ");
        $stmt->execute([
            ':sid' => $story_id,
            ':uid' => $user_id
        ]);

        $stmt = $pdo->prepare("
            UPDATE stories 
            SET like_count = GREATEST(like_count - 1, 0)
            WHERE story_id = :sid
        ");
        $stmt->execute([':sid' => $story_id]);

        $liked = false;
    } else {
        // กดไลก์
        $stmt = $pdo->prepare("
            INSERT INTO story_likes (story_id, user_id, created_at)
            VALUES (:sid, :uid, NOW())
        ");
        $stmt->execute([
            ':sid' => $story_id,
            ':uid' => $user_id
        ]);

        $stmt = $pdo->prepare("
            UPDATE stories 
            SET like_count = like_count + 1
            WHERE story_id = :sid
        ");
        $stmt->execute([':sid' => $story_id]);

        $liked = true;
    }

    // ดึง like_count ปัจจุบัน
    $stmt = $pdo->prepare("SELECT like_count FROM stories WHERE story_id = :sid LIMIT 1");
    $stmt->execute([':sid' => $story_id]);
    $like_count = (int)$stmt->fetchColumn();

    $pdo->commit();

    echo json_encode([
        'success'    => true,
        'liked'      => $liked,
        'like_count' => $like_count
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error'
    ]);
}
