<?php
// edit_question.php
session_start();
require_once 'db_config.php'; // يجب أن يعرّف $conn (mysqli)

//
// 0) حماية الصفحة + السماح فقط للمدرّس
//
if (!isset($_SESSION['userType']) || $_SESSION['userType'] !== 'educator') {
    header("Location: login.php?error=notAllowed");
    exit();
}

$errors = [];
$question = null;   // بيانات السؤال الحالية
$quizID = null;     // سنحتاجها للرجوع لصفحة الكويز
$questionID = null;

//
// 1) GET: جلب questionID، قراءة بيانات السؤال وملء النموذج
//
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $questionID = filter_input(INPUT_GET, 'questionID', FILTER_VALIDATE_INT);
    if (!$questionID) {
        header("Location: educator.php?error=missingQuestionID");
        exit();
    }

    $sql = "SELECT id, quizID, question, questionFigureFileName, answerA, answerB, answerC, answerD, correctAnswer
            FROM QuizQuestion WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        die("DB error: " . mysqli_error($conn));
    }
    mysqli_stmt_bind_param($stmt, "i", $questionID);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    if (!$res || mysqli_num_rows($res) === 0) {
        header("Location: educator.php?error=questionNotFound");
        exit();
    }
    $question = mysqli_fetch_assoc($res);
    $quizID = (int)$question['quizID'];
}

//
// 2) POST: تحديث السؤال (مع استبدال الصورة إن وُجدت)
//
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $questionID = filter_input(INPUT_POST, 'questionID', FILTER_VALIDATE_INT);
    $quizID     = filter_input(INPUT_POST, 'quizID', FILTER_VALIDATE_INT);

    $text = trim($_POST['text'] ?? '');
    $c0   = trim($_POST['c0'] ?? '');
    $c1   = trim($_POST['c1'] ?? '');
    $c2   = trim($_POST['c2'] ?? '');
    $c3   = trim($_POST['c3'] ?? '');
    $correctIndex = filter_input(INPUT_POST, 'correctIndex', FILTER_VALIDATE_INT);
    $oldFigureName = $_POST['oldFigure'] ?? null; // الاسم القديم من الـ DB

    if (!$questionID || !$quizID)             $errors[] = "Invalid request.";
    if ($text === '')                         $errors[] = "Question text is required.";
    if ($c0 === '' || $c1 === '' || $c2 === '' || $c3 === '') $errors[] = "All choices (A-D) are required.";
    if (!in_array($correctIndex, [0,1,2,3], true)) $errors[] = "Correct choice is required.";

    $letters = ['A','B','C','D'];
    $correctAnswer = $letters[$correctIndex ?? 0] ?? 'A';

    // صورة جديدة؟
    $newFigureName = $oldFigureName; // الافتراضي: احتفظ بالقديمة
    if (isset($_FILES['figure']) && $_FILES['figure']['error'] !== UPLOAD_ERR_NO_FILE) {
        if ($_FILES['figure']['error'] === UPLOAD_ERR_OK) {
            $allowed = ['jpg','jpeg','png','gif','webp'];
            $orig = $_FILES['figure']['name'];
            $ext  = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
            if (!in_array($ext, $allowed, true)) {
                $errors[] = "Invalid image type. Allowed: jpg, jpeg, png, gif, webp.";
            } else {
                $targetDir = __DIR__ . '/uploads/questions/';
                if (!is_dir($targetDir)) {
                    @mkdir($targetDir, 0775, true);
                }
                try {
                    $rand = bin2hex(random_bytes(3));
                } catch (Exception $e) {
                    $rand = mt_rand(100000, 999999);
                }
                $fileBase = "q_{$quizID}_" . time() . "_{$rand}." . $ext;
                $destPath = $targetDir . $fileBase;
                if (!move_uploaded_file($_FILES['figure']['tmp_name'], $destPath)) {
                    $errors[] = "Failed to upload image.";
                } else {
                    // نجاح الرفع → احذف القديمة إن وُجدت
                    if (!empty($oldFigureName)) {
                        $oldPath = $targetDir . $oldFigureName;
                        if (is_file($oldPath)) { @unlink($oldPath); }
                    }
                    $newFigureName = $fileBase;
                }
            }
        } else {
            $errors[] = "Image upload error (code: {$_FILES['figure']['error']}).";
        }
    }

    if (!$errors) {
        $sql = "UPDATE QuizQuestion
                SET question = ?, questionFigureFileName = ?, answerA = ?, answerB = ?, answerC = ?, answerD = ?, correctAnswer = ?
                WHERE id = ? AND quizID = ?";
        $stmt = mysqli_prepare($conn, $sql);
        if (!$stmt) {
            $errors[] = "DB error: " . mysqli_error($conn);
        } else {
            mysqli_stmt_bind_param(
                $stmt,
                "ssssssssi",
                $text,
                $newFigureName, // قد تكون NULL أو اسم ملف
                $c0, $c1, $c2, $c3,
                $correctAnswer,
                $questionID,
                $quizID
            );
            if (!mysqli_stmt_execute($stmt)) {
                $errors[] = "Update failed: " . mysqli_stmt_error($stmt);
            } else {
                header("Location: quiz.php?quizID={$quizID}&updated=1");
                exit();
            }
        }
    }

    // عند الخطأ: نعيد تحميل قيم النموذج من POST
    $question = [
        'id' => $questionID,
        'quizID' => $quizID,
        'question' => $text,
        'questionFigureFileName' => $newFigureName,
        'answerA' => $c0,
        'answerB' => $c1,
        'answerC' => $c2,
        'answerD' => $c3,
        'correctAnswer' => $correctAnswer
    ];
}

