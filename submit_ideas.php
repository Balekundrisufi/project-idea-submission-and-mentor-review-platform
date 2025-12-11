<?php
/* Submit or update 5 ideas – one shared set per group */
session_start();
require 'config.php';

if (!isset($_SESSION['student_id'])) {
    http_response_code(401); exit("Unauthorized");
}

$student_id = $_SESSION['student_id'];

/* 1. Get student's group */
$stmt = $conn->prepare("SELECT group_id FROM users WHERE id=?");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();
$group_id = $result['group_id'] ?? null;

if (!$group_id) {
    exit("You are not in a group.");
}

/* 2. Validate input */
$titles = $_POST['title'] ?? [];
$descs  = $_POST['desc'] ?? [];

if (count($titles) !== 5 || count($descs) !== 5) {
    exit("Exactly five ideas are required.");
}

foreach ($titles as $i => $t) {
    if (trim($t) === '' || trim($descs[$i]) === '') {
        exit("All ideas need both title and description.");
    }
}

/* 3. Check if ideas are currently editable */
$stmt = $conn->prepare("SELECT is_editable FROM project_ideas WHERE group_id=? LIMIT 1");
$stmt->bind_param("i", $group_id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();

if ($row && !$row['is_editable']) {
    exit("Ideas are locked by the teacher.");
}

/* 4. Delete previous ideas for this group */
$conn->query("DELETE FROM project_ideas WHERE group_id = $group_id");

/* 5. Insert new 5 ideas */
$stmt = $conn->prepare(
    "INSERT INTO project_ideas (group_id, student_id, idea_title, description, status, feedback, is_editable)
     VALUES (?, ?, ?, ?, 'Pending', '', 0)"
);
foreach ($titles as $i => $t) {
    $desc = $descs[$i];
    $stmt->bind_param("iiss", $group_id, $student_id, $t, $desc);
    $stmt->execute();
}

/* 6. Redirect back */
header("Location: student_dashboard.php");
exit;
?>
