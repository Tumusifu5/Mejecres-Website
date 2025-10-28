<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['admin_username'])) {
    header("Location: admin-login.php");
    exit;
}

include_once 'connection.php';

// Get today's and tomorrow's dates
$today = date('Y-m-d');
$tomorrow = date('Y-m-d', strtotime('+1 day'));

// Get all classes from ATTENDANCE table
$classes_result = $conn->query("SELECT DISTINCT class_name FROM attendance ORDER BY class_name");
$classes = [];
while ($row = $classes_result->fetch_assoc()) {
    $classes[] = $row['class_name'];
}

// Handle search
$search_class = isset($_GET['search_class']) ? trim($_GET['search_class']) : '';
$filtered_classes = $classes;

if (!empty($search_class)) {
    $filtered_classes = array_filter($classes, function($class) use ($search_class) {
        return stripos($class, $search_class) !== false;
    });
}

// Pagination setup for classes
$current_class_index = isset($_GET['class_index']) ? intval($_GET['class_index']) : 0;
$total_classes = count($filtered_classes);

// Ensure current_class_index is within bounds
if ($current_class_index < 0) $current_class_index = 0;
if ($current_class_index >= $total_classes) $current_class_index = $total_classes - 1;

// Get current class data
$current_class_data = null;
if ($total_classes > 0) {
    $current_class = $filtered_classes[$current_class_index];
    
    // Get students from ATTENDANCE records for this class
    $students_stmt = $conn->prepare("
        SELECT DISTINCT student_name 
        FROM attendance 
        WHERE class_name = ? 
        ORDER BY student_name
    ");
    $students_stmt->bind_param("s", $current_class);
    $students_stmt->execute();
    $students_result = $students_stmt->get_result();
    
    $class_data = [
        'class_name' => $current_class,
        'students' => [],
        'today_stats' => ['present' => 0, 'absent' => 0, 'total' => 0],
        'tomorrow_stats' => ['present' => 0, 'absent' => 0, 'total' => 0]
    ];
    
    $student_names = [];
    while ($student = $students_result->fetch_assoc()) {
        $student_names[] = $student['student_name'];
    }
    
    // Remove duplicates and process each student
    $unique_student_names = array_unique($student_names);
    
    foreach ($unique_student_names as $student_name) {
        // Get today's attendance - FIXED: Use created_at instead of timestamp
        $today_stmt = $conn->prepare("SELECT status FROM attendance WHERE student_name = ? AND class_name = ? AND DATE(created_at) = ?");
        $today_stmt->bind_param("sss", $student_name, $current_class, $today);
        $today_stmt->execute();
        $today_result = $today_stmt->get_result();
        $today_attendance = $today_result->fetch_assoc();
        
        // Get tomorrow's attendance (if any) - FIXED: Use created_at instead of timestamp
        $tomorrow_stmt = $conn->prepare("SELECT status FROM attendance WHERE student_name = ? AND class_name = ? AND DATE(created_at) = ?");
        $tomorrow_stmt->bind_param("sss", $student_name, $current_class, $tomorrow);
        $tomorrow_stmt->execute();
        $tomorrow_result = $tomorrow_stmt->get_result();
        $tomorrow_attendance = $tomorrow_result->fetch_assoc();
        
        // Try to get student number from students table if it exists
        $student_number = 'N/A';
        // Extract first name and last name from student_name (assuming format "First Last")
        $name_parts = explode(' ', $student_name, 2);
        if (count($name_parts) >= 2) {
            $first_name = $name_parts[0];
            $last_name = $name_parts[1];
            
            $student_number_stmt = $conn->prepare("SELECT student_number FROM students WHERE first_name = ? AND last_name = ? AND class = ? LIMIT 1");
            $student_number_stmt->bind_param("sss", $first_name, $last_name, $current_class);
            $student_number_stmt->execute();
            $student_number_result = $student_number_stmt->get_result();
            $student_number_data = $student_number_result->fetch_assoc();
            
            if ($student_number_data) {
                $student_number = $student_number_data['student_number'];
            }
        }
        
        $student_data = [
            'name' => $student_name,
            'student_number' => $student_number,
            'today' => $today_attendance ? ($today_attendance['status'] == 1 ? 'Present' : 'Absent') : 'Not Marked',
            'tomorrow' => $tomorrow_attendance ? ($tomorrow_attendance['status'] == 1 ? 'Present' : 'Absent') : 'Not Marked'
        ];
        
        // Update statistics
        $class_data['today_stats']['total']++;
        $class_data['tomorrow_stats']['total']++;
        
        if ($student_data['today'] === 'Present') {
            $class_data['today_stats']['present']++;
        } elseif ($student_data['today'] === 'Absent') {
            $class_data['today_stats']['absent']++;
        }
        
        if ($student_data['tomorrow'] === 'Present') {
            $class_data['tomorrow_stats']['present']++;
        } elseif ($student_data['tomorrow'] === 'Absent') {
            $class_data['tomorrow_stats']['absent']++;
        }
        
        $class_data['students'][] = $student_data;
    }
    
    $current_class_data = $class_data;
}

// Calculate overall statistics
$total_today_present = 0;
$total_today_absent = 0;
$total_today_marked = 0;
$total_tomorrow_present = 0;
$total_tomorrow_absent = 0;
$total_tomorrow_marked = 0;
$total_students = 0;

if ($current_class_data) {
    $total_today_present = $current_class_data['today_stats']['present'];
    $total_today_absent = $current_class_data['today_stats']['absent'];
    $total_today_marked = $current_class_data['today_stats']['present'] + $current_class_data['today_stats']['absent'];
    $total_tomorrow_present = $current_class_data['tomorrow_stats']['present'];
    $total_tomorrow_absent = $current_class_data['tomorrow_stats']['absent'];
    $total_tomorrow_marked = $current_class_data['tomorrow_stats']['present'] + $current_class_data['tomorrow_stats']['absent'];
    $total_students = $current_class_data['today_stats']['total'];
}

$today_attendance_rate = $total_students > 0 ? round(($total_today_marked / $total_students) * 100, 1) : 0;
$today_present_rate = $total_today_marked > 0 ? round(($total_today_present / $total_today_marked) * 100, 1) : 0;

// Pagination for students table
$students_per_page = 10;
$total_students_count = $current_class_data ? count($current_class_data['students']) : 0;
$total_student_pages = ceil($total_students_count / $students_per_page);
$current_student_page = isset($_GET['student_page']) ? max(1, intval($_GET['student_page'])) : 1;

// Ensure current_student_page is within bounds
if ($current_student_page < 1) $current_student_page = 1;
if ($current_student_page > $total_student_pages) $current_student_page = $total_student_pages;

// Get students for current page
$paginated_students = [];
if ($current_class_data && !empty($current_class_data['students'])) {
    $start_index = ($current_student_page - 1) * $students_per_page;
    $paginated_students = array_slice($current_class_data['students'], $start_index, $students_per_page);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Daily Attendance Report - MEJECRES SCHOOL</title>
<link rel="icon" href="logo.jpg" type="image/x-icon">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
:root {
  --primary: #4361ee;
  --primary-dark: #3a56d4;
  --secondary: #7209b7;
  --success: #4cc9f0;
  --danger: #f72585;
  --warning: #ff9e00;
  --info: #17a2b8;
  --light: #f8f9fa;
  --dark: #212529;
  --bg: #f5f7fa;
  --card-bg: #ffffff;
  --sidebar-width: 260px;
  --card-shadow: 0 6px 18px rgba(0,0,0,0.08);
  --card-hover: 0 12px 24px rgba(0,0,0,0.12);
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
  transition: all 0.3s ease;
}

body.sidebar-open {
  overflow: hidden;
}

/* Fixed Sidebar Styles */
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
  top: 0;
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
  z-index: 1001;
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

/* Overlay for mobile */
.sidebar-overlay {
  display: none;
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0,0,0,0.5);
  z-index: 999;
  backdrop-filter: blur(2px);
}

.sidebar-overlay.active {
  display: block;
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

/* Search Container */
.search-container {
  background: white;
  padding: 25px;
  border-radius: 16px;
  box-shadow: var(--card-shadow);
  margin-bottom: 2rem;
}

.search-container h2 {
  margin-bottom: 15px;
  color: var(--primary-dark);
  display: flex;
  align-items: center;
  gap: 10px;
}

.search-box {
  display: flex;
  gap: 15px;
  align-items: center;
  flex-wrap: wrap;
}

.search-input {
  flex: 1;
  min-width: 300px;
  padding: 14px 20px;
  border: 2px solid #e9ecef;
  border-radius: 12px;
  font-size: 15px;
  transition: 0.3s;
  background: #f8f9fa;
}

.search-input:focus {
  border-color: var(--primary);
  outline: none;
  box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.1);
  background: white;
}

.search-btn {
  background: var(--primary);
  color: white;
  border: none;
  padding: 14px 24px;
  border-radius: 12px;
  cursor: pointer;
  font-weight: 600;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  gap: 8px;
}

.search-btn:hover {
  background: var(--primary-dark);
  transform: translateY(-2px);
  box-shadow: 0 5px 15px rgba(67, 97, 238, 0.3);
}

.clear-btn {
  background: var(--danger);
  color: white;
  border: none;
  padding: 14px 20px;
  border-radius: 12px;
  cursor: pointer;
  font-weight: 600;
  transition: all 0.3s ease;
  text-decoration: none;
  display: flex;
  align-items: center;
  gap: 8px;
}

.clear-btn:hover {
  background: #e0006d;
  transform: translateY(-2px);
}

.search-results {
  margin-top: 15px;
  padding: 15px;
  background: #e7f3ff;
  border-radius: 8px;
  border: 1px solid #b3d9ff;
  font-weight: 500;
}

/* Date Header */
.date-header {
  background: linear-gradient(135deg, var(--primary), var(--secondary));
  color: white;
  padding: 25px;
  border-radius: 16px;
  margin-bottom: 2rem;
  box-shadow: var(--card-shadow);
  text-align: center;
}

.date-header h2 {
  color: white;
  margin: 0 0 20px 0;
  font-size: 1.5rem;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
}

.date-display {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 20px;
}

.date-card {
  background: rgba(255,255,255,0.15);
  padding: 20px;
  border-radius: 12px;
  backdrop-filter: blur(10px);
  border: 1px solid rgba(255,255,255,0.2);
}

.date-card h3 {
  margin: 0 0 10px 0;
  font-size: 1.1rem;
  opacity: 0.9;
}

.date-card .date {
  font-size: 1.4rem;
  font-weight: 700;
}

/* Class Navigation */
.class-navigation {
  background: white;
  padding: 20px;
  border-radius: 16px;
  box-shadow: var(--card-shadow);
  margin-bottom: 2rem;
  text-align: center;
}

.class-nav-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 15px;
  flex-wrap: wrap;
  gap: 15px;
}

