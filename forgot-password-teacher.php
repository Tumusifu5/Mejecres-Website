<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

include_once 'connection.php';

// Include PHPMailer
require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    
    if (empty($email)) {
        $message = 'Please enter your email address';
        $message_type = 'error';
    } else {
        try {
            // Check if teacher exists
            $stmt = $conn->prepare("SELECT id, fullname, email FROM teachers WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            $teacher = $result->fetch_assoc();
            
            if ($teacher) {
                // Generate reset token
                $reset_token = bin2hex(random_bytes(32));
                $expires_at = date('Y-m-d H:i:s', strtotime('+1 hour'));
                
                // Store reset token in database
                $update_stmt = $conn->prepare("UPDATE teachers SET reset_token = ?, reset_expires = ? WHERE id = ?");
                $update_stmt->bind_param("ssi", $reset_token, $expires_at, $teacher['id']);
                
                if ($update_stmt->execute()) {
                    // Create reset link
                    $reset_link = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/reset-password-teacher.php?token=" . $reset_token;
                    
                    // Try to send email with PHPMailer
                    try {
                        $mail = new PHPMailer(true);
                        
                        // Server settings for Gmail
                        $mail->isSMTP();
                        $mail->Host       = 'smtp.gmail.com';
                        $mail->SMTPAuth   = true;
                        $mail->Username   = 'tumusifueric032@gmail.com'; // REPLACE WITH YOUR GMAIL
                        $mail->Password   = 'ehva yvfb vxvm bsip';    // REPLACE WITH 16-CHAR APP PASSWORD
                        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                        $mail->Port       = 587;
                        
                        // Recipients
                        $mail->setFrom('noreply@mejecres-school.com', 'MEJECRES SCHOOL');
                        $mail->addAddress($email, $teacher['fullname']);
                        
                        // Content
                        $mail->isHTML(true);
                        $mail->Subject = 'MEJECRES SCHOOL - Teacher Password Reset';
                        $mail->Body    = "
                            <h2>MEJECRES SCHOOL - Teacher Password Reset</h2>
                            <p>Hello <strong>" . htmlspecialchars($teacher['fullname']) . "</strong>,</p>
                            <p>You have requested to reset your teacher password. Click the link below to reset your password:</p>
                            <p style='text-align: center; margin: 20px 0;'>
                                <a href='$reset_link' style='background-color: #007bff; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; display: inline-block; font-size: 16px;'>Reset Password</a>
                            </p>
                            <p>Or copy and paste this link in your browser:</p>
                            <p style='background: #f8f9fa; padding: 12px; border-radius: 5px; border: 1px solid #ddd; word-break: break-all; font-family: monospace;'>
                                $reset_link
                            </p>
                            <p><strong>⚠️ Important: This link will expire in 1 hour.</strong></p>
                            <p>If you didn't request this reset, please ignore this email and your password will remain unchanged.</p>
                            <br>
                            <p>Best regards,<br>MEJECRES SCHOOL</p>
                        ";
                        
                        $mail->send();
                        $message = '✅ Password reset link has been sent to your email address! Please check your inbox (and spam folder).';
                        $message_type = 'success';
                        
                    } catch (Exception $e) {
                        // If email fails, show the reset link
                        $message = '📧 <strong>Password Reset Link Generated</strong>';
                        $message .= "<br><br><div style='background: #e7f3ff; padding: 15px; border-radius: 8px; border: 1px solid #b3d9ff;'>";
                        $message .= "<strong>Click this link to reset your password:</strong><br>";
                        $message .= "<a href='$reset_link' style='color: #007bff; word-break: break-all; font-size: 14px;'>$reset_link</a>";
                        $message .= "<br><br><small><strong>⚠️ Important:</strong> This link will expire in 1 hour.</small>";
                        $message .= "</div>";
                        $message_type = 'info';
                    }
                    
                } else {
                    $message = 'Error generating reset token. Please try again.';
                    $message_type = 'error';
                }
            } else {
                $message = 'No teacher found with that email address.';
                $message_type = 'error';
            }
        } catch (mysqli_sql_exception $e) {
            $message = 'Database error. Please try again.';
            $message_type = 'error';
        }
    }
}
?>

<!-- REST OF YOUR HTML REMAINS THE SAME -->
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Forgot Password - Teacher - MEJECRES SCHOOL</title>
<link rel="icon" href="logo.jpg" type="image/x-icon">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
<style>
:root {
  --primary-1: #023eaa;
  --primary-2: #0b4bd8;
  --accent: #ffd700;
  --bg: #f4f6f8;
  --card-bg: #ffffff;
  --muted: #666;
  --success: #28a745;
  --danger: #dc3545;
  --warning: #ffc107;
}

