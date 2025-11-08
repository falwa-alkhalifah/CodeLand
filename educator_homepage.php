<?php
// ===== Guard & DB =====
session_start();
require_once 'db_config.php'; // يجب أن يعرّف $conn كـ mysqli متصل
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }
if (($_SESSION['user_type'] ?? '') !== 'educator') { header("Location: LearnerHomePage.php"); exit; }
$conn->set_charset('utf8mb4');

function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

// ===== معالجة مراجعة التوصيات (approve/disapprove) =====
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['rq_id'], $_POST['action'])) {
  $rqId     = (int)($_POST['rq_id'] ?? 0);
  $action   = $_POST['action'] ?? '';
  $comments = trim($_POST['comments'] ?? '');
  if ($rqId > 0 && in_array($action, ['approved','disapproved'], true)) {
    // اجلب التوصية + تحقق أن الكويز يعود لهذا المعلّم
    $sql = "SELECT rq.*, q.educatorID
            FROM RecommendedQuestion rq
            JOIN Quiz q ON q.id = rq.quizID
            WHERE rq.id = ?";
    $st = $conn->prepare($sql);
    $st->bind_param("i", $rqId);
    $st->execute();
    $rec = $st->get_result()->fetch_assoc();
    $st->close();

    if ($rec && (int)$rec['educatorID'] === (int)$_SESSION['user_id']) {
      // حدّث حالة التوصية
      $sql = "UPDATE RecommendedQuestion SET status=?, comments=? WHERE id=?";
      $st = $conn->prepare($sql);
      $st->bind_param("ssi", $action, $comments, $rqId);
      $st->execute();
      $st->close();

      // لو approved انسخيها لجدول QuizQuestion
      if ($action === 'approved') {
        $sql = "INSERT INTO QuizQuestion
                (quizID, question, questionFigureFileName, answerA, answerB, answerC, answerD, correctAnswer)
                VALUES (?,?,?,?,?,?,?,?)";
        $st = $conn->prepare($sql);
        $st->bind_param(
          "isssssss",
          $rec['quizID'],
          $rec['question'],
          $rec['questionFigureFileName'],
          $rec['answerA'], $rec['answerB'], $rec['answerC'], $rec['answerD'],
          $rec['correctAnswer']
        );
        $st->execute();
        $st->close();
      }
      header("Location: educator_homepage.php?review=ok");
      exit;
    } else {
      $message = "⚠️ Invalid request.";
    }
  } else {
    $message = "⚠️ Invalid form submission.";
  }
}

// ===== معلومات المعلّم =====
$educatorId = (int)$_SESSION['user_id'];
$me = ['firstName'=>'','lastName'=>'','emailAddress'=>'','photoFileName'=>''];
$st = $conn->prepare("SELECT firstName,lastName,emailAddress,photoFileName FROM User WHERE id=?");
$st->bind_param("i", $educatorId);
$st->execute();
$me = $st->get_result()->fetch_assoc() ?: $me;
$st->close();

