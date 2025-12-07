<?php
// *** ใส่สองบรรทัดนี้เพิ่ม (ตัด Warning/Notice ออกไปจาก output) ***
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
ini_set('display_errors', 0);
// stories_api.php - ดึงรายการ Story ทั้งหมดสำหรับหน้า Feed
require_once 'config.php'; // สมมติว่าไฟล์นี้มี $pdo ต่อ DB แล้ว

header('Content-Type: application/json; charset=utf-8');

try {
    // 1) ดึงข้อมูล Story ที่จำเป็นทั้งหมด 
    $stmt = $pdo->prepare("
        SELECT 
            s.story_id,
            s.cover_image,
            s.story_title,
            s.story_excerpt,
            s.like_count,
            s.author_id,
            s.tags_text,
            s.created_at,
            u.name AS author_name
        FROM stories s
        LEFT JOIN users u ON u.id = s.author_id
        ORDER BY s.created_at DESC
    ");
    
    $stmt->execute();
    $stories = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 2) จัดการข้อมูลก่อนส่งออก (เช่น แปลง tags)
    $outputStories = [];
    foreach ($stories as $story) {
        // แปลง tags_text เป็น array
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

        $outputStories[] = [
            'id'          => (int)$story['story_id'],
            'title'       => $story['story_title'],
            'excerpt'     => $story['story_excerpt'],
            'cover_image' => $story['cover_image'],
            'author_name' => $story['author_name'] ?: 'Unknown',
            'author_id'   => (int)$story['author_id'],
            'created_at'  => $story['created_at'],
            'like_count'  => (int)$story['like_count'],
            'tags'        => $tags,
        ];
    }

    // 3) ส่ง JSON กลับไป
    $data = [
        'success' => true,
        'stories' => $outputStories,
        'total'   => count($outputStories)
    ];

    echo json_encode($data);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error'
    ]);
}
?>