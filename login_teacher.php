<?php
session_start();
include 'config.php'; // Your DB connection file

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $teacherIdentifier = $_POST['teacher_id'];  // can be email or id
    $password = $_POST['password'];

    // Since your form allows ID or Email, let's check email first (assuming you want email login)
    $sql = "SELECT * FROM teachers WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $teacherIdentifier);
    $stmt->execute();
    $result = $stmt->get_result();
    $teacher = $result->fetch_assoc();

    if ($teacher && password_verify($password, $teacher['password'])) {
        $_SESSION['teacher_id'] = $teacher['id']; // Store the unique teacher ID
        $_SESSION['teacher_name'] = $teacher['name']; // Optional: store name for greetings
        header("Location: teacher_dashboard.php");
        exit();
    } else {
        echo "<script>alert('Invalid Teacher Email or Password');window.location.href='login_in.html';</script>";
    }
}
?>
