<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['teacher_id'])) {
    header("Location: teacher-login.php");
    exit;
}

include_once 'connection.php';

// Create attendance table if it doesn't exist (with proper structure)
$create_table_sql = "
CREATE TABLE IF NOT EXISTS attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    student_name VARCHAR(255) NOT NULL,
    class_name VARCHAR(100) NOT NULL,
    status INT NOT NULL,
    teacher_id INT NOT NULL,
    attendance_date DATE NOT NULL,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (teacher_id) REFERENCES teachers(id),
    FOREIGN KEY (student_id) REFERENCES students(id),
    UNIQUE KEY unique_attendance (student_id, attendance_date)
)";
$conn->query($create_table_sql);

$stmt = $conn->prepare("SELECT fullname FROM teachers WHERE id = ?");
$stmt->bind_param("i", $_SESSION['teacher_id']);
$stmt->execute();
$res = $stmt->get_result();
$teacher = $res->fetch_assoc();
$teacher_name = $teacher['fullname'] ?? '';
$teacher_id = $_SESSION['teacher_id'];

// Fetch classes from students table
$classes_result = $conn->query("SELECT DISTINCT class FROM students ORDER BY class");
$classes = [];
while ($row = $classes_result->fetch_assoc()) {
    $classes[] = $row['class'];
}

// Pagination and student data loading
$students = [];
$all_class_students = []; // Store ALL students in the selected class
$totalStudents = 0;
$currentPage = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$rowsPerPage = isset($_GET['rows']) ? max(10, intval($_GET['rows'])) : 10;
$offset = ($currentPage - 1) * $rowsPerPage;

if (isset($_GET['class']) && !empty($_GET['class'])) {
    $selected_class = $_GET['class'];
    
    // Get total count for pagination
    $count_stmt = $conn->prepare("SELECT COUNT(*) as total FROM students WHERE class = ?");
    $count_stmt->bind_param("s", $selected_class);
    $count_stmt->execute();
    $count_result = $count_stmt->get_result();
    $totalStudents = $count_result->fetch_assoc()['total'];
    
    // Fetch ALL students for this class (for saving attendance)
    $all_stmt = $conn->prepare("SELECT id, student_number, first_name, last_name, class FROM students WHERE class = ? ORDER BY first_name, last_name");
    $all_stmt->bind_param("s", $selected_class);
    $all_stmt->execute();
    $all_result = $all_stmt->get_result();
    
    while ($row = $all_result->fetch_assoc()) {
        $all_class_students[] = [
            'id' => $row['id'],
            'student_number' => $row['student_number'],
            'name' => $row['first_name'] . ' ' . $row['last_name'],
            'class' => $row['class']
        ];
    }
    
    // Fetch students with pagination (for display only)
    $stmt = $conn->prepare("SELECT id, student_number, first_name, last_name, class FROM students WHERE class = ? ORDER BY first_name, last_name LIMIT ? OFFSET ?");
    $stmt->bind_param("sii", $selected_class, $rowsPerPage, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $students[] = [
            'id' => $row['id'],
            'student_number' => $row['student_number'],
            'name' => $row['first_name'] . ' ' . $row['last_name'],
            'class' => $row['class']
        ];
    }
}

// Handle attendance submission - SAVES ALL STUDENTS IN THE CLASS
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['attendance_data'])) {
    $attendance_data = json_decode($_POST['attendance_data'], true);
    $class_name = $_POST['class'] ?? '';
    $save_type = $_POST['save_type'] ?? 'page'; // 'page' or 'all'
    $success = true;
    $message = '';
    
    try {
        $conn->begin_transaction();
        
        // Get current date for attendance record
        $current_date = date('Y-m-d');
        
        if ($save_type === 'all' && !empty($all_class_students)) {
            // Save attendance for ALL students in the class
            foreach ($all_class_students as $student) {
                $student_id = intval($student['id']);
                $status = 1; // Default to Present for all students
                
                // Check if this student is in the submitted data (marked absent)
                $submitted_student = array_filter($attendance_data, function($s) use ($student_id) {
                    return $s['id'] == $student_id;
                });
                
                if (!empty($submitted_student)) {
                    $submitted_student = reset($submitted_student);
                    $status = ($submitted_student['status'] === 'Present') ? 1 : 0;
                }
                
                // Use INSERT ... ON DUPLICATE KEY UPDATE
                $sql = "INSERT INTO attendance (student_id, student_name, class_name, status, teacher_id, attendance_date) 
                        VALUES (?, ?, ?, ?, ?, ?)
                        ON DUPLICATE KEY UPDATE 
                        status = VALUES(status), 
                        teacher_id = VALUES(teacher_id),
                        student_name = VALUES(student_name),
                        class_name = VALUES(class_name)";
                
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("issiis", $student_id, $student['name'], $student['class'], $status, $teacher_id, $current_date);
                
                if (!$stmt->execute()) {
                    throw new Exception("Failed to save attendance for student ID: " . $student_id . " - " . $stmt->error);
                }
            }
            
            $message = "Attendance saved successfully for ALL " . count($all_class_students) . " students in " . htmlspecialchars($class_name) . "!";
            
        } else {
            // Save only students on current page (original behavior)
            foreach ($attendance_data as $student) {
                $student_id = intval($student['id']);
                $status = ($student['status'] === 'Present') ? 1 : 0;
                $student_name = $student['name'];
                $student_class = $student['class'];
                
                // Use INSERT ... ON DUPLICATE KEY UPDATE
                $sql = "INSERT INTO attendance (student_id, student_name, class_name, status, teacher_id, attendance_date) 
                        VALUES (?, ?, ?, ?, ?, ?)
                        ON DUPLICATE KEY UPDATE 
                        status = VALUES(status), 
                        teacher_id = VALUES(teacher_id),
                        student_name = VALUES(student_name),
                        class_name = VALUES(class_name)";
                
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("issiis", $student_id, $student_name, $student_class, $status, $teacher_id, $current_date);
                
                if (!$stmt->execute()) {
                    throw new Exception("Failed to save attendance for student ID: " . $student_id . " - " . $stmt->error);
                }
            }
            
            $message = "Attendance saved successfully for " . count($attendance_data) . " students on this page!";
        }
        
        $conn->commit();
        
    } catch (Exception $e) {
        $conn->rollback();
        $success = false;
        $message = "Failed to save attendance. Please try again.";
        error_log("Attendance save error: " . $e->getMessage());
    }
    
    // Return JSON response instead of redirecting
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $success,
        'message' => $message
    ]);
    exit;
}

