<?php 
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit;
}
require 'config.php';

$teachers = $conn->query("SELECT id, name, email FROM teachers")->fetch_all(MYSQLI_ASSOC);
$students = $conn->query("SELECT id, name, email FROM users")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Admin Dashboard</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <style>
    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }

    body {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      background: #eef1f4;
    }
    
    .header {
      width: 100%;
      background-color: #ffe8cc;
      display: flex;
      justify-content: flex-start;
      align-items: center;
      padding: 15px 30px;
      position: sticky;
      top: 0;
      left: 0;
      z-index: 1000;
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
    }

    .header img {
      height: 80px;
      width: 90px;
      margin-right: 40px;
      border: 3px solid black;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
    }

   .header-text {
  flex-grow: 1;
  font-size: 28px;
  font-weight: bold;
  color: #291911;
  max-width: 1000px;
  line-height: 1.3;
  
}
    .announcement {
      width: 100%;
      text-align: center;
      background: linear-gradient(to right, #f1b85b, #e4b9aa);
      color: black;
      font-size: 20px;
      font-weight: bold;
      padding: 12px 0;
      margin-bottom: 10px;
      box-shadow: 0 3px 2px rgba(116, 10, 10, 0.3);
    }

    .nav {
      background: #310d53ff;
      color: #fff;
      padding: 1rem;
      display: flex;
      justify-content: space-between;
      box-shadow: 0 5px 2px rgba(20, 10, 20, 0.3);
    }

    .tabs {
      display: flex;
      gap: 10px;
      margin: 20px;
    }

    .tab {
      padding: 10px 20px;
      background: linear-gradient(135deg, #89f7fe 0%, #66a6ff 100%);
      color: #fff;
      border-radius: 8px;
      font-weight: 600;
      cursor: pointer;
      transition: 0.3s ease;
      box-shadow: 0 3px 6px rgba(0, 0, 0, 0.15);
    }

    .tab:hover {
      transform: translateY(-2px);
      background: linear-gradient(135deg, #66a6ff 0%, #89f7fe 100%); 
    }
    .tab.active {
      background: linear-gradient(135deg, #3f51b5, #5a55ae);
      font-weight: 700;
    }

    .box {
      display: none;
      margin: 0 20px 20px;
      padding: 20px;
      border-radius: 10px;
      background: linear-gradient(to bottom right, #a0ebe8, #f2f2f2);
      box-shadow: 0 6px 12px rgba(0, 0, 0, 0.1);
    }

    .box.active {
      display: block;
    }

    /* TEACHERS SECTION STYLING */
    #box-teachers {
      background: linear-gradient(to bottom right, #bbeff5, #fce4ec);
    }

    h2, h3 {
      color: #333;
      margin-bottom: 10px;
    }

    input {
      padding: 8px;
      margin: 6px;
      border: 1px solid #ccc;
      border-radius: 6px;
      width: 200px;
    }

    button {
      padding: 8px 16px;
      background-color: #da3923ff;
      color: black;
      font-style:bold;
      font-size:15px;
      border: none;
      border-radius: 6px;
      cursor: pointer;
      margin-left: 6px;
      padding: 10px 18px;
      text-align: center;
      text-decoration: none;
      display: inline-block;
      -webkit-transition-duration: 0.4s; /* Safari */
      transition-duration: 0.4s;
    
    }

    button:hover {
      background-color: #21e928ff;
      box-shadow: 0 12px 16px 0 rgba(38, 88, 226, 0.24),0 5px 20px 0 rgba(146, 4, 4, 0.82);
      
    }

    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 16px;
      background: #fff;
      box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
    }

    th {
      background-color: #b2ebf2;
      color: #333;
    }

    th, td {
      border: 1px solid #ddd;
      padding: 10px;
      text-align: left;
    }

    .btn-edit {
      background: #28a745;
      color: #fff;
      font-family:Georgia, 'Times New Roman', Times, serif;
      /*box-shadow: 0 12px 16px 0 rgba(226, 9, 9, 0.24),0 5px 20px 0 rgba(146, 4, 4, 0.82);*/

    }

    .btn-del {
      background: #f72f2fff;
      color: black;
      padding:2px;
      font-family:Georgia, 'Times New Roman', Times, serif;
      /* box-shadow: 0 12px 16px 0 rgba(253, 5, 5, 0.99),0 5px 5px 0 rgba(223, 37, 37, 0.82);*/

    }

    .btn-edit:hover {
      background: #f11181ff;
    }

    .btn-del:hover {
      background: #d60909;
    }
  </style>
  <script>
    function show(id) {
      document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
      document.querySelectorAll('.box').forEach(b => b.classList.remove('active'));
      document.getElementById('tab-' + id).classList.add('active');
      document.getElementById('box-' + id).classList.add('active');
    }

    window.onload = function () {
      const params = new URLSearchParams(window.location.search);
      const tab = params.get('tab');
      if (tab === 'students') show('students');
      else if (tab === 'profile') show('profile');
      else show('teachers'); // default
    };
  </script>
</head>
<body>

  <!-- Header with Logo -->
  <div class="header">
    <img src="images/contin_kle.png" alt="KLE Logo">
    <div class="header-text">
      <span>KLE Technological University, Dr. M S Sheshgiri Campus, Udyambag, Belagavi - 590 008</span>
    </div>
  </div>

  <!-- Announcement Bar -->
  <div class="announcement">
    <marquee> Manage by the admin to add the guide as well as Students</marquee>
  </div>

  <!-- Top Navbar -->
  <div class="nav">
    <div>Logged in as <strong><?= htmlspecialchars($_SESSION['admin_user']) ?></strong></div>
    <a href="admin_logout.php" style="color:#fff;text-decoration:none;">Logout</a>
  </div>

  <!-- Tab Menu -->
  <div class="tabs">
    <div id="tab-teachers" class="tab" onclick="show('teachers')">Teachers</div>
    <div id="tab-students" class="tab" onclick="show('students')">Students</div>
    <div id="tab-profile" class="tab" onclick="show('profile')">Profile</div>
  </div>

  <!-- Teachers Tab -->
  <div id="box-teachers" class="box">
    <h2>Teachers</h2>
    <?php
      if (isset($_GET['msg']) && $_GET['tab'] === 'teachers')
        echo "<p style='color:green'>" . htmlspecialchars($_GET['msg']) . "</p>";
      if (isset($_GET['err']) && $_GET['tab'] === 'teachers')
        echo "<p style='color:red'>" . htmlspecialchars($_GET['err']) . "</p>";
    ?>
    <h3>Add Teacher</h3>
    <form method="POST" action="teacher_add.php">
      <input type="text" name="name" placeholder="Name" required>
      <input type="email" name="email" pattern="[a-zA-Z0-9._%+-]+@gmail\.com" placeholder="Email" required>
      <input type="password" name="password" placeholder="Password" required>
      <button>Add</button>
    </form>

    <table>
      <tr><th>ID</th><th>Name</th><th>Email</th><th>Action</th></tr>
      <?php foreach ($teachers as $t): ?>
        <tr>
          <td><?= $t['id'] ?></td>
          <td><?= htmlspecialchars($t['name']) ?></td>
          <td><?= htmlspecialchars($t['email']) ?></td>
          <td>
            <a href="teacher_edit.php?id=<?= $t['id'] ?>" class="btn-edit">Edit</a>
            <a href="teacher_delete.php?id=<?= $t['id'] ?>" class="btn-del" onclick="return confirm('Delete this teacher?')">Delete</a>
          </td>
        </tr>
      <?php endforeach; ?>
    </table>
  </div>

  <!-- Students Tab -->
  <div id="box-students" class="box">
    <h2>Students</h2>
    <?php
      if (isset($_GET['msg']) && $_GET['tab'] === 'students')
        echo "<p style='color:green'>" . htmlspecialchars($_GET['msg']) . "</p>";
      if (isset($_GET['err']) && $_GET['tab'] === 'students')
        echo "<p style='color:red'>" . htmlspecialchars($_GET['err']) . "</p>";
    ?>
    <h3>Add Student</h3>
    <form method="POST" action="student_add.php">
      <input type="text" name="name" placeholder="Name" required>
      <input type="email" name="email" pattern="[a-zA-Z0-9._%+-]+@gmail\.com" placeholder="Email" required>
      <input type="password" name="password" placeholder="Password" required>
      <button>Add</button>
    </form>

    <table>
      <tr><th>ID</th><th>Name</th><th>Email</th><th>Action</th></tr>
      <?php foreach ($students as $s): ?>
        <tr>
          <td><?= $s['id'] ?></td>
          <td><?= htmlspecialchars($s['name']) ?></td>
          <td><?= htmlspecialchars($s['email']) ?></td>
          <td>
            <a href="student_edit.php?id=<?= $s['id'] ?>" class="btn-edit">Edit</a>
            <a href="student_delete.php?id=<?= $s['id'] ?>" class="btn-del" onclick="return confirm('Delete this student?')">Delete</a>
          </td>
        </tr>
      <?php endforeach; ?>
    </table>
  </div>

  <!-- Profile Tab -->
  <div id="box-profile" class="box">
    <h2>Change Admin Password</h2>
    <form method="POST" action="admin_change_password.php">
      <input type="text" name="name" placeholder="New username" required><br><br>
      <input type="password" name="new" placeholder="New password" required><br><br>
      <input type="password" name="confirm" placeholder="Confirm new password" required><br><br>
      <button>Update Details</button>
    </form>
    <?php
      if (isset($_GET['msg']) && $_GET['tab'] === 'profile')
        echo "<p style='color:green'>" . htmlspecialchars($_GET['msg']) . "</p>";
      if (isset($_GET['err']) && $_GET['tab'] === 'profile')
        echo "<p style='color:red'>" . htmlspecialchars($_GET['err']) . "</p>";
    ?>
  </div>

</body>
</html>
