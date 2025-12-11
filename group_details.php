<?php
/*****************************************************************
 *  group_details.php – teacher view of a single group
 *****************************************************************/
session_start();
require 'config.php';
if(!isset($_SESSION['teacher_id'])){ header("Location: index.php"); exit; }

$teacher_id = $_SESSION['teacher_id'];
$group_id   = intval($_GET['group_id'] ?? 0);
$active_tab = $_GET['tab'] ?? 'members';
$is_chat_tab = $active_tab === 'chat';

// Count unread group chats
$unread_count = 0;
$role = 'teacher';
$unreadStmt = $conn->prepare("
  SELECT COUNT(*) AS unread_count
  FROM group_chats gc
  JOIN chat_reads cr 
    ON gc.id = cr.chat_id 
    AND cr.user_id = ? AND cr.role = ?
  WHERE gc.group_id = ? AND cr.is_read = 0
");

$unreadStmt->bind_param("isi", $teacher_id, $role, $group_id);
$unreadStmt->execute();
$unreadResult = $unreadStmt->get_result()->fetch_assoc();
$unread_count = $unreadResult['unread_count'] ?? 0;

/* verify ownership of the group */
$stmt = $conn->prepare("SELECT group_name FROM groups WHERE id=? AND guide_id=?");
$stmt->bind_param("ii",$group_id,$teacher_id);
$stmt->execute();
$group = $stmt->get_result()->fetch_assoc();
if(!$group){ die("Invalid group."); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Group Details</title>
<style>
/*.tabs{display:flex;gap:10px;margin-bottom:20px}*/
/*.tab{padding:10px 20px;background:#333;color:#fff;border-radius:5px;cursor:pointer}*/
/*.tab.active{background:#de3939}*/
:root {
  --shadow: 0 8px 18px rgba(0, 0, 0, 0.1);
}
body {
  font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
  margin: 0;
  background: linear-gradient(to right, rgba(245, 245, 240, 1), #3ccbe4ff);
}
.tab-content{display:none}
.tab-content.active{display:block}

 .header {
  width: 100%;
  background-color: #f51892e5;
  display: flex;
  justify-content: flex-start;
  align-items: center;
  padding: 15px 30px;
  position: absolute;
  top: 0;
  left: 0;
  box-shadow: 0 40px 60px rgba(230, 15, 201, 0.49); /* updated shadow */
}
.header {
      width: 100%;
      background-color: #ffe8cc;
      display: flex;
      align-items: center;
      padding: 15px 30px;
      box-shadow: 0 30px 60px rgba(230, 15, 201, 0.49);
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

.group-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
  font-size: 20px;
  font-weight: bold;
  background-color:yellow;
  line-height: 1.4;
  box-shadow: 0 20px 35px rgba(236, 14, 14, 1);
}

.tabs {
  display: flex;
  border-bottom: 3px solid #cc7c7cff;
  margin-bottom: 2rem;
  margin-top: 1rem;
  flex-wrap: wrap;
  gap: 1rem;
  padding-bottom: 0.9rem;
}
.tab {
  padding: 10px 20px;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
  transition: 0.3s ease;
}
.group-header h1 {
  margin: 0;
  font-size: 24px;
}

.back-button {
  padding: 8px 16px;
  background-color: #f72f9dff;
  color: white;
  text-decoration: none;
  border-radius: 6px;
  font-weight: bold;
  transition: background-color 0.3s ease;
  box-shadow: 0 10px 35px rgba(0, 0, 0, 0.3);
}

.back-button:hover {
  background-color: #38f3f3ff;
}
.tab:hover{
  background-color: rgba(36, 231, 18, 0.91);
  transition: background-color 1.3s ease;
  box-shadow: 0 5px 10px rgba(231, 12, 12, 0.16);
}

</style>
<script>
function showTab(id) {
  document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));
  document.getElementById(id).classList.add('active');
  document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
  document.querySelector('[data-tab="' + id + '"]').classList.add('active');

  if (id === 'chat') {
    markMessagesAsRead(); // 👈 NEW function to mark as read on switch
  }
}
function markMessagesAsRead() {
  fetch('mark_read.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: 'group_id=<?= $group_id ?>'
  })
  .then(res => res.text())
  .then(() => {
    // ✅ Update frontend: Remove unread count and bold styles
    document.querySelector('[data-tab="chat"]').innerHTML = "Group Chat";

    // Remove bold styling from chat messages
    document.querySelectorAll('#chat-feed > div').forEach(msg => {
      msg.style.fontWeight = 'normal';
    });
  });
}



