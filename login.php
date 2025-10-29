<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Log In - CodeLand</title>
  <link rel="stylesheet" href="HF.css">
  <link rel="stylesheet" href="HomeStyle.css">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <style>
    .error-message {
      color: #ff4d4f;
      background-color: #ffe6e6;
      border: 1px solid #ff4d4f;
      padding: 8px 12px;
      border-radius: 5px;
      margin: 10px 0;
      display: block; 
    }
    <?php if (!isset($_SESSION['login_error'])) echo '.error-message { display: none; }'; ?>
  </style>

</head>
<body>

<header class="cl-header">
  <a href="index.html" class="brand">
    <img src="images/logo.png" alt="Logo">
    <span>Codeland</span>
  </a>
  <nav class="main-nav">
    <a href="login.php">Login</a>
    <a href="signup.php">Sign Up</a>
  </nav>
</header>

<main class="section">
  <div class="form-side">
    <h2>Log In</h2>
    
    <form action="login_process.php" method="POST">
      
      <label for="email">Email:</label>
      <input id="email" type="email" name="emailAddress" required>

      <label for="password">Password:</label>
      <input id="password" type="password" name="password" required>

      <input type="hidden" name="userType" id="hiddenUserType" required>

      <p id="loginError" class="error-message">
        <?php
        if (isset($_SESSION['login_error'])) {
            echo $_SESSION['login_error'];
            unset($_SESSION['login_error']);
        }
        ?>
      </p>

      <div class="btn-group">
        <button type="submit" onclick="document.getElementById('hiddenUserType').value='learner'">Log In as Learner</button>
        <button type="submit" onclick="document.getElementById('hiddenUserType').value='educator'">Log In as Educator</button>
      </div>
    </form>
    
  </div>

  <div class="image-side">
    <img src="images/logo.png" alt="CodeLand Logo">
  </div>
</main>

<footer class="cl-footer">
  </footer>

</body>
</html>