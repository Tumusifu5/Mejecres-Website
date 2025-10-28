<?php
// admin-register.php
session_start();

// Database configuration and connection (all internal)
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'mejecres_db');

// Create connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create admins table if it doesn't exist
$createTable = "CREATE TABLE IF NOT EXISTS admins (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
$conn->query($createTable);

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm'] ?? '';

    // Basic empty field check
    if (!$username || !$email || !$password || !$confirm) {
        $errors[] = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid email address.";
    } elseif ($password !== $confirm) {
        $errors[] = "Passwords do not match.";
    } else {
        // Strong password check
        if (strlen($password) < 8) {
            $errors[] = "Password must be at least 8 characters long.";
        }
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = "Password must include at least one uppercase letter.";
        }
        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = "Password must include at least one lowercase letter.";
        }
        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = "Password must include at least one number.";
        }
        if (!preg_match('/[\W_]/', $password)) {
            $errors[] = "Password must include at least one special character.";
        }
    }

    if (empty($errors)) {
        // Check if username or email already exists
        $stmt = $conn->prepare("SELECT id FROM admins WHERE username = ? OR email = ? LIMIT 1");
        $stmt->bind_param("ss", $username, $email);
        $stmt->execute();
        $stmt->store_result();
        
        if ($stmt->num_rows > 0) {
            // Check which one exists
            $checkStmt = $conn->prepare("SELECT username, email FROM admins WHERE username = ? OR email = ?");
            $checkStmt->bind_param("ss", $username, $email);
            $checkStmt->execute();
            $result = $checkStmt->get_result();
            $existing = $result->fetch_assoc();
            
            if ($existing['username'] === $username) {
                $errors[] = "Username already exists.";
            } else {
                $errors[] = "Email already exists.";
            }
        } else {
            // Hash password and insert
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $ins = $conn->prepare("INSERT INTO admins (username, email, password) VALUES (?, ?, ?)");
            $ins->bind_param("sss", $username, $email, $hash);
            if ($ins->execute()) {
                $success = "Admin registered successfully!";
                // Clear form fields
                $username = $email = '';
            } else {
                $errors[] = "Registration failed. Please try again.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Registration - MEJECRES SCHOOL</title>
<link rel="icon" href="logo.jpg" type="image/x-icon">
<style>
  body {
    font-family: Arial, sans-serif;
    background: linear-gradient(135deg, #0073e6, #004080);
    color: white;
    display: flex;
    justify-content: center;
    align-items: center;
    height: 100vh;
    margin: 0;
  }
  .register-box {
    background: white;
    color: #004080;
    padding: 30px;
    border-radius: 15px;
    box-shadow: 0 0 10px rgba(0,0,0,0.3);
    width: 350px;
    text-align: center;
  }
  h2 { margin-bottom: 20px; }
  input { 
    width: 90%; 
    padding: 10px; 
    margin: 10px 0; 
    border-radius: 5px; 
    border: 1px solid #ccc; 
  }
  button { 
    background: #0073e6; 
    color: white; 
    border: none; 
    padding: 10px 20px; 
    border-radius: 5px; 
    cursor: pointer; 
    font-weight: bold;
    width: 95%;
    margin-top: 10px;
  }
  button:hover { background: #005bb5; }
  a { 
    color: #004080; 
    text-decoration: none; 
    font-weight: bold; 
  }
  a:hover { text-decoration: underline; }
  p { margin-top: 15px; }
  .error { 
    color: #b70505; 
    margin-bottom: 10px; 
    text-align:left;
    background: #ffe6e6;
    padding: 8px;
    border-radius: 4px;
    border: 1px solid #ffb3b3;
  }
  .success { color: #0a7a0a; margin-bottom: 10px; }

  /* Toast Styles */
  .toast {
    position: fixed;
    top: 20px;
    right: 20px;
    padding: 15px 20px;
    border-radius: 5px;
    color: white;
    font-weight: bold;
    z-index: 1000;
    opacity: 0;
    transform: translateX(100%);
    transition: all 0.3s ease;
    max-width: 300px;
  }
  
  .toast.show {
    opacity: 1;
    transform: translateX(0);
  }
  
  .toast.success {
    background: #28a745;
    border-left: 5px solid #1e7e34;
  }
  
  .toast.error {
    background: #dc3545;
    border-left: 5px solid #c82333;
  }
  
  .toast-container {
    position: fixed;
    top: 0;
    right: 0;
    padding: 20px;
    z-index: 1000;
  }
</style>
</head>
<body>
  <!-- Toast Container -->
  <div class="toast-container">
    <?php if ($success): ?>
      <div class="toast success" id="successToast">
        ✅ <?php echo htmlspecialchars($success); ?>
      </div>
    <?php endif; ?>
    
    <?php if (!empty($errors)): ?>
      <div class="toast error" id="errorToast">
        ❌ <?php echo htmlspecialchars($errors[0]); ?>
      </div>
    <?php endif; ?>
  </div>

  <div class="register-box">
    <h2>Register New Admin</h2>

    <form method="post" action="admin-register.php" novalidate>
      <input type="text" name="username" id="username" placeholder="Create Username" value="<?php echo htmlspecialchars($username ?? ''); ?>" required>
      <input type="email" name="email" id="email" placeholder="Email Address" value="<?php echo htmlspecialchars($email ?? ''); ?>" required>
      <input type="password" name="password" id="password" placeholder="Create Password" required>
      <input type="password" name="confirm" id="confirm" placeholder="Confirm Password" required>
      <button type="submit">Register</button>
      <p>Already have an account? <a href="admin-login.php">Login here</a></p>
    </form>
  </div>

  <script>
    // Toast notification functionality
    document.addEventListener('DOMContentLoaded', function() {
      const successToast = document.getElementById('successToast');
      const errorToast = document.getElementById('errorToast');
      
      function showToast(toast) {
        if (toast) {
          // Show toast
          setTimeout(() => {
            toast.classList.add('show');
          }, 100);
          
          // Hide toast after 5 seconds
          setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => {
              if (toast.parentNode) {
                toast.parentNode.removeChild(toast);
              }
            }, 300);
          }, 5000);
          
          // Add click to dismiss
          toast.addEventListener('click', function() {
            this.classList.remove('show');
            setTimeout(() => {
              if (this.parentNode) {
                this.parentNode.removeChild(this);
              }
            }, 300);
          });
        }
      }
      
      // Show toasts
      showToast(successToast);
      showToast(errorToast);
      
      // Clear form on successful registration
      <?php if ($success): ?>
        setTimeout(() => {
          document.getElementById('username').value = '';
          document.getElementById('email').value = '';
          document.getElementById('password').value = '';
          document.getElementById('confirm').value = '';
        }, 100);
      <?php endif; ?>
      
      // Real-time password validation
      const password = document.getElementById('password');
      const confirm = document.getElementById('confirm');
      
      function validatePassword() {
        const pass = password.value;
        const conf = confirm.value;
        
        // Remove previous validation styles
        password.style.borderColor = '';
        confirm.style.borderColor = '';
        
        if (conf && pass !== conf) {
          confirm.style.borderColor = '#dc3545';
        } else if (conf && pass === conf) {
          confirm.style.borderColor = '#28a745';
        }
      }
      
      password.addEventListener('input', validatePassword);
      confirm.addEventListener('input', validatePassword);
    });
  </script>
</body>
</html>