<?php
// delete-question.php — removes a quiz question and its figure image
session_start();
require_once 'db_config.php';

if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }
if (($_SESSION['user_type'] ?? '') !== 'educator') { header("Location: LearnerHomePage.php"); exit; }
$conn->set_charset('utf8mb4');

// Validate input
$quizID = filter_input(INPUT_GET, 'quizID', FILTER_VALIDATE_INT);
$questionID = filter_input(INPUT_GET, 'questionID', FILTER_VALIDATE_INT);

if (!$quizID || !$questionID) {
    header("Location: educator_homepage.php?error=missingParams");
    exit;
}

$educatorId = (int)$_SESSION['user_id'];

// Verify the question belongs to this educator
$sql = "
    SELECT qq.questionFigureFileName
    FROM quizquestion qq
    JOIN quiz q ON q.id = qq.quizID
    WHERE qq.id=? AND qq.quizID=? AND q.educatorID=?
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iii", $questionID, $quizID, $educatorId);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$result) {
    header("Location: quiz.php?quizID={$quizID}&error=notFound");
    exit;
}

// Delete the question record
$deleteStmt = $conn->prepare("DELETE FROM quizquestion WHERE id=? AND quizID=?");
$deleteStmt->bind_param("ii", $questionID, $quizID);
$ok = $deleteStmt->execute();
$deleteStmt->close();

// Delete the associated image if present
if ($ok && !empty($result['questionFigureFileName'])) {
    $baseDir = __DIR__;
    $figurePath = $baseDir . '/uploads/figures/' . $result['questionFigureFileName'];
    $imagePath = $baseDir . '/images/' . $result['questionFigureFileName'];

    if (is_file($figurePath)) { @unlink($figurePath); }
    elseif (is_file($imagePath)) { @unlink($imagePath); }
}

$conn->close();

// Redirect back to quiz page
header("Location: quiz.php?quizID={$quizID}&deleted=1");
exit;
?>
