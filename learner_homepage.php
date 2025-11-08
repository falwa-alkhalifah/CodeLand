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

if(!isset($_SESSION['user_type']) || $_SESSION['user_type'] != 'learner' || !isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit();
}

$learnerID = intval($_SESSION['user_id']);
$selectedTopic = 'all'; // Default filter value
$availableQuizzes = [];
$recommendedQuestions = [];
$learner = null;

$stmt = mysqli_prepare($connect, "SELECT * FROM user WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $learnerID);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$learner = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$learner) {
    session_unset();
    session_destroy();
    header("Location: index.html"); 
    exit();
}

$topicsResult = mysqli_query($connect, "SELECT DISTINCT topicName FROM topic ORDER BY topicName");
$topics = mysqli_fetch_all($topicsResult, MYSQLI_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['topicFilter']) && $_POST['topicFilter'] != 'all') {
    $selectedTopic = $_POST['topicFilter'];
}

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

$whereClause = "";
$paramTypes = "";
$params = [];

if ($selectedTopic != 'all') {
    $whereClause = " WHERE t.topicName = ?";
    $paramTypes = "s";
    $params[] = $selectedTopic;
}

$query .= $whereClause . "
    GROUP BY q.id, t.topicName, educatorName, educatorPhoto
    ORDER BY t.topicName, educatorName;
";

