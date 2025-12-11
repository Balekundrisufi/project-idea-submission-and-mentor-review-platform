<?php
session_start();
include 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $phase1 = $_POST['phase1']; // array: student_id => p1 mark
    $phase2 = $_POST['phase2'];
    $phase3 = $_POST['phase3'];

    foreach ($phase1 as $student_id => $p1) {
        $p2 = isset($phase2[$student_id]) ? $phase2[$student_id] : 0;
        $p3 = isset($phase3[$student_id]) ? $phase3[$student_id] : 0;

        $stmt = $conn->prepare("UPDATE users SET phase1 = ?, phase2 = ?, phase3 = ? WHERE id = ?");
        $stmt->bind_param("dddi", $p1, $p2, $p3, $student_id);
        $stmt->execute();
    }
}
?>
