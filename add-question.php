<?php
// add_question.php
session_start();
require_once 'db_config.php';
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }
if (($_SESSION['user_type'] ?? '') !== 'educator') { header("Location: LearnerHomePage.php"); exit; }
$conn->set_charset('utf8mb4');
function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

$educatorId = (int)$_SESSION['user_id'];
$quizID = filter_input(INPUT_GET, 'quizID', FILTER_VALIDATE_INT);
if (!$quizID) { $quizID = filter_input(INPUT_GET, 'quiz_id', FILTER_VALIDATE_INT); }
if (!$quizID) { header("Location: educator_homepage.php"); exit; }

// ملكية الكويز
$st = $conn->prepare("SELECT q.id, t.topicName FROM quiz q JOIN topic t ON t.id=q.topicID WHERE q.id=? AND q.educatorID=?");
$st->bind_param("ii", $quizID, $educatorId);
$st->execute(); $quiz = $st->get_result()->fetch_assoc(); $st->close();
if (!$quiz) { header("Location: educator_homepage.php?error=quizNotYours"); exit; }

$uploadDirRel = 'uploads/questions/'; $uploadDirAbs = __DIR__.'/'.$uploadDirRel;
if (!is_dir($uploadDirAbs)) { @mkdir($uploadDirAbs,0775,true); }

$errors = [];
if ($_SERVER['REQUEST_METHOD']==='POST') {
  $text = trim($_POST['text'] ?? '');
  $c0 = trim($_POST['c0'] ?? ''); $c1 = trim($_POST['c1'] ?? '');
  $c2 = trim($_POST['c2'] ?? ''); $c3 = trim($_POST['c3'] ?? '');
  $correctIndex = filter_input(INPUT_POST,'correctIndex',FILTER_VALIDATE_INT);
  if ($text==='') $errors[]="Question text is required.";
  if ($c0===''||$c1===''||$c2===''||$c3==='') $errors[]="All choices are required.";
  if (!in_array($correctIndex,[0,1,2,3],true)) $errors[]="Correct choice is required.";

  $letters=['A','B','C','D']; $correctAnswer=$letters[$correctIndex??0]??'A';
  $figureName = null;
  if (isset($_FILES['figure']) && $_FILES['figure']['error']!==UPLOAD_ERR_NO_FILE) {
    if ($_FILES['figure']['error']===UPLOAD_ERR_OK) {
      $ext = strtolower(pathinfo($_FILES['figure']['name'], PATHINFO_EXTENSION));
      if (!in_array($ext,['jpg','jpeg','png','gif','webp'],true)) $errors[]="Invalid image type.";
      else {
        try{$rand=bin2hex(random_bytes(4));}catch(Exception $e){$rand=mt_rand(100000,999999);}
        $figureName = "q_{$quizID}_".time()."_{$rand}.".$ext;
        if (!move_uploaded_file($_FILES['figure']['tmp_name'], $uploadDirAbs.$figureName)) $figureName=null;
      }
    } else $errors[]="Image upload error.";
  }

  if (!$errors) {
    $sql="INSERT INTO quizquestion (quizID,question,questionFigureFileName,answerA,answerB,answerC,answerD,correctAnswer)
          VALUES (?,?,?,?,?,?,?,?)";
    $st=$conn->prepare($sql);
    $st->bind_param("isssssss",$quizID,$text,$figureName,$c0,$c1,$c2,$c3,$correctAnswer);
    if($st->execute()){ $st->close(); header("Location: quiz.php?quizID={$quizID}&added=1"); exit; }
    $st->close(); $errors[]="Database error.";
  }
}

// صورة الهيدر
$pf = $_SESSION['photoFileName'] ?? ''; $avatar='images/educatorUser.jpeg';
foreach(['uploads/users/','uploads/'] as $root){ $abs=__DIR__.'/'.$root.$pf; if($pf && is_file($abs)){ $avatar=$root.$pf.'?v='.@filemtime($abs); break; } }
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Codeland • Add Question</title>
  <link rel="stylesheet" href="styleDeema.css"><link rel="stylesheet" href="HF.css">
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
  <?php if($errors): ?><div class="toast toast-error" style="display:block"><?php foreach($errors as $e):?><div><?=h($e)?></div><?php endforeach;?></div><?php endif; ?>

  <section class="card page-header" style="display:flex;justify-content:space-between;align-items:center;">
    <div><h1>Add Question — <?= h($quiz['topicName']) ?></h1><p class="muted">Quiz #<?= (int)$quizID ?></p></div>
    <a class="btn-outline" href="quiz.php?quizID=<?= (int)$quizID ?>">Cancel</a>
  </section>

  <section class="card">
    <form method="post" enctype="multipart/form-data">
      <label>Question Text</label><textarea name="text" rows="3" class="input" required><?=h($_POST['text']??'')?></textarea>
      <label>Choice A</label><input name="c0" class="input" required value="<?=h($_POST['c0']??'')?>">
      <label>Choice B</label><input name="c1" class="input" required value="<?=h($_POST['c1']??'')?>">
      <label>Choice C</label><input name="c2" class="input" required value="<?=h($_POST['c2']??'')?>">
      <label>Choice D</label><input name="c3" class="input" required value="<?=h($_POST['c3']??'')?>">
      <label>Correct Choice</label>
      <select name="correctIndex" class="input" required>
        <?php $sel=(int)($_POST['correctIndex']??0); $labels=['A','B','C','D']; for($i=0;$i<4;$i++): ?>
          <option value="<?=$i?>" <?=$sel===$i?'selected':''?>><?=$labels[$i]?></option>
        <?php endfor; ?>
      </select>
      <label>Figure (optional)</label><input type="file" name="figure" accept="image/*" class="input">
      <hr class="sep"><div class="flex"><button class="btn">Save</button><span class="helper"></span></div>
    </form>
  </section>
</div>

<footer class="cl-footer"><p>OUR VISION</p><p>At CodeLand, we make coding education simple, engaging, and accessible for everyone</p><p>© 2025 Website. All rights reserved.</p></footer>
</body>
</html>
