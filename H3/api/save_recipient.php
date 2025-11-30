<?php
require_once "config.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

if (empty($_SESSION['user_id'])) {
    echo json_encode([
        "status"  => "error",
        "message" => "Not logged in",
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$userId = (int) $_SESSION['user_id'];

$name            = trim($_POST['name'] ?? '');
$genderId        = isset($_POST['gender'])       && $_POST['gender']       !== '' ? (int)$_POST['gender']       : null;
$ageRangeId      = isset($_POST['age'])          && $_POST['age']          !== '' ? (int)$_POST['age']          : null;
$relationshipId  = isset($_POST['relationship']) && $_POST['relationship'] !== '' ? (int)$_POST['relationship'] : null;
$budgetId        = isset($_POST['budget'])       && $_POST['budget']       !== '' ? (int)$_POST['budget']       : null;
$rid             = !empty($_POST['recipient_id']) ? (int)$_POST['recipient_id'] : null;

try {
    if ($pdo instanceof PDO) {
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    // ❶ เช็คชื่อซ้ำ (ต่อ user_id เดียวกัน, ไม่สนตัวพิมพ์ใหญ่/เล็ก, ตัด space หัวท้าย)
    $checkSql = "
        SELECT id
        FROM gift_recipients
        WHERE user_id = :user_id
          AND TRIM(LOWER(name)) = TRIM(LOWER(:name))
    ";
    if ($rid) {
        $checkSql .= " AND id <> :id";
    }

    $checkStmt = $pdo->prepare($checkSql);
    $checkStmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
    $checkStmt->bindValue(':name', $name, PDO::PARAM_STR);
    if ($rid) {
        $checkStmt->bindValue(':id', $rid, PDO::PARAM_INT);
    }
    $checkStmt->execute();
    $dup = $checkStmt->fetch(PDO::FETCH_ASSOC);

    if ($dup) {
        echo json_encode([
            'status'  => 'duplicate',
            'message' => 'มีเพื่อนชื่อนี้อยู่แล้ว',
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // ❷ ไม่ซ้ำ → update หรือ insert ตามปกติ
    if ($rid) {
        // UPDATE friend เดิม ให้ใช้ *_id ให้ตรง schema
        $sql = "
            UPDATE gift_recipients
            SET
                name            = :name,
                gender_id       = :gender_id,
                age_range_id    = :age_range_id,
                relationship_id = :relationship_id,
                budget_id       = :budget_id
            WHERE id = :id AND user_id = :user_id
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':name'            => $name ?: null,
            ':gender_id'       => $genderId,
            ':age_range_id'    => $ageRangeId,
            ':relationship_id' => $relationshipId,
            ':budget_id'       => $budgetId,
            ':id'              => $rid,
            ':user_id'         => $userId,
        ]);

        echo json_encode([
            'status' => 'ok',
            'mode'   => 'update',
            'id'     => $rid,
        ], JSON_UNESCAPED_UNICODE);

    } else {
        // INSERT friend ใหม่ ให้ใช้ *_id ให้ตรง schema
        $sql = "
            INSERT INTO gift_recipients
                (user_id, name, gender_id, age_range_id, relationship_id, budget_id)
            VALUES
                (:user_id, :name, :gender_id, :age_range_id, :relationship_id, :budget_id)
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':user_id'         => $userId,
            ':name'            => $name ?: null,
            ':gender_id'       => $genderId,
            ':age_range_id'    => $ageRangeId,
            ':relationship_id' => $relationshipId,
            ':budget_id'       => $budgetId,
        ]);

        $newId = (int)$pdo->lastInsertId();

        echo json_encode([
            'status' => 'ok',
            'mode'   => 'insert',
            'id'     => $newId,
        ], JSON_UNESCAPED_UNICODE);
    }

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'status'  => 'error',
        'message' => $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE);
}
