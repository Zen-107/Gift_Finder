<?php
include  'config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['isBookmarked' => false]);
    exit;
}

$user_id = $_SESSION['user_id'];
$product_id = intval($_GET['product_id'] ?? 0);

if ($product_id <= 0) {
    echo json_encode(['isBookmarked' => false]);
    exit;
}

$stmt = $pdo->prepare("SELECT id FROM bookmarks WHERE user_id = ? AND product_id = ?");
$stmt->execute([$user_id, $product_id]);
$isBookmarked = $stmt->fetch() ? true : false;

echo json_encode(['isBookmarked' => $isBookmarked]);