<?php
// add_question.php
session_start();
require_once 'db_config.php'; // يجب أن يعرّف $conn = mysqli_connect(...)

//
// 1) حماية الصفحة + السماح فقط للمدرّس
//
if (!isset($_SESSION['userType']) || $_SESSION['userType'] !== 'educator') {
    header("Location: login.php?error=notAllowed");
    exit();
}

//
// 2) جلب quizID من GET لعرض النموذج
//
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $quizID = filter_input(INPUT_GET, 'quizID', FILTER_VALIDATE_INT);
    if (!$quizID) {
        header("Location: educator.php?error=missingQuiz");
        exit();
    }
    // سنعرض النموذج لاحقاً (HTML بالأسفل)
}

//
// 3) معالجة POST: إدخال السؤال في قاعدة البيانات + رفع الصورة (اختياري)
//
$errors = [];
$old = ['text'=>'','c0'=>'','c1'=>'','c2'=>'','c3'=>'','correctIndex'=>'0'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // 3.1 قراءة المدخلات
    $quizID = filter_input(INPUT_POST, 'quizID', FILTER_VALIDATE_INT);
    $text   = trim($_POST['text'] ?? '');
    $c0     = trim($_POST['c0'] ?? '');
    $c1     = trim($_POST['c1'] ?? '');
    $c2     = trim($_POST['c2'] ?? '');
    $c3     = trim($_POST['c3'] ?? '');
    $correctIndex = filter_input(INPUT_POST, 'correctIndex', FILTER_VALIDATE_INT);

    $old = ['text'=>$text,'c0'=>$c0,'c1'=>$c1,'c2'=>$c2,'c3'=>$c3,'correctIndex'=>(string)$correctIndex];

    if (!$quizID)               $errors[] = "Invalid quiz.";
    if ($text === '')           $errors[] = "Question text is required.";
    if ($c0 === ''||$c1===''||$c2===''||$c3==='') $errors[] = "All choices (A-D) are required.";
    if (!in_array($correctIndex, [0,1,2,3], true)) $errors[] = "Correct choice is required.";

    // 3.2 تجهيز correctAnswer كحرف (A-D)
    $letters = ['A','B','C','D'];
    $correctAnswer = $letters[$correctIndex ?? 0] ?? 'A';

    // 3.3 رفع الصورة (اختياري)
    $figureFileName = null;
    if (isset($_FILES['figure']) && $_FILES['figure']['error'] !== UPLOAD_ERR_NO_FILE) {
        if ($_FILES['figure']['error'] === UPLOAD_ERR_OK) {

            $allowed = ['jpg','jpeg','png','gif','webp'];
            $orig    = $_FILES['figure']['name'];
            $ext     = strtolower(pathinfo($orig, PATHINFO_EXTENSION));

            if (!in_array($ext, $allowed, true)) {
                $errors[] = "Invalid image type. Allowed: jpg, jpeg, png, gif, webp.";
            } else {
                $targetDir = __DIR__ . '/uploads/questions/';
                if (!is_dir($targetDir)) {
                    @mkdir($targetDir, 0775, true);
                }
                // اسم فريد للصورة: quizID + وقت + random
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
                    // نخزن الاسم فقط في قاعدة البيانات
                    $figureFileName = $fileBase;
                }
            }
        } else {
            $errors[] = "Image upload error (code: {$_FILES['figure']['error']}).";
        }
    }

    // 3.4 إن لا توجد أخطاء → INSERT للسؤال
    if (!$errors) {
        $sql = "INSERT INTO QuizQuestion
                (quizID, question, questionFigureFileName, answerA, answerB, answerC, answerD, correctAnswer)
                VALUES (?,?,?,?,?,?,?,?)";
        $stmt = mysqli_prepare($conn, $sql);
        if (!$stmt) {
            $errors[] = "DB error: " . mysqli_error($conn);
        } else {
            // لو مافي صورة نخليها NULL
            // ملاحظة: في mysqli_bind_param لا يوجد type خاص بـ NULL؛ نمرر null وسيُخزّن NULL.
            mysqli_stmt_bind_param(
                $stmt,
                "isssssss",
                $quizID,
                $text,
                $figureFileName,
                $c0,
                $c1,
                $c2,
                $c3,
                $correctAnswer
            );
            if (!mysqli_stmt_execute($stmt)) {
                $errors[] = "Insert failed: " . mysqli_stmt_error($stmt);
            } else {
                // نجاح → ارجاع لصفحة الكويز
                header("Location: quiz.php?quizID={$quizID}&added=1");
                exit();
            }
        }
    }
}

// إذا وصلنا هنا فنحن إما في GET (عرض النموذج) أو في POST مع أخطاء (نعرضها)
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
          <div><?php echo htmlspecialchars($e, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <section class="card page-header">
      <h1>Add Question</h1>
      <a id="cancel-link"
         href="quiz.php?quizID=<?php echo htmlspecialchars($quizID ?? ($_POST['quizID'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
         class="btn-outline">Cancel</a>
    </section>

    <section class="card">
      <form id="question-form" method="post" enctype="multipart/form-data" action="add_question.php">
        <!-- نمرر quizID كـ hidden -->
        <input type="hidden" name="quizID"
               value="<?php echo htmlspecialchars($quizID ?? ($_POST['quizID'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">

        <label>Question Text</label>
        <textarea name="text" rows="3" class="input" required><?php
          echo htmlspecialchars($old['text'] ?? '', ENT_QUOTES, 'UTF-8');
        ?></textarea>

        <label>Choice A</label>
        <input name="c0" class="input" required
               value="<?php echo htmlspecialchars($old['c0'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">

        <label>Choice B</label>
        <input name="c1" class="input" required
               value="<?php echo htmlspecialchars($old['c1'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">

        <label>Choice C</label>
        <input name="c2" class="input" required
               value="<?php echo htmlspecialchars($old['c2'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">

        <label>Choice D</label>
        <input name="c3" class="input" required
               value="<?php echo htmlspecialchars($old['c3'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">

        <label>Correct Choice</label>
        <select name="correctIndex" class="input" required>
          <?php
          $sel = isset($old['correctIndex']) ? (int)$old['correctIndex'] : 0;
          $labels = ['A','B','C','D'];
          for ($i=0; $i<4; $i++):
          ?>
            <option value="<?php echo $i; ?>" <?php echo ($i===$sel)?'selected':''; ?>>
              <?php echo $labels[$i]; ?>
            </option>
          <?php endfor; ?>
        </select>

        <label>Figure (optional)</label>
        <input type="file" name="figure" accept="image/*" class="input">

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
