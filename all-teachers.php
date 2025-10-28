<?php
session_start();

// Only allow admin to access
if (!isset($_SESSION['admin_username'])) {
    header('Location: admin-login.php');
    exit;
}

// ---------- DATABASE CONNECTION ----------
include 'connection.php';

// ---------- HANDLE DELETE ----------
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    
    // Optional: fetch teacher name for confirmation in session flash
    $stmt = $conn->prepare("SELECT fullname FROM teachers WHERE id = ?");
    $stmt->bind_param("i", $delete_id);
    $stmt->execute();
    $stmt->bind_result($teacher_name);
    $stmt->fetch();
    $stmt->close();

    if ($teacher_name) {
        $stmt = $conn->prepare("DELETE FROM teachers WHERE id = ?");
        $stmt->bind_param("i", $delete_id);
        $stmt->execute();
        $stmt->close();
        $deleted_message = "Teacher '{$teacher_name}' has been deleted successfully.";
    }
}

// ---------- FETCH TEACHERS ----------
$sql = "SELECT id, username, fullname, email, created_at FROM teachers ORDER BY id DESC";
$result = $conn->query($sql);
$teachers = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

// Get stats
$total_teachers = count($teachers);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Teachers Management | MEJECRES SCHOOL</title>
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

/* Teachers Card */
.teachers-card {
  background: white;
  padding: 30px;
  border-radius: 16px;
  box-shadow: var(--card-shadow);
  margin-bottom: 25px;
}

.teachers-card h2 {
  margin-bottom: 20px;
  color: var(--primary-dark);
  border-bottom: 2px solid var(--primary);
  padding-bottom: 10px;
  display: flex;
  align-items: center;
  gap: 10px;
}

.teachers-card h2 i {
  color: var(--primary);
}

/* Search Container */
.search-container {
  background: white;
  padding: 20px;
  border-radius: 16px;
  box-shadow: var(--card-shadow);
  margin-bottom: 2rem;
}

.search-input {
  width: 100%;
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

/* Teachers Grid */
.teachers-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 20px;
  margin-top: 20px;
}

.teacher-card {
  background: white;
  border-radius: 16px;
  padding: 25px;
  box-shadow: var(--card-shadow);
  border-left: 4px solid var(--primary);
  transition: all 0.3s ease;
  position: relative;
  overflow: hidden;
}

.teacher-card:hover {
  transform: translateY(-5px);
  box-shadow: var(--card-hover);
}

.teacher-card::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 4px;
  background: linear-gradient(90deg, var(--primary), var(--secondary));
}

.teacher-avatar {
  width: 70px;
  height: 70px;
  border-radius: 50%;
  background: linear-gradient(135deg, var(--primary), var(--secondary));
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 1.5rem;
  font-weight: 600;
  margin-bottom: 15px;
  border: 3px solid #e9ecef;
}

.teacher-name {
  font-size: 1.2rem;
  font-weight: 600;
  color: var(--dark);
  margin-bottom: 8px;
}

.teacher-username {
  color: var(--primary);
  font-weight: 500;
  margin-bottom: 5px;
  display: flex;
  align-items: center;
  gap: 8px;
}

.teacher-email {
  color: #6c757d;
  margin-bottom: 15px;
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 0.9rem;
}

.teacher-joined {
  color: #6c757d;
  font-size: 0.8rem;
  display: flex;
  align-items: center;
  gap: 6px;
  margin-bottom: 15px;
}

.teacher-actions {
  display: flex;
  gap: 10px;
}

.btn {
  padding: 8px 16px;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  font-weight: 500;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 0.85rem;
  text-decoration: none;
}

.btn-view {
  background: var(--primary);
  color: white;
}

.btn-view:hover {
  background: var(--primary-dark);
  transform: translateY(-2px);
}

.btn-delete {
  background: var(--danger);
  color: white;
}

.btn-delete:hover {
  background: #e0006d;
  transform: translateY(-2px);
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
}

/* Alert Message */
.alert {
  padding: 16px 20px;
  border-radius: 12px;
  margin-bottom: 20px;
  display: flex;
  align-items: center;
  gap: 12px;
  font-weight: 500;
}

.alert-success {
  background: #e8f5e9;
  color: #2e7d32;
  border-left: 4px solid #4caf50;
}

