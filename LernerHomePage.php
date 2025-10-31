<?php 
session_start();

// Connect to the database
$connect = mysqli_connect("localhost", "root", "root", "database");
if(mysqli_connect_errno()) {
    echo "Failed to connect to MySQL: " . mysqli_connect_error();
    exit();
}
if($_SESSION['user_type'] != 'learner') {
    header("Location: login.html");
    exit();
}
// Fetch learner information
if(isset($_SESSION['user_id'])) {
$result = mysqli_query($connect, "SELECT * FROM user WHERE id = " . $_SESSION['user_id']);
$learner = mysqli_fetch_assoc($result);
}
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
      <a href="LernerHomePage.Html">
        <img src="images/educatorUser.jpeg" alt="User" class="avatar">
    </a>
    <a href="index.html" class="logout-btn">Logout</a>
  </div>
</header>

<main class="container main">
  <h1 style="margin:0 0 12px 0">Welcome, <span id="firstName"><?php echo $learner['firstName']; ?></span> 👋</h1>
  <p class="small">This is your learner dashboard. Browse quizzes, track your suggested questions, and keep learning.</p>

  <div class="grid">
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

    <!-- Right column -->
        <section class="card">
      <h2>Quick Actions</h2>
      <div class="row row-2">
        <a class="btn" href="Recomended Questions.php">+ Recommend a Question</a>
        <a class="btn btn-outline" href="Comments Page.php">View Quiz Comments</a>
      </div>
    </section>
  </div>
  <!-- form -->
<form action="LernerHomePage.php" method="POST">
  <section class="card" style="margin-top:16px">
    <div class="inline" style="justify-content:space-between">
      <h2>Available Quizzes</h2>
      <div class="filterbar">
        <select id="topicFilter" name="topic" class="select">
          <?php foreach ($topics as $topic) {
            echo "<option value='$topic'>$topic</option>";
          } ?>
        </select>
        <button class="btn" id="filterBtn" type="submit">Filter</button>
      </div>
    </div>

    <table class="table" id="quizzesTable">
      <thead>
        <tr><th>Topic</th><th>Educator</th><th># Questions</th><th>Take Quiz</th></tr>
      </thead>
      <tbody>
        <tr data-topic="databases">
          <td>HTML</td>
          <td class="inline"><div class="avatar"><img src="images/educatorUser.jpeg" alt=""></div> Dr. Amal</td>
          <td>12</td>
          <td><a class="btn" href="TakeQuiz.html">Take Quiz</a></td>
        </tr>
        <tr data-topic="webdev">
          <td>JAVA</td>
          <td class="inline"><div class="avatar"><img src="images/educatorUser.jpeg" alt=""></div> Eng. Noura</td>
          <td>0</td>
          <td class="muted">—</td>
        </tr>
        <tr data-topic="networks">
          <td>C++</td>
          <td class="inline"><div class="avatar"><img src="images/educatorUser.jpeg" alt=""></div> Dr. Reem</td>
          <td>8</td>
          <td><a class="btn" href="TakeQuiz.html">Take Quiz</a></td>
        </tr>
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
    <td>HTML</td>
    <td class="inline"><div class="avatar"><img src="images/educatorUser.jpeg" alt=""></div> Dr. Amal</td>
    <td>
      <img src="images/code.png" class="figure">
      <div><strong>Which HTML tag is used to insert an image?</strong></div>
      <ol style="margin:6px 0 0 18px">
        <li>&lt;picture&gt;</li>
        <li><span class="answer correct" style="font-weight:800;padding:2px 6px;border-radius:6px;">&lt;img&gt; (Correct)</span></li>
        <li>&lt;figure&gt;</li>
        <li>&lt;src&gt;</li>
      </ol>
    </td>
    <td><span class="status approved">Approved</span></td>
    <td>Nice! Added to HTML basics quiz.</td>
  </tr>

  <tr>
    <td>Java</td>
    <td class="inline"><div class="avatar"><img src="images/educatorUser.jpeg" alt=""></div> Eng. Noura</td>
    <td>
      <div><strong>Which keyword is used to create a subclass in Java?</strong></div>
      <ol style="margin:6px 0 0 18px">
        <li>super</li>
        <li>implements</li>
        <li><span class="answer correct" style="font-weight:800;padding:2px 6px;border-radius:6px">extends (Correct)</span></li>
        <li>inherit</li>
      </ol>
    </td>
    <td><span class="status pending">Pending</span></td>
    <td class="muted">—</td>
  </tr>

  <tr>
    <td>C++</td>
    <td class="inline"><div class="avatar"><img src="images/educatorUser.jpeg" alt=""></div> Dr. Reem</td>
    <td>
      <div><strong>Which symbol is used to indicate a pointer in C++?</strong></div>
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

// Dummy filter interaction
document.getElementById('filterBtn').addEventListener('click', function () {{
  const val = document.getElementById('topicFilter').value;
  const rows = document.querySelectorAll('#quizzesTable tbody tr');
  rows.forEach(r => {{
    if(val === 'all') r.style.display = '';
    else r.style.display = (r.dataset.topic === val) ? '' : 'none';
  }});
}});
</script>

</body>
</html>