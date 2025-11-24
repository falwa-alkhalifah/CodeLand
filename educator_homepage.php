<?php
// educator_homepage.php

session_start();
require_once 'db_config.php'; // يعرّف $conn

if (!isset($_SESSION['user_id']) || ($_SESSION['user_type'] ?? '') !== 'educator') {
    $_SESSION['login_error'] = "You must log in as an educator.";
    header("Location: login.php");
    exit;
}

$conn->set_charset('utf8mb4');

function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

$educatorId = (int)$_SESSION['user_id'];

/* ================== معلومات المعلّم ================== */
$me = ['firstName'=>'','lastName'=>'','emailAddress'=>'','photoFileName'=>''];
$st = $conn->prepare("SELECT firstName,lastName,emailAddress,photoFileName FROM user WHERE id=?");
$st->bind_param("i", $educatorId);
$st->execute();
$meRow = $st->get_result()->fetch_assoc();
$st->close();
if ($meRow) { $me = $meRow; }

/* صورة البروفايل */
$defaultAvatar = 'images/educatorUser.jpeg';
$stored = $me['photoFileName'] ?? '';
$avatar = $defaultAvatar;
if ($stored && is_file(__DIR__ . '/uploads/users/' . $stored)) {
    $avatar = 'uploads/users/' . $stored . '?v=' . @filemtime(__DIR__ . '/uploads/users/' . $stored);
}

/* ================== الكويزات + المواضيع ================== */
$quizzes = [];
$st = $conn->prepare(
    "SELECT q.id AS quizID, t.topicName
     FROM quiz q
     JOIN topic t ON t.id = q.topicID
     WHERE q.educatorID = ?
     ORDER BY q.id DESC"
);
$st->bind_param("i", $educatorId);
$st->execute();
$rs = $st->get_result();
while ($r = $rs->fetch_assoc()) { $quizzes[] = $r; }
$st->close();

/* Topic badges في كرت المعلّم */
$topicBadges = [];
foreach ($quizzes as $q) { $topicBadges[$q['topicName']] = true; }
$topicBadges = array_keys($topicBadges);

/* ================== إحصائيات لكل كويز ================== */
$stats = [];
if ($quizzes) {
    $q1 = $conn->prepare("SELECT COUNT(*) AS c FROM quizquestion WHERE quizID=?");
    $q2 = $conn->prepare("SELECT COUNT(*) AS c, AVG(score) AS a FROM takenquiz WHERE quizID=?");
    $q3 = $conn->prepare("SELECT COUNT(*) AS c, AVG(rating) AS a FROM quizfeedback WHERE quizID=?");

    foreach ($quizzes as $q) {
        $qid = (int)$q['quizID'];

        $q1->bind_param("i", $qid);
        $q1->execute();
        $row1 = $q1->get_result()->fetch_assoc();
        $numQ = (int)($row1['c'] ?? 0);

        $q2->bind_param("i", $qid);
        $q2->execute();
        $row2 = $q2->get_result()->fetch_assoc();
        $tkCount = (int)($row2['c'] ?? 0);
        $tkAvg   = (float)($row2['a'] ?? 0);
        $takenText = $tkCount
            ? ($tkCount . " taker(s) • avg " . round($tkAvg, 1) . "%")
            : "quiz not taken yet";

        $q3->bind_param("i", $qid);
        $q3->execute();
        $row3 = $q3->get_result()->fetch_assoc();
        $fbCount = (int)($row3['c'] ?? 0);
        $fbAvg   = (float)($row3['a'] ?? 0);
        $fbText = $fbCount
            ? ("★ " . round($fbAvg, 1))
            : "no feedback yet";

        $stats[$qid] = [
            'num'    => $numQ,
            'taken'  => $takenText,
            'fb'     => $fbText,
            'hasFb'  => (bool)$fbCount
        ];
    }

    $q1->close();
    $q2->close();
    $q3->close();
}

/* ================== التوصيات المعلّقة ================== */
$pending = [];
$st = $conn->prepare(
    "SELECT rq.id AS rq_id, rq.quizID, rq.question, rq.questionFigureFileName,
            rq.answerA, rq.answerB, rq.answerC, rq.answerD, rq.correctAnswer,
            u.firstName AS learnerFirst, u.lastName AS learnerLast,
            t.topicName
     FROM recommendedquestion rq
     JOIN quiz  q ON q.id = rq.quizID
     JOIN topic t ON t.id = q.topicID
     JOIN user  u ON u.id = rq.learnerID
     WHERE q.educatorID = ? AND rq.status = 'pending'
     ORDER BY rq.id DESC"
);
$st->bind_param("i", $educatorId);
$st->execute();
$rs = $st->get_result();
while ($r = $rs->fetch_assoc()) { $pending[] = $r; }
$st->close();
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
    <a href="educator_homepage.php">
      <img src="<?= h($avatar) ?>" alt="User" class="avatar">
    </a>
    <a href="logout.php" class="logout-btn">Logout</a>
  </div>
</header>

