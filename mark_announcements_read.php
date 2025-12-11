<?php
session_start();
require 'config.php';

if (!isset($_SESSION['student_id'])) exit;

$student_id = $_SESSION['student_id'];

// Get the student's group
$stmt = $conn->prepare("SELECT group_id FROM users WHERE id = ?");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$group_id = $stmt->get_result()->fetch_assoc()['group_id'] ?? null;

if ($group_id) {
    // Get the guide/teacher of the group
    $stmt = $conn->prepare("SELECT guide_id FROM groups WHERE id = ?");
    $stmt->bind_param("i", $group_id);
    $stmt->execute();
    $teacher_id = $stmt->get_result()->fetch_assoc()['guide_id'] ?? null;

    if ($teacher_id) {
        // Get all announcements by this teacher
        $stmt = $conn->prepare("SELECT id FROM announcements WHERE teacher_id = ?");
        $stmt->bind_param("i", $teacher_id);
        $stmt->execute();
        $res = $stmt->get_result();

        while ($row = $res->fetch_assoc()) {
            $announcement_id = $row['id'];

            // Delete the read entry if it exists
            $del = $conn->prepare("DELETE FROM announcement_reads WHERE student_id = ? AND announcement_id = ?");
            $del->bind_param("ii", $student_id, $announcement_id);
            $del->execute();
        }
    }
}
?>
