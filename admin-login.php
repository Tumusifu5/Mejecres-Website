<?php
session_start();
include 'connection.php';

$err = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (!$username || !$password) {
        $err = "Please fill both fields.";
    } else {
        $stmt = $conn->prepare("SELECT id, password FROM admins WHERE username = ? LIMIT 1");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($res && $res->num_rows === 1) {
            $row = $res->fetch_assoc();
            $dbPassword = $row['password'];

            // Check if password is hashed (bcrypt starts with $2y$ or $2a$)
            if (str_starts_with($dbPassword, '$2y$') || str_starts_with($dbPassword, '$2a$')) {
                // Password is hashed
                $valid = password_verify($password, $dbPassword);
            } else {
                // Password is plain text (old account)
                $valid = ($password === $dbPassword);

                // If login succeeds with old plain password, re-hash it automatically
                if ($valid) {
                    $newHash = password_hash($password, PASSWORD_BCRYPT);
                    $update = $conn->prepare("UPDATE admins SET password = ? WHERE id = ?");
                    $update->bind_param("si", $newHash, $row['id']);
                    $update->execute();
                }
            }

            if ($valid) {
                $_SESSION['admin_id'] = $row['id'];
                $_SESSION['admin_username'] = $username;
                header("Location: admin.php");
                exit;
            } else {
                $err = "Invalid password.";
            }

        } else {
            $err = "Admin not found.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Admin Login - MEJECRES SCHOOL</title>
<link rel="icon" href="logo.jpg" type="image/x-icon">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
:root {
  --primary-1: #023eaa;
  --primary-2: #0b4bd8;
  --primary-light: #e6eeff;
  --accent: #ffd700;
  --bg: #f8fafc;
  --text: #333333;
  --text-light: #666666;
  --border: #e1e5eb;
  --success: #10b981;
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
  background: linear-gradient(135deg, var(--primary-1), var(--primary-2));
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
  background: linear-gradient(135deg, var(--primary-1), var(--primary-2));
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
  color: var(--primary-1);
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
  color: var(--primary-1);
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
  border-color: var(--primary-2);
  box-shadow: 0 0 0 3px rgba(11, 75, 216, 0.1);
}

.btn-login {
  width: 100%;
  padding: 15px;
  background: linear-gradient(135deg, var(--primary-1), var(--primary-2));
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
  color: var(--primary-2);
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
  color: var(--primary-1);
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

.success {
  background: #d1fae5;
  color: var(--success);
  padding: 12px 15px;
  border-radius: 8px;
  margin-bottom: 20px;
  border-left: 4px solid var(--success);
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
      <p>Administrative Portal</p>
    </div>
    
    <div class="features">
      <div class="feature">
        <i class="fas fa-shield-alt"></i>
        <p>Secure administrative access</p>
      </div>
      <div class="feature">
        <i class="fas fa-user-graduate"></i>
        <p>Manage student records</p>
      </div>
      <div class="feature">
        <i class="fas fa-chalkboard-teacher"></i>
        <p>Oversee teacher accounts</p>
      </div>
      <div class="feature">
        <i class="fas fa-chart-line"></i>
        <p>View school analytics</p>
      </div>
    </div>
  </div>
  
  <div class="login-right">
    <div class="login-header">
      <h2>Admin Login</h2>
      <p>Enter your credentials to access the admin dashboard</p>
    </div>
    
    <?php if ($err): ?>
      <div class="error">
        <i class="fas fa-exclamation-circle"></i>
        <?=htmlspecialchars($err)?>
      </div>
    <?php endif; ?>
    
    <?php if(isset($_GET['registered'])): ?>
      <div class="success">
        <i class="fas fa-check-circle"></i>
        Registration successful. Please login.
      </div>
    <?php endif; ?>

    <form method="post" action="admin-login.php" class="login-form">
      <div class="form-group">
        <label for="username">Username</label>
        <div class="input-with-icon">
          <i class="fas fa-user"></i>
          <input type="text" name="username" id="username" placeholder="Enter your username" required>
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
        Login to Dashboard
      </button>
    </form>
    
    <div class="links">
      <a href="forgot-password.php">
        <i class="fas fa-key"></i>
        Forgot Password?
      </a>
      <a href="admin-register.php">
        <i class="fas fa-user-plus"></i>
        Create Admin Account
      </a>
    </div>
  </div>
</div>

</body>
</html>