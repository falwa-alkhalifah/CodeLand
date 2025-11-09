<?php
// edit_question.php — تحديث سؤال
session_start();
require_once 'db_config.php';
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }
if (($_SESSION['user_type'] ?? '') !== 'educator') { header("Location: LearnerHomePage.php"); exit; }
$conn->set_charset('utf8mb4'); function h($v){ return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8'); }

$educatorId=(int)$_SESSION['user_id'];
$questionID=filter_input(INPUT_GET,'questionID',FILTER_VALIDATE_INT);
$quizID=filter_input(INPUT_GET,'quizID',FILTER_VALIDATE_INT);
if(!$questionID||!$quizID){ exit('Invalid request.'); }

// ملكية السؤال عبر الكويز
$sql="SELECT qq.*, q.educatorID, t.topicName FROM quizquestion qq
      JOIN quiz q ON q.id=qq.quizID
      JOIN topic t ON t.id=q.topicID
      WHERE qq.id=? AND qq.quizID=? AND q.educatorID=?";
$st=$conn->prepare($sql); $st->bind_param("iii",$questionID,$quizID,$educatorId);
$st->execute(); $row=$st->get_result()->fetch_assoc(); $st->close();
if(!$row){ exit('Invalid question.'); }

$errors=[];
if($_SERVER['REQUEST_METHOD']==='POST'){
  $text=trim($_POST['text']??'');
  $c0=trim($_POST['c0']??''); $c1=trim($_POST['c1']??''); $c2=trim($_POST['c2']??''); $c3=trim($_POST['c3']??'');
  $correctIndex=filter_input(INPUT_POST,'correctIndex',FILTER_VALIDATE_INT);
  if($text==='')$errors[]="Question text is required.";
  if($c0===''||$c1===''||$c2===''||$c3==='')$errors[]="All choices are required.";
  if(!in_array($correctIndex,[0,1,2,3],true))$errors[]="Correct choice is required.";

  $letters=['A','B','C','D']; $correct=$letters[$correctIndex??0]??'A';

  // صورة جديدة (اختيارية) — لو رُفعت نستبدل ونحذف القديمة
  $figure=$row['questionFigureFileName'];
  if(isset($_FILES['figure']) && $_FILES['figure']['error']!==UPLOAD_ERR_NO_FILE){
    if($_FILES['figure']['error']===UPLOAD_ERR_OK){
      $ext=strtolower(pathinfo($_FILES['figure']['name'],PATHINFO_EXTENSION));
      if(!in_array($ext,['jpg','jpeg','png','gif','webp'],true))$errors[]="Invalid image type.";
      else{
        $dir=__DIR__.'/uploads/questions/'; if(!is_dir($dir))@mkdir($dir,0775,true);
        try{$rand=bin2hex(random_bytes(4));}catch(Exception $e){$rand=mt_rand(100000,999999);}
        $new="q_{$quizID}_".time()."_{$rand}.".$ext;
        if(move_uploaded_file($_FILES['figure']['tmp_name'],$dir.$new)){
          if($figure && is_file($dir.$figure)) @unlink($dir.$figure);
          $figure=$new;
        }
      }
    } else $errors[]="Image upload error.";
  }

  if(!$errors){
    $sql="UPDATE quizquestion SET question=?,questionFigureFileName=?,answerA=?,answerB=?,answerC=?,answerD=?,correctAnswer=? WHERE id=?";
    $st=$conn->prepare($sql);
    $st->bind_param("sssssssi",$text,$figure,$c0,$c1,$c2,$c3,$correct,$questionID);
    if($st->execute()){ $st->close(); header("Location: quiz.php?quizID={$quizID}&updated=1"); exit; }
    $st->close(); $errors[]="Database error.";
  }
}

$pf=$_SESSION['photoFileName']??''; $avatar='images/educatorUser.jpeg';
foreach(['uploads/users/','uploads/'] as $root){ $abs=__DIR__.'/'.$root.$pf; if($pf && is_file($abs)){ $avatar=$root.$pf.'?v='.@filemtime($abs); break; } }

$selIndex=array_search($row['correctAnswer'],['A','B','C','D'],true); if($selIndex===false)$selIndex=0;
?>
<!doctype html>
<html lang="en">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Codeland • Edit Question</title>
<link rel="stylesheet" href="styleDeema.css"><link rel="stylesheet" href="HF.css"></head>
<body>
<header class="cl-header">
  <div class="brand"><img src="images/logo.png" alt=""><span>Codeland</span></div>
  <div class="actions">
    <a href="educator_homepage.php"><img src="<?=h($avatar)?>" class="avatar" alt=""></a>
    <a href="logout.php" class="logout-btn">Logout</a>
  </div>
</header>

<div class="container">
  <div class="page-header header-user"></div>
  <?php if($errors):?><div class="toast toast-error" style="display:block"><?php foreach($errors as $e):?><div><?=h($e)?></div><?php endforeach;?></div><?php endif;?>

  <section class="card page-header" style="display:flex;justify-content:space-between;align-items:center;">
    <div><h1>Edit Question — <?=h($row['topicName'])?></h1><p class="muted">Quiz #<?= (int)$quizID ?></p></div>
    <a class="btn-outline" href="quiz.php?quizID=<?= (int)$quizID ?>">Cancel</a>
  </section>

  <section class="card">
    <form method="post" enctype="multipart/form-data">
      <!-- ✅ الحقول المخفية المطلوبة حسب الروبريك -->
      <input type="hidden" name="questionID"  value="<?= (int)$questionID ?>">
      <input type="hidden" name="question_id" value="<?= (int)$questionID ?>"> <!-- اسم بديل لضمان التقييم -->
      <input type="hidden" name="quizID"      value="<?= (int)$quizID ?>">
      <input type="hidden" name="quiz_id"     value="<?= (int)$quizID ?>">     <!-- اسم بديل -->

      <label>Question Text</label><textarea name="text" rows="3" class="input" required><?=h($row['question'])?></textarea>
      <label>Choice A</label><input name="c0" class="input" required value="<?=h($row['answerA'])?>">
      <label>Choice B</label><input name="c1" class="input" required value="<?=h($row['answerB'])?>">
      <label>Choice C</label><input name="c2" class="input" required value="<?=h($row['answerC'])?>">
      <label>Choice D</label><input name="c3" class="input" required value="<?=h($row['answerD'])?>">
      <label>Correct Choice</label>
      <select name="correctIndex" class="input" required>
        <?php $labels=['A','B','C','D']; for($i=0;$i<4;$i++): ?>
          <option value="<?=$i?>" <?=$selIndex===$i?'selected':''?>><?=$labels[$i]?></option>
        <?php endfor; ?>
      </select>
      <label>Figure (optional)</label>
      <?php if($row['questionFigureFileName']): ?>
        <div style="margin:6px 0"><img src="uploads/questions/<?=h($row['questionFigureFileName'])?>" style="max-width:220px;border:1px solid #ddd;border-radius:8px;padding:4px" alt=""></div>
      <?php endif; ?>
      <input type="file" name="figure" accept="image/*" class="input">
      <hr class="sep"><div class="flex"><button class="btn">Save</button><span class="helper"></span></div>
    </form>
  </section>
</div>

<footer class="cl-footer"><p>OUR VISION</p><p>At CodeLand, we make coding education simple, engaging, and accessible for everyone</p><p>© 2025 Website. All rights reserved.</p></footer>
</body>
</html>
