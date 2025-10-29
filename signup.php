<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Sign Up - CodeLand</title>
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
    <?php if (!isset($_SESSION['signup_error'])) echo '.error-message { display: none; }'; ?>
  </style>

  <script>
    function showForm(role) {
      document.getElementById("learnerForm").classList.add("hidden");
      document.getElementById("educatorForm").classList.add("hidden");
      if(role === "learner") {
        document.getElementById("learnerForm").classList.remove("hidden");
      } else if(role === "educator") {
        document.getElementById("educatorForm").classList.remove("hidden");
      }
    }
  </script>
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
    <h2>Create Your Account</h2>

    <p id="signupError" class="error-message">
        <?php
        if (isset($_SESSION['signup_error'])) {
            echo $_SESSION['signup_error'];
            unset($_SESSION['signup_error']); 
        }
        ?>
    </p>

    <div class="btn-group">
      <button onclick="showForm('learner')">I am a Learner</button>
      <button onclick="showForm('educator')">I am an Educator</button>
    </div>

    <form id="learnerForm" action="signup_process.php" method="POST" enctype="multipart/form-data">
      <input type="hidden" name="userType" value="learner">
      
      <label>First Name:</label>
      <input type="text" name="firstName" required>
      <label>Last Name:</label>
      <input type="text" name="lastName" required>
      <label>Email:</label>
      <input type="email" name="emailAddress" required>
      <label>Password:</label>
      <input type="password" name="password" required>
      <label>Profile Image (optional):</label>
      <input type="file" name="photo" accept="image/*">
      <button type="submit">Sign Up as Learner</button>
    </form>

    <form id="educatorForm" class="hidden" action="signup_process.php" method="POST" enctype="multipart/form-data">
      <input type="hidden" name="userType" value="educator">
      
      <label>First Name:</label>
      <input type="text" name="firstName" required>
      <label>Last Name:</label>
      <input type="text" name="lastName" required>
      <label>Email:</label>
      <input type="email" name="emailAddress" required>
      <label>Password:</label>
      <input type="password" name="password" required>
      <label>Profile Image (optional):</label>
      <input type="file" name="photo" accept="image/*">
      
      <label>Topics:</label>
      <div class="checkbox-group">
        <label><input type="checkbox" name="topics[]" value="1"> HTML</label>
        <label><input type="checkbox" name="topics[]" value="2"> CSS</label>
        <label><input type="checkbox" name="topics[]" value="3"> Python</label>
        <label><input type="checkbox" name="topics[]" value="4"> Java</label>
        </div>
      <button type="submit">Sign Up as Educator</button>
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