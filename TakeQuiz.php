<?php
// Start session and security check
session_start();

// Security check: Only logged-in learners can access this page
if(!isset($_SESSION['user_type']) || $_SESSION['user_type'] != 'learner' || !isset($_SESSION['user_id'])) {
    header("Location: index.html");
    exit();
}

// Database connection configuration
$host = 'localhost';
$dbname = 'database';
$username = 'root'; 
$password = 'root'; 

// Create MySQLi connection
$conn = new mysqli($host, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// Check if quiz ID is provided in the query string
if (!isset($_GET['quizID']) || empty($_GET['quizID'])) {
    die("Error: Quiz ID not provided.");
}

$quizID = intval($_GET['quizID']); // Sanitize quiz ID
$learnerID = intval($_SESSION['user_id']);

// Get learner information for profile picture
$learnerQuery = "SELECT photoFileName FROM user WHERE id = ?";
$stmt = $conn->prepare($learnerQuery);
$stmt->bind_param("i", $learnerID);
$stmt->execute();
$result = $stmt->get_result();
$learner = $result->fetch_assoc();
$stmt->close();

// Set default avatar and learner photo path
$defaultAvatar = 'images/default_avatar.jpeg';
$storedPhoto = $learner['photoFileName'] ?? '';
$learnerPhotoPath = $defaultAvatar;
$photoUploadsDir = __DIR__ . '/uploads/users/';

if ($storedPhoto && is_file($photoUploadsDir . $storedPhoto)) {
    $learnerPhotoPath = 'uploads/users/' . htmlspecialchars($storedPhoto) . '?v=' . @filemtime($photoUploadsDir . $storedPhoto);
} else {
    if (is_file(__DIR__ . '/images/' . $storedPhoto)) {
        $learnerPhotoPath = 'images/' . htmlspecialchars($storedPhoto);
    }
}

// Retrieve quiz information with topic name and educator details
$quizQuery = "
    SELECT 
        Quiz.id AS quizID,
        Topic.topicName,
        User.firstName AS educatorFirstName,
        User.lastName AS educatorLastName,
        User.photoFileName AS educatorPhoto
    FROM Quiz
    INNER JOIN Topic ON Quiz.topicID = Topic.id
    INNER JOIN User ON Quiz.educatorID = User.id
    WHERE Quiz.id = ? AND User.userType = 'educator'
";

// Prepare statement for quiz info
$stmt = $conn->prepare($quizQuery);
$stmt->bind_param("i", $quizID);
$stmt->execute();
$result = $stmt->get_result();
$quizInfo = $result->fetch_assoc();
$stmt->close();

// Check if quiz exists
if (!$quizInfo) {
    die("Error: Quiz not found.");
}

// Function to get image path
function getImagePath($fileName, $isFigure = false) {
    global $defaultAvatar;
    $filePath = $defaultAvatar;

    if (empty($fileName)) {
        return $filePath;
    }

    $baseDir = __DIR__;
    $userUploadsDir = $baseDir . '/uploads/users/';
    $userRelativePath = 'uploads/users/';
    $figureUploadsDir = $baseDir . '/uploads/figures/';
    $figureRelativePath = 'uploads/figures/';

    if ($isFigure) {
        if (is_file($figureUploadsDir . $fileName)) {
            $filePath = $figureRelativePath . htmlspecialchars($fileName) . '?v=' . @filemtime($figureUploadsDir . $fileName);
        } else if (is_file($baseDir . '/images/' . $fileName)) {
            $filePath = 'images/' . htmlspecialchars($fileName);
        }
    } else {
        if (is_file($userUploadsDir . $fileName)) {
            $filePath = $userRelativePath . htmlspecialchars($fileName) . '?v=' . @filemtime($userUploadsDir . $fileName);
        } else if (is_file($baseDir . '/images/' . $fileName)) {
            $filePath = 'images/' . htmlspecialchars($fileName);
        }
    }

    return $filePath;
}

// Get educator photo path
$educatorPhotoPath = getImagePath($quizInfo['educatorPhoto'], false);

// Retrieve all questions for this quiz
$questionsQuery = "
    SELECT 
        id,
        question,
        questionFigureFileName,
        answerA,
        answerB,
        answerC,
        answerD,
        correctAnswer
    FROM QuizQuestion
    WHERE quizID = ?
";

// Prepare statement for questions
$stmt = $conn->prepare($questionsQuery);
$stmt->bind_param("i", $quizID);
$stmt->execute();
$result = $stmt->get_result();

// Fetch all questions into an array
$allQuestions = [];
while ($row = $result->fetch_assoc()) {
    $allQuestions[] = $row;
}
$stmt->close();

// Check if there are questions
if (count($allQuestions) == 0) {
    die("Error: No questions available for this quiz.");
}

// Randomly select 5 questions (or all if less than 5)
$numberOfQuestionsToDisplay = min(5, count($allQuestions));
shuffle($allQuestions); // Randomize the order
$selectedQuestions = array_slice($allQuestions, 0, $numberOfQuestionsToDisplay);

// Extract selected question IDs for hidden input
$selectedQuestionIDs = array_column($selectedQuestions, 'id');

// Close database connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title><?php echo htmlspecialchars($quizInfo['topicName']); ?> Quiz</title>
   <link rel="stylesheet" href="H-style.css">
   <link rel="stylesheet" href="HF.css">
</head>

<body>

    <!-- ====== HEADER ====== -->
    <header class="cl-header">
        <div class="brand">
            <img src="images/logo.png" alt="Logo">
            <span>Codeland</span>
        </div>

        <div class="actions">
            <a href="learner_homepage.php">
                <img src="<?php echo $learnerPhotoPath; ?>" alt="Profile" class="avatar">
            </a>
            <a href="index.html" class="logout-btn">Logout</a>
        </div>
    </header>

    <main> 
        <div class="container">
            <!-- Quiz Info - Display dynamic quiz topic and educator details -->
            <div class="quiz-info">
                <div>
                    <h2><?php echo htmlspecialchars($quizInfo['topicName']); ?></h2>
                    <p>
                        <img src="<?php echo $educatorPhotoPath; ?>" alt="Educator Photo" class="avatar"> 
                        <strong>By: <?php echo htmlspecialchars($quizInfo['educatorFirstName'] . ' ' . $quizInfo['educatorLastName']); ?></strong>
                    </p>
                </div>
            </div>
   
            <!-- Quiz Form - Submit to Score-Feedback.php with quiz data -->
            <form action="Score-Feedback.php" method="POST">
              
                <!-- Hidden input for Quiz ID -->
                <input type="hidden" name="quizID" value="<?php echo htmlspecialchars($quizID); ?>">
              
                <!-- Hidden input for selected question IDs (comma-separated) -->
                <input type="hidden" name="questionIDs" value="<?php echo htmlspecialchars(implode(',', $selectedQuestionIDs)); ?>">
              
                <?php 
                // Loop through selected questions and display them
                $questionNumber = 1;
                foreach ($selectedQuestions as $question) {
                    $questionID = $question['id'];
                    $figurePath = getImagePath($question['questionFigureFileName'], true);
                ?>
              
                <!-- Question <?php echo $questionNumber; ?> -->
                <div class="question">
                    <p>
                        <?php echo $questionNumber; ?>. <?php echo htmlspecialchars($question['question']); ?>
                      
                        <?php 
                        // Display question figure if available
                        if (!empty($question['questionFigureFileName'])) {
                            echo '<br><img src="' . $figurePath . '" alt="Question Image">';
                        }
                        ?>
                    </p>
                  
                    <div class="choices">
                        <!-- Answer A -->
                        <label>
                            <input type="radio" name="q<?php echo $questionID; ?>" value="a" required>
                            A- <?php echo htmlspecialchars($question['answerA']); ?>
                        </label>
                      
                        <!-- Answer B -->
                        <label>
                            <input type="radio" name="q<?php echo $questionID; ?>" value="b">
                            B- <?php echo htmlspecialchars($question['answerB']); ?>
                        </label>
                      
                        <!-- Answer C -->
                        <label>
                            <input type="radio" name="q<?php echo $questionID; ?>" value="c">
                            C- <?php echo htmlspecialchars($question['answerC']); ?>
                        </label>
                      
                        <!-- Answer D -->
                        <label>
                            <input type="radio" name="q<?php echo $questionID; ?>" value="d">
                            D- <?php echo htmlspecialchars($question['answerD']); ?>
                        </label>
                    </div>
                </div>
              
                <?php 
                    $questionNumber++;
                } // End foreach loop
                ?>
   
                <!-- Submit Button -->
                <button type="submit">Submit Quiz</button>
            </form>
        </div>
    </main> 

    <!-- ====== FOOTER ====== -->
    <footer class="cl-footer">
        <p>OUR VISION</p>
        <p>At CodeLand, we make coding education simple, engaging, and accessible for everyone</p>
        <p>© <span id="year"></span> Website. All rights reserved.</p>
        <div class="social">
            <a href="#"><img src="images/xpng.jpg" alt="Twitter"></a>
            <a href="#"><img src="images/facebook.png" alt="Facebook"></a>
            <a href="#"><img src="images/instagram.png" alt="Instagram"></a>
        </div>
    </footer>

    <script>
        document.getElementById('year').textContent = new Date().getFullYear();
    </script>
   
</body>
</html>