.class-title {
  font-size: 1.4rem;
  font-weight: 700;
  color: var(--primary-dark);
  display: flex;
  align-items: center;
  gap: 10px;
}

.class-pagination {
  display: flex;
  align-items: center;
  gap: 15px;
}

.nav-btn {
  background: var(--primary);
  color: white;
  border: none;
  padding: 12px 20px;
  border-radius: 10px;
  cursor: pointer;
  font-weight: 600;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  gap: 8px;
  text-decoration: none;
}

.nav-btn:hover:not(:disabled) {
  background: var(--primary-dark);
  transform: translateY(-2px);
  box-shadow: 0 5px 15px rgba(67, 97, 238, 0.3);
}

.nav-btn:disabled {
  background: #6c757d;
  cursor: not-allowed;
  opacity: 0.6;
}

.class-counter {
  font-weight: 600;
  color: var(--dark);
  background: #f8f9fa;
  padding: 8px 16px;
  border-radius: 20px;
  border: 2px solid #e9ecef;
}

.class-list {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  justify-content: center;
  margin-top: 15px;
}

.class-chip {
  padding: 8px 16px;
  background: #f8f9fa;
  border: 2px solid #e9ecef;
  border-radius: 20px;
  font-size: 0.9rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.3s ease;
  text-decoration: none;
  color: var(--dark);
}

