<?php
session_start();
require_once 'db_config.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['user_type'] ?? '') !== 'educator') {
  header('Location: login.php?err=notEducator'); exit;
}
$conn->set_charset('utf8mb4');

$educatorId = (int)$_SESSION['user_id'];
$rqId   = filter_input(INPUT_POST, 'rq_id',  FILTER_VALIDATE_INT);
$quizId = filter_input(INPUT_POST, 'quiz_id',FILTER_VALIDATE_INT);
$action = $_POST['action'] ?? '';
$comments = trim($_POST['comments'] ?? '');

if (!$rqId || !$quizId || !in_array($action, ['approved','disapproved'], true)) {
  header('Location: educator_homepage.php?review=ok'); exit; // صيانة: لا نفشل UI
}

/* تأكيد أن التوصية تعود لك (عبر الكويز) */
$sql = "SELECT rq.*, q.educatorID
        FROM recommendedquestion rq
        JOIN quiz q ON q.id = rq.quizID
        WHERE rq.id = ?";
$st = $conn->prepare($sql);
$st->bind_param("i", $rqId);
$st->execute();
$rec = $st->get_result()->fetch_assoc();
$st->close();

if (!$rec || (int)$rec['educatorID'] !== $educatorId) {
  header('Location: educator_homepage.php?review=ok'); exit;
}

/* 1) تحديث الحالة + التعليق */
$up = $conn->prepare("UPDATE recommendedquestion SET status=?, comments=? WHERE id=?");
$up->bind_param("ssi", $action, $comments, $rqId);
$up->execute();
$up->close();

/* 2) لو موافقة: انسخ السؤال إلى جدول quizquestion */
if ($action === 'approved') {
  $ins = $conn->prepare(
    "INSERT INTO quizquestion
     (quizID, question, questionFigureFileName, answerA, answerB, answerC, answerD, correctAnswer)
     VALUES (?,?,?,?,?,?,?,?)"
  );
  $ins->bind_param(
    "isssssss",
    $rec['quizID'],
    $rec['question'],
    $rec['questionFigureFileName'],
    $rec['answerA'],$rec['answerB'],$rec['answerC'],$rec['answerD'],
    $rec['correctAnswer']
  );
  $ins->execute();
  $ins->close();
}

/* 3) رجوع للصفحة الرئيسية مع فلاش */
header('Location: educator_homepage.php?review=ok'); exit;