// Calculate total pages for pagination
$totalPages = $totalStudents > 0 ? ceil($totalStudents / $rowsPerPage) : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width,initial-scale=1" />
<title>Teacher Attendance - MEJECRES SCHOOL</title>
<link rel="icon" href="logo.jpg" type="image/x-icon">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
:root {
  --primary-1: #023eaa;
  --primary-2: #0b4bd8;
  --primary-light: #e6eeff;
  --accent: #957037ff;
  --accent-light: #7a5112ff;
  --bg: #f8fafc;
  --card-bg: #ffffff;
  --text: #333333;
  --text-light: #666666;
  --border: #e1e5eb;
  --success: #10b981;
  --success-light: #d1fae5;
  --warning: #5b431bff;
  --warning-light: #fef3c7;
  --danger: #ef4444;
  --danger-light: #fee2e2;
  --info: #3b82f6;
  --max-width: 1200px;
  --border-radius: 12px;
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
  background: var(--bg);
  color: var(--text);
  line-height: 1.6;
  min-height: 100vh;
  display: flex;
  flex-direction: column;
}

/* Header - COMPACT DESIGN */
header.site-header {
  background: linear-gradient(135deg, var(--accent), var(--accent-light));
  color: white;
  padding: 0;
  box-shadow: var(--shadow);
  position: sticky;
  top: 0;
  z-index: 1000;
}

.header-inner {
  display: flex;
  align-items: center;
  justify-content: space-between;
  width: 100%;
  max-width: var(--max-width);
  margin: 0 auto;
  padding: 6px 20px;
  position: relative;
}

.brand {
  display: flex;
  align-items: center;
  gap: 8px;
}

header img.logo {
  height: 50px;
  width: 50px;
  object-fit: cover;
  border-radius: 8px;
  flex-shrink: 0;
  border: 2px solid rgba(255, 255, 255, 0.2);
}

header h1 {
  font-size: 18px;
  margin: 0;
  font-weight: 700;
  white-space: nowrap;
}

nav {
  display: flex;
  gap: 4px;
  align-items: center;
  position: relative;
}

nav a {
  color: white;
  text-decoration: none;
  font-weight: 500;
  font-size: 13px;
  padding: 6px 10px;
  border-radius: 5px;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  gap: 4px;
  white-space: nowrap;
}

nav a:hover, nav a.active {
  background: rgba(255, 255, 255, 0.15);
  transform: translateY(-1px);
}

.hamburger {
  display: none;
  font-size: 18px;
  cursor: pointer;
  background: none;
  border: none;
  color: white;
  padding: 5px;
  border-radius: 5px;
  z-index: 1000;
}

.close-btn {
  display: none;
  font-size: 18px;
  cursor: pointer;
  background: none;
  border: none;
  color: white;
  padding: 5px;
  border-radius: 5px;
  position: absolute;
  top: 6px;
  right: 15px;
}

@media (max-width: 768px) {
  nav {
    display: none;
    flex-direction: column;
    gap: 5px;
    background: var(--accent);
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    padding: 45px 15px 15px;
    border-radius: 0 0 var(--border-radius) var(--border-radius);
    box-shadow: var(--shadow-lg);
  }

  nav.show {
    display: flex;
    animation: fadeIn 0.3s ease;
  }

  .hamburger {
    display: block;
  }

  .close-btn {
    display: block;
  }

  .header-inner {
    padding: 5px 15px;
  }

  header img.logo {
    height: 45px;
    width: 45px;
  }

  header h1 {
    font-size: 16px;
  }
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(-10px); }
  to { opacity: 1; transform: translateY(0); }
}

