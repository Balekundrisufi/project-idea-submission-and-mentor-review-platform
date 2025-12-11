<?php
session_start();
require 'config.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_username = trim($_POST['name']);
    $new_password = trim($_POST['new']);
    $confirm_password = trim($_POST['confirm']);

    if (empty($new_username) || empty($new_password) || empty($confirm_password)) {
        header("Location: admin_dashboard.php?tab=profile&err=Please fill all fields");
        exit;
    }

    if ($new_password !== $confirm_password) {
        header("Location: admin_dashboard.php?tab=profile&err=Passwords do not match");
        exit;
    }

    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

    $stmt = $conn->prepare("UPDATE admin SET username=?, password=? WHERE id=?");
    $stmt->bind_param("ssi", $new_username, $hashed_password, $_SESSION['admin_id']);

    if ($stmt->execute()) {
        // Update session username as well
        $_SESSION['admin_user'] = $new_username;
        header("Location: admin_dashboard.php?tab=profile&msg=Admin credentials updated successfully");
        exit;
    } else {
        header("Location: admin_dashboard.php?tab=profile&err=Error updating credentials");
        exit;
    }
}
?>
