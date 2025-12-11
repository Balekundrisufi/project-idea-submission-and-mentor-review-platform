<?php
session_start();
require 'config.php';

if (!isset($_SESSION['teacher_id'])) {
    header("Location: index.php");
    exit;
}

$teacher_id = $_SESSION['teacher_id'];
$group_id   = isset($_GET['group_id']) ? intval($_GET['group_id']) : 0;

// Validate group ownership
$checkStmt = $conn->prepare("SELECT id FROM groups WHERE id = ? AND guide_id = ?");
$checkStmt->bind_param("ii", $group_id, $teacher_id);
$checkStmt->execute();
$result = $checkStmt->get_result();

if ($result->num_rows === 0) {
    echo "Invalid group or you are not authorized to delete this group.";
    exit;
}

// --- Get all student IDs from this group ---
$studentIds = [];
$stuQ = $conn->prepare("SELECT student_id FROM group_members WHERE group_id = ?");
$stuQ->bind_param("i", $group_id);
$stuQ->execute();
$stuRes = $stuQ->get_result();
while ($row = $stuRes->fetch_assoc()) {
    $studentIds[] = $row['student_id'];
}
$studentIdList = $studentIds ? implode(',', $studentIds) : '0';

// --- Delete uploaded chat files ---
$files = $conn->prepare("SELECT file_path FROM group_chats WHERE group_id = ? AND file_path IS NOT NULL");
$files->bind_param("i", $group_id);
$files->execute();
$res = $files->get_result();
while ($row = $res->fetch_assoc()) {
    $file = $row['file_path'];
    if ($file && file_exists($file)) {
        unlink($file);
    }
}

// --- Delete chat_reads for this group's chats ---
$conn->query("DELETE cr FROM chat_reads cr 
              JOIN group_chats gc ON cr.chat_id = gc.id 
              WHERE gc.group_id = $group_id");

// --- Delete announcement_reads for students in this group ---
if ($studentIdList !== '0') {
    // Get announcement IDs by this teacher
    $annIds = [];
    $annQ = $conn->prepare("SELECT id FROM announcements WHERE teacher_id = ?");
    $annQ->bind_param("i", $teacher_id);
    $annQ->execute();
    $annRes = $annQ->get_result();
    while ($r = $annRes->fetch_assoc()) {
        $annIds[] = $r['id'];
    }

    if (count($annIds)) {
        $annIdList = implode(',', $annIds);
        $conn->query("DELETE FROM announcement_reads 
                      WHERE student_id IN ($studentIdList) AND announcement_id IN ($annIdList)");
    }
}

// --- Update users.group_id = NULL for group members ---
$conn->query("UPDATE users 
              SET group_id = NULL 
              WHERE id IN ($studentIdList)");

// --- Delete related group records ---
$conn->query("DELETE FROM group_chats WHERE group_id = $group_id");
$conn->query("DELETE FROM project_ideas WHERE group_id = $group_id");
$conn->query("DELETE FROM group_members WHERE group_id = $group_id");

// --- Delete the group ---
$delGroup = $conn->prepare("DELETE FROM groups WHERE id = ?");
$delGroup->bind_param("i", $group_id);

if ($delGroup->execute()) {
    header("Location: teacher_dashboard.php?msg=Group deleted successfully");
    exit;
} else {
    echo "Error deleting group: " . $delGroup->error;
}
?>