/* Main Content */
.container {
  width: 95%;
  max-width: var(--max-width);
  margin: 25px auto;
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.welcome-section {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 10px;
}

.welcome-section h3 {
  color: var(--accent);
  font-weight: 600;
  font-size: 17px;
  display: flex;
  align-items: center;
  gap: 8px;
}

.btn-back {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  margin-bottom: 12px;
  padding: 8px 15px;
  background: var(--accent);
  color: white;
  border-radius: 6px;
  text-decoration: none;
  font-weight: 500;
  transition: all 0.3s ease;
  box-shadow: var(--shadow);
}

.btn-back:hover {
  background: var(--accent-light);
  transform: translateY(-2px);
  box-shadow: var(--shadow-lg);
}

/* Dashboard Cards */
.dashboard-cards {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
  gap: 18px;
  margin-bottom: 20px;
}

.dashboard-card {
  background: white;
  padding: 22px;
  border-radius: var(--border-radius);
  box-shadow: var(--shadow);
  display: flex;
  align-items: center;
  gap: 18px;
  transition: transform 0.3s ease, box-shadow 0.3s ease;
  border-left: 4px solid var(--accent);
}

.dashboard-card:hover {
  transform: translateY(-4px);
  box-shadow: var(--shadow-lg);
}

.dashboard-card i {
  font-size: 36px;
  width: 65px;
  height: 65px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
  background: var(--accent-light);
  color: white;
}

.card-content h2 {
  font-size: 28px;
  margin: 0;
  color: var(--accent);
}

.card-content p {
  margin: 0;
  color: var(--text-light);
  font-weight: 500;
  font-size: 14px;
}

/* Main Panel */
.main-panel {
  background: var(--card-bg);
  padding: 25px;
  border-radius: var(--border-radius);
  box-shadow: var(--shadow);
  transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.main-panel:hover {
  box-shadow: var(--shadow-lg);
}

.panel-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 22px;
  padding-bottom: 18px;
  border-bottom: 1px solid var(--border);
}

.panel-header h2 {
  margin: 0;
  color: var(--accent);
  font-weight: 600;
  font-size: 22px;
  display: flex;
  align-items: center;
  gap: 8px;
}

.teacher-info {
  background: var(--primary-light);
  padding: 14px 18px;
  border-radius: var(--border-radius);
  margin-bottom: 18px;
  display: flex;
  align-items: center;
  gap: 12px;
}

.teacher-info i {
  font-size: 22px;
  color: var(--primary-2);
}

.teacher-details {
  flex: 1;
}

.teacher-details strong {
  color: var(--primary-2);
  font-size: 15px;
}

.teacher-details span {
  color: var(--text-light);
  font-size: 13px;
}

/* Controls */
.controls {
  display: flex;
  gap: 12px;
  align-items: center;
  margin-bottom: 22px;
  padding: 18px;
  background: var(--primary-light);
  border-radius: var(--border-radius);
  flex-wrap: wrap;
}

.select-group {
  display: flex;
  gap: 8px;
  align-items: center;
  flex-wrap: wrap;
  flex: 1;
}

.select-group label {
  font-weight: 600;
  color: var(--primary-2);
  white-space: nowrap;
  font-size: 14px;
}

select {
  padding: 10px 12px;
  border-radius: 6px;
  border: 1px solid var(--border);
  font-size: 14px;
  min-width: 160px;
  background: white;
  flex: 1;
  transition: all 0.3s ease;
}

select:focus {
  outline: none;
  border-color: var(--accent);
  box-shadow: 0 0 0 3px rgba(255, 152, 0, 0.1);
}

/* Status Indicator */
.status-indicator {
  padding: 8px 14px;
  border-radius: 18px;
  font-weight: 600;
  font-size: 13px;
  display: inline-flex;
  align-items: center;
  gap: 6px;
  white-space: nowrap;
}

.status-ready {
  background: var(--success-light);
  color: var(--success);
}

.status-loading {
  background: var(--warning-light);
  color: var(--warning);
}

.status-error {
  background: var(--danger-light);
  color: var(--danger);
}

.status-success {
  background: var(--success-light);
  color: var(--success);
}

/* Stats Grid */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
  gap: 14px;
  margin: 22px 0;
}

.stat-card {
  background: white;
  padding: 18px;
  border-radius: var(--border-radius);
  box-shadow: var(--shadow);
  border-left: 4px solid var(--accent);
  text-align: center;
  transition: transform 0.3s ease;
}

.stat-card:hover {
  transform: translateY(-3px);
}

.stat-number {
  font-size: 26px;
  font-weight: 700;
  color: var(--accent);
  margin-bottom: 4px;
}

.stat-label {
  font-size: 13px;
  color: var(--text-light);
  font-weight: 500;
}

