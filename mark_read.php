<?php
session_start();
require 'config.php';

if (!isset($_SESSION['teacher_id'])) {
    http_response_code(401);
    exit("Unauthorized");
}

$teacher_id = $_SESSION['teacher_id'];
$group_id = intval($_POST['group_id'] ?? 0);

if ($group_id <= 0) {
    http_response_code(400);
    exit("Invalid group");
}

// 🧠 Delete in a single query
$deleteStmt = $conn->prepare("
    DELETE cr FROM chat_reads cr
    JOIN group_chats gc ON gc.id = cr.chat_id
    WHERE gc.group_id = ? AND cr.user_id = ? AND cr.role = 'teacher' AND gc.sender_id != ?
");
$deleteStmt->bind_param("iii", $group_id, $teacher_id, $teacher_id);
$deleteStmt->execute();

echo "Deleted read entries";
?>
