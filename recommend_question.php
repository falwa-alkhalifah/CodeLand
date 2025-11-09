<?php 



session_start();



$db_host = "localhost";

$db_user = "root";

$db_pass = "root"; 

$db_name = "database";



$connect = mysqli_connect($db_host, $db_user, $db_pass, $db_name);

if(mysqli_connect_errno()) {

    echo "Failed to connect to MySQL: " . mysqli_connect_error();

    exit();

}



if(!isset($_SESSION['user_type']) || $_SESSION['user_type'] != 'learner' || !isset($_SESSION['user_id'])) {

    header("Location: index.html"); 

    exit();

}



$learnerID = intval($_SESSION['user_id']);



// --- Fetch Learner Info for Header Display ---

$stmt = mysqli_prepare($connect, "SELECT photoFileName FROM user WHERE id = ?");

mysqli_stmt_bind_param($stmt, "i", $learnerID);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$learner = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);



if (!$learner) {

    session_unset();

    session_destroy();

    header("Location: index.html"); 

    exit();

}

// --- Define Learner Photo Path ---

// Prioritize 'uploads/users/' if educator_homepage.php logic is followed, otherwise assume 'images/'


$defaultAvatar = 'images/default_avatar.jpeg'; 
$storedPhoto = $learner['photoFileName'] ?? '';
$learnerPhotoPath = $defaultAvatar;
$photoUploadsDir = __DIR__ . '/uploads/users/';

if ($storedPhoto && is_file($photoUploadsDir . $storedPhoto)) {
    $learnerPhotoPath = 'uploads/users/' . htmlspecialchars($storedPhoto) . '?v=' . @filemtime($photoUploadsDir . $storedPhoto);
} else {
    if (is_file(__DIR__ . '/images/' . $storedPhoto)) {
        $learnerPhotoPath = 'images/' . htmlspecialchars($storedPhoto);
    }
}

// ---------------------------------------------



$topicsResult = mysqli_query($connect, "SELECT id, topicName FROM topic ORDER BY topicName");

$topics = mysqli_fetch_all($topicsResult, MYSQLI_ASSOC);



$educatorsResult = mysqli_query($connect, "SELECT id, firstName, lastName FROM user WHERE userType = 'educator' ORDER BY lastName");

$educators = mysqli_fetch_all($educatorsResult, MYSQLI_ASSOC);



$message = '';

$message_type = ''; 



if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Collect inputs

    $topicID = $_POST['topic'];

    $educatorID = $_POST['educator'];

    $questionText = $_POST['qtext'];

    $answerA = $_POST['c1'];

    $answerB = $_POST['c2'];

    $answerC = $_POST['c3'];

    $answerD = $_POST['c4'];

    $correctAnswer = $_POST['correct'];



    // --- 1. Find the QuizID (Using Prepared Statement for security) ---

    $quizID = null;

    $stmt = mysqli_prepare($connect, "SELECT id FROM quiz WHERE educatorID = ? AND topicID = ?");

    mysqli_stmt_bind_param($stmt, "ii", $educatorID, $topicID);

    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    $quizRow = mysqli_fetch_assoc($result);

    mysqli_stmt_close($stmt);

    

    if ($quizRow) {

        $quizID = $quizRow['id'];

    }

    

    if ($quizID) {

        $figureFileName = null;

        if (isset($_FILES['figure']) && $_FILES['figure']['error'] === UPLOAD_ERR_OK) {

            $fileTmpPath = $_FILES['figure']['tmp_name'];

            $fileName = basename($_FILES['figure']['name']);

            $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

            $allowedExtensions = array('jpg', 'jpeg', 'png', 'gif', 'svg');

            

            // === FIX 1: Change upload directory to match educator review page logic ===

            $UPLOAD_DIR = __DIR__ . '/uploads/questions/';

            if (!is_dir($UPLOAD_DIR)) {

                // Attempt to create the directory if it doesn't exist

                @mkdir($UPLOAD_DIR, 0755, true); 

            }



            if (in_array($fileExtension, $allowedExtensions)) {

                $newFileName = uniqid() . '.' . $fileExtension;

                $uploadPath = $UPLOAD_DIR . $newFileName; 



                if (move_uploaded_file($fileTmpPath, $uploadPath)) {

                    $figureFileName = $newFileName;

                } else {

                    $message = "Error uploading file. Check permissions on the 'uploads/questions/' folder.";

                    $message_type = 'error';

                }

            } else {

                $message = "Invalid file type. Only PNG, JPG, or SVG allowed.";

                $message_type = 'error';

            }

        }

        

        // --- 2. Insert Recommended Question (Using Prepared Statement for security) ---

        if ($message_type !== 'error') {

            // Note: We list 10 columns, but only use 9 placeholders, relying on 'pending' being hardcoded as the 10th value.

            // This is a common pattern, but requires the bind string to match the 9 placeholders.

            $insertQuery = "

                INSERT INTO recommendedquestion 

                (quizID, learnerID, question, questionFigureFileName, answerA, answerB, answerC, answerD, correctAnswer, status) 

                VALUES 

                (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')

            ";

            

            $figureParam = $figureFileName;

            

            $stmt = mysqli_prepare($connect, $insertQuery);

            

            // === FIX 2: Corrected 9-character bind string. ===

            // Types: i (quizID), i (learnerID), s (question), s (figure), s (A), s (B), s (C), s (D), s (correct)

            mysqli_stmt_bind_param($stmt, "iississss", 

                $quizID, 

                $learnerID, 

                $questionText, 

                $figureParam, 

                $answerA, 

                $answerB, 

                $answerC, 

                $answerD, 

                $correctAnswer

            );



            if (mysqli_stmt_execute($stmt)) {

                mysqli_stmt_close($stmt);

                header("Location: learner_homePage.php?recommend_status=success");

                exit();

            } else {

                $message = "Database error: " . mysqli_error($connect);

                $message_type = 'error';

            }

        }

    } else {

        $message = "Error: Could not find a matching quiz for the selected Topic and Educator.";

        $message_type = 'error';

    }

}



