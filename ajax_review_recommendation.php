<?php
// ajax_review_recommendation.php
session_start();
header('Content-Type: text/plain');

if (empty($_SESSION['user_id']) || (($_SESSION['user_type'] ?? '') !== 'educator')) { echo 'false'; exit; }

require_once 'db_config.php'; // $conn (mysqli)
$educatorId = (int)$_SESSION['user_id'];

$rqId     = (int)($_POST['rq_id'] ?? 0);
$quizId   = (int)($_POST['quiz_id'] ?? 0);
$action   = $_POST['action'] ?? '';
$comments = trim($_POST['comments'] ?? '');

if (!$rqId || !$quizId || !in_array($action, ['approved','disapproved'], true)) { echo 'false'; exit; }

// تأكيد ملكية المعلم
$sql = "SELECT rq.*, q.educatorID FROM RecommendedQuestion rq JOIN Quiz q ON q.id=rq.quizID WHERE rq.id=?";
$stmt = $conn->prepare($sql); $stmt->bind_param('i',$rqId); $stmt->execute();
$row = $stmt->get_result()->fetch_assoc(); $stmt->close();
if (!$row || (int)$row['educatorID'] !== $educatorId) { echo 'false'; exit; }

// تحديث الحالة + التعليقات
$sql = "UPDATE RecommendedQuestion SET status=?, comments=? WHERE id=?";
$stmt = $conn->prepare($sql); $stmt->bind_param('ssi',$action,$comments,$rqId);
if (!$stmt->execute()) { $stmt->close(); echo 'false'; exit; }
$stmt->close();

// لو الموافقـة → أضف السؤال إلى QuizQuestion
if ($action === 'approved') {
  $sql = "INSERT INTO QuizQuestion
          (quizID, question, questionFigureFileName, answerA, answerB, answerC, answerD, correctAnswer)
          VALUES (?,?,?,?,?,?,?,?)";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param(
    'isssssss',
    $row['quizID'],
    $row['question'],
    $row['questionFigureFileName'],
    $row['answerA'], $row['answerB'], $row['answerC'], $row['answerD'],
    $row['correctAnswer']
  );
  if (!$stmt->execute()) { $stmt->close(); echo 'false'; exit; }
  $stmt->close();
}

echo 'true';
