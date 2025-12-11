<?php
session_start();
require 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = trim($_POST['name']);
    $email    = trim($_POST['email']);
    $password = trim($_POST['password']);

    if (empty($name) || empty($email) || empty($password)) {
        header("Location: admin_dashboard.php#students&err=Please fill all fields");
        exit;
    }
	if (!preg_match('/^[a-zA-Z0-9._%+-]+@gmail\.com$/', $email)) {
		header("Location: admin_dashboard.php?tab=students&err=Invalid email format");
		exit;
	}
	if (!preg_match('/^[A-Za-z\s.]+$/', $name)) {
		header("Location: admin_dashboard.php?err=Invalid name format&tab=students");
		exit;
	}

    // Check if email already exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
		header("Location: admin_dashboard.php?tab=students&err=Email already exists");
        exit;
    }

    // Insert student
    $hashed = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $name, $email, $hashed);

    if ($stmt->execute()) {
		header("Location: admin_dashboard.php?tab=students&msg=Student added successfully");
        exit;
    } else {
		header("Location: admin_dashboard.php?tab=students&err=Error adding student");
        exit;
    }
}
?>
