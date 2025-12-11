<?php
session_start();
require 'config.php';

$teacher_id = $_SESSION['teacher_id'] ?? null;
$group_id   = $_GET['group_id']        ?? null;
$student_id = $_GET['student_id']      ?? null;

if(!$teacher_id || !$group_id || !$student_id){
    echo "Missing parameters"; exit;
}

// Verify group belongs to teacher
$chk = $conn->prepare("SELECT 1 FROM groups WHERE id=? AND guide_id=?");
$chk->bind_param("ii", $group_id, $teacher_id);
$chk->execute();
if(!$chk->get_result()->fetch_column()){
    echo "Not authorised"; exit;
}

// ✅ Delete from group_members
$del = $conn->prepare("DELETE FROM group_members WHERE group_id=? AND student_id=?");
$del->bind_param("ii", $group_id, $student_id);
$del->execute();

// ✅ Update users table - reset group_id
$upd = $conn->prepare("UPDATE users SET group_id=NULL WHERE id=?");
$upd->bind_param("i", $student_id);
$upd->execute();

// ✅ Delete group chat messages and files by the student
$chatQ = $conn->prepare("SELECT file_path FROM group_chats 
                          WHERE group_id=? AND sender_id=? AND sender_role='student'");
$chatQ->bind_param("ii", $group_id, $student_id);
$chatQ->execute();
$res = $chatQ->get_result();
while($row = $res->fetch_assoc()){
    if(!empty($row['file_path'])){
        $filepath = "uploads/" . $row['file_path'];
        if(file_exists($filepath)){
            unlink($filepath); // Delete the file
        }
    }
}
// Now delete the chat entries
$chatDel = $conn->prepare("DELETE FROM group_chats 
                            WHERE group_id=? AND sender_id=? AND sender_role='student'");
$chatDel->bind_param("ii", $group_id, $student_id);
$chatDel->execute();

// ✅ Delete chat_reads related to those chats
$readDel = $conn->prepare("DELETE cr FROM chat_reads cr 
                           INNER JOIN group_chats gc ON gc.id = cr.chat_id
                           WHERE gc.group_id = ? AND gc.sender_id = ? AND gc.sender_role = 'student'");
$readDel->bind_param("ii", $group_id, $student_id);
$readDel->execute();

// ✅ Delete announcement_reads
$delAR = $conn->prepare("DELETE FROM announcement_reads WHERE student_id = ?");
$delAR->bind_param("i", $student_id);
$delAR->execute();

// ✅ Done - Redirect back
header("Location: group_details.php?group_id=$group_id&tab=members");
exit;
?>
