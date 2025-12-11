<?php
session_start();
require 'config.php';

if (!isset($_SESSION['student_id'])) exit;

$student_id = $_SESSION['student_id'];

// Get group_id of the student
$stmt = $conn->prepare("SELECT group_id FROM users WHERE id = ?");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$group_id = $stmt->get_result()->fetch_assoc()['group_id'] ?? null;

if ($group_id) {
    // Get all group chat IDs not sent by the student
    $stmt = $conn->prepare("SELECT id FROM group_chats WHERE group_id = ? AND sender_id != ?");
    $stmt->bind_param("ii", $group_id, $student_id);
    $stmt->execute();
    $res = $stmt->get_result();

    // Delete from chat_reads (clean method)
    while ($row = $res->fetch_assoc()) {
        $chat_id = $row['id'];

        $delete = $conn->prepare("DELETE FROM chat_reads WHERE chat_id = ? AND user_id = ? AND role = 'student'");
        $delete->bind_param("ii", $chat_id, $student_id);
        $delete->execute();
    }
}
?>
