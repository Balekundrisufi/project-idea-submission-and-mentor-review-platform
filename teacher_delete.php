<?php
session_start();
require 'config.php';

/* --- 1. Authorize ------------------------------------------------------- */
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

/* --- 2. Get teacher ID from query string -------------------------------- */
$teacher_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($teacher_id === 0) {
    echo "Invalid teacher ID.";
    exit;
}

/* --- 3. Get all group IDs under this teacher ---------------------------- */
$grpStmt = $conn->prepare("SELECT id FROM groups WHERE guide_id = ?");
$grpStmt->bind_param("i", $teacher_id);
$grpStmt->execute();
$grpRes = $grpStmt->get_result();

$groupIds = [];
while ($g = $grpRes->fetch_assoc()) {
    $groupIds[] = $g['id'];
}
$idList = $groupIds ? implode(',', $groupIds) : '0';

/* --- 4. Get all student IDs under those groups -------------------------- */
$studentIds = [];
if ($idList !== '0') {
    $studentStmt = $conn->query("SELECT student_id FROM group_members WHERE group_id IN ($idList)");
    while ($s = $studentStmt->fetch_assoc()) {
        $studentIds[] = $s['student_id'];
    }
}
$studentIdList = $studentIds ? implode(',', $studentIds) : '0';

/* --- 5. Delete group chat files ----------------------------------------- */
if ($idList !== '0') {
    $fileStmt = $conn->query("SELECT file_path FROM group_chats WHERE group_id IN ($idList) AND file_path IS NOT NULL");
    while ($row = $fileStmt->fetch_assoc()) {
        $file = $row['file_path'];
        if ($file && file_exists($file)) unlink($file);
    }

    // Delete chat_reads for all group chats
    $conn->query("DELETE cr FROM chat_reads cr JOIN group_chats gc ON cr.chat_id = gc.id WHERE gc.group_id IN ($idList)");
    $conn->query("DELETE FROM group_chats WHERE group_id IN ($idList)");
    $conn->query("DELETE FROM project_ideas WHERE group_id IN ($idList)");
    $conn->query("DELETE FROM group_members WHERE group_id IN ($idList)");
    $conn->query("UPDATE users SET group_id = NULL WHERE group_id IN ($idList)");
    $conn->query("DELETE FROM groups WHERE id IN ($idList)");
}

/* --- 6. Delete announcement files --------------------------------------- */
$annStmt = $conn->query("SELECT id, file_path FROM announcements WHERE teacher_id = $teacher_id");
$announcementIds = [];

while ($row = $annStmt->fetch_assoc()) {
    $announcementIds[] = $row['id'];
    if ($row['file_path'] && file_exists($row['file_path'])) {
        unlink($row['file_path']);
    }
}
$announcementIdList = $announcementIds ? implode(',', $announcementIds) : '0';

/* --- 7. Delete from announcement_reads (for related announcements) ------ */
if ($announcementIdList !== '0') {
    $conn->query("DELETE FROM announcement_reads WHERE announcement_id IN ($announcementIdList)");
}

/* --- 8. Delete announcements by this teacher ---------------------------- */
$conn->query("DELETE FROM announcements WHERE teacher_id = $teacher_id");

/* --- 9. Finally delete the teacher -------------------------------------- */
$delStmt = $conn->prepare("DELETE FROM teachers WHERE id = ?");
$delStmt->bind_param("i", $teacher_id);

if ($delStmt->execute()) {
	header("Location: admin_dashboard.php?tab=teachers&msg=Teacher deleted successfully");
} else {
    echo "Error deleting teacher: " . $delStmt->error;
}
?>
