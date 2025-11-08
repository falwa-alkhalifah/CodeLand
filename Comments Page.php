<?php 

session_start();
$db_host = "localhost";
$db_user = "root";
$db_pass = "root"; 
$db_name = "database";

$connect = mysqli_connect($db_host, $db_user, $db_pass, $db_name);
if(mysqli_connect_errno()) {
    die("Failed to connect to MySQL: " . mysqli_connect_error());
}

$quizID = isset($_GET['quiz_id']) ? intval($_GET['quiz_id']) : 0;
if ($quizID === 0) {
    $headerText = "Quiz Not Found";
    $comments = [];
} else {
    $headerQuery = "
        SELECT 
            t.topicName, 
            u.firstName, 
            u.lastName
        FROM quiz q
        JOIN topic t ON q.topicID = t.id
        JOIN user u ON q.educatorID = u.id
        WHERE q.id = ?
    ";
    $stmt = mysqli_prepare($connect, $headerQuery);
    mysqli_stmt_bind_param($stmt, "i", $quizID);
    mysqli_stmt_execute($stmt);
    $headerResult = mysqli_stmt_get_result($stmt);
    $quizDetails = mysqli_fetch_assoc($headerResult);
    mysqli_stmt_close($stmt);

    if ($quizDetails) {
        $topicName = htmlspecialchars($quizDetails['topicName']);
        $educatorName = htmlspecialchars("Dr. " . $quizDetails['lastName']);
        $headerText = "Comments · <span class='badge'>{$topicName}</span> with <span class='badge'>{$educatorName}</span>";
    } else {
        $headerText = "Comments · Quiz ID {$quizID}";
    }

    $commentsQuery = "
        SELECT 
            rating, 
            commentText, 
            DATE_FORMAT(timestamp, '%Y-%m-%d') AS commentDate
        FROM quizFeedback
        WHERE quizID = ?
        ORDER BY timestamp DESC
    ";
    $stmt = mysqli_prepare($connect, $commentsQuery);
    mysqli_stmt_bind_param($stmt, "i", $quizID);
    mysqli_stmt_execute($stmt);
    $commentsResult = mysqli_stmt_get_result($stmt);
    $comments = mysqli_fetch_all($commentsResult, MYSQLI_ASSOC);
    mysqli_stmt_close($stmt);
}
mysqli_close($connect);

function getStars($rating) {
    $rating = max(0, min(5, (int)$rating)); 
    $full = str_repeat('★', $rating);
    $empty = str_repeat('☆', 5 - $rating);
    return "<span class='star'>" . $full . $empty . "</span>";
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Quiz Comments</title>
  <link rel="stylesheet" href="lernerStyle.css">
  <link rel="stylesheet" href="HF.css">
  <style>
    .badge {
      display: inline-block;
      padding: 0.25em 0.6em;
      font-size: 0.85em;
      font-weight: 600;
      line-height: 1;
      text-align: center;
      white-space: nowrap;
      vertical-align: baseline;
      border-radius: 0.375rem;
      background-color: #0d9488;
      color: white;
    }
    .main {
        max-width: 800px;
        margin: 40px auto;
    }
    .card {
        padding: 20px;
        background-color: #fff;
        border-radius: 8px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -2px rgba(0, 0, 0, 0.1);
    }
    .comment {
        border-bottom: 1px solid #e5e7eb;
        padding: 15px 0;
    }
    .comment:last-child {
        border-bottom: none;
        padding-bottom: 0;
    }
    .meta {
        font-size: 0.9em;
        color: #6b7280;
        margin-bottom: 5px;
        display: flex;
        justify-content: space-between;
    }
    .star {
        color: gold;
        font-size: 1.1em;
    }
  </style>
</head>
<body>

<header class="cl-header">
  <div class="brand">
    <img src="images/logo.png" alt="Logo">
    <span>Codeland</span>
  </div>
  <div class="actions">
      <a href="LernerHomePage.Html">
      <a href="educator_homepage.php"><img src="<?= h($avatar) ?>" alt="User" class="avatar"></a>
    </a>
    <a href="index.html" class="logout-btn">Logout</a>
  </div>
</header>

<main class="container main">
  <h1><?php echo $headerText; ?></h1>
  <section class="card">
    <?php if (empty($comments)): ?>
        <p style="text-align: center; color: #6b7280;">No feedback has been submitted for this quiz yet.</p>
    <?php else: ?>
        <?php foreach ($comments as $c): ?>
    <div class="comment">
      <div class="meta">
          <span><?php echo htmlspecialchars($c['commentDate']); ?></span> 
          <span>Rating: <?php echo getStars($c['rating']); ?></span>
      </div>
      <div><?php echo htmlspecialchars($c['commentText']); ?></div>
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

<script>
    document.getElementById('year').textContent = new Date().getFullYear();
</script>

</body>
</html>