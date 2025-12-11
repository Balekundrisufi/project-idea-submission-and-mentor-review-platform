<?php
session_start();
require 'config.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit;
}

$student_id = intval($_GET['id']);

// ✅ Check if student exists
$check = $conn->prepare("SELECT id FROM users WHERE id = ?");
$check->bind_param("i", $student_id);
$check->execute();
$check->store_result();

if ($check->num_rows == 0) {
    header("Location: admin_dashboard.php?tab=students&err=Student not found");
    exit;
}

/* ▸ Get group IDs where this student is a member */
$groupIds = [];
$grpStmt = $conn->prepare("SELECT group_id FROM group_members WHERE student_id = ?");
$grpStmt->bind_param("i", $student_id);
$grpStmt->execute();
$grpRes = $grpStmt->get_result();

while ($g = $grpRes->fetch_assoc()) {
    $groupIds[] = $g['group_id'];
}

$idList = $groupIds ? implode(',', $groupIds) : '0';

/* ▸ Delete chat files if any */
if ($idList !== '0') {
    // ✅ Fetch all files linked to this student's chat messages
    $fileStmt = $conn->query(
        "SELECT file_path FROM group_chats 
         WHERE group_id IN ($idList) AND sender_id = $student_id AND sender_role='student' AND file_path IS NOT NULL"
    );

    while ($row = $fileStmt->fetch_assoc()) {
        $file = $row['file_path'];
        $filepath = "uploads/" . $file;
        if ($file && file_exists($filepath)) {
            unlink($filepath);
        }
    }

    // ✅ Delete chat_reads for this student's chats
    $conn->query(
        "DELETE cr FROM chat_reads cr 
         JOIN group_chats gc ON cr.chat_id = gc.id 
         WHERE gc.group_id IN ($idList) AND gc.sender_id = $student_id AND gc.sender_role='student'"
    );

    // ✅ Delete the student's chat messages
    $conn->query(
        "DELETE FROM group_chats 
         WHERE group_id IN ($idList) AND sender_id = $student_id AND sender_role='student'"
    );
}

/* ▸ Remove from group_members */
$gmDel = $conn->prepare("DELETE FROM group_members WHERE student_id = ?");
$gmDel->bind_param("i", $student_id);
$gmDel->execute();

/* ▸ Remove announcement_reads */
$arDel = $conn->prepare("DELETE FROM announcement_reads WHERE student_id = ?");
$arDel->bind_param("i", $student_id);
$arDel->execute();

/* ▸ Remove from users table */
$delStmt = $conn->prepare("DELETE FROM users WHERE id = ?");
$delStmt->bind_param("i", $student_id);

if ($delStmt->execute()) {
    header("Location: admin_dashboard.php?tab=students&msg=Student deleted successfully");
    exit;
} else {
    header("Location: admin_dashboard.php?tab=students&err=Failed to delete student");
    exit;
}
?>
