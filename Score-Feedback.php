<?php
// Start session and security check
session_start();

// Security: Only logged-in learners can access this page
if (!isset($_SESSION['user_id']) || ($_SESSION['user_type'] ?? '') !== 'learner') {
    $_SESSION['login_error'] = "You must log in as a learner.";
    header("Location: login.php");
    exit;
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

// Check if form was submitted with required data
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['quizID']) || !isset($_POST['questionIDs'])) {
    die("Error: Invalid request. Quiz data not provided.");
}

// Get data from form submission
$quizID = intval($_POST['quizID']);
$questionIDsString = $_POST['questionIDs'];
$questionIDs = array_map('intval', explode(',', $questionIDsString));
$learnerID = intval($_SESSION['user_id']);

// Get learner information
$learnerQuery = "SELECT firstName, photoFileName FROM user WHERE id = ?";
$stmt = $conn->prepare($learnerQuery);
$stmt->bind_param("i", $learnerID);
$stmt->execute();
$result = $stmt->get_result();
$learner = $result->fetch_assoc();
$stmt->close();

if (!$learner) {
    die("Error: Learner not found.");
}

// Function to get image path
function getImagePath($fileName) {
    $defaultAvatar = 'images/default_avatar.jpeg';
    
    if (empty($fileName)) {
        return $defaultAvatar;
    }

    $baseDir = __DIR__;
    $userUploadsDir = $baseDir . '/uploads/users/';
    $userRelativePath = 'uploads/users/';

    if (is_file($userUploadsDir . $fileName)) {
        return $userRelativePath . htmlspecialchars($fileName) . '?v=' . @filemtime($userUploadsDir . $fileName);
    } else if (is_file($baseDir . '/images/' . $fileName)) {
        return 'images/' . htmlspecialchars($fileName);
    }
    
    return $defaultAvatar;
}

// Set learner photo path for header
$learnerPhotoPath = getImagePath($learner['photoFileName']);

// Retrieve quiz information with topic name and educator details
$quizQuery = "
    SELECT 
        Quiz.id AS quizID,
        Topic.topicName,
        User.firstName AS educatorFirstName,
        User.lastName AS educatorLastName
    FROM Quiz
    INNER JOIN Topic ON Quiz.topicID = Topic.id
    INNER JOIN User ON Quiz.educatorID = User.id
    WHERE Quiz.id = ? AND User.userType = 'educator'
";

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

// Retrieve correct answers for the selected questions
$placeholders = implode(',', array_fill(0, count($questionIDs), '?'));
$correctAnswersQuery = "
    SELECT 
        id,
        correctAnswer
    FROM QuizQuestion
    WHERE id IN ($placeholders)
";

$stmt = $conn->prepare($correctAnswersQuery);
$types = str_repeat('i', count($questionIDs));
$stmt->bind_param($types, ...$questionIDs);
$stmt->execute();
$result = $stmt->get_result();

// Store correct answers in an associative array
$correctAnswers = [];
while ($row = $result->fetch_assoc()) {
    // Store correct answer in lowercase for comparison
    $correctAnswers[$row['id']] = strtolower(trim($row['correctAnswer']));
}
$stmt->close();

// Calculate the score
$score = 0;
$totalQuestions = count($questionIDs);
$debugInfo = []; // For debugging

foreach ($questionIDs as $questionID) {
    if (isset($_POST["q$questionID"])) {
        // Get learner's answer and convert to lowercase for comparison
        $learnerAnswer = strtolower(trim($_POST["q$questionID"]));
        $correctAnswer = isset($correctAnswers[$questionID]) ? $correctAnswers[$questionID] : 'N/A';
        $isCorrect = ($learnerAnswer === $correctAnswer);
        
        // Store debug info
        $debugInfo[] = [
            'questionID' => $questionID,
            'learnerAnswer' => $learnerAnswer,
            'correctAnswer' => $correctAnswer,
            'isCorrect' => $isCorrect
        ];
        
        // Compare answers (both are now lowercase)
        if ($isCorrect) {
            $score++;
        }
    }
}

// TEMPORARY DEBUG - Remove this after testing
// Uncomment the lines below to see what's being compared
/*
echo "<pre>DEBUG INFO:\n";
echo "Total Questions: $totalQuestions\n";
echo "Score: $score\n\n";
foreach ($debugInfo as $info) {
    echo "Question ID: " . $info['questionID'] . "\n";
    echo "Your Answer: '" . $info['learnerAnswer'] . "'\n";
    echo "Correct Answer: '" . $info['correctAnswer'] . "'\n";
    echo "Is Correct: " . ($info['isCorrect'] ? 'YES' : 'NO') . "\n\n";
}
echo "</pre>";
exit; // Stop here to see debug info
*/

