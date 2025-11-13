<?php
// teacher-login.php
session_start();
include 'connection.php';
$err = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!$email || !$password) {
        $err = "Fill both fields.";
    } else {
        // Include assigned_class in the SELECT
        $stmt = $conn->prepare("SELECT id, password, fullname, assigned_class FROM teachers WHERE email = ? LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($res && $res->num_rows === 1) {
            $row = $res->fetch_assoc();
            if (password_verify($password, $row['password'])) {
                // Store teacher info in session
                $_SESSION['teacher_id'] = $row['id'];
                $_SESSION['teacher_name'] = $row['fullname'];
                $_SESSION['teacher_class'] = $row['assigned_class']; // <-- new line

                header("Location: teachers.php");
                exit;
            } else {
                $err = "Invalid password.";
            }
        } else {
            $err = "Teacher not found.";
        }
    }
}
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Teacher Login - MEJECRES SCHOOL</title>
<link rel="icon" href="logo.jpg" type="image/x-icon">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
:root {
  --primary-1: #023eaa;
  --primary-2: #0b4bd8;
  --accent: #654310ff;
  --accent-light: #6b4205ff;
  --bg: #f8fafc;
  --text: #333333;
  --text-light: #666666;
  --border: #e1e5eb;
  --error: #ef4444;
  --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
  --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
}

* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}

