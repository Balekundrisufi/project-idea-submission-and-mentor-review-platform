<?php
session_start();
require 'config.php';

if (!isset($_SESSION['teacher_id'])) {
    header("Location: index.php");
    exit();
}

$teacher_id = $_SESSION['teacher_id'];
$msg = trim($_POST['announcement'] ?? '');

// ---------- file upload (optional) ----------
$fileName = $filePath = null;
if (!empty($_FILES['attachment']['name'])) {
    $allowed = ['pdf','doc','docx','ppt','pptx','xls','xlsx','png','jpg','jpeg'];
    $ext = strtolower(pathinfo($_FILES['attachment']['name'], PATHINFO_EXTENSION));
    if (in_array($ext, $allowed)) {
        $fileName = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/','_', $_FILES['attachment']['name']);
        $filePath = 'uploads/' . $fileName;
        move_uploaded_file($_FILES['attachment']['tmp_name'], $filePath);
    }
}

// ✅ If both message and file are empty, stop
if ($msg === '' && $filePath === null) {
    header("Location: teacher_dashboard.php#tab-announcement");
    exit();
}

// ---------- insert announcement ----------
$stmt = $conn->prepare("INSERT INTO announcements (teacher_id, message, file_name, file_path) VALUES (?,?,?,?)");
$stmt->bind_param("isss", $teacher_id, $msg, $fileName, $filePath);
$stmt->execute();

// Get the ID of the inserted announcement
$announcement_id = $stmt->insert_id;

// ---------- insert into announcement_reads ----------
$studentStmt = $conn->prepare("
    SELECT u.id 
    FROM users u
    JOIN group_members gm ON u.id = gm.student_id
    JOIN groups g ON g.id = gm.group_id
    WHERE g.guide_id = ?
");
$studentStmt->bind_param("i", $teacher_id);
$studentStmt->execute();
$result = $studentStmt->get_result();

$insertReadStmt = $conn->prepare("INSERT INTO announcement_reads (announcement_id, student_id, is_read) VALUES (?, ?, 0)");
while ($row = $result->fetch_assoc()) {
    $student_id = $row['id'];
    $insertReadStmt->bind_param("ii", $announcement_id, $student_id);
    $insertReadStmt->execute();
}

header("Location: teacher_dashboard.php#tab-announcement");
exit;
?>
