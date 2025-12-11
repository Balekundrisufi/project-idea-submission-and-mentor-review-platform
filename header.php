<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <link rel="stylesheet" href="style.css">
    <title>Student Dashboard</title>
</head>
<body>
<nav class="navbar">
    <ul>
        <li><a href="project_ideas.php" class="<?= basename($_SERVER['PHP_SELF'])=='project_ideas.php'?'active':'' ?>">Project Ideas</a></li>
        <li><a href="group_chat.php" class="<?= basename($_SERVER['PHP_SELF'])=='group_chat.php'?'active':'' ?>">Group Chat</a></li>
        <li><a href="announcements.php" class="<?= basename($_SERVER['PHP_SELF'])=='announcements.php'?'active':'' ?>">Announcements</a></li>
        <li><a href="profile.php" class="<?= basename($_SERVER['PHP_SELF'])=='profile.php'?'active':'' ?>">Profile</a></li>
        <li><a href="logout.php">Logout</a></li>
    </ul>
</nav>
<div class="container">
