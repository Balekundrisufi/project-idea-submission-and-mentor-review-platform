<?php
session_start();
require 'config.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit;
}

$id = intval($_GET['id']);

// Fetch teacher data
$teacher = $conn->query("SELECT * FROM teachers WHERE id=$id")->fetch_assoc();
if (!$teacher) {
    die("Teacher not found.");
}

$msg = $err = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $newpass = trim($_POST['password']);

    if (!preg_match('/^[a-zA-Z0-9._%+-]+@gmail\.com$/', $email)) {
        $err = "Invalid email format. Only Gmail addresses are allowed.";
    }
    else if (!preg_match('/^[A-Za-z\s.]+$/', $name)) {
        $err = "Invalid name format. Only characters are allowed.";
    }
    else {
        $stmt = $conn->prepare("SELECT id FROM teachers WHERE email = ? AND id != ?");
        $stmt->bind_param("si", $email, $id);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $err = "Email already exists with another teacher.";
        } else {
            if ($newpass != '') {
                $hash = password_hash($newpass, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE teachers SET name=?, email=?, password=? WHERE id=?");
                $stmt->bind_param("sssi", $name, $email, $hash, $id);
            } else {
                $stmt = $conn->prepare("UPDATE teachers SET name=?, email=? WHERE id=?");
                $stmt->bind_param("ssi", $name, $email, $id);
            }

            if ($stmt->execute()) {
                $msg = "Teacher details updated successfully.";
                $teacher = $conn->query("SELECT * FROM teachers WHERE id=$id")->fetch_assoc();
            } else {
                $err = "Error updating teacher.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Edit Teacher</title>
    <style>
        body {
             font-family: Arial; background:aqua; margin:0; padding: 1px; 
            box-shadow: 0 20px 60px rgba(5, 1, 1, 0.89);
            }

        .header {
            width: 100%;
            background-color: #ffe8cc;
            display: flex;
            justify-content: flex-start;
            align-items: center;
            padding: 10px 30px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }

        .header img {
            height: 80px;
            width: 90px;
            margin-right: 40px;
            border: 3px solid black;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
        }

        .header-text {
            color: #291911;
            font-size: 24px;
            font-weight: 600;
            line-height: 1.4;
        }

        .container {
            background:#fff;
            padding:20px;
            border-radius:8px;
            max-width:600px;
            margin:30px auto;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }

        h2 { margin-top:0; }

        input, button {
            padding:8px;
            width:100%;
            margin-bottom:10px;
            border-radius: 4px;
            border: 1px solid #ccc;
        }

        button {
            background:red;
            color:white;
            border:none;
            cursor:pointer;
        }

        /*button:hover { background:#0056b3; }

        .msg { color:green; }
        .err { color:red; }
        */

        .top-bar {
            padding:5px 10px;
            display:flex;
            justify-content:space-between;
            align-items:center;
            margin-bottom:15px;
            background-color: lightblue;
        }

        .back-btn {
            background:#dc3545;
            padding:6px 12px;
            color:white;
            text-decoration:none;
            border-radius:4px;
            box-shadow: 0 5px 20px rgba(24, 4, 4, 0.15);
        }
        .btn :hover {
            background-color:blueviolet;
  box-shadow: 0 12px 16px 0 rgba(0,0,0,0.24),0 17px 50px 0 rgba(0,0,0,0.19);
}

        label {
            font-weight: bold;
        }
    </style>
</head>
<body>

<!-- Header Section -->
<div class="header">
    <img src="images/contin_kle.png" alt="KLE Logo">
    <div class="header-text">
        KLE Technological University, Dr. M S Sheshgiri Campus, Udyambag, Belagavi - 590 008
    </div>
</div>

<div class="container">
    <div class="top-bar">
        <h2>Edit Teacher</h2>
        <a class="back-btn" href="admin_dashboard.php?tab=teachers#teachers">← Back</a>
    </div>

    <?php if ($msg) echo "<p class='msg'>$msg</p>"; ?>
    <?php if ($err) echo "<p class='err'>$err</p>"; ?>

    <form method="POST">
        <label>Name:</label><br>
        <input name="name" value="<?= htmlspecialchars($teacher['name']) ?>" required><br>

        <label>Email:</label><br>
        <input name="email" type="email" pattern="[a-zA-Z0-9._%+-]+@gmail\.com" value="<?= htmlspecialchars($teacher['email']) ?>" required><br>

        <label>New Password (leave blank to keep same):</label><br>
        <input type="password" name="password"><br>

        <button type="submit">Save Changes</button>
    </form>
</div>

</body>
</html>
