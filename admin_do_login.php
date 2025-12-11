<?php
session_start();
require 'config.php';       // $conn = new mysqli(...)

$user = $_POST['username'] ?? '';
$pass = $_POST['password'] ?? '';

$stmt = $conn->prepare("SELECT id, password FROM admin WHERE username = ?");
$stmt->bind_param("s", $user);
$stmt->execute();
$admin = $stmt->get_result()->fetch_assoc();

if ($admin && password_verify($pass, $admin['password'])) {
    $_SESSION['admin_id'] = $admin['id'];
    $_SESSION['admin_user'] = $user;
    header("Location: admin_dashboard.php");
} else {
    echo "<script>alert('Invalid credentials');window.location='admin_login.php';</script>";
}
