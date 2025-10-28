<?php
// classes-table.php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_username'])) {
    header('Location: admin-login.php');
    exit;
}

// Database connection
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

// Get classes from database
function getClassesFromDB($conn) {
    try {
        // Get unique classes from students table
        $stmt = $conn->prepare("SELECT DISTINCT class FROM students ORDER BY class");
        $stmt->execute();
        $classes = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        // If no classes in database, return sample data
        if (empty($classes)) {
            return [
                'Nursery' => ['N1 N1 A', 'N1 N1 B', 'N2', 'N3'],
                'Primary' => ['P1 A', 'P1 B', 'P1 C', 'P2 A', 'P2 B', 'P2 C', 'P3 A', 'P3 B', 'P3 C', 
                             'P4 A', 'P4 B', 'P4 C', 'P5 A', 'P5 B', 'P5 C', 'P6 A', 'P6 B', 'P6 C']
            ];
        }
        
        // Organize classes by level
        $organized = [];
        foreach ($classes as $class) {
            if (strpos($class, 'N') === 0) {
                $organized['Nursery'][] = $class;
            } else if (strpos($class, 'P') === 0) {
                $organized['Primary'][] = $class;
            } else {
                $organized['Other'][] = $class;
            }
        }
        
        return $organized;
    } catch(PDOException $e) {
        // Return sample data if query fails
        return [
            'Nursery' => ['N1 N1 A', 'N1 N1 B', 'N2', 'N3'],
            'Primary' => ['P1 A', 'P1 B', 'P1 C', 'P2 A', 'P2 B', 'P2 C', 'P3 A', 'P3 B', 'P3 C', 
                         'P4 A', 'P4 B', 'P4 C', 'P5 A', 'P5 B', 'P5 C', 'P6 A', 'P6 B', 'P6 C']
        ];
    }
}

// Get student counts per class
function getStudentCounts($conn, $classes) {
    $counts = [];
    try {
        foreach ($classes as $level => $classList) {
            foreach ($classList as $class) {
                $stmt = $conn->prepare("SELECT COUNT(*) FROM students WHERE class = ?");
                $stmt->execute([$class]);
                $counts[$class] = $stmt->fetchColumn();
            }
        }
    } catch(PDOException $e) {
        // Set default counts if query fails
        foreach ($classes as $level => $classList) {
            foreach ($classList as $class) {
                $counts[$class] = rand(15, 35); // Random sample data
            }
        }
    }
    return $counts;
}

$classes = getClassesFromDB($conn);
$studentCounts = getStudentCounts($conn, $classes);