// ===== الكويزات + المواضيع =====
$quizzes = [];
$st = $conn->prepare("SELECT q.id AS quizID, t.topicName
                      FROM Quiz q JOIN Topic t ON t.id=q.topicID
                      WHERE q.educatorID=?
                      ORDER BY q.id DESC");
$st->bind_param("i", $educatorId);
$st->execute();
$rs = $st->get_result();
while ($r = $rs->fetch_assoc()) { $quizzes[] = $r; }
$st->close();

// badges للمواضيع في الهيرو
$topicBadges = [];
foreach ($quizzes as $q) { $topicBadges[$q['topicName']] = true; }
$topicBadges = array_keys($topicBadges);

// ===== إحصائيات لكل كويز =====
$stats = [];
if ($quizzes) {
  $q1 = $conn->prepare("SELECT COUNT(*) AS c FROM QuizQuestion WHERE quizID=?");
  $q2 = $conn->prepare("SELECT COUNT(*) AS c, AVG(score) AS a FROM TakenQuiz WHERE quizID=?");
  $q3 = $conn->prepare("SELECT COUNT(*) AS c, AVG(rating) AS a FROM QuizFeedback WHERE quizID=?");
  foreach ($quizzes as $q) {
    $qid = (int)$q['quizID'];

    $q1->bind_param("i", $qid); $q1->execute();
    $numQ = (int)($q1->get_result()->fetch_assoc()['c'] ?? 0);

    $q2->bind_param("i", $qid); $q2->execute();
    $tk = $q2->get_result()->fetch_assoc();
    $takenText = ($tk['c'] ?? 0) ? ($tk['c']." taker(s) • avg ".round((float)$tk['a'],1)."%") : "quiz not taken yet";

    $q3->bind_param("i", $qid); $q3->execute();
    $fb = $q3->get_result()->fetch_assoc();
    $fbText = ($fb['c'] ?? 0) ? ("★ ".round((float)$fb['a'],1)) : "no feedback yet";

    $stats[$qid] = ['num'=>$numQ,'taken'=>$takenText,'fb'=>$fbText,'hasFb'=> (bool)($fb['c'] ?? 0)];
  }
  $q1->close(); $q2->close(); $q3->close();
}

// ===== التوصيات المعلّقة =====
$pending = [];
$st = $conn->prepare(
  "SELECT rq.id AS rq_id, rq.quizID, rq.question, rq.questionFigureFileName,
          rq.answerA, rq.answerB, rq.answerC, rq.answerD, rq.correctAnswer,
          u.firstName AS learnerFirst, u.lastName AS learnerLast, t.topicName
   FROM RecommendedQuestion rq
   JOIN Quiz  q ON q.id=rq.quizID
   JOIN Topic t ON t.id=q.topicID
   JOIN User  u ON u.id=rq.learnerID
   WHERE q.educatorID=? AND rq.status='pending'
   ORDER BY rq.id DESC"
);
$st->bind_param("i", $educatorId);
$st->execute();
$rs = $st->get_result();
while ($r = $rs->fetch_assoc()) { $pending[] = $r; }
$st->close();

// ===== صورة الهيدر =====
$defaultAvatar = 'images/educatorUser.jpeg';
$stored = $me['photoFileName'] ?? '';
$avatar = $defaultAvatar;
if ($stored && is_file(__DIR__ . '/uploads/users/' . $stored)) {
  $avatar = 'uploads/users/' . $stored . '?v=' . @filemtime(__DIR__ . '/uploads/users/' . $stored);
}
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

  <?php if (!empty($_GET['review'])): ?>
    <div class="toast" style="display:block;">✅ Review processed successfully.</div>
  <?php elseif (!empty($_GET['added'])): ?>
    <div class="toast" style="display:block;">✅ Question added successfully.</div>
  <?php elseif (!empty($_GET['updated'])): ?>
    <div class="toast" style="display:block;">✅ Question updated successfully.</div>
  <?php elseif (!empty($message)): ?>
    <div class="toast toast-error" style="display:block;"><?= h($message) ?></div>
  <?php endif; ?>

  <!-- ====== HERO / WELCOME ====== -->
  <section class="card page-header" style="margin-bottom:16px;">
    <h1>Welcome back, <?= h($me['firstName'].' '.$me['lastName']) ?> 👋</h1>
    <p class="muted">Manage your quizzes and review learners’ recommendations.</p>
  </section>

  <!-- ====== Profile Summary ====== -->
  <section class="card" style="margin-bottom:16px;">
    <div style="display:flex;align-items:center;gap:14px;">
      <img src="<?= h($avatar) ?>" alt="User" class="avatar" style="width:48px;height:48px;">
      <div>
        <div><strong><?= h($me['firstName'].' '.$me['lastName']) ?></strong></div>
        <div class="muted"><?= h($me['emailAddress']) ?></div>
        <?php if ($topicBadges): ?>
          <div style="margin-top:8px;display:flex;gap:8px;flex-wrap:wrap;">
            <?php foreach ($topicBadges as $t): ?>
              <span class="tag"><?= h($t) ?></span>
            <?php endforeach; ?>
          </div>
        <a href="add_quiz.php" class="btn">+ Create Quiz</a>

        <?php endif; ?>
      </div>
    </div>
  </section>

  <!-- ====== Your Quizzes ====== -->
  <section class="card">
    <h2>Your Quizzes</h2>
    <table class="table">
      <thead>
        <tr><th>Topic</th><th>#Questions</th><th>Stats</th><th>Feedback</th></tr>
      </thead>
      <tbody>
      <?php if (!$quizzes): ?>
        <tr><td colspan="4">You have no quizzes yet.</td></tr>
      <?php else: foreach ($quizzes as $q):
        $qid = (int)$q['quizID']; $s = $stats[$qid] ?? ['num'=>0,'taken'=>'','fb'=>'no feedback yet','hasFb'=>false];
      ?>
        <tr>
          <td><a href="quiz.php?quizID=<?= $qid ?>"><?= h($q['topicName']) ?></a></td>
          <td><?= (int)$s['num'] ?></td>
          <td><?= h($s['taken']) ?></td>
          <td>
            <?= h($s['fb']) ?>
            <?php if ($s['hasFb']): ?> • <a href="comments.php?quizID=<?= $qid ?>">comments</a><?php endif; ?>
          </td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </section>

  <!-- ====== Recommended Questions ====== -->
  <section class="card" style="margin-top:16px;">
    <h2>Recommended Questions</h2>
    <table class="table">
      <thead><tr><th>Topic</th><th>Learner</th><th>Question</th><th>Review</th></tr></thead>
      <tbody>
      <?php if (!$pending): ?>
        <tr><td colspan="4">No pending recommendations.</td></tr>
      <?php else: foreach ($pending as $p): ?>
        <tr>
          <td><?= h($p['topicName']) ?> <span class="muted">(Quiz #<?= (int)$p['quizID'] ?>)</span></td>
          <td><?= h($p['learnerFirst'].' '.$p['learnerLast']) ?></td>
          <td>
            <div><strong><?= h($p['question']) ?></strong></div>
            <?php if (!empty($p['questionFigureFileName'])): ?>
              <div style="margin:6px 0;"><img src="uploads/questions/<?= h($p['questionFigureFileName']) ?>" alt="" style="max-width:220px;border:1px solid #ddd;border-radius:8px;padding:4px;"></div>
            <?php endif; ?>
            <div>A) <?= h($p['answerA']) ?></div>
            <div>B) <?= h($p['answerB']) ?></div>
            <div>C) <?= h($p['answerC']) ?></div>
            <div>D) <?= h($p['answerD']) ?></div>
            <div class="muted"><em>Correct:</em> <?= h($p['correctAnswer']) ?></div>
          </td>
          <td>
            <form method="post" style="display:flex;flex-direction:column;gap:8px;">
              <input type="hidden" name="rq_id" value="<?= (int)$p['rq_id'] ?>">
              <textarea name="comments" rows="2" placeholder="Write a note (optional)" class="input"></textarea>
              <div style="display:flex;gap:8px;">
                <button class="btn"   name="action" value="approved">Approve</button>
                <button class="btn-outline" name="action" value="disapproved">Disapprove</button>
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
  <p>© <span id="year"></span>2025 Website. All rights reserved.</p>
  <div class="social">
    <a href="#"><img src="images/xpng.jpg" alt="Twitter"></a>
    <a href="#"><img src="images/facebook.png" alt="Facebook"></a>
    <a href="#"><img src="images/instagram.png" alt="Instagram"></a>
  </div>
</footer>
</body>
</html>