function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

// نحسب القيمة المختارة من A-D إلى اندكس 0..3
$selectedIndex = 0;
if ($question && isset($question['correctAnswer'])) {
    $map = ['A'=>0,'B'=>1,'C'=>2,'D'=>3];
    $selectedIndex = $map[$question['correctAnswer']] ?? 0;
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Codeland • Edit Question</title>
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
      <a href="educator.php">
        <img src="images/educatorUser.jpeg" alt="User" class="avatar">
      </a>
      <a href="logout.php" class="logout-btn">Logout</a>
    </div>
  </header>

  <div class="container">
    <div class="page-header header-user"></div>

    <?php if (!empty($errors)): ?>
      <div class="toast toast-error" style="display:block">
        <?php foreach($errors as $e): ?>
          <div><?php echo h($e); ?></div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <section class="card page-header">
      <h1>Edit Question</h1>
      <a id="cancel-link" class="btn-outline"
         href="quiz.php?quizID=<?php echo h($quizID ?? ($question['quizID'] ?? '')); ?>">Cancel</a>
    </section>

    <section class="card">
      <form id="question-form" method="post" enctype="multipart/form-data" action="edit_question.php">
        <!-- مخفية: IDs + الصورة القديمة -->
        <input type="hidden" name="questionID" value="<?php echo h($question['id'] ?? ''); ?>">
        <input type="hidden" name="quizID" value="<?php echo h($quizID ?? ($question['quizID'] ?? '')); ?>">
        <input type="hidden" name="oldFigure" value="<?php echo h($question['questionFigureFileName'] ?? ''); ?>">

        <label>Question Text</label>
        <textarea name="text" rows="3" class="input" required><?php echo h($question['question']); ?></textarea>

        <label>Choice A</label>
        <input name="c0" class="input" required value="<?php echo h($question['answerA']); ?>">

        <label>Choice B</label>
        <input name="c1" class="input" required value="<?php echo h($question['answerB']); ?>">

        <label>Choice C</label>
        <input name="c2" class="input" required value="<?php echo h($question['answerC']); ?>">

        <label>Choice D</label>
        <input name="c3" class="input" required value="<?php echo h($question['answerD']); ?>">

        <label>Correct Choice</label>
        <select name="correctIndex" class="input" required>
          <?php
          $labels = ['A','B','C','D'];
          for ($i=0; $i<4; $i++):
          ?>
            <option value="<?php echo $i; ?>" <?php echo ($i===$selectedIndex)?'selected':''; ?>>
              <?php echo $labels[$i]; ?>
            </option>
          <?php endfor; ?>
        </select>

        <label>Figure (optional)</label>
        <input type="file" name="figure" accept="image/*" class="input">
        <?php if (!empty($question['questionFigureFileName'])): ?>
          <div style="margin-top:8px">
            <small>Current image:</small><br>
            <img src="<?php echo 'uploads/questions/' . h($question['questionFigureFileName']); ?>" alt="Figure" style="max-width:220px;height:auto;border:1px solid #ddd;border-radius:8px;padding:4px;">
          </div>
        <?php endif; ?>

        <hr class="sep">
        <div class="flex">
          <button type="submit" class="btn">Save</button>
          <span class="helper"></span>
        </div>
      </form>
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