.class-chip:hover {
  background: var(--primary);
  color: white;
  border-color: var(--primary);
  transform: translateY(-2px);
}

.class-chip.active {
  background: var(--primary);
  color: white;
  border-color: var(--primary);
  box-shadow: 0 4px 12px rgba(67, 97, 238, 0.3);
}

/* Statistics Cards */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 20px;
  margin-bottom: 2rem;
}

.stat-card {
  background: white;
  padding: 25px;
  border-radius: 16px;
  box-shadow: var(--card-shadow);
  text-align: center;
  transition: 0.3s;
  border-left: 4px solid var(--primary);
  position: relative;
  overflow: hidden;
}

.stat-card:hover {
  transform: translateY(-5px);
  box-shadow: var(--card-hover);
}

.stat-card::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 4px;
  background: linear-gradient(90deg, var(--primary), var(--secondary));
}

.stat-icon {
  font-size: 2.5rem;
  margin-bottom: 15px;
  color: var(--primary);
}

.stat-number {
  font-size: 2.2rem;
  font-weight: 700;
  color: var(--dark);
  margin-bottom: 5px;
}

.stat-label {
  color: var(--dark);
  font-weight: 500;
  font-size: 0.9rem;
  margin-bottom: 10px;
}

.stat-percentage {
  font-size: 1.1rem;
  font-weight: 600;
  padding: 4px 12px;
  border-radius: 20px;
  display: inline-block;
}

.percentage-high {
  background: #e8f5e9;
  color: #2e7d32;
}

.percentage-medium {
  background: #fff3e0;
  color: #ef6c00;
}

.percentage-low {
  background: #ffebee;
  color: #c62828;
}

/* Progress Bars */
.progress-container {
  background: white;
  padding: 25px;
  border-radius: 16px;
  box-shadow: var(--card-shadow);
  margin-bottom: 2rem;
}

.progress-container h2 {
  margin-bottom: 20px;
  color: var(--primary-dark);
  display: flex;
  align-items: center;
  gap: 10px;
}

.progress-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 20px;
}

.progress-item {
  margin-bottom: 15px;
}

