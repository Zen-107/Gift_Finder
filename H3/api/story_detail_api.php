<?php
// *** ใส่สองบรรทัดนี้เพิ่ม (ตัด Warning/Notice ออกไปจาก output) ***
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
ini_set('display_errors', 0);
// story_detail_api.php
require_once 'config.php'; // มี $pdo ต่อ DB แล้ว

header('Content-Type: application/json; charset=utf-8');

$story_id = isset($_GET['story_id']) ? (int)$_GET['story_id'] : 0;

if ($story_id <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid story_id'
    ]);
    exit;
}

try {
    // 1) ดึงข้อมูล story หลัก + คนเขียน
    $stmt = $pdo->prepare("
        SELECT 
            s.story_id,
            s.cover_image,
            s.story_title,
            s.story_excerpt,
            s.body_text,
            s.like_count,
            s.author_id,
            s.tags_text,
            s.created_at,
            u.name AS author_name
        FROM stories s
        LEFT JOIN users u ON u.id = s.author_id
        WHERE s.story_id = :id
        LIMIT 1
    ");
    $stmt->execute([':id' => $story_id]);
    $story = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$story) {
        echo json_encode([
            'success' => false,
            'message' => 'Story not found'
        ]);
        exit;
    }

    // แปลง tags_text เป็น array (สมมติใช้ # หรือ | คั่น)
    $tags = [];
    if (!empty($story['tags_text'])) {
        $parts = preg_split('/[#|]/', $story['tags_text']);
        foreach ($parts as $tag) {
            $tag = trim($tag);
            if ($tag !== '') {
                $tags[] = $tag;
            }
        }
    }

    // เช็ค login (ถ้ามี session)
    if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
    $user_id = $_SESSION['user_id'] ?? null;
    $liked = false;
    $saved = false;

    if ($user_id) {
        // เคย like ไหม
        $stmt = $pdo->prepare("
            SELECT 1 FROM story_likes 
            WHERE story_id = :sid AND user_id = :uid 
            LIMIT 1
        ");
        $stmt->execute([
            ':sid' => $story_id,
            ':uid' => $user_id
        ]);
        $liked = (bool)$stmt->fetchColumn();

        // เคย save ไหม
        $stmt = $pdo->prepare("
            SELECT 1 FROM story_saves 
            WHERE story_id = :sid AND user_id = :uid 
            LIMIT 1
        ");
        $stmt->execute([
            ':sid' => $story_id,
            ':uid' => $user_id
        ]);
        $saved = (bool)$stmt->fetchColumn();
    }

    // ------------------------------------
    // 2) More stories logic
    //    1) ผู้เขียนคนเดียวกัน
    //    2) tags คล้ายกัน
    //    3) ถ้ายังไม่ครบ เติมจากทั้งหมด
    //    จำกัด 5 อัน
    // ------------------------------------
    $MAX_SUGGESTIONS = 5;
    $suggestions = [];

    // helper กันซ้ำ
    $addSuggestion = function (&$list, $row) {
        $id = (int)$row['story_id'];
        if (!isset($list[$id])) {
            $list[$id] = [
                'story_id'    => $id,
                'story_title' => $row['story_title'],
                'cover_image' => $row['cover_image'],
                'created_at'  => $row['created_at'],
                'author_id'   => $row['author_id'],
                'tags_text'   => $row['tags_text'],
            ];
        }
    };

    // 2.1) จากผู้เขียนคนเดียวกันก่อน
    $stmt = $pdo->prepare("
        SELECT 
            s.story_id,
            s.story_title,
            s.cover_image,
            s.created_at,
            s.author_id,
            s.tags_text
        FROM stories s
        WHERE s.story_id != :id
          AND s.author_id = :author_id
        ORDER BY s.created_at DESC
        LIMIT :limit
    ");
    $stmt->bindValue(':id', $story_id, PDO::PARAM_INT);
    $stmt->bindValue(':author_id', $story['author_id'], PDO::PARAM_INT);
    $stmt->bindValue(':limit', $MAX_SUGGESTIONS, PDO::PARAM_INT);
    $stmt->execute();

    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $addSuggestion($suggestions, $row);
    }

    // 2.2) ถ้ายังไม่ครบ และมี tags → หาตาม tags คล้ายกัน
    $remaining = $MAX_SUGGESTIONS - count($suggestions);

    if ($remaining > 0 && !empty($tags)) {
        $likeConditions = [];
        $params = [
            ':id'        => $story_id,
            ':author_id' => $story['author_id'], // กันซ้ำกับข้อ 2.1
        ];

        foreach ($tags as $idx => $tag) {
            $key = ':tag' . $idx;
            $likeConditions[] = "s.tags_text LIKE $key";
            $params[$key] = '%' . $tag . '%';
        }

        $whereTags = implode(' OR ', $likeConditions);

        $sql = "
            SELECT 
                s.story_id,
                s.story_title,
                s.cover_image,
                s.created_at,
                s.author_id,
                s.tags_text
            FROM stories s
            WHERE s.story_id != :id
              AND s.author_id != :author_id
              AND ($whereTags)
            ORDER BY s.created_at DESC
            LIMIT " . ($remaining * 2) . "
        ";

        $stmt = $pdo->prepare($sql);

        foreach ($params as $k => $v) {
            if ($k === ':id' || $k === ':author_id') {
                $stmt->bindValue($k, $v, PDO::PARAM_INT);
            } else {
                $stmt->bindValue($k, $v, PDO::PARAM_STR);
            }
        }

        $stmt->execute();

        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            if (count($suggestions) >= $MAX_SUGGESTIONS) break;
            $addSuggestion($suggestions, $row);
        }
    }

    // 2.3) ถ้ายังไม่ครบ → เติมจากเรื่องอื่น ๆ ทั่วไป
    $remaining = $MAX_SUGGESTIONS - count($suggestions);

    if ($remaining > 0) {
        $stmt = $pdo->prepare("
            SELECT 
                s.story_id,
                s.story_title,
                s.cover_image,
                s.created_at,
                s.author_id,
                s.tags_text
            FROM stories s
            WHERE s.story_id != :id
            ORDER BY s.created_at DESC
            LIMIT :limit
        ");
        $stmt->bindValue(':id', $story_id, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $remaining, PDO::PARAM_INT);
        $stmt->execute();

        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            if (count($suggestions) >= $MAX_SUGGESTIONS) break;
            $addSuggestion($suggestions, $row);
        }
    }

    $suggestions = array_values($suggestions);

    // 3) ส่ง JSON กลับไปให้หน้า HTML
    $data = [
        'success' => true,
        'story' => [
            'id'          => (int)$story['story_id'],
            'title'       => $story['story_title'],
            'excerpt'     => $story['story_excerpt'],
            'body_html'   => nl2br($story['body_text']),
            'cover_image' => $story['cover_image'],
            'author_name' => $story['author_name'] ?: 'Unknown',
            'author_id'   => (int)$story['author_id'],
            'created_at'  => $story['created_at'],
            'like_count'  => (int)$story['like_count'],
            'tags'        => $tags,
        ],
        'liked'       => $liked,
        'saved'       => $saved,
        'suggestions' => $suggestions,
    ];

    echo json_encode($data);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error'
    ]);
}
