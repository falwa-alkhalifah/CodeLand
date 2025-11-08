<?php 
session_start();

// Connect to the database
$connect = mysqli_connect("localhost", "root", "root", "database");
if(mysqli_connect_errno()) {
    echo "Failed to connect to MySQL: " . mysqli_connect_error();
    exit();
}
// 1- Check if user is logged in and is a learner
if($_SESSION['user_type'] != 'learner') {
    header("Location: login.html");
    exit();
}
// 2- Checks the user’s id
// Fetch learner information
if(isset($_SESSION['user_id'])) {
$result = mysqli_query($connect, "SELECT * FROM user WHERE id = " . $_SESSION['user_id']);
$learner = mysqli_fetch_assoc($result);
}
// 3- Fetch distinct topics from the database
$topics = mysqli_query($connect, "SELECT DISTINCT topicName FROM topic");
$topicsArray = [];
while($row = mysqli_fetch_assoc($topics)) {
    $topicsArray[] = $row['topicName'];
}
$topics = $topicsArray;


?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Home page</title>
  <link rel="stylesheet" href="lernerStyle.css">
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
        <div class="avatar"><img src="<?php echo $learner['photoFileName']; ?>" alt="Profile"></div>
    </a>
    <a href="index.html" class="logout-btn">Logout</a>
  </div>
</header>

<main class="container main">
  <h1 style="margin:0 0 12px 0">Welcome, <span id="firstName"><?php echo $learner['firstName']; ?></span> 👋</h1>
  <p class="small">This is your learner dashboard. Browse quizzes, track your suggested questions, and keep learning.</p>

   <!-- Left column -->
    <section class="card">
      <h2>Your Info</h2>
      <div class="user">
        <div class="avatar"><img src="<?php echo $learner['photoFileName']; ?>" alt="Profile"></div>
        <div>
          <div><strong id="fullName"><?php echo $learner['firstName']." ".$learner['lastName']; ?></strong></div>
          <div class="muted"><?php echo $learner['emailAddress']; ?></div>
        </div>
      </div>
    </section>
  <!-- form -->
<form action="learner_homePage.php" method="POST">
  <section class="card" style="margin-top:16px">
    <div class="inline" style="justify-content:space-between">
      <h2>Available Quizzes</h2>
      <div class="filterbar">
        <!-- 3- Displays a form for filtering quizzes by topic -->
        <select id="topicFilter" class="select" name="topicFilter">
          <option value="all">All Topics</option>
          <?php foreach($topics as $topic) 
            echo "<option value='$topic'>$topic</option>";
            ?>
        </select>
        <button class="btn" id="filterBtn" type="submit">Filter</button>
      </div>
    </div>

    <table class="table" id="quizzesTable">
      <thead>
  <tr><th>Topic</th><th>Educator</th><th># Questions</th><th>Take Quiz</th></tr>
</thead>
<tbody>
  <?php
// Base query
$query = "
SELECT 
  q.id AS quizID,
  t.topicName,
  CONCAT(u.firstName, ' ', u.lastName) AS educatorName,
  u.photoFileName AS educatorPhoto,
  COUNT(qq.id) AS numberOfQuestions
FROM quiz q
LEFT JOIN topic t ON q.topicID = t.id
LEFT JOIN user u ON q.educatorID = u.id
LEFT JOIN quizquestion qq ON q.id = qq.quizID
";

// If the request is POST, filter by the selected topic
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['topicFilter']) && $_POST['topicFilter'] != 'all') {
    $topic = mysqli_real_escape_string($connect, $_POST['topicFilter']);
    $query .= " WHERE t.topicName = '$topic'";
}

$query .= "
GROUP BY q.id, t.topicName, educatorName, educatorPhoto
ORDER BY q.id;
";

$result = mysqli_query($connect, $query);

