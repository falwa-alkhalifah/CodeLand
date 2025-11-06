<?php
// ===== Auth guard =====

$conn = new mysqli('localhost','root','root','database'); // غيّري اسم القاعدة
if ($conn->connect_error) { die('DB error: '.$conn->connect_error); }
$conn->set_charset('utf8mb4');

session_start();

// خلال التشخيص فقط؛ احذفيه بعد ما يشتغل
ini_set('display_errors',1); error_reporting(E_ALL);

if (empty($_SESSION['user_id'])) { header("Location: login.php"); exit; }
if (($_SESSION['user_type'] ?? '') !== 'educator') { header("Location: LearnerHomePage.php"); exit; }

require_once 'db_config.php'; // يجب أن يعرّف $conn (mysqli)
if (!isset($conn) || !($conn instanceof mysqli)) { die('DB connection ($conn) not found.'); }

$educatorId = (int)$_SESSION['user_id'];

function h($s){ return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }



/* ================== معالجة مراجعة الأسئلة الموصى بها ================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['rq_id'], $_POST['action'])) {
  $rqId     = (int)($_POST['rq_id'] ?? 0);
  $quizId   = (int)($_POST['quiz_id'] ?? 0);
  $action   = $_POST['action'] ?? '';
  $comments = trim($_POST['comments'] ?? '');

  if ($rqId > 0 && $quizId > 0 && in_array($action, ['approved','disapproved'], true)) {
    // تأكد أن هذا السؤال يتبع كويز يملكه نفس المعلّم
    $sql = "SELECT rq.*, q.educatorID FROM RecommendedQuestion rq JOIN Quiz q ON q.id=rq.quizID WHERE rq.id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $rqId);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();
    $stmt->close();

    if ($row && (int)$row['educatorID'] === $educatorId) {
      // تحديث الحالة + التعليق
      $sql = "UPDATE RecommendedQuestion SET status=?, comments=? WHERE id=?";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param("ssi", $action, $comments, $rqId);
      $stmt->execute();
      $stmt->close();

      // لو موافق → انسخ للسؤال الفعلي
      if ($action === 'approved') {
        $sql = "INSERT INTO QuizQuestion
                  (quizID, question, questionFigureFileName, answerA, answerB, answerC, answerD, correctAnswer)
                VALUES (?,?,?,?,?,?,?,?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param(
          "isssssss",
          $row['quizID'],
          $row['question'],
          $row['questionFigureFileName'],
          $row['answerA'],
          $row['answerB'],
          $row['answerC'],
          $row['answerD'],
          $row['correctAnswer']
        );
        $stmt->execute();
        $stmt->close();
      }
      $message = "✅ Review processed successfully.";
    } else {
      $message = "⚠️ Invalid request.";
    }
  } else {
    $message = "⚠️ Invalid form submission.";
  }
}
/* ====================================================================== */

/* ================== معلومات المعلّم ================== */
$sql = "SELECT firstName,lastName,emailAddress,photoFileName FROM User WHERE id=?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $educatorId);
$stmt->execute();
$me = $stmt->get_result()->fetch_assoc();
$stmt->close();

/* ================== الكويزات + اسم الموضوع ================== */
$sql = "SELECT q.id AS quiz_id, t.topicName
        FROM Quiz q JOIN Topic t ON t.id=q.topicID
        WHERE q.educatorID=?
        ORDER BY q.id DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $educatorId);
$stmt->execute();
$qres = $stmt->get_result();
$quizzes = [];
while ($r = $qres->fetch_assoc()) { $quizzes[] = $r; }
$stmt->close();

