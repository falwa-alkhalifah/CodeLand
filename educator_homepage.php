<?php
// ================== Auth (ضعه في أعلى الصفحة دائمًا) ==================
session_start();

if (!isset($_SESSION['user_id'])) { 
  header("Location: login.php"); 
  exit();
}

// توجيه إن لم يكن Educator
if (basename($_SERVER['PHP_SELF']) === 'educator_homepage.php' && (($_SESSION['user_type'] ?? '') !== 'educator')) {
  header("Location: LearnerHomePage.php");
  exit();
}
// ======================================================================

// فعّلي السطرين التاليين مؤقتًا لو أردتِ رؤية تفاصيل أي خطأ 500 أثناء التطوير
// ini_set('display_errors', 1);
// error_reporting(E_ALL);

// ======== الاتصال بقاعدة البيانات ========
// استخدمي اتصال PDO عبر db.php (المتغير $pdo)
// أو استبدلي السطر التالي بملف اتصالك (مثلاً db_config.php) وعدّلي الاستعلامات إن كنتِ تستخدمين mysqli
require_once 'db_config.php'; // يوفر $pdo (PDO)

$educatorId = (int)$_SESSION['user_id'];

// فلتر XSS بسيط
function h($s){ return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }

/* ========== مراجعة الأسئلة الموصى بها (POST من نفس الصفحة) ========== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['rq_id'], $_POST['action'])) {
  $rqId     = (int)($_POST['rq_id'] ?? 0);
  $quizId   = (int)($_POST['quiz_id'] ?? 0);
  $action   = $_POST['action'] ?? '';
  $comments = trim($_POST['comments'] ?? '');

  if ($rqId > 0 && $quizId > 0 && in_array($action, ['approved','disapproved'], true)) {
    // تأكيد أن السؤال يتبع لهذا المعلّم
    $chk = $pdo->prepare("
      SELECT rq.*, q.educatorID
      FROM RecommendedQuestion rq
      JOIN Quiz q ON q.id = rq.quizID
      WHERE rq.id = ?
    ");
    $chk->execute([$rqId]);
    $row = $chk->fetch(PDO::FETCH_ASSOC);

    if ($row && (int)$row['educatorID'] === $educatorId) {
      // تحديث الحالة والتعليق
      $upd = $pdo->prepare("UPDATE RecommendedQuestion SET status=?, comments=? WHERE id=?");
      $upd->execute([$action, $comments, $rqId]);

      // عند الموافقة → أضف السؤال لجدول QuizQuestion
      if ($action === 'approved') {
        $ins = $pdo->prepare("
          INSERT INTO QuizQuestion (quizID, question, questionFigureFileName, answerA, answerB, answerC, answerD, correctAnswer)
          VALUES (?,?,?,?,?,?,?,?)
        ");
        $ins->execute([
          $row['quizID'],
          $row['question'],
          $row['questionFigureFileName'],
          $row['answerA'],
          $row['answerB'],
          $row['answerC'],
          $row['answerD'],
          $row['correctAnswer']
        ]);
      }
      $message = "✅ Review processed successfully.";
    } else {
      $message = "⚠️ Invalid request.";
    }
  } else {
    $message = "⚠️ Invalid form submission.";
  }
}
/* ==================================================================== */

// ======== معلومات المعلّم ========
$userStmt = $pdo->prepare("SELECT firstName,lastName,emailAddress,photoFileName FROM User WHERE id=?");
$userStmt->execute([$educatorId]);
$me = $userStmt->fetch(PDO::FETCH_ASSOC);

