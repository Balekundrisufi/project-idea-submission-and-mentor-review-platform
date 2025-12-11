<?php
/*****************************************************************
 *  add_member.php  – AJAX endpoint
 *  Adds a student to a group (one-group-per-student rule)
 *****************************************************************/
session_start();
require 'config.php';

header('Content-Type: application/json');
ini_set('display_errors', 0);        // never echo PHP warnings to JSON
error_reporting(E_ALL);

try {
    /* ---------- basic validation ---------- */
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method.');
    }

    $group_id   = $_POST['group_id']  ?? null;
$student_id = $_POST['student_id']?? null;

// Validate both IDs as positive integers
if (!filter_var($group_id, FILTER_VALIDATE_INT, ["options" => ["min_range" => 1]]) ||
    !filter_var($student_id, FILTER_VALIDATE_INT, ["options" => ["min_range" => 1]])) {
    echo json_encode(["status"=>"error","message"=>"Invalid student ID."]); exit;
}


    /* ---------- 1. does the student exist? ---------- */
    $chk = $conn->prepare("SELECT id FROM users WHERE id = ?");
    $chk->bind_param("i", $student_id);
    $chk->execute();
    if (!$chk->get_result()->num_rows) {
        throw new Exception('Student ID not found. Ask admin to register the student first.');
    }

    /* ---------- 2. already in THIS group? ---------- */
    $dup = $conn->prepare("SELECT 1 FROM group_members WHERE group_id = ? AND student_id = ?");
    $dup->bind_param("ii", $group_id, $student_id);
    $dup->execute();
    if ($dup->get_result()->num_rows) {
        throw new Exception('Student is already in this group.');
    }

    /* ---------- 3. already in ANOTHER group? ---------- */
    $grp = $conn->prepare("
        SELECT g.id AS grp, t.name AS prof
        FROM   group_members gm
        JOIN   groups        g ON g.id = gm.group_id
        JOIN   teachers      t ON t.id = g.guide_id
        WHERE  gm.student_id = ? AND g.id <> ?
        LIMIT  1
    ");
    $grp->bind_param("ii", $student_id, $group_id);
    $grp->execute();
    $clash = $grp->get_result()->fetch_assoc();
    if ($clash) {
        throw new Exception(
            "Student is already in Group #{$clash['grp']} (added by Prof. {$clash['prof']}). "
        );
    }

    /* ---------- 4. insert membership ---------- */
    $ins = $conn->prepare("INSERT INTO group_members (group_id, student_id) VALUES (?, ?)");
    $ins->bind_param("ii", $group_id, $student_id);
    if (!$ins->execute()) {
        throw new Exception('Database insert error: '.$ins->error);
    }

    /* ---------- 5. update users.group_id ---------- */
    $upd = $conn->prepare("UPDATE users SET group_id = ? WHERE id = ?");
    $upd->bind_param("ii", $group_id, $student_id);
    $upd->execute();

    echo json_encode([
        "status"  => "success",
        "message" => "Student added to the group successfully!"
    ]);
} catch (Exception $e) {
    echo json_encode([
        "status"  => "error",
        "message" => $e->getMessage()
    ]);
}