mysqli_close($connect);

?>



<!DOCTYPE html>

<html lang="en">



<head>

    <meta charset="UTF-8" />

    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>

    <title>Recommend Question</title>

    <link rel="stylesheet" href="lernerStyle.css">

    <link rel="stylesheet" href="HF.css">

</head>



<body>



<header class="cl-header">

    <div class="brand">

        <img src="images/logo.png" alt="Logo">

        <span>Codeland</span>

    </div>

    <div class="actions">

        <a href="learner_homePage.php">

            <img src="<?php echo $learnerPhotoPath; ?>" alt="Profile" class="avatar">

        </a>

        <a href="index.html" class="logout-btn">Logout</a>

    </div>

</header>



<main class="container main">

    <h1>Recommend a Question</h1>

    <p class="small">Suggest a new multiple-choice question for a quiz. Fields marked with * are required.</p>

    <?php if ($message): ?>

    <div class="card" style="margin-bottom:16px; padding:15px; background:<?php echo $message_type === 'success' ? '#064e3b' : '#450a0a'; ?>; border:1px solid <?php echo $message_type === 'success' ? '#0d9488' : '#dc2626'; ?>;">

        <p style="margin:0; color:white; font-weight:bold;"><?php echo htmlspecialchars($message); ?></p>

    </div>

    <?php endif; ?>

    <form class="card" action="recommend_question.php" method="POST" enctype="multipart/form-data">

        <div class="row row-2">

            <div>

                <label for="topic">* Topic</label>

                <select id="topic" name="topic" class="select" required>

                    <option value="">Choose a topic</option>

                    <?php foreach($topics as $t): ?>

                        <option value="<?php echo htmlspecialchars($t['id']); ?>"><?php echo htmlspecialchars($t['topicName']); ?></option>

                    <?php endforeach; ?>

                </select>

            </div>

            <div>

                <label for="educator">* Educator</label>

                <select id="educator" name="educator" class="select" required>

                    <option value="">Choose an educator</option>

                    <?php foreach($educators as $e): ?>

                        <option value="<?php echo htmlspecialchars($e['id']); ?>"><?php echo htmlspecialchars($e['firstName'] . ' ' . $e['lastName']); ?></option>

                    <?php endforeach; ?>

                </select>

            </div>

        </div>

        <div class="row" style="margin-top:12px">

            <div>

                <label for="figure">Question Figure</label>

                <input id="figure" name="figure" type="file" accept="image/*" class="input"/>

                <div class="helper">PNG, JPG, or SVG.</div>

            </div>

        </div>

        <div class="row" style="margin-top:12px">

            <div>

                <label for="qtext">* Question Text</label>

                <textarea id="qtext" name="qtext" rows="3" class="input" required placeholder="Write your question here..."></textarea>

            </div>

        </div>

        <div class="row row-2" style="margin-top:12px">

            <div>

                <label for="c1">* Choice A</label>

                <input id="c1" name="c1" class="input" required/>

            </div>

            <div>

                <label for="c2">* Choice B</label>

                <input id="c2" name="c2" class="input" required/>

            </div>

        </div>

        <div class="row row-2" style="margin-top:12px">

            <div>

                <label for="c3">* Choice C</label>

                <input id="c3" name="c3" class="input" required/>

            </div>

            <div>

                <label for="c4">* Choice D</label>

                <input id="c4" name="c4" class="input" required/>

            </div>

        </div>

        <div class="row" style="margin:12px 0">

            <label>* Correct Choice</label>

            <div class="inline">

                <label><input type="radio" name="correct" value="A" required/> A</label>

                <label><input type="radio" name="correct" value="B"/> B</label>

                <label><input type="radio" name="correct" value="C"/> C</label>

                <label><input type="radio" name="correct" value="D"/> D</label>

            </div>

            <div class="helper">Exactly one correct answer is allowed.</div>

        </div>

        <div class="right" style="gap:10px">

            <a class="btn btn-outline" href="learner_homePage.php">Cancel</a>

            <button type="submit" class="btn">Submit Recommendation</button>

        </div>

    </form>

</main>



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



<script>

    document.getElementById('year').textContent = new Date().getFullYear();

</script>



</body>

</html>