/* ================== الإحصائيات ================== */
$stats = [];
if ($quizzes) {
  $sqlCount = $conn->prepare("SELECT COUNT(*) AS c FROM QuizQuestion WHERE quizID=?");
  $sqlTaken = $conn->prepare("SELECT COUNT(*) AS c, AVG(score) AS a FROM TakenQuiz WHERE quizID=?");
  $sqlFb    = $conn->prepare("SELECT COUNT(*) AS c, AVG(rating) AS a FROM QuizFeedback WHERE quizID=?");

  foreach ($quizzes as $q) {
    $qid = (int)$q['quiz_id'];

    $sqlCount->bind_param("i", $qid);
    $sqlCount->execute();
    $numQ = $sqlCount->get_result()->fetch_assoc()['c'] ?? 0;

    $sqlTaken->bind_param("i", $qid);
    $sqlTaken->execute();
    $tk = $sqlTaken->get_result()->fetch_assoc();
    $takenText = ((int)($tk['c'] ?? 0) > 0) ? ($tk['c']." attempts, avg ".round((float)($tk['a'] ?? 0),1)) : "quiz not taken yet";

    $sqlFb->bind_param("i", $qid);
    $sqlFb->execute();
    $fb = $sqlFb->get_result()->fetch_assoc();
    $fbText = ((int)($fb['c'] ?? 0) > 0) ? ("avg ".round((float)($fb['a'] ?? 0),1)." ★") : "no feedback yet";

    $stats[$qid] = ['num'=>$numQ,'taken'=>$takenText,'fb'=>$fbText];
  }

  $sqlCount->close();
  $sqlTaken->close();
  $sqlFb->close();
}

/* ================== الأسئلة الموصى بها (pending) ================== */
$sql = "SELECT rq.id AS rq_id, rq.quizID, rq.question, rq.questionFigureFileName,
               rq.answerA, rq.answerB, rq.answerC, rq.answerD, rq.correctAnswer,
               u.firstName AS learnerFirst, u.lastName AS learnerLast, t.topicName
        FROM RecommendedQuestion rq
        JOIN Quiz q   ON q.id=rq.quizID
        JOIN Topic t  ON t.id=q.topicID
        JOIN User  u  ON u.id=rq.learnerID
        WHERE q.educatorID=? AND rq.status='pending'
        ORDER BY rq.id DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $educatorId);
$stmt->execute();
$pres = $stmt->get_result();
$pending = [];
while ($r = $pres->fetch_assoc()) { $pending[] = $r; }
$stmt->close();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
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
    <a href="educator_homepage.php">
      <img src="<?= 'uploads/users/'.h($me['photoFileName'] ?: 'default.png') ?>" alt="User" class="avatar">
    </a>
    <a href="logout.php" class="logout-btn">Logout</a>
  </div>
</header>

<div class="container">
  <?php if (!empty($message)): ?>
    <div style="background:#e7ffe7;border:1px solid #b7ebb7;padding:10px;border-radius:8px;margin:12px 0;">
      <?= h($message) ?>
    </div>
  <?php endif; ?>

  <section id="welcome">
    <h1>Welcome back, <?= h($me['firstName'].' '.$me['lastName']) ?></h1>
  </section>

  <section class="card" id="educator-info">
    <p><strong>Name:</strong> <?= h($me['firstName'].' '.$me['lastName']) ?></p>
    <p><strong>Email:</strong> <?= h($me['emailAddress']) ?></p>
  </section>

  <section class="card">
    <h2>Your Quizzes</h2>
    <table class="table">
      <thead><tr><th>Topic</th><th>#Questions</th><th>Stats</th><th>Feedback</th></tr></thead>
      <tbody>
      <?php if (!$quizzes): ?>
        <tr><td colspan="4">You have no quizzes yet.</td></tr>
      <?php else: foreach ($quizzes as $q): $qid=(int)$q['quiz_id']; ?>
        <tr>
          <td><a href="quiz.php?quiz_id=<?= $qid ?>"><?= h($q['topicName']) ?></a></td>
          <td><?= (int)$stats[$qid]['num'] ?></td>
          <td><?= h($stats[$qid]['taken']) ?></td>
          <td>
            <?= h($stats[$qid]['fb']) ?>
            <?php if ($stats[$qid]['fb'] !== 'no feedback yet'): ?>
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
          <td><?= h($p['topicName']) ?> (Quiz #<?= (int)$p['quizID'] ?>)</td>
          <td><?= h($p['learnerFirst'].' '.$p['learnerLast']) ?></td>
          <td>
            <b><?= h($p['question']) ?></b><br>
            <?php if (!empty($p['questionFigureFileName'])): ?>
              <img src="uploads/questions/<?= h($p['questionFigureFileName']) ?>" width="120" alt=""><br>
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
