<?php
session_start();
require 'config.php';
if(!isset($_SESSION['student_id'])){ http_response_code(401); exit; }

$new = trim($_POST['newpass'] ?? '');
if(strlen($new) < 6){
  echo json_encode(['error'=>'Password too short']); exit;
}
$hash = password_hash($new,PASSWORD_DEFAULT);
$up=$conn->prepare("UPDATE users SET password=? WHERE id=?");
$up->bind_param("si",$hash,$_SESSION['student_id']);
$up->execute();
echo json_encode(['success'=>true]);
?>
