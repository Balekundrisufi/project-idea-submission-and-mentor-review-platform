<?php
session_start();
include 'config.php';  // Your DB connection file

// Assume user is logged in and group_id, sender_id, sender_role are known:
$group_id = $_SESSION['group_id'];  
$sender_id = $_SESSION['user_id'];
$sender_role = $_SESSION['role'];  // 'student' or 'teacher'

// Message text from POST (optional if file is uploaded)
$message = trim($_POST['message'] ?? '');

// File upload handling
$file_path = null;
$file_name = null;

if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
    $uploadDir = 'uploads/';
    $originalName = basename($_FILES['file']['name']);
    $ext = pathinfo($originalName, PATHINFO_EXTENSION);
    $allowed = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png', 'gif', 'txt'];

    if (in_array(strtolower($ext), $allowed)) {
        // Unique filename to avoid conflicts
        $file_name = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $originalName);
        $file_path = $uploadDir . $file_name;

        if (!move_uploaded_file($_FILES['file']['tmp_name'], $file_path)) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to upload file']);
            exit;
        }
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'File type not allowed']);
        exit;
    }
}

// At least message or file must be sent
if ($message === '' && $file_path === null) {
    http_response_code(400);
    echo json_encode(['error' => 'No message or file sent']);
    exit;
}

// Insert into DB
$stmt = $conn->prepare("INSERT INTO group_chats (group_id, sender_id, sender_role, message, file_path, file_name) VALUES (?, ?, ?, ?, ?, ?)");
$stmt->bind_param("iissss", $group_id, $sender_id, $sender_role, $message, $file_path, $file_name);
if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Database insert failed']);
}
?>