/* Attendance Table */
.attendance-container {
  width: 100%;
  overflow-x: auto;
  border: 1px solid var(--border);
  border-radius: var(--border-radius);
  margin: 18px 0;
  box-shadow: var(--shadow);
}

.attendance-table {
  width: 100%;
  min-width: 650px;
  border-collapse: collapse;
  margin: 0;
}

.attendance-table th {
  background: linear-gradient(135deg, var(--accent), var(--accent-light));
  color: white;
  font-weight: 600;
  padding: 14px 10px;
  text-align: left;
  position: sticky;
  top: 0;
  border-bottom: 2px solid var(--border);
  font-size: 14px;
}

.attendance-table td {
  padding: 12px 10px;
  border-bottom: 1px solid var(--border);
  transition: background 0.3s ease;
  font-size: 14px;
}

.attendance-table tr:hover {
  background: var(--primary-light);
}

.attendance-table input[type="checkbox"] {
  width: 20px;
  height: 20px;
  cursor: pointer;
  accent-color: var(--success);
  transition: all 0.3s ease;
}

.attendance-table input[type="checkbox"]:checked {
  transform: scale(1.1);
}

/* Buttons */
.btn {
  background: var(--accent);
  color: white;
  border: none;
  padding: 10px 16px;
  border-radius: 6px;
  cursor: pointer;
  font-weight: 600;
  transition: all 0.3s ease;
  font-size: 13px;
  white-space: nowrap;
  display: inline-flex;
  align-items: center;
  gap: 6px;
  box-shadow: var(--shadow);
}

.btn:hover {
  background: var(--accent-light);
  transform: translateY(-2px);
  box-shadow: var(--shadow-lg);
}

.btn:disabled {
  background: #ccc;
  cursor: not-allowed;
  transform: none;
  box-shadow: none;
}

.btn-success {
  background: var(--success);
}

.btn-success:hover {
  background: #0da271;
}

.btn-warning {
  background: var(--warning);
  color: white;
}

.btn-warning:hover {
  background: #e68a00;
}

.btn-danger {
  background: var(--danger);
}

.btn-danger:hover {
  background: #dc2626;
}

.btn-info {
  background: var(--info);
}

.btn-info:hover {
  background: #2563eb;
}

/* Action Buttons */
.action-buttons {
  display: flex;
  gap: 10px;
  align-items: center;
  margin-top: 22px;
  flex-wrap: wrap;
}

.rows-per-page {
  display: flex;
  align-items: center;
  gap: 6px;
  margin-left: auto;
  white-space: nowrap;
  font-size: 13px;
  color: var(--text-light);
}

/* Pagination */
.pagination {
  display: flex;
  gap: 6px;
  justify-content: center;
  margin-top: 22px;
  flex-wrap: wrap;
}

