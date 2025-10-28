<?php 
// Session start with proper check
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['admin_username'])) {
    header('Location: admin-login.php');
    exit;
}

// Integrated Database Connection
$host = 'localhost';
$dbname = 'mejecres_db';
$username = 'root';
$password = '';

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Integrated Student Model
class Student {
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    public function create($data) {
        try {
            $sql = "INSERT INTO students (student_number, first_name, last_name, class, gender, birth_year, father_phone, mother_phone, province, district, sector, cell, village) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $this->conn->prepare($sql);
            return $stmt->execute([
                $data['student_number'],
                $data['first_name'],
                $data['last_name'],
                $data['class'],
                $data['gender'],
                $data['birth_year'],
                $data['father_phone'],
                $data['mother_phone'],
                $data['province'],
                $data['district'],
                $data['sector'],
                $data['cell'],
                $data['village']
            ]);
        } catch(PDOException $e) {
            return false;
        }
    }
    
    public function getTotalCount() {
        try {
            $stmt = $this->conn->prepare("SELECT COUNT(*) as total FROM students");
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['total'] ?? 0;
        } catch(PDOException $e) {
            return 0;
        }
    }
    
    public function getPaginated($page, $per_page) {
        $offset = ($page - 1) * $per_page;
        try {
            $stmt = $this->conn->prepare("SELECT * FROM students ORDER BY student_number ASC LIMIT :limit OFFSET :offset");
            $stmt->bindValue(':limit', $per_page, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            return $this->getSampleData();
        }
    }
    
    public function getClassesGrouped() {
        // Sample class structure - you can modify this based on your actual classes
        return [
            'Nursery' => ['N1 N1 A', 'N1 N1 B', 'N2', 'N3'],
            'Primary' => ['P1 A', 'P1 B','P1 C', 'P2 A', 'P2 B','P2 c', 'P3 A', 'P3 B','P3 C'
          , 'P4 A', 'P4 B','P4 C', 'P5 A', 'P5 B','P5 C','P6 A', 'P6 B','P6 C']        ];
    }
    
    private function getSampleData() {
        return [
            [
                'id' => 1,
                'student_number' => 1,
                'first_name' => 'AGANZE',
                'last_name' => 'SHAMI',
                'gender' => 'M',
                'birth_year' => '2022-01-31',
                'class' => 'N1 N1A',
                'father_phone' => '783316228',
                'mother_phone' => '788298889',
                'province' => 'Kigali',
                'district' => 'Kicukiro',
                'sector' => 'Gatenga',
                'cell' => 'Nyarurama',
                'village' => 'Kabeza',
                'created_at' => '2025-10-24'
            ],
            [
                'id' => 2,
                'student_number' => 2,
                'first_name' => 'KEZA',
                'last_name' => 'MUKAMANA',
                'gender' => 'F',
                'birth_year' => '2021-05-15',
                'class' => 'N2 N2B',
                'father_phone' => '788765432',
                'mother_phone' => '783219876',
                'province' => 'Kigali',
                'district' => 'Gasabo',
                'sector' => 'Remera',
                'cell' => 'Gishushu',
                'village' => 'Nyabugogo',
                'created_at' => '2025-10-24'
            ]
        ];
    }
}

// Initialize Student Model
$studentModel = new Student($conn);
$message = '';

// Handle student actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_student'])) {
        // Add new student
        $data = [
            'student_number' => $_POST['student_no'] ?? '',
            'first_name' => $_POST['first_name'] ?? '',
            'last_name' => $_POST['last_name'] ?? '',
            'class' => $_POST['class'] ?? '',
            'gender' => $_POST['gender'] ?? '',
            'birth_year' => $_POST['birth_year'] ?? '',
            'father_phone' => $_POST['father_tel'] ?? '',
            'mother_phone' => $_POST['mother_tel'] ?? '',
            'province' => $_POST['province'] ?? '',
            'district' => $_POST['district'] ?? '',
            'sector' => $_POST['sector'] ?? '',
            'cell' => $_POST['cell'] ?? '',
            'village' => $_POST['village'] ?? ''
        ];
        
        if ($studentModel->create($data)) {
            $message = '<div class="success">Student added successfully!</div>';
        } else {
            $message = '<div class="error">Failed to add student.</div>';
        }
    }
}

