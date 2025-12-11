<?php
session_start();
require 'config.php';

$id = intval($_GET['id']);

if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit;
}

// Fetch student data
$student = $conn->query("SELECT * FROM users WHERE id=$id")->fetch_assoc();
if (!$student) {
    die("Student not found.");
}

$msg = $err = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $newpass = trim($_POST['password']);

    // ✅ Email format validation
    if (!preg_match('/^[a-zA-Z0-9._%+-]+@gmail\.com$/', $email)) {
        $err = "Invalid email format. Only Gmail addresses are allowed.";
    } 
	else if (!preg_match('/^[A-Za-z\s.]+$/', $name)) {
		$err = "Invalid name format. Only characters are allowed.";
	}
	else {
        // ✅ Check if email exists for other users
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->bind_param("si", $email, $id);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $err = "Email already exists with another student.";
        } else {
            if ($newpass != '') {
                $hash = password_hash($newpass, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE users SET name=?, email=?, password=? WHERE id=?");
                $stmt->bind_param("sssi", $name, $email, $hash, $id);
            } else {
                $stmt = $conn->prepare("UPDATE users SET name=?, email=? WHERE id=?");
                $stmt->bind_param("ssi", $name, $email, $id);
            }

            if ($stmt->execute()) {
                $msg = "Student details updated successfully.";
                $student = $conn->query("SELECT * FROM users WHERE id=$id")->fetch_assoc();
            } else {
                $err = "Error updating student.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Student</title>
    <style>
        body { font-family: Arial; background:#eef1f4; padding: 20px; }
        .container { background:#fff; padding:20px; border-radius:8px; max-width:600px; margin:auto; }
        h2 { margin-top:0; }
        input, button { padding:8px; width:100%; margin-bottom:10px; }
        button { background:#007bff; color:white; border:none; border-radius:4px; cursor:pointer; }
        button:hover { background:#0056b3; }
        .msg { color:green; }
        .err { color:red; }
        .top-bar { display:flex; justify-content:space-between; align-items:center; margin-bottom:15px; }
        .back-btn { background:#dc3545; padding:6px 12px; color:white; text-decoration:none; border-radius:4px; }
    </style>
</head>
<body>

<div class="container">
    <div class="top-bar">
        <h2>Edit Student</h2>
        <a class="back-btn" href="admin_dashboard.php?tab=students#students">← Back</a>
    </div>

    <?php if ($msg) echo "<p class='msg'>$msg</p>"; ?>
    <?php if ($err) echo "<p class='err'>$err</p>"; ?>

    <form method="POST">
        <label>Name:</label><br>
        <input name="name" value="<?= htmlspecialchars($student['name']) ?>" required><br>

        <label>Email:</label><br>
        <input name="email" type="email" pattern="[a-zA-Z0-9._%+-]+@gmail\.com" value="<?= htmlspecialchars($student['email']) ?>" required><br>

        <label>New Password (leave blank to keep same):</label><br>
        <input type="password" name="password"><br>

        <button type="submit">Save Changes</button>
    </form>
</div>

</body>
</html>
