<?php
/*****************************************************************
 *  student_dashboard.php  –  complete portal for students 
 *****************************************************************/
session_start();
if (!isset($_SESSION['student_id'])) { header("Location: login.html"); exit; }
require 'config.php';

$student_id = $_SESSION['student_id'];
/* ► student & group */
$s = $conn->prepare(
    "SELECT name,email,group_id,
            IFNULL(phase1,0) p1, IFNULL(phase2,0) p2, IFNULL(phase3,0) p3
     FROM   users WHERE id=?");
$s->bind_param("i",$student_id); $s->execute();
$student      = $s->get_result()->fetch_assoc();
$group_id     = $student['group_id'];
$studentName  = $student['name'];
$inGroup      = !empty($group_id);

/* ► guide */
$guide_id = null;
if ($inGroup) {
    $g = $conn->prepare("SELECT guide_id FROM groups WHERE id=?");
    $g->bind_param("i",$group_id); $g->execute();
    $row      = $g->get_result()->fetch_assoc();
    $guide_id = $row ? $row['guide_id'] : null;
}
/* -------------------------------------------------
 *  unread counts for red‑dot badges
 * -------------------------------------------------*/
$unreadChat = 0;
$unreadAnn  = 0;

if ($inGroup) {
    // ✅ COUNT from chat_reads directly
    $sql = "
        SELECT COUNT(*)
        FROM chat_reads cr
        JOIN group_chats gc ON cr.chat_id = gc.id
        WHERE gc.group_id = ?
          AND cr.user_id = ?
          AND cr.role = 'student'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $group_id, $student_id);
    $stmt->execute();
    $stmt->bind_result($unreadChat);
    $stmt->fetch();
    $stmt->close();

    // ✅ COUNT from announcement_reads directly
    $sql = "
        SELECT COUNT(*)
        FROM announcement_reads
        WHERE student_id = ?
          AND is_read = 0";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $stmt->bind_result($unreadAnn);
    $stmt->fetch();
    $stmt->close();
}




/* ► ideas */
$ideas         = [];
$submitted     = false;
$editable      = true;
$finalSelected = false;
if ($inGroup) {
    $iq = $conn->prepare("SELECT * FROM project_ideas WHERE group_id=? ORDER BY id");
    $iq->bind_param("i",$group_id);
    $iq->execute();
    $ideas = $iq->get_result()->fetch_all(MYSQLI_ASSOC);

    /* One Approved idea left? → final chosen */
    if (count($ideas) === 1 && $ideas[0]['status'] === 'approved') {
        $finalSelected = true;
    }

    $submitted = count($ideas) == 5 && !$ideas[0]['is_editable'];
    /* EDITED: clearer precedence */
    $editable  = (!$submitted && !$finalSelected) || ($ideas && $ideas[0]['is_editable'] == 1);
}

