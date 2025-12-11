<?php
session_start();
require 'db_connect.php';

if(!isset($_SESSION['group_id']) || !isset($_POST['keep_id'])){
    header('Location: project_ideas.php');
    exit;
}
$group_id = $_SESSION['group_id'];
$keep_id = intval($_POST['keep_id']);

// Verify keep_id belongs to group and is approved
$stmt = $conn->prepare("SELECT id FROM project_ideas WHERE id=? AND group_id=? AND status='approved'");
$stmt->bind_param("ii", $keep_id,$group_id);
$stmt->execute();
$res=$stmt->get_result();
if($res->num_rows==0){
    header('Location: project_ideas.php?err=invalid');
    exit;
}
$stmt->close();

// Delete all other ideas of the group
$stmt = $conn->prepare("DELETE FROM project_ideas WHERE group_id=? AND id<>?");
$stmt->bind_param("ii", $group_id,$keep_id);
$stmt->execute();
$stmt->close();

// Optionally set chosen idea status to 'selected'
$conn->query("UPDATE project_ideas SET status='selected' WHERE id="$keep_id);

header('Location: project_ideas.php?chosen=1');
?>
