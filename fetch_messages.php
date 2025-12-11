<?php
session_start();
require 'config.php';

$group_id   = intval($_GET['group_id'] ?? 0);
$student_id = $_SESSION['student_id'] ?? 0;

header('Content-Type: application/json');

if (!$group_id || !$student_id) {
    echo '[]';
    exit;
}

// Step 1: Fetch messages
$q = $conn->prepare(
    "SELECT id, sender_role, message, file_path, file_name, sent_at
     FROM group_chats
     WHERE group_id = ?
     ORDER BY id DESC");
$q->bind_param("i", $group_id);
$q->execute();
$res = $q->get_result();

$out = [];
$message_ids_to_remove = [];

while ($row = $res->fetch_assoc()) {
    $label = ($row['sender_role'] === 'teacher') ? 'Teacher' : 'Student';

    $out[] = [
        'sender'   => $label,
        'message'  => $row['message'],
        'file'     => $row['file_path'] ?? '',
        'fileName' => $row['file_name'] ?? '',
        'time'     => date('d M H:i', strtotime($row['sent_at']))
    ];

    // Mark teacher messages as read (delete from chat_reads)
    if ($row['sender_role'] === 'teacher') {
        $message_ids_to_remove[] = $row['id'];
    }
}

// Step 2: Delete existing unread entries from chat_reads (for teacher messages)
if (!empty($message_ids_to_remove)) {
    $placeholders = implode(',', array_fill(0, count($message_ids_to_remove), '?'));
    $types = str_repeat('i', count($message_ids_to_remove)) . 'i';  // all message ids + student_id

    $sql = "DELETE FROM chat_reads 
            WHERE chat_id IN ($placeholders) AND user_id = ? AND role = 'student'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...[...$message_ids_to_remove, $student_id]);
    $stmt->execute();
}

echo json_encode($out);
