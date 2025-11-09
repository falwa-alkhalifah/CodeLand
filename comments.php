<?php

$host = 'localhost'; 
$db   = 'database'; 
$user = 'root'; 
$pass = 'root';

$comments = [];
$quiz_topic = 'Topic Not Found'; 
$quiz_educator = 'Instructor Not Found'; 
$error_message = null;
$quiz_id = null;

function render_rating($rating) {
    $full_star = '★';
    $empty_star = '☆';
    $rating_int = (int) round($rating); 
    $stars = str_repeat($full_star, $rating_int) . str_repeat($empty_star, 5 - $rating_int);
    return $stars;
}
try {
    $quiz_id = filter_input(INPUT_GET, 'quizID', FILTER_VALIDATE_INT); 
    if ($quiz_id === false || $quiz_id === null) {
        throw new Exception("Invalid or missing Quiz ID.");
    }
    $conn = mysqli_connect($host, $user, $pass, $db);
    if (!$conn) {
        throw new Exception("Database connection failed: " . mysqli_connect_error());
    }
    $quiz_info_sql = "
        SELECT 
            t.topicName, 
            u.firstName,
            u.lastName
        FROM quiz q
        JOIN topic t ON q.topicID = t.id
        JOIN user u ON q.educatorID = u.id
        WHERE q.id = ?
    ";
    $stmt_quiz = mysqli_prepare($conn, $quiz_info_sql);
    mysqli_stmt_bind_param($stmt_quiz, "i", $quiz_id);
    mysqli_stmt_execute($stmt_quiz);
    $result_quiz = mysqli_stmt_get_result($stmt_quiz);
    if ($quiz_info = mysqli_fetch_assoc($result_quiz)) {
        $quiz_topic = htmlspecialchars($quiz_info['topicName']);
        $quiz_educator = 'Dr. ' . htmlspecialchars($quiz_info['firstName']) . ' ' . htmlspecialchars($quiz_info['lastName']);
    }
    mysqli_stmt_close($stmt_quiz);
    $comments_sql = "
        SELECT comments, rating, date 
        FROM quizfeedback 
        WHERE quizID = ? 
        ORDER BY date DESC 
    ";
    $stmt_comments = mysqli_prepare($conn, $comments_sql);
    mysqli_stmt_bind_param($stmt_comments, "i", $quiz_id);
    mysqli_stmt_execute($stmt_comments);
    $result_comments = mysqli_stmt_get_result($stmt_comments);
    while ($row = mysqli_fetch_assoc($result_comments)) {
        $comments[] = $row;
    }
    mysqli_stmt_close($stmt_comments);
    mysqli_close($conn);
} catch (Exception $e) {
    $comments = []; 
    $error_message = "An error occurred while loading quiz data or comments.";
    error_log("Application Error (MySQLi): " . $e->getMessage()); 
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Comments</title>
    <link rel="stylesheet" href="lernerStyle.css">
    <link rel="stylesheet" href="HF.css">
</head>

<body>
<header class="cl-header">
    <div class="brand">
        <img src="images/logo.png" alt="Logo">
        <span>Codeland</span>
    </div>
    <div class="actions">
        <a href="LernerHomePage.Html">
            <img src="images/educatorUser.jpeg" alt="User" class="avatar">
        </a>
        <a href="index.html" class="logout-btn">Logout</a>
    </div>
</header>
<main class="container main">
    <h1>Comments · <span class="badge"><?php echo $quiz_topic; ?></span> with <span class="badge"><?php echo $quiz_educator; ?></span></h1>
    <section class="card">
        <?php if ($error_message): ?>
            <p style="color: red; padding: 10px; border: 1px solid red;"><?php echo $error_message; ?></p>
        <?php elseif (empty($comments)): ?>
            <p>No comments found for Quiz ID **<?php echo htmlspecialchars($quiz_id); ?>** yet.</p>
        <?php else: ?>
            <?php foreach ($comments as $comment): ?>
            <div class="comment">
                <div class="meta">
                    <?php 
                        echo htmlspecialchars($comment['date']); 
                    ?> 
                    · Rating: <span class="star"><?php echo render_rating($comment['rating']); ?></span>
                </div>
                <div><?php echo htmlspecialchars($comment['comments']); ?></div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </section>
</main>

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

</body>
</html>