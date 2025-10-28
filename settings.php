<?php
session_start();
if (!isset($_SESSION['admin_username'])) {
    header('Location: admin-login.php');
    exit;
}

// DB connection
$servername = "localhost";
$dbuser = "root";
$dbpass = "";
$dbname = "mejecres_db";
$conn = new mysqli($servername, $dbuser, $dbpass, $dbname);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);
$conn->set_charset("utf8mb4");

$messages = [];

// Get current settings
$settings = [];
$settingsResult = $conn->query("SELECT setting_key, setting_value FROM settings");
if ($settingsResult) {
    while ($row = $settingsResult->fetch_assoc()) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // General Settings
    if (isset($_POST['save_general'])) {
        $school_name = trim($_POST['school_name'] ?? '');
        $school_email = trim($_POST['school_email'] ?? '');
        $school_phone = trim($_POST['school_phone'] ?? '');
        $school_address = trim($_POST['school_address'] ?? '');
        $academic_year = trim($_POST['academic_year'] ?? '');
        
        $this->saveSetting($conn, 'school_name', $school_name);
        $this->saveSetting($conn, 'school_email', $school_email);
        $this->saveSetting($conn, 'school_phone', $school_phone);
        $this->saveSetting($conn, 'school_address', $school_address);
        $this->saveSetting($conn, 'academic_year', $academic_year);
        
        $messages[] = ['type' => 'success', 'text' => 'General settings updated successfully.'];
    }
    
    // Security Settings
    if (isset($_POST['save_security'])) {
        $session_timeout = intval($_POST['session_timeout'] ?? 60);
        $max_login_attempts = intval($_POST['max_login_attempts'] ?? 5);
        $password_min_length = intval($_POST['password_min_length'] ?? 8);
        $require_strong_password = isset($_POST['require_strong_password']) ? 1 : 0;
        $enable_2fa = isset($_POST['enable_2fa']) ? 1 : 0;
        
        $this->saveSetting($conn, 'session_timeout', $session_timeout);
        $this->saveSetting($conn, 'max_login_attempts', $max_login_attempts);
        $this->saveSetting($conn, 'password_min_length', $password_min_length);
        $this->saveSetting($conn, 'require_strong_password', $require_strong_password);
        $this->saveSetting($conn, 'enable_2fa', $enable_2fa);
        
        $messages[] = ['type' => 'success', 'text' => 'Security settings updated successfully.'];
    }
    
    // Appearance Settings
    if (isset($_POST['save_appearance'])) {
        $theme_color = trim($_POST['theme_color'] ?? '#4361ee');
        $sidebar_style = trim($_POST['sidebar_style'] ?? 'default');
        $enable_dark_mode = isset($_POST['enable_dark_mode']) ? 1 : 0;
        $logo_url = trim($_POST['logo_url'] ?? '');
        
        $this->saveSetting($conn, 'theme_color', $theme_color);
        $this->saveSetting($conn, 'sidebar_style', $sidebar_style);
        $this->saveSetting($conn, 'enable_dark_mode', $enable_dark_mode);
        $this->saveSetting($conn, 'logo_url', $logo_url);
        
        $messages[] = ['type' => 'success', 'text' => 'Appearance settings updated successfully.'];
    }
    
    // Change Password
    if (isset($_POST['change_password'])) {
        $current_password = trim($_POST['current_password'] ?? '');
        $new_password = trim($_POST['new_password'] ?? '');
        $confirm_password = trim($_POST['confirm_password'] ?? '');
        
        // Verify current password
        $stmt = $conn->prepare("SELECT password FROM admins WHERE username = ?");
        $stmt->bind_param("s", $_SESSION['admin_username']);
        $stmt->execute();
        $result = $stmt->get_result();
        $admin = $result->fetch_assoc();
        $stmt->close();
        
        if ($admin && password_verify($current_password, $admin['password'])) {
            if ($new_password === $confirm_password) {
                if (strlen($new_password) >= 8) {
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $stmt = $conn->prepare("UPDATE admins SET password = ? WHERE username = ?");
                    $stmt->bind_param("ss", $hashed_password, $_SESSION['admin_username']);
                    if ($stmt->execute()) {
                        $messages[] = ['type' => 'success', 'text' => 'Password changed successfully.'];
                    } else {
                        $messages[] = ['type' => 'error', 'text' => 'Error updating password.'];
                    }
                    $stmt->close();
                } else {
                    $messages[] = ['type' => 'error', 'text' => 'New password must be at least 8 characters long.'];
                }
            } else {
                $messages[] = ['type' => 'error', 'text' => 'New passwords do not match.'];
            }
        } else {
            $messages[] = ['type' => 'error', 'text' => 'Current password is incorrect.'];
        }
    }
    
    // Backup Database
    if (isset($_POST['backup_database'])) {
        $backup_result = $this->backupDatabase($conn, $dbname);
        if ($backup_result) {
            $messages[] = ['type' => 'success', 'text' => 'Database backup created successfully.'];
        } else {
            $messages[] = ['type' => 'error', 'text' => 'Error creating database backup.'];
        }
    }
}