.pagination button {
  padding: 8px 14px;
  border-radius: 6px;
  border: 1px solid var(--accent);
  background: white;
  color: var(--accent);
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
  font-size: 13px;
  min-width: 40px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.pagination button:hover {
  background: var(--accent);
  color: white;
  transform: translateY(-2px);
}

.pagination button.active {
  background: var(--accent);
  color: white;
  border-color: var(--accent);
}

.pagination button.disabled {
  opacity: 0.5;
  cursor: not-allowed;
  transform: none;
}

/* Progress Container */
.progress-container {
  margin: 22px 0;
  background: white;
  padding: 18px;
  border-radius: var(--border-radius);
  box-shadow: var(--shadow);
}

.progress {
  height: 12px;
  background: #e9ecef;
  border-radius: 6px;
  overflow: hidden;
  margin-bottom: 8px;
}

.progress-bar {
  height: 100%;
  background: linear-gradient(135deg, var(--accent), var(--accent-light));
  transition: width 0.4s ease;
  border-radius: 6px;
}

.progress-stats {
  display: flex;
  justify-content: space-between;
  margin-top: 6px;
  font-size: 13px;
  color: var(--text-light);
  flex-wrap: wrap;
  gap: 8px;
}

/* Toast Notification */
.toast {
  position: fixed;
  top: 15px;
  right: 15px;
  padding: 14px 18px;
  border-radius: var(--border-radius);
  color: white;
  font-weight: 500;
  z-index: 10000;
  animation: slideIn 0.3s ease;
  box-shadow: var(--shadow-lg);
  max-width: 380px;
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 14px;
}

.toast.success {
  background: var(--success);
}

.toast.error {
  background: var(--danger);
}

@keyframes slideIn {
  from { transform: translateX(100%); opacity: 0; }
  to { transform: translateX(0); opacity: 1; }
}

@keyframes slideOut {
  from { transform: translateX(0); opacity: 1; }
  to { transform: translateX(100%); opacity: 0; }
}

/* Loading Animation */
.loading {
  display: inline-block;
  width: 16px;
  height: 16px;
  border: 2px solid #f3f3f3;
  border-top: 2px solid var(--accent);
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

/* Footer */
footer {
  background: linear-gradient(135deg, var(--accent), var(--accent-light));
  color: white;
  text-align: center;
  padding: 18px;
  margin-top: 35px;
  font-size: 14px;
}

/* Responsive */
@media (max-width: 768px) {
  .container {
    width: 92%;
    margin: 18px auto;
  }
  
  .welcome-section {
    flex-direction: column;
    align-items: flex-start;
    gap: 8px;
  }
  
  .dashboard-cards {
    grid-template-columns: 1fr;
  }
  
  .controls {
    flex-direction: column;
    align-items: stretch;
    gap: 10px;
  }
  
  .select-group {
    flex-direction: column;
    align-items: stretch;
    width: 100%;
  }
  
  select {
    width: 100%;
    min-width: auto;
  }
  
  .action-buttons {
    justify-content: center;
  }
  
  .rows-per-page {
    margin-left: 0;
    width: 100%;
    justify-content: center;
  }
  
  .stats-grid {
    grid-template-columns: repeat(2, 1fr);
    gap: 10px;
  }
  
  .attendance-table th, 
  .attendance-table td {
    padding: 10px 8px;
    font-size: 13px;
  }
  
  .toast {
    top: 10px;
    right: 10px;
    left: 10px;
    max-width: none;
  }

  .main-panel {
    padding: 20px;
  }
}

@media (max-width: 480px) {
  .container {
    padding: 0 8px;
  }
  
  .main-panel {
    padding: 18px 12px;
  }
  
  .stats-grid {
    grid-template-columns: 1fr 1fr;
  }
  
  .attendance-table th, 
  .attendance-table td {
    padding: 8px 6px;
    font-size: 12px;
  }
  
  .btn {
    padding: 8px 12px;
    font-size: 12px;
  }
  
  .pagination button {
    padding: 6px 10px;
    font-size: 12px;
    min-width: 34px;
  }

  .header-inner {
    padding: 4px 10px;
  }

  .brand {
    gap: 6px;
  }

  header img.logo {
    height: 40px;
    width: 40px;
  }

  header h1 {
    font-size: 14px;
  }

  nav a {
    font-size: 12px;
    padding: 5px 8px;
  }
}
</style>
</head>
<body>

<header class="site-header">
  <div class="header-inner">
    <div class="brand">
      <img src="logo.jpg" alt="MEJECRES Logo" class="logo">
      <h1>MEJECRES SCHOOL</h1>
    </div>
    <button class="hamburger" id="hamburger">
      <i class="fas fa-bars"></i>
    </button>
    <nav id="NavMenu">
      <button class="close-btn" id="closeBtn">
        <i class="fas fa-times"></i>
      </button>
      <a href="index.php"><i class="fas fa-home"></i> Home</a>
      <a href="about.php"><i class="fas fa-info-circle"></i> About</a>
<a href="report.php"><i class="fas fa-file-alt"></i>Mid-Term Reports</a>      <a href="teachers.php" class="active"><i class="fas fa-clipboard-check"></i> Attendance</a>
      <a href="vision.php"><i class="fas fa-eye"></i> Vision & Values</a>
      <a href="contact.php"><i class="fas fa-envelope"></i> Contact</a>
      <a href="logout.php" style="color:#ffd700;"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </nav>
  </div>
</header>

<div class="container">
  <div class="welcome-section">
    <h3><i class="fas fa-chalkboard-teacher"></i> Welcome, <?php echo htmlspecialchars($teacher_name); ?></h3>
  </div>

  <div class="dashboard-cards">
    <div class="dashboard-card">
      <i class="fas fa-user-graduate"></i>
      <div class="card-content">
        <h2 id="totalStudents"><?php echo $totalStudents; ?></h2>
        <p>Total Students</p>
      </div>
    </div>
    
    <div class="dashboard-card">
      <i class="fas fa-door-open"></i>
      <div class="card-content">
        <h2><?php echo count($classes); ?></h2>
        <p>Available Classes</p>
      </div>
    </div>
    
    <div class="dashboard-card">
      <i class="fas fa-calendar-check"></i>
      <div class="card-content">
        <h2><?php echo date('d M Y'); ?></h2>
        <p>Today's Date</p>
      </div>
    </div>
  </div>

  <div class="main-panel">
    <div class="panel-header">
      <h2><i class="fas fa-clipboard-check"></i> Student Attendance Management</h2>
    </div>

    <div class="teacher-info">
      <i class="fas fa-user-tie"></i>
      <div class="teacher-details">
        <strong>Teacher:</strong> <?php echo htmlspecialchars($teacher_name); ?> | 
        <strong>ID:</strong> <?php echo $teacher_id; ?>
      </div>
    </div>

    <form method="GET" action="" id="classForm">
        <input type="hidden" name="page" value="1">
        <input type="hidden" name="rows" value="<?php echo $rowsPerPage; ?>" id="rowsHidden">
        
        <div class="controls">
            <div class="select-group">
                <label for="selectClass"><i class="fas fa-door-open"></i> Select Class:</label>
                <select id="selectClass" name="class" onchange="loadStudents()">
                    <option value="">-- Choose Class --</option>
                    <?php foreach ($classes as $class): ?>
                        <option value="<?php echo htmlspecialchars($class); ?>" 
                            <?php echo (isset($_GET['class']) && $_GET['class'] === $class) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($class); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="button" class="btn btn-info" onclick="refreshClasses()" id="refreshBtn">
                  <i class="fas fa-sync-alt"></i> Refresh
                </button>
            </div>
            <div class="status-indicator status-ready" id="statusText">
                <i class="fas fa-check-circle"></i>
                <span><?php echo count($classes); ?> classes available</span>
            </div>
        </div>
    </form>

    <?php if (!empty($students)): ?>
    <div class="stats-grid" id="statsGrid">
        <div class="stat-card">
            <div class="stat-number"><?php echo $totalStudents; ?></div>
            <div class="stat-label">Total Students</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?php echo count($students); ?></div>
            <div class="stat-label">Loaded This Page</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?php echo $currentPage; ?> of <?php echo $totalPages; ?></div>
            <div class="stat-label">Current Page</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?php echo $rowsPerPage; ?></div>
            <div class="stat-label">Per Page</div>
        </div>
    </div>
    <?php endif; ?>

    <div class="attendance-container">
        <table class="attendance-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th><i class="fas fa-id-card"></i> Student Number</th>
                    <th><i class="fas fa-user"></i> Student Name</th>
                    <th><i class="fas fa-door-open"></i> Class</th>
                    <th><i class="fas fa-check-circle"></i> Present</th>
                    <th><i class="fas fa-info-circle"></i> Status</th>
                </tr>
            </thead>
            <tbody id="attendanceBody">
                <?php if (empty($students)): ?>
                    <tr>
                        <td colspan="6" style="text-align:center; padding:35px; color:var(--text-light);">
                            <i class="fas fa-door-open" style="font-size: 42px; margin-bottom: 12px; display: block; opacity: 0.5;"></i>
                            <h3 style="margin-bottom: 8px; font-size: 18px;">Select a Class</h3>
                            <p style="font-size: 14px;">Choose a class from the dropdown above to load students</p>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($students as $index => $student): ?>
                    <tr>
                        <td><?php echo $offset + $index + 1; ?></td>
                        <td><?php echo htmlspecialchars($student['student_number']); ?></td>
                        <td><?php echo htmlspecialchars($student['name']); ?></td>
                        <td><?php echo htmlspecialchars($student['class']); ?></td>
                        <td>
                            <input type="checkbox" name="attendance[<?php echo $student['id']; ?>]" 
                                   checked onchange="updateStudentStatus(this, <?php echo $index; ?>)">
                        </td>
                        <td>
                            <span id="status<?php echo $index; ?>" style="color: var(--success); font-weight: 600; font-size: 13px;">
                                <i class="fas fa-check-circle"></i> Present
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
    <div class="pagination">
        <?php if ($currentPage > 1): ?>
            <button onclick="goToPage(1)"><i class="fas fa-angle-double-left"></i></button>
            <button onclick="goToPage(<?php echo $currentPage - 1; ?>)"><i class="fas fa-angle-left"></i></button>
        <?php endif; ?>

        <?php
        $startPage = max(1, $currentPage - 2);
        $endPage = min($totalPages, $currentPage + 2);
        
        for ($i = $startPage; $i <= $endPage; $i++):
        ?>
            <button onclick="goToPage(<?php echo $i; ?>)" <?php echo $i == $currentPage ? 'class="active"' : ''; ?>>
                <?php echo $i; ?>
            </button>
        <?php endfor; ?>

        <?php if ($currentPage < $totalPages): ?>
            <button onclick="goToPage(<?php echo $currentPage + 1; ?>)"><i class="fas fa-angle-right"></i></button>
            <button onclick="goToPage(<?php echo $totalPages; ?>)"><i class="fas fa-angle-double-right"></i></button>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <div class="progress-container" id="progressContainer" style="<?php echo empty($students) ? 'display:none;' : ''; ?>">
        <div class="progress">
            <div class="progress-bar" id="progressBar" style="width:100%"></div>
        </div>
        <div class="progress-stats">
            <span><i class="fas fa-chart-line"></i> Current Page Attendance</span>
            <strong id="presentCount">100% (<?php echo count($students); ?>/<?php echo count($students); ?>) on this page</strong>
        </div>
        <?php if ($totalPages > 1): ?>
        <div style="margin-top: 8px; font-size: 12px; color: var(--text-light); text-align: center;">
            <i class="fas fa-info-circle"></i> 
            Showing page <?php echo $currentPage; ?> of <?php echo $totalPages; ?> 
            (<?php echo count($students); ?> of <?php echo $totalStudents; ?> total students)
        </div>
        <?php endif; ?>
    </div>

    <div class="action-buttons" id="actionButtons" style="<?php echo empty($students) ? 'display:none;' : ''; ?>">
        <button type="button" class="btn btn-success" onclick="saveAttendance('all')" id="saveAllBtn">
            <i class="fas fa-save"></i> Save Attendance (ALL STUDENTS)
        </button>
        <button type="button" class="btn btn-info" onclick="saveAttendance('page')" id="savePageBtn">
            <i class="fas fa-save"></i> Save Current Page Only
        </button>
        <button type="button" class="btn btn-warning" onclick="markAllPresent()" id="markAllBtn">
            <i class="fas fa-check-double"></i> All Present (This Page)
        </button>
        <button type="button" class="btn btn-danger" onclick="markAllAbsent()" id="markAllAbsentBtn">
            <i class="fas fa-times-circle"></i> All Absent (This Page)
        </button>
        <button type="button" class="btn" onclick="resetForm()" id="resetBtn">
            <i class="fas fa-undo"></i> Reset (This Page)
        </button>
        
        <div class="rows-per-page">
            <span>Show:</span>
            <select id="rowsPerPage" onchange="changeRowsPerPage()">
                <option value="10" <?php echo $rowsPerPage == 10 ? 'selected' : ''; ?>>10</option>
                <option value="20" <?php echo $rowsPerPage == 20 ? 'selected' : ''; ?>>20</option>
                <option value="50" <?php echo $rowsPerPage == 50 ? 'selected' : ''; ?>>50</option>
                <option value="100" <?php echo $rowsPerPage == 100 ? 'selected' : ''; ?>>100</option>
            </select>
        </div>
    </div>
  </div>
</div>

<footer>
    <p>&copy; 2025 MEJECRES SCHOOL | Teacher Attendance System | Designed by MUGISHA</p>
</footer>

<script>
// Global variables
let currentStudents = <?php echo json_encode($students); ?>;
let allClassStudents = <?php echo json_encode($all_class_students); ?>;
let currentClassName = '<?php echo isset($_GET['class']) ? $_GET['class'] : ''; ?>';
let totalStudentsInClass = <?php echo $totalStudents; ?>;

// DOM elements
const hamburger = document.getElementById('hamburger');
const closeBtn = document.getElementById('closeBtn');
const nav = document.getElementById('NavMenu');
const statusText = document.getElementById('statusText');
const progressBar = document.getElementById('progressBar');
const presentCount = document.getElementById('presentCount');
const saveAllBtn = document.getElementById('saveAllBtn');
const savePageBtn = document.getElementById('savePageBtn');
const markAllBtn = document.getElementById('markAllBtn');
const markAllAbsentBtn = document.getElementById('markAllAbsentBtn');
const resetBtn = document.getElementById('resetBtn');
const statsGrid = document.getElementById('statsGrid');
const progressContainer = document.getElementById('progressContainer');
const actionButtons = document.getElementById('actionButtons');

// Navigation
hamburger.addEventListener('click', ()=>{ 
  nav.classList.add('show'); 
  hamburger.style.display='none'; 
});
closeBtn.addEventListener('click', ()=>{ 
  nav.classList.remove('show'); 
  hamburger.style.display='block'; 
});
document.querySelectorAll('#NavMenu a').forEach(link=>{ 
  link.addEventListener('click', ()=>{
    if(window.innerWidth <= 768){ 
      nav.classList.remove('show'); 
      hamburger.style.display='block'; 
    }
  });
});
window.addEventListener('resize', ()=>{ 
  if(window.innerWidth>768){ 
    nav.classList.remove('show'); 
    hamburger.style.display='none'; 
  } else { 
    hamburger.style.display='block'; 
  } 
});

// Utility functions
function updateStatus(message, type = 'ready') {
    const icon = type === 'ready' ? 'fa-check-circle' : 
                 type === 'loading' ? 'fa-spinner fa-spin' : 
                 type === 'error' ? 'fa-exclamation-circle' : 
                 'fa-check-circle';
    
    statusText.innerHTML = `<i class="fas ${icon}"></i><span>${message}</span>`;
    statusText.className = `status-indicator status-${type}`;
}

function loadStudents() {
    document.getElementById('classForm').submit();
}

function refreshClasses() {
    const refreshBtn = document.getElementById('refreshBtn');
    refreshBtn.innerHTML = '<div class="loading"></div> Refreshing...';
    refreshBtn.disabled = true;
    
    setTimeout(() => {
        location.reload();
    }, 1000);
}

function updateStudentStatus(checkbox, index) {
    const statusElement = document.getElementById('status' + index);
    if (checkbox.checked) {
        statusElement.innerHTML = '<i class="fas fa-check-circle"></i> Present';
        statusElement.style.color = 'var(--success)';
    } else {
        statusElement.innerHTML = '<i class="fas fa-times-circle"></i> Absent';
        statusElement.style.color = 'var(--danger)';
    }
    updateProgress();
}

function updateProgress() {
    const checkboxes = document.querySelectorAll('input[type="checkbox"]');
    const checkedCount = Array.from(checkboxes).filter(cb => cb.checked).length;
    const totalCount = checkboxes.length;
    
    // Calculate percentage based on current page only for the progress bar
    const currentPagePercent = totalCount ? Math.round((checkedCount / totalCount) * 100) : 0;
    
    // But show the actual numbers in the display
    progressBar.style.width = currentPagePercent + '%';
    presentCount.innerHTML = `<i class="fas fa-chart-pie"></i> ${currentPagePercent}% (${checkedCount}/${totalCount}) on this page`;
}

function markAllPresent() {
    if (confirm('Mark all students on this page as Present?')) {
        const checkboxes = document.querySelectorAll('input[type="checkbox"]');
        checkboxes.forEach((checkbox, index) => {
            checkbox.checked = true;
            updateStudentStatus(checkbox, index);
        });
    }
}

function markAllAbsent() {
    if (confirm('Mark all students on this page as Absent?')) {
        const checkboxes = document.querySelectorAll('input[type="checkbox"]');
        checkboxes.forEach((checkbox, index) => {
            checkbox.checked = false;
            updateStudentStatus(checkbox, index);
        });
    }
}

function resetForm() {
    if (confirm('Reset all changes on this page?')) {
        const checkboxes = document.querySelectorAll('input[type="checkbox"]');
        checkboxes.forEach(checkbox => {
            checkbox.checked = true;
        });
        updateProgress();
    }
}

function goToPage(page) {
    document.querySelector('input[name="page"]').value = page;
    document.getElementById('classForm').submit();
}

function changeRowsPerPage() {
    const rowsSelect = document.getElementById('rowsPerPage');
    document.getElementById('rowsHidden').value = rowsSelect.value;
    document.querySelector('input[name="page"]').value = 1;
    document.getElementById('classForm').submit();
}

// Toast notification function
function showToast(message, type = 'success') {
    // Remove existing toast
    const existingToast = document.querySelector('.toast');
    if (existingToast) {
        existingToast.remove();
    }
    
    // Create new toast
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    const icon = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle';
    toast.innerHTML = `<i class="fas ${icon}"></i> ${message}`;
    
    document.body.appendChild(toast);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        toast.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => {
            if (toast.parentNode) {
                toast.parentNode.removeChild(toast);
            }
        }, 300);
    }, 5000);
}