// Display rows
while ($quiz = mysqli_fetch_assoc($result)) {
    echo "<tr>";
    echo "<td>" . htmlspecialchars($quiz['topicName']) . "</td>";

    // Educator info
    $photo = !empty($quiz['educatorPhoto']) ? $quiz['educatorPhoto'] : 'educatorUser.jpeg';
    echo "<td class='inline'><div class='avatar'><img src='images/" . htmlspecialchars($photo) . "' alt=''></div> " . htmlspecialchars($quiz['educatorName']) . "</td>";

    // Number of questions
    echo "<td>" . intval($quiz['numberOfQuestions']) . "</td>";

    // Take quiz link
    if ($quiz['numberOfQuestions'] > 0) {
        echo "<td><a class='btn' href='TakeQuiz.php?quizID=" . $quiz['quizID'] . "'>Take Quiz</a></td>";
    } else {
        echo "<td class='muted'>—</td>";
    }

    echo "</tr>";
}
?>
</tbody>

    </table>
  </section>
</form>

  <section class="card" style="margin-top:16px">
    <h2>Your Recommended Questions</h2>
    <table class="table">
      <thead>
        <tr><th>Topic</th><th>Educator</th><th>Question</th><th>Status</th><th>Educator Comments</th></tr>
      </thead>
      <tbody>
  <tr>
    <?php
    $learnerID = intval($_SESSION['user_id']);

$query = "
SELECT 
    rq.id AS recQuestionID,
    t.topicName,
    CONCAT(u.firstName, ' ', u.lastName) AS educatorName,
    u.photoFileName AS educatorPhoto,
    rq.question,
    rq.questionFigureFileName,
    rq.answerA,
    rq.answerB,
    rq.answerC,
    rq.answerD,
    rq.correctAnswer,      -- <--- add this line
    rq.status,
    rq.comments
FROM recommendedquestion rq
LEFT JOIN quiz q ON rq.quizID = q.id
LEFT JOIN topic t ON q.topicID = t.id
LEFT JOIN user u ON q.educatorID = u.id
WHERE rq.learnerID = $learnerID
ORDER BY rq.id DESC
";


$result = mysqli_query($connect, $query);
while($row = mysqli_fetch_assoc($result)) {
    echo "<tr>";
    
    // Topic
    echo "<td>" . htmlspecialchars($row['topicName']) . "</td>";
    
    // Educator with photo
    $photo = !empty($row['educatorPhoto']) ? $row['educatorPhoto'] : 'educatorUser.jpeg';
    echo "<td class='inline'><div class='avatar'><img src='images/" . htmlspecialchars($photo) . "' alt=''></div> " . htmlspecialchars($row['educatorName']) . "</td>";
    

    // Question text and optional figure
    echo "<td>";
    if(!empty($row['questionFigureFileName'])) {
        echo "<img src='images/" . htmlspecialchars($row['questionFigureFileName']) . "' class='figure' alt='Question Figure'>";
    }
    echo "<div><strong>" . htmlspecialchars($row['question']) . "</strong></div>";
    
        $correct = $row['correctAnswer']; //error

    // Prepare answers
    $answers = [
        'A' => $row['answerA'],
        'B' => $row['answerB'],
        'C' => $row['answerC'],
        'D' => $row['answerD']
    ];

    echo "<ol style='margin:6px 0 0 18px'>";
    foreach ($answers as $key => $text) {
        // Highlight correct answer in green and bold
        $style = ($key === $correct) ? "color:green; font-weight:bold;" : "";
        echo "<li style='$style'>" . htmlspecialchars($text) . "</li>";
    }
    echo "</ol>";

    echo "</td>";
    
    // Status
    $statusClass = strtolower($row['status']); // pending, approved, disapproved
    echo "<td><span class='status " . htmlspecialchars($statusClass) . "'>" . htmlspecialchars(ucfirst($row['status'])) . "</span></td>";
    
    // Educator comments
    echo "<td>" . (!empty($row['comments']) ? htmlspecialchars($row['comments']) : '—') . "</td>";
    
    echo "</tr>";
}

?>

</tbody>

    </table>
    <div class="right" style="margin-top:10px">
      <a class="btn" href="Recomended Questions.html">Recommend another question</a>
    </div>
  </section>
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


</body>
</html> 