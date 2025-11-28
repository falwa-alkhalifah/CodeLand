<?php
// delete-question.php — removes a quiz question and its figure image (AJAX version)
session_start();
require_once 'db_config.php';

header("Content-Type: application/json");

// Security check
if (!isset($_SESSION['user_id']) || ($_SESSION['user_type'] ?? '') !== 'educator') {
    echo json_encode(false);
    exit;
}

$conn->set_charset('utf8mb4');

// Validate input — AJAX uses POST
$quizID = filter_input(INPUT_POST, 'quizID', FILTER_VALIDATE_INT);
$questionID = filter_input(INPUT_POST, 'questionID', FILTER_VALIDATE_INT);

if (!$quizID || !$questionID) {
    echo json_encode(false);
    exit;
}

$educatorId = (int)$_SESSION['user_id'];

// Verify question belongs to educator
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
    echo json_encode(false);
    exit;
}

// Delete question
$deleteStmt = $conn->prepare("DELETE FROM quizquestion WHERE id=? AND quizID=?");
$deleteStmt->bind_param("ii", $questionID, $quizID);
$ok = $deleteStmt->execute();
$deleteStmt->close();

// Delete associated figure
if ($ok && !empty($result['questionFigureFileName'])) {
    $baseDir = __DIR__;
    $figurePath = $baseDir . '/uploads/figures/' . $result['questionFigureFileName'];
    $imagePath  = $baseDir . '/images/' . $result['questionFigureFileName'];

    if (is_file($figurePath)) { @unlink($figurePath); }
    elseif (is_file($imagePath)) { @unlink($imagePath); }
}

$conn->close();

// Return TRUE or FALSE as required
echo json_encode($ok);
exit;
?>