// Pagination setup
$records_per_page = 10;
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($current_page < 1) $current_page = 1;

// Get total number of students
$total_students = $studentModel->getTotalCount();
$total_pages = ceil($total_students / $records_per_page);

// Get students for current page
$students = $studentModel->getPaginated($current_page, $records_per_page);
$classes = $studentModel->getClassesGrouped();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Student Management - MEJECRES SCHOOL</title>
<link rel="icon" href="logo.jpg" type="image/x-icon">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
:root {
  --primary-1: #023eaa;
  --primary-2: #0b4bd8;
  --primary-light: #e6eeff;
  --accent: #ffd700;
  --accent-light: #fff9e6;
  --bg: #f8fafc;
  --card-bg: #ffffff;
  --text: #333333;
  --text-light: #666666;
  --border: #e1e5eb;
  --success: #10b981;
  --success-light: #d1fae5;
  --error: #ef4444;
  --error-light: #fee2e2;
  --warning: #f59e0b;
  --max-width: 1200px;
  --border-radius: 12px;
  --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
  --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
}

* {
  box-sizing: border-box;
  margin: 0;
  padding: 0;
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

/* Header */
header.site-header {
  background: linear-gradient(135deg, var(--primary-1), var(--primary-2));
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
  padding: 12px 20px;
  position: relative;
}

.brand {
  display: flex;
  align-items: center;
  gap: 15px;
}

header img.logo {
  height: 70px;
  width: 70px;
  border-radius: 12px;
  object-fit: cover;
  flex-shrink: 0;
  border: 2px solid rgba(255, 255, 255, 0.2);
}

header h1 {
  font-size: 22px;
  margin: 0;
  font-weight: 700;
  white-space: nowrap;
}

nav {
  display: flex;
  gap: 10px;
  align-items: center;
  position: relative;
}

nav a {
  color: white;
  text-decoration: none;
  font-weight: 500;
  font-size: 15px;
  padding: 8px 16px;
  border-radius: 8px;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  gap: 6px;
}

nav a:hover, nav a.active {
  background: rgba(255, 255, 255, 0.15);
  transform: translateY(-2px);
}

.hamburger {
  display: none;
  font-size: 24px;
  cursor: pointer;
  background: none;
  border: none;
  color: white;
  padding: 8px;
  border-radius: 8px;
  z-index: 1000;
}

.close-btn {
  display: none;
  font-size: 24px;
  cursor: pointer;
  background: none;
  border: none;
  color: white;
  padding: 8px;
  border-radius: 8px;
  position: absolute;
  top: 12px;
  right: 20px;
}

@media (max-width: 768px) {
  nav {
    display: none;
    flex-direction: column;
    gap: 8px;
    background: var(--primary-2);
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    padding: 60px 20px 20px;
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
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(-10px); }
  to { opacity: 1; transform: translateY(0); }
}

/* Main Content */
.container {
  width: 95%;
  max-width: var(--max-width);
  margin: 30px auto;
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 25px;
}

.welcome-section {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 10px;
}

.welcome-section h3 {
  color: var(--primary-1);
  font-weight: 600;
  font-size: 18px;
}

.stats-card {
  background: linear-gradient(135deg, var(--primary-1), var(--primary-2));
  color: white;
  padding: 20px;
  border-radius: var(--border-radius);
  box-shadow: var(--shadow);
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.stats-card i {
  font-size: 40px;
  opacity: 0.8;
}

.stats-content h2 {
  font-size: 32px;
  margin: 0;
}

.stats-content p {
  margin: 0;
  opacity: 0.9;
}

.section {
  background: var(--card-bg);
  padding: 25px;
  border-radius: var(--border-radius);
  box-shadow: var(--shadow);
  transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.section:hover {
  box-shadow: var(--shadow-lg);
}

.section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
  padding-bottom: 15px;
  border-bottom: 1px solid var(--border);
}

.section-header h2 {
  margin: 0;
  color: var(--primary-1);
  font-weight: 600;
  font-size: 22px;
  display: flex;
  align-items: center;
  gap: 10px;
}

.section-header h2 i {
  color: var(--primary-2);
}

.btn-back {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 15px;
  padding: 10px 18px;
  background: var(--primary-2);
  color: white;
  border-radius: 8px;
  text-decoration: none;
  font-weight: 500;
  transition: all 0.3s ease;
  box-shadow: var(--shadow);
}

.btn-back:hover {
  background: var(--primary-1);
  transform: translateY(-2px);
  box-shadow: var(--shadow-lg);
}

/* Form Styles */
.form-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: 20px;
  margin-bottom: 25px;
}

.form-group {
  display: flex;
  flex-direction: column;
}

.form-group label {
  font-weight: 500;
  margin-bottom: 8px;
  color: var(--primary-1);
  display: flex;
  align-items: center;
  gap: 5px;
}

.form-group input, .form-group select {
  padding: 12px 15px;
  border: 1px solid var(--border);
  border-radius: 8px;
  font-size: 15px;
  transition: all 0.3s ease;
  background: white;
}

.form-group input:focus, .form-group select:focus {
  outline: none;
  border-color: var(--primary-2);
  box-shadow: 0 0 0 3px rgba(11, 75, 216, 0.1);
}

.form-group.full-width {
  grid-column: 1 / -1;
}

.btn {
  background: var(--primary-2);
  color: white;
  border: none;
  padding: 12px 25px;
  border-radius: 8px;
  cursor: pointer;
  font-weight: 600;
  transition: all 0.3s ease;
  display: inline-flex;
  align-items: center;
  gap: 8px;
  box-shadow: var(--shadow);
}

.btn:hover {
  background: var(--primary-1);
  transform: translateY(-2px);
  box-shadow: var(--shadow-lg);
}

.btn i {
  font-size: 16px;
}

/* Table Styles */
.table-container {
  overflow-x: auto;
  border-radius: var(--border-radius);
  box-shadow: 0 0 0 1px var(--border);
}

table {
  width: 100%;
  border-collapse: collapse;
  margin-top: 5px;
}

table th {
  background: var(--primary-light);
  color: var(--primary-1);
  font-weight: 600;
  padding: 15px;
  text-align: left;
  border-bottom: 1px solid var(--border);
}

table td {
  padding: 15px;
  border-bottom: 1px solid var(--border);
  font-size: 14px;
}

table tr:last-child td {
  border-bottom: none;
}

table tr:hover {
  background: var(--primary-light);
}

.gender-badge {
  display: inline-block;
  padding: 4px 10px;
  border-radius: 20px;
  font-size: 12px;
  font-weight: 500;
}

.gender-badge.male {
  background: #dbeafe;
  color: #1e40af;
}

.gender-badge.female {
  background: #fce7f3;
  color: #be185d;
}

/* Search Box */
.search-box {
  margin: 20px 0;
  display: flex;
  gap: 10px;
  position: relative;
}

.search-box input {
  flex: 1;
  padding: 12px 15px 12px 40px;
  border: 1px solid var(--border);
  border-radius: 8px;
  font-size: 15px;
  transition: all 0.3s ease;
}

.search-box input:focus {
  outline: none;
  border-color: var(--primary-2);
  box-shadow: 0 0 0 3px rgba(11, 75, 216, 0.1);
}

.search-box i {
  position: absolute;
  left: 15px;
  top: 50%;
  transform: translateY(-50%);
  color: var(--text-light);
  z-index: 1;
}

/* Message Styles */
.success {
  background: var(--success-light);
  color: var(--success);
  padding: 12px 15px;
  border-radius: 8px;
  margin: 15px 0;
  border-left: 4px solid var(--success);
  display: flex;
  align-items: center;
  gap: 10px;
}

.error {
  background: var(--error-light);
  color: var(--error);
  padding: 12px 15px;
  border-radius: 8px;
  margin: 15px 0;
  border-left: 4px solid var(--error);
  display: flex;
  align-items: center;
  gap: 10px;
}

/* Pagination Styles */
.pagination {
  display: flex;
  justify-content: center;
  align-items: center;
  margin-top: 25px;
  gap: 8px;
}

.pagination a, .pagination span {
  padding: 8px 14px;
  border: 1px solid var(--border);
  border-radius: 8px;
  text-decoration: none;
  color: var(--primary-2);
  background: white;
  transition: all 0.3s ease;
  font-weight: 500;
  display: flex;
  align-items: center;
  justify-content: center;
  min-width: 40px;
}

.pagination a:hover {
  background: var(--primary-2);
  color: white;
  transform: translateY(-2px);
}

.pagination .current {
  background: var(--primary-2);
  color: white;
  border-color: var(--primary-2);
}

.pagination .disabled {
  color: #ccc;
  cursor: not-allowed;
  background: #f9f9f9;
}

/* Responsive */
@media (max-width: 768px) {
  .container {
    width: 92%;
    margin: 20px auto;
  }
  
  .form-grid {
    grid-template-columns: 1fr;
  }
  
  .section {
    padding: 20px;
  }
  
  .welcome-section {
    flex-direction: column;
    align-items: flex-start;
    gap: 10px;
  }
  
  .stats-card {
    padding: 15px;
  }
  
  .stats-content h2 {
    font-size: 28px;
  }
  
  .pagination {
    flex-wrap: wrap;
    gap: 5px;
  }
  
  .pagination a, .pagination span {
    padding: 6px 12px;
    font-size: 14px;
  }
  
  table th, table td {
    padding: 10px 8px;
  }
}

/* Empty State */
.empty-state {
  text-align: center;
  padding: 40px 20px;
  color: var(--text-light);
}

.empty-state i {
  font-size: 50px;
  margin-bottom: 15px;
  color: var(--border);
}

.empty-state h3 {
  font-weight: 500;
  margin-bottom: 10px;
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
      <a href="admin.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
      <a href="student.php" class="active"><i class="fas fa-user-graduate"></i> Students</a>
      <a href="all-teachers.php"><i class="fas fa-chalkboard-teacher"></i> Teachers</a>
      <a href="classes-table.php"><i class="fas fa-door-open"></i> Classes</a>
    </nav>
  </div>
</header>

<div class="container">
  <div class="welcome-section">
    <h3><i class="fas fa-user-graduate"></i> Welcome, <?=htmlspecialchars($_SESSION['admin_username'])?></h3>
    <a href="admin.php" class="btn-back">
      <i class="fas fa-arrow-left"></i> Back to Admin
    </a>
  </div>

  <div class="stats-card">
    <div class="stats-content">
      <h2><?= $total_students ?></h2>
      <p>Total Students</p>
    </div>
    <i class="fas fa-user-graduate"></i>
  </div>

  <div class="section">
    <div class="section-header">
      <h2><i class="fas fa-plus-circle"></i> Add New Student</h2>
    </div>
    <?= $message ?>
    <form method="POST">
      <div class="form-grid">
        <div class="form-group">
          <label><i class="fas fa-id-card"></i> Student Number</label>
          <input type="number" name="student_no" required>
        </div>
        <div class="form-group">
          <label><i class="fas fa-user"></i> First Name</label>
          <input type="text" name="first_name" required>
        </div>
        <div class="form-group">
          <label><i class="fas fa-user"></i> Last Name</label>
          <input type="text" name="last_name" required>
        </div>
        <div class="form-group">
          <label><i class="fas fa-door-open"></i> Class</label>
          <select name="class" required>
            <option value="">Select Class</option>
            <?php foreach($classes as $level => $classList): ?>
              <optgroup label="<?= $level ?>">
                <?php foreach($classList as $class): ?>
                  <option value="<?= $class ?>"><?= $class ?></option>
                <?php endforeach; ?>
              </optgroup>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label><i class="fas fa-venus-mars"></i> Gender</label>
          <select name="gender" required>
            <option value="">Select Gender</option>
            <option value="M">Male</option>
            <option value="F">Female</option>
          </select>
        </div>
        <div class="form-group">
          <label><i class="fas fa-birthday-cake"></i> Birth Year</label>
          <input type="date" name="birth_year">
        </div>
        <div class="form-group">
          <label><i class="fas fa-phone"></i> Father's Telephone</label>
          <input type="text" name="father_tel">
        </div>
        <div class="form-group">
          <label><i class="fas fa-phone"></i> Mother's Telephone</label>
          <input type="text" name="mother_tel">
        </div>
        <div class="form-group">
          <label><i class="fas fa-map-marker-alt"></i> Province</label>
          <input type="text" name="province">
        </div>
        <div class="form-group">
          <label><i class="fas fa-map-marker-alt"></i> District</label>
          <input type="text" name="district">
        </div>
        <div class="form-group">
          <label><i class="fas fa-map-marker-alt"></i> Sector</label>
          <input type="text" name="sector">
        </div>
        <div class="form-group">
          <label><i class="fas fa-map-marker-alt"></i> Cell</label>
          <input type="text" name="cell">
        </div>
        <div class="form-group">
          <label><i class="fas fa-map-marker-alt"></i> Village</label>
          <input type="text" name="village">
        </div>
      </div>
      <button type="submit" name="add_student" class="btn">
        <i class="fas fa-user-plus"></i> Add Student
      </button>
    </form>
  </div>

  <div class="section">
    <div class="section-header">
      <h2><i class="fas fa-list"></i> All Students</h2>
    </div>
    
    <div class="search-box">
      <i class="fas fa-search"></i>
      <input type="text" id="searchStudent" placeholder="Search students by name, class, or contact...">
    </div>
    
    <div class="table-container">
      <table id="studentsTable">
        <thead>
          <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Class</th>
            <th>Gender</th>
            <th>Birth Year</th>
            <th>Contact</th>
          </tr>
        </thead>
        <tbody>
          <?php if(empty($students)): ?>
            <tr>
              <td colspan="6">
                <div class="empty-state">
                  <i class="fas fa-user-graduate"></i>
                  <h3>No students found</h3>
                  <p>Add your first student using the form above</p>
                </div>
              </td>
            </tr>
          <?php else: ?>
            <?php foreach($students as $index => $student): ?>
            <tr>
              <td><?= (($current_page - 1) * $records_per_page) + $index + 1 ?></td>
              <td>
                <strong><?= htmlspecialchars($student['first_name'] . ' ' . $student['last_name']) ?></strong>
              </td>
              <td><?= htmlspecialchars($student['class']) ?></td>
              <td>
                <span class="gender-badge <?= $student['gender'] == 'M' ? 'male' : 'female' ?>">
                  <?= $student['gender'] == 'M' ? 'Male' : 'Female' ?>
                </span>
              </td>
              <td>
                <?php 
                  $birth_year = $student['birth_year'];
                  if (!empty($birth_year) && $birth_year !== '0000-00-00') {
                    echo htmlspecialchars(date('Y-m-d', strtotime($birth_year)));
                  } else {
                    echo 'Not Set';
                  }
                ?>
              </td>
              <td><?= htmlspecialchars($student['father_phone'] ?: 'N/A') ?></td>
            </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
    
    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
    <div class="pagination">
      <?php if ($current_page > 1): ?>
        <a href="?page=1"><i class="fas fa-angle-double-left"></i></a>
        <a href="?page=<?= $current_page - 1 ?>"><i class="fas fa-angle-left"></i></a>
      <?php else: ?>
        <span class="disabled"><i class="fas fa-angle-double-left"></i></span>
        <span class="disabled"><i class="fas fa-angle-left"></i></span>
      <?php endif; ?>

      <?php
      // Show page numbers
      $start_page = max(1, $current_page - 2);
      $end_page = min($total_pages, $current_page + 2);
      
      for ($i = $start_page; $i <= $end_page; $i++):
      ?>
        <?php if ($i == $current_page): ?>
          <span class="current"><?= $i ?></span>
        <?php else: ?>
          <a href="?page=<?= $i ?>"><?= $i ?></a>
        <?php endif; ?>
      <?php endfor; ?>

      <?php if ($current_page < $total_pages): ?>
        <a href="?page=<?= $current_page + 1 ?>"><i class="fas fa-angle-right"></i></a>
        <a href="?page=<?= $total_pages ?>"><i class="fas fa-angle-double-right"></i></a>
      <?php else: ?>
        <span class="disabled"><i class="fas fa-angle-right"></i></span>
        <span class="disabled"><i class="fas fa-angle-double-right"></i></span>
      <?php endif; ?>
    </div>
    <?php endif; ?>
  </div>
</div>

<script>
const hamburger = document.getElementById('hamburger');
const closeBtn = document.getElementById('closeBtn');
const nav = document.getElementById('NavMenu');

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

// Search functionality
document.getElementById('searchStudent').addEventListener('input', function() {
  const searchTerm = this.value.toLowerCase();
  const rows = document.querySelectorAll('#studentsTable tbody tr');
  
  rows.forEach(row => {
    const text = row.textContent.toLowerCase();
    if (text.includes(searchTerm)) {
      row.style.display = '';
    } else {
      row.style.display = 'none';
    }
  });
});
</script>
</body>
</html>