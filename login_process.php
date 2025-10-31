<?php
session_start();
require_once 'db_config.php'; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $email = $_POST['emailAddress'] ?? '';
    $password = $_POST['password'] ?? '';
    $userType = $_POST['userType'] ?? '';

    if (empty($email) || empty($password) || empty($userType)) {
        $_SESSION['login_error'] = "Please enter all required data and select your role."; 
        header("Location: login.php"); 
        exit();
    }

    $query = "SELECT id, password, userType FROM User WHERE emailAddress = ? AND userType = ?";
    $stmt = mysqli_prepare($conn, $query); 
    
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "ss", $email, $userType);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if (mysqli_num_rows($result) === 1) {
            $user = mysqli_fetch_assoc($result);
            
            if (password_verify($password, $user['password'])) {
                
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_type'] = $user['userType'];
                
                mysqli_stmt_close($stmt);
                mysqli_close($conn); 
                
                if ($user['userType'] == 'educator') {
                    header("Location: educator_homepage.php");
                } else { 
                    header("Location: learner_homepage.php");
                }
                exit();
                
            } else {
                $_SESSION['login_error'] = "Incorrect password.";
            }
        } else {
            $_SESSION['login_error'] = "The email address is not registered for this role.";
        }
        
        mysqli_stmt_close($stmt);
    } else {
        $_SESSION['login_error'] = "System error. Please try again later.";
    }

} else {
    $_SESSION['login_error'] = "Invalid request method.";
}

header("Location: login.php");
mysqli_close($conn); 
exit();
?>
