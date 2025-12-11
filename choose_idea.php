<?php
session_start();
require 'config.php';

$id = intval($_GET['id'] ?? 0);
$student_id = $_SESSION['student_id'] ?? 0;

if (!$id || !$student_id) {
    exit('Invalid request');
}

// 1. Fetch idea + group info
$q = $conn->prepare("
    SELECT group_id 
    FROM project_ideas 
    WHERE id = ? 
      AND status = 'Approved' 
      AND group_id IN (
          SELECT group_id FROM group_members WHERE student_id = ?
      )
");
$q->bind_param("ii", $id, $student_id);
$q->execute();
$result = $q->get_result();
if (!$result->num_rows) {
    exit('Idea not found or not allowed');
}

$group_id = $result->fetch_assoc()['group_id'];

// 2. Delete all other ideas in the group
$del = $conn->prepare("DELETE FROM project_ideas WHERE group_id = ? AND id <> ?");
$del->bind_param("ii", $group_id, $id);
$del->execute();

// 3. Update the chosen idea as final
$upd = $conn->prepare("
    UPDATE project_ideas 
    SET final_chosen = 1, is_editable = 0, status = 'Approved'
    WHERE id = ?
");
$upd->bind_param("i", $id);
$upd->execute();

// 4. Redirect to student dashboard
header("Location: student_dashboard.php");
exit;
?>