// Insert the quiz score into TakenQuiz table
$insertScoreQuery = "INSERT INTO TakenQuiz (quizID, score) VALUES (?, ?)";
$stmt = $conn->prepare($insertScoreQuery);
$stmt->bind_param("ii", $quizID, $score);
$stmt->execute();
$stmt->close();

// Determine which video to display based on score
if ($score === $totalQuestions) {
    // Full marks - show celebration video
    $videoFile = "images/Fullmark_video.mp4";
    $congratsMessage = "Perfect Score! Well done " . htmlspecialchars($learner['firstName']) . " 🎉";
} else {
    // Not full marks - show encouragement video
    $videoFile = "images/Almost_video.MOV";
    $congratsMessage = "Great effort " . htmlspecialchars($learner['firstName']) . "! 💪";
}

// Close database connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quiz Results - <?php echo htmlspecialchars($quizInfo['topicName']); ?></title>
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
            <!-- Score Section - Display calculated score and appropriate video -->
            <div class="score-section">
                <div style="font-size: 1.5em; color: #a8c0d8; margin: 8px 0 14px; font-weight: 600;">
                    <?php echo $congratsMessage; ?>
                </div>
                
                <div class="score-display">
                    <?php echo $score; ?> out of <?php echo $totalQuestions; ?>
                </div>
                
                <div class="video-container">
                    <video autoplay muted controls>
                        <source src="<?php echo $videoFile; ?>" type="video/mp4">
                        Your browser does not support the video tag.
                    </video>
                </div>
            </div>

            <!-- Feedback Section -->
            <div class="feedback-section">
                <h2 class="feedback-title">We value your opinion</h2>
                
                <!-- Form submits to save-feedback.php to store feedback in database -->
                <form action="save-feedback.php" method="post">
                    
                    <!-- Hidden input: Quiz ID for storing feedback -->
                    <input type="hidden" name="quizID" value="<?php echo $quizID; ?>">
                    
                    <!-- Star Rating -->
                    <div class="rating-container">
                        <div class="star-rating" id="starRating">
                            <span class="star" data-rating="1">★</span>
                            <span class="star" data-rating="2">★</span>
                            <span class="star" data-rating="3">★</span>
                            <span class="star" data-rating="4">★</span>
                            <span class="star" data-rating="5">★</span>
                        </div>
                        <input type="hidden" name="rating" id="ratingValue" value="0">
                    </div>

                    <!-- Feedback Textarea -->
                    <textarea 
                        class="feedback-textarea" 
                        name="comments" 
                        placeholder="Please share your feedback"
                    ></textarea>

                    <!-- Submit Container -->
                    <div class="submit-container">
                        <button type="submit" class="submit-btn">Send</button>
                        <br>
                        <a href="learner_homepage.php" class="skip-link">Skip feedback</a> 
                    </div>
                </form>
            </div>
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

        // Star rating functionality - allows half-star ratings (0.5 increments)
        const stars = document.querySelectorAll('.star');
        const ratingValue = document.getElementById('ratingValue');
        let currentRating = 0;

        stars.forEach((star, index) => {
            // Hover effect - show potential rating
            star.addEventListener('mousemove', (e) => {
                const rect = star.getBoundingClientRect();
                const x = e.clientX - rect.left;
                const width = rect.width;
                const isLeftHalf = x < width / 2;
                
                const baseRating = parseInt(star.dataset.rating);
                const rating = isLeftHalf ? baseRating - 0.5 : baseRating;
                
                highlightStars(rating);
            });

            // Mouse leave - restore actual rating
            star.addEventListener('mouseleave', () => {
                highlightStars(currentRating);
            });

            // Click - set the rating
            star.addEventListener('click', (e) => {
                const rect = star.getBoundingClientRect();
                const x = e.clientX - rect.left;
                const width = rect.width;
                const isLeftHalf = x < width / 2;
                
                const baseRating = parseInt(star.dataset.rating);
                currentRating = isLeftHalf ? baseRating - 0.5 : baseRating;
                ratingValue.value = currentRating;
                highlightStars(currentRating);
            });
        });

        // Function to visually highlight stars based on rating
        function highlightStars(rating) {
            stars.forEach((star, index) => {
                const starRating = parseInt(star.dataset.rating);
                star.classList.remove('active', 'half');
                
                if (starRating <= Math.floor(rating)) {
                    star.classList.add('active');
                } else if (starRating === Math.floor(rating) + 1 && rating % 1 === 0.5) {
                    star.classList.add('half');
                }
            });
        }

        // Initialize with no rating
        highlightStars(0);
    </script>    

</body>
</html>
