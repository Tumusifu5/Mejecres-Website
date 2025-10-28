<?php 
session_start();
if (!isset($_SESSION['admin_username'])) {
  header('Location: admin-login.php');
  exit;
}

// Database connection for XAMPP
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "mejecres_db";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}

// Student model functionality
class Student {
  private $conn;
  
  public function __construct($connection) {
    $this->conn = $connection;
  }
  
  public function getAll() {
    $sql = "SELECT id, student_number, first_name, last_name, gender, birth_year, class FROM students";
    $result = $this->conn->query($sql);
    
    $students = [];
    if ($result && $result->num_rows > 0) {
      while($row = $result->fetch_assoc()) {
        $students[] = $row;
      }
    }
    
    return $students;
  }
}

$studentModel = new Student($conn);
$students = $studentModel->getAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>All Students - MEJECRES SCHOOL</title>
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

/* Stats Cards */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
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
}

.stat-card:hover {
  transform: translateY(-5px);
  box-shadow: var(--card-hover);
}

.stat-card i {
  font-size: 2.5rem;
  margin-bottom: 15px;
  color: var(--primary);
}

.stat-number {
  font-size: 2rem;
  font-weight: 700;
  color: var(--dark);
  margin-bottom: 5px;
}

.stat-label {
  color: var(--dark);
  font-weight: 500;
  font-size: 0.9rem;
}

/* Search and Filter */
.search-container {
  background: white;
  padding: 20px;
  border-radius: 16px;
  box-shadow: var(--card-shadow);
  margin-bottom: 2rem;
}

.search-box {
  display: flex;
  gap: 15px;
  align-items: center;
}

.search-input {
  flex: 1;
  padding: 14px 20px;
  border: 2px solid #e9ecef;
  border-radius: 12px;
  font-size: 15px;
  transition: 0.3s;
}

.search-input:focus {
  border-color: var(--primary);
  outline: none;
  box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.1);
}

.filter-select {
  padding: 14px 20px;
  border: 2px solid #e9ecef;
  border-radius: 12px;
  background: white;
  font-size: 15px;
  min-width: 150px;
}

/* COMPACT TABLE STYLES */
.students-card {
  background: white;
  padding: 20px;
  border-radius: 16px;
  box-shadow: var(--card-shadow);
  margin-bottom: 25px;
}

.students-card h2 {
  margin-bottom: 15px;
  color: var(--primary-dark);
  border-bottom: 2px solid var(--primary);
  padding-bottom: 10px;
  display: flex;
  align-items: center;
  gap: 10px;
  font-size: 1.3rem;
}

.students-card h2 i {
  color: var(--primary);
}

.table-container {
  overflow-x: auto;
  border-radius: 12px;
  border: 1px solid #e9ecef;
}

.students-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.9rem;
}

.students-table th {
  background: linear-gradient(135deg, var(--primary), var(--primary-dark));
  color: white;
  padding: 12px 15px;
  text-align: left;
  font-weight: 600;
  border: none;
  white-space: nowrap;
  font-size: 0.85rem;
}

.students-table td {
  padding: 12px 15px;
  border-bottom: 1px solid #e9ecef;
  vertical-align: middle;
  white-space: nowrap;
}

.students-table tr:hover {
  background: #f8f9fa;
}

.students-table tr:last-child td {
  border-bottom: none;
}

/* Compact badges */
.gender-badge {
  padding: 4px 8px;
  border-radius: 12px;
  font-size: 0.75rem;
  font-weight: 500;
  white-space: nowrap;
  display: inline-flex;
  align-items: center;
  gap: 4px;
}

.gender-male {
  background: #e3f2fd;
  color: #1976d2;
}

.gender-female {
  background: #fce4ec;
  color: #c2185b;
}

.class-badge {
  padding: 4px 8px;
  background: var(--primary);
  color: white;
  border-radius: 12px;
  font-size: 0.75rem;
  font-weight: 500;
  white-space: nowrap;
  display: inline-block;
  max-width: 120px;
  overflow: hidden;
  text-overflow: ellipsis;
}

/* Compact button */
.view-btn {
  padding: 6px 12px;
  background: var(--primary);
  color: white;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  font-weight: 500;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  gap: 4px;
  font-size: 0.8rem;
  white-space: nowrap;
}

.view-btn:hover {
  background: var(--primary-dark);
  transform: translateY(-1px);
}

/* Pagination */
.pagination {
  display: flex;
  justify-content: center;
  gap: 6px;
  margin-top: 20px;
  flex-wrap: wrap;
}

.pagination a {
  padding: 8px 12px;
  background: white;
  color: var(--primary);
  border: 2px solid #e9ecef;
  border-radius: 6px;
  text-decoration: none;
  font-weight: 600;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  gap: 4px;
  font-size: 0.85rem;
}

.pagination a.active {
  background: var(--primary);
  color: white;
  border-color: var(--primary);
}

