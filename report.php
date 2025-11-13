<?php
// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "mejecres_db";

$conn = new mysqli($servername, $username, $password, $dbname);
if($conn->connect_error) die("Connection failed: ".$conn->connect_error);

// Create marks table if not exists - UPDATED with kiswahili
$conn->query("
CREATE TABLE IF NOT EXISTS marks (
    student_id INT PRIMARY KEY,
    math INT DEFAULT 0,
    srs INT DEFAULT 0,
    eng INT DEFAULT 0,
    set_subject INT DEFAULT 0,
    kiny INT DEFAULT 0,
    art INT DEFAULT 0,
    franc INT DEFAULT 0,
    pes INT DEFAULT 0,
    religion INT DEFAULT 0,
    kisw INT DEFAULT 0,
    total INT DEFAULT 0,
    average DECIMAL(5,2) DEFAULT 0,
    grade VARCHAR(3),
    rank INT DEFAULT NULL,
    teacher_comment TEXT,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)");

// Create table for maximum marks per class
$conn->query("
CREATE TABLE IF NOT EXISTS class_max_marks (
    class VARCHAR(10) PRIMARY KEY,
    math INT DEFAULT 0,
    srs INT DEFAULT 0,
    eng INT DEFAULT 0,
    set_subject INT DEFAULT 0,
    kiny INT DEFAULT 0,
    art INT DEFAULT 0,
    franc INT DEFAULT 0,
    pes INT DEFAULT 0,
    religion INT DEFAULT 0,
    kisw INT DEFAULT 0,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)");

// Fetch available classes
$classes = [];
$res = $conn->query("SELECT DISTINCT class FROM students ORDER BY class");
while($row = $res->fetch_assoc()) $classes[] = $row['class'];

$selected_class = $_POST['class'] ?? ($_GET['class'] ?? '');
$students = [];
$max_marks = [];
$active_subjects = [];

if($selected_class){
    // Fetch students
    $stmt = $conn->prepare("SELECT * FROM students WHERE class=? ORDER BY first_name, last_name");
    $stmt->bind_param("s",$selected_class);
    $stmt->execute();
    $res = $stmt->get_result();
    while($row = $res->fetch_assoc()) $students[] = $row;
    
    // Fetch maximum marks for this class
    $stmt = $conn->prepare("SELECT * FROM class_max_marks WHERE class=?");
    $stmt->bind_param("s",$selected_class);
    $stmt->execute();
    $res = $stmt->get_result();
    $max_marks_raw = $res->fetch_assoc() ?? [
        'math'=>0, 'srs'=>0, 'eng'=>0, 'set_subject'=>0, 'kiny'=>0, 
        'art'=>0, 'franc'=>0, 'pes'=>0, 'religion'=>0, 'kisw'=>0
    ];
    
    // Convert all max marks to integers
    $max_marks = [
        'math' => intval($max_marks_raw['math'] ?? 0),
        'srs' => intval($max_marks_raw['srs'] ?? 0),
        'eng' => intval($max_marks_raw['eng'] ?? 0),
        'set_subject' => intval($max_marks_raw['set_subject'] ?? 0),
        'kiny' => intval($max_marks_raw['kiny'] ?? 0),
        'art' => intval($max_marks_raw['art'] ?? 0),
        'franc' => intval($max_marks_raw['franc'] ?? 0),
        'pes' => intval($max_marks_raw['pes'] ?? 0),
        'religion' => intval($max_marks_raw['religion'] ?? 0),
        'kisw' => intval($max_marks_raw['kisw'] ?? 0)
    ];
    
    // Determine which subjects are active (have max marks > 0)
    $active_subjects = array_filter($max_marks, function($mark) {
        return $mark > 0;
    });
}

// Handle Save Maximum Marks
if(isset($_POST['save_max_marks'])){
    $max_marks_data = [
        'math' => intval($_POST['max_math'] ?? 0),
        'srs' => intval($_POST['max_srs'] ?? 0),
        'eng' => intval($_POST['max_eng'] ?? 0),
        'set_subject' => intval($_POST['max_set_subject'] ?? 0),
        'kiny' => intval($_POST['max_kiny'] ?? 0),
        'art' => intval($_POST['max_art'] ?? 0),
        'franc' => intval($_POST['max_franc'] ?? 0),
        'pes' => intval($_POST['max_pes'] ?? 0),
        'religion' => intval($_POST['max_religion'] ?? 0),
        'kisw' => intval($_POST['max_kisw'] ?? 0)
    ];
    
    // Save maximum marks
    $stmt = $conn->prepare("REPLACE INTO class_max_marks 
        (class, math, srs, eng, set_subject, kiny, art, franc, pes, religion, kisw) 
        VALUES (?,?,?,?,?,?,?,?,?,?,?)");
    $stmt->bind_param("siiiiiiiiii", 
        $selected_class,
        $max_marks_data['math'],
        $max_marks_data['srs'],
        $max_marks_data['eng'],
        $max_marks_data['set_subject'],
        $max_marks_data['kiny'],
        $max_marks_data['art'],
        $max_marks_data['franc'],
        $max_marks_data['pes'],
        $max_marks_data['religion'],
        $max_marks_data['kisw']
    );
    
    if($stmt->execute()){
        $success_message = "✅ Maximum marks set for class $selected_class!";
        $max_marks = $max_marks_data;
        $active_subjects = array_filter($max_marks, function($mark) {
            return $mark > 0;
        });
    } else {
        $error_message = "Error saving maximum marks: " . $stmt->error;
    }
}

// Handle Save & Rank
$success_message = '';
$error_message = '';

if(isset($_POST['save_rank'])){
    $marks_data = $_POST['marks'] ?? [];
    $teacher_comments = $_POST['teacher_comment'] ?? [];
    
    // Validate marks before processing - now against maximum marks
    $valid = true;
    foreach($marks_data as $student_id => $subjects){
        foreach($subjects as $subject => $mark){
            $mark = intval($mark);
            // FIXED: Proper mapping for set_subject
            $db_field = $subject;
            if($subject == 'set') {
                $db_field = 'set_subject';
            }
            $max_mark = $max_marks[$db_field] ?? 0;
            
            if($mark < 0 || $mark > $max_mark){
                $subject_name = getSubjectDisplayName($db_field);
                $error_message = "Marks for $subject_name must be between 0 and $max_mark!";
                $valid = false;
                break 2;
            }
        }
    }
    
    if($valid){
        foreach($marks_data as $student_id => $subjects){
            // Clean and validate numeric inputs against max marks
            foreach($subjects as $subject => $mark){ 
                // FIXED: Proper mapping for set_subject
                $db_field = $subject;
                if($subject == 'set') {
                    $db_field = 'set_subject';
                }
                $max_mark = $max_marks[$db_field] ?? 0;
                $subjects[$subject] = is_numeric($mark) ? max(0, min($max_mark, intval($mark))) : 0;
            }
            
            // FIXED: Calculate total marks and average percentage
            $total_marks_obtained = 0;
            $total_max_marks = 0;
            
            foreach($active_subjects as $subject => $max_mark){
                // FIXED: Ensure $max_mark is integer
                $max_mark = intval($max_mark);
                
                // FIXED: Proper mapping for set_subject
                $field_name = $subject;
                if($subject == 'set_subject') {
                    $field_name = 'set';
                }
                $mark = intval($subjects[$field_name] ?? 0);
                
                // Calculate total marks obtained and total maximum marks
                $total_marks_obtained += $mark;
                $total_max_marks += $max_mark;
            }
            
            // Calculate average percentage
            $average = $total_max_marks > 0 ? round(($total_marks_obtained / $total_max_marks) * 100, 1) : 0;
            
            $grade = getGrade($average);
            $comment = trim($teacher_comments[$student_id] ?? '');

            // Save marks - UPDATED with proper field mapping
            $stmt = $conn->prepare("REPLACE INTO marks
                (student_id, math, srs, eng, set_subject, kiny, art, franc, pes, religion, kisw, total, average, grade, teacher_comment)
                VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
            $stmt->bind_param("iiiiiiiiiiiidss",
                $student_id,
                $subjects['math'],
                $subjects['srs'],
                $subjects['eng'],
                $subjects['set'],  // This maps to set_subject in database
                $subjects['kiny'],
                $subjects['art'],
                $subjects['franc'],
                $subjects['pes'],
                $subjects['religion'],
                $subjects['kisw'],
                $total_marks_obtained,  // Now stores total marks obtained
                $average,               // Average percentage
                $grade,
                $comment
            );

            if(!$stmt->execute()){
                $error_message = "Error saving marks: " . $stmt->error;
                break;
            }
        }

        if(empty($error_message)){
            // Ranking for this class
            $stmt = $conn->prepare("SELECT m.*, s.first_name, s.last_name FROM marks m 
                JOIN students s ON m.student_id = s.id 
                WHERE s.class=? AND m.total > 0
                ORDER BY m.average DESC, m.total DESC");
            $stmt->bind_param("s",$selected_class);
            $stmt->execute();
            $res = $stmt->get_result();
            $rank = 1;
            $marks_all_display = [];
            while($row = $res->fetch_assoc()){
                $stmt_update = $conn->prepare("UPDATE marks SET rank=? WHERE student_id=?");
                $stmt_update->bind_param("ii",$rank,$row['student_id']);
                $stmt_update->execute();
                $row['rank'] = $rank;
                $marks_all_display[$row['student_id']] = $row;
                $rank++;
            }
            $success_message = "✅ Marks saved and ranking updated for class $selected_class!";
        }
    }
}

// Fetch marks for display if not already done
if(empty($marks_all_display) && $selected_class){
    $stmt = $conn->prepare("SELECT m.*, s.first_name, s.last_name FROM marks m 
        JOIN students s ON m.student_id = s.id 
        WHERE s.class=? ORDER BY m.rank ASC, s.first_name ASC");
    $stmt->bind_param("s",$selected_class);
    $stmt->execute();
    $res = $stmt->get_result();
    $marks_all_display = [];
    while($row = $res->fetch_assoc()) $marks_all_display[$row['student_id']] = $row;
}

// Grade function - FIXED: Properly handles 0 values
function getGrade($percentage){
    if($percentage >= 91) return "A+";
    if($percentage >= 81) return "A";
    if($percentage >= 71) return "B+";
    if($percentage >= 61) return "B";
    if($percentage >= 51) return "C+";
    if($percentage >= 41) return "C";
    if($percentage >= 33) return "D";
    if($percentage > 0) return "E";
    return "-";
}

// Get grade color
function getGradeColor($grade){
    $colors = [
        'A+' => '#28a745',
        'A' => '#28a745',
        'B+' => '#20c997',
        'B' => '#20c997',
        'C+' => '#ffc107',
        'C' => '#ffc107',
        'D' => '#fd7e14',
        'E' => '#dc3545',
        '-' => '#6c757d'
    ];
    return $colors[$grade] ?? '#6c757d';
}

// Function to get subject display name
function getSubjectDisplayName($subject){
    $names = [
        'math' => 'Mathematics',
        'srs' => 'Social Studies',
        'eng' => 'English',
        'set_subject' => 'Science & Tech',
        'set' => 'Science & Tech',  // ADDED: Map 'set' to same display name
        'kiny' => 'Kinyarwanda',
        'kisw' => 'Kiswahili',
        'art' => 'Creative Arts',
        'franc' => 'French',
        'pes' => 'PE',
        'religion' => 'Religion'
    ];
    return $names[$subject] ?? $subject;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="icon" href="logo.jpg" type="image/x-icon">

<title>Student Marks & Ranking - Grade <?= htmlspecialchars($selected_class) ?></title>
<style>
body{font-family:Arial, sans-serif; background:#f8f9fa; padding:15px;}
.container{max-width:1400px; margin:0 auto; background:#fff; padding:20px; border-radius:10px; box-shadow:0 2px 10px rgba(0,0,0,0.1);}
h2{color:#1a4b8c; margin-bottom:20px; border-bottom:2px solid #e9ecef; padding-bottom:10px;}
.header-actions{display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; flex-wrap:wrap; gap:10px;}
.class-selector{display:flex; align-items:center; gap:10px;}
.class-selector select{padding:8px 12px; border:1px solid #ced4da; border-radius:5px; font-size:14px;}
table{border-collapse:collapse; width:100%; margin-bottom:20px;}
table, th, td{border:1px solid #dee2e6;}
th, td{padding:8px; text-align:center; font-size:13px;}
th{background:#1a4b8c; color:white; font-weight:600;}
/* Fixed sticky header background */
th.sticky{position:sticky; right:0; background:#1a4b8c !important; color:white; z-index:2; box-shadow:-2px 0 5px rgba(0,0,0,0.1);}
td.sticky{position:sticky; right:0; background:#fff; z-index:1; box-shadow:-2px 0 5px rgba(0,0,0,0.1);}
/* Ensure sticky cells maintain background on hover */
.student-row:hover td.sticky{background:#f8f9fa !important;}
input.marks-input{width:55px; text-align:center; padding:4px; border:1px solid #ced4da; border-radius:3px; font-size:12px;}
input.marks-input:focus{outline:none; border-color:#1a4b8c; box-shadow:0 0 0 2px rgba(26,75,140,0.25);}
textarea.comment-input{width:120px; height:35px; padding:4px; border:1px solid #ced4da; border-radius:3px; font-size:11px; resize:vertical;}
button{padding:8px 15px; margin:2px; border:none; border-radius:5px; cursor:pointer; font-size:13px; transition:all 0.3s;}
.btn-primary{background:#1a4b8c; color:white;}
.btn-primary:hover{background:#0d3a6b;}
.btn-success{background:#28a745; color:white;}
.btn-success:hover{background:#218838;}
.btn-warning{background:#ffc107; color:#212529;}
.btn-warning:hover{background:#e0a800;}
.btn-secondary{background:#6c757d; color:white;}
.btn-secondary:hover{background:#545b62;}
.btn-info{background:#17a2b8; color:white;}
.btn-info:hover{background:#138496;}
.btn-white-black{background: #6c8580ff;; color:white; border:2px solid ;}
.btn-white-black:hover{background: #fff;; color: #6c8580ff; border:2px solid ;}
.alert-success{background:#d4edda; color:#155724; padding:12px; border-radius:5px; margin:10px 0; border:1px solid #c3e6cb;}
.alert-error{background:#f8d7da; color:#721c24; padding:12px; border-radius:5px; margin:10px 0; border:1px solid #f5c6cb;}
.student-row:hover{background:#f8f9fa;}
.grade-badge{padding:2px 6px; border-radius:3px; color:white; font-weight:bold; font-size:11px;}
.stats-bar{display:flex; gap:15px; margin:15px 0; padding:10px; background:#e9ecef; border-radius:5px; flex-wrap:wrap;}
.stat-item{display:flex; align-items:center; gap:5px; font-size:13px;}
.max-marks-badge{background:#28a745; color:white; padding:2px 6px; border-radius:3px; font-size:10px; font-weight:bold;}
.subject-inactive{background:#f8f9fa; color:#6c757d;}
/* Ensure table header maintains style in sticky columns */
thead th.sticky{background:#1a4b8c !important; color:white !important;}

/* New Styles for Maximum Marks Section */
.max-marks-container {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    padding: 25px;
    border-radius: 15px;
    margin-bottom: 25px;
    border-left: 6px solid #4a90e2;
    box-shadow: 0 8px 25px rgba(0,0,0,0.1);
    color: white;
    position: relative;
    overflow: hidden;
}

.max-marks-container::before {
    content: '';
    position: absolute;
    top: -50px;
    right: -50px;
    width: 150px;
    height: 150px;
    background: rgba(255,255,255,0.1);
    border-radius: 50%;
}

.max-marks-header {
    display: flex;
    align-items: center;
    gap: 15px;
    margin-bottom: 20px;
}

.max-marks-icon {
    font-size: 28px;
    background: rgba(255,255,255,0.2);
    padding: 12px;
    border-radius: 12px;
    backdrop-filter: blur(10px);
}

.max-marks-title {
    font-size: 22px;
    font-weight: 700;
    margin: 0;
    text-shadow: 0 2px 4px rgba(0,0,0,0.3);
}

.max-marks-subtitle {
    font-size: 14px;
    opacity: 0.9;
    margin-bottom: 25px;
    line-height: 1.5;
}

.max-marks-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 15px;
    margin-bottom: 20px;
}

.max-marks-card {
    background: rgba(255,255,255,0.95);
    padding: 20px;
    border-radius: 12px;
    border: 2px solid rgba(255,255,255,0.3);
    backdrop-filter: blur(10px);
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.max-marks-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
    border-color: #4a90e2;
}

.subject-header {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 15px;
}

.subject-icon {
    font-size: 20px;
    background: linear-gradient(135deg, #667eea, #764ba2);
    padding: 8px;
    border-radius: 8px;
    color: white;
}

.subject-name {
    font-size: 16px;
    font-weight: 600;
    color: #2c3e50;
    margin: 0;
}

.marks-input-container {
    display: flex;
    align-items: center;
    gap: 10px;
}

.max-marks-input {
    width: 80px !important;
    text-align: center;
    padding: 10px !important;
    border: 2px solid #e1e8ed !important;
    border-radius: 8px !important;
    font-size: 16px !important;
    font-weight: 600;
    background: white;
    transition: all 0.3s ease;
}

.max-marks-input:focus {
    border-color: #4a90e2 !important;
    box-shadow: 0 0 0 3px rgba(74, 144, 226, 0.1) !important;
    transform: scale(1.05);
}

.marks-divider {
    font-size: 14px;
    font-weight: 600;
    color: #7f8c8d;
}

.max-marks-actions {
    text-align: center;
    margin-top: 10px;
}

.btn-save-marks {
    background: linear-gradient(135deg, #00b894, #00a085);
    color: white;
    padding: 12px 30px;
    border: none;
    border-radius: 8px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(0, 184, 148, 0.3);
}

.btn-save-marks:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(0, 184, 148, 0.4);
    background: linear-gradient(135deg, #00a085, #008c75);
}

.active-subjects-badge {
    background: rgba(255,255,255,0.9);
    color: #27ae60;
    padding: 12px 20px;
    border-radius: 10px;
    margin-top: 20px;
    border-left: 4px solid #27ae60;
    font-weight: 600;
}

.subject-status {
    font-size: 12px;
    padding: 4px 8px;
    border-radius: 4px;
    font-weight: 600;
    margin-left: auto;
}

.status-active {
    background: #d4edda;
    color: #155724;
}

.status-inactive {
    background: #f8d7da;
    color: #721c24;
}

/* Responsive Design */
@media screen and (max-width: 768px){
    table, th, td{font-size:11px; padding:6px;}
    input.marks-input{width:45px;}
    textarea.comment-input{width:90px; height:30px;}
    .header-actions{flex-direction:column; align-items:flex-start;}
    .max-marks-grid {
        grid-template-columns: 1fr;
    }
    .max-marks-container {
        padding: 20px 15px;
    }
    .max-marks-card {
        padding: 15px;
    }
}
</style>
</head>
<body>
<div class="container">
    <div class="header-actions">
        <div class="class-selector">
            <label><strong>Select Class:</strong></label>
            <form method="POST" id="classForm" style="display:inline;">
                <select name="class" onchange="document.getElementById('classForm').submit()">
                    <option value="">--Select Class--</option>
                    <?php foreach($classes as $class): ?>
                    <option value="<?= htmlspecialchars($class) ?>" <?= ($selected_class==$class)?'selected':'' ?>>
                        Grade <?= htmlspecialchars($class) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </form>
        </div>
        
        <?php if($selected_class && !empty($students)): ?>
        <div class="action-buttons">
            <button class="btn-info" onclick="window.open('class_reports.php?class=<?= urlencode($selected_class) ?>','_blank')">
                📋 View Class Reports
            </button>
            <button class="btn-white-black" onclick="window.location.href='teachers.php'">
                ⬅️ Back to Teachers
            </button>
        </div>
        <?php endif; ?>
    </div>

    <h2>📊 Marks Entry & Ranking - <?= $selected_class ? "Grade ".htmlspecialchars($selected_class) : "Select a Class" ?></h2>

    <?php if($success_message): ?>
    <div class="alert-success"><?= htmlspecialchars($success_message) ?></div>
    <?php endif; ?>
    
    <?php if($error_message): ?>
    <div class="alert-error"><?= htmlspecialchars($error_message) ?></div>
    <?php endif; ?>

    <?php if($selected_class && empty($students)): ?>
    <div class="alert-error">No students found for class <?= htmlspecialchars($selected_class) ?>.</div>
    <?php endif; ?>

    <?php if($selected_class && $students): ?>
    <!-- Maximum Marks Setup - UPDATED ATTRACTIVE DESIGN -->
    <div class="max-marks-container">
        <div class="max-marks-header">
            <div class="max-marks-icon">📝</div>
            <div>
                <h3 class="max-marks-title">Set Maximum Marks for This Midterm</h3>
                <p class="max-marks-subtitle">Enter the maximum marks for each subject. Set to 0 for subjects not included in this assessment period.</p>
            </div>
        </div>
        
        <form method="POST">
            <input type="hidden" name="class" value="<?= htmlspecialchars($selected_class) ?>">
            
            <div class="max-marks-grid">
                <?php 
                $subjects = [
                    'math' => ['Mathematics', '🔢'],
                    'srs' => ['Social Studies', '🌍'],
                    'eng' => ['English', '📚'],
                    'set_subject' => ['Science & Tech', '🔬'],
                    'kiny' => ['Kinyarwanda', 'KINY'],
                    'kisw' => ['Kiswahili', 'KISW'],
                    'art' => ['Creative Arts', '🎨'],
                    'franc' => ['French', '🇫🇷'],
                    'pes' => ['PE', '⚽'],
                    'religion' => ['Religion', '🕊️']
                ];
                
                foreach($subjects as $field => $subject_data): 
                    $name = $subject_data[0];
                    $icon = $subject_data[1];
                    $current_value = $max_marks[$field] ?? 0;
                    $is_active = $current_value > 0;
                ?>
                <div class="max-marks-card">
                    <div class="subject-header">
                        <div class="subject-icon"><?= $icon ?></div>
                        <h4 class="subject-name"><?= $name ?></h4>
                        <span class="subject-status <?= $is_active ? 'status-active' : 'status-inactive' ?>">
                            <?= $is_active ? 'ACTIVE' : 'INACTIVE' ?>
                        </span>
                    </div>
                    
                    <div class="marks-input-container">
                        <input type="number" 
                               name="max_<?= $field ?>" 
                               class="max-marks-input" 
                               value="<?= $current_value ?>" 
                               min="0" 
                               max="100" 
                               required
                               placeholder="0-100">
                        <span class="marks-divider">/ 100</span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <div class="max-marks-actions">
                <button type="submit" name="save_max_marks" class="btn-save-marks">
                    💾 Save Maximum Marks Configuration
                </button>
            </div>
        </form>
        
        <?php if(!empty($active_subjects)): ?>
        <div class="active-subjects-badge">
            <strong>✅ Active Subjects:</strong> 
            <?= implode(', ', array_map('getSubjectDisplayName', array_keys($active_subjects))) ?>
            <span style="color: #2c3e50;">(<?= count($active_subjects) ?> subjects included)</span>
        </div>
        <?php endif; ?>
    </div>

    <!-- Statistics Bar -->
    <div class="stats-bar">
        <div class="stat-item">
            <strong>Total Students:</strong> <?= count($students) ?>
        </div>
        <div class="stat-item">
            <strong>Active Subjects:</strong> <?= count($active_subjects) ?>
        </div>
        <div class="stat-item">
            <strong>With Marks:</strong> <?= count($marks_all_display) ?>
        </div>
        <div class="stat-item">
            <strong>Pending:</strong> <?= count($students) - count($marks_all_display) ?>
        </div>
    </div>

    <?php if(empty($active_subjects)): ?>
    <div class="alert-error">
        ⚠️ Please set maximum marks above before entering student marks. Set marks to 0 for subjects not included in this midterm.
    </div>
    <?php else: ?>

    <form method="POST" id="saveRankForm">
        <input type="hidden" name="class" value="<?= htmlspecialchars($selected_class) ?>">
        
        <div style="overflow-x:auto; border:1px solid #dee2e6; border-radius:5px;">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Student Name</th>
                        
                        <?php foreach($subjects as $field => $subject_data): ?>
                            <?php if(($max_marks[$field] ?? 0) > 0): ?>
                            <th>
                                <?= $subject_data[0] ?>
                                <div class="max-marks-badge">Max: <?= $max_marks[$field] ?></div>
                            </th>
                            <?php endif; ?>
                        <?php endforeach; ?>
                        
                        <th>Teacher Comment</th>
                        <th class="sticky">Total</th>
                        <th class="sticky">Average %</th>
                        <th class="sticky">Grade</th>
                        <th class="sticky">Rank</th>
                        <th class="sticky">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($students as $student):
                        $marks = $marks_all_display[$student['id']] ?? [
                            'math'=>0,'srs'=>0,'eng'=>0,'set_subject'=>0,'kiny'=>0,'art'=>0,'franc'=>0,'pes'=>0,'religion'=>0,'kisw'=>0,
                            'total'=>0,'average'=>0,'grade'=>'','rank'=>'-','teacher_comment'=>''
                        ];
                        $grade_color = getGradeColor($marks['grade']);
                    ?>
                    <tr class="student-row" id="row-<?= $student['id'] ?>">
                        <td><?= $student['id'] ?></td>
                        <td style="text-align:left; white-space:nowrap;">
                            <strong><?= htmlspecialchars($student['first_name'].' '.$student['last_name']) ?></strong>
                        </td>
                        
                        <!-- Subject Inputs - Only show active subjects -->
                        <?php foreach($subjects as $field => $subject_data): ?>
                            <?php if(($max_marks[$field] ?? 0) > 0): 
                                // FIXED: Proper mapping for form field names
                                $input_field = $field;
                                if($field == 'set_subject') {
                                    $input_field = 'set'; // Use 'set' in form, maps to 'set_subject' in database
                                }
                            ?>
                            <td>
                                <input type="number" 
                                       name="marks[<?= $student['id'] ?>][<?= $input_field ?>]" 
                                       class="marks-input" 
                                       value="<?= $marks[$field] ?>" 
                                       min="0" 
                                       max="<?= $max_marks[$field] ?>" 
                                       onchange="calculateRow(<?= $student['id'] ?>)"
                                       title="Max: <?= $max_marks[$field] ?>">
                            </td>
                            <?php endif; ?>
                        <?php endforeach; ?>
                        
                        <td>
                            <textarea name="teacher_comment[<?= $student['id'] ?>]" class="comment-input" 
                                placeholder="Enter comment..."><?= htmlspecialchars($marks['teacher_comment'] ?? '') ?></textarea>
                        </td>
                        
                        <!-- Results -->
                        <td class="sticky" id="total-<?= $student['id'] ?>"><?= $marks['total'] ?></td>
                        <td class="sticky" id="average-<?= $student['id'] ?>"><?= $marks['average'] ?></td>
                        <td class="sticky">
                            <?php if($marks['grade'] && $marks['grade'] != '-'): ?>
                                <span class="grade-badge" style="background:<?= $grade_color ?>"><?= $marks['grade'] ?></span>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                        <td class="sticky"><?= $marks['rank'] ?></td>
                        <td class="sticky">
                            <button type="button" class="btn-info" 
                                onclick="window.open('individual_report.php?student_id=<?= $student['id'] ?>','_blank')"
                                <?= $marks['total'] == 0 ? 'disabled' : '' ?>>
                                👁️ View
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <div style="margin-top:20px; text-align:center;">
            <button type="submit" name="save_rank" class="btn-success">💾 Save Marks & Update Ranking</button>
            <button type="button" class="btn-primary" onclick="autoFillMarks()">🎲 Fill Sample Marks (Demo)</button>
            <button type="button" class="btn-white-black" onclick="window.location.href='teachers.php'">
                ⬅️ Back to Teachers
            </button>
        </div>
    </form>
    <?php endif; ?>
    <?php endif; ?>
</div>

<script>
// FIXED: Now calculates total marks and average percentage correctly
function calculateRow(studentId) {
    let totalMarksObtained = 0;
    let totalMaxMarks = 0;
    
    // Get all mark inputs for this student
    const inputs = document.querySelectorAll(`input[name="marks[${studentId}][math]"], 
                                              input[name="marks[${studentId}][srs]"],
                                              input[name="marks[${studentId}][eng]"],
                                              input[name="marks[${studentId}][set]"],
                                              input[name="marks[${studentId}][kiny]"],
                                              input[name="marks[${studentId}][kisw]"],
                                              input[name="marks[${studentId}][art]"],
                                              input[name="marks[${studentId}][franc]"],
                                              input[name="marks[${studentId}][pes]"],
                                              input[name="marks[${studentId}][religion]"]`);
    
    inputs.forEach(input => {
        const value = parseInt(input.value) || 0;
        const max = parseInt(input.max) || 100;
        
        if (max > 0) {
            // Add to total marks obtained and total maximum marks
            totalMarksObtained += value;
            totalMaxMarks += max;
        }
    });
    
    // Calculate average percentage
    const average = totalMaxMarks > 0 ? ((totalMarksObtained / totalMaxMarks) * 100).toFixed(1) : 0;
    
    document.getElementById(`total-${studentId}`).textContent = totalMarksObtained;
    document.getElementById(`average-${studentId}`).textContent = average;
    
    // Update grade display
    updateGradeDisplay(studentId, average);
}

function updateGradeDisplay(studentId, average) {
    const gradeCell = document.querySelector(`#row-${studentId} td.sticky:nth-last-child(3)`);
    if (gradeCell) {
        let grade = '-';
        let color = '#6c757d';
        
        if (average >= 91) { grade = 'A+'; color = '#28a745'; }
        else if (average >= 81) { grade = 'A'; color = '#28a745'; }
        else if (average >= 71) { grade = 'B+'; color = '#20c997'; }
        else if (average >= 61) { grade = 'B'; color = '#20c997'; }
        else if (average >= 51) { grade = 'C+'; color = '#ffc107'; }
        else if (average >= 41) { grade = 'C'; color = '#ffc107'; }
        else if (average >= 33) { grade = 'D'; color = '#fd7e14'; }
        else if (average > 0) { grade = 'E'; color = '#dc3545'; }
        
        gradeCell.innerHTML = grade !== '-' 
            ? `<span class="grade-badge" style="background:${color}">${grade}</span>`
            : '-';
    }
}

function autoFillMarks() {
    const inputs = document.querySelectorAll('input.marks-input');
    inputs.forEach(input => {
        if (!input.value || input.value == '0') {
            const max = parseInt(input.max) || 100;
            // Generate random marks between 50% and 95% of maximum
            const randomMark = Math.floor(Math.random() * (max * 0.45)) + Math.floor(max * 0.5);
            input.value = Math.min(randomMark, max);
        }
    });
    
    // Recalculate all rows
    <?php foreach($students as $student): ?>
    calculateRow(<?= $student['id'] ?>);
    <?php endforeach; ?>
    
    alert('Sample marks filled! Click "Save Marks & Update Ranking" to apply.');
}

// Add interactive features for maximum marks section
document.addEventListener('DOMContentLoaded', function() {
    // Add real-time status updates for max marks inputs
    const maxMarkInputs = document.querySelectorAll('.max-marks-input');
    
    maxMarkInputs.forEach(input => {
        input.addEventListener('input', function() {
            const value = parseInt(this.value) || 0;
            const statusElement = this.closest('.max-marks-card').querySelector('.subject-status');
            
            if (value > 0) {
                statusElement.textContent = 'ACTIVE';
                statusElement.className = 'subject-status status-active';
                this.style.borderColor = '#00b894';
            } else {
                statusElement.textContent = 'INACTIVE';
                statusElement.className = 'subject-status status-inactive';
                this.style.borderColor = '#e1e8ed';
            }
        });
    });
    
    // Add animation to cards on load
    const cards = document.querySelectorAll('.max-marks-card');
    cards.forEach((card, index) => {
        card.style.animationDelay = `${index * 0.1}s`;
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        
        setTimeout(() => {
            card.style.transition = 'all 0.6s ease';
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, index * 100);
    });
});

// Auto-save functionality (optional)
let autoSaveTimer;
document.querySelectorAll('.marks-input, .comment-input').forEach(input => {
    input.addEventListener('input', () => {
        clearTimeout(autoSaveTimer);
        autoSaveTimer = setTimeout(() => {
            // Optional: Implement auto-save here
        }, 2000);
    });
});

// Keyboard shortcuts
document.addEventListener('keydown', function(e) {
    if ((e.ctrlKey || e.metaKey) && e.key === 's') {
        e.preventDefault();
        document.querySelector('button[name="save_rank"]').click();
    }
});

// Initialize calculations on page load
document.addEventListener('DOMContentLoaded', function() {
    <?php foreach($students as $student): ?>
    calculateRow(<?= $student['id'] ?>);
    <?php endforeach; ?>
});
</script>   
</body>
</html>