body {
  font-family: 'Poppins', sans-serif;
  background: linear-gradient(135deg, #92774fff, #c88d34ff);
  color: var(--text);
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: 100vh;
  padding: 20px;
}

.login-container {
  display: flex;
  width: 100%;
  max-width: 1000px;
  background: white;
  border-radius: 20px;
  overflow: hidden;
  box-shadow: var(--shadow-lg);
  min-height: 600px;
}

.login-left {
  flex: 1;
  background: linear-gradient(135deg, var(--accent), var(--accent-light));
  color: white;
  padding: 50px 40px;
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  text-align: center;
  position: relative;
  overflow: hidden;
}

.login-left::before {
  content: '';
  position: absolute;
  top: -50%;
  right: -50%;
  width: 100%;
  height: 100%;
  background: rgba(255, 255, 255, 0.1);
  border-radius: 50%;
  transform: scale(1.2);
}

.brand {
  display: flex;
  flex-direction: column;
  align-items: center;
  margin-bottom: 40px;
  z-index: 1;
}

.brand img {
  height: 90px;
  width: 90px;
  border-radius: 12px;
  object-fit: cover;
  border: 3px solid rgba(255, 255, 255, 0.2);
  margin-bottom: 15px;
}

.brand h1 {
  font-size: 24px;
  font-weight: 700;
  margin-bottom: 5px;
}

.brand p {
  font-size: 16px;
  opacity: 0.9;
}

.features {
  text-align: left;
  margin-top: 30px;
  z-index: 1;
}

.feature {
  display: flex;
  align-items: center;
  margin-bottom: 20px;
}

.feature i {
  font-size: 20px;
  margin-right: 15px;
  background: rgba(255, 255, 255, 0.2);
  width: 40px;
  height: 40px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
}

.feature p {
  margin: 0;
  font-size: 15px;
}

.login-right {
  flex: 1;
  padding: 50px 40px;
  display: flex;
  flex-direction: column;
  justify-content: center;
}

.login-header {
  margin-bottom: 30px;
}

.login-header h2 {
  font-size: 28px;
  color: var(--accent);
  margin-bottom: 10px;
  font-weight: 700;
}

.login-header p {
  color: var(--text-light);
  font-size: 16px;
}

.login-form {
  width: 100%;
}

.form-group {
  margin-bottom: 20px;
  position: relative;
}

.form-group label {
  display: block;
  margin-bottom: 8px;
  font-weight: 500;
  color: var(--accent);
}

.input-with-icon {
  position: relative;
}

.input-with-icon i {
  position: absolute;
  left: 15px;
  top: 50%;
  transform: translateY(-50%);
  color: var(--text-light);
  font-size: 18px;
}

.input-with-icon input {
  width: 100%;
  padding: 15px 15px 15px 50px;
  border: 1px solid var(--border);
  border-radius: 10px;
  font-size: 16px;
  transition: all 0.3s ease;
  background: white;
}

.input-with-icon input:focus {
  outline: none;
  border-color: var(--accent);
  box-shadow: 0 0 0 3px rgba(255, 152, 0, 0.1);
}

.btn-login {
  width: 100%;
  padding: 15px;
  background: linear-gradient(135deg, var(--accent), var(--accent-light));
  color: white;
  border: none;
  border-radius: 10px;
  font-size: 16px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
  margin-top: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
}

.btn-login:hover {
  transform: translateY(-2px);
  box-shadow: var(--shadow-lg);
}

.links {
  margin-top: 25px;
  text-align: center;
}

.links a {
  display: block;
  color: var(--accent);
  text-decoration: none;
  margin-bottom: 10px;
  font-weight: 500;
  transition: color 0.3s ease;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
}

.links a:hover {
  color: #e65100;
}

.error {
  background: #fee2e2;
  color: var(--error);
  padding: 12px 15px;
  border-radius: 8px;
  margin-bottom: 20px;
  border-left: 4px solid var(--error);
  display: flex;
  align-items: center;
  gap: 10px;
}

@media (max-width: 768px) {
  .login-container {
    flex-direction: column;
    max-width: 450px;
  }
  
  .login-left, .login-right {
    padding: 30px 25px;
  }
  
  .login-left {
    border-radius: 20px 20px 0 0;
  }
  
  .login-right {
    border-radius: 0 0 20px 20px;
  }
}
</style>
</head>
<body>

<div class="login-container">
  <div class="login-left">
    <div class="brand">
      <img src="logo.jpg" alt="MEJECRES Logo">
      <h1>MEJECRES SCHOOL</h1>
      <p>Teacher Portal</p>
    </div>
    
    <div class="features">
      <div class="feature">
        <i class="fas fa-chalkboard-teacher"></i>
        <p>Access your teaching dashboard</p>
      </div>
      <div class="feature">
        <i class="fas fa-user-graduate"></i>
        <p>Manage your class students</p>
      </div>
      <div class="feature">
        <i class="fas fa-tasks"></i>
        <p>Track assignments and grades</p>
      </div>
      <div class="feature">
        <i class="fas fa-calendar-alt"></i>
        <p>View your teaching schedule</p>
      </div>
    </div>
  </div>
  
  <div class="login-right">
    <div class="login-header">
      <h2>Teacher Login</h2>
      <p>Enter your credentials to access the teacher portal</p>
    </div>
    
    <?php if ($err): ?>
      <div class="error">
        <i class="fas fa-exclamation-circle"></i>
        <?=htmlspecialchars($err)?>
      </div>
    <?php endif; ?>

    <form method="post" action="teacher-login.php" class="login-form">
      <div class="form-group">
        <label for="email">Email Address</label>
        <div class="input-with-icon">
          <i class="fas fa-envelope"></i>
          <input type="email" name="email" id="email" placeholder="Enter your email" required>
        </div>
      </div>
      
      <div class="form-group">
        <label for="password">Password</label>
        <div class="input-with-icon">
          <i class="fas fa-lock"></i>
          <input type="password" name="password" id="password" placeholder="Enter your password" required>
        </div>
      </div>
      
      <button type="submit" class="btn-login">
        <i class="fas fa-sign-in-alt"></i>
        Login to Teacher Portal
      </button>
    </form>
    
    <div class="links">
      <a href="forgot-password-teacher.php">
        <i class="fas fa-key"></i>
        Forgot Password?
      </a>
    </div>
  </div>
</div>

</body>
</html>