$stmt = mysqli_prepare($connect, $query);
if ($paramTypes) {
    mysqli_stmt_bind_param($stmt, $paramTypes, ...$params);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$availableQuizzes = mysqli_fetch_all($result, MYSQLI_ASSOC);
mysqli_stmt_close($stmt);

$query = "
    SELECT 
        t.topicName,
        CONCAT(u.firstName, ' ', u.lastName) AS educatorName,
        u.photoFileName AS educatorPhoto,
        rq.question,
        rq.questionFigureFileName,
        rq.answerA,
        rq.answerB,
        rq.answerC,
        rq.answerD,
        rq.correctAnswer, 
        rq.status,
        rq.comments
    FROM recommendedquestion rq
    LEFT JOIN quiz q ON rq.quizID = q.id
    LEFT JOIN topic t ON q.topicID = t.id
    LEFT JOIN user u ON q.educatorID = u.id
    WHERE rq.learnerID = ?
    ORDER BY rq.id DESC
";
$stmt = mysqli_prepare($connect, $query);
mysqli_stmt_bind_param($stmt, "i", $learnerID);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$recommendedQuestions = mysqli_fetch_all($result, MYSQLI_ASSOC);
mysqli_stmt_close($stmt);

function renderAnswerList($answers, $correct) {
    $html = "<ol style='margin:6px 0 0 18px'>";
    foreach ($answers as $key => $text) {
        $style = ($key === $correct) ? "color:green; font-weight:bold;" : "";
        $html .= "<li style='$style'>" . htmlspecialchars($text) . "</li>";
    }
    $html .= "</ol>";
    return $html;
}

mysqli_close($connect);

?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Learner Home Page</title>
  <link rel="stylesheet" href="lernerStyle.css">
  <link rel="stylesheet" href="HF.css">
  
  <style>
    .status.pending { background-color: #fef3c7; color: #b45309; padding: 4px 8px; border-radius: 4px; }
    .status.approved { background-color: #d1fae5; color: #065f46; padding: 4px 8px; border-radius: 4px; }
    .status.disapproved { background-color: #fee2e2; color: #991b1b; padding: 4px 8px; border-radius: 4px; }
    .figure { max-width: 100px; height: auto; margin-bottom: 5px; border-radius: 4px; }
  </style>

</head>

<body>

<header class="cl-header">
  <div class="brand">
    <img src="images/logo.png" alt="Logo">
    <span>Codeland</span>
  </div>
  <div class="actions">
      <a href="learner_homepage.php">
        <img src="images/<?php echo htmlspecialchars($learner['photoFileName']); ?>" alt="Profile" class="avatar">
    </a>
    <a href="index.html" class="logout-btn">Logout</a>
  </div>
</header>

<main class="container main">
  <h1 style="margin:0 0 12px 0">Welcome, <span id="firstName"><?php echo htmlspecialchars($learner['firstName']); ?></span> 👋</h1>
  <p class="small">This is your learner dashboard. Browse quizzes, track your suggested questions, and keep learning.</p>
    <section class="card">
      <h2>Your Info</h2>
      <div class="user">
        <img src="images/<?php echo htmlspecialchars($learner['photoFileName']); ?>" alt="Profile" class="avatar">
        <div>
          <div><strong id="fullName"><?php echo htmlspecialchars($learner['firstName'] . " " . $learner['lastName']); ?></strong></div>
          <div class="muted"><?php echo htmlspecialchars($learner['emailAddress']); ?></div>
        </div>
      </div>
    </section>
<form action="learner_homePage.php" method="POST">
  <section class="card" style="margin-top:16px">
    <div class="inline" style="justify-content:space-between">
      <h2>Available Quizzes</h2>
      <div class="filterbar">
        <select id="topicFilter" class="select" name="topicFilter">
          <option value="all" <?php if ($selectedTopic == 'all') echo 'selected'; ?>>All Topics</option>
          <?php foreach($topics as $t): ?>
            <option value="<?php echo htmlspecialchars($t['topicName']); ?>" 
                  <?php if ($selectedTopic == $t['topicName']) echo 'selected'; ?>>
                <?php echo htmlspecialchars($t['topicName']); ?>
            </option>
          <?php endforeach; ?>
        </select>
        <button class="btn" id="filterBtn" type="submit">Filter</button>
      </div>
    </div>
    <table class="table" id="quizzesTable">
      <thead>
  <tr><th>Topic</th><th>Educator</th><th># Questions</th><th>Take Quiz</th></tr>
</thead>
<tbody>
  <?php foreach ($availableQuizzes as $quiz): 
      $photo = $quiz['educatorPhoto']; 
      $questionCount = intval($quiz['numberOfQuestions']);
  ?>
    <tr>
      <td><?php echo htmlspecialchars($quiz['topicName']); ?></td>
      <td class='inline'>
          <img src='images/<?php echo htmlspecialchars($photo); ?>' alt='<?php echo htmlspecialchars($quiz['educatorName']); ?> Profile Photo' class="avatar">
          <?php echo htmlspecialchars($quiz['educatorName']); ?>
      </td>
      <td><?php echo $questionCount; ?></td>
      <td>
        <?php if ($questionCount > 0): ?>
          <a class='btn' href='TakeQuiz.php?quizID=<?php echo htmlspecialchars($quiz['quizID']); ?>'>Take Quiz</a>
        <?php else: ?>
          <span class='muted'>—</span>
        <?php endif; ?>
      </td>
    </tr>
  <?php endforeach; ?>
</tbody>
    </table>
  </section>
</form>
  <section class="card" style="margin-top:16px">
    <h2>Your Recommended Questions</h2>
    <table class="table">
      <thead>
        <tr><th>Topic</th><th>Educator</th><th>Question Details</th><th>Status</th><th>Educator Comments</th></tr>
      </thead>
      <tbody>
    <?php foreach($recommendedQuestions as $row): 
      $photo = $row['educatorPhoto']; 
      $statusClass = strtolower($row['status']); 
      $answers = [
          'A' => $row['answerA'], 'B' => $row['answerB'],
          'C' => $row['answerC'], 'D' => $row['answerD']
      ];
    ?>
    <tr>
      <td><?php echo htmlspecialchars($row['topicName']); ?></td>
      <td class='inline'><img src='images/<?php echo htmlspecialchars($photo); ?>' alt='<?php echo htmlspecialchars($row['educatorName']); ?> Profile Photo' class="avatar"> <?php echo htmlspecialchars($row['educatorName']); ?></td>
      <td>
        <?php if(!empty($row['questionFigureFileName'])): ?>
            <img src='images/<?php echo htmlspecialchars($row['questionFigureFileName']); ?>' class='figure' alt='Question Figure'>
        <?php endif; ?>
        <div><strong><?php echo htmlspecialchars($row['question']); ?></strong></div>
        <?php echo renderAnswerList($answers, $row['correctAnswer']); ?>
      </td>
      <td><span class='status <?php echo $statusClass; ?>'><?php echo htmlspecialchars(ucfirst($row['status'])); ?></span></td>
      <td><?php echo (!empty($row['comments']) ? htmlspecialchars($row['comments']) : '—'); ?></td>
    </tr>
    <?php endforeach; ?>
</tbody>
    </table>
    <div class="right" style="margin-top:10px">
      <a class="btn" href="recommend_question.php">Recommend another question</a>
    </div>
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