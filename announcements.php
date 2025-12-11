<?php
session_start();
include 'config.php';

$user_role = $_SESSION['role']; // 'student' or 'teacher'
$user_id = $_SESSION['id'];

// Handle new announcement (only for teachers)
if ($user_role === 'teacher' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $message = trim($_POST['message']);
    if ($message !== '') {
        $stmt = $conn->prepare("INSERT INTO announcements (teacher_id, message) VALUES (?, ?)");
        $stmt->bind_param("is", $user_id, $message);
        $stmt->execute();
        header("Location: announcements.php");
        exit;
    }
}

// Fetch announcements (latest first)
$sql = "SELECT a.*, t.name AS teacher_name FROM announcements a JOIN teachers t ON a.teacher_id = t.id ORDER BY a.created_at DESC";
$result = $conn->query($sql);

?>

<?php include 'header.php'; ?>

<h2>Announcements</h2>

<?php if ($user_role === 'teacher'): ?>
    <form method="POST" style="margin-bottom: 20px;">
        <textarea name="message" required placeholder="Write your announcement here..." rows="4" style="width:100%;"></textarea><br><br>
        <button type="submit">Post Announcement</button>
    </form>
<?php endif; ?>

<div>
    <?php if ($result->num_rows > 0): ?>
        <?php while($row = $result->fetch_assoc()): ?>
            <div style="border:1px solid #ccc; padding:10px; margin-bottom:10px;">
                <strong><?= htmlspecialchars($row['teacher_name']) ?></strong> 
                <small style="color:gray;">(<?= $row['created_at'] ?>)</small>
                <p><?= nl2br(htmlspecialchars($row['message'])) ?></p>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <p>No announcements found.</p>
    <?php endif; ?>
</div>
