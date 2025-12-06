<?php
include 'config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'กรุณาล็อกอินก่อน']);
    exit;
}

$user_id = $_SESSION['user_id'];
$name = trim($_POST['name'] ?? $_GET['name'] ?? (json_decode(file_get_contents('php://input'), true)['name'] ?? ''));

if (!$name) {
    echo json_encode(['success' => false, 'message' => 'ชื่อโฟลเดอร์ไม่สามารถเว้นว่างได้']);
    exit;
}

$stmt = $pdo->prepare("SELECT id FROM bookmark_folders WHERE user_id = ? AND name = ?");
$stmt->execute([$user_id, $name]);
$row = $stmt->fetch();

if ($row) {
    echo json_encode(['success' => true, 'folder_id' => $row['id']]);
} else {
    echo json_encode(['success' => false, 'message' => 'ไม่พบโฟลเดอร์']);
}
?>