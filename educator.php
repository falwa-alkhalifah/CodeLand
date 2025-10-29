<?php
// educator_home.php — النسخة النهائية
session_start();
require_once 'db.php';

// ✅ تحقق من الجلسة
if (!isset($_SESSION['user_id'])) {
  header('Location: login.php?err=login_required'); exit;
}
if (($_SESSION['user_type'] ?? '') !== 'educator') {
  header('Location: login.php?err=not_educator'); exit;
}
$educatorId = (int)$_SESSION['user_id'];

/* ------------------- 🧾 معالجة مراجعة الأسئلة الموصى بها ------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['rq_id'], $_POST['action'])) {
  $rqId = (int)$_POST['rq_id'];
  $quizId = (int)$_POST['quiz_id'];
  $action = $_POST['action'];
  $comments = trim($_POST['comments'] ?? '');

  if (in_array($action, ['approved','disapproved'], true)) {
    // جلب السؤال الموصى به للتأكد أنه يخص هذا المعلّم
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

      // إذا تمت الموافقة -> نضيف السؤال إلى QuizQuestion
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
  }
}
/* ------------------------------------------------------------------------- */

// ✅ معلومات المعلّم
$stmt = $pdo->prepare("SELECT firstName,lastName,emailAddress,photoFileName FROM User WHERE id=?");
$stmt->execute([$educatorId]);
$me = $stmt->fetch(PDO::FETCH_ASSOC);

// ✅ الكويزات + الإحصائيات
$qzStmt = $pdo->prepare("
  SELECT q.id AS quiz_id, t.topicName
  FROM Quiz q JOIN Topic t ON t.id = q.topicID
  WHERE q.educatorID = ?
");
$qzStmt->execute([$educatorId]);
$quizzes = $qzStmt->fetchAll(PDO::FETCH_ASSOC);

$qCount = $pdo->prepare("SELECT COUNT(*) FROM QuizQuestion WHERE quizID=?");
$tkStat = $pdo->prepare("SELECT COUNT(*) c, AVG(score) a FROM TakenQuiz WHERE quizID=?");
$fbStat = $pdo->prepare("SELECT COUNT(*) c, AVG(rating) a FROM QuizFeedback WHERE quizID=?");
$quizStats = [];

foreach ($quizzes as $q) {
  $qid = (int)$q['quiz_id'];
  $qCount->execute([$qid]);
  $numQ = (int)$qCount->fetchColumn();

  $tkStat->execute([$qid]);
  $tk = $tkStat->fetch(PDO::FETCH_ASSOC);
  $tkText = $tk['c'] > 0 ? ($tk['c']." attempts, avg ".round($tk['a'],1)) : "quiz not taken yet";

  $fbStat->execute([$qid]);
  $fb = $fbStat->fetch(PDO::FETCH_ASSOC);
  $fbText = $fb['c'] > 0 ? ("avg ".round($fb['a'],1)." ★") : "no feedback yet";

  $quizStats[$qid] = ['num'=>$numQ,'taken'=>$tkText,'fb'=>$fbText];
}

// ✅ الأسئلة الموصى بها (pending)
$rec = $pdo->prepare("
  SELECT rq.id AS rq_id, rq.quizID, rq.question, rq.questionFigureFileName,
         rq.answerA,rq.answerB,rq.answerC,rq.answerD,rq.correctAnswer,
         u.firstName AS learnerFirst, u.lastName AS learnerLast, t.topicName
  FROM RecommendedQuestion rq
  JOIN Quiz q ON q.id=rq.quizID
  JOIN Topic t ON t.id=q.topicID
  JOIN User u ON u.id=rq.learnerID
  WHERE q.educatorID=? AND rq.status='pending'
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
<header class="cl-header">
  <div class="brand">
    <img src="images/logo.png" alt="Logo">
    <span>Codeland</span>
  </div>
  <div class="actions">
    <img src="<?= 'uploads/users/'.htmlspecialchars($me['photoFileName'] ?: 'default.png') ?>" class="avatar">
    <a href="signout.php" class="logout-btn">Logout</a>
  </div>
</header>

<div class="container">
  <?php if (!empty($message)): ?>
    <div style="background:#e0ffe0;padding:10px;margin:10px 0;border-radius:5px;">
      <?= htmlspecialchars($message) ?>
    </div>
  <?php endif; ?>

  <section id="welcome">
    <h1>Welcome back, <?= htmlspecialchars($me['firstName'].' '.$me['lastName']) ?></h1>
  </section>

  <section class="card" id="educator-info">
    <p><strong>Email:</strong> <?= htmlspecialchars($me['emailAddress']) ?></p>
  </section>

  <section class="card">
    <h2>Your Quizzes</h2>
    <table class="table">
      <thead><tr><th>Topic</th><th>#Questions</th><th>Stats</th><th>Feedback</th></tr></thead>
      <tbody>
      <?php if (!$quizzes): ?>
        <tr><td colspan="4">No quizzes yet.</td></tr>
      <?php else: foreach ($quizzes as $q): $qid=(int)$q['quiz_id']; ?>
        <tr>
          <td><a href="quiz.php?quiz_id=<?= $qid ?>"><?= htmlspecialchars($q['topicName']) ?></a></td>
          <td><?= $quizStats[$qid]['num'] ?></td>
          <td><?= htmlspecialchars($quizStats[$qid]['taken']) ?></td>
          <td>
            <?= htmlspecialchars($quizStats[$qid]['fb']) ?>
            <?php if ($quizStats[$qid]['fb']!=='no feedback yet'): ?>
              | <a href="comments.php?quiz_id=<?= $qid ?>">comments</a>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </section>

  <section class="card">
    <h2>Recommended Questions</h2>
    <table class="table">
      <thead><tr><th>Topic</th><th>Learner</th><th>Question</th><th>Review</th></tr></thead>
      <tbody>
      <?php if (!$pending): ?>
        <tr><td colspan="4">No pending recommendations.</td></tr>
      <?php else: foreach ($pending as $p): ?>
        <tr>
          <td><?= htmlspecialchars($p['topicName']) ?> (Quiz #<?= $p['quizID'] ?>)</td>
          <td><?= htmlspecialchars($p['learnerFirst'].' '.$p['learnerLast']) ?></td>
          <td>
            <b><?= htmlspecialchars($p['question']) ?></b><br>
            <?php if ($p['questionFigureFileName']): ?>
              <img src="uploads/questions/<?= htmlspecialchars($p['questionFigureFileName']) ?>" width="100"><br>
            <?php endif; ?>
            A) <?= htmlspecialchars($p['answerA']) ?><br>
            B) <?= htmlspecialchars($p['answerB']) ?><br>
            C) <?= htmlspecialchars($p['answerC']) ?><br>
            D) <?= htmlspecialchars($p['answerD']) ?><br>
            <em>Correct:</em> <?= htmlspecialchars($p['correctAnswer']) ?>
          </td>
          <td>
            <form method="post" style="display:flex;flex-direction:column;gap:6px;">
              <input type="hidden" name="rq_id" value="<?= $p['rq_id'] ?>">
              <input type="hidden" name="quiz_id" value="<?= $p['quizID'] ?>">
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