<div class="container">
  <div class="page-header header-user"></div>
  <div id="toast" class="toast" style="display:none"></div>

  <!-- Welcome / Hero -->
  <section class="card page-header" style="margin-bottom:16px;">
    <h1>Welcome back, <?= h($me['firstName'].' '.$me['lastName']) ?> 👋</h1>
    <p class="muted">Manage your quizzes and review learners’ recommendations.</p>
  </section>

  <!-- Profile summary -->
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
        <?php endif; ?>
      </div>
    </div>
  </section>

  <!-- Your Quizzes -->
  <section class="card">
    <h2>Your Quizzes</h2>
    <table class="table">
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
      <?php else: ?>
        <?php foreach ($quizzes as $q):
          $qid = (int)$q['quizID'];
          $s = $stats[$qid] ?? ['num'=>0,'taken'=>'quiz not taken yet','fb'=>'no feedback yet','hasFb'=>false];
        ?>
          <tr>
            <td>
              <a href="quiz.php?quizID=<?= $qid ?>">
                <?= h($q['topicName']) ?>
              </a>
            </td>
            <td><?= (int)$s['num'] ?></td>
            <td><?= h($s['taken']) ?></td>
            <td>
              <?= h($s['fb']) ?>
              <?php if ($s['hasFb']): ?>
                • <a href="comments.php?quizID=<?= $qid ?>">comments</a>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
      </tbody>
    </table>
  </section>

  <!-- Recommended Questions -->
  <section class="card" style="margin-top:16px;">
    <h2>Recommended Questions</h2>
    <table class="table">
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
      <?php else: ?>
        <?php foreach ($pending as $p): ?>
          <tr>
            <td>
              <?= h($p['topicName']) ?>
              <span class="muted">(Quiz #<?= (int)$p['quizID'] ?>)</span>
            </td>
            <td><?= h($p['learnerFirst'].' '.$p['learnerLast']) ?></td>
            <td>
              <div><strong><?= h($p['question']) ?></strong></div>
              <?php if (!empty($p['questionFigureFileName'])): ?>
                <div style="margin:6px 0;">
                  <img src="uploads/questions/<?= h($p['questionFigureFileName']) ?>"
                       alt=""
                       style="max-width:220px;border:1px solid #ddd;border-radius:8px;padding:4px;">
                </div>
              <?php endif; ?>
              <div>A) <?= h($p['answerA']) ?></div>
              <div>B) <?= h($p['answerB']) ?></div>
              <div>C) <?= h($p['answerC']) ?></div>
              <div>D) <?= h($p['answerD']) ?></div>
              <div class="muted"><em>Correct:</em> <?= h($p['correctAnswer']) ?></div>
            </td>
            <td>
              <form class="review-rq" method="post" action="review_recommendation_ajax.php" style="display:flex;flex-direction:column;gap:8px;">
                <input type="hidden" name="rq_id"  value="<?= (int)$p['rq_id'] ?>">
                <input type="hidden" name="quiz_id"value="<?= (int)$p['quizID'] ?>">
                <textarea name="comments" rows="2" class="input" placeholder="Write a note (optional)"></textarea>
                <div style="display:flex;gap:8px;">
                  <button type="submit" name="action" value="approved"   class="btn">Approve</button>
                  <button type="submit" name="action" value="disapproved" class="btn-outline">Disapprove</button>
                </div>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
      </tbody>
    </table>
  </section>
</div>

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

<!-- jQuery + AJAX لمراجعة التوصيات -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script>
$(function(){
  // نحدد أي زر تم الضغط عليه (Approve / Disapprove)
  $('form.review-rq button[type="submit"]').on('click', function(){
    const $btn = $(this);
    $btn.closest('form').find('button[type="submit"]').removeAttr('data-clicked');
    $btn.attr('data-clicked', 'true');
  });

  $('form.review-rq').on('submit', function(e){
    e.preventDefault();
    const $form = $(this);
    const rq_id   = $form.find('input[name="rq_id"]').val();
    const quiz_id = $form.find('input[name="quiz_id"]').val();
    const comments= $form.find('textarea[name="comments"]').val() || '';
    const $btn    = $form.find('button[type="submit"][data-clicked="true"]');
    const action  = $btn.val() || $btn.attr('value') || 'approved';

    $.post('review_recommendation_ajax.php',
      { rq_id: rq_id, quiz_id: quiz_id, action: action, comments: comments },
      function(resp){
        try { resp = JSON.parse(resp); } catch(e) {}
        const ok = (resp === true) || (resp && resp.ok === true) || (resp === 'true');
        if(ok){
          $form.closest('tr').remove();
          showToast('✅ Review processed successfully.');
        } else {
          showToast('⚠️ Could not process review.');
        }
      }
    ).fail(function(){
      showToast('⚠️ Network / server error.');
    });
  });

  function showToast(msg){
    const $t = $('#toast');
    $t.text(msg).fadeIn(150, function(){
      setTimeout(function(){ $t.fadeOut(200); }, 1500);
    });
  }
});
</script>
</body>
</html>
