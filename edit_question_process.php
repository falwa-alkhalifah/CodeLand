<?php
session_start();
require_once 'db_config.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['user_type'] ?? '') !== 'educator') {
  header("Location: login.php"); exit;
}
$conn->set_charset('utf8mb4');

$educatorId  = (int)$_SESSION['user_id'];
$questionID  = filter_input(INPUT_POST, 'questionID', FILTER_VALIDATE_INT);
$quizID      = filter_input(INPUT_POST, 'quizID',     FILTER_VALIDATE_INT);

$question    = trim($_POST['question']   ?? '');
$answerA     = trim($_POST['answerA']    ?? '');
$answerB     = trim($_POST['answerB']    ?? '');
$answerC     = trim($_POST['answerC']    ?? '');
$answerD     = trim($_POST['answerD']    ?? '');
$correct     = $_POST['correctAnswer']   ?? '';
$oldImage    = trim($_POST['old_image']  ?? '');

if (!$questionID || !$quizID || !in_array($correct, ['A','B','C','D'], true)) {
  header("Location: educator_homepage.php?error=badEdit"); exit;
}

$own = $conn->prepare(
  "SELECT qq.questionFigureFileName
   FROM quizquestion qq
   JOIN quiz q ON q.id = qq.quizID
   WHERE qq.id=? AND qq.quizID=? AND q.educatorID=?"
);
$own->bind_param("iii", $questionID, $quizID, $educatorId);
$own->execute();
$ownRow = $own->get_result()->fetch_assoc();
$own->close();

if (!$ownRow) { header("Location: educator_homepage.php?error=notOwner"); exit; }

$newFileName = $oldImage; 
if (isset($_FILES['figure']) && $_FILES['figure']['error'] === UPLOAD_ERR_OK && $_FILES['figure']['size'] > 0) {
  $ext = strtolower(pathinfo($_FILES['figure']['name'], PATHINFO_EXTENSION));
  $allowed = ['jpg','jpeg','png','gif','webp'];
  if (in_array($ext, $allowed, true)) {
    $newName = 'q_'.time().'_'.bin2hex(random_bytes(4)).'.'.$ext;
    $dest = __DIR__ . '/uploads/questions/' . $newName;
    if (move_uploaded_file($_FILES['figure']['tmp_name'], $dest)) {
      if (!empty($oldImage)) {
        $oldPath = __DIR__ . '/uploads/questions/' . $oldImage;
        if (is_file($oldPath)) { @unlink($oldPath); }
      }
      $newFileName = $newName;
    }
  }
}

/* تحديث السجل */
$up = $conn->prepare(
  "UPDATE quizquestion
   SET question=?, questionFigureFileName=?, answerA=?, answerB=?, answerC=?, answerD=?, correctAnswer=?
   WHERE id=? AND quizID=?"
);
$up->bind_param(
  "sssssssi i",
  $question, $newFileName, $answerA, $answerB, $answerC, $answerD, $correct,
  $questionID, $quizID
);
$ok = $up->execute();
$up->close();

/* رجوع لصفحة الكويز */
header("Location: quiz.php?quizID={$quizID}&updated=1");
exit;