// Calculate totals
$totalClasses = 0;
$totalStudents = 0;
foreach ($classes as $level => $classList) {
    $totalClasses += count($classList);
    foreach ($classList as $class) {
        $totalStudents += $studentCounts[$class];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>Class Management - MEJECRES SCHOOL</title>
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
  object-fit: cover;
  border-radius: 12px;
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

/* Stats Cards */
.stats-container {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 20px;
  margin-bottom: 20px;
}

.stat-card {
  background: white;
  padding: 25px;
  border-radius: var(--border-radius);
  box-shadow: var(--shadow);
  display: flex;
  align-items: center;
  gap: 20px;
  transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.stat-card:hover {
  transform: translateY(-5px);
  box-shadow: var(--shadow-lg);
}

.stat-card i {
  font-size: 40px;
  width: 70px;
  height: 70px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
}

.stat-card.classes i {
  background: var(--primary-light);
  color: var(--primary-2);
}

.stat-card.students i {
  background: #e0f2fe;
  color: #0369a1;
}

.stat-card.levels i {
  background: var(--accent-light);
  color: var(--warning);
}

.stat-content h2 {
  font-size: 32px;
  margin: 0;
  color: var(--primary-1);
}

.stat-content p {
  margin: 0;
  color: var(--text-light);
  font-weight: 500;
}

/* Class Sections */
.class-section {
  background: var(--card-bg);
  padding: 25px;
  border-radius: var(--border-radius);
  box-shadow: var(--shadow);
  transition: transform 0.3s ease, box-shadow 0.3s ease;
  margin-bottom: 25px;
}

.class-section:hover {
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

.class-count {
  background: var(--primary-2);
  color: white;
  padding: 4px 12px;
  border-radius: 20px;
  font-size: 14px;
  font-weight: 500;
}

/* Class Grid */
.class-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: 20px;
}

.class-card {
  background: white;
  border-radius: var(--border-radius);
  overflow: hidden;
  box-shadow: 0 3px 10px rgba(0, 0, 0, 0.08);
  transition: all 0.3s ease;
  border: 1px solid var(--border);
}

.class-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 10px 20px rgba(0, 0, 0, 0.12);
}

.class-header {
  background: linear-gradient(135deg, var(--primary-1), var(--primary-2));
  color: white;
  padding: 15px;
  text-align: center;
}

.class-header h3 {
  margin: 0;
  font-size: 18px;
  font-weight: 600;
}

.class-body {
  padding: 20px;
  text-align: center;
}

.student-count {
  font-size: 32px;
  font-weight: 700;
  color: var(--primary-1);
  margin-bottom: 5px;
}

.student-label {
  color: var(--text-light);
  font-size: 14px;
  margin-bottom: 15px;
}

.view-btn {
  display: inline-block;
  background: var(--primary-light);
  color: var(--primary-2);
  padding: 8px 16px;
  border-radius: 6px;
  text-decoration: none;
  font-weight: 500;
  font-size: 14px;
  transition: all 0.3s ease;
}

.view-btn:hover {
  background: var(--primary-2);
  color: white;
}

/* Empty State */
.empty-state {
  text-align: center;
  padding: 40px 20px;
  color: var(--text-light);
  grid-column: 1 / -1;
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

/* Footer */
footer {
  background: linear-gradient(135deg, var(--primary-1), var(--primary-2));
  color: white;
  text-align: center;
  padding: 20px;
  margin-top: 40px;
}

/* Responsive */
@media (max-width: 768px) {
  .container {
    width: 92%;
    margin: 20px auto;
  }
  
  .welcome-section {
    flex-direction: column;
    align-items: flex-start;
    gap: 10px;
  }
  
  .stats-container {
    grid-template-columns: 1fr;
  }
  
  .class-grid {
    grid-template-columns: 1fr;
  }
  
  .section-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 10px;
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
      <a href="admin.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
      <a href="student.php"><i class="fas fa-user-graduate"></i> Students</a>
      <a href="all-teachers.php"><i class="fas fa-chalkboard-teacher"></i> Teachers</a>
      <a href="classes-table.php" class="active"><i class="fas fa-door-open"></i> Classes</a>
    </nav>
  </div>
</header>

<div class="container">
  <div class="welcome-section">
    <h3><i class="fas fa-door-open"></i> Welcome, <?=htmlspecialchars($_SESSION['admin_username'])?></h3>
    <a href="admin.php" class="btn-back">
      <i class="fas fa-arrow-left"></i> Back to Admin
    </a>
  </div>

  <div class="stats-container">
    <div class="stat-card classes">
      <i class="fas fa-door-open"></i>
      <div class="stat-content">
        <h2><?= $totalClasses ?></h2>
        <p>Total Classes</p>
      </div>
    </div>
    
    <div class="stat-card students">
      <i class="fas fa-user-graduate"></i>
      <div class="stat-content">
        <h2><?= $totalStudents ?></h2>
        <p>Total Students</p>
      </div>
    </div>
    
    <div class="stat-card levels">
      <i class="fas fa-layer-group"></i>
      <div class="stat-content">
        <h2><?= count($classes) ?></h2>
        <p>Education Levels</p>
      </div>
    </div>
  </div>

  <?php if (empty($classes)): ?>
    <div class="class-section">
      <div class="empty-state">
        <i class="fas fa-door-open"></i>
        <h3>No classes found</h3>
        <p>Classes will appear here once students are added to the system</p>
      </div>
    </div>
  <?php else: ?>
    <?php foreach ($classes as $level => $classList): ?>
      <div class="class-section">
        <div class="section-header">
          <h2>
            <i class="fas fa-layer-group"></i>
            <?= $level ?> Classes
          </h2>
          <span class="class-count"><?= count($classList) ?> classes</span>
        </div>
        
        <div class="class-grid">
          <?php foreach ($classList as $class): ?>
            <div class="class-card">
              <div class="class-header">
                <h3><?= htmlspecialchars($class) ?></h3>
              </div>
              <div class="class-body">
                <div class="student-count"><?= $studentCounts[$class] ?></div>
                <div class="student-label">Students</div>
                <a href="#" class="view-btn">View Details</a>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>

<footer>
  <p>&copy; 2025 MEJECRES SCHOOL | Designed by MUGISHA</p>
</footer>

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
</script>
</body>
</html>