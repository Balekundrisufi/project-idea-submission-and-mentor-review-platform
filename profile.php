<?php
session_start();
include 'config.php';

$user_id = $_SESSION['id'];
$user_role = $_SESSION['role']; // 'student' or 'teacher'

// Get user info based on role
if ($user_role === 'student') {
    $table = 'users';
} else {
    $table = 'teachers';
}

$msg = '';
$error = '';

// Handle password update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old_password = $_POST['old_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($old_password) || empty($new_password) || empty($confirm_password)) {
        $error = "Please fill all fields.";
    } elseif ($new_password !== $confirm_password) {
        $error = "New password and confirmation do not match.";
    } else {
        // Fetch current password hash
        $stmt = $conn->prepare("SELECT password FROM $table WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->bind_result($hashed_password);
        $stmt->fetch();
        $stmt->close();

        if (!password_verify($old_password, $hashed_password)) {
            $error = "Old password is incorrect.";
        } else {
            // Update password
            $new_hashed = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE $table SET password = ? WHERE id = ?");
            $stmt->bind_param("si", $new_hashed, $user_id);
            if ($stmt->execute()) {
                $msg = "Password updated successfully.";
            } else {
                $error = "Failed to update password.";
            }
            $stmt->close();
        }
    }
}

// Fetch user info
$stmt = $conn->prepare("SELECT name, email FROM $table WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($name, $email);
$stmt->fetch();
$stmt->close();

?>

<?php include 'header.php'; ?>

<h2>Profile</h2>

<p><strong>Name:</strong> <?= htmlspecialchars($name) ?></p>
<p><strong>Email:</strong> <?= htmlspecialchars($email) ?></p>
<p><strong>Role:</strong> <?= htmlspecialchars(ucfirst($user_role)) ?></p>

<h3>Change Password</h3>

<?php if ($msg): ?>
    <p style="color:green;"><?= htmlspecialchars($msg) ?></p>
<?php endif; ?>

<?php if ($error): ?>
    <p style="color:red;"><?= htmlspecialchars($error) ?></p>
<?php endif; ?>

<form method="POST">
    <label>Old Password:</label><br>
    <input type="password" name="old_password" required><br><br>

    <label>New Password:</label><br>
    <input type="password" name="new_password" required><br><br>

    <label>Confirm New Password:</label><br>
    <input type="password" name="confirm_password" required><br><br>

    <button type="submit">Update Password</button>
</form>

<?php include 'footer.php'; ?>
