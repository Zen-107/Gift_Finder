<?php
// เริ่มต้น session (check ก่อนว่ายังไม่มี session)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database Configuration
// เลือกค่าอัตโนมัติ: เปิดจาก localhost = DB ในเครื่อง (CAMPP/XAMPP), เปิดจากโดเมนจริง = DB บนโฮสต์
$isLocal = in_array(explode(':', $_SERVER['HTTP_HOST'] ?? 'localhost')[0], ['localhost', '127.0.0.1']);

if ($isLocal) {
    // CAMPP / XAMPP ในเครื่อง
    $host = '127.0.0.1;port=3307';
    $dbname = 'gift_finder';
    $username = 'root';
    $password = '';
} else {
    // InfinityFree: ใส่ค่าจาก Control Panel > MySQL Databases
    $host = 'sqlXXX.infinityfree.com';
    $dbname = 'if0_XXXX_gift_finder';
    $username = 'if0_XXXX';
    $password = 'XXXX';
}

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// สร้าง Favorite folder อัตโนมัติเมื่อ login
if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
    try {
        $user_id = $_SESSION['user_id'];
        
        // ตรวจสอบว่ามี folder 'Favorite' หรือยัง
        $stmt = $pdo->prepare("SELECT id FROM bookmark_folders WHERE user_id = ? AND name = 'Favorite'");
        $stmt->execute([$user_id]);
        
        // ถ้ายังไม่มี ให้สร้าง
        if (!$stmt->fetch()) {
            $stmt = $pdo->prepare("INSERT INTO bookmark_folders (user_id, name) VALUES (?, 'Favorite')");
            $stmt->execute([$user_id]);
        }
    } catch (PDOException $e) {
        // Log error แต่ไม่หยุดการทำงาน
        error_log("Favorite folder creation error: " . $e->getMessage());
    }
}
?>