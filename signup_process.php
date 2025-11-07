<?php
session_start();
require_once 'db_config.php';

// فعّلي الإبلاغ عن أخطاء mysqli (احذفي السطرين قبل التسليم)
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
ini_set('display_errors', 1);
error_reporting(E_ALL);

// ========= إعدادات رفع الصورة =========
// بقية الصفحات تبحث عن الصورة في uploads/users/
// لذلك نوحّده هنا أيضًا
$targetDir = __DIR__ . "/uploads/users/";
$targetDirRel = "uploads/users/"; // الذي نخزّنه في الداتابيس
if (!is_dir($targetDir)) { @mkdir($targetDir, 0775, true); }

$defaultPhoto = "default_user.png"; // تأكدي موجود في uploads/users/

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: signup.php");
    exit();
}

$conn->set_charset('utf8mb4');

// ========== استلام القيم ==========
$firstName = trim($_POST['firstName'] ?? '');
$lastName  = trim($_POST['lastName'] ?? '');
$email     = trim($_POST['emailAddress'] ?? '');
$password  = $_POST['password'] ?? '';
$userType  = $_POST['userType'] ?? '';
$topics    = ($userType === 'educator' && !empty($_POST['topics'])) ? (array)$_POST['topics'] : [];

// ========== تحقق البريد موجود ==========
$check_stmt = $conn->prepare("SELECT id FROM user WHERE emailAddress = ?");
$check_stmt->bind_param("s", $email);
$check_stmt->execute();
$check_stmt->store_result();
if ($check_stmt->num_rows > 0) {
    $_SESSION['signup_error'] = "The email address is already in the database.";
    $check_stmt->close();
    header("Location: signup.php");
    exit();
}
$check_stmt->close();

// ========== تجهيز الصورة ==========
$photoFileName = $defaultPhoto;

if (isset($_FILES['photo']) && $_FILES['photo']['error'] !== UPLOAD_ERR_NO_FILE) {
    if ($_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
        // يسمح بهذه الامتدادات فقط
        $allowed = ['jpg','jpeg','png','gif','webp'];
        if (in_array($ext, $allowed, true)) {
            // اسم ملف فريد
            try { $rand = bin2hex(random_bytes(4)); } catch (Exception $e) { $rand = mt_rand(100000, 999999); }
            $uniqueFileName = "u_" . time() . "_" . $rand . "." . $ext;
            $targetFileAbs  = $targetDir . $uniqueFileName;

            if (move_uploaded_file($_FILES['photo']['tmp_name'], $targetFileAbs)) {
                $photoFileName = $uniqueFileName; // نخزّن الاسم فقط
            }
        }
    }
}

// ========== إدخال المستخدم ==========
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

$insert_query = "INSERT INTO user (firstName, lastName, emailAddress, password, photoFileName, userType)
                 VALUES (?, ?, ?, ?, ?, ?)";
$insert_stmt = $conn->prepare($insert_query);
$insert_stmt->bind_param("ssssss", $firstName, $lastName, $email, $hashed_password, $photoFileName, $userType);
$insert_stmt->execute();

$new_user_id = $conn->insert_id;
$insert_stmt->close();

// ========== إن كان معلّم: إنشاء كويزات للتوبيكات المختارة ==========
// ملاحظة: جدول quiz في السكيمة الجديدة يحتوي (educatorID, topicID) فقط — لا تدخل quizName
if ($userType === 'educator' && !empty($topics)) {
    // تحقّق أن كل topicID موجود فعلاً لكي لا يفشل FK
    $chkTopic = $conn->prepare("SELECT id FROM topic WHERE id=?");
    $insQuiz  = $conn->prepare("INSERT INTO quiz (educatorID, topicID) VALUES (?, ?)");

    foreach ($topics as $topicID_raw) {
        $topicID = (int)$topicID_raw;
        if ($topicID <= 0) continue;

        $chkTopic->bind_param("i", $topicID);
        $chkTopic->execute();
        $exists = $chkTopic->get_result()->fetch_assoc();
        if (!$exists) continue; // تجاهل التوبيك غير الموجود

        $insQuiz->bind_param("ii", $new_user_id, $topicID);
        $insQuiz->execute();
    }
    $chkTopic->close();
    $insQuiz->close();
}

// ========== تفعيل الجلسة وتوجيه ==========
$_SESSION['user_id']       = $new_user_id;
$_SESSION['user_type']     = $userType;
$_SESSION['photoFileName'] = $photoFileName;

if ($userType === 'educator') {
    header("Location: educator_homepage.php");
} else {
    header("Location: learner_homepage.php");
}
exit();