/* ► announcements */
$ann = [];
if ($guide_id) {
    $a = $conn->prepare("SELECT message, created_at, file_path, file_name FROM announcements
                     WHERE teacher_id=? ORDER BY created_at DESC LIMIT 20");
    $a->bind_param("i",$guide_id); $a->execute();
    $ann = $a->get_result()->fetch_all(MYSQLI_ASSOC);
}

/* ► marks */
$marks = [];
if ($inGroup) {
    $mq = $conn->prepare(
        "SELECT u.id,u.name,
                IFNULL(u.phase1,0) p1, IFNULL(u.phase2,0) p2, IFNULL(u.phase3,0) p3,
                (IFNULL(u.phase1,0)+IFNULL(u.phase2,0)+IFNULL(u.phase3,0)) ie
         FROM   group_members gm
         JOIN   users u ON u.id = gm.student_id
         WHERE  gm.group_id=? ORDER BY u.id");
    $mq->bind_param("i",$group_id); $mq->execute();
    $marks = $mq->get_result()->fetch_all(MYSQLI_ASSOC);
}

/* ► helper */
$approved = array_filter($ideas, fn($r)=>$r['status']=='approved');
?>
<!DOCTYPE html>
<html lang="en"><head><meta charset="utf-8">
<title>Student Dashboard</title>
<style>
/* ——— styles unchanged ——— */
body{
  font-family:Arial;margin:0;background:#f4f7fa ;background: #d2f7f5}
    
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
      box-shadow: 0 40px 60px rgba(230, 15, 201, 0.49);
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
.container{max-width:960px;margin:0.1rem auto;background:#fff;padding:1.5rem 2rem;border-radius:8px;box-shadow:0 0 10px rgba(0,0,0,.1)}
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
      box-shadow: 0 8px 20px rgba(180, 18, 18, 0.93); 
    }
    .tab.active {
      background: linear-gradient(135deg, #3f51b5, #5a55ae);
      font-weight: 700;
    }

.tab-content {
  display: none;
  background: linear-gradient(to bottom right, #f4fc85ff, #fce4ec);
  
  padding: 55px;
  border-radius: 12px;
  box-shadow: 0 4px 12px rgba(112, 23, 23, 0.97);
  max-width: 1000px;
  margin-top:2px;
}

table{width:100%;border-collapse:collapse}th,td{border:1px solid #ddd;padding:6px}
.message-box{border:1px solid #ccc;height:100px;overflow-y:auto;background:#f6f8fa;margin-bottom:6px;padding:.5rem}
 button {
      padding: 8px 16px;
       background: linear-gradient(135deg, #2a5a5eff 0%, #f81111ff 100%);
       box-shadow: 0 8px 20px rgba(180, 18, 18, 0.93);
      color: white;
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
button[disabled]{opacity:.5;cursor:not-allowed}
button:hover:not([disabled]){background:#0056b3}
input,textarea{width:98%;padding:.5rem;margin-bottom:.8rem;border:1px solid #ccc;border-radius:4px}
.success{color:green}.error{color:red}
</style>
</head><body>
   <!-- Header with Logo -->
  <div class="header">
    <img src="images/contin_kle.png" alt="KLE Logo">
    <div class="header-text">
      <span>KLE Technological University, Dr. M S Sheshgiri Campus, Udyambag, Belagavi - 590 008</span>
    </div>
  </div>
<div class="container">
  <h2>Welcome, <?= htmlspecialchars($studentName) ?></h2>

  <!-- tabs -->
  <div class="tabs">
    <div class="tab active" data-tab="ideas">Project&nbsp;Ideas</div>
    <?php if($inGroup): ?>
      <div class="tab" data-tab="chat">
	  Group&nbsp;Message <?= $unreadChat ? "($unreadChat)" : '' ?>
	</div>
	<div class="tab" data-tab="ann">
	  Announcements <?= $unreadAnn ? "($unreadAnn)" : '' ?>
	</div>




      <div class="tab" data-tab="marks">Marks</div>
    <?php endif; ?>
    <div class="tab" data-tab="profile">Profile</div>
    <div class="tab" data-tab="logout">Logout</div>
  </div>

  <!-- ▸ IDEAS -->
<div id="ideas" class="tab-content" style="display:block">
<?php if(!$inGroup): ?>
  <p>You are not yet added by your teacher. Please contact your guide.</p>
<?php else: ?>
<h3 style="background:#007bff;color:#fff;padding:0.1rem;box-shadow:2px 2px 8px rgba(0,0,0,0.3);border-radius:6px;width:17%">Project Ideas 👇</h3>

  <h4>Enter Total 5 Project Idea's</h4>

  <?php if($submitted && !$editable): ?>
     <p><em>Ideas locked until teacher reopens.</em></p>
  <?php endif; ?>

  <?php if($finalSelected): ?>
     <p style="color:green;"><strong>Final project chosen.</strong> You can no longer add or edit ideas.</p>
  <?php endif; ?>

  <?php if($editable && !$submitted): ?>
    <!-- Input section -->
    <div id="idea-entry">
      <input id="idea-title" placeholder="Enter Title">
      <textarea id="idea-desc" placeholder="Enter Description"></textarea>
      <button id="add-idea-btn">Add Idea</button>
    </div>
	<br><br>
    <!-- Dynamic table + submission -->
    <form method="post" action="submit_ideas.php" id="idea-form">
      <table id="ideas-table">
        <tr>
          <th>S.No</th><th>Title</th><th>Description</th><th>Status</th><th>Action</th>
        </tr>
      </table>
      <div id="hidden-inputs"></div><br>
      <button type="submit" id="submit-btn" disabled>Submit Ideas</button>
    </form>

  <?php else: ?>
    <!-- Already submitted: server-rendered ideas -->
    <table>
      <tr>
        <th>S.No</th><th>Title</th><th>Description</th><th>Status</th><th>Feedback</th>
        <?php if(!$finalSelected && count($approved)>1): ?><th>Chosen</th><?php endif; ?>
      </tr>
      <?php for($i=1;$i<=5;$i++):
        $row = $ideas[$i-1]??null; ?>
        <tr>
          <td><?= $i ?></td>
          <?php if($row): ?>
            <td><?= htmlspecialchars($row['idea_title']) ?></td>
            <td><button><a style="color:white;text-decoration:none" href="idea_detail.php?id=<?= $row['id'] ?>&type=description">View</a></button></td>
            <td><?= $row['status'] ?? '--' ?></td>
            <td><button><a style="color:white;text-decoration:none" href="idea_detail.php?id=<?= $row['id'] ?>&type=feedback">View</a></button></td>
            <?php if(!$finalSelected && count($approved)>1): ?>
              <td>
                <?php if($row['status']=='approved'): ?>
                  <button onclick="chooseIdea(<?= $row['id'] ?>)">Choose</button>
                <?php else: ?>
                  <button disabled>Choose</button>
                <?php endif; ?>
              </td>
            <?php endif; ?>
          <?php else: ?>
            <td colspan="<?= (!$finalSelected && count($approved)>1)?5:4 ?>">-- not submitted --</td>
          <?php endif; ?>
        </tr>
      <?php endfor; ?>
    </table>
  <?php endif; ?>
<?php endif; ?>
</div><!-- ideas -->


<?php if($inGroup): ?>
  <!-- ▸ CHAT -->
  <div id="chat" class="tab-content">
    <h3>Group Chat</h3>
    <div class="message-box" id="chatBox"></div>
    <textarea id="chatInput" rows="2" placeholder="Type message"></textarea>
    <input type="file" id="chatFile">
    <button id="sendChatBtn">Send</button>
  </div>

  <!-- ▸ ANNOUNCEMENTS -->
  <div id="ann" class="tab-content">
    <h3>Announcements</h3>
    <?php if(!$ann): ?>
      <p>No announcements yet.</p>
    <?php else: foreach ($ann as $a): ?>
        <div class="announcement">
          <small><?= date("d M Y H:i",strtotime($a['created_at'])) ?></small>
          <p><?= nl2br(htmlspecialchars($a['message'])) ?></p>
		<?php if (!empty($a['file_path'])): ?>
		  <a href="<?= htmlspecialchars($a['file_path']) ?>" target="_blank">
			📎 <?= htmlspecialchars($a['file_name']) ?>
		  </a>
		<?php endif; ?>
		<hr>

        </div>
    <?php endforeach; endif; ?>
  </div>

  <!-- ▸ MARKS -->
  <div id="marks" class="tab-content">
    <h3>Group Marks</h3>
    <table>
      <tr><th>ID</th><th>Name</th><th>P1</th><th>P2</th><th>P3</th><th>IE/50</th></tr>
      <?php foreach($marks as $m): ?>
        <tr<?= $m['id']==$student_id?' style="background:#eaffea"':'' ?>>
          <td><?= $m['id'] ?></td>
          <td><?= htmlspecialchars($m['name']) ?></td>
          <td><?= $m['p1'] ?></td><td><?= $m['p2'] ?></td><td><?= $m['p3'] ?></td><td><?= $m['ie'] ?></td>
        </tr>
      <?php endforeach; ?>
    </table>
  </div>
<?php endif; ?>

  <!-- ▸ PROFILE -->
  <div id="profile" class="tab-content">
    <h3>Change Password</h3>
    <form id="pwForm">
      <label>New Password (min 6)</label>
      <input type="password" name="newpass" minlength="6" required>
      <button>Update</button>
    </form>
    <div id="pwMsg"></div>
  </div>
</div><!-- container -->

<script>
/* tab switch */
document.querySelectorAll('.tab').forEach(t => {
  t.onclick = () => {
    const tabName = t.dataset.tab;

    if (tabName === 'logout') {
      location = 'logout.php';
      return;
    }

    // 🟢 Mark announcements as read AND remove (x)
    if (tabName === 'ann') {
      fetch('mark_announcements_read.php').then(() => {
        t.innerHTML = 'Announcements';
      });
    }

    // 🔵 Mark chat messages as read AND remove (x)
    if (tabName === 'chat') {
      fetch('mark_chat_read.php').then(() => {
        t.innerHTML = 'Group Message';
      });
    }

    // Tab switch logic
    document.querySelectorAll('.tab').forEach(x => x.classList.remove('active'));
    t.classList.add('active');
    document.querySelectorAll('.tab-content').forEach(x => x.style.display = 'none');
    document.getElementById(tabName).style.display = 'block';
  };
});

/* choose via AJAX */
function chooseIdea(id){
  if(!confirm('Make this your final idea?')) return;
  fetch('choose_idea.php?id='+id).then(()=>location.reload());
}

/* chat polling + send */
<?php if($inGroup): ?>
const box = document.getElementById('chatBox'),
      inp = document.getElementById('chatInput'),
      file = document.getElementById('chatFile');

function fetchMsg(){
  fetch('fetch_messages.php?group_id=<?= $group_id ?>')
    .then(r => r.json())
    .then(arr => {
      box.innerHTML = '';
      arr.forEach(o => {
        const f = o.file ? `<br><a href="${o.file}" target="_blank">📎 ${o.fileName}</a>` : '';
        box.insertAdjacentHTML('beforeend', `<div><strong>${o.sender}:</strong> ${o.message}${f}<br><small style="color:gray;">${o.time}</small></div><hr>`);
      });
    });
}
fetchMsg();
setInterval(fetchMsg, 3000);

document.getElementById('sendChatBtn').onclick = () => {
  if (!inp.value.trim() && !file.files.length) return;
  const fd = new FormData();
  fd.append('group_id', <?= $group_id ?>);
  fd.append('sender_id', <?= $student_id ?>);
  fd.append('sender_role', 'student');
  fd.append('message', inp.value.trim());
  if (file.files.length) fd.append('attach', file.files[0]);
  fetch('send_group_message.php', { method: 'POST', body: fd })
    .then(() => {
      inp.value = '';
      file.value = '';
      fetchMsg();
    });
};
<?php endif; ?>

/* password AJAX */
document.getElementById('pwForm').onsubmit = e => {
  e.preventDefault();
  const fd = new FormData(e.target);
  fetch('update_password.php', { method: 'POST', body: fd })
    .then(r => r.json()).then(j => {
      document.getElementById('pwMsg').innerHTML = j.success
        ? '<span class="success">Password updated.</span>'
        : '<span class="error">' + j.error + '</span>';
      if (j.success) e.target.reset();
    });
};
</script>

<script>
let ideas = [];

// Load saved ideas from localStorage
window.addEventListener('DOMContentLoaded', () => {
  const saved = localStorage.getItem('saved_ideas');
  if (saved) {
    ideas = JSON.parse(saved);
    repaint();
  }
});

document.getElementById('add-idea-btn').onclick = () => {
  const title = document.getElementById('idea-title').value.trim();
  const desc = document.getElementById('idea-desc').value.trim();
  if (!title || !desc) return alert('Enter both Title and Description');
  if (ideas.length >= 5) return alert("Maximum 5 ideas allowed");
  ideas.push({ title, desc });
  repaint();
  saveToStorage();
  document.getElementById('idea-title').value = '';
  document.getElementById('idea-desc').value = '';
};

function repaint() {
  const table = document.getElementById('ideas-table');
  table.querySelectorAll('tr:not(:first-child)').forEach(r => r.remove());
  ideas.forEach((idea, i) => {
    const tr = document.createElement('tr');
    tr.innerHTML = `
      <td>${i + 1}</td>
      <td>${idea.title}</td>
      <td><a style=" color:red;" href="javascript:void(0)" onclick="viewDesc(${i})">View</a></td>
      <td>--</td>
      <td>
        <button type="button" onclick="editIdea(${i})">Edit</button>
        <button type="button" onclick="removeIdea(${i})">Delete</button>
      </td>
    `;
    table.appendChild(tr);
  });
  updateHidden();
  document.getElementById('submit-btn').disabled = ideas.length !== 5;
}

function updateHidden() {
  const div = document.getElementById('hidden-inputs');
  div.innerHTML = '';
  ideas.forEach((idea) => {
    const ti = document.createElement('input'); ti.type = 'hidden'; ti.name = 'title[]'; ti.value = idea.title;
    const di = document.createElement('input'); di.type = 'hidden'; di.name = 'desc[]'; di.value = idea.desc;
    div.append(ti, di);
  });
}

// Save to localStorage
function saveToStorage() {
  localStorage.setItem('saved_ideas', JSON.stringify(ideas));
}

// Clear localStorage after form submission
document.getElementById('idea-form').onsubmit = () => {
  localStorage.removeItem('saved_ideas');
};

// View full description
window.viewDesc = i => {
  document.getElementById('modalTitle').innerText = ideas[i].title;
  document.getElementById('modalDesc').innerText = ideas[i].desc;
  document.getElementById('ideaModal').style.display = 'flex';
};

function closeModal() {
  document.getElementById('ideaModal').style.display = 'none';
  document.getElementById('editModal').style.display = 'none';
  document.getElementById('overlay').style.display = 'none';
}



let editIndex = null;

window.editIdea = i => {
  editIndex = i;
  document.getElementById('editTitle').value = ideas[i].title;
  document.getElementById('editDesc').value = ideas[i].desc;
  document.getElementById('editModal').style.display = 'block';
  document.getElementById('overlay').style.display = 'block';
};

function saveEdit() {
  const t = document.getElementById('editTitle').value.trim();
  const d = document.getElementById('editDesc').value.trim();
  if (t && d) {
    ideas[editIndex] = { title: t, desc: d };
    repaint();
    saveToStorage();
    closeModal();
  } else {
    alert("Both Title and Description are required");
  }
}


// Remove idea
window.removeIdea = i => {
  if (confirm("Delete this idea?")) {
    ideas.splice(i, 1);
    repaint();
    saveToStorage();
  }
};
</script>

<!-- Modal to view idea description -->
<div id="ideaModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%;
     background:rgba(0,0,0,0.6); z-index:1000; align-items:center; justify-content:center;">
  <div style="background:white; padding:2rem; border-radius:8px; max-width:600px; width:90%; box-shadow:0 0 10px rgba(0,0,0,0.3); position:relative;">
    <h3 id="modalTitle"></h3>
    <p id="modalDesc" style="white-space:pre-wrap;"></p>
    <button onclick="closeModal()" style="margin-top:1rem; background:#007bff; color:white; padding:.5rem 1rem; border:none; border-radius:4px;">Close</button>
  </div>
</div>


<!-- ✨ Modal for editing ideas -->
<div id="editModal" style="display:none;position:fixed;top:10%;left:50%;
    transform:translateX(-50%);background:white;padding:2rem;border:1px solid #ccc;
    border-radius:8px;z-index:1000;max-width:600px;">
  <h3>Edit Idea</h3>
  <label>Title</label><br>
  <input id="editTitle" placeholder="Title" style="width:100%"><br><br>
  <label>Description</label><br>
  <textarea id="editDesc" rows="10" cols="100" placeholder="Description" style="width:100%"></textarea><br><br>
  <button onclick="saveEdit()">Save</button>
  <button onclick="closeModal()">Cancel</button>
</div>

<!-- dark background behind modal -->
<div id="overlay" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;
    background:rgba(0,0,0,0.4);z-index:999;" onclick="closeModal()"></div>

</body></html>
