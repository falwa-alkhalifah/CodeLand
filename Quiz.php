<?php
// quiz.php — عرض وإدارة أسئلة الكويز للمعلم (سكيمة lowercase) + حذف مباشر بـ POST

session_start();
require_once 'db_config.php';
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }
if (($_SESSION['user_type'] ?? '') !== 'educator') { header("Location: LearnerHomePage.php"); exit; }
$conn->set_charset('utf8mb4');

function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

/* ========= 1) قراءة باراميتر الكويز + fallback ========= */
$quizID = filter_input(INPUT_GET, 'quizID', FILTER_VALIDATE_INT);
if (!$quizID) { $quizID = filter_input(INPUT_GET, 'quiz_id', FILTER_VALIDATE_INT); }
if (!$quizID) { header("Location: educator_homepage.php?error=missingQuizId"); exit; }

/* ========= 2) تحقق وجود الكويز وملكيته ========= */
$educatorId = (int)$_SESSION['user_id'];

// الكويز موجود؟
$existsStmt = $conn->prepare("SELECT id, educatorID, topicID FROM quiz WHERE id=?");
$existsStmt->bind_param("i", $quizID);
$existsStmt->execute();
$quizRow = $existsStmt->get_result()->fetch_assoc();
$existsStmt->close();

if (!$quizRow) { header("Location: educator_homepage.php?error=quizNotFound"); exit; }

// مملوك لنفس المعلّم؟
if ((int)$quizRow['educatorID'] !== $educatorId) {
  header("Location: educator_homepage.php?error=notOwner"); exit;
}

// جلب اسم التوبك
$tStmt = $conn->prepare("SELECT topicName FROM topic WHERE id=?");
$tStmt->bind_param("i", $quizRow['topicID']);
$tStmt->execute();
$tRow = $tStmt->get_result()->fetch_assoc();
$tStmt->close();

$topicName = $tRow['topicName'] ?? 'Untitled';

/* ========= 3) حذف سؤال (POST) ========= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_question_id'])) {
  $deleteID = (int)$_POST['delete_question_id'];

  // تأكيد أن السؤال يتبع هذا الكويز المملوك لك + جلب اسم الصورة (إن وجدت)
  $imgQ = $conn->prepare(
    "SELECT qq.questionFigureFileName
     FROM quizquestion qq
     JOIN quiz q ON q.id = qq.quizID
     WHERE qq.id=? AND qq.quizID=? AND q.educatorID=?"
  );
  $imgQ->bind_param("iii", $deleteID, $quizID, $educatorId);
  $imgQ->execute();
  $imgR = $imgQ->get_result()->fetch_assoc();
  $imgQ->close();

  if ($imgR) {
    // احذف السجل
    $del = $conn->prepare("DELETE FROM quizquestion WHERE id=? AND quizID=?");
    $del->bind_param("ii", $deleteID, $quizID);
    $ok = $del->execute();
    $del->close();

    // احذف الصورة من السيرفر إن وجدت
    if ($ok && !empty($imgR['questionFigureFileName'])) {
      $path = __DIR__ . '/uploads/questions/' . $imgR['questionFigureFileName'];
      if (is_file($path)) { @unlink($path); }
    }
  }

  // رجّع مع Toast نجاح
  header("Location: quiz.php?quizID={$quizID}&deleted=1");
  exit;
}

/* ========= 4) صورة البروفايل (من السيشن/الداتابيس) ========= */
$pfRow = $conn->prepare("SELECT photoFileName FROM user WHERE id=?");
$pfRow->bind_param("i", $educatorId);
$pfRow->execute();
$pfRes = $pfRow->get_result()->fetch_assoc();
$pfRow->close();

$storedName = $_SESSION['photoFileName'] ?? ($pfRes['photoFileName'] ?? '');
$avatar = 'images/educatorUser.jpeg';
foreach (['uploads/users/','uploads/'] as $root) {
  $abs = __DIR__ . '/' . $root . $storedName;
  if ($storedName && is_file($abs)) { $avatar = $root.$storedName.'?v='.@filemtime($abs); break; }
}

