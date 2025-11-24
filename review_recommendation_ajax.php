<?php
// review_recommendation_ajax.php
// يستقبل طلب AJAX من صفحة المعلّم للموافقة / الرفض على سؤال مُوصّى به

session_start();
require_once 'db_config.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id']) || ($_SESSION['user_type'] ?? '') !== 'educator') {
    echo json_encode(false);
    exit;
}

$conn->set_charset('utf8mb4');

$educatorId = (int)$_SESSION['user_id'];

// قراءة البيانات القادمة من AJAX
$rq_id    = filter_input(INPUT_POST, 'rq_id',   FILTER_VALIDATE_INT);
$quiz_id  = filter_input(INPUT_POST, 'quiz_id', FILTER_VALIDATE_INT);
$action   = $_POST['action']   ?? '';
$comments = trim($_POST['comments'] ?? '');

if (!$rq_id || !$quiz_id || !in_array($action, ['approved', 'disapproved'], true)) {
    echo json_encode(false);
    exit;
}

/* 1) تأكيد أن التوصية تابعة لكويز يملكه هذا المعلّم */
$sql = $conn->prepare(
    "SELECT rq.*, q.educatorID
     FROM recommendedquestion rq
     JOIN quiz q ON q.id = rq.quizID
     WHERE rq.id = ? AND rq.quizID = ?"
);
$sql->bind_param("ii", $rq_id, $quiz_id);
$sql->execute();
$rec = $sql->get_result()->fetch_assoc();
$sql->close();

if (!$rec || (int)$rec['educatorID'] !== $educatorId) {
    echo json_encode(false);
    exit;
}

/* 2) تحديث حالة التوصية والتعليق */
$up = $conn->prepare("UPDATE recommendedquestion SET status = ?, comments = ? WHERE id = ?");
$up->bind_param("ssi", $action, $comments, $rq_id);
$ok = $up->execute();
$up->close();

/* 3) إذا Approved انقلي السؤال لجدول QuizQuestion */
if ($ok && $action === 'approved') {
    $ins = $conn->prepare(
        "INSERT INTO quizquestion
         (quizID, question, questionFigureFileName,
          answerA, answerB, answerC, answerD, correctAnswer)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
    );
    $ins->bind_param(
        "isssssss",
        $rec['quizID'],
        $rec['question'],
        $rec['questionFigureFileName'],
        $rec['answerA'],
        $rec['answerB'],
        $rec['answerC'],
        $rec['answerD'],
        $rec['correctAnswer']
    );
    $ok = $ins->execute() && $ok;
    $ins->close();
}

/* 4) رجّع نتيجة منطقية لـ AJAX */
echo json_encode($ok === true);
