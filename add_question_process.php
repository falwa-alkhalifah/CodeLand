<?php
// add_question_process.php — معالجة إضافة السؤال
session_start();
require_once 'db_config.php';
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }
if (($_SESSION['user_type'] ?? '') !== 'educator') { header("Location: LearnerHomePage.php"); exit; }
$conn->set_charset('utf8mb4');

// (a) استلام quizID
$quizID = filter_input(INPUT_POST, 'quizID', FILTER_VALIDATE_INT);
if (!$quizID) { header("Location: educator_homepage.php?error=missingQuizId"); exit; }

$educatorId = (int)$_SESSION['user_id'];

// تأكيد ملكية الكويز
$st = $conn->prepare("SELECT id FROM quiz WHERE id=? AND educatorID=?");
$st->bind_param("ii", $quizID, $educatorId);
$st->execute();
$own = (bool)$st->get_result()->fetch_assoc();
$st->close();
if (!$own) { header("Location: educator_homepage.php?error=notOwner"); exit; }

// قراءة الحقول
$text = trim($_POST['text'] ?? '');
$c0   = trim($_POST['c0'] ?? '');
$c1   = trim($_POST['c1'] ?? '');
$c2   = trim($_POST['c2'] ?? '');
$c3   = trim($_POST['c3'] ?? '');
$idx  = filter_input(INPUT_POST, 'correctIndex', FILTER_VALIDATE_INT);

if ($text==='' || $c0==='' || $c1==='' || $c2==='' || $c3==='' || !in_array($idx,[0,1,2,3],true)) {
  header("Location: add_question.php?quizID={$quizID}&error=invalidInput"); exit;
}

$letters = ['A','B','C','D'];
$correct = $letters[$idx];

// رفع صورة (اختياري)
$figureName = null;
$dir = __DIR__.'/uploads/questions/';
if (!is_dir($dir)) { @mkdir($dir,0775,true); }
if (isset($_FILES['figure']) && $_FILES['figure']['error'] !== UPLOAD_ERR_NO_FILE) {
  if ($_FILES['figure']['error'] === UPLOAD_ERR_OK) {
    $ext = strtolower(pathinfo($_FILES['figure']['name'], PATHINFO_EXTENSION));
    if (in_array($ext,['jpg','jpeg','png','gif','webp'],true)) {
      try { $rand = bin2hex(random_bytes(4)); } catch(Exception $e) { $rand = mt_rand(100000,999999); }
      $figureName = "q_{$quizID}_".time()."_{$rand}.".$ext;
      if (!move_uploaded_file($_FILES['figure']['tmp_name'], $dir.$figureName)) {
        $figureName = null;
      }
    } else {
      header("Location: add_question.php?quizID={$quizID}&error=uploadError"); exit;
    }
  } else {
    header("Location: add_question.php?quizID={$quizID}&error=uploadError"); exit;
  }
}

// الإدخال (lowercase schema)
$sql = "INSERT INTO quizquestion
          (quizID, question, questionFigureFileName, answerA, answerB, answerC, answerD, correctAnswer)
        VALUES (?,?,?,?,?,?,?,?)";
$ins = $conn->prepare($sql);
$ins->bind_param("isssssss", $quizID, $text, $figureName, $c0, $c1, $c2, $c3, $correct);
$ins->execute();
$ins->close();

// (c) إعادة التوجيه إلى صفحة الكويز
header("Location: quiz.php?quizID={$quizID}&added=1");
exit;
