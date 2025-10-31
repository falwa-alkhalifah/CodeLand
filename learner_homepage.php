<?php 
session_start();
require_once 'db_config.php'; 

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'learner') {
    header("Location: login.php");
    exit();
}


$result = mysqli_query($conn, "SELECT * FROM User WHERE id = " . $_SESSION['user_id']);
$learner = mysqli_fetch_assoc($result);


$topics_result = mysqli_query($conn, "SELECT id, topicName FROM Topic ORDER BY topicName");

$quiz_query = "SELECT Q.id, Q.quizName, T.topicName, U.firstName, U.lastName 
               FROM Quiz Q
               JOIN Topic T ON Q.topicID = T.id
               JOIN User U ON Q.educatorID = U.id";

$where_clause = "";
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['topicID']) && $_POST['topicID'] != 'all') {
    $topicID = mysqli_real_escape_string($conn, $_POST['topicID']);
    $where_clause = " WHERE Q.topicID = '$topicID'";
}

$quiz_query .= $where_clause . " ORDER BY Q.id DESC";
$quizzes_result = mysqli_query($conn, $quiz_query);

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
<header class="cl-header">
  <div class="brand">
    <img src="images/logo.png" alt="Logo">
    <span>Codeland</span>
  </div>

  <div class="actions">
      <a href="learner_homepage.php">
        <img src="uploads/<?php echo htmlspecialchars($learner['photoFileName'] ?? 'default_user.png'); ?>" alt="User" class="avatar">
    </a>
    <a href="logout.php" class="logout-btn">Logout</a>
  </div>
</header>

<main class="container main">
  <h1 style="margin:0 0 12px 0">Welcome, <span id="firstName"><?php echo htmlspecialchars($learner['firstName'] ?? 'Learner'); ?></span> 👋</h1>
  <p class="small-text" style="color:#666;margin:0 0 20px 0"><?php echo htmlspecialchars($learner['emailAddress'] ?? 'No email'); ?></p>

  <section class="card" style="margin-top:20px">
    <h2>Available Quizzes</h2>
    
    <form method="POST" action="learner_homepage.php" class="filterbar">
        <select id="topicFilter" class="select" name="topicID">
          <option value="all">All Topics</option>
          <?php while ($topic = mysqli_fetch_assoc($topics_result)): ?>
            <option value="<?php echo htmlspecialchars($topic['id']); ?>" 
                    <?php echo (isset($_POST['topicID']) && $_POST['topicID'] == $topic['id']) ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($topic['topicName']); ?>
            </option>
          <?php endwhile; mysqli_free_result($topics_result); ?>
        </select>
        <button type="submit" id="filterBtn" class="btn">Filter</button>
    </form>

    <table id="quizzesTable" class="table">
      <thead>
        <tr>
          <th>Quiz Name</th>
          <th>Topic</th>
          <th>Educator</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($quizzes_result && mysqli_num_rows($quizzes_result) > 0): ?>
            <?php while ($quiz = mysqli_fetch_assoc($quizzes_result)): ?>
            <tr>
                <td><?php echo htmlspecialchars($quiz['quizName'] ?? 'Quiz ID: ' . $quiz['id']); ?></td>
                <td><?php echo htmlspecialchars($quiz['topicName']); ?></td>
                <td><?php echo htmlspecialchars($quiz['firstName'] . ' ' . $quiz['lastName']); ?></td>
                <td>
                    <a href="quiz_form.php?quizID=<?php echo htmlspecialchars($quiz['id']); ?>" class="btn">Take Quiz</a>
                </td>
            </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="4" style="text-align: center;">No quizzes found for the selected filter.</td></tr>
        <?php endif; ?>
        <?php if ($quizzes_result) mysqli_free_result($quizzes_result); ?>

      </tbody>
    </table>
  </section>
  
  <section class="card" style="margin-top:20px">
    <h2>Recommended Questions Feedback</h2>
    <table class="table">
      <thead>
        <tr>
          <th>Question</th>
          <th>Status</th>
          <th>Educator Feedback</th>
        </tr>
      </thead>
      <tbody>
        <tr>
            <td>
                <div><strong>How do you create a pointer in C++?</strong></div>
                <ol style="margin:6px 0 0 18px">
                    <li>&amp;</li>
                    <li><span class="answer wrong" style="font-weight:800;padding:2px 6px;border-radius:6px">@ (Wrong)</span></li>
                    <li>*</li>
                    <li>%</li>
                </ol>
            </td>
            <td><span class="status disapproved">Disapproved</span></td>
            <td>Correct answer should be <span class="kbd">*</span>.</td>
        </tr>
      </tbody>
    </table>
    <div class="right" style="margin-top:10px">
      <a class="btn" href="Recomended Questions.html">Recommend another question</a>
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

<?php 
mysqli_close($conn); 
?>