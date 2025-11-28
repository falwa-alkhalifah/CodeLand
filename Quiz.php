<?php
session_start();
require_once 'db_config.php';

function getUserPhoto($fileName) {
    $defaultAvatar = 'images/default_avatar.jpeg';
    if (empty($fileName)) return $defaultAvatar;

    $baseDir = __DIR__;
    $uploadDir = $baseDir . '/uploads/users/';
    $uploadRel = 'uploads/users/';

    if (is_file($uploadDir . $fileName)) {
        return $uploadRel . htmlspecialchars($fileName) . '?v=' . @filemtime($uploadDir . $fileName);
    }

    if (is_file($baseDir . '/images/' . $fileName)) {
        return 'images/' . htmlspecialchars($fileName);
    }

    return $defaultAvatar;
}


if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }
if (($_SESSION['user_type'] ?? '') !== 'educator') { header("Location: LearnerHomePage.php"); exit; }
$conn->set_charset('utf8mb4');

function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

/* ========= Helper to get correct image path ========= */
function getImagePath($fileName) {
    if (empty($fileName)) return '';

    $baseDir = __DIR__;

    // Question figure folders
    $questionDirAbs  = $baseDir . '/uploads/questions/';
    $questionDirRel  = 'uploads/questions/';

    // Old/alternative folder
    $figureDirAbs = $baseDir . '/uploads/figures/';
    $figureDirRel = 'uploads/figures/';

    // Image folder
    $imagesDirAbs = $baseDir . '/images/';

    // 1️⃣ Check uploads/questions/
    if (is_file($questionDirAbs . $fileName)) {
        return $questionDirRel . h($fileName) . '?v=' . @filemtime($questionDirAbs . $fileName);
    }

    // 2️⃣ Check uploads/figures/
    if (is_file($figureDirAbs . $fileName)) {
        return $figureDirRel . h($fileName) . '?v=' . @filemtime($figureDirAbs . $fileName);
    }

    // 3️⃣ Check images/
    if (is_file($imagesDirAbs . $fileName)) {
        return 'images/' . h($fileName);
    }

    // Not found
    return '';
}


/* ========= 1) quizID ========= */
$quizID = filter_input(INPUT_GET, 'quizID', FILTER_VALIDATE_INT);
if (!$quizID) { $quizID = filter_input(INPUT_GET, 'quiz_id', FILTER_VALIDATE_INT); }
if (!$quizID) { header("Location: educator_homepage.php?error=missingQuizId"); exit; }

/* ========= 2) تحقق وجود/ملكية الكويز ========= */
$educatorId = (int)$_SESSION['user_id'];

$existsStmt = $conn->prepare("SELECT id, educatorID, topicID FROM quiz WHERE id=?");
$existsStmt->bind_param("i", $quizID);
$existsStmt->execute();
$quizRow = $existsStmt->get_result()->fetch_assoc();
$existsStmt->close();

if (!$quizRow) { header("Location: educator_homepage.php?error=quizNotFound"); exit; }
if ((int)$quizRow['educatorID'] !== $educatorId) { header("Location: educator_homepage.php?error=notOwner"); exit; }

/* ========= 3) اسم التوبك ========= */
$tStmt = $conn->prepare("SELECT topicName FROM topic WHERE id=?");
$tStmt->bind_param("i", $quizRow['topicID']);
$tStmt->execute();
$tRow = $tStmt->get_result()->fetch_assoc();
$tStmt->close();
$topicName = $tRow['topicName'] ?? 'Untitled';

/* ========= 4) صورة البروفايل ========= */
$educatorId = (int)$_SESSION['user_id'];

$stmt = $conn->prepare("SELECT photoFileName FROM user WHERE id=?");
$stmt->bind_param("i", $educatorId);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

$avatar = getUserPhoto($row['photoFileName'] ?? '');


/* ========= 5) جلب الأسئلة ========= */
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

/* ========= 6) فلاش رسائل ========= */
$added   = !empty($_GET['added']);
$updated = !empty($_GET['updated']);
$deleted = !empty($_GET['deleted']);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Codeland • Quiz (<?= h($topicName) ?>)</title>
  <link rel="stylesheet" href="H-style.css">
  <link rel="stylesheet" href="HF.css">
</head>
<body>

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
  <?php if ($added):   ?><div class="toast" style="display:block">✅ Question added.</div><?php endif; ?>
  <?php if ($updated): ?><div class="toast" style="display:block">✅ Question updated.</div><?php endif; ?>
  <?php if ($deleted): ?><div class="toast" style="display:block">✅ Question deleted.</div><?php endif; ?>

  <section class="card page-header" style="display:flex;justify-content:space-between;align-items:center;gap:12px;">
    <div>
      <h1><?= h($topicName) ?> — Quiz</h1>
    </div>
    <div style="display:flex;gap:8px;flex-wrap:wrap;">
      <a class="btn" href="add-question.php?quizID=<?= (int)$quizID ?>">+ Add Question</a>
      <a class="btn-outline" href="educator_homepage.php">Back</a>
    </div>
  </section>

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
              <?php if (!empty($q['questionFigureFileName'])): 
                $figPath = getImagePath($q['questionFigureFileName']);
                if ($figPath): ?>
                  <div style="margin-top:6px">
                    <img src="<?= $figPath ?>" alt="Question Figure"
                         style="max-width:100%;height:auto;border:1px solid #ddd;border-radius:8px;padding:4px;">
                  </div>
                <?php endif;
              endif; ?>
            </td>

            <td>
              <div class="choice-item <?= ($q['correctAnswer'] === 'A') ? 'correct-choice' : '' ?>">A) <?= h($q['answerA']) ?></div>
              <div class="choice-item <?= ($q['correctAnswer'] === 'B') ? 'correct-choice' : '' ?>">B) <?= h($q['answerB']) ?></div>
              <div class="choice-item <?= ($q['correctAnswer'] === 'C') ? 'correct-choice' : '' ?>">C) <?= h($q['answerC']) ?></div>
              <div class="choice-item <?= ($q['correctAnswer'] === 'D') ? 'correct-choice' : '' ?>">D) <?= h($q['answerD']) ?></div>
            </td>

            <td>
              <a class="btn btn-edit" href="edit-question.php?questionID=<?= (int)$q['id'] ?>&quizID=<?= (int)$quizID ?>">Edit</a>

              <!-- ✅ New delete link that calls separate PHP page -->
              <a class="btn btn-delete"
                 href="delete-question.php?questionID=<?= (int)$q['id'] ?>&quizID=<?= (int)$quizID ?>"
                 onclick="return confirm('Are you sure you want to delete this question?');">
                 Delete
              </a>
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
