<?php
session_start();
require_once 'db_config.php';
if (!isset($_SESSION['user_id']) || ($_SESSION['user_type']??'')!=='educator') { header('Location: login.php'); exit; }
$conn->set_charset('utf8mb4');

$educatorId = (int)$_SESSION['user_id'];
$topicID    = filter_input(INPUT_POST, 'topicID', FILTER_VALIDATE_INT);
if (!$topicID) { header('Location: add-quiz.php?err=topic'); exit; }

/* نسمح بتكرار الكويز لنفس التوبيك، فقط نتحقق أن التوبيك موجود وهو واحد من الأربعة */
$fixedTopics = ['Python','Java','CSS','HTML'];
$in  = implode(',', array_fill(0, count($fixedTopics), '?'));
$typ = str_repeat('s', count($fixedTopics));

$ok = false;
$st = $conn->prepare("SELECT id, topicName FROM topic WHERE id=?");
$st->bind_param("i", $topicID);
$st->execute();
$topicRow = $st->get_result()->fetch_assoc();
$st->close();

if ($topicRow && in_array($topicRow['topicName'], $fixedTopics, true)) {
  $ok = true;
}
if (!$ok) { header('Location: add-quiz.php?err=topic'); exit; }

/* إنشاء الكويز (نسمح بالتكرار) */
$ins = $conn->prepare("INSERT INTO quiz (educatorID, topicID) VALUES (?, ?)");
$ins->bind_param("ii", $educatorId, $topicID);
$ins->execute();
$newQuizId = $conn->insert_id;
$ins->close();

header("Location: quiz.php?quizID={$newQuizId}");
exit;
