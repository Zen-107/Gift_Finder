<?php
// ลด Warning/Notice ไม่ให้มากวน JSON
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
ini_set('display_errors', 0);

require_once 'config.php'; // ต้องมี $pdo ต่อ DB ไว้แล้ว

header('Content-Type: application/json; charset=utf-8');

try {
    // 1) ดึงข้อมูลทุก story พร้อมผู้เขียน
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
        ORDER BY s.created_at DESC
    ");
    $stmt->execute();
    $stories = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $outputStories     = [];
    $allTags           = [];
    $authorStats       = []; // เอาไว้เก็บจำนวนโพสต์ของแต่ละนักเขียน

    foreach ($stories as $story) {
        // ---------- TAGS ----------
        $tags = [];
        if (!empty($story['tags_text'])) {
            // แยกด้วย # ตามที่บอก
            $parts = explode('#', $story['tags_text']);
            foreach ($parts as $tag) {
                $tag = trim($tag);
                if ($tag !== '') {
                    $tags[]    = $tag;
                    $allTags[] = $tag; // เก็บรวมไว้ทำ recommended tags
                }
            }
        }

        // ---------- AUTHORS (นับโพสต์ต่อคน) ----------
        $authorId   = (int)$story['author_id'];
        $authorName = $story['author_name'] ?: 'Unknown';

        if ($authorId) {
            if (!isset($authorStats[$authorId])) {
                $authorStats[$authorId] = [
                    'id'         => $authorId,
                    'name'       => $authorName,
                    'post_count' => 0,
                ];
            }
            $authorStats[$authorId]['post_count']++;
        }

        // ---------- ทำ excerpt ฝั่ง backend เผื่อใช้ ----------
        $sourceText = $story['story_excerpt'] ?: $story['body_text'];
        $excerpt    = '';
        if (!empty($sourceText)) {
            $excerpt = mb_substr($sourceText, 0, 150, 'UTF-8');
            if (mb_strlen($sourceText, 'UTF-8') > 150) {
                $excerpt .= '...';
            }
        }

        // ---------- รูปแบบ story ที่ส่งให้ frontend ----------
        $outputStories[] = [
            'id'          => (int)$story['story_id'],
            'title'       => $story['story_title'],
            'body'        => $story['body_text'],
            'excerpt'     => $excerpt,
            'cover_image' => $story['cover_image'],
            'author_name' => $authorName,
            'author_id'   => $authorId,
            'created_at'  => $story['created_at'],
            'like_count'  => (int)$story['like_count'],
            'tags'        => $tags,
        ];
    }

    // ---------- Staff Picks: สุ่ม 5 เรื่อง ----------
    $staffPicks = [];
    if (count($outputStories) > 0) {
        $count = min(5, count($outputStories));
        $keys  = array_rand($outputStories, $count);
        if (!is_array($keys)) {
            $keys = [$keys];
        }
        foreach ($keys as $key) {
            $staffPicks[] = $outputStories[$key];
        }
    }

    // ---------- Tags แนะนำ: สุ่ม 10 tag ----------
    $recommendedTags = [];
    if (!empty($allTags)) {
        $allTags = array_unique($allTags);  // ตัดซ้ำ
        shuffle($allTags);                  // สุ่มลำดับ
        $recommendedTags = array_slice($allTags, 0, 10);
    }

    // ---------- Authors แนะนำ: สุ่ม 20 คน ----------
    $recommendedAuthors = [];
    if (!empty($authorStats)) {
        $authorArray = array_values($authorStats); // ตัด key author_id ออก ให้เป็น array ธรรมดา
        shuffle($authorArray);                     // สุ่มลำดับนักเขียน
        $recommendedAuthors = array_slice($authorArray, 0, 20);
    }

    // ---------- ส่ง JSON กลับ ----------
    $data = [
        'success'            => true,
        'message'            => 'Stories loaded successfully.',
        'stories'            => $outputStories,
        'staff_picks'        => $staffPicks,
        'recommended_tags'   => $recommendedTags,
        'recommended_authors'=> $recommendedAuthors,
    ];

    echo json_encode($data);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
?>
