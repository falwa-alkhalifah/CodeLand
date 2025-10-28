<?php
// Security check: Only logged-in learners can access this page
require_once 'check_learner.php';

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

// Get quiz ID and question IDs from the form submission
$quizID = intval($_POST['quizID']);
$questionIDsString = $_POST['questionIDs'];
$questionIDs = explode(',', $questionIDsString); // Convert comma-separated string to array

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

// Bind parameters dynamically (all question IDs are integers)
$types = str_repeat('i', count($questionIDs));
$stmt->bind_param($types, ...$questionIDs);
$stmt->execute();
$result = $stmt->get_result();

// Store correct answers in an associative array [questionID => correctAnswer]
$correctAnswers = [];
while ($row = $result->fetch_assoc()) {
    $correctAnswers[$row['id']] = $row['correctAnswer'];
}
$stmt->close();

// Calculate the score by comparing learner's answers with correct answers
$score = 0;
$totalQuestions = count($questionIDs);

foreach ($questionIDs as $questionID) {
    $questionID = intval($questionID);
    
    // Check if learner submitted an answer for this question
    if (isset($_POST["q$questionID"])) {
        $learnerAnswer = $_POST["q$questionID"];
        
        // Check if answer is correct
        if (isset($correctAnswers[$questionID]) && $learnerAnswer === $correctAnswers[$questionID]) {
            $score++;
        }
    }
}

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
    $congratsMessage = "Perfect Score! Well done " . htmlspecialchars($_SESSION['firstName']) . " 👏";
} else {
    // Not full marks - show encouragement video
    $videoFile = "images/Almost_video.mp4";
    $congratsMessage = "Great effort " . htmlspecialchars($_SESSION['firstName']) . "! 👏";
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
            <a href="LernerHomePage.Html">
                <img src="images/educatorUser.jpeg" alt="User" class="avatar">
            </a>
            <a href="logout.php" class="logout-btn">Logout</a>
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
                        <a href="LernerHomePage.Html" class="skip-link">Skip feedback</a> 
                    </div>
                </form>
            </div>
        </div>
    </main>

    <!-- ====== FOOTER ====== -->
    <footer class="cl-footer">
        <p>OUR VISION</p>
        <p>At CodeLand, we make coding education simple, engaging, and accessible for everyone</p>
        <p>© <span id="year"></span>2025 Website. All rights reserved.</p>
        <div class="social">
            <a href="#"><img src="images/xpng.jpg" alt="Twitter"></a>
            <a href="#"><img src="images/facebook.png" alt="Facebook"></a>
            <a href="#"><img src="images/instagram.png" alt="Instagram"></a>
        </div>
    </footer>

    <script>
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
