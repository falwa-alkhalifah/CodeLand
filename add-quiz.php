<?php
// add_question.php — عرض فورم إضافة سؤال

session_start();
require_once 'db_config.php';
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }
if (($_SESSION['user_type'] ?? '') !== 'educator') { header("Location: LearnerHomePage.php"); exit; }
$conn->set_charset('utf8mb4');

function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

$educatorId = (int)$_SESSION['user_id'];

// يدعم ?quizID= أو ?quiz_id=
$quizID = filter_input(INPUT_GET, 'quizID', FILTER_VALIDATE_INT);
if (!$quizID) { $quizID = filter_input(INPUT_GET, 'quiz_id', FILTER_VALIDATE_INT); }
if (!$quizID) { header("Location: educator_homepage.php?error=missingQuizId"); exit; }

// تأكيد الملكية + جلب اسم التوبك
$sql = "SELECT q.id, t.topicName 
        FROM quiz q JOIN topic t ON t.id=q.topicID 
        WHERE q.id=? AND q.educatorID=?";
$st = $conn->prepare($sql);
$st->bind_param("ii", $quizID, $educatorId);
$st->execute();
$quiz = $st->get_result()->fetch_assoc();
$st->close();
if (!$quiz) { header("Location: educator_homepage.php?error=notOwner"); exit; }

// صورة الهيدر (اختياري)
$pf   = $_SESSION['photoFileName'] ?? '';
$avatar = 'images/educatorUser.jpeg';
foreach (['uploads/users/','uploads/'] as $root) {
  $abs = __DIR__.'/'.$root.$pf;
  if ($pf && is_file($abs)) { $avatar = $root.$pf.'?v='.@filemtime($abs); break; }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Codeland • Add Question</title>
  <link rel="stylesheet" href="styleDeema.css">
  <link rel="stylesheet" href="HF.css">
</head>
<body>
<header class="cl-header">
  <div class="brand"><img src="images/logo.png" alt=""><span>Codeland</span></div>
  <div class="actions">
    <a href="educator_homepage.php"><img src="<?= h($avatar) ?>" class="avatar" alt=""></a>
    <a href="logout.php" class="logout-btn">Logout</a>
  </div>
</header>

<div class="container">
  <div class="page-header header-user"></div>

  <section class="card page-header" style="display:flex;justify-content:space-between;align-items:center;">
    <div>
      <h1>Add Question — <?= h($quiz['topicName']) ?></h1>
      <p class="muted">Quiz #<?= (int)$quizID ?></p>
    </div>
    <a href="quiz.php?quizID=<?= (int)$quizID ?>" class="btn-outline">Cancel</a>
  </section>

  <section class="card">
    <!-- يرسل إلى صفحة المعالجة -->
    <form method="post" action="add_question_process.php" enctype="multipart/form-data">
      <!-- ✅ hidden المطلوب حسب المعايير -->
      <input type="hidden" name="quizID"  value="<?= (int)$quizID ?>">
      <!-- ✅ نسخة بديلة بالاسم الثاني لضمان مطابقة المصحّح -->
      <input type="hidden" name="quiz_id" value="<?= (int)$quizID ?>">

      <label>Question Text</label>
      <textarea name="text" rows="3" class="input" required></textarea>

      <label>Choice A</label><input name="c0" class="input" required>
      <label>Choice B</label><input name="c1" class="input" required>
      <label>Choice C</label><input name="c2" class="input" required>
      <label>Choice D</label><input name="c3" class="input" required>

      <label>Correct Choice</label>
      <select name="correctIndex" class="input" required>
        <option value="0">A</option>
        <option value="1">B</option>
        <option value="2">C</option>
        <option value="3">D</option>
      </select>

      <label>Figure (optional)</label>
      <input type="file" name="figure" accept="image/*" class="input">

      <hr class="sep">
      <div class="flex">
        <button class="btn">Save</button>
        <span class="helper"></span>
      </div>
    </form>
  </section>
</div>

<footer class="cl-footer">
  <p>OUR VISION</p>
  <p>At CodeLand, we make coding education simple, engaging, and accessible for everyone</p>
  <p>© <span id="year"></span>2025 Website. All rights reserved.</p>
</footer>
</body>
</html>
