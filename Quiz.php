<?php
// ========== Auth & DB Connection ==========
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['user_type'] ?? '') !== 'educator') {
    header("Location: login.php");
    exit();
}

require_once 'db_config.php'; // PDO instance $pdo

$quizId = isset($_GET['quiz_id']) ? (int)$_GET['quiz_id'] : 0;
if ($quizId <= 0) {
    die("Invalid quiz ID.");
}

// Function for htmlspecialchars
function h($str) { return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8'); }

// Get quiz info and validate educator ownership
$qz = $pdo->prepare("
    SELECT q.*, t.topicName 
    FROM Quiz q
    JOIN Topic t ON t.id = q.topicID
    WHERE q.id = ? AND q.educatorID = ?
");
$qz->execute([$quizId, $_SESSION['user_id']]);
$quiz = $qz->fetch(PDO::FETCH_ASSOC);

if (!$quiz) {
    die("Quiz not found or you don't have permission to view it.");
}

// Fetch all questions
$qs = $pdo->prepare("SELECT * FROM QuizQuestion WHERE quizID = ?");
$qs->execute([$quizId]);
$questions = $qs->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quiz - <?= h($quiz['topicName']) ?></title>
    <link rel="stylesheet" href="H-style.css">
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
            <a href="educator.php"><img src="images/educatorUser.jpeg" alt="User" class="avatar"></a>
            <a href="signout.php" class="logout-btn">Logout</a>
        </div>
    </header>

    <main>
        <div class="container">
            <div id="toast" class="toast"></div>

            <!-- Page Header -->
            <section class="page-header">
                <h1 id="quiz-title"><?= h($quiz['topicName']) ?> Quiz Questions</h1>
                <a href="add-question.php?quiz_id=<?= $quizId ?>" id="add-question-btn" class="btn">+ Add Question</a>
            </section>

            <!-- Questions Table -->
            <?php if (!$questions): ?>
                <div style="padding: 20px; text-align: center;">No questions found for this quiz.</div>
            <?php else: ?>
                <section class="table-section">
                    <table class="table" id="questions-table">
                        <thead>
                            <tr>
                                <th>Question</th>
                                <th class="actions-column">Edit</th>
                                <th class="actions-column">Delete</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($questions as $q): ?>
                            <tr>
                                <td class="question-column">
                                    <div class="question-text"><?= h($q['question']) ?></div>
                                    <?php if (!empty($q['questionFigureFileName'])): ?>
                                        <div style="padding: 10px 0;">
                                            <img src="uploads/questions/<?= h($q['questionFigureFileName']) ?>" alt="Figure" style="width:120px;">
                                        </div>
                                    <?php endif; ?>
                                    <div class="choices-container">
                                        <span class="choice <?= $q['correctAnswer'] === 'A' ? 'correct' : '' ?>">A- <?= h($q['answerA']) ?></span>
                                        <span class="choice <?= $q['correctAnswer'] === 'B' ? 'correct' : '' ?>">B- <?= h($q['answerB']) ?></span>
                                        <span class="choice <?= $q['correctAnswer'] === 'C' ? 'correct' : '' ?>">C- <?= h($q['answerC']) ?></span>
                                        <span class="choice <?= $q['correctAnswer'] === 'D' ? 'correct' : '' ?>">D- <?= h($q['answerD']) ?></span>
                                    </div>
                                </td>
                                <td class="actions-column">
                                    <a href="edit-question.php?question_id=<?= $q['id'] ?>&quiz_id=<?= $quizId ?>" class="btn btn-edit action-button">Edit</a>
                                </td>
                                <td class="actions-column">
                                    <a href="delete-question.php?question_id=<?= $q['id'] ?>&quiz_id=<?= $quizId ?>" class="btn btn-delete action-button" onclick="return confirm('Are you sure you want to delete this question?')">Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </section>
            <?php endif; ?>
        </div>
    </main>

    <!-- ====== FOOTER ====== -->
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