.alert-success i {
  color: #4caf50;
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
  .stats-grid {
    grid-template-columns: 1fr;
  }
  
  .teachers-grid {
    grid-template-columns: 1fr;
  }
  
  .teachers-card {
    padding: 20px;
  }
  
  .teacher-card {
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
        <a href="all-teachers.php" class="active"><i class="fas fa-chalkboard-teacher"></i> Teachers</a>
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
            <h1>Teacher Management</h1>
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
            <i class="fas fa-chalkboard-teacher"></i> Teaching Staff Management
        </div>

        <?php if(isset($deleted_message)): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            <?= htmlspecialchars($deleted_message); ?>
        </div>
        <?php endif; ?>

        <!-- Statistics Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <i class="fas fa-chalkboard-teacher"></i>
                <div class="stat-number"><?= $total_teachers ?></div>
                <div class="stat-label">Total Teachers</div>
            </div>
            <div class="stat-card">
                <i class="fas fa-user-plus"></i>
                <div class="stat-number"><?= $total_teachers ?></div>
                <div class="stat-label">Active Staff</div>
            </div>
            <div class="stat-card">
                <i class="fas fa-calendar-check"></i>
                <div class="stat-number">100%</div>
                <div class="stat-label">Staff Availability</div>
            </div>
            <div class="stat-card">
                <i class="fas fa-award"></i>
                <div class="stat-number">A+</div>
                <div class="stat-label">Performance Rating</div>
            </div>
        </div>

        <!-- Search Container -->
        <div class="search-container">
            <input type="text" id="searchInput" class="search-input" placeholder="Search teachers by name, username, or email...">
        </div>

        <!-- Teachers Grid -->
        <div class="teachers-card">
            <h2><i class="fas fa-users"></i> Teaching Staff</h2>
            
            <?php if(empty($teachers)): ?>
            <div class="empty-state">
                <i class="fas fa-user-slash"></i>
                <h3>No Teachers Found</h3>
                <p>There are no teachers registered in the system yet.</p>
            </div>
            <?php else: ?>
            <div class="teachers-grid" id="teachersGrid">
                <?php foreach($teachers as $teacher): ?>
                <div class="teacher-card" data-search="<?= htmlspecialchars(strtolower($teacher['fullname'] . ' ' . $teacher['username'] . ' ' . $teacher['email'])) ?>">
                    <div class="teacher-avatar">
                        <?= strtoupper(substr($teacher['fullname'], 0, 1)) ?>
                    </div>
                    <div class="teacher-name"><?= htmlspecialchars($teacher['fullname']) ?></div>
                    <div class="teacher-username">
                        <i class="fas fa-user"></i>
                        @<?= htmlspecialchars($teacher['username']) ?>
                    </div>
                    <div class="teacher-email">
                        <i class="fas fa-envelope"></i>
                        <?= htmlspecialchars($teacher['email']) ?>
                    </div>
                    <div class="teacher-joined">
                        <i class="fas fa-calendar-alt"></i>
                        Joined: <?= date('M j, Y', strtotime($teacher['created_at'])) ?>
                    </div>
                    <div class="teacher-actions">
                        <button class="btn btn-view" onclick="viewTeacher(<?= $teacher['id'] ?>)">
                            <i class="fas fa-eye"></i> View Profile
                        </button>
                        <button class="btn btn-delete" onclick="confirmDelete('<?= addslashes($teacher['fullname']); ?>', <?= $teacher['id']; ?>)">
                            <i class="fas fa-trash"></i> Delete
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
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

        // Search functionality
        document.getElementById('searchInput').addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const teacherCards = document.querySelectorAll('.teacher-card');
            
            teacherCards.forEach(card => {
                const searchData = card.getAttribute('data-search');
                if (searchData.includes(searchTerm)) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        });

        function viewTeacher(id) {
            alert('View teacher profile for ID: ' + id);
            // Implement view teacher functionality
        }

        function confirmDelete(fullname, id) {
            if(confirm("Are you sure you want to delete teacher: " + fullname + "?\n\nThis action cannot be undone.")) {
                window.location.href = "all-teachers.php?delete_id=" + id;
            }
        }
    </script>

</body>
</html>
<?php $conn->close(); ?>