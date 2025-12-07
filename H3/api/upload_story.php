<?php
// กำหนด Header ให้ Browser รู้ว่าเป็น JSON response
header('Content-Type: application/json');
$response = ['success' => false, 'message' => 'An unknown error occurred.'];
require_once 'config.php';
// ----------------------------------------------------
// 2. รับข้อมูลข้อความจาก ฟอร์ม
// ----------------------------------------------------
// author_id เป็น INT ใน DB
$author_id = (int) ($_POST['author_id'] ?? 0); 
$story_title = $_POST['story_title'] ?? null;
$body_text = $_POST['body_text'] ?? null;
$tags_text = $_POST['tags_text'] ?? null;
// ค่าอื่น ๆ เช่น like_count จะถูกกำหนดโดย DB default

// ตรวจสอบข้อมูลข้อความที่จำเป็น
if ($author_id === 0 || !$story_title || !$body_text) {
    $response['message'] = 'Missing required text data (Author ID, Title, or Body).';
    $conn->close();
    echo json_encode($response);
    exit;
}

// ----------------------------------------------------
// 3. การจัดการไฟล์รูปภาพและการกำหนด Path
// ----------------------------------------------------
if (!isset($_FILES['cover_file']) || $_FILES['cover_file']['error'] !== UPLOAD_ERR_OK) {
    $response['message'] = 'No cover file uploaded or file error occurred.';
    $conn->close();
    echo json_encode($response);
    exit;
}

$file = $_FILES['cover_file'];

// 3.1 กำหนด Path อัปโหลดจริงบน Server 
// Path: D:\xampp\htdocs\Shit_test\H3\api
// ต้องการ Path: D:\xampp\htdocs\Shit_test\H3\assets\img\
// ใช้ /../ เพื่อออกจากโฟลเดอร์ api/ ไปยัง H3/ แล้วเข้า assets/img/
$user_folder = $author_id; 
$base_upload_dir = __DIR__ . '/../assets/img/'; 
$upload_dir = $base_upload_dir . $user_folder . '/'; 

// 3.2 สร้างโฟลเดอร์ย่อยตาม author_id ถ้ายังไม่มี
if (!is_dir($upload_dir)) {
    if (!mkdir($upload_dir, 0777, true)) {
        $response['message'] = 'Failed to create author directory. Check permissions: ' . $upload_dir;
        $conn->close();
        echo json_encode($response);
        exit;
    }
}

// 3.3 สร้างชื่อไฟล์ที่ไม่ซ้ำกันและกำหนด Path ปลายทาง
$file_ext = pathinfo($file['name'], PATHINFO_EXTENSION);
$new_file_name = uniqid('story_', true) . '.' . $file_ext; 
$target_file = $upload_dir . $new_file_name;

$db_image_path = null; 

// ----------------------------------------------------
// 4. ย้ายไฟล์และบันทึกข้อมูลลง DB
// ----------------------------------------------------
if (move_uploaded_file($file['tmp_name'], $target_file)) {
    
    // Path สัมพัทธ์สำหรับเรียกใช้ใน HTML (บันทึกใน DB)
    $db_image_path = 'Shit_test/H3/assets/img/' . $user_folder . '/' . $new_file_name;

    // 5. บันทึกข้อมูลทั้งหมดลงในตาราง stories
    // คอลัมน์ที่ถูกตั้งค่า: cover_image, story_title, body_text, author_id, tags_text
    $sql = "INSERT INTO stories (cover_image, story_title, body_text, author_id, tags_text) VALUES (?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    // ประเภทข้อมูล: string, string, string, integer, string -> 'sssis'
    $stmt->bind_param("sssis", $db_image_path, $story_title, $body_text, $author_id, $tags_text);

    if ($stmt->execute()) {
        $response['success'] = true;
        $response['story_id'] = $conn->insert_id; // Story ID ที่เพิ่งสร้าง
        $response['cover_image_path'] = $db_image_path;
        $response['message'] = 'Story and cover image saved successfully.';
    } else {
        $response['message'] = 'Database query failed: ' . $stmt->error;
        // หากบันทึก DB ล้มเหลว ให้ลบไฟล์ที่อัปโหลดไปแล้วออก
        unlink($target_file); 
    }

    $stmt->close();

} else {
    // ล้มเหลวในการย้ายไฟล์
    $response['message'] = 'Failed to move uploaded file.';
}

$conn->close();
echo json_encode($response);
?>