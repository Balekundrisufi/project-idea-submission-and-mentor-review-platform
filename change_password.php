<?php
session_start();
require 'config.php';

if (!isset($_SESSION['teacher_id'])) {
    echo json_encode(["error" => "Not logged in."]);
    exit;
}

$new = $_POST['new_password'] ?? '';
$confirm = $_POST['confirm_password'] ?? '';

if (strlen($new) < 6) {
    echo json_encode(["error" => "Password must be at least 6 characters."]);
    exit;
}

if ($new !== $confirm) {
    echo json_encode(["error" => "Passwords do not match."]);
    exit;
}

$hashed = password_hash($new, PASSWORD_DEFAULT);
$update = $conn->prepare("UPDATE teachers SET password=? WHERE id=?");
$update->bind_param("si", $hashed, $_SESSION['teacher_id']);
$update->execute();

echo json_encode(["success" => true]);
