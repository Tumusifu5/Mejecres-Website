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

// Get filter parameters
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-t');
$report_type = $_GET['report_type'] ?? 'overview';
$class_filter = $_GET['class_filter'] ?? 'all';

// Initialize variables with default values
$total_students = 0;
$total_teachers = 0;
$total_classes = 0;
$attendance_stats = ['total_records' => 0, 'present' => 0, 'absent' => 0, 'late' => 0];
$monthly_trend = [];
$class_attendance = [];
$recent_activities = [];
$attendance_table_exists = false;

// Check if tables exist and get statistics with error handling
try {
    // Get basic counts
    $total_students = $conn->query("SELECT COUNT(*) as count FROM students")->fetch_assoc()['count'] ?? 0;
    $total_teachers = $conn->query("SELECT COUNT(*) as count FROM teachers")->fetch_assoc()['count'] ?? 0;
    
    // Check if students table has class column
    $class_check = $conn->query("SHOW COLUMNS FROM students LIKE 'class'");
    if ($class_check && $class_check->num_rows > 0) {
        $total_classes = $conn->query("SELECT COUNT(DISTINCT class) as count FROM students")->fetch_assoc()['count'] ?? 0;
    }
    
    // Check if attendance table exists
    $attendance_table = $conn->query("SHOW TABLES LIKE 'attendance'");
    $attendance_table_exists = $attendance_table && $attendance_table->num_rows > 0;
    
    if ($attendance_table_exists) {
        $columns_check = $conn->query("SHOW COLUMNS FROM attendance");
        $columns = [];
        while ($col = $columns_check->fetch_assoc()) {
            $columns[] = $col['Field'];
        }
        
        // Build query based on available columns
        $date_condition = in_array('date', $columns) ? "date BETWEEN '$start_date' AND '$end_date'" : "1=1";
        $status_condition = in_array('status', $columns) ? 
            "SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present,
             SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent,
             SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late" : 
            "COUNT(*) as present, 0 as absent, 0 as late";
        
        $attendance_stats_query = "SELECT COUNT(*) as total_records, $status_condition FROM attendance WHERE $date_condition";
        $attendance_stats_result = $conn->query($attendance_stats_query);
        if ($attendance_stats_result) {
            $attendance_stats = $attendance_stats_result->fetch_assoc() ?? ['total_records' => 0, 'present' => 0, 'absent' => 0, 'late' => 0];
        }
        
        // Monthly trend (only if date column exists)
        if (in_array('date', $columns)) {
            $monthly_trend_result = $conn->query("
                SELECT 
                    DATE_FORMAT(date, '%Y-%m') as month,
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present
                FROM attendance 
                WHERE date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
                GROUP BY DATE_FORMAT(date, '%Y-%m')
                ORDER BY month DESC
                LIMIT 6
            ");
            if ($monthly_trend_result) {
                $monthly_trend = $monthly_trend_result->fetch_all(MYSQLI_ASSOC);
            }
        }
        
        // Class-wise attendance (if students table has class column)
        if ($class_check && $class_check->num_rows > 0) {
            $class_attendance_result = $conn->query("
                SELECT 
                    s.class,
                    COUNT(a.id) as total_records,
                    SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) as present,
                    CASE 
                        WHEN COUNT(a.id) > 0 THEN ROUND((SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) / COUNT(a.id)) * 100, 2)
                        ELSE 0 
                    END as attendance_rate
                FROM students s
                LEFT JOIN attendance a ON s.id = a.student_id 
                " . (in_array('date', $columns) ? "AND a.date BETWEEN '$start_date' AND '$end_date'" : "") . "
                GROUP BY s.class
                ORDER BY attendance_rate DESC
            ");
            if ($class_attendance_result) {
                $class_attendance = $class_attendance_result->fetch_all(MYSQLI_ASSOC);
            }
        }
    }
    
    // Recent activities from multiple tables
    $activities = [];
    
    // Check each table and add activities
    if ($attendance_table_exists) {
        $attendance_activities = $conn->query("
            SELECT 'attendance' as type, created_at as activity_date, 
                   CONCAT('Attendance records updated for ', DATE_FORMAT(created_at, '%M %d, %Y')) as description 
            FROM attendance 
            GROUP BY DATE(created_at) 
            ORDER BY created_at DESC 
            LIMIT 3
        ");
        if ($attendance_activities) {
            $activities = array_merge($activities, $attendance_activities->fetch_all(MYSQLI_ASSOC));
        }
    }
    
    $teacher_activities = $conn->query("
        SELECT 'teacher' as type, created_at as activity_date, 
               CONCAT('Teacher added: ', fullname) as description 
        FROM teachers 
        ORDER BY created_at DESC 
        LIMIT 3
    ");
    if ($teacher_activities) {
        $activities = array_merge($activities, $teacher_activities->fetch_all(MYSQLI_ASSOC));
    }
    
    $student_activities = $conn->query("
        SELECT 'student' as type, created_at as activity_date, 
               CONCAT('Student enrolled: ', name) as description 
        FROM students 
        ORDER BY created_at DESC 
        LIMIT 3
    ");
    if ($student_activities) {
        $activities = array_merge($activities, $student_activities->fetch_all(MYSQLI_ASSOC));
    }
    
    // Sort activities by date and limit to 10
    usort($activities, function($a, $b) {
        return strtotime($b['activity_date']) - strtotime($a['activity_date']);
    });
    $recent_activities = array_slice($activities, 0, 10);
    
} catch (Exception $e) {
    error_log("Reports page error: " . $e->getMessage());
}

// Close connection after all database operations are done
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports & Analytics - MEJECRES SCHOOL</title>
    <link rel="icon" href="logo.jpg" type="image/x-icon">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
        
        /* Database Setup Alert */
        .setup-alert {
            background: linear-gradient(135deg, #ff9800, #f57c00);
            color: white;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 2rem;
            text-align: center;
        }
        
        .setup-alert h3 {
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        /* Filter Section */
        .filter-section {
            background: white;
            padding: 25px;
            border-radius: 16px;
            box-shadow: var(--card-shadow);
            margin-bottom: 2rem;
        }
        
        .filter-form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            align-items: end;
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
        
        input, select, button {
            padding: 12px 16px;
            border-radius: 10px;
            border: 1px solid #ddd;
            font-size: 14px;
            width: 100%;
            transition: 0.3s;
            font-family: 'Poppins', sans-serif;
        }
        
        input:focus, select:focus {
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
        
        /* Stats Grid */
        .stats-grid {
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
        
        .stat-icon.students { background: linear-gradient(45deg, #4361ee, #4cc9f0); }
        .stat-icon.teachers { background: linear-gradient(45deg, #7209b7, #b5179e); }
        .stat-icon.classes { background: linear-gradient(45deg, #f72585, #ff9e00); }
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
        
        /* Charts Section */
        .charts-section {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 20px;
            margin-bottom: 2rem;
        }
        
        .chart-card {
            background: white;
            padding: 25px;
            border-radius: 16px;
            box-shadow: var(--card-shadow);
        }
        
        .chart-card h3 {
            margin-bottom: 20px;
            color: var(--primary-dark);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .chart-container {
            position: relative;
            height: 300px;
            width: 100%;
        }
        
        /* Reports Grid */
        .reports-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 20px;
            margin-bottom: 2rem;
        }
        
        .report-card {
            background: white;
            padding: 25px;
            border-radius: 16px;
            box-shadow: var(--card-shadow);
            transition: 0.3s;
        }
        
        .report-card:hover {
            box-shadow: var(--card-hover);
        }
        
        .report-card h3 {
            margin-bottom: 20px;
            color: var(--primary-dark);
            display: flex;
            align-items: center;
            gap: 10px;
            border-bottom: 2px solid var(--primary);
            padding-bottom: 10px;
        }
        
        /* Tables */
        .table-wrapper {
            overflow-x: auto;
            margin-bottom: 1rem;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            border: none;
        }
        
        th, td {
            text-align: left;
            padding: 12px 10px;
            border-bottom: 1px solid #e0e0e0;
        }
        
        th {
            background: var(--primary);
            color: white;
            font-weight: 600;
            position: sticky;
            top: 0;
        }
        
        .progress-bar {
            width: 100%;
            height: 8px;
            background: #e0e0e0;
            border-radius: 4px;
            overflow: hidden;
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #4caf50, #8bc34a);
            border-radius: 4px;
            transition: width 0.3s ease;
        }
        
        .badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .badge.success { background: #e8f5e9; color: #2e7d32; }
        .badge.warning { background: #fff3e0; color: #ef6c00; }
        .badge.danger { background: #ffebee; color: #c62828; }
        .badge.info { background: #e3f2fd; color: #1565c0; }
        
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #6c757d;
        }
        
        .empty-state i {
            font-size: 48px;
            margin-bottom: 15px;
            color: #dee2e6;
        }
        
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
        }
        
        @media (max-width: 768px) {
            .filter-form,
            .stats-grid,
            .charts-section,
            .reports-grid {
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
        <a href="settings.php"><i class="fas fa-cog"></i> Settings</a>
        <a href="reports.php" class="active"><i class="fas fa-chart-bar"></i> Reports</a>
        <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        
        <div class="sidebar-footer">
            <p>&copy; 2023 MEJECRES SCHOOL</p>
            <p>Admin Panel v2.0</p>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content" id="mainContent">
        <div class="header">
            <h1>Reports & Analytics</h1>
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
            <i class="fas fa-chart-line"></i> Comprehensive Reports & Analytics Dashboard
        </div>

        <!-- Database Setup Alert -->
        <?php if (!$attendance_table_exists): ?>
        <div class="setup-alert">
            <h3><i class="fas fa-exclamation-triangle"></i> Setup Required</h3>
            <p>Attendance tracking is not yet configured. Some reports may not be available until you set up the attendance system.</p>
        </div>
        <?php endif; ?>

        <!-- Filter Section -->
        <div class="filter-section">
            <form method="GET" class="filter-form">
                <div class="form-group">
                    <label for="report_type"><i class="fas fa-chart-pie"></i> Report Type</label>
                    <select id="report_type" name="report_type">
                        <option value="overview" <?= $report_type === 'overview' ? 'selected' : '' ?>>Overview</option>
                        <option value="attendance" <?= $report_type === 'attendance' ? 'selected' : '' ?>>Attendance</option>
                        <option value="performance" <?= $report_type === 'performance' ? 'selected' : '' ?>>Performance</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="start_date"><i class="fas fa-calendar-alt"></i> Start Date</label>
                    <input type="date" id="start_date" name="start_date" value="<?= $start_date ?>">
                </div>
                
                <div class="form-group">
                    <label for="end_date"><i class="fas fa-calendar-alt"></i> End Date</label>
                    <input type="date" id="end_date" name="end_date" value="<?= $end_date ?>">
                </div>
                
                <div class="form-group">
                    <button type="submit">
                        <i class="fas fa-filter"></i> Apply Filters
                    </button>
                </div>
            </form>
        </div>

        <!-- Statistics Overview -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon students">
                    <i class="fas fa-user-graduate"></i>
                </div>
                <div class="stat-info">
                    <h3>Total Students</h3>
                    <div class="number"><?= number_format($total_students) ?></div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon teachers">
                    <i class="fas fa-chalkboard-teacher"></i>
                </div>
                <div class="stat-info">
                    <h3>Total Teachers</h3>
                    <div class="number"><?= number_format($total_teachers) ?></div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon classes">
                    <i class="fas fa-school"></i>
                </div>
                <div class="stat-info">
                    <h3>Active Classes</h3>
                    <div class="number"><?= number_format($total_classes) ?></div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon attendance">
                    <i class="fas fa-clipboard-check"></i>
                </div>
                <div class="stat-info">
                    <h3>Attendance Rate</h3>
                    <div class="number">
                        <?= $attendance_stats['total_records'] > 0 ? 
                            number_format(($attendance_stats['present'] / $attendance_stats['total_records']) * 100, 1) : '0' ?>%
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Section -->
        <?php if ($attendance_stats['total_records'] > 0): ?>
        <div class="charts-section">
            <div class="chart-card">
                <h3><i class="fas fa-chart-bar"></i> Attendance Overview</h3>
                <div class="chart-container">
                    <canvas id="attendanceChart"></canvas>
                </div>
            </div>
            
            <div class="chart-card">
                <h3><i class="fas fa-chart-pie"></i> Attendance Distribution</h3>
                <div class="chart-container">
                    <canvas id="attendancePieChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Reports Grid - Only show when attendance data exists -->
        <div class="reports-grid">
            <!-- Class-wise Attendance -->
            <?php if (!empty($class_attendance)): ?>
            <div class="report-card">
                <h3><i class="fas fa-users"></i> Class-wise Attendance</h3>
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>Class</th>
                                <th>Total Records</th>
                                <th>Present</th>
                                <th>Attendance Rate</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($class_attendance as $class): ?>
                            <tr>
                                <td>Class <?= htmlspecialchars($class['class']) ?></td>
                                <td><?= $class['total_records'] ?></td>
                                <td><?= $class['present'] ?></td>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 10px;">
                                        <span><?= $class['attendance_rate'] ?>%</span>
                                        <div class="progress-bar">
                                            <div class="progress-fill" style="width: <?= min($class['attendance_rate'], 100) ?>%"></div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>

            <!-- Recent Activities -->
            <?php if (!empty($recent_activities)): ?>
            <div class="report-card">
                <h3><i class="fas fa-history"></i> Recent Activities</h3>
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>Activity</th>
                                <th>Date</th>
                                <th>Type</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($recent_activities as $activity): ?>
                            <tr>
                                <td><?= htmlspecialchars($activity['description']) ?></td>
                                <td><?= date('M d, Y', strtotime($activity['activity_date'])) ?></td>
                                <td>
                                    <span class="badge 
                                        <?= $activity['type'] === 'attendance' ? 'info' : 
                                           ($activity['type'] === 'teacher' ? 'success' : 'warning') ?>">
                                        <?= ucfirst($activity['type']) ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>

    <script>
        // Initialize Charts only if data exists
        document.addEventListener('DOMContentLoaded', function() {
            <?php if ($attendance_stats['total_records'] > 0): ?>
            // Attendance Bar Chart
            const attendanceCtx = document.getElementById('attendanceChart').getContext('2d');
            const attendanceChart = new Chart(attendanceCtx, {
                type: 'bar',
                data: {
                    labels: ['Present', 'Absent', 'Late'],
                    datasets: [{
                        label: 'Attendance Records',
                        data: [
                            <?= $attendance_stats['present'] ?>,
                            <?= $attendance_stats['absent'] ?>,
                            <?= $attendance_stats['late'] ?>
                        ],
                        backgroundColor: [
                            'rgba(76, 175, 80, 0.8)',
                            'rgba(244, 67, 54, 0.8)',
                            'rgba(255, 152, 0, 0.8)'
                        ],
                        borderColor: [
                            'rgba(76, 175, 80, 1)',
                            'rgba(244, 67, 54, 1)',
                            'rgba(255, 152, 0, 1)'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        }
                    }
                }
            });

            // Attendance Pie Chart
            const pieCtx = document.getElementById('attendancePieChart').getContext('2d');
            const pieChart = new Chart(pieCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Present', 'Absent', 'Late'],
                    datasets: [{
                        data: [
                            <?= $attendance_stats['present'] ?>,
                            <?= $attendance_stats['absent'] ?>,
                            <?= $attendance_stats['late'] ?>
                        ],
                        backgroundColor: [
                            'rgba(76, 175, 80, 0.8)',
                            'rgba(244, 67, 54, 0.8)',
                            'rgba(255, 152, 0, 0.8)'
                        ],
                        borderColor: [
                            'rgba(76, 175, 80, 1)',
                            'rgba(244, 67, 54, 1)',
                            'rgba(255, 152, 0, 1)'
                        ],
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const value = context.raw;
                                    const percentage = Math.round((value / total) * 100);
                                    return `${context.label}: ${value} (${percentage}%)`;
                                }
                            }
                        }
                    }
                }
            });
            <?php endif; ?>
        });

        // Toggle sidebar on mobile
        const sidebarToggle = document.getElementById('sidebarToggle');
        const sidebar = document.getElementById('sidebar');
        
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
    </script>
</body>
</html>