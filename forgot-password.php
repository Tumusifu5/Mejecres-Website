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
            // Check if admin exists
            $stmt = $conn->prepare("SELECT id, username, email FROM admins WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            $admin = $result->fetch_assoc();

            if ($admin) {
                // Generate reset token
                $reset_token = bin2hex(random_bytes(32));
                $expires_at = date('Y-m-d H:i:s', strtotime('+1 hour'));

                // Store reset token in database
                $update_stmt = $conn->prepare("UPDATE admins SET reset_token = ?, reset_expires = ? WHERE id = ?");
                $update_stmt->bind_param("ssi", $reset_token, $expires_at, $admin['id']);

                if ($update_stmt->execute()) {
                    // Create reset link
                    $reset_link = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/reset-password-admin.php?token=" . $reset_token;

                    try {
                        $mail = new PHPMailer(true);

                        // SMTP settings (Gmail)
                        $mail->isSMTP();
                        $mail->Host       = 'smtp.gmail.com';
                        $mail->SMTPAuth   = true;
                        $mail->Username   = 'tumusifueric032@gmail.com'; // Your Gmail
                        $mail->Password   = 'ehva yvfb vxvm bsip';      // 16-character app password
                        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                        $mail->Port       = 587;

                        // Recipients
                        $mail->setFrom('noreply@mejecres-school.com', 'MEJECRES SCHOOL');
                        $mail->addAddress($email, $admin['username']);

                        // Email content
                        $mail->isHTML(true);
                        $mail->Subject = 'MEJECRES SCHOOL - Admin Password Reset';
                        $mail->Body = "
                            <h2>MEJECRES SCHOOL - Admin Password Reset</h2>
                            <p>Hello <strong>" . htmlspecialchars($admin['username']) . "</strong>,</p>
                            <p>You requested to reset your admin password. Click the button below to reset:</p>
                            <p style='text-align: center; margin: 20px 0;'>
                                <a href='$reset_link' style='background-color: #007bff; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; font-size: 16px;'>Reset Password</a>
                            </p>
                            <p>Or copy and paste this link into your browser:</p>
                            <p style='background: #f8f9fa; padding: 12px; border-radius: 5px; border: 1px solid #ddd; word-break: break-all; font-family: monospace;'>$reset_link</p>
                            <p><strong>⚠️ This link will expire in 1 hour.</strong></p>
                            <p>If you didn't request this, ignore this email.</p>
                            <br><p>Best regards,<br>MEJECRES SCHOOL</p>
                        ";

                        $mail->send();
                        $message = '✅ Password reset link sent! Check your email inbox (and spam folder).';
                        $message_type = 'success';
                    } catch (Exception $e) {
                        // Show link if email fails
                        $message = '📧 <strong>Password Reset Link Generated</strong>';
                        $message .= "<br><br><div style='background: #e7f3ff; padding: 15px; border-radius: 8px; border: 1px solid #b3d9ff;'>";
                        $message .= "<strong>Click this link to reset your password:</strong><br>";
                        $message .= "<a href='$reset_link' style='color: #007bff; word-break: break-all; font-size: 14px;'>$reset_link</a>";
                        $message .= "<br><br><small><strong>⚠️ Important:</strong> Link expires in 1 hour.</small></div>";
                        $message_type = 'info';
                    }
                } else {
                    $message = 'Error generating reset token. Try again.';
                    $message_type = 'error';
                }
            } else {
                $message = 'No admin found with that email address.';
                $message_type = 'error';
            }
        } catch (mysqli_sql_exception $e) {
            $message = 'Database error. Please try again.';
            $message_type = 'error';
        }
    }
}
?>

<!-- HTML Content -->
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Forgot Password - Admin - MEJECRES SCHOOL</title>
<link rel="icon" href="logo.jpg" type="image/x-icon">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
<style>
:root { --primary-1: #023eaa; --primary-2: #0b4bd8; --bg: #f4f6f8; --success: #28a745; --danger: #dc3545; }
body { font-family: 'Poppins', sans-serif; display: flex; justify-content: center; align-items: center; min-height: 100vh; background: var(--bg); padding: 20px; }
.login-container { background: white; border-radius: 15px; box-shadow: 0 15px 35px rgba(0,0,0,0.1); width: 100%; max-width: 450px; overflow: hidden; }
.login-header { background: linear-gradient(135deg, var(--primary-1), var(--primary-2)); color: white; padding: 30px; text-align: center; }
.login-header h2 { font-size: 18px; font-weight: 400; opacity: 0.9; }
.login-body { padding: 40px; }
.form-group { margin-bottom: 20px; }
.form-group label { display: block; margin-bottom: 8px; font-weight: 600; color: var(--primary-1); }
.form-control { width: 100%; padding: 12px 15px; border: 2px solid #e1e5e9; border-radius: 8px; font-size: 14px; background: #f8f9fa; }
.form-control:focus { outline: none; border-color: var(--primary-2); background: white; }
.btn { width: 100%; padding: 12px; background: linear-gradient(135deg, var(--primary-1), var(--primary-2)); color: white; border: none; border-radius: 8px; cursor: pointer; font-size: 16px; font-weight: 600; }
.login-links { text-align: center; margin-top: 20px; }
.login-links a { color: var(--primary-2); text-decoration: none; }
.alert { padding: 12px 15px; border-radius: 8px; margin-bottom: 20px; font-weight: 500; }
.alert-success { background: #d4edda; color: var(--success); border: 1px solid #c3e6cb; }
.alert-error { background: #f8d7da; color: var(--danger); border: 1px solid #f5c6cb; }
.alert-info { background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
</style>
</head>
<body>
<div class="login-container">
    <div class="login-header">
        <h2>Admin Password Recovery</h2>
    </div>
    <div class="login-body">
        <?php if ($message): ?>
        <div class="alert alert-<?php echo $message_type === 'success' ? 'success' : ($message_type === 'info' ? 'info' : 'error'); ?>">
            <?php echo $message; ?>
        </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label for="email">📧 Email Address</label>
                <input type="email" id="email" name="email" class="form-control" placeholder="Enter your email" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
            </div>
            <button type="submit" class="btn">Send Reset Link</button>
        </form>

        <div class="login-links">
            <a href="admin-login.php">← Back to Admin Login</a>
        </div>
    </div>
</div>
</body>
</html>
