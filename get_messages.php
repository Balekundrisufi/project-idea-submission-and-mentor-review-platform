<?php
session_start();
include 'config.php';

$group_id = $_SESSION['group_id'];

$sql = "SELECT gc.*, 
        CASE gc.sender_role 
          WHEN 'student' THEN u.name 
          WHEN 'teacher' THEN t.name 
          ELSE 'Unknown' END AS sender_name
        FROM group_chats gc
        LEFT JOIN users u ON gc.sender_role='student' AND gc.sender_id = u.id
        LEFT JOIN teachers t ON gc.sender_role='teacher' AND gc.sender_id = t.id
        WHERE gc.group_id = ? ORDER BY gc.sent_at ASC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $group_id);
$stmt->execute();
$result = $stmt->get_result();

$messages = [];
while ($row = $result->fetch_assoc()) {
    $messages[] = $row;
}

header('Content-Type: application/json');
echo json_encode($messages);
?>
