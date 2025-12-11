<?php
session_start();
require 'config.php';

if (!isset($_SESSION['student_id'])) {
    header("Location: login.html");
    exit;
}

$idea_id = intval($_GET['id'] ?? 0);
$type = $_GET['type'] ?? 'description';

if (!in_array($type, ['description', 'feedback'])) {
    echo "Invalid request.";
    exit;
}

$stmt = $conn->prepare("SELECT idea_title, description, feedback FROM project_ideas WHERE id = ?");
$stmt->bind_param("i", $idea_id);
$stmt->execute();
$res = $stmt->get_result();
$idea = $res->fetch_assoc();
$stmt->close();

if (!$idea) {
    echo "Idea not found.";
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title><?= ucfirst($type) ?> - <?= htmlspecialchars($idea['idea_title']) ?></title>
  <style>
    body { font-family: Arial; margin: 2rem; background: #f4f7fa; }
    .card { background: #fff; padding: 1.5rem; border-radius: 8px; max-width: 600px; margin: auto; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
    h2 { color: #007bff; margin-top: 0; }
    p { line-height: 1.6; white-space: pre-wrap; }
    .back-btn { margin-top: 1rem; display: inline-block; padding: 0.5rem 1rem; background: #007bff; color: white; text-decoration: none; border-radius: 4px; }
  </style>
</head>
<body>
  <div class="card">
    <h2><?= htmlspecialchars($idea['idea_title']) ?></h2>
    <h4><?= ucfirst($type) ?></h4>
    <p>
      <?php
        if ($type === 'description') {
            echo $idea['description'] ? htmlspecialchars($idea['description']) : 'No description available.';
        } else {
            echo $idea['feedback'] ? htmlspecialchars($idea['feedback']) : 'No feedback yet.';
        }
      ?>
    </p>

    <a href="student_dashboard.php" class="back-btn">← Back to Dashboard</a>
  </div>
</body>
</html>
