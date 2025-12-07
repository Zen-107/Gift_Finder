<?php
// ************************************************************
// *** จุดแก้ไขที่ 1: ใช้ Output Buffering เพื่อรับประกัน JSON ที่สะอาด ***
// ************************************************************
ob_start();

// *** config.php ของคุณมีการเรียก session_start() อยู่แล้ว ***
// ใส่การตรวจสอบนี้อีกครั้งเพื่อป้องกันการเรียกซ้ำ
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// กำหนด Header ให้ Browser รู้ว่าเป็น JSON response
header('Content-Type: application/json');
$response = ['success' => false, 'message' => 'An unknown error occurred.'];

// สมมติว่าไฟล์ config.php มีการเชื่อมต่อฐานข้อมูล $pdo (PDO Object)
require_once 'config.php'; 

// ----------------------------------------------------
// 1. ตรวจสอบว่า $pdo ถูกกำหนดค่าและเป็น Object หรือไม่
// ----------------------------------------------------
if (!isset($pdo) || !($pdo instanceof PDO)) {
    $response['message'] = 'Database connection object ($pdo) is not available. Check config.php.';
    ob_clean(); // ล้าง buffer ก่อนส่ง
    echo json_encode($response);
    exit;
}

// ----------------------------------------------------
// 2. การยืนยันตัวตน (Authentication Check)
// ----------------------------------------------------\
$session_user_id = $_SESSION['user_id'] ?? 0;

if ($session_user_id === 0) {
    // ถ้าไม่มี Session ID ผู้ใช้ (คือยังไม่ได้ล็อกอิน) ให้ปฏิเสธการโพสต์ทันที
    $response['message'] = 'Authentication required. Please log in to post a story.';
    ob_clean(); // ล้าง buffer ก่อนส่ง
    echo json_encode($response);
    exit;
}

// ----------------------------------------------------
// 3. รับข้อมูลข้อความจาก ฟอร์ม
// ----------------------------------------------------
$author_id = (int) $session_user_id; 
$story_title = $_POST['story_title'] ?? null;
$body_text = $_POST['body_text'] ?? null; 
$tags_text = $_POST['tags_text'] ?? null;

if (!$story_title || !$body_text) {
    $response['message'] = 'Missing required text data (Title or Body).';
    ob_clean(); // ล้าง buffer ก่อนส่ง
    echo json_encode($response);
    exit;
}

// ใช้ try...catch เพื่อจัดการ Error ของ PDO และไฟล์
try {

    // ----------------------------------------------------
    // 4. การจัดการไฟล์รูปภาพและการกำหนด Path
    // ----------------------------------------------------
    if (!isset($_FILES['cover_file']) || $_FILES['cover_file']['error'] !== UPLOAD_ERR_OK) {
        $response['message'] = 'No cover file uploaded or file error occurred. (Error: ' . ($_FILES['cover_file']['error'] ?? 'N/A') . ')';
        ob_clean(); // ล้าง buffer ก่อนส่ง
        echo json_encode($response);
        exit;
    }

    $file = $_FILES['cover_file'];
    
    // กำหนด Path สำหรับ Server: ย้อนกลับไป 1 ระดับจากโฟลเดอร์ api/ เพื่อไปที่ root/assets/img/
    $base_upload_dir = __DIR__ . '/../assets/img/'; 

    // ตรวจสอบและสร้างโฟลเดอร์ assets/img/ ถ้ายังไม่มี
    if (!is_dir($base_upload_dir)) {
        if (!mkdir($base_upload_dir, 0777, true)) {
            throw new Exception('Failed to create base upload directory. Check folder permissions (0777).');
        }
    }

    $file_ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $new_file_name = 'story_' . $author_id . '_' . uniqid() . '.' . $file_ext; 
    $target_file = $base_upload_dir . $new_file_name;
    $db_image_path = null; 

    // ----------------------------------------------------
    // 5. ย้ายไฟล์และบันทึกข้อมูลลง DB (ใช้ PDO)
    // ----------------------------------------------------\
    if (move_uploaded_file($file['tmp_name'], $target_file)) {
        
        $db_image_path = 'assets/img/' . $new_file_name;

        $sql = "INSERT INTO stories (cover_image, story_title, body_text, author_id, tags_text) 
                VALUES (:cover_image, :story_title, :body_text, :author_id, :tags_text)";
        
        $stmt = $pdo->prepare($sql);
        
        $stmt->bindParam(':cover_image', $db_image_path);
        $stmt->bindParam(':story_title', $story_title);
        $stmt->bindParam(':body_text', $body_text);
        $stmt->bindParam(':author_id', $author_id, PDO::PARAM_INT);
        $stmt->bindParam(':tags_text', $tags_text);
        
        if ($stmt->execute()) {
            $response['success'] = true;
            $response['story_id'] = $pdo->lastInsertId();
            $response['cover_image_path'] = $db_image_path;
            $response['message'] = 'Story and cover image saved successfully.'; 
        } else {
            $errorInfo = $stmt->errorInfo();
            $response['message'] = 'Database query failed: ' . ($errorInfo[2] ?? 'Unknown PDO error');
            unlink($target_file); 
        }

    } else {
        $response['message'] = 'Failed to move uploaded file. Check folder permissions (0777).';
    }

} catch (PDOException $e) {
    $response['message'] = 'Database error (PDOException): ' . $e->getMessage();
} catch (Exception $e) {
    $response['message'] = 'General error: ' . $e->getMessage();
}

// ************************************************************
// *** จุดแก้ไขที่ 2: ล้าง Buffer และส่ง JSON ที่สะอาดกลับไป ***
// ************************************************************
ob_clean(); 
echo json_encode($response);
?>