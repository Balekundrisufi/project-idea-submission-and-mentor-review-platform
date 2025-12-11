<?php
session_start();
include 'config.php';

if (!isset($_SESSION['teacher_id'])) {
    exit;
}

$teacher_id = $_SESSION['teacher_id'];

/* ▸ Delete announcements older than 90 days */
$old = $conn->prepare(
    "SELECT id, file_path FROM announcements WHERE teacher_id = ? AND created_at < NOW() - INTERVAL 90 DAY"
);
$old->bind_param("i", $teacher_id);
$old->execute();
$resOld = $old->get_result();

while ($row = $resOld->fetch_assoc()) {
    // Delete attached file if exists
    if (!empty($row['file_path']) && file_exists($row['file_path'])) {
        unlink($row['file_path']);
    }

    // Delete from announcement_reads table
    $delReads = $conn->prepare("DELETE FROM announcement_reads WHERE announcement_id = ?");
    $delReads->bind_param("i", $row['id']);
    $delReads->execute();

    // Delete the announcement itself
    $delAnn = $conn->prepare("DELETE FROM announcements WHERE id = ?");
    $delAnn->bind_param("i", $row['id']);
    $delAnn->execute();
}

/* ▸ Fetch current announcements */
$res = $conn->prepare(
    "SELECT * FROM announcements WHERE teacher_id = ? ORDER BY created_at DESC"
);
$res->bind_param("i", $teacher_id);
$res->execute();
$result = $res->get_result();

while ($row = $result->fetch_assoc()) {
    echo '<div style="margin-bottom:10px;">';
    echo '<strong>You:</strong> ' . nl2br(htmlspecialchars($row['message'])) . '<br>';
    echo '<small style="color:gray;">' . date("d M Y H:i", strtotime($row['created_at'])) . '</small>';
    if (!empty($row['file_path'])) {
        echo '<br><a href="' . htmlspecialchars($row['file_path']) . '" target="_blank">📎 '
           . htmlspecialchars($row['file_name']) . '</a>';
    }
    echo '</div><hr>';
}
?>