.pagination a:hover:not(.active) {
  background: #f8f9fa;
  border-color: var(--primary);
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
  
  .filter-select {
    width: 100%;
  }
  
  /* Even more compact on tablets */
  .students-table {
    font-size: 0.85rem;
  }
  
  .students-table th,
  .students-table td {
    padding: 10px 12px;
  }
}

@media (max-width: 768px) {
  .stats-grid {
    grid-template-columns: 1fr;
  }
  
  .students-card {
    padding: 15px;
  }
  
  .students-table {
    font-size: 0.8rem;
  }
  
  .students-table th,
  .students-table td {
    padding: 8px 10px;
  }
  
  .gender-badge,
  .class-badge {
    font-size: 0.7rem;
    padding: 3px 6px;
  }
  
  .view-btn {
    padding: 4px 8px;
    font-size: 0.75rem;
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

/* Scrollbar styling for table container */
.table-container::-webkit-scrollbar {
  height: 8px;
}

.table-container::-webkit-scrollbar-track {
  background: #f1f1f1;
  border-radius: 4px;
}

.table-container::-webkit-scrollbar-thumb {
  background: var(--primary);
  border-radius: 4px;
}

.table-container::-webkit-scrollbar-thumb:hover {
  background: var(--primary-dark);
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
        <a href="all-students.php" class="active"><i class="fas fa-user-graduate"></i> All Students</a>
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
            <h1>Student Management</h1>
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
            <i class="fas fa-user-graduate"></i> All Students Database
        </div>

        <!-- Statistics Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <i class="fas fa-users"></i>
                <div class="stat-number"><?= count($students) ?></div>
                <div class="stat-label">Total Students</div>
            </div>
            <div class="stat-card">
                <i class="fas fa-male"></i>
                <div class="stat-number">
                    <?= count(array_filter($students, function($s) { return $s['gender'] == 'M'; })) ?>
                </div>
                <div class="stat-label">Male Students</div>
            </div>
            <div class="stat-card">
                <i class="fas fa-female"></i>
                <div class="stat-number">
                    <?= count(array_filter($students, function($s) { return $s['gender'] == 'F'; })) ?>
                </div>
                <div class="stat-label">Female Students</div>
            </div>
            <div class="stat-card">
                <i class="fas fa-chalkboard"></i>
                <div class="stat-number">
                    <?= count(array_unique(array_column($students, 'class'))) ?>
                </div>
                <div class="stat-label">Active Classes</div>
            </div>
        </div>

        <!-- Search and Filter -->
        <div class="search-container">
            <div class="search-box">
                <input type="text" id="search" class="search-input" placeholder="Search students by name, number, or class...">
                <select id="classFilter" class="filter-select">
                    <option value="">All Classes</option>
                    <?php 
                    $classes = array_unique(array_column($students, 'class'));
                    foreach($classes as $class): 
                    ?>
                        <option value="<?= htmlspecialchars($class) ?>"><?= htmlspecialchars($class) ?></option>
                    <?php endforeach; ?>
                </select>
                <select id="genderFilter" class="filter-select">
                    <option value="">All Genders</option>
                    <option value="M">Male</option>
                    <option value="F">Female</option>
                </select>
            </div>
        </div>

        <!-- Students Table -->
        <div class="students-card">
            <h2><i class="fas fa-list"></i> Students List</h2>
            <div class="table-container">
                <table class="students-table" id="studentsTable">
                    <thead>
                        <tr>
                            <th>Student Number</th>
                            <th>Full Name</th>
                            <th>Class</th>
                            <th>Gender</th>
                            <th>Birth Year</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($students)): ?>
                        <tr>
                            <td colspan="6" style="text-align:center; padding: 30px; color: #6c757d; font-size: 0.9rem;">
                                <i class="fas fa-user-slash" style="font-size: 2rem; margin-bottom: 10px; display: block;"></i>
                                No students found in the database
                            </td>
                        </tr>
                        <?php else: ?>
                        <?php foreach($students as $student): ?>
                        <tr>
                            <td>
                                <div style="font-weight: 600; color: var(--primary); font-size: 0.85rem;">
                                    <?= htmlspecialchars($student['student_number']) ?>
                                </div>
                            </td>
                            <td>
                                <div style="font-weight: 500; font-size: 0.85rem;"><?= htmlspecialchars($student['first_name'] . ' ' . $student['last_name']) ?></div>
                            </td>
                            <td>
                                <span class="class-badge" title="<?= htmlspecialchars($student['class']) ?>"><?= htmlspecialchars($student['class']) ?></span>
                            </td>
                            <td>
                                <span class="gender-badge <?= $student['gender'] == 'M' ? 'gender-male' : 'gender-female' ?>">
                                    <i class="fas fa-<?= $student['gender'] == 'M' ? 'male' : 'female' ?>"></i>
                                    <?= $student['gender'] == 'M' ? 'Male' : 'Female' ?>
                                </span>
                            </td>
                            <td>
                                <span style="color: #6c757d; font-size: 0.85rem;"><?= htmlspecialchars($student['birth_year']) ?></span>
                            </td>
                            <td>
                                <button class="view-btn" onclick="viewStudent(<?= $student['id'] ?>)">
                                    <i class="fas fa-eye"></i> View
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="pagination" id="pagination"></div>
        </div>
    </div>

    <script>
        // ---------------- Student Data from PHP ----------------
        let studentsData = <?php echo json_encode($students); ?>;
        let currentPage = 1;
        const perPage = 15;

        // Toggle sidebar on mobile
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

        function renderTable() {
            const tbody = document.querySelector("#studentsTable tbody");
            const search = document.getElementById("search").value.toLowerCase();
            const classFilter = document.getElementById("classFilter").value;
            const genderFilter = document.getElementById("genderFilter").value;
            
            const filtered = studentsData.filter(s => {
                const matchesSearch = (s.first_name + ' ' + s.last_name).toLowerCase().includes(search) ||
                                    s.student_number.toString().includes(search) ||
                                    s.class.toLowerCase().includes(search);
                const matchesClass = !classFilter || s.class === classFilter;
                const matchesGender = !genderFilter || s.gender === genderFilter;
                
                return matchesSearch && matchesClass && matchesGender;
            });
            
            const start = (currentPage-1)*perPage;
            const paginated = filtered.slice(start,start+perPage);
            
            if(paginated.length === 0){
                tbody.innerHTML = `
                    <tr>
                        <td colspan="6" style="text-align:center; padding: 30px; color: #6c757d; font-size: 0.9rem;">
                            <i class="fas fa-search" style="font-size: 2rem; margin-bottom: 10px; display: block;"></i>
                            No students match your search criteria
                        </td>
                    </tr>
                `;
                return;
            }
            
            tbody.innerHTML = paginated.map(s=>`
                <tr>
                    <td><div style="font-weight: 600; color: var(--primary); font-size: 0.85rem;">${s.student_number}</div></td>
                    <td><div style="font-weight: 500; font-size: 0.85rem;">${s.first_name} ${s.last_name}</div></td>
                    <td><span class="class-badge" title="${s.class}">${s.class}</span></td>
                    <td>
                        <span class="gender-badge ${s.gender == 'M' ? 'gender-male' : 'gender-female'}">
                            <i class="fas fa-${s.gender == 'M' ? 'male' : 'female'}"></i>
                            ${s.gender == 'M' ? 'Male' : 'Female'}
                        </span>
                    </td>
                    <td><span style="color: #6c757d; font-size: 0.85rem;">${s.birth_year}</span></td>
                    <td>
                        <button class="view-btn" onclick="viewStudent(${s.id})">
                            <i class="fas fa-eye"></i> View
                        </button>
                    </td>
                </tr>
            `).join('');
        }

        function renderPagination() {
            const search = document.getElementById("search").value.toLowerCase();
            const classFilter = document.getElementById("classFilter").value;
            const genderFilter = document.getElementById("genderFilter").value;
            
            const filtered = studentsData.filter(s => {
                const matchesSearch = (s.first_name + ' ' + s.last_name).toLowerCase().includes(search) ||
                                    s.student_number.toString().includes(search) ||
                                    s.class.toLowerCase().includes(search);
                const matchesClass = !classFilter || s.class === classFilter;
                const matchesGender = !genderFilter || s.gender === genderFilter;
                
                return matchesSearch && matchesClass && matchesGender;
            });
            
            const totalPages = Math.ceil(filtered.length/perPage);
            const container = document.getElementById("pagination");
            let html = '';

            if(currentPage>1){
                html += `<a href="javascript:void(0)" onclick="goPage(${currentPage-1})">
                            <i class="fas fa-chevron-left"></i> Prev
                         </a>`;
            }

            for(let p=1;p<=totalPages;p++){
                if(p>currentPage+2 || p<currentPage-2) continue;
                html += `<a href="javascript:void(0)" onclick="goPage(${p})" class="${p===currentPage?'active':''}">${p}</a>`;
            }

            if(currentPage<totalPages){
                html += `<a href="javascript:void(0)" onclick="goPage(${currentPage+1})">
                            Next <i class="fas fa-chevron-right"></i>
                         </a>`;
            }

            container.innerHTML = html;
        }

        function goPage(page){ 
            currentPage=page; 
            renderTable(); 
            renderPagination();
            window.scrollTo({ top: document.querySelector('.students-card').offsetTop - 20, behavior: 'smooth' });
        }

        function viewStudent(id) {
            alert('View student details for ID: ' + id);
            // Implement view student functionality
        }

        // Event listeners for search and filters
        document.getElementById("search").addEventListener("input",()=>{
            currentPage=1;
            renderTable();
            renderPagination();
        });

        document.getElementById("classFilter").addEventListener("change",()=>{
            currentPage=1;
            renderTable();
            renderPagination();
        });

        document.getElementById("genderFilter").addEventListener("change",()=>{
            currentPage=1;
            renderTable();
            renderPagination();
        });

        // Initialize
        renderTable();
        renderPagination();
    </script>

</body>
</html>
<?php
// Close the database connection
$conn->close();
?>