// Helper function to save settings
function saveSetting($conn, $key, $value) {
    $stmt = $conn->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) 
                           ON DUPLICATE KEY UPDATE setting_value = ?");
    $stmt->bind_param("sss", $key, $value, $value);
    $stmt->execute();
    $stmt->close();
}

// Helper function to backup database
function backupDatabase($conn, $dbname) {
    $backup_file = 'backups/backup_' . date('Y-m-d_H-i-s') . '.sql';
    $backup_dir = dirname(__FILE__) . '/backups';
    
    if (!is_dir($backup_dir)) {
        mkdir($backup_dir, 0755, true);
    }
    
    // Simple table backup (in a real scenario, use mysqldump command)
    $tables = $conn->query("SHOW TABLES");
    $backup_content = "-- MEJECRES School Database Backup\n";
    $backup_content .= "-- Generated: " . date('Y-m-d H:i:s') . "\n\n";
    
    while ($table = $tables->fetch_array()) {
        $table_name = $table[0];
        $backup_content .= "--\n-- Table structure for table `$table_name`\n--\n";
        
        $create_table = $conn->query("SHOW CREATE TABLE $table_name")->fetch_array();
        $backup_content .= $create_table[1] . ";\n\n";
        
        $backup_content .= "--\n-- Dumping data for table `$table_name`\n--\n";
        
        $rows = $conn->query("SELECT * FROM $table_name");
        while ($row = $rows->fetch_assoc()) {
            $columns = implode("`, `", array_keys($row));
            $values = implode("', '", array_map([$conn, 'real_escape_string'], array_values($row)));
            $backup_content .= "INSERT INTO `$table_name` (`$columns`) VALUES ('$values');\n";
        }
        $backup_content .= "\n";
    }
    
    return file_put_contents($backup_dir . '/' . $backup_file, $backup_content) !== false;
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - MEJECRES SCHOOL</title>
    <link rel="icon" href="logo.jpg" type="image/x-icon">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #4361ee;
            --primary-dark: #3a56d4;
            --secondary: #7209b7;
            --success: #4cc9f0;
            --info: #4895ef;
            --warning: #f72585;
            --light: #f8f9fa;
            --dark: #212529;
            --bg: #f5f7fa;
            --card-shadow: 0 6px 18px rgba(0,0,0,0.08);
            --card-hover: 0 12px 24px rgba(0,0,0,0.12);
            --sidebar-width: 260px;
        }
        
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background: var(--bg);
            color: var(--dark);
            display: flex;
            min-height: 100vh;
            overflow-x: hidden;
        }
        
        /* Sidebar Styles */
        .sidebar {
            width: var(--sidebar-width);
            background: linear-gradient(180deg, var(--primary-dark), var(--primary));
            padding: 2rem 0;
            display: flex;
            flex-direction: column;
            transition: all 0.3s ease;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
            z-index: 100;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
        }
        
        .sidebar-header {
            padding: 0 1.5rem 1.5rem;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            margin-bottom: 1rem;
        }
        
        .sidebar-header h2 {
            color: white;
            font-size: 1.5rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .sidebar-header h2 i {
            color: #4cc9f0;
        }
        
        .sidebar a {
            color: white;
            text-decoration: none;
            padding: 14px 20px;
            border-radius: 8px;
            font-weight: 500;
            transition: 0.2s;
            margin: 4px 15px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .sidebar a i {
            width: 20px;
            text-align: center;
        }
        
        .sidebar a:hover {
            background: rgba(255,255,255,0.15);
            transform: translateX(5px);
        }
        
        .sidebar a.active {
            background: rgba(255,255,255,0.2);
            border-left: 4px solid var(--success);
        }
        
        .sidebar-footer {
            margin-top: auto;
            padding: 1rem 1.5rem;
            border-top: 1px solid rgba(255,255,255,0.1);
            color: rgba(255,255,255,0.7);
            font-size: 0.85rem;
        }
        
        .sidebar-toggle {
            display: none;
            position: fixed;
            top: 15px;
            left: 15px;
            background: white;
            border: none;
            border-radius: 8px;
            padding: 10px;
            cursor: pointer;
            z-index: 1000;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            width: 44px;
            height: 44px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            gap: 4px;
        }
        
        .toggle-line {
            width: 22px;
            height: 2px;
            background-color: var(--primary);
            transition: all 0.3s ease;
        }
        
        .sidebar-toggle.active .toggle-line:nth-child(1) {
            transform: translateY(6px) rotate(45deg);
        }
        
        .sidebar-toggle.active .toggle-line:nth-child(2) {
            opacity: 0;
        }
        
        .sidebar-toggle.active .toggle-line:nth-child(3) {
            transform: translateY(-6px) rotate(-45deg);
        }
        
        /* Main Content Styles */
        .main-content {
            flex: 1;
            padding: 20px;
            transition: all 0.3s ease;
            margin-left: var(--sidebar-width);
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding: 1rem 0;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .user-avatar {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: linear-gradient(45deg, var(--primary), var(--secondary));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
        }
        
        .page-title {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            font-size: 24px;
            font-weight: 600;
            text-align: center;
            padding: 25px;
            border-radius: 16px;
            margin-bottom: 2rem;
            box-shadow: var(--card-shadow);
        }
        
        /* Settings Tabs */
        .settings-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 2rem;
            flex-wrap: wrap;
            background: white;
            padding: 15px;
            border-radius: 12px;
            box-shadow: var(--card-shadow);
        }
        
        .tab-btn {
            padding: 12px 24px;
            background: #f8f9fa;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .tab-btn.active {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }
        
        .tab-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
            animation: fadeIn 0.5s ease;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .card {
            background: white;
            padding: 30px;
            border-radius: 16px;
            box-shadow: var(--card-shadow);
            margin-bottom: 25px;
            transition: 0.3s;
        }
        
        .card:hover {
            box-shadow: var(--card-hover);
        }
        
        .card h2 {
            margin-bottom: 20px;
            color: var(--primary-dark);
            border-bottom: 2px solid var(--primary);
            padding-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .card h2 i {
            color: var(--primary);
        }
        
        form {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        .form-group label {
            font-weight: 500;
            color: var(--dark);
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .form-group label i {
            color: var(--primary);
            width: 16px;
        }
        
        input, textarea, select, button {
            padding: 14px 16px;
            border-radius: 10px;
            border: 1px solid #ddd;
            font-size: 15px;
            width: 100%;
            transition: 0.3s;
            font-family: 'Poppins', sans-serif;
        }
        
        input:focus, textarea:focus, select:focus {
            border-color: var(--primary);
            outline: none;
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.2);
        }
        
        button {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            border: none;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all 0.3s ease;
        }
        
        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(67, 97, 238, 0.3);
        }
        
        button.danger {
            background: linear-gradient(135deg, #e63946, #d00000);
        }
        
        button.success {
            background: linear-gradient(135deg, #4caf50, #2e7d32);
        }
        
        button.warning {
            background: linear-gradient(135deg, #ff9800, #f57c00);
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .checkbox-group input[type="checkbox"] {
            width: 18px;
            height: 18px;
        }
        
        .color-picker {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .color-picker input[type="color"] {
            width: 60px;
            height: 45px;
            padding: 2px;
            border-radius: 6px;
            cursor: pointer;
        }
        
        .alert {
            padding: 14px 18px;
            border-radius: 10px;
            margin: 15px 0;
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 500;
        }
        
        .alert.success {
            background: #e8f5e9;
            color: #2e7d32;
            border-left: 4px solid #4caf50;
        }
        
        .alert.error {
            background: #ffebee;
            color: #c62828;
            border-left: 4px solid #f44336;
        }
        
        .alert.warning {
            background: #fff3e0;
            color: #ef6c00;
            border-left: 4px solid #ff9800;
        }
        
        .system-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }
        
        .info-card {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            border-left: 4px solid var(--primary);
        }
        
        .info-card h4 {
            color: var(--primary);
            margin-bottom: 8px;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .info-card p {
            font-size: 18px;
            font-weight: 600;
            color: var(--dark);
        }
        
        .backup-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }
        
        .backup-btn {
            background: white;
            padding: 20px;
            border-radius: 12px;
            border: 2px solid #e9ecef;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            color: var(--dark);
        }
        
        .backup-btn:hover {
            transform: translateY(-3px);
            box-shadow: var(--card-hover);
            border-color: var(--primary);
        }
        
        .backup-btn i {
            font-size: 32px;
            margin-bottom: 10px;
            color: var(--primary);
        }
        
        .password-strength {
            margin-top: 5px;
            height: 4px;
            border-radius: 2px;
            background: #e0e0e0;
            overflow: hidden;
        }
        
        .strength-bar {
            height: 100%;
            transition: all 0.3s ease;
        }
        
        .strength-weak { background: #f44336; width: 25%; }
        .strength-fair { background: #ff9800; width: 50%; }
        .strength-good { background: #4caf50; width: 75%; }
        .strength-strong { background: #2e7d32; width: 100%; }
        
        /* Responsive Styles */
        @media (max-width: 1024px) {
            .sidebar {
                left: -270px;
            }
            
            .sidebar.active {
                left: 0;
            }
            
            .sidebar-toggle {
                display: flex;
            }
            
            .main-content {
                margin-left: 0;
                padding-top: 70px;
            }
            
            .form-row {
                grid-template-columns: 1fr;
            }
        }
        
        @media (max-width: 768px) {
            .settings-tabs {
                flex-direction: column;
            }
            
            .tab-btn {
                justify-content: center;
            }
            
            .system-info {
                grid-template-columns: 1fr;
            }
            
            .backup-actions {
                grid-template-columns: 1fr;
            }
        }
        
        @media (min-width: 1025px) {
            .sidebar-toggle {
                display: none;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar Navigation -->
    <button class="sidebar-toggle" id="sidebarToggle" title="Toggle Sidebar">
        <div class="toggle-line"></div>
        <div class="toggle-line"></div>
        <div class="toggle-line"></div>
    </button>
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <h2><i class="fas fa-graduation-cap"></i> MEJECRES ADMIN</h2>
        </div>
        <a href="admin.php"><i class="fas fa-home"></i> Dashboard</a>
        <a href="all-teachers.php"><i class="fas fa-chalkboard-teacher"></i> Teachers</a>
        <a href="all-students.php"><i class="fas fa-user-graduate"></i> All Students</a>
        <a href="student.php"><i class="fas fa-plus-circle"></i> Add Students</a>
        <a href="attendance.php"><i class="fas fa-clipboard-check"></i> Attendance</a>
        <a href="messages.php"><i class="fas fa-envelope"></i> Messages</a>
        <a href="settings.php" class="active"><i class="fas fa-cog"></i> Settings</a>
        <a href="reports.php"><i class="fas fa-chart-bar"></i> Reports</a>
        <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        
        <div class="sidebar-footer">
            <p>&copy; 2023 MEJECRES SCHOOL</p>
            <p>Admin Panel v2.0</p>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content" id="mainContent">
        <div class="header">
            <h1>System Settings</h1>
            <div class="user-info">
                <div class="user-avatar">
                    <?= strtoupper(substr($_SESSION['admin_username'], 0, 1)) ?>
                </div>
                <div>
                    <div style="font-weight: 600;"><?= htmlspecialchars($_SESSION['admin_username']) ?></div>
                    <div style="font-size: 0.85rem; color: #6c757d;">Administrator</div>
                </div>
            </div>
        </div>

        <div class="page-title">
            <i class="fas fa-cogs"></i> System Configuration & Settings
        </div>

        <?php foreach($messages as $m): ?>
        <div class="alert <?= $m['type'] ?>">
            <i class="fas fa-<?= $m['type'] === 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i>
            <?= $m['text'] ?>
        </div>
        <?php endforeach; ?>

        <!-- Settings Tabs -->
        <div class="settings-tabs">
            <button class="tab-btn active" data-tab="general">
                <i class="fas fa-school"></i> General
            </button>
            <button class="tab-btn" data-tab="security">
                <i class="fas fa-shield-alt"></i> Security
        </button>
            <button class="tab-btn" data-tab="password">
                <i class="fas fa-key"></i> Password
            </button>
            <button class="tab-btn" data-tab="backup">
                <i class="fas fa-database"></i> Backup
            </button>
            <button class="tab-btn" data-tab="system">
                <i class="fas fa-info-circle"></i> System Info
            </button>
        </div>

        <!-- General Settings -->
        <div class="tab-content active" id="general-tab">
            <div class="card">
                <h2><i class="fas fa-school"></i> School Information</h2>
                <form method="post">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="school_name"><i class="fas fa-university"></i> School Name</label>
                            <input type="text" id="school_name" name="school_name" 
                                   value="<?= htmlspecialchars($settings['school_name'] ?? 'MEJECRES SCHOOL') ?>" 
                                   placeholder="Enter school name">
                        </div>
                        <div class="form-group">
                            <label for="academic_year"><i class="fas fa-calendar-alt"></i> Academic Year</label>
                            <input type="text" id="academic_year" name="academic_year" 
                                   value="<?= htmlspecialchars($settings['academic_year'] ?? '2023-2024') ?>" 
                                   placeholder="e.g., 2023-2024">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="school_email"><i class="fas fa-envelope"></i> School Email</label>
                        <input type="email" id="school_email" name="school_email" 
                               value="<?= htmlspecialchars($settings['school_email'] ?? 'info@mejecres.edu') ?>" 
                               placeholder="Enter school email">
                    </div>
                    
                    <div class="form-group">
                        <label for="school_phone"><i class="fas fa-phone"></i> School Phone</label>
                        <input type="tel" id="school_phone" name="school_phone" 
                               value="<?= htmlspecialchars($settings['school_phone'] ?? '+1 234 567 8900') ?>" 
                               placeholder="Enter school phone number">
                    </div>
                    
                    <div class="form-group">
                        <label for="school_address"><i class="fas fa-map-marker-alt"></i> School Address</label>
                        <textarea id="school_address" name="school_address" rows="3" 
                                  placeholder="Enter school address"><?= htmlspecialchars($settings['school_address'] ?? '') ?></textarea>
                    </div>
                    
                    <button type="submit" name="save_general">
                        <i class="fas fa-save"></i> Save General Settings
                    </button>
                </form>
            </div>
        </div>

        <!-- Security Settings -->
        <div class="tab-content" id="security-tab">
            <div class="card">
                <h2><i class="fas fa-shield-alt"></i> Security Settings</h2>
                <form method="post">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="session_timeout"><i class="fas fa-clock"></i> Session Timeout (minutes)</label>
                            <input type="number" id="session_timeout" name="session_timeout" 
                                   value="<?= htmlspecialchars($settings['session_timeout'] ?? '60') ?>" 
                                   min="15" max="480">
                        </div>
                        <div class="form-group">
                            <label for="max_login_attempts"><i class="fas fa-user-lock"></i> Max Login Attempts</label>
                            <input type="number" id="max_login_attempts" name="max_login_attempts" 
                                   value="<?= htmlspecialchars($settings['max_login_attempts'] ?? '5') ?>" 
                                   min="3" max="10">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="password_min_length"><i class="fas fa-key"></i> Minimum Password Length</label>
                            <input type="number" id="password_min_length" name="password_min_length" 
                                   value="<?= htmlspecialchars($settings['password_min_length'] ?? '8') ?>" 
                                   min="6" max="20">
                        </div>
                        <div class="form-group">
                            <label>&nbsp;</label>
                            <div class="checkbox-group">
                                <input type="checkbox" id="require_strong_password" name="require_strong_password" 
                                       <?= ($settings['require_strong_password'] ?? 0) ? 'checked' : '' ?>>
                                <label for="require_strong_password" style="font-weight: normal;">
                                    Require strong passwords (mix of letters, numbers, symbols)
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <div class="checkbox-group">
                            <input type="checkbox" id="enable_2fa" name="enable_2fa" 
                                   <?= ($settings['enable_2fa'] ?? 0) ? 'checked' : '' ?>>
                            <label for="enable_2fa">Enable Two-Factor Authentication</label>
                        </div>
                    </div>
                    
                    <button type="submit" name="save_security">
                        <i class="fas fa-save"></i> Save Security Settings
                    </button>
                </form>
            </div>
        </div>

        <!-- Password Settings -->
        <div class="tab-content" id="password-tab">
            <div class="card">
                <h2><i class="fas fa-key"></i> Change Password</h2>
                <form method="post">
                    <div class="form-group">
                        <label for="current_password"><i class="fas fa-lock"></i> Current Password</label>
                        <input type="password" id="current_password" name="current_password" 
                               placeholder="Enter current password" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="new_password"><i class="fas fa-lock"></i> New Password</label>
                        <input type="password" id="new_password" name="new_password" 
                               placeholder="Enter new password" required>
                        <div class="password-strength">
                            <div class="strength-bar" id="password-strength-bar"></div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password"><i class="fas fa-lock"></i> Confirm New Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" 
                               placeholder="Confirm new password" required>
                    </div>
                    
                    <button type="submit" name="change_password" class="success">
                        <i class="fas fa-key"></i> Change Password
                    </button>
                </form>
            </div>
        </div>

        <!-- Backup Settings -->
        <div class="tab-content" id="backup-tab">
            <div class="card">
                <h2><i class="fas fa-database"></i> Database Backup</h2>
                <p>Create a backup of your database to prevent data loss. Backups are stored in the backups folder.</p>
                
                <div class="backup-actions">
                    <form method="post" class="backup-btn">
                        <button type="submit" name="backup_database" class="success" style="background: transparent; border: none; color: inherit;">
                            <i class="fas fa-download"></i>
                            <div>Create Backup</div>
                            <small>Backup current database</small>
                        </button>
                    </form>
                    
                    <a href="#" class="backup-btn">
                        <i class="fas fa-upload"></i>
                        <div>Restore Backup</div>
                        <small>Restore from file</small>
                    </a>
                    
                    <a href="#" class="backup-btn">
                        <i class="fas fa-history"></i>
                        <div>Auto Backup</div>
                        <small>Schedule automatic backups</small>
                    </a>
                    
                    <a href="backups/" class="backup-btn">
                        <i class="fas fa-folder-open"></i>
                        <div>View Backups</div>
                        <small>Browse backup files</small>
                    </a>
                </div>
                
                <div class="alert warning">
                    <i class="fas fa-exclamation-triangle"></i>
                    <strong>Important:</strong> Regular backups are essential for data protection. We recommend creating backups at least once a week.
                </div>
            </div>
        </div>

        <!-- System Information -->
        <div class="tab-content" id="system-tab">
            <div class="card">
                <h2><i class="fas fa-info-circle"></i> System Information</h2>
                
                <div class="system-info">
                    <div class="info-card">
                        <h4>PHP Version</h4>
                        <p><?= phpversion() ?></p>
                    </div>
                    <div class="info-card">
                        <h4>Database</h4>
                        <p>MySQL</p>
                    </div>
                    <div class="info-card">
                        <h4>Server Software</h4>
                        <p><?= $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown' ?></p>
                    </div>
                    <div class="info-card">
                        <h4>System Load</h4>
                        <p><?= function_exists('sys_getloadavg') ? round(sys_getloadavg()[0], 2) : 'N/A' ?></p>
                    </div>
                </div>
                
                <div class="alert info" style="background: #e3f2fd; color: #1565c0; border-left-color: #2196f3;">
                    <i class="fas fa-server"></i>
                    <strong>System Status:</strong> All systems operational. Last checked: <?= date('Y-m-d H:i:s') ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Tab functionality
        document.addEventListener('DOMContentLoaded', function() {
            const tabBtns = document.querySelectorAll('.tab-btn');
            const tabContents = document.querySelectorAll('.tab-content');
            
            tabBtns.forEach(btn => {
                btn.addEventListener('click', () => {
                    const tabId = btn.getAttribute('data-tab');
                    
                    // Remove active class from all buttons and contents
                    tabBtns.forEach(b => b.classList.remove('active'));
                    tabContents.forEach(c => c.classList.remove('active'));
                    
                    // Add active class to clicked button and corresponding content
                    btn.classList.add('active');
                    document.getElementById(`${tabId}-tab`).classList.add('active');
                });
            });
            
            // Password strength indicator
            const passwordInput = document.getElementById('new_password');
            const strengthBar = document.getElementById('password-strength-bar');
            
            if (passwordInput && strengthBar) {
                passwordInput.addEventListener('input', function() {
                    const password = this.value;
                    let strength = 0;
                    
                    if (password.length >= 8) strength++;
                    if (password.match(/[a-z]/) && password.match(/[A-Z]/)) strength++;
                    if (password.match(/\d/)) strength++;
                    if (password.match(/[^a-zA-Z\d]/)) strength++;
                    
                    strengthBar.className = 'strength-bar';
                    if (password.length === 0) {
                        strengthBar.style.width = '0%';
                    } else if (strength === 1) {
                        strengthBar.className += ' strength-weak';
                    } else if (strength === 2) {
                        strengthBar.className += ' strength-fair';
                    } else if (strength === 3) {
                        strengthBar.className += ' strength-good';
                    } else if (strength === 4) {
                        strengthBar.className += ' strength-strong';
                    }
                });
            }
            
            // Color picker update
            const colorPicker = document.getElementById('theme_color');
            if (colorPicker) {
                colorPicker.addEventListener('input', function() {
                    this.nextElementSibling.textContent = this.value;
                });
            }
        });
        
        // Toggle sidebar on mobile
        const sidebarToggle = document.getElementById('sidebarToggle');
        const sidebar = document.getElementById('sidebar');
        const mainContent = document.getElementById('mainContent');
        
        sidebarToggle.addEventListener('click', () => {
            sidebar.classList.toggle('active');
            sidebarToggle.classList.toggle('active');
        });
        
        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', (event) => {
            if (window.innerWidth <= 1024) {
                const isClickInsideSidebar = sidebar.contains(event.target);
                const isClickInsideToggle = sidebarToggle.contains(event.target);
                
                if (!isClickInsideSidebar && !isClickInsideToggle && sidebar.classList.contains('active')) {
                    sidebar.classList.remove('active');
                    sidebarToggle.classList.remove('active');
                }
            }
        });
        
        // Handle window resize
        window.addEventListener('resize', () => {
            if (window.innerWidth > 1024) {
                sidebar.classList.remove('active');
                sidebarToggle.classList.remove('active');
            }
        });
    </script>
</body>
</html>