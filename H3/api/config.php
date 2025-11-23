<?php
session_start();

$host = 'localhost';
$dbname = 'gift_finder';
$username = 'root'; // ค่าเริ่มต้นของ XAMPP
$password = '';     // ค่าเริ่มต้นของ XAMPP

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>
