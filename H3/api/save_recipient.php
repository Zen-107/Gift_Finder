<?php
require_once "config.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

if (empty($_SESSION['user_id'])) {
    echo json_encode(["status"=>"error","message"=>"Not logged in"]);
    exit;
}

$user_id     = $_SESSION['user_id'];
$rid         = $_POST['recipient_id'] ?? null;

$name         = $_POST['name'] ?? "";
$gender       = $_POST['gender'] ?? "";
$age_range    = $_POST['age'] ?? "";
$relationship = $_POST['relationship'] ?? "";

try {
//  $rid = $_POST['recipient_id'] ?? null;
    if ($rid) {
        // update
        $stmt = $pdo->prepare("
            UPDATE gift_recipients
            SET name=?, gender=?, age_range=?, relationship=?
            WHERE id=? AND user_id=?
        ");
        $stmt->execute([$name,$gender,$age_range,$relationship,$rid,$user_id]);
    } else {
        // insert
        $stmt = $pdo->prepare("
            INSERT INTO gift_recipients (user_id,name,gender,age_range,relationship)
            VALUES (?,?,?,?,?)
        ");
        $stmt->execute([$user_id,$name,$gender,$age_range,$relationship]);
        $rid = $pdo->lastInsertId();
    }

    echo json_encode(["status"=>"ok","id"=>$rid]);

} catch (Throwable $e) {
    echo json_encode(["status"=>"error","message"=>$e->getMessage()]);
}