.progress-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 8px;
}

.progress-label {
  font-weight: 600;
  color: var(--dark);
}

.progress-value {
  font-weight: 600;
  color: var(--primary);
}

.progress-bar {
  height: 8px;
  background: #e9ecef;
  border-radius: 4px;
  overflow: hidden;
}

.progress-fill {
  height: 100%;
  border-radius: 4px;
  transition: width 0.8s ease-in-out;
}

.progress-present {
  background: linear-gradient(90deg, #4caf50, #8bc34a);
}

.progress-absent {
  background: linear-gradient(90deg, #f44336, #ff9800);
}

.progress-marked {
  background: linear-gradient(90deg, #2196f3, #03a9f4);
}

/* Attendance Table */
.attendance-container {
  background: white;
  padding: 25px;
  border-radius: 16px;
  box-shadow: var(--card-shadow);
  margin-bottom: 25px;
}

.attendance-container h2 {
  margin-bottom: 20px;
  color: var(--primary-dark);
  display: flex;
  align-items: center;
  gap: 10px;
}

.attendance-table-container {
  overflow-x: auto;
  border-radius: 12px;
  border: 1px solid #e9ecef;
}

.attendance-table {
  width: 100%;
  border-collapse: collapse;
  min-width: 800px;
}

.attendance-table th {
  background: linear-gradient(135deg, var(--primary), var(--primary-dark));
  color: white;
  padding: 16px 12px;
  text-align: left;
  font-weight: 600;
  border: none;
  position: sticky;
  top: 0;
}

.attendance-table td {
  padding: 14px 12px;
  border-bottom: 1px solid #e9ecef;
  vertical-align: middle;
}

.attendance-table tr:hover {
  background: #f8f9fa;
}

.attendance-table tr:last-child td {
  border-bottom: none;
}

/* Status Badges */
.status-badge {
  padding: 6px 12px;
  border-radius: 20px;
  font-weight: 600;
  font-size: 0.8rem;
  display: inline-flex;
  align-items: center;
  gap: 5px;
  min-width: 90px;
  justify-content: center;
}

.status-present {
  background: #e8f5e9;
  color: #2e7d32;
  border: 1px solid #c8e6c9;
}

.status-absent {
  background: #ffebee;
  color: #c62828;
  border: 1px solid #ffcdd2;
}

.status-not-marked {
  background: #fff3e0;
  color: #ef6c00;
  border: 1px solid #ffe0b2;
}

/* Student Pagination */
.student-pagination {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-top: 20px;
  padding: 15px;
  background: #f8f9fa;
  border-radius: 12px;
  border: 1px solid #e9ecef;
}

.student-pagination-info {
  font-weight: 600;
  color: var(--dark);
}

.student-pagination-controls {
  display: flex;
  gap: 10px;
  align-items: center;
}

.student-page-btn {
  background: var(--primary);
  color: white;
  border: none;
  padding: 10px 16px;
  border-radius: 8px;
  cursor: pointer;
  font-weight: 600;
  transition: all 0.3s ease;
  text-decoration: none;
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 0.9rem;
}

.student-page-btn:hover:not(.disabled) {
  background: var(--primary-dark);
  transform: translateY(-2px);
}

.student-page-btn.disabled {
  background: #6c757d;
  cursor: not-allowed;
  opacity: 0.6;
}

.student-page-numbers {
  display: flex;
  gap: 5px;
  align-items: center;
  margin: 0 10px;
}

.student-page-number {
  padding: 8px 12px;
  border-radius: 6px;
  font-weight: 600;
  text-decoration: none;
  color: var(--dark);
  background: white;
  border: 1px solid #e9ecef;
  transition: all 0.3s ease;
}

.student-page-number:hover {
  background: #f8f9fa;
}

.student-page-number.active {
  background: var(--primary);
  color: white;
  border-color: var(--primary);
}

/* Empty State */
.empty-state {
  text-align: center;
  padding: 60px 20px;
  color: #6c757d;
}

.empty-state i {
  font-size: 4rem;
  margin-bottom: 20px;
  color: #dee2e6;
}

.empty-state h3 {
  font-size: 1.5rem;
  margin-bottom: 10px;
  color: #6c757d;
}

.empty-state p {
  font-size: 1rem;
  color: #6c757d;
  margin-bottom: 20px;
}

/* Responsive Styles */
@media (max-width: 1024px) {
  .sidebar {
    left: -270px;
    height: 100vh;
    top: 0;
  }
  
  .sidebar.active {
    left: 0;
    height: 100vh;
  }
  
  .sidebar-toggle {
    display: flex;
  }
  
  .main-content {
    margin-left: 0;
    padding-top: 70px;
    width: 100%;
  }
  
  .search-box {
    flex-direction: column;
  }
  
  .search-input {
    min-width: auto;
    width: 100%;
  }
  
  .class-nav-header {
    flex-direction: column;
    text-align: center;
  }
  
  .student-pagination {
    flex-direction: column;
    gap: 15px;
    text-align: center;
  }
}

@media (max-width: 768px) {
  .stats-grid {
    grid-template-columns: 1fr;
  }
  
  .progress-grid {
    grid-template-columns: 1fr;
  }
  
  .date-display {
    grid-template-columns: 1fr;
  }
  
  .class-pagination {
    flex-wrap: wrap;
    justify-content: center;
  }
  
  .attendance-table {
    font-size: 0.8rem;
  }
  
  .attendance-table th,
  .attendance-table td {
    padding: 10px 8px;
  }
  
  .student-pagination-controls {
    flex-wrap: wrap;
    justify-content: center;
  }
  
  .student-page-numbers {
    order: -1;
    width: 100%;
    justify-content: center;
    margin: 10px 0;
  }
  
  .page-title {
    font-size: 20px;
    padding: 20px;
  }
  
  .header h1 {
    font-size: 1.5rem;
  }
}

@media (min-width: 1025px) {
  .sidebar-toggle {
    display: none;
  }
  
  .sidebar-overlay {
    display: none !important;
  }
}

/* Animation for progress bars */
@keyframes growWidth {
  from { width: 0%; }
  to { width: var(--final-width); }
}

.progress-fill {
  animation: growWidth 1.5s ease-out;
}

/* Sidebar scrollbar */
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

.sidebar::-webkit-scrollbar-thumb:hover {
  background: rgba(255,255,255,0.5);
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
    
    <!-- Overlay for mobile -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <h2><i class="fas fa-graduation-cap"></i> MEJECRES ADMIN</h2>
        </div>
        <a href="admin.php"><i class="fas fa-home"></i> Dashboard</a>
        <a href="all-teachers.php"><i class="fas fa-chalkboard-teacher"></i> Teachers</a>
        <a href="all-students.php"><i class="fas fa-user-graduate"></i> All Students</a>
        <a href="student.php"><i class="fas fa-plus-circle"></i> Add Students</a>
        <a href="attendance.php" class="active"><i class="fas fa-clipboard-check"></i> Attendance</a>
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
            <h1>Attendance Management</h1>
            <div class="user-info">
                <div class="user-avatar">
                    <?= strtoupper(substr($_SESSION['admin_username'], 0, 1)) ?>
                </div>
                <div>
                    <div style="font-weight: 600;"><?= htmlspecialchars($_SESSION['admin_username']) ?></div>
                    <div style="font-size: 0.85rem; color: #6c757d;">Attendance Administrator</div>
                </div>
            </div>
        </div>

        <div class="page-title">
            <i class="fas fa-clipboard-check"></i> Daily Attendance Report & Analytics
        </div>

        <!-- Search Container -->
        <div class="search-container">
            <h2><i class="fas fa-search"></i> Search Classes</h2>
            <form method="GET" action="" class="search-box">
                <input type="hidden" name="class_index" value="<?php echo $current_class_index; ?>">
                <input type="hidden" name="student_page" value="<?php echo $current_student_page; ?>">
                <input 
                    type="text" 
                    name="search_class" 
                    class="search-input" 
                    placeholder="Enter class name to search (e.g., P1, S2, Form 3...)" 
                    value="<?php echo htmlspecialchars($search_class); ?>"
                >
                <button type="submit" class="search-btn">
                    <i class="fas fa-search"></i> Search Classes
                </button>
                <?php if (!empty($search_class)): ?>
                    <a href="attendance.php" class="clear-btn">
                        <i class="fas fa-times"></i> Clear Search
                    </a>
                <?php endif; ?>
            </form>
            
            <?php if (!empty($search_class)): ?>
                <div class="search-results">
                    <i class="fas fa-info-circle"></i>
                    Found <?php echo count($filtered_classes); ?> class(es) matching "<strong><?php echo htmlspecialchars($search_class); ?></strong>"
                    out of <?php echo count($classes); ?> total classes with attendance records.
                </div>
            <?php else: ?>
                <div class="search-results">
                    <i class="fas fa-info-circle"></i>
                    <strong>Total Classes with Attendance Records:</strong> <?php echo count($classes); ?> classes
                </div>
            <?php endif; ?>
        </div>

        <!-- Date Header -->
        <div class="date-header">
            <h2><i class="fas fa-calendar-alt"></i> Attendance Period</h2>
            <div class="date-display">
                <div class="date-card">
                    <h3>Today's Attendance</h3>
                    <div class="date"><?php echo date('l, F j, Y'); ?></div>
                </div>
                <div class="date-card">
                    <h3>Tomorrow's Preview</h3>
                    <div class="date"><?php echo date('l, F j, Y', strtotime('+1 day')); ?></div>
                </div>
            </div>
        </div>

        <?php if (empty($filtered_classes)): ?>
            <div class="empty-state">
                <i class="fas fa-clipboard-list"></i>
                <h3>No Classes Found</h3>
                <p>
                    <?php if (!empty($search_class)): ?>
                        No classes with attendance records found matching "<?php echo htmlspecialchars($search_class); ?>".
                    <?php else: ?>
                        No attendance records found in the system for any classes.
                    <?php endif; ?>
                </p>
                <?php if (!empty($search_class)): ?>
                    <a href="attendance.php" class="search-btn" style="text-decoration: none;">
                        <i class="fas fa-arrow-left"></i> View All Classes
                    </a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <!-- Class Navigation -->
            <div class="class-navigation">
                <div class="class-nav-header">
                    <div class="class-title">
                        <i class="fas fa-chalkboard-teacher"></i>
                        <?php echo htmlspecialchars($current_class_data['class_name']); ?>
                    </div>
                    <div class="class-pagination">
                        <a 
                            href="?search_class=<?php echo urlencode($search_class); ?>&class_index=<?php echo max(0, $current_class_index - 1); ?>&student_page=1" 
                            class="nav-btn <?php echo $current_class_index <= 0 ? 'disabled' : ''; ?>"
                            <?php echo $current_class_index <= 0 ? 'onclick="return false;"' : ''; ?>
                        >
                            <i class="fas fa-chevron-left"></i> Previous Class
                        </a>
                        
                        <div class="class-counter">
                            Class <?php echo $current_class_index + 1; ?> of <?php echo $total_classes; ?>
                        </div>
                        
                        <a 
                            href="?search_class=<?php echo urlencode($search_class); ?>&class_index=<?php echo min($total_classes - 1, $current_class_index + 1); ?>&student_page=1" 
                            class="nav-btn <?php echo $current_class_index >= $total_classes - 1 ? 'disabled' : ''; ?>"
                            <?php echo $current_class_index >= $total_classes - 1 ? 'onclick="return false;"' : ''; ?>
                        >
                            Next Class <i class="fas fa-chevron-right"></i>
                        </a>
                    </div>
                </div>
                
                <div class="class-list">
                    <?php foreach($filtered_classes as $index => $class): ?>
                        <a 
                            href="?search_class=<?php echo urlencode($search_class); ?>&class_index=<?php echo $index; ?>&student_page=1" 
                            class="class-chip <?php echo $index === $current_class_index ? 'active' : ''; ?>"
                        >
                            <?php echo htmlspecialchars($class); ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-user-check"></i>
                    </div>
                    <div class="stat-number"><?php echo $total_today_present; ?></div>
                    <div class="stat-label">Students Present Today</div>
                    <div class="stat-percentage percentage-high">
                        <?php echo $total_today_marked > 0 ? round(($total_today_present / $total_today_marked) * 100, 1) : 0; ?>%
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-user-times"></i>
                    </div>
                    <div class="stat-number"><?php echo $total_today_absent; ?></div>
                    <div class="stat-label">Students Absent Today</div>
                    <div class="stat-percentage percentage-low">
                        <?php echo $total_today_marked > 0 ? round(($total_today_absent / $total_today_marked) * 100, 1) : 0; ?>%
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-clipboard-list"></i>
                    </div>
                    <div class="stat-number"><?php echo $total_today_marked; ?>/<?php echo $total_students; ?></div>
                    <div class="stat-label">Attendance Marked Today</div>
                    <div class="stat-percentage percentage-medium">
                        <?php echo $today_attendance_rate; ?>%
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                    <div class="stat-number"><?php echo $total_tomorrow_marked; ?>/<?php echo $total_students; ?></div>
                    <div class="stat-label">Preview for Tomorrow</div>
                    <div class="stat-percentage <?php echo $total_tomorrow_marked > 0 ? 'percentage-medium' : 'percentage-low'; ?>">
                        <?php echo $total_students > 0 ? round(($total_tomorrow_marked / $total_students) * 100, 1) : 0; ?>%
                    </div>
                </div>
            </div>

            <!-- Progress Bars -->
            <div class="progress-container">
                <h2><i class="fas fa-chart-line"></i> Attendance Progress</h2>
                <div class="progress-grid">
                    <div>
                        <h3 style="margin-bottom: 15px; color: var(--primary-dark);">Today's Progress</h3>
                        <div class="progress-item">
                            <div class="progress-header">
                                <span class="progress-label">Attendance Marked</span>
                                <span class="progress-value"><?php echo $total_today_marked; ?>/<?php echo $total_students; ?></span>
                            </div>
                            <div class="progress-bar">
                                <div class="progress-fill progress-marked" style="--final-width: <?php echo $today_attendance_rate; ?>%; width: <?php echo $today_attendance_rate; ?>%;"></div>
                            </div>
                        </div>
                        
                        <div class="progress-item">
                            <div class="progress-header">
                                <span class="progress-label">Present Rate</span>
                                <span class="progress-value"><?php echo $today_present_rate; ?>%</span>
                            </div>
                            <div class="progress-bar">
                                <div class="progress-fill progress-present" style="--final-width: <?php echo $today_present_rate; ?>%; width: <?php echo $today_present_rate; ?>%;"></div>
                            </div>
                        </div>
                    </div>
                    
                    <div>
                        <h3 style="margin-bottom: 15px; color: var(--primary-dark);">Tomorrow's Preview</h3>
                        <div class="progress-item">
                            <div class="progress-header">
                                <span class="progress-label">Attendance Marked</span>
                                <span class="progress-value"><?php echo $total_tomorrow_marked; ?>/<?php echo $total_students; ?></span>
                            </div>
                            <div class="progress-bar">
                                <div class="progress-fill progress-marked" style="--final-width: <?php echo $total_students > 0 ? round(($total_tomorrow_marked / $total_students) * 100, 1) : 0; ?>%; width: <?php echo $total_students > 0 ? round(($total_tomorrow_marked / $total_students) * 100, 1) : 0; ?>%;"></div>
                            </div>
                        </div>
                        
                        <div class="progress-item">
                            <div class="progress-header">
                                <span class="progress-label">Present Rate</span>
                                <span class="progress-value">
                                    <?php echo $total_tomorrow_marked > 0 ? round(($total_tomorrow_present / $total_tomorrow_marked) * 100, 1) : 0; ?>%
                                </span>
                            </div>
                            <div class="progress-bar">
                                <div class="progress-fill progress-present" style="--final-width: <?php echo $total_tomorrow_marked > 0 ? round(($total_tomorrow_present / $total_tomorrow_marked) * 100, 1) : 0; ?>%; width: <?php echo $total_tomorrow_marked > 0 ? round(($total_tomorrow_present / $total_tomorrow_marked) * 100, 1) : 0; ?>%;"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Attendance Table -->
            <div class="attendance-container">
                <h2><i class="fas fa-list-alt"></i> Student Attendance Details</h2>
                <div class="attendance-table-container">
                    <?php if (empty($paginated_students)): ?>
                        <div class="empty-state" style="padding: 40px 20px;">
                            <i class="fas fa-users"></i>
                            <h3>No Students Found</h3>
                            <p>No students found for this class with attendance records.</p>
                        </div>
                    <?php else: ?>
                        <table class="attendance-table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Student Number</th>
                                    <th>Student Name</th>
                                    <th>Today's Status</th>
                                    <th>Tomorrow's Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $start_number = (($current_student_page - 1) * $students_per_page) + 1;
                                foreach($paginated_students as $index => $student): 
                                ?>
                                    <tr>
                                        <td><?php echo $start_number + $index; ?></td>
                                        <td><?php echo htmlspecialchars($student['student_number']); ?></td>
                                        <td><?php echo htmlspecialchars($student['name']); ?></td>
                                        <td>
                                            <span class="status-badge 
                                                <?php 
                                                if ($student['today'] === 'Present') echo 'status-present';
                                                elseif ($student['today'] === 'Absent') echo 'status-absent';
                                                else echo 'status-not-marked';
                                                ?>
                                            ">
                                                <?php if ($student['today'] === 'Present'): ?>
                                                    <i class="fas fa-check-circle"></i>
                                                <?php elseif ($student['today'] === 'Absent'): ?>
                                                    <i class="fas fa-times-circle"></i>
                                                <?php else: ?>
                                                    <i class="fas fa-clock"></i>
                                                <?php endif; ?>
                                                <?php echo htmlspecialchars($student['today']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="status-badge 
                                                <?php 
                                                if ($student['tomorrow'] === 'Present') echo 'status-present';
                                                elseif ($student['tomorrow'] === 'Absent') echo 'status-absent';
                                                else echo 'status-not-marked';
                                                ?>
                                            ">
                                                <?php if ($student['tomorrow'] === 'Present'): ?>
                                                    <i class="fas fa-check-circle"></i>
                                                <?php elseif ($student['tomorrow'] === 'Absent'): ?>
                                                    <i class="fas fa-times-circle"></i>
                                                <?php else: ?>
                                                    <i class="fas fa-clock"></i>
                                                <?php endif; ?>
                                                <?php echo htmlspecialchars($student['tomorrow']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>

                <!-- Student Pagination -->
                <?php if ($total_student_pages > 1): ?>
                    <div class="student-pagination">
                        <div class="student-pagination-info">
                            Showing <?php echo count($paginated_students); ?> of <?php echo $total_students_count; ?> students
                            (Page <?php echo $current_student_page; ?> of <?php echo $total_student_pages; ?>)
                        </div>
                        
                        <div class="student-pagination-controls">
                            <a 
                                href="?search_class=<?php echo urlencode($search_class); ?>&class_index=<?php echo $current_class_index; ?>&student_page=1" 
                                class="student-page-btn <?php echo $current_student_page <= 1 ? 'disabled' : ''; ?>"
                                <?php echo $current_student_page <= 1 ? 'onclick="return false;"' : ''; ?>
                            >
                                <i class="fas fa-angle-double-left"></i> First
                            </a>
                            
                            <a 
                                href="?search_class=<?php echo urlencode($search_class); ?>&class_index=<?php echo $current_class_index; ?>&student_page=<?php echo max(1, $current_student_page - 1); ?>" 
                                class="student-page-btn <?php echo $current_student_page <= 1 ? 'disabled' : ''; ?>"
                                <?php echo $current_student_page <= 1 ? 'onclick="return false;"' : ''; ?>
                            >
                                <i class="fas fa-chevron-left"></i> Previous
                            </a>
                            
                            <div class="student-page-numbers">
                                <?php
                                // Show page numbers
                                $start_page = max(1, $current_student_page - 2);
                                $end_page = min($total_student_pages, $current_student_page + 2);
                                
                                for ($page = $start_page; $page <= $end_page; $page++):
                                ?>
                                    <a 
                                        href="?search_class=<?php echo urlencode($search_class); ?>&class_index=<?php echo $current_class_index; ?>&student_page=<?php echo $page; ?>" 
                                        class="student-page-number <?php echo $page == $current_student_page ? 'active' : ''; ?>"
                                    >
                                        <?php echo $page; ?>
                                    </a>
                                <?php endfor; ?>
                            </div>
                            
                            <a 
                                href="?search_class=<?php echo urlencode($search_class); ?>&class_index=<?php echo $current_class_index; ?>&student_page=<?php echo min($total_student_pages, $current_student_page + 1); ?>" 
                                class="student-page-btn <?php echo $current_student_page >= $total_student_pages ? 'disabled' : ''; ?>"
                                <?php echo $current_student_page >= $total_student_pages ? 'onclick="return false;"' : ''; ?>
                            >
                                Next <i class="fas fa-chevron-right"></i>
                            </a>
                            
                            <a 
                                href="?search_class=<?php echo urlencode($search_class); ?>&class_index=<?php echo $current_class_index; ?>&student_page=<?php echo $total_student_pages; ?>" 
                                class="student-page-btn <?php echo $current_student_page >= $total_student_pages ? 'disabled' : ''; ?>"
                                <?php echo $current_student_page >= $total_student_pages ? 'onclick="return false;"' : ''; ?>
                            >
                                Last <i class="fas fa-angle-double-right"></i>
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <script>
    // Enhanced Sidebar toggle functionality
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('sidebar');
    const sidebarOverlay = document.getElementById('sidebarOverlay');
    const mainContent = document.getElementById('mainContent');
    
    sidebarToggle.addEventListener('click', (e) => {
        e.stopPropagation();
        sidebar.classList.toggle('active');
        sidebarToggle.classList.toggle('active');
        sidebarOverlay.classList.toggle('active');
        document.body.classList.toggle('sidebar-open');
    });

    // Close sidebar when clicking overlay
    sidebarOverlay.addEventListener('click', () => {
        sidebar.classList.remove('active');
        sidebarToggle.classList.remove('active');
        sidebarOverlay.classList.remove('active');
        document.body.classList.remove('sidebar-open');
    });

    // Close sidebar when clicking on a link
    sidebar.addEventListener('click', (e) => {
        if (e.target.tagName === 'A' && window.innerWidth <= 1024) {
            sidebar.classList.remove('active');
            sidebarToggle.classList.remove('active');
            sidebarOverlay.classList.remove('active');
            document.body.classList.remove('sidebar-open');
        }
    });

    // Handle window resize
    window.addEventListener('resize', () => {
        if (window.innerWidth > 1024) {
            sidebar.classList.remove('active');
            sidebarToggle.classList.remove('active');
            sidebarOverlay.classList.remove('active');
            document.body.classList.remove('sidebar-open');
        }
    });

    // Close sidebar with Escape key
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && sidebar.classList.contains('active')) {
            sidebar.classList.remove('active');
            sidebarToggle.classList.remove('active');
            sidebarOverlay.classList.remove('active');
            document.body.classList.remove('sidebar-open');
        }
    });

    // Update progress bar animation
    document.addEventListener('DOMContentLoaded', function() {
        const progressBars = document.querySelectorAll('.progress-fill');
        progressBars.forEach(bar => {
            const width = bar.style.width;
            bar.style.width = '0%';
            setTimeout(() => {
                bar.style.width = width;
            }, 100);
        });
    });
    </script>
</body>
</html>