<?php
session_start();
include 'config.php'; // Your DB connection file

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $studentId = $_POST['student_id'];
    $password = $_POST['password'];

    $sql = "SELECT * FROM users WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $studentId);
    $stmt->execute();
    $result = $stmt->get_result();
    $student = $result->fetch_assoc();

    if ($student && password_verify($password, $student['password'])) {
        $_SESSION['student_id'] = $student['id'];
        header("Location: student_dashboard.php");
        exit();
    } else {
        echo "<script>alert('Invalid Student ID or Password');window.location.href='login_in.html';</script>";
    }
}
?>