window.onload = () => {
  showTab("<?= $active_tab ?>");
  initAddMember();
  initMarksForm();
};

function initAddMember() {
  const f = document.getElementById('addMemberForm');
  if (!f) return;
  f.onsubmit = e => {
    e.preventDefault();
    fetch('add_member.php', {
      method: 'POST',
      body: new FormData(f)
    })
    .then(r => r.json())
    .then(j => {
      alert(j.message);
      if (j.status === 'success') {
        location.href = 'group_details.php?group_id=<?= $group_id ?>&tab=members';
      }
    })
    .catch(err => alert('Failed to add member: ' + err));
  };
}

function initMarksForm() {
  const form = document.getElementById("marksForm");
  if (!form) return;
  form.addEventListener("submit", function(e) {
    e.preventDefault();
    const formData = new FormData(form);

    fetch("save_marks.php", {
      method: "POST",
      body: formData
    })
    .then(res => res.text())
    .then(response => {
      alert("Marks saved successfully!");
      location.href = 'group_details.php?group_id=<?= $group_id ?>&tab=marks';
    })
    .catch(err => {
      alert("Error saving marks. Please try again.");
    });
  });
}
</script>

</head><body>
   <div class="header">
    <img src="images/contin_kle.png" alt="KLE Logo">
    <div class="header-text">
      <span>KLE Technological University, Dr. M S Sheshgiri Campus, Udyambag, Belagavi - 590 008</span>
    </div>
  </div><br><br><br><br><br>

<div class="group-header">

  <h1>Group: <?= htmlspecialchars($group['group_name']) ?></h1><br><br><br>
  <a href="teacher_dashboard.php" class="back-button">← Go Back</a>
</div>


<div class="tabs">
  <div class="tab" data-tab="members" onclick="showTab('members')">Members</div>
  <div class="tab" data-tab="marks"   onclick="showTab('marks')">Marks</div>
  <div class="tab" data-tab="ideas"   onclick="showTab('ideas')">Project Ideas</div>
  <div class="tab" data-tab="chat" onclick="showTab('chat')">
  Group Chat<?= $unread_count > 0 ? " ({$unread_count})" : "" ?>
</div>

</div>

