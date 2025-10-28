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

include 'models/Attendance.php';
$attendanceModel = new Attendance($conn);

$messages = [];

// Get stats for dashboard - Fixed queries to handle missing columns
$totalTeachers = $conn->query("SELECT COUNT(*) as count FROM teachers")->fetch_assoc()['count'] ?? 0;
$totalStudents = $conn->query("SELECT COUNT(*) as count FROM students")->fetch_assoc()['count'] ?? 0;
$totalAnnouncements = $conn->query("SELECT COUNT(*) as count FROM announcements")->fetch_assoc()['count'] ?? 0;

// Fixed: Check if attendance table and date column exist
$todayAttendance = 0;
$attendanceTableCheck = $conn->query("SHOW TABLES LIKE 'attendance'");
if ($attendanceTableCheck && $attendanceTableCheck->num_rows > 0) {
    $columnsCheck = $conn->query("SHOW COLUMNS FROM attendance LIKE 'date'");
    if ($columnsCheck && $columnsCheck->num_rows > 0) {
        $todayAttendanceResult = $conn->query("SELECT COUNT(*) as count FROM attendance WHERE date = CURDATE()");
        if ($todayAttendanceResult) {
            $todayAttendance = $todayAttendanceResult->fetch_assoc()['count'] ?? 0;
        }
    } else {
        // If date column doesn't exist, count all attendance records
        $todayAttendanceResult = $conn->query("SELECT COUNT(*) as count FROM attendance");
        if ($todayAttendanceResult) {
            $todayAttendance = $todayAttendanceResult->fetch_assoc()['count'] ?? 0;
        }
    }
}

// Announcements - Fixed to store details in database
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['post_announcement'])){
    $title = trim($_POST['ann_title'] ?? '');
    $date = trim($_POST['ann_date'] ?? '');
    $details = trim($_POST['ann_details'] ?? '');

    if($title && $date){
        $stmt=$conn->prepare("INSERT INTO announcements (title, date, details) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $title, $date, $details); 
        if($stmt->execute()){
            $messages[]=['type'=>'success','text'=>'Announcement posted successfully.'];
        } else {
            $messages[]=['type'=>'error','text'=>'Error posting announcement.'];
        }
        $stmt->close();
    } else {
        $messages[]=['type'=>'error','text'=>'Please fill title and date.'];
    }
}

if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['delete_announcement'])){
    $aid=intval($_POST['announcement_id']);
    $stmt=$conn->prepare("DELETE FROM announcements WHERE id=?");
    $stmt->bind_param("i",$aid); 
    if($stmt->execute()){
        $messages[]=['type'=>'success','text'=>'Announcement deleted successfully.'];
    } else {
        $messages[]=['type'=>'error','text'=>'Error deleting announcement.'];
    }
    $stmt->close();
}

// Add Teacher
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['add_teacher'])){
    $username = trim($_POST['teacher_username'] ?? '');
    $fullname = trim($_POST['teacher_fullname'] ?? '');
    $email = trim($_POST['teacher_email'] ?? '');
    $password = trim($_POST['teacher_password'] ?? '');

    if($username && $fullname && $email && $password){
        // Check if username already exists
        $checkStmt = $conn->prepare("SELECT id FROM teachers WHERE username = ?");
        $checkStmt->bind_param("s", $username);
        $checkStmt->execute();
        $checkStmt->store_result();
        
        if($checkStmt->num_rows > 0){
            $messages[] = ['type'=>'error','text'=>'Username already exists.'];
        } else {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO teachers (username, fullname, email, password) VALUES (?,?,?,?)");
            $stmt->bind_param("ssss", $username, $fullname, $email, $hashedPassword);
            if($stmt->execute()){
                $messages[] = ['type'=>'success','text'=>'Teacher added successfully.'];
            } else {
                $messages[] = ['type'=>'error','text'=>'Error adding teacher.'];
            }
            $stmt->close();
        }
        $checkStmt->close();
    } else {
        $messages[] = ['type'=>'error','text'=>'Please fill all fields.'];
    }
}

