<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/config.php';



// --- 2) ถ้าไม่ได้ล็อกอิน ให้ส่ง [] กลับไป (ไม่ใช่ null) ---
if (empty($_SESSION['user_id'])) {
    echo json_encode([]);
    exit;
}

$userId = (int) $_SESSION['user_id'];

try {
    // --- 3) ดึงรายชื่อบุคคลสำคัญของ user คนนี้ ---

        $sql = "
        SELECT
            r.id,
            r.name
        FROM gift_recipients AS r
        WHERE r.user_id = :uid
        ORDER BY r.id DESC
    ";

    /* โบกีมาแก้
    $sql = "
        SELECT
            r.id,
            r.name,
            r.gender_id,
            r.age_range_id,
            r.relationship_id,
            r.budget_id,
            r.category,
            r.created_at
        FROM gift_recipients AS r
        WHERE r.user_id = :uid
        ORDER BY r.id DESC
    ";
*/


    $stmt = $pdo->prepare($sql);
    $stmt->execute([':uid' => $userId]);

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // สำคัญมาก: ต้องเป็น array เสมอ ถ้าไม่มีก็ส่ง []
    if (!is_array($rows)) {
        $rows = [];
    }

    echo json_encode($rows);
} catch (Throwable $e) {
    // ถ้า error ก็ยังส่ง [] กลับไป เพื่อไม่ให้ฝั่ง JS พัง
    http_response_code(500);
    error_log("get_recipients error: " . $e->getMessage());
    echo json_encode([]);
}
