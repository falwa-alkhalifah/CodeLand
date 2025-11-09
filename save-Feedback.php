<?php
// Start session and verify learner access
session_start();
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'learner' || !isset($_SESSION['user_id'])) {
    header("Location: index.html");
    exit();
}

// Database connection
$host = 'localhost';
$dbname = 'database';
$username = 'root';
$password = 'root';
$conn = new mysqli($host, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// Handle feedback submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['quizID'])) {
    $quizID = intval($_POST['quizID']);
    $rating = isset($_POST['rating']) ? floatval($_POST['rating']) : 0;
    $comments = trim($_POST['comments'] ?? '');

    // Only insert if the learner actually gave feedback
    if ($rating > 0 || $comments !== '') {
        $insertFeedbackQuery = "
            INSERT INTO QuizFeedback (quizID, rating, comments, date)
            VALUES (?, ?, ?, NOW())
        ";
        $stmt = $conn->prepare($insertFeedbackQuery);
        $stmt->bind_param("ids", $quizID, $rating, $comments);
        $stmt->execute();
        $stmt->close();
    }
}

$conn->close();

// Redirect back to learner homepage
header("Location: learner_homepage.php");
exit();
?>
