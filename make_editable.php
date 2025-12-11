<?php
/* make_editable.php – teacher re-opens editing for a group */
session_start();
require 'config.php';

if (!isset($_SESSION['teacher_id'])) {
    http_response_code(401);
    exit('Unauthorized');
}

$group_id = intval($_POST['group_id'] ?? 0);
$teacher  = $_SESSION['teacher_id'];

/* ── Verify the group really belongs to this teacher ──────────────────── */
$chk = $conn->prepare("SELECT 1 FROM groups WHERE id = ? AND guide_id = ?");
$chk->bind_param("ii", $group_id, $teacher);
$chk->execute();
if (!$chk->get_result()->num_rows) {
    exit('Forbidden');
}

/* ── Re-open: clear final flag, make editable, reset to Pending ───────── */
$reopen = $conn->prepare(
   "UPDATE project_ideas
    SET final_chosen = 0,
        is_editable  = 1,
        status       = 'Pending'
    WHERE group_id = ?"
);
$reopen->bind_param("i", $group_id);
$reopen->execute();

header("Location: group_details.php?group_id=$group_id&tab=ideas");
exit;
?>