/* ========= 5) جلب أسئلة الكويز ========= */
$questions = [];
$st = $conn->prepare(
  "SELECT id, question, questionFigureFileName, answerA, answerB, answerC, answerD, correctAnswer
   FROM quizquestion WHERE quizID=? ORDER BY id DESC"
);
$st->bind_param("i", $quizID);
$st->execute();
$rs = $st->get_result();
while ($r = $rs->fetch_assoc()) { $questions[] = $r; }
$st->close();

/* ========= 6) فلاش رسائل النجاح ========= */
$added   = !empty($_GET['added']);
$updated = !empty($_GET['updated']);
$deleted = !empty($_GET['deleted']);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Codeland • Quiz (<?= h($topicName) ?>)</title>
  <link rel="stylesheet" href="styleDeema.css">
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
    <a href="educator_homepage.php"><img src="<?= h($avatar) ?>" alt="User" class="avatar"></a>
    <a href="logout.php" class="logout-btn">Logout</a>
  </div>
</header>

<div class="container">
  <div class="page-header header-user"></div>

  <?php if ($added): ?>
    <div class="toast" style="display:block">✅ Question added.</div>
  <?php elseif ($updated): ?>
    <div class="toast" style="display:block">✅ Question updated.</div>
  <?php elseif ($deleted): ?>
    <div class="toast" style="display:block">✅ Question deleted.</div>
  <?php endif; ?>

  <!-- ====== Header Section ====== -->
  <section class="card page-header" style="display:flex;justify-content:space-between;align-items:center;gap:12px;">
    <div>
      <h1><?= h($topicName) ?> — Quiz</h1>
      <p class="muted">Manage questions for Quiz #<?= (int)$quizID ?>.</p>
    </div>
    <div style="display:flex;gap:8px;flex-wrap:wrap;">
      <a class="btn" href="add-question.php?quizID=<?= (int)$quizID ?>">+ Add Question</a>
      <a class="btn-outline" href="educator_homepage.php">Back</a>
    </div>
  </section>

  <!-- ====== Questions Table ====== -->
  <?php if (!$questions): ?>
    <section class="card"><p>No questions yet.</p></section>
  <?php else: ?>
    <section class="card">
      <table class="table">
        <thead>
          <tr>
            <th style="width:40px">#</th>
            <th>Question</th>
            <th>Choices</th>
            <th style="width:220px">Actions</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($questions as $i => $q): ?>
          <tr>
            <td><?= $i+1 ?></td>
            <td>
              <div><strong><?= h($q['question']) ?></strong></div>
              <?php if (!empty($q['questionFigureFileName'])): ?>
                <div style="margin-top:6px">
                  <img src="uploads/questions/<?= h($q['questionFigureFileName']) ?>"
                       alt="" style="max-width:220px;border:1px solid #ddd;border-radius:8px;padding:4px;">
                </div>
              <?php endif; ?>
            </td>
            <td>
              <div>A) <?= h($q['answerA']) ?></div>
              <div>B) <?= h($q['answerB']) ?></div>
              <div>C) <?= h($q['answerC']) ?></div>
              <div>D) <?= h($q['answerD']) ?></div>
              <div class="muted" style="margin-top:4px"><em>Correct:</em> <?= h($q['correctAnswer']) ?></div>
            </td>
            <td>
              <a class="btn-outline" href="edit-question.php?questionID=<?= (int)$q['id'] ?>&quizID=<?= (int)$quizID ?>">Edit</a>

              <!-- حذف مباشر بـ POST في نفس الصفحة -->
              <form action="" method="post" style="display:inline;">
                <input type="hidden" name="delete_question_id" value="<?= (int)$q['id'] ?>">
                <button class="btn-outline" onclick="return confirm('Delete this question?');">Delete</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </section>
  <?php endif; ?>
</div>

<footer class="cl-footer">
  <p>OUR VISION</p>
  <p>At CodeLand, we make coding education simple, engaging, and accessible for everyone</p>
  <p>© <span id="year"></span>2025 Website. All rights reserved.</p>
  <div class="social">
    <a href="#"><img src="images/xpng.jpg" alt="Twitter"></a>
    <a href="#"><img src="images/instagram.png" alt="Instagram"></a>
  </div>
</footer>
</body>
</html>
