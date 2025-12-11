<?php
require 'header.php';
require 'config.php';

// Ensure student is logged in
after_login(); // optional helper; otherwise use session check
if (!isset($_SESSION['group_id']) || !isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
$group_id = $_SESSION['group_id'];
$user_id   = $_SESSION['user_id'];

// Fetch all ideas for this group
$ideas_stmt = $conn->prepare("SELECT * FROM project_ideas WHERE group_id = ? ORDER BY id ASC");
$ideas_stmt->bind_param("i", $group_id);
$ideas_stmt->execute();
$ideas_res = $ideas_stmt->get_result();
$ideas     = $ideas_res->fetch_all(MYSQLI_ASSOC);
$idea_count = count($ideas);

// Count approvals
$approved_ids = array_filter($ideas, function($i){return $i['status']=='approved';});
$approved_count = count($approved_ids);
?>
<h2>Project Ideas</h2>
<?php if ($idea_count < 5): ?>
<form id="idea-form" method="post" action="submit_idea.php">
  <label>Idea Title</label>
  <input type="text" name="title" required>
  <label>Description</label>
  <textarea name="description" required></textarea>
  <button type="submit">Add Idea</button>
  <p><small>You have submitted <?php echo $idea_count; ?>/5 ideas.</small></p>
</form>
<?php else: ?>
<p><strong>You have submitted the maximum 5 ideas. Await feedback from your guide.</strong></p>
<?php endif; ?>

<table class="ideas-table">
 <thead>
   <tr>
     <th>S.No</th>
     <th>Title</th>
     <th>Description</th>
     <th>Status</th>
     <th>Feedback</th>
     <th>Edit</th>
     <?php if($approved_count>1): ?>
       <th>Select</th>
     <?php endif; ?>
   </tr>
 </thead>
 <tbody>
 <?php foreach ($ideas as $index=>$idea): ?>
   <tr>
     <td><?= $index+1 ?></td>
     <td><?= htmlspecialchars($idea['idea_title']) ?></td>
     <td><button class="small-btn view-desc" data-desc="<?= htmlspecialchars($idea['description']) ?>">View</button></td>
     <td><span class="badge <?= $idea['status'] ?>"><?= ucfirst($idea['status']) ?></span></td>
     <td>
       <button class="small-btn feedback-btn" data-feedback="<?= htmlspecialchars($idea['feedback']?:'No feedback yet') ?>" <?= $idea['feedback']? '' : 'disabled' ?>>Feedback</button>
     </td>
     <td>
       <?php if($idea['status']=='pending' && $idea['is_editable']): ?>
         <a href="edit_idea.php?id=<?= $idea['id'] ?>" class="small-btn">Edit</a>
       <?php else: ?>—<?php endif; ?>
     </td>
     <?php if($approved_count>1): ?>
       <td style="text-align:center;">
         <?php if($idea['status']=='approved'): ?>
           <input type="radio" name="keep_idea" value="<?= $idea['id'] ?>">
         <?php endif; ?>
       </td>
     <?php endif; ?>
   </tr>
 <?php endforeach; ?>
 </tbody>
</table>

<?php if($approved_count>1): ?>
  <form id="choose-form" method="post" action="select_approved.php" style="margin-top:16px;">
     <button type="submit" class="small-btn">Confirm Selected Idea</button>
  </form>
<?php endif; ?>

<!-- Modal -->
<div class="modal" id="modal">
  <div class="modal-content" id="modalContent">
    <span class="modal-close" id="modalClose">&times;</span>
    <h3 id="modalTitle"></h3>
    <div id="modalBody"></div>
  </div>
</div>

<script>
// modal helpers
const modal = document.getElementById('modal');
const modalBody = document.getElementById('modalBody');
const modalTitle = document.getElementById('modalTitle');
const closeBtn = document.getElementById('modalClose');
closeBtn.onclick = ()=> modal.style.display='none';
window.onclick = e=>{ if(e.target==modal) modal.style.display='none'; };

document.querySelectorAll('.view-desc').forEach(btn=>{
  btn.addEventListener('click',()=>{
     modalTitle.textContent='Idea Description';
     modalBody.textContent=btn.dataset.desc;
     modal.style.display='flex';
  });
});

document.querySelectorAll('.feedback-btn').forEach(btn=>{
  btn.addEventListener('click',()=>{
     modalTitle.textContent='Guide Feedback';
     modalBody.textContent=btn.dataset.feedback;
     modal.style.display='flex';
  });
});

// choose approved idea
const chooseForm = document.getElementById('choose-form');
if(chooseForm){
 chooseForm.addEventListener('submit',function(e){
   const chosen = document.querySelector('input[name="keep_idea"]:checked');
   if(!chosen){ alert('Please select one approved idea to keep.'); e.preventDefault(); return; }
   const hidden = document.createElement('input');
   hidden.type='hidden';
   hidden.name='keep_id';
   hidden.value=chosen.value;
   this.appendChild(hidden);
 });
}
</script>