// Gallery
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['upload_photos'])){
    $uploadDir=__DIR__.'/uploads'; 
    if(!is_dir($uploadDir)) mkdir($uploadDir,0755,true);
    $saved=0;
    $errors=0;
    
    foreach($_FILES['photos']['tmp_name'] as $i=>$tmpName){
        if($_FILES['photos']['error'][$i] !== UPLOAD_ERR_OK) {
            $errors++;
            continue;
        }
        
        $name=$_FILES['photos']['name'][$i]; 
        $ext=strtolower(pathinfo($name,PATHINFO_EXTENSION));
        if(!in_array($ext,['jpg','jpeg','png','gif','webp'])) {
            $errors++;
            continue;
        }
        
        $newName=uniqid('img_',true).".$ext";
        if(move_uploaded_file($tmpName,"$uploadDir/$newName")){
            $stmt=$conn->prepare("INSERT INTO gallery (filename, uploaded_at) VALUES (?, NOW())"); 
            $stmt->bind_param("s",$newName); 
            if($stmt->execute()){
                $saved++;
            } else {
                $errors++;
            }
            $stmt->close();
        } else {
            $errors++;
        }
    }
    
    if($saved > 0){
        $messages[]=['type'=>'success','text'=>"Successfully uploaded $saved photo(s)."];
    }
    if($errors > 0){
        $messages[]=['type'=>'error','text'=>"Failed to upload $errors file(s)."];
    }
}

if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['delete_image'])){
    $id=intval($_POST['image_id']); 
    $filename=$_POST['filename']??''; 
    $path=__DIR__."/uploads/$filename";
    
    $stmt=$conn->prepare("DELETE FROM gallery WHERE id=?"); 
    $stmt->bind_param("i",$id); 
    if($stmt->execute()){
        if(file_exists($path)) {
            unlink($path);
        }
        $messages[]=['type'=>'success','text'=>"Image deleted successfully."];
    } else {
        $messages[]=['type'=>'error','text'=>"Error deleting image."];
    }
    $stmt->close();
}

// Fetch data with error handling
try {
    $teachers=$conn->query("SELECT id,username,fullname,email FROM teachers ORDER BY id DESC")?->fetch_all(MYSQLI_ASSOC)??[];
} catch (Exception $e) {
    $teachers = [];
    $messages[]=['type'=>'error','text'=>'Error loading teachers.'];
}

try {
    $announcements=$conn->query("SELECT id,title,date,details FROM announcements ORDER BY date DESC,id DESC")?->fetch_all(MYSQLI_ASSOC)??[];
} catch (Exception $e) {
    $announcements = [];
    $messages[]=['type'=>'error','text'=>'Error loading announcements.'];
}

try {
    $gallery=$conn->query("SELECT id,filename FROM gallery ORDER BY id DESC")?->fetch_all(MYSQLI_ASSOC)??[];
} catch (Exception $e) {
    $gallery = [];
    $messages[]=['type'=>'error','text'=>'Error loading gallery.'];
}

try {
    $attendance = $attendanceModel->getAll();
} catch (Exception $e) {
    $attendance = [];
    $messages[]=['type'=>'error','text'=>'Error loading attendance data.'];
}

$conn->close();

// Pagination for announcements (5 per page)
$itemsPerPage = 5;
$totalAnn = count($announcements);
$totalAnnPages = ceil($totalAnn / $itemsPerPage);
$annPage = isset($_GET['ann_page']) ? max(1,intval($_GET['ann_page'])) : 1;
$startAnn = ($annPage-1)*$itemsPerPage;
$announcementsPage = array_slice($announcements, $startAnn, $itemsPerPage);

