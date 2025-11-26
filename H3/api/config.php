<?php
session_start();

$host = 'localhost';
$dbname = 'gift_finder';
$username = 'root';
$password = '';


try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// สร้าง Favorite folder อัตโนมัติเมื่อ login
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $stmt = $pdo->prepare("SELECT id FROM bookmark_folders WHERE user_id = ? AND name = ?");
    $stmt->execute([$user_id, 'Favorite']);
    if (!$stmt->fetch()) {
        $stmt = $pdo->prepare("INSERT INTO bookmark_folders (user_id, name) VALUES (?, 'Favorite')");
        $stmt->execute([$user_id]);
    }
}