* {
  box-sizing: border-box;
  margin: 0;
  padding: 0;
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

.login-container {
  background: white;
  border-radius: 15px;
  box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
  overflow: hidden;
  width: 100%;
  max-width: 450px;
}

.login-header {
  background: linear-gradient(135deg, var(--primary-1), var(--primary-2));
  color: white;
  padding: 30px;
  text-align: center;
}

.brand {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 15px;
  margin-bottom: 15px;
}

.brand img {
  height: 60px;
  width: 60px;
  border-radius: 10px;
  object-fit: cover;
}

.brand h1 {
  font-size: 24px;
  font-weight: 700;
}

.login-header h2 {
  font-size: 18px;
  font-weight: 400;
  opacity: 0.9;
}

.login-body {
  padding: 40px;
}

.form-group {
  margin-bottom: 20px;
}

.form-group label {
  display: block;
  margin-bottom: 8px;
  font-weight: 600;
  color: var(--primary-1);
}

.form-control {
  width: 100%;
  padding: 12px 15px;
  border: 2px solid #e1e5e9;
  border-radius: 8px;
  font-size: 14px;
  transition: all 0.3s ease;
  background: #f8f9fa;
}

.form-control:focus {
  outline: none;
  border-color: var(--primary-2);
  background: white;
  box-shadow: 0 0 0 3px rgba(11, 75, 216, 0.1);
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
  transition: all 0.3s ease;
}

.btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 5px 15px rgba(11, 75, 216, 0.3);
}

.btn:active {
  transform: translateY(0);
}

.login-links {
  text-align: center;
  margin-top: 20px;
}

.login-links a {
  color: var(--primary-2);
  text-decoration: none;
  font-weight: 500;
  transition: color 0.3s ease;
}

.login-links a:hover {
  color: var(--primary-1);
  text-decoration: underline;
}

.alert {
  padding: 12px 15px;
  border-radius: 8px;
  margin-bottom: 20px;
  font-weight: 500;
}

.alert-success {
  background: #d4edda;
  color: var(--success);
  border: 1px solid #c3e6cb;
}

.alert-error {
  background: #f8d7da;
  color: var(--danger);
  border: 1px solid #f5c6cb;
}

.alert-info {
  background: #d1ecf1;
  color: #0c5460;
  border: 1px solid #bee5eb;
}

.instructions {
  background: #e7f3ff;
  border: 1px solid #b3d9ff;
  border-radius: 8px;
  padding: 15px;
  margin-bottom: 20px;
  font-size: 14px;
}

.instructions h4 {
  color: var(--primary-1);
  margin-bottom: 8px;
}

.instructions ul {
  padding-left: 20px;
}

.instructions li {
  margin-bottom: 5px;
}

@media (max-width: 480px) {
  .login-container {
    margin: 10px;
  }
  
  .login-body {
    padding: 30px 25px;
  }
  
  .login-header {
    padding: 25px 20px;
  }
  
  .brand {
    flex-direction: column;
    gap: 10px;
  }
  
  .brand h1 {
    font-size: 20px;
  }
}
</style>
</head>
<body>
<div class="login-container">
  <div class="login-header">
    <div class="brand">
      <img src="logo.jpg" alt="MEJECRES Logo">
      <h1>MEJECRES SCHOOL</h1>
    </div>
    <h2>Teacher Password Recovery</h2>
  </div>
  
  <div class="login-body">
    <?php if ($message): ?>
      <div class="alert alert-<?php echo $message_type === 'success' ? 'success' : ($message_type === 'info' ? 'info' : 'error'); ?>">
        <?php echo $message; ?>
      </div>
    <?php endif; ?>
    
    <div class="instructions">
      <h4>🔒 Password Reset Instructions</h4>
      <ul>
        <li>Enter your registered email address</li>
        <li>We'll send you a password reset link via email</li>
        <li>Click the link to create a new password</li>
        <li>The link expires in 1 hour</li>
      </ul>
    </div>
    
    <form method="POST" action="">
      <div class="form-group">
        <label for="email">📧 Email Address</label>
        <input type="email" id="email" name="email" class="form-control" placeholder="Enter your email address" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
      </div>
      
      <button type="submit" class="btn">Send Reset Link</button>
    </form>
    
    <div class="login-links">
      <a href="teacher-login.php">← Back to Teacher Login</a>
    </div>
  </div>
</div>

<script>
// Auto-hide success message after 10 seconds
setTimeout(() => {
  const successAlert = document.querySelector('.alert-success');
  if (successAlert) {
    successAlert.style.display = 'none';
  }
}, 10000);

// Focus on email field
document.getElementById('email')?.focus();
</script>
</body>
</html>