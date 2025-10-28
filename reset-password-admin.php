<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

include_once 'connection.php';

$message = '';
$message_type = '';

if (!isset($_GET['token'])) {
    die('Invalid password reset link.');
}

$token = $_GET['token'];

// Check if token exists and is not expired
$stmt = $conn->prepare("SELECT id, username, reset_expires FROM admins WHERE reset_token = ?");
$stmt->bind_param("s", $token);
$stmt->execute();
$result = $stmt->get_result();
$admin = $result->fetch_assoc();

if (!$admin) {
    die('Invalid or expired password reset link.');
}

if (strtotime($admin['reset_expires']) < time()) {
    die('This password reset link has expired.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = trim($_POST['password'] ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? '');

    if (empty($password) || empty($confirm_password)) {
        $message = 'Please enter both password fields.';
        $message_type = 'error';
    } elseif ($password !== $confirm_password) {
        $message = 'Passwords do not match.';
        $message_type = 'error';
    } else {
        // Hash the password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Update the password in DB and remove token
        $update_stmt = $conn->prepare("UPDATE admins SET password = ?, reset_token = NULL, reset_expires = NULL WHERE id = ?");
        $update_stmt->bind_param("si", $hashed_password, $admin['id']);

        if ($update_stmt->execute()) {
            $message = '✅ Your password has been reset successfully. You can now <a href="admin-login.php">login</a>.';
            $message_type = 'success';
        } else {
            $message = 'Error updating password. Please try again.';
            $message_type = 'error';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Reset Password - Admin - MEJECRES SCHOOL</title>
<link rel="icon" href="logo.jpg" type="image/x-icon">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
<style>
:root {
  --primary-1: #023eaa;
  --primary-2: #0b4bd8;
  --success: #28a745;
  --danger: #dc3545;
}
body {
  font-family: 'Poppins', sans-serif;
  background: linear-gradient(135deg, var(--primary-1), var(--primary-2));
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 20px;
}
.reset-container {
  background: white;
  border-radius: 15px;
  box-shadow: 0 15px 35px rgba(0,0,0,0.1);
  width: 100%;
  max-width: 400px;
  padding: 30px;
}
.reset-container h2 {
  text-align: center;
  margin-bottom: 25px;
  color: var(--primary-1);
}
.form-group {
  margin-bottom: 20px;
}
.form-group label {
  display: block;
  margin-bottom: 8px;
  font-weight: 600;
}
.form-control {
  width: 100%;
  padding: 12px;
  border: 2px solid #e1e5e9;
  border-radius: 8px;
  font-size: 14px;
}
.btn {
  width: 100%;
  padding: 12px;
  background: linear-gradient(135deg, var(--primary-1), var(--primary-2));
  color: white;
  border: none;
  border-radius: 8px;
  font-size: 16px;
  font-weight: 600;
  cursor: pointer;
}
.alert {
  padding: 12px;
  border-radius: 8px;
  margin-bottom: 20px;
  font-weight: 500;
}
.alert-success { background: #d4edda; color: var(--success); border: 1px solid #c3e6cb; }
.alert-error { background: #f8d7da; color: var(--danger); border: 1px solid #f5c6cb; }
</style>
</head>
<body>
<div class="reset-container">
    <h2>Reset Admin Password</h2>
    <?php if ($message): ?>
      <div class="alert <?php echo $message_type === 'success' ? 'alert-success' : 'alert-error'; ?>">
        <?php echo $message; ?>
      </div>
    <?php endif; ?>
    <form method="POST" action="">
        <div class="form-group">
            <label for="password">New Password</label>
            <input type="password" id="password" name="password" class="form-control" required>
        </div>
        <div class="form-group">
            <label for="confirm_password">Confirm New Password</label>
            <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
        </div>
        <button type="submit" class="btn">Reset Password</button>
    </form>
</div>
</body>
</html>
