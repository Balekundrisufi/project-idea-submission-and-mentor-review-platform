<?php
/*****************************************************************
 * update_idea_status.php – teacher updates ALL idea statuses/feedback
 * --------------------------------------------------------------
 * Updated to process multiple ideas in one form
 * Extra logic:
 *   • After updating, if exactly ONE idea is Approved, mark it final
 *     and delete all others from that group.
 *****************************************************************/
session_start();
require 'config.php';

if (!isset($_SESSION['teacher_id'])) {
    http_response_code(401);
    exit('Unauthorized');
}

$idea_ids = $_POST['idea_id'] ?? [];
$feedbacks = $_POST['feedback'] ?? [];
$statuses = $_POST['status'] ?? [];
$group_id = intval($_POST['group_id'] ?? 0);

/* 1. Verify teacher owns this group */
$chk = $conn->prepare("SELECT 1 FROM groups WHERE id = ? AND guide_id = ?");
$chk->bind_param("ii", $group_id, $_SESSION['teacher_id']);
$chk->execute();
if (!$chk->get_result()->num_rows) {
    echo "Forbidden";
    exit;
}

/* 2. Update each idea with status and feedback */
foreach ($idea_ids as $i => $idea_id) {
    $idea_id = intval($idea_id);
    $status = isset($statuses[$i]) ? $statuses[$i] : 'Pending'; // Default to 'Pending'
    $fb = trim($feedbacks[$i]);

    $upd = $conn->prepare(
        "UPDATE project_ideas
         SET status = ?, feedback = ?
         WHERE id = ?"
    );
    $upd->bind_param("ssi", $status, $fb, $idea_id);
    $upd->execute();
}

/* 3. Check if exactly one Approved idea exists now */
$cntQ = $conn->prepare(
    "SELECT id FROM project_ideas
     WHERE group_id = ? AND status = 'Approved'"
);
$cntQ->bind_param("i", $group_id);
$cntQ->execute();
$res = $cntQ->get_result();
$approvedIdeas = $res->fetch_all(MYSQLI_ASSOC);

if (count($approvedIdeas) === 1) {
    $chosenId = intval($approvedIdeas[0]['id']);

    // Lock this idea as final_chosen and delete others
    $conn->begin_transaction();

    $lock = $conn->prepare(
        "UPDATE project_ideas
         SET final_chosen = 1, is_editable = 0
         WHERE id = ?"
    );
    $lock->bind_param("i", $chosenId);
    $lock->execute();

    $del = $conn->prepare(
        "DELETE FROM project_ideas
         WHERE group_id = ? AND id <> ?"
    );
    $del->bind_param("ii", $group_id, $chosenId);
    $del->execute();

    $conn->commit();
}

header("Location: group_details.php?group_id={$group_id}&tab=ideas");
exit;
?>