<!-- ▸ MEMBERS ------------------------------------------------------------ -->
<div id="members" class="tab-content">
  <h2>Group Members</h2>
  <form id="addMemberForm">
    <input type="hidden" name="group_id" value="<?= $group_id ?>">
    <input type="number" name="student_id" placeholder="Student ID" required>
    <button>Add Member</button>
  </form>
  <ul>
  <?php
    $m=$conn->prepare("SELECT u.id,u.name
                       FROM group_members gm JOIN users u ON u.id=gm.student_id
                       WHERE gm.group_id=?");
    $m->bind_param("i",$group_id); $m->execute(); $rs=$m->get_result();
    while($r=$rs->fetch_assoc()){
      echo "<li>".htmlspecialchars($r['name'])." (ID {$r['id']})
            <a href='remove_member.php?group_id=$group_id&student_id={$r['id']}'
               onclick=\"return confirm('Remove this student?')\">[Remove]</a></li>";
    }
  ?>
  </ul>
</div>

<!-- ▸ MARKS -------------------------------------------------------------- -->
<div id="marks" class="tab-content">
  <h2>Marks</h2>
  <form id="marksForm">
    <input type="hidden" name="group_id" value="<?= $group_id ?>">
    <table border="1" cellpadding="5">
      <tr><th>ID</th><th>Name</th><th>P1</th><th>P2</th><th>P3</th><th>Total/50</th></tr>
<?php
$mk = $conn->prepare("SELECT u.id,u.name,IFNULL(u.phase1,0)p1,IFNULL(u.phase2,0)p2,IFNULL(u.phase3,0)p3
                      FROM group_members gm JOIN users u ON u.id=gm.student_id
                      WHERE gm.group_id=?");
$mk->bind_param("i", $group_id); $mk->execute(); $rs = $mk->get_result();
while($r = $rs->fetch_assoc()) {
  $tot = $r['p1'] + $r['p2'] + $r['p3'];
  echo "<tr>
        <td>{$r['id']}</td><td>".htmlspecialchars($r['name'])."</td>
        <td><input name='phase1[{$r['id']}]' value='{$r['p1']}' type='number' min='0' max='20'></td>
        <td><input name='phase2[{$r['id']}]' value='{$r['p2']}' type='number' min='0' max='15'></td>
        <td><input name='phase3[{$r['id']}]' value='{$r['p3']}' type='number' min='0' max='15'></td>
        <td>$tot</td></tr>";
}
?>
    </table>
    <button type="submit">Save Marks</button>
  </form>
</div>

<!-- ▸ PROJECT IDEAS ------------------------------------------------------ -->
<div id="ideas" class="tab-content">
<h2>Project Ideas</h2>
<?php
$ideasQ = $conn->prepare(
  "SELECT pi.*, u.name
   FROM project_ideas pi
   LEFT JOIN users u ON u.id = pi.student_id
   WHERE pi.group_id = ?");

$ideasQ->bind_param("i", $group_id);
$ideasQ->execute();
$ideas = $ideasQ->get_result()->fetch_all(MYSQLI_ASSOC);

$total       = count($ideas);
$rejected    = array_sum(array_map(fn($r) => $r['status'] == 'rejected', $ideas));
$finalChosen = array_sum(array_map(fn($r) => $r['final_chosen'] == 1, $ideas));

// Show reopen editing button if needed
if (($total > 0 && $rejected == $total) || $finalChosen == 1) {
  echo "<form method='post' action='make_editable.php?tab=ideas' style='margin-bottom:15px'>
          <input type='hidden' name='group_id' value='$group_id'>
          <button>Re-open Editing for Students</button>
        </form>";
}

if ($total === 0) {
  echo "<p style='color:#888;font-style:italic'>No ideas submitted yet.</p>";
} else {
  echo "<form method='post' action='update_idea_status.php?tab=ideas'>";

  foreach ($ideas as $index => $row) {
    $disabled = ($row['final_chosen'] == 1) ? 'disabled' : '';
    $flag = $row['final_chosen'] ? "<em style='color:green'>(final)</em>" :
            ($row['is_editable'] ? "<em style='color:green'>(editable)</em>" : "");
	$studentName = $row['name'] ? htmlspecialchars($row['name']) : "<span style='color:#888'>Former Student</span>";
    echo "<div style='border:1px solid #ccc;padding:10px;margin:10px 0'>
            <strong>" . htmlspecialchars($row['idea_title']) . "</strong> by "
            . $studentName . " $flag<br>
            <em>Current Status:</em> {$row['status']}<br>
            <p>" . nl2br(htmlspecialchars($row['description'])) . "</p>

            <input type='hidden' name='idea_id[]' value='{$row['id']}'>

            <label>
              <input type='radio' name='status[$index]' value='Approved' " . ($row['status'] == 'Approved' ? 'checked' : '') . " $disabled>
              Approved
            </label>
            <label>
              <input type='radio' name='status[$index]' value='Rejected' " . ($row['status'] == 'Rejected' ? 'checked' : '') . " $disabled>
              Rejected
            </label>
            <br>
            <input type='text' name='feedback[]' value='" . htmlspecialchars($row['feedback']) . "' placeholder='Feedback' $disabled>
          </div>";
  }

  echo "<input type='hidden' name='group_id' value='$group_id'>
        <button type='submit' " . ($finalChosen ? 'disabled' : '') . ">Update All</button>
        </form>";
}
?>
</div>



<!-- ▸ CHAT -------------------------------------------------------------- -->
<div id="chat" class="tab-content">
<h2>Group Chat</h2>
<div style="border:10px solid black;height:200px;overflow-y:scroll;padding:10px;background:#fdfdfd" id="chat-feed">
<?php
// ✅ Delete files from chats older than 90 days
$oldFiles = $conn->query("
    SELECT file_path FROM group_chats 
    WHERE sent_at < NOW() - INTERVAL 90 DAY AND file_path IS NOT NULL
");

while ($row = $oldFiles->fetch_assoc()) {
    $file = $row['file_path'];
    if ($file && file_exists($file)) {
        unlink($file);
    }
}

// ✅ Delete chat_reads linked to old chats
$conn->query("
    DELETE cr FROM chat_reads cr
    JOIN group_chats gc ON cr.chat_id = gc.id
    WHERE gc.sent_at < NOW() - INTERVAL 90 DAY
");

// ✅ Delete old chats themselves
$conn->query("
    DELETE FROM group_chats 
    WHERE sent_at < NOW() - INTERVAL 90 DAY
");

$cf=$conn->prepare("SELECT * FROM group_chats WHERE group_id=? ORDER BY sent_at DESC");
$cf->bind_param("i",$group_id); $cf->execute(); $msg=$cf->get_result();
// 🔍 Get list of chat_ids that are unread by the teacher
$unread = [];
if ($is_chat_tab) {
  $checkUnread = $conn->prepare("SELECT chat_id FROM group_chats gc
    LEFT JOIN chat_reads cr ON gc.id = cr.chat_id AND cr.user_id = ? AND cr.role = ?
    WHERE gc.group_id = ? AND (cr.is_read IS NULL OR cr.is_read = 0)");
  $checkUnread->bind_param("isi", $teacher_id, $role, $group_id);
  $checkUnread->execute();
  $res = $checkUnread->get_result();
  while ($r = $res->fetch_assoc()) $unread[] = $r['chat_id'];
}

while($m = $msg->fetch_assoc()) {
  $isUnread = in_array($m['id'], $unread);
  $style = $isUnread ? 'font-weight:bold' : '';
  $who = $m['sender_role'] === 'teacher' ? 'Guide' : 'Student';

  echo "<div style='margin-bottom:12px; $style'>";
  echo "<strong>$who #{$m['sender_id']}</strong>  ";
  echo "<small style='color:gray'>".date('d M Y H:i', strtotime($m['sent_at']))."</small><br>";
  echo nl2br(htmlspecialchars($m['message']));

  if (!empty($m['file_path'])) {
    echo '<a href="' . htmlspecialchars($m['file_path']) . '" target="_blank">📎 '
       . htmlspecialchars($m['file_name']) . '</a>';
  }
  echo "<br><br>";
  echo "<hr></div>";
}

  echo "</div>";

?>
 
  <form method="POST" action="send_group_message.php?tab=chat" enctype="multipart/form-data" style="margin-top:10px;color:red;box-shadow:black;">
    <input type="hidden" name="group_id" value="<?= $group_id ?>">
    <textarea name="message" rows="2" placeholder="Type message..." style="width:100%"></textarea><br>
    <input type="file" name="attach">
    <button>Send</button>
  </form>
</div> <!-- Ends #chat -->

<style>
  button{
     padding: 4px 10px;
  color: white;
  text-decoration: none;
  border-radius: 6px;
  font-weight: bold;
  background-color: rgba(240, 6, 6, 0.99);
  transition: background-color 1.3s ease;
  box-shadow: 0 10px 2px rgba(231, 120, 12, 0.16);
}

</style>

<script>initAddMember();</script>
</body></html>