// ======== الكويزات الخاصة بالمعلّم + اسم الموضوع ========
$qzStmt = $pdo->prepare("
  SELECT q.id AS quiz_id, t.topicName
  FROM Quiz q 
  JOIN Topic t ON t.id = q.topicID
  WHERE q.educatorID = ?
  ORDER BY q.id DESC
");
$qzStmt->execute([$educatorId]);
$quizzes = $qzStmt->fetchAll(PDO::FETCH_ASSOC);

// ======== إحصائيات لكل كويز ========
$qCount = $pdo->prepare("SELECT COUNT(*) FROM QuizQuestion WHERE quizID=?");
$tkStat = $pdo->prepare("SELECT COUNT(*) c, AVG(score) a FROM TakenQuiz WHERE quizID=?");
$fbStat = $pdo->prepare("SELECT COUNT(*) c, AVG(rating) a FROM QuizFeedback WHERE quizID=?");
$stats  = [];

foreach ($quizzes as $q) {
  $qid = (int)$q['quiz_id'];

  $qCount->execute([$qid]);
  $numQ = (int)$qCount->fetchColumn();

  $tkStat->execute([$qid]);
  $tk = $tkStat->fetch(PDO::FETCH_ASSOC);
  $takenText = ((int)$tk['c'] > 0) ? ($tk['c']." attempts, avg ".round((float)$tk['a'],1)) : "quiz not taken yet";

  $fbStat->execute([$qid]);
  $fb = $fbStat->fetch(PDO::FETCH_ASSOC);
  $fbText = ((int)$fb['c'] > 0) ? ("avg ".round((float)$fb['a'],1)." ★") : "no feedback yet";

  $stats[$qid] = ['num'=>$numQ,'taken'=>$takenText,'fb'=>$fbText];
}

// ======== الأسئلة الموصى بها (Pending) ========
$rec = $pdo->prepare("
  SELECT rq.id AS rq_id, rq.quizID, rq.question, rq.questionFigureFileName,
         rq.answerA, rq.answerB, rq.answerC, rq.answerD, rq.correctAnswer,
         u.firstName AS learnerFirst, u.lastName AS learnerLast, t.topicName
  FROM RecommendedQuestion rq
  JOIN Quiz  q ON q.id = rq.quizID
  JOIN Topic t ON t.id = q.topicID
  JOIN User  u ON u.id = rq.learnerID
  WHERE q.educatorID = ? AND rq.status = 'pending'
  ORDER BY rq.id DESC
");
$rec->execute([$educatorId]);
$pending = $rec->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html> 
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Codeland • Educator</title>
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
    <a href="Educator.php">
      <img src="<?= 'uploads/users/'.h($me['photoFileName'] ?: 'default.png') ?>" alt="User" class="avatar">
    </a>
    <a href="signout.php" class="logout-btn">Logout</a>
  </div>
</header>

<!-- ====== MAIN CONTENT ====== -->
<div class="container">
  <div class="header header-user"></div>
  <div id="toast" class="toast"></div>

  <?php if (!empty($message)): ?>
    <div style="background:#e7ffe7;border:1px solid #b7ebb7;padding:10px;border-radius:8px;margin:12px 0;">
      <?= h($message) ?>
    </div>
  <?php endif; ?>

  <!-- Welcome -->
  <section id="welcome">
    <h1>Welcome back, <?= h($me['firstName'].' '.$me['lastName']) ?></h1>
  </section>

  <!-- Educator info -->
  <section class="card" id="educator-info">
    <p><strong>Name:</strong> <?= h($me['firstName'].' '.$me['lastName']) ?></p>
    <p><strong>Email:</strong> <?= h($me['emailAddress']) ?></p>
  </section>

  <!-- Quizzes table -->
  <section class="card">
    <h2>Your Quizzes</h2>
    <table class="table" id="quizzes-table">
      <thead>
        <tr>
          <th>Topic</th>
          <th>#Questions</th>
          <th>Stats</th>
          <th>Feedback</th>
        </tr>
      </thead>
      <tbody>
      <?php if (!$quizzes): ?>
        <tr><td colspan="4">You have no quizzes yet.</td></tr>
      <?php else: foreach ($quizzes as $q): $qid=(int)$q['quiz_id']; ?>
        <tr>
          <td><a href="quiz.php?quiz_id=<?= $qid ?>"><?= h($q['topicName']) ?></a></td>
          <td><?= $stats[$qid]['num'] ?></td>
          <td><?= h($stats[$qid]['taken']) ?></td>
          <td>
            <?= h($stats[$qid]['fb']) ?>
            <?php if ($stats[$qid]['fb'] !== 'no feedback yet'): ?>
              &nbsp;|&nbsp;<a href="comments.php?quiz_id=<?= $qid ?>">comments</a>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </section>

  <!-- Recommended questions -->
  <section class="card">
    <h2>Recommended Questions</h2>
    <table class="table" id="recommended-table">
      <thead>
        <tr>
          <th>Topic</th>
          <th>Learner</th>
          <th>Question</th>
          <th>Review</th>
        </tr>
      </thead>
      <tbody>
      <?php if (!$pending): ?>
        <tr><td colspan="4">No pending recommendations.</td></tr>
      <?php else: foreach ($pending as $p): ?>
        <tr>
          <td><?= h($p['topicName']) ?> (Quiz #<?= (int)$p['quizID'] ?>)</td>
          <td><?= h($p['learnerFirst'].' '.$p['learnerLast']) ?></td>
          <td>
            <b><?= h($p['question']) ?></b><br>
            <?php if (!empty($p['questionFigureFileName'])): ?>
              <div style="margin:8px 0;">
                <img src="uploads/questions/<?= h($p['questionFigureFileName']) ?>" width="120" alt="">
              </div>
            <?php endif; ?>
            A) <?= h($p['answerA']) ?><br>
            B) <?= h($p['answerB']) ?><br>
            C) <?= h($p['answerC']) ?><br>
            D) <?= h($p['answerD']) ?><br>
            <em>Correct:</em> <?= h($p['correctAnswer']) ?>
          </td>
          <td>
            <form method="post" style="display:flex;flex-direction:column;gap:6px;">
              <input type="hidden" name="rq_id" value="<?= (int)$p['rq_id'] ?>">
              <input type="hidden" name="quiz_id" value="<?= (int)$p['quizID'] ?>">
              <textarea name="comments" rows="2" placeholder="Add comments..."></textarea>
              <div style="display:flex;gap:6px;">
                <button name="action" value="approved">Approve</button>
                <button name="action" value="disapproved">Disapprove</button>
              </div>
            </form>
          </td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </section>
</div>

<!-- ====== FOOTER ====== -->
<footer class="cl-footer">
  <p>OUR VISION</p>
  <p>At CodeLand, we make coding education simple, engaging, and accessible for everyone</p>
  <p>© 2025 Website. All rights reserved.</p>
  <div class="social">
    <a href="#"><img src="images/xpng.jpg" alt="Twitter"></a>
    <a href="#"><img src="images/facebook.png" alt="Facebook"></a>
    <a href="#"><img src="images/instagram.png" alt="Instagram"></a>
  </div>
</footer>
</body>
</html>
