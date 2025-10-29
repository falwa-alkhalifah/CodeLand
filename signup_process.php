<?php
session_start();
require_once 'db_config.php'; 

$targetDir = "uploads/"; 
$defaultPhoto = "default_user.png"; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Receive data
    $firstName = $_POST['firstName'] ?? '';
    $lastName = $_POST['lastName'] ?? '';
    $email = $_POST['emailAddress'] ?? '';
    $password = $_POST['password'] ?? '';
    $userType = $_POST['userType'] ?? '';
    $topics = ($userType == 'educator' && isset($_POST['topics'])) ? $_POST['topics'] : [];

    $check_stmt = mysqli_prepare($conn, "SELECT id FROM User WHERE emailAddress = ?");
    mysqli_stmt_bind_param($check_stmt, "s", $email);
    mysqli_stmt_execute($check_stmt);
    $check_result = mysqli_stmt_get_result($check_stmt);

    if (mysqli_num_rows($check_result) > 0) {
        $_SESSION['signup_error'] = "The email address is already in the database.";
        header("Location: signup.php");
        mysqli_stmt_close($check_stmt);
        mysqli_close($conn);
        exit();
    }
    mysqli_stmt_close($check_stmt);

    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    $photoFileName = $defaultPhoto; 
    
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
        $fileExtension = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
        $uniqueFileName = $email . "_" . uniqid() . "." . $fileExtension;
        $targetFile = $targetDir . $uniqueFileName;
        
        if (move_uploaded_file($_FILES['photo']['tmp_name'], $targetFile)) {
            $photoFileName = $uniqueFileName;
        }
    }
    
    $insert_query = "INSERT INTO User (firstName, lastName, emailAddress, password, photoFileName, userType) VALUES (?, ?, ?, ?, ?, ?)";
    $insert_stmt = mysqli_prepare($conn, $insert_query);
    mysqli_stmt_bind_param($insert_stmt, "ssssss", $firstName, $lastName, $email, $hashed_password, $photoFileName, $userType);

    if (mysqli_stmt_execute($insert_stmt)) {
        $new_user_id = mysqli_insert_id($conn); 

        if ($userType == 'educator' && !empty($topics)) {
            $quiz_query = "INSERT INTO Quiz (educatorID, topicID) VALUES (?, ?)";
            $quiz_stmt = mysqli_prepare($conn, $quiz_query);
            foreach ($topics as $topicID) {
                mysqli_stmt_bind_param($quiz_stmt, "ii", $new_user_id, $topicID);
                mysqli_stmt_execute($quiz_stmt);
            }
            mysqli_stmt_close($quiz_stmt);
        }

        $_SESSION['user_id'] = $new_user_id;
        $_SESSION['user_type'] = $userType;

        if ($userType == 'educator') {
            header("Location: educator_homepage.php");
        } else {
            header("Location: learner_homepage.php");
        }
        mysqli_stmt_close($insert_stmt);
        mysqli_close($conn);
        exit();

    } else {
        $_SESSION['signup_error'] = "Registration failed. Please try again.";
        header("Location: signup.php");
        mysqli_stmt_close($insert_stmt);
        mysqli_close($conn);
        exit();
    }
}
mysqli_close($conn);
?>