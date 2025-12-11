<?php
session_start();
require 'config.php';

if (!isset($_SESSION['teacher_id']) && !isset($_SESSION['student_id'])) {
    header("Location: index.php");
    exit;
}

$group_id = intval($_POST['group_id']);
$message  = trim($_POST['message'] ?? '');
$fileName = null;
$filePath = null;

/* Identify sender */
if (isset($_SESSION['teacher_id'])) {
    $sender_id   = $_SESSION['teacher_id'];
    $sender_role = 'teacher';
} else {
    $sender_id   = $_SESSION['student_id'];
    $sender_role = 'student';
}

/* Handle file upload (optional) */
if (!empty($_FILES['attach']['name'])) {
    $allowed = ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'xls', 'xlsx', 'png', 'jpg', 'jpeg'];
    $ext = strtolower(pathinfo($_FILES['attach']['name'], PATHINFO_EXTENSION));
    if (in_array($ext, $allowed)) {
        $fileName = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $_FILES['attach']['name']);
        $filePath = 'uploads/' . $fileName;
        move_uploaded_file($_FILES['attach']['tmp_name'], $filePath);
    }
}

/* Insert chat message (even if message is empty but file exists) */
if ($message !== '' || $filePath) {
    $stmt = $conn->prepare(
        "INSERT INTO group_chats (group_id, sender_id, sender_role, message, file_name, file_path)
         VALUES (?, ?, ?, ?, ?, ?)"
    );
    $stmt->bind_param("iissss", $group_id, $sender_id, $sender_role, $message, $fileName, $filePath);
    $stmt->execute();

    $chat_id = $conn->insert_id;

    if ($sender_role === 'teacher') {
        // 🔵 Teacher → Insert unread for ALL students in group
        $q = $conn->prepare("SELECT student_id FROM group_members WHERE group_id = ?");
        $q->bind_param("i", $group_id);
        $q->execute();
        $res = $q->get_result();

        $ins = $conn->prepare("INSERT INTO chat_reads (chat_id, user_id, role, is_read) VALUES (?, ?, 'student', 0)");
        while ($row = $res->fetch_assoc()) {
            $student_id = $row['student_id'];
            $ins->bind_param("ii", $chat_id, $student_id);
            $ins->execute();
        }

    } elseif ($sender_role === 'student') {
        // 🔵 Student → Insert unread for guide (teacher)
        $q1 = $conn->prepare("SELECT guide_id FROM groups WHERE id = ?");
        $q1->bind_param("i", $group_id);
        $q1->execute();
        $guide_id = $q1->get_result()->fetch_assoc()['guide_id'] ?? 0;

        if ($guide_id) {
            $insT = $conn->prepare("INSERT INTO chat_reads (chat_id, user_id, role, is_read) VALUES (?, ?, 'teacher', 0)");
            $insT->bind_param("ii", $chat_id, $guide_id);
            $insT->execute();
        }

        // 🔵 Student → Insert unread for other students (not sender)
        $q2 = $conn->prepare("SELECT student_id FROM group_members WHERE group_id = ? AND student_id != ?");
        $q2->bind_param("ii", $group_id, $sender_id);
        $q2->execute();
        $res = $q2->get_result();

        $insS = $conn->prepare("INSERT INTO chat_reads (chat_id, user_id, role, is_read) VALUES (?, ?, 'student', 0)");
        while ($row = $res->fetch_assoc()) {
            $other_student = $row['student_id'];
            $insS->bind_param("ii", $chat_id, $other_student);
            $insS->execute();
        }
    }
}

/* Redirect back to the group chat tab */
header("Location: group_details.php?group_id=$group_id&tab=chat");
exit;
