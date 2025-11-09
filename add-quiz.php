<?php
session_start();
require_once 'db_config.php';
if (!isset($_SESSION['user_id']) || ($_SESSION['user_type']??'')!=='educator') { header('Location: login.php'); exit; }
$conn->set_charset('utf8mb4');

$educatorId   = (int)$_SESSION['user_id'];

/* التوبيكات الثابتة بالترتيب المطلوب */
$fixedTopics = ['Python','Java','CSS','HTML'];

/* تأكّد وجودها بجدول topic، وإن ما وُجدت أنشئها */
$in  = implode(',', array_fill(0, count($fixedTopics), '?'));
$typ = str_repeat('s', count($fixedTopics));

$map = []; // name => id

// 1) جلب الموجود
$sql = "SELECT id, topicName FROM topic WHERE topicName IN ($in)";
$st  = $conn->prepare($sql);
$st->bind_param($typ, ...$fixedTopics);
$st->execute();
$res = $st->get_result();
while ($r = $res->fetch_assoc()) { $map[$r['topicName']] = (int)$r['id']; }
$st->close();

// 2) إنشاء المفقود
$ins = $conn->prepare("INSERT INTO topic (topicName) VALUES (?)");
foreach ($fixedTopics as $name) {
  if (!isset($map[$name])) {
    $ins->bind_param("s", $name);
    $ins->execute();
    $map[$name] = $ins->insert_id;
  }
}
$ins->close();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Codeland • Add Quiz</title>
  <link rel="stylesheet" href="H-style.css"><link rel="stylesheet" href="HF.css">
</head>
<body>
<header class="cl-header">
  <div class="brand"><img src="images/logo.png"><span>Codeland</span></div>
  <div class="actions"><a href="educator_homepage.php" class="logout-btn">Back</a></div>
</header>

<div class="container">
  <section class="card page-header" style="display:flex;justify-content:space-between;align-items:center;">
    <h1>Create New Quiz</h1>
    <a href="educator_homepage.php" class="btn-outline">Cancel</a>
  </section>

  <section class="card">
    <form method="post" action="add-quiz_process.php">
      <label>Topic</label>
      <select name="topicID" class="input" required>
        <option value="" disabled selected>Select a topic</option>
        <?php
          // نطبع بنفس ترتيب fixedTopics
          foreach ($fixedTopics as $name):
            $id = (int)($map[$name] ?? 0);
        ?>
          <option value="<?= $id ?>"><?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?></option>
        <?php endforeach; ?>
      </select>

      <hr class="sep">
      <button class="btn">Create</button>
    </form>
  </section>
</div>

<footer class="cl-footer"><p>© 2025</p></footer>
</body>
</html>
