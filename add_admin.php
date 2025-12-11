<?php
require 'config.php';

$username = 'admin';           
$plainPassword = 'admin123'; 

$hashedPassword = password_hash($plainPassword, PASSWORD_DEFAULT);

// Check if admin username already exists
$check = $conn->prepare("SELECT id FROM admin WHERE username = ?");
$check->bind_param("s", $username);
$check->execute();
$check->store_result();

if ($check->num_rows > 0) {
    echo "Username already exists.";
} else {
    $stmt = $conn->prepare("INSERT INTO admin (username, password) VALUES (?, ?)");
    $stmt->bind_param("ss", $username, $hashedPassword);

    if ($stmt->execute()) {
        echo "Admin added successfully.";
    } else {
        echo "Error adding admin: " . $stmt->error;
    }
}
?>