// Pagination for gallery
$totalGallery = count($gallery);
$totalGalleryPages = ceil($totalGallery / $itemsPerPage);
$galleryPageNum = isset($_GET['page']) ? max(1,intval($_GET['page'])) : 1;
$startGallery = ($galleryPageNum-1)*$itemsPerPage;
$galleryPage = array_slice($gallery, $startGallery, $itemsPerPage);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - MEJECRES SCHOOL</title>
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
        
        /* Enhanced Sidebar Styles */
        .sidebar {
            width: var(--sidebar-width);
            background: linear-gradient(180deg, var(--primary-dark), var(--primary));
            padding: 2rem 0;
            display: flex;
            flex-direction: column;
            transition: all 0.3s ease;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
            z-index: 1000;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            left: 0;
        }
        
        .sidebar::-webkit-scrollbar {
            width: 6px;
        }
        
        .sidebar::-webkit-scrollbar-track {
            background: rgba(255,255,255,0.1);
        }
        
        .sidebar::-webkit-scrollbar-thumb {
            background: rgba(255,255,255,0.3);
            border-radius: 3px;
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
        
        /* Enhanced Sidebar Toggle */
        .sidebar-toggle {
            display: none;
            position: fixed;
            top: 20px;
            left: 20px;
            background: var(--primary);
            border: none;
            border-radius: 12px;
            padding: 12px;
            cursor: pointer;
            z-index: 1001;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
            width: 50px;
            height: 50px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            gap: 5px;
            transition: all 0.3s ease;
        }

        /* Hide toggle when scrolling down */
        .sidebar-toggle.hidden {
            transform: translateY(-100px);
            opacity: 0;
        }

        .toggle-line {
            width: 24px;
            height: 3px;
            background-color: white;
            transition: all 0.3s ease;
            border-radius: 2px;
        }

        .sidebar-toggle.active .toggle-line:nth-child(1) {
            transform: translateY(8px) rotate(45deg);
        }

        .sidebar-toggle.active .toggle-line:nth-child(2) {
            opacity: 0;
        }

        .sidebar-toggle.active .toggle-line:nth-child(3) {
            transform: translateY(-8px) rotate(-45deg);
        }
        
        /* Mobile Overlay */
        .sidebar-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 999;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }
        
        .sidebar-overlay.active {
            opacity: 1;
            visibility: visible;
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
        
        .welcome-card {
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
        
        /* Stats Cards */
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: var(--card-shadow);
            display: flex;
            align-items: center;
            gap: 15px;
            transition: 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--card-hover);
        }
        
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: white;
        }
        
        .stat-icon.teachers { background: linear-gradient(45deg, #4361ee, #4cc9f0); }
        .stat-icon.students { background: linear-gradient(45deg, #7209b7, #b5179e); }
        .stat-icon.announcements { background: linear-gradient(45deg, #f72585, #ff9e00); }
        .stat-icon.attendance { background: linear-gradient(45deg, #4caf50, #8bc34a); }
        
        .stat-info h3 {
            font-size: 14px;
            color: #6c757d;
            margin-bottom: 5px;
        }
        
        .stat-info .number {
            font-size: 28px;
            font-weight: 700;
            color: var(--dark);
        }
        
        .card {
            background: white;
            padding: 25px;
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
            gap: 15px;
            max-width: 500px;
            margin: 0 auto;
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        
        .form-group label {
            font-weight: 500;
            color: var(--dark);
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
        
        table {
            width: 100%;
            border-collapse: collapse;
            border: none;
            margin-top: 20px;
        }
        
        th, td {
            text-align: left;
            padding: 14px 12px;
        }
        
        th {
            background: var(--primary);
            color: white;
            font-weight: 600;
            position: sticky;
            top: 0;
        }
        
        tr:nth-child(even) {
            background: rgba(67, 97, 238, 0.05);
        }
        
        tr:hover {
            background: rgba(67, 97, 238, 0.1);
        }
        
        .table-wrapper {
            overflow-x: auto;
            margin-bottom: 1rem;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
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
        
        .pagination {
            display: flex;
            justify-content: center;
            gap: 8px;
            margin: 20px 0;
            flex-wrap: wrap;
        }
        
        .pagination a {
            padding: 8px 14px;
            background: white;
            color: var(--primary);
            border-radius: 8px;
            text-decoration: none;
            border: 1px solid var(--primary);
            transition: 0.3s;
            font-weight: 500;
        }
        
        .pagination a.active {
            background: var(--primary);
            color: white;
        }
        
        .pagination a:hover {
            background: var(--primary);
            color: white;
        }
        
        .spacing {
            margin-top: 20px;
        }
        
        .gallery-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }
        
        .gallery-item {
            position: relative;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            aspect-ratio: 1;
        }
        
        .gallery-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: 0.3s;
        }
        
        .gallery-item:hover img {
            transform: scale(1.05);
        }
        
        .gallery-actions {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: rgba(0,0,0,0.7);
            padding: 10px;
            display: flex;
            justify-content: center;
        }
        
        /* Quick Actions */
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }
        
        .action-btn {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: var(--card-shadow);
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
            text-decoration: none;
            color: var(--dark);
            transition: 0.3s;
            text-align: center;
        }
        
        .action-btn:hover {
            transform: translateY(-5px);
            box-shadow: var(--card-hover);
            color: var(--primary);
        }
        
        .action-btn i {
            font-size: 28px;
            color: var(--primary);
        }
        
        /* Enhanced Responsive Styles */
        @media (max-width: 1024px) {
            .sidebar {
                transform: translateX(-100%);
                left: 0;
            }
            
            .sidebar.active {
                transform: translateX(0);
            }
            
            .sidebar-toggle {
                display: flex;
            }
            
            .main-content {
                margin-left: 0;
                padding-top: 20px;
            }
            
            .stats-container {
                grid-template-columns: repeat(2, 1fr);
            }
            
            /* Prevent body scroll when sidebar is open */
            body.sidebar-open {
                overflow: hidden;
            }
        }
        
        @media (max-width: 768px) {
            .stats-container {
                grid-template-columns: 1fr;
            }
            
            .header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
            
            .gallery-grid {
                grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            }
            
            .quick-actions {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        /* For very small screens */
        @media (max-width: 480px) {
            .sidebar {
                width: 100%;
                max-width: 280px;
            }
            
            .sidebar-toggle {
                top: 15px;
                left: 15px;
                width: 45px;
                height: 45px;
            }
            
            .main-content {
                padding: 15px;
            }
            
            .card {
                padding: 20px;
            }
            
            .welcome-card {
                font-size: 20px;
                padding: 20px;
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
    <!-- Sidebar Overlay -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    
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
        <a href="index.php" class="active"><i class="fas fa-home"></i> Dashboard</a>
        <a href="all-teachers.php"><i class="fas fa-chalkboard-teacher"></i> Teachers</a>
        <a href="all-students.php"><i class="fas fa-user-graduate"></i> All Students</a>
        <a href="student.php"><i class="fas fa-plus-circle"></i> Add Students</a>
        <a href="attendance.php"><i class="fas fa-clipboard-check"></i> Attendance</a>
        <a href="messages.php"><i class="fas fa-envelope"></i> Messages</a>
        <a href="settings.php"><i class="fas fa-cog"></i> Settings</a>
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
            <h1>Admin Dashboard</h1>
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

        <div class="welcome-card">
            Welcome to MEJECRES School Admin Panel, <?= htmlspecialchars($_SESSION['admin_username']) ?>!
        </div>

        <?php foreach($messages as $m): ?>
        <div class="alert <?= $m['type'] ?>">
            <i class="fas fa-<?= $m['type'] === 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i>
            <?= $m['text'] ?>
        </div>
        <?php endforeach; ?>

        <!-- Stats Cards -->
        <div class="stats-container">
            <div class="stat-card">
                <div class="stat-icon teachers">
                    <i class="fas fa-chalkboard-teacher"></i>
                </div>
                <div class="stat-info">
                    <h3>Total Teachers</h3>
                    <div class="number"><?= $totalTeachers ?></div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon students">
                    <i class="fas fa-user-graduate"></i>
                </div>
                <div class="stat-info">
                    <h3>Total Students</h3>
                    <div class="number"><?= $totalStudents ?></div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon announcements">
                    <i class="fas fa-bullhorn"></i>
                </div>
                <div class="stat-info">
                    <h3>Announcements</h3>
                    <div class="number"><?= $totalAnnouncements ?></div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon attendance">
                    <i class="fas fa-clipboard-check"></i>
                </div>
                <div class="stat-info">
                    <h3>Attendance Records</h3>
                    <div class="number"><?= $todayAttendance ?></div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="card">
            <h2><i class="fas fa-bolt"></i> Quick Actions</h2>
            <div class="quick-actions">
                <a href="student.php" class="action-btn">
                    <i class="fas fa-user-plus"></i>
                    <span>Add Student</span>
                </a>
                <a href="all-teachers.php" class="action-btn">
                    <i class="fas fa-users"></i>
                    <span>Manage Teachers</span>
                </a>
                <a href="attendance.php" class="action-btn">
                    <i class="fas fa-clipboard-list"></i>
                    <span>Take Attendance</span>
                </a>
                <a href="reports.php" class="action-btn">
                    <i class="fas fa-chart-pie"></i>
                    <span>View Reports</span>
                </a>
            </div>
        </div>

        <!-- Add Teacher Card -->
        <div class="card">
            <h2><i class="fas fa-user-plus"></i> Add Teacher</h2>
            <form method="post">
                <div class="form-group">
                    <label for="teacher_username">Username</label>
                    <input id="teacher_username" name="teacher_username" placeholder="Enter username" required>
                </div>
                <div class="form-group">
                    <label for="teacher_fullname">Full Name</label>
                    <input id="teacher_fullname" name="teacher_fullname" placeholder="Enter full name" required>
                </div>
                <div class="form-group">
                    <label for="teacher_email">Email</label>
                    <input type="email" id="teacher_email" name="teacher_email" placeholder="Enter email" required>
                </div>
                <div class="form-group">
                    <label for="teacher_password">Password</label>
                    <input type="password" id="teacher_password" name="teacher_password" placeholder="Enter password" required>
                </div>
                <button name="add_teacher"><i class="fas fa-plus"></i> Add Teacher</button>
            </form>
        </div>

        <!-- Announcements Card -->
        <div class="card">
            <h2><i class="fas fa-bullhorn"></i> Announcements</h2>
            <form method="post">
                <div class="form-group">
                    <label for="ann_title">Title</label>
                    <input id="ann_title" name="ann_title" placeholder="Enter announcement title" required>
                </div>
                <div class="form-group">
                    <label for="ann_date">Date</label>
                    <input id="ann_date" name="ann_date" type="date" required>
                </div>
                <div class="form-group">
                    <label for="ann_details">Details</label>
                    <textarea id="ann_details" name="ann_details" placeholder="Enter announcement details" rows="5"></textarea>
                </div>
                <button name="post_announcement"><i class="fas fa-paper-plane"></i> Post Announcement</button>
            </form>

            <div class="spacing"></div>

            <?php if($announcementsPage): ?>
            <div class="table-wrapper">
                <table>
                    <tr>
                        <th>Title</th>
                        <th>Date</th>
                        <th>Details</th>
                        <th>Action</th>
                    </tr>
                    <?php foreach($announcementsPage as $ann): ?>
                    <tr>
                        <td><?= htmlspecialchars($ann['title']) ?></td>
                        <td><?= htmlspecialchars($ann['date']) ?></td>
                        <td><?= htmlspecialchars($ann['details'] ?? 'No details') ?></td>
                        <td>
                            <form method="post" style="margin:0;">
                                <input type="hidden" name="announcement_id" value="<?= $ann['id'] ?>">
                                <button name="delete_announcement" class="danger" style="padding:8px 12px;font-size:14px;">
                                    <i class="fas fa-trash"></i> Delete
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </table>
            </div>

            <!-- Announcement Pagination -->
            <?php if($totalAnnPages>1): ?>
            <div class="pagination">
                <?php for($i=1;$i<=$totalAnnPages;$i++): ?>
                    <a href="?ann_page=<?= $i ?>" class="<?= $i==$annPage?'active':'' ?>"><?= $i ?></a>
                <?php endfor; ?>
            </div>
            <?php endif; ?>
            <?php else: ?>
                <p style="margin-top:10px; text-align:center; color:#6c757d;">No announcements found.</p>
            <?php endif; ?>
        </div>

        <!-- Gallery Card -->
        <div class="card">
            <h2><i class="fas fa-images"></i> Gallery</h2>
            <form method="post" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="photos">Select Images</label>
                    <input type="file" id="photos" name="photos[]" multiple accept="image/*" required>
                </div>
                <button name="upload_photos"><i class="fas fa-upload"></i> Upload Images</button>
            </form>

            <div class="spacing"></div>

            <?php if($galleryPage): ?>
            <div class="gallery-grid">
                <?php foreach($galleryPage as $img): ?>
                <div class="gallery-item">
                    <img src="uploads/<?= htmlspecialchars($img['filename']) ?>" alt="Gallery Image" onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAwIiBoZWlnaHQ9IjIwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZGRkIi8+PHRleHQgeD0iNTAlIiB5PSI1MCUiIGZvbnQtc2l6ZT0iMTgiIGZpbGw9IiM5OTkiIGR5PSIuM2VtIiB0ZXh0LWFuY2hvcj0ibWlkZGxlIj5JbWFnZTwvdGV4dD48L3N2Zz4='">
                    <div class="gallery-actions">
                        <form method="post" style="margin:0;">
                            <input type="hidden" name="image_id" value="<?= $img['id'] ?>">
                            <input type="hidden" name="filename" value="<?= htmlspecialchars($img['filename']) ?>">
                            <button name="delete_image" class="danger" style="padding:6px 10px;font-size:12px;">
                                <i class="fas fa-trash"></i> Delete
                            </button>
                        </form>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <div class="pagination">
                <?php for($i=1;$i<=$totalGalleryPages;$i++): ?>
                    <a href="?page=<?= $i ?>" class="<?= $i==$galleryPageNum?'active':'' ?>"><?= $i ?></a>
                <?php endfor; ?>
            </div>
            <?php else: ?>
                <p style="margin-top:10px; text-align:center; color:#6c757d;">No images in gallery.</p>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Enhanced sidebar functionality
        const sidebarToggle = document.getElementById('sidebarToggle');
        const sidebar = document.getElementById('sidebar');
        const mainContent = document.getElementById('mainContent');
        const overlay = document.getElementById('sidebarOverlay');
        const body = document.body;

        let lastScrollTop = 0;

        function toggleSidebar() {
            sidebar.classList.toggle('active');
            sidebarToggle.classList.toggle('active');
            overlay.classList.toggle('active');
            body.classList.toggle('sidebar-open');
        }

        function closeSidebar() {
            sidebar.classList.remove('active');
            sidebarToggle.classList.remove('active');
            overlay.classList.remove('active');
            body.classList.remove('sidebar-open');
        }

        // Toggle sidebar
        sidebarToggle.addEventListener('click', (e) => {
            e.stopPropagation();
            toggleSidebar();
        });

        // Close sidebar when clicking overlay
        overlay.addEventListener('click', closeSidebar);

        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', (event) => {
            if (window.innerWidth <= 1024 && sidebar.classList.contains('active')) {
                const isClickInsideSidebar = sidebar.contains(event.target);
                const isClickInsideToggle = sidebarToggle.contains(event.target);
                
                if (!isClickInsideSidebar && !isClickInsideToggle) {
                    closeSidebar();
                }
            }
        });

        // Close sidebar when clicking on a link (mobile)
        sidebar.addEventListener('click', (event) => {
            if (window.innerWidth <= 1024 && event.target.tagName === 'A') {
                closeSidebar();
            }
        });

        // Hide/show toggle button on scroll
        window.addEventListener('scroll', () => {
            if (window.innerWidth <= 1024) {
                const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
                
                if (scrollTop > lastScrollTop && scrollTop > 100) {
                    sidebarToggle.classList.add('hidden');
                } else {
                    sidebarToggle.classList.remove('hidden');
                }
                
                lastScrollTop = scrollTop;
            }
        });

        // Handle window resize
        window.addEventListener('resize', () => {
            if (window.innerWidth > 1024) {
                closeSidebar();
                sidebarToggle.classList.remove('hidden');
            }
        });

        // Add active class to current page link
        document.addEventListener('DOMContentLoaded', function() {
            const currentPage = window.location.pathname.split('/').pop() || 'index.php';
            const navLinks = document.querySelectorAll('.sidebar a');
            
            navLinks.forEach(link => {
                const linkHref = link.getAttribute('href');
                if (linkHref === currentPage) {
                    link.classList.add('active');
                }
            });
        });

        // Set today's date as default for announcement date
        document.addEventListener('DOMContentLoaded', function() {
            const today = new Date().toISOString().split('T')[0];
            const dateInput = document.getElementById('ann_date');
            if (dateInput && !dateInput.value) {
                dateInput.value = today;
            }
        });

        // Handle escape key to close sidebar
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && sidebar.classList.contains('active')) {
                closeSidebar();
            }
        });

        // Prevent body scroll when sidebar is open on mobile
        document.addEventListener('touchmove', function(e) {
            if (body.classList.contains('sidebar-open')) {
                e.preventDefault();
            }
        }, { passive: false });
    </script>
</body>
</html>