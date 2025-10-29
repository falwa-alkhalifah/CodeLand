<?php
// Security check: Only logged-in learners can submit feedback
require_once 'check_learner.php';

// Database connection configuration
$host = 'localhost';
$dbname = 'database';
$username = 'root'; // Update with your database username
$password = ''; // Update with your database password

// Create MySQLi connection
$conn = new mysqli($host, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// Check if form was submitted with required data
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['quizID'])) {
    die("Error: Invalid request. Quiz ID not provided.");
}

// Get form data
$quizID = intval($_POST['quizID']);
$rating = isset($_POST['rating']) ? floatval($_POST['rating']) : 0.0;
$comments = isset($_POST['comments']) ? trim($_POST['comments']) : '';
$currentDate = date('Y-m-d'); // Get current date in YYYY-MM-DD format

// Validate rating (must be between 0.0 and 5.0)
if ($rating < 0.0 || $rating > 5.0) {
    die("Error: Invalid rating value.");
}

// Insert feedback into QuizFeedback table
$insertFeedbackQuery = "
    INSERT INTO QuizFeedback (quizID, rating, comments, date) 
    VALUES (?, ?, ?, ?)
";

$stmt = $conn->prepare($insertFeedbackQuery);
$stmt->bind_param("idss", $quizID, $rating, $comments, $currentDate);

// Execute the query
if ($stmt->execute()) {
    // Feedback saved successfully
    $stmt->close();
    $conn->close();
    
    // Redirect to Learner's homepage
    header("Location: LernerHomePage.Html");
    exit();
} else {
    // Error occurred
    $stmt->close();
    $conn->close();
    die("Error: Could not save feedback. " . $conn->error);
}
?>