function saveAttendance(saveType = 'page') {
    if (!currentStudents.length) {
        showToast('No students to save. Please load a class first.', 'error');
        return;
    }
    
    const checkboxes = document.querySelectorAll('input[type="checkbox"]');
    const presentCount = Array.from(checkboxes).filter(cb => cb.checked).length;
    
    if (presentCount === 0 && !confirm('No students are marked as present on this page. Save anyway?')) {
        return;
    }

    // Prepare attendance data for current page students
    const attendanceData = [];
    const checkboxesArray = Array.from(document.querySelectorAll('input[type="checkbox"]'));
    
    checkboxesArray.forEach((checkbox, index) => {
        if (index < currentStudents.length && currentStudents[index]) {
            const student = currentStudents[index];
            if (student && student.id && student.name && student.class) {
                attendanceData.push({
                    id: parseInt(student.id),
                    name: student.name,
                    class: student.class,
                    status: checkbox.checked ? 'Present' : 'Absent'
                });
            }
        }
    });

    // Validate we have data to save
    if (attendanceData.length === 0) {
        showToast('No valid student data to save.', 'error');
        return;
    }

    const saveButton = saveType === 'all' ? saveAllBtn : savePageBtn;
    const savingText = saveType === 'all' ? 'Saving ALL Students...' : 'Saving Current Page...';
    const savedText = saveType === 'all' ? 'Save Attendance (ALL STUDENTS)' : 'Save Current Page Only';

    updateStatus(savingText, 'loading');
    saveButton.disabled = true;
    saveButton.innerHTML = '<div class="loading"></div> Saving...';

    // Use fetch API to submit attendance without page reload
    fetch('', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'attendance_data=' + encodeURIComponent(JSON.stringify(attendanceData)) + 
              '&class=' + encodeURIComponent(currentClassName) +
              '&save_type=' + encodeURIComponent(saveType)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast(data.message, 'success');
            updateStatus('Attendance saved successfully!', 'success');
        } else {
            showToast(data.message, 'error');
            updateStatus('Save failed', 'error');
        }
    })
    .catch(error => {
        console.error('Save error:', error);
        showToast('Failed to save attendance. Please try again.', 'error');
        updateStatus('Save failed', 'error');
    })
    .finally(() => {
        saveButton.disabled = false;
        saveButton.innerHTML = savedText;
    });
}

// Initialize progress if students are loaded
document.addEventListener('DOMContentLoaded', function() {
    if (currentStudents.length > 0) {
        updateProgress();
        statsGrid.style.display = 'grid';
        progressContainer.style.display = 'block';
        actionButtons.style.display = 'flex';
        
        // Update button text to show total students count
        if (allClassStudents.length > 0) {
            saveAllBtn.innerHTML = `<i class="fas fa-save"></i> Save Attendance (ALL ${allClassStudents.length} STUDENTS)`;
        }
    }
});
</script>
</body>
</html>