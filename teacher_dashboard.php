<?php
session_start();
include 'config.php';

if (!isset($_SESSION['teacher_id'])) {
    header("Location: index.php");
    exit();
}

$teacher_id = $_SESSION['teacher_id'];

// Fetch teacher info
$sql = "SELECT * FROM teachers WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$result = $stmt->get_result();
$teacher = $result->fetch_assoc();

// Handle group creation/update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_group'])) {
        $group_name = $_POST['group_name'];
        $insert = $conn->prepare("INSERT INTO groups (guide_id, group_name) VALUES (?, ?)");
        $insert->bind_param("is", $teacher_id, $group_name);
        $insert->execute();
        header("Location: teacher_dashboard.php");
        exit();
    }
    if (isset($_POST['update_group'])) {
        $edit_group_id = $_POST['edit_group_id'];
        $new_group_name = $_POST['new_group_name'];
        $update = $conn->prepare("UPDATE groups SET group_name = ? WHERE id = ? AND guide_id = ?");
        $update->bind_param("sii", $new_group_name, $edit_group_id, $teacher_id);
        $update->execute();
        header("Location: teacher_dashboard.php");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>Teacher Dashboard</title>
<style>
body {
  font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
  background-color: #e8f0f9;
  margin: 0;
  padding: 0;
  color: #2c3e50;
}

/* Header styling */
.header {
    width: 100%;
    height: 100%;
    background-color: #ffe8cc;
    display: flex;
    align-items: center;
    justify-content: start;
    padding: 15px 30px;
    box-shadow: 0 20px 50px rgba(230, 15, 201, 0.49);
}

.header img {
  height: 80px;
  width: 90px;
  margin-right: 25px;
  border: 3px solid black;
  box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
}

.header-text {
  flex-grow: 1;
  font-size: 28px;
  font-weight: bold;
  color: #291911;
  max-width: 1000px;
  line-height: 1.3;
 
}

/* Announcement styling */
.announcement {
    width: 100%;
    text-align: center;
    background: linear-gradient(to right, #f1b85b, #e4b9aa);
    color: #000;
    font-size: 20px;
    font-weight: bold;
    line-height: 1.6;
    margin-top: 35px;
    padding: 10px 0;
    box-shadow: 0 5px 50px rgba(161, 13, 247, 0.911);
}
.announcement h1 {
  margin: 0;
  font-size: 28px;
}

h1 {
  text-align: center;
  margin-bottom: 30px;
  color: #2c3e50;
}

.tabs {
  display: flex;
  justify-content: center;
  gap: 15px;
  flex-wrap: wrap;
  margin: 20px auto;
  max-width: 1000px;
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
  background: #2f68b2;
}

.tab.active {
  background: #1e3a8a;
  box-shadow: 0 2px 5px #28a745;
}

.tab-content {
  display: none;
  background: linear-gradient(to bottom right, #f4fc85ff, #fce4ec);
  padding: 55px;
  border-radius: 12px;
  box-shadow: 0 4px 12px rgba(112, 23, 23, 0.97);
  max-width: 1000px;
  margin: auto;
}

.tab-content.active {
  display: block;
}

form {
  margin-top: 20px;
}

input[type="text"],
input[type="password"],
textarea {
  width: 98%;
  padding: 10px 12px;
  margin: 10px 0;
  border: 1px solid #ccc;
  border-radius: 8px;
  font-size: 14px;
  background-color: #fff;
}

button {
  background-color: #28a745;
  color: white;
  border: none;
  padding: 10px 18px;
  font-size: 14px;
  border-radius: 8px;
  cursor: pointer;
  box-shadow: 0 5px 20px rgba(112, 2, 5, 0.8);
  transition: background 0.3s ease;
}

button:hover {
  background-color: #089b28ff;
  box-shadow: 0 0 5px #e8f0f9;
}

.group-list {
  margin-top: 20px;
}

.group-item {
  background-color: #86f3f3ff;
  padding: 15px;
  border-radius: 10px;
  margin-bottom: 12px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  box-shadow: 0 2px 5px rgba(0,0,0,0.05);
}

.group-item span:first-child {
  font-weight: bold;
  color: #3a7bd5;
  cursor: pointer;
}

.group-item input[type="text"] {
  width: 160px;
  display: inline-block;
  margin-right: 8px;
}

.group-item button {
  background-color: #e74c3c;
}

.group-item button:hover {
  background-color: #c0392b;
}

.message-box {
  height: 220px;
  overflow-y: auto;
  border: 1px solid #ccc;
  background: #d1faf4ff;
  padding: 15px;
  border-radius: 8px;
  margin-bottom: 20px;
}
</style>
</head>
<body>
  <!-- Header -->
  <div class="header">
    <img src="images/contin_kle.png" alt="KLE Logo">
    <div class="header-text">
      KLE Technological University, Dr. M S Sheshgiri Campus, Udyambag, Belagavi - 590 008
    </div>
  </div>

  <!-- Announcement -->
  <div class="announcement">
    <h1><marquee>Welcome, <?php echo htmlspecialchars($teacher['name']); ?></marquee></h1>
  </div>

  <div style="max-width: 1000px; margin: auto;">
    <div class="tabs">
      <div class="tab active" data-tab="tab-groups" onclick="showTab('tab-groups')">Groups</div>
      <div class="tab" data-tab="tab-announcement" onclick="showTab('tab-announcement')">Announcement</div>
      <div class="tab" data-tab="tab-profile" onclick="showTab('tab-profile')">Profile</div>
      <div class="tab" onclick="window.location.href='logout_teacher.php'">Logout</div>
    </div>

    <!-- GROUP TAB -->
    <div id="tab-groups" class="tab-content active">
      <h2><center>Your Groups</center></h2>
      <form method="post" action="">
        <input type="text" name="group_name" placeholder="Group name" required />
        <button type="submit" name="add_group">Add Group</button>
      </form>

      <div class="group-list">
        <?php
        $groups_sql = "
        SELECT g.*, 
          (
            SELECT COUNT(*) 
            FROM group_chats gc
            JOIN chat_reads cr 
              ON cr.chat_id = gc.id AND cr.user_id = ? AND cr.role = 'teacher'
            WHERE gc.group_id = g.id 
              AND gc.sender_role = 'student'
              AND cr.is_read = 0
          ) AS unread_count
        FROM groups g 
        WHERE guide_id = ?";

        $groups_stmt = $conn->prepare($groups_sql);
        $groups_stmt->bind_param("ii", $teacher_id, $teacher_id);
        $groups_stmt->execute();
        $groups_result = $groups_stmt->get_result();

        while ($group = $groups_result->fetch_assoc()) {
            echo '<div class="group-item">';
            echo '<span onclick="openGroup(' . $group['id'] . ')">' . htmlspecialchars($group['group_name']);
            if (!empty($group['unread_count'])) {
                echo ' <span style="
                    display: inline-block;
                    background-color: red;
                    color: white;
                    font-weight: bold;
                    border-radius: 50%;
                    padding: 2px 7px;
                    font-size: 12px;
                    margin-left: 5px;
                    line-height: 1;
                " title="Unread messages">' . $group['unread_count'] . '</span>';
            }
            echo '</span>';

            echo '<span>';
            echo '<form method="post" style="display:inline;">
            <input type="hidden" name="edit_group_id" value="' . $group['id'] . '">
            <input type="text" name="new_group_name" value="' . htmlspecialchars($group['group_name']) . '" required>
            <button type="submit" name="update_group">Save</button>
            </form>';
            echo '<button onclick="if(confirm(\'Delete this group?\')){window.location=\'delete_group.php?group_id=' . $group['id'] . '\'}">Delete</button>';
            echo '</span>';
            echo '</div>';
        }
        ?>
      </div>
    </div>

    <!-- ANNOUNCEMENT TAB -->
    <div id="tab-announcement" class="tab-content">
      <h2><center>Send Announcement</center></h2>
      <div class="message-box" id="announcementBox"></div>
      <form id="announcementForm" enctype="multipart/form-data">
        <textarea id="announcementText" name="announcement" rows="3" placeholder="Type announcement here..."></textarea>
        <input type="file" name="attachment" id="announcementFile" accept=".pdf,.doc,.docx,.txt,.ppt,.pptx,.xls,.xlsx,.zip,.rar,.jpg,.jpeg,.png">
        <button type="submit">Send</button>
      </form>
    </div>

    <!-- PROFILE TAB -->
    <div id="tab-profile" class="tab-content">
      <h2><center>👨‍🏫 Your Profile</center></h2>
      <p><strong>Name:</strong> <?php echo htmlspecialchars($teacher['name']); ?></p>
      <p><strong>Email:</strong> <?php echo htmlspecialchars($teacher['email']); ?></p>

      <h3>Change Password</h3>
      <form id="changePasswordForm">
        <input type="password" name="new_password" placeholder="New Password" required />
        <input type="password" name="confirm_password" placeholder="Confirm New Password" required />
        <button type="submit">Change Password</button>
      </form>
      <div id="pwMsg"></div>
    </div>
  </div>

<script>
function showTab(tabId) {
  document.querySelectorAll('.tab-content').forEach(tc => tc.classList.remove('active'));
  document.getElementById(tabId).classList.add('active');
  document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
  document.querySelector(`[data-tab="${tabId}"]`).classList.add('active');
}

function openGroup(groupId) {
  window.location.href = 'group_details.php?group_id=' + groupId;
}

function loadAnnouncements() {
  fetch('fetch_announcements.php')
    .then(res => res.text())
    .then(html => {
      document.getElementById('announcementBox').innerHTML = html;
    });
}

document.getElementById('announcementForm').onsubmit = function(e) {
  e.preventDefault();
  const form = e.target;
  const formData = new FormData(form);

  fetch('send_announcement.php', {
    method: 'POST',
    body: formData
  }).then(() => {
    form.reset();
    loadAnnouncements();
  });
};

document.getElementById('changePasswordForm').onsubmit = function(e) {
  e.preventDefault();
  const fd = new FormData(this);

  fetch('change_password.php', {
    method: 'POST',
    body: fd
  }).then(r => r.json())
    .then(j => {
      const msg = document.getElementById('pwMsg');
      if (j.success) {
        msg.innerHTML = '<span style="color:green;">Password changed successfully.</span>';
        e.target.reset();
      } else {
        msg.innerHTML = '<span style="color:red;">' + j.error + '</span>';
      }
    });
};

loadAnnouncements();
</script>
</body>
</html>
