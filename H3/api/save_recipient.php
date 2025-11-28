<?php
header('Content-Type: application/json; charset=utf-8');

// ✅ แก้ path นี้ให้ตรงกับ config.php ของโปรเจกต์คุณ
require_once __DIR__ . '/config.php';


// ✅ ต้องล็อกอินก่อน (มี user_id ใน session)
if (!isset($_SESSION['user_id'])) {
  http_response_code(401);
  echo json_encode([
    'status'  => 'error',
    'message' => 'Not logged in'
  ]);
  exit;
}

$userId = (int) $_SESSION['user_id'];

// ดึงค่าจาก POST
$name            = trim($_POST['name'] ?? '');
$genderId        = !empty($_POST['gender_id'])        ? (int)$_POST['gender_id']        : null;
$ageRangeId      = !empty($_POST['age_range_id'])     ? (int)$_POST['age_range_id']     : null;
$relationshipId  = !empty($_POST['relationship_id']) ? (int)$_POST['relationship_id']  : null;
$budgetId        = !empty($_POST['budget_id'])        ? (int)$_POST['budget_id']        : null;
$recipientId     = !empty($_POST['recipient_id'])    ? (int)$_POST['recipient_id']     : null;

try {
  $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

  // ✅ ถ้ามี recipient_id = แก้ไขข้อมูลเดิม
  if ($recipientId) {

    $sql = "
      UPDATE gift_recipients
      SET
        name = :name,
        gender_id = :gender_id,
        age_range_id = :age_range_id,
        relationship_id = :relationship_id,
        budget_id = :budget_id
      WHERE id = :id AND user_id = :user_id
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
      ':name'            => $name ?: null,
      ':gender_id'       => $genderId,
      ':age_range_id'    => $ageRangeId,
      ':relationship_id'=> $relationshipId,
      ':budget_id'       => $budgetId,
      ':id'              => $recipientId,
      ':user_id'         => $userId,
    ]);

    echo json_encode([
      'status' => 'ok',
      'mode'   => 'update',
      'id'     => $recipientId
    ], JSON_UNESCAPED_UNICODE);

  } else {

    // ✅ ถ้าไม่มี recipient_id = เพิ่มคนใหม่
    $sql = "
      INSERT INTO gift_recipients
      (name, gender_id, age_range_id, relationship_id, budget_id, user_id)
      VALUES
      (:name, :gender_id, :age_range_id, :relationship_id, :budget_id, :user_id)
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
      ':name'            => $name ?: null,
      ':gender_id'       => $genderId,
      ':age_range_id'    => $ageRangeId,
      ':relationship_id'=> $relationshipId,
      ':budget_id'       => $budgetId,
      ':user_id'         => $userId,
    ]);

    $newId = (int) $pdo->lastInsertId();

    echo json_encode([
      'status' => 'ok',
      'mode'   => 'insert',
      'id'     => $newId
    ], JSON_UNESCAPED_UNICODE);
  }

} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode([
    'status'  => 'error',
    'message' => $e->getMessage()
  ], JSON_UNESCAPED_UNICODE);
}
