<?php
// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "mejecres_db";

$conn = new mysqli($servername, $username, $password, $dbname);
if($conn->connect_error) die("Connection failed: ".$conn->connect_error);

$selected_class = $_GET['class'] ?? '';
if(!$selected_class) die("No class selected.");

// Fetch maximum marks for this class
$stmt = $conn->prepare("SELECT * FROM class_max_marks WHERE class=?");
$stmt->bind_param("s", $selected_class);
$stmt->execute();
$max_marks_result = $stmt->get_result();
$max_marks_data = $max_marks_result->fetch_assoc();

// Initialize max_marks with proper defaults
$max_marks = [
    'math' => 0, 'srs' => 0, 'eng' => 0, 'set_subject' => 0, 'kiny' => 0, 
    'art' => 0, 'franc' => 0, 'pes' => 0, 'religion' => 0, 'kisw' => 0
];

if($max_marks_data) {
    // Safely extract and convert to integers
    foreach($max_marks as $subject => $value) {
        $max_marks[$subject] = isset($max_marks_data[$subject]) ? intval($max_marks_data[$subject]) : 0;
    }
}

// Determine active subjects (subjects with max marks > 0)
$active_subjects = array_filter($max_marks, function($mark) {
    return $mark > 0;
});

// Fetch all students in class with their marks and ranking
$stmt = $conn->prepare("
    SELECT s.*, m.math, m.srs, m.eng, m.set_subject, m.kiny, m.art, m.franc, m.pes, m.religion, m.kisw,
           m.total, m.average, m.grade, m.rank, m.teacher_comment 
    FROM students s 
    LEFT JOIN marks m ON s.id = m.student_id 
    WHERE s.class = ? 
    ORDER BY 
        CASE 
            WHEN m.rank IS NULL THEN 9999 
            ELSE m.rank 
        END ASC,
        s.first_name ASC, 
        s.last_name ASC
");
$stmt->bind_param("s", $selected_class);
$stmt->execute();
$res = $stmt->get_result();
$students = [];
while($row = $res->fetch_assoc()) $students[] = $row;

// Helper function - FIXED: Ensure it handles 0 values properly
function getGrade($percentage){
    if($percentage >= 91) return "A+";
    if($percentage >= 81) return "A";
    if($percentage >= 71) return "B+";
    if($percentage >= 61) return "B";
    if($percentage >= 51) return "C+";
    if($percentage >= 41) return "C";
    if($percentage >= 33) return "D";
    if($percentage > 0) return "E"; // Changed to handle values above 0 but below 33
    return "-"; // Return dash for no marks
}

// Get rank badge color
function getRankBadgeColor($rank) {
    if ($rank == 1) return '#ffd700'; // Gold for 1st
    if ($rank == 2) return '#c0c0c0'; // Silver for 2nd
    if ($rank == 3) return '#cd7f32'; // Bronze for 3rd
    if ($rank <= 10) return '#1a4b8c'; // Blue for top 10
    return '#6c757d'; // Gray for others
}

// Function to get subject display name
function getSubjectDisplayName($subject){
    $names = [
        'math' => 'Mathematics',
        'srs' => 'Science & Social',
        'eng' => 'English',
        'set_subject' => 'Science & Tech',
        'kiny' => 'Kinyarwanda',
        'kisw' => 'Kiswahili',
        'art' => 'Creative Arts',
        'franc' => 'French',
        'pes' => 'PE',
        'religion' => 'Religion'
    ];
    return $names[$subject] ?? $subject;
}

// Calculate percentage for each subject based on maximum marks - FIXED: Type safety
function calculatePercentage($marks_obtained, $max_marks) {
    // Ensure both parameters are numeric
    $marks_obtained = is_numeric($marks_obtained) ? floatval($marks_obtained) : 0;
    $max_marks = is_numeric($max_marks) ? floatval($max_marks) : 0;
    
    if ($max_marks <= 0) return 0;
    return round(($marks_obtained / $max_marks) * 100, 1);
}

// Calculate overall performance message
function getPerformanceMessage($average, $grade) {
    if ($average >= 80) {
        return ["Outstanding performance! Consistent excellence across all subjects.", "#28a745"];
    } elseif ($average >= 70) {
        return ["Very good performance! Shows strong understanding of concepts.", "#20c997"];
    } elseif ($average >= 60) {
        return ["Good performance! Steady progress with room for improvement.", "#ffc107"];
    } elseif ($average >= 50) {
        return ["Satisfactory performance. Requires more effort in weaker areas.", "#fd7e14"];
    } elseif ($average >= 33) {
        return ["Needs significant improvement. Please focus on studies.", "#dc3545"];
    } elseif ($average > 0) {
        return ["Unsatisfactory performance. Immediate attention required.", "#dc3545"];
    } else {
        return ["No marks recorded for this term.", "#6c757d"];
    }
}

// Count students with and without marks
$students_with_marks = 0;
$students_without_marks = 0;
$top_students = [];

foreach($students as $student) {
    if ($student['total'] > 0) {
        $students_with_marks++;
        if ($student['rank'] <= 3) {
            $top_students[] = $student;
        }
    } else {
        $students_without_marks++;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="icon" href="logo.jpg" type="image/x-icon">

<title>Class Reports - Grade <?= htmlspecialchars($selected_class) ?></title>
<style>
* {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
}
body {
    font-family: Arial, sans-serif;
    background: #f0f8ff;
    padding: 10px;
    line-height: 1.4;
}
.container {
    max-width: 1200px;
    margin: 0 auto;
    background: #fff;
    padding: 15px;
    border-radius: 8px;
    box-shadow: 0 0 10px rgba(0,0,0,0.1);
}
h2 {
    color: #1a4b8c;
    text-align: center;
    margin-bottom: 15px;
    font-size: clamp(18px, 5vw, 24px);
}
.report-card {
    border: 1px solid #000;
    padding: 12px;
    margin-bottom: 15px;
    border-radius: 5px;
    background: #f9f9f9;
    break-inside: avoid;
    position: relative;
}
table {
    border-collapse: collapse;
    width: 100%;
    margin-bottom: 12px;
    font-size: 12px;
}
table, th, td {
    border: 1px solid #ddd;
}
th, td {
    padding: 6px;
    text-align: center;
    word-wrap: break-word;
}
th {
    background: #1a4b8c;
    color: white;
    font-size: 11px;
}
button {
    padding: 8px 12px;
    margin: 4px;
    background: #1a4b8c;
    color: white;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 12px;
    width: 100%;
    max-width: 200px;
}
button:hover {
    background: #0d3a6b;
}
.rank-badge {
    display: inline-block;
    padding: 3px 6px;
    border-radius: 10px;
    color: white;
    font-weight: bold;
    font-size: 10px;
    margin-bottom: 5px;
}
.stats-bar {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 8px;
    background: #e6f2ff;
    padding: 10px;
    border-radius: 5px;
    margin-bottom: 15px;
}
.stat-item {
    text-align: center;
    padding: 5px;
}
.stat-number {
    font-size: 18px;
    font-weight: bold;
    color: #1a4b8c;
}
.stat-label {
    font-size: 10px;
    color: #666;
}
.top-students {
    background: #fff3cd;
    padding: 12px;
    border-radius: 5px;
    margin-bottom: 15px;
    border: 1px solid #ffeaa7;
}
.top-student-item {
    display: block;
    margin: 8px 0;
    text-align: center;
    padding: 8px;
    background: white;
    border-radius: 5px;
    border: 1px solid #ffeaa7;
}
.top-student-rank {
    font-size: 14px;
    font-weight: bold;
    color: #856404;
    margin-bottom: 5px;
}
.no-marks-warning {
    background: #f8d7da;
    color: #721c24;
    padding: 8px;
    border-radius: 5px;
    margin: 8px 0;
    font-size: 12px;
}
.action-buttons {
    display: flex;
    flex-direction: column;
    gap: 8px;
    margin-top: 15px;
}
.mobile-student-info {
    display: none;
    background: #e6f2ff;
    padding: 8px;
    border-radius: 4px;
    margin-bottom: 10px;
    font-size: 12px;
}
.mobile-student-info strong {
    display: block;
    margin-bottom: 3px;
}

/* Performance Summary Styles */
.performance-summary {
    margin: 15px 0 10px 0;
    padding: 10px;
    border: 1px solid #000;
    background: #f8f9fa;
    border-radius: 4px;
    font-size: 12px;
}
.performance-summary h4 {
    margin: 0 0 8px 0;
    color: #1a4b8c;
    font-size: 13px;
}
.teacher-comments {
    margin: 10px 0;
    padding: 10px;
    border: 1px solid #000;
    background: #f9f9f9;
    border-radius: 4px;
    font-size: 12px;
}
.teacher-comments h4 {
    margin: 0 0 8px 0;
    color: #1a4b8c;
    font-size: 13px;
}
.signature-section {
    margin: 15px 0 5px 0;
    padding-top: 10px;
    border-top: 1px solid #ddd;
}
.signature-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 10px;
}
.signature-table td {
    text-align: center;
    border: none;
    padding: 15px 5px 5px 5px;
    vertical-align: top;
}
.signature-line {
    border-top: 1px solid #000;
    width: 150px;
    margin: 0 auto 5px auto;
}
.signature-label {
    font-size: 10px;
    color: #666;
}

/* Logo Styles */
.report-logo {
    max-width: 60px;
    height: auto;
    display: block;
}

.desktop-logo {
    max-width: 80px;
    height: auto;
}

/* Student Header Layout */
.student-header {
    display: flex;
    align-items: center;
    gap: 15px;
    margin-bottom: 10px;
    padding: 8px;
    background: #e6f2ff;
    border-radius: 4px;
}

.student-info-right {
    flex: 1;
}

.student-name {
    font-size: 18px;
    font-weight: bold;
    color: #1a4b8c;
    margin-bottom: 5px;
}

.student-details {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 5px;
    font-size: 11px;
}

/* Mobile Header Layout */
.mobile-student-header {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 8px;
    padding: 8px;
    background: #e6f2ff;
    border-radius: 4px;
}

.mobile-student-info-right {
    flex: 1;
}

.mobile-student-name {
    font-size: 14px;
    font-weight: bold;
    color: #1a4b8c;
    margin-bottom: 3px;
}

/* Subject Status */
.subject-active {
    background: #fff;
}
.subject-inactive {
    background: #f8f9fa;
    color: #6c757d;
    opacity: 0.7;
}
.max-marks-info {
    font-size: 9px;
    color: #666;
    margin-top: 2px;
}

/* NEW: Percentage display styles */
.percentage-display {
    font-size: 11px;
    font-weight: bold;
    color: #1a4b8c;
    margin-left: 5px;
}
.mobile-percentage {
    font-size: 10px;
    color: #1a4b8c;
    font-weight: bold;
    margin-top: 2px;
}

/* Enhanced Print Styles - REMOVES URL */
@media print {
    /* Remove browser-added headers and footers */
    @page {
        margin: 0.5cm;
        size: auto;
        margin-header: 0;
        margin-footer: 0;
    }
    
    body {
        margin: 0;
        padding: 0;
        background: white !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
        color-adjust: exact;
    }
    
    .container {
        box-shadow: none;
        padding: 10px;
        margin: 0 auto;
        max-width: 100%;
        border: none;
    }
    
    button {
        display: none !important;
    }
    
    .report-card {
        border: 1px solid #000 !important;
        margin-bottom: 15px;
        page-break-inside: avoid;
        background: white !important;
        padding: 10px;
        break-inside: avoid;
    }
    
    .stats-bar, .top-students {
        display: none;
    }
    
    /* Ensure logo is visible in print */
    .report-logo, .desktop-logo {
        display: block !important;
        max-width: 70px;
    }
    
    /* Show desktop layout in print */
    table {
        display: table !important;
        font-size: 10px;
        border-collapse: collapse;
    }
    
    .mobile-student-info,
    .mobile-subjects,
    .mobile-summary {
        display: none !important;
    }
    
    .student-header {
        display: flex !important;
    }
    
    /* Remove any URL and page number displays */
    .report-card::before,
    .report-card::after,
    .container::before,
    .container::after,
    body::before,
    body::after,
    html::before,
    html::after {
        content: none !important;
    }
    
    /* Ensure proper colors in print */
    th {
        background: #1a4b8c !important;
        color: white !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
    
    /* Performance sections in print */
    .performance-summary,
    .teacher-comments {
        border: 1px solid #000 !important;
        background: #f8f9fa !important;
        -webkit-print-color-adjust: exact;
    }
    
    /* Signature lines in print */
    .signature-line {
        border-top: 1px solid #000 !important;
    }
    
    /* Hide action buttons in print */
    .action-buttons {
        display: none !important;
    }
}

/* Mobile Styles */
@media screen and (max-width: 768px) {
    body {
        padding: 5px;
    }
    .container {
        padding: 10px;
    }
    .report-card {
        padding: 8px;
        margin-bottom: 12px;
    }
    
    /* Hide regular tables on mobile */
    table {
        display: none;
    }
    
    /* Hide desktop header on mobile */
    .student-header {
        display: none;
    }
    
    /* Show mobile-friendly layout */
    .mobile-student-info {
        display: block;
    }
    
    .mobile-student-header {
        display: flex;
    }
    
    .mobile-subjects {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 5px;
        margin: 10px 0;
    }
    
    .mobile-subject-item {
        background: white;
        padding: 6px;
        border: 1px solid #ddd;
        border-radius: 4px;
        text-align: center;
        font-size: 11px;
    }
    
    .mobile-subject-name {
        font-weight: bold;
        color: #1a4b8c;
        margin-bottom: 2px;
    }
    
    .mobile-subject-marks {
        display: flex;
        justify-content: space-between;
        font-size: 10px;
    }
    
    .mobile-summary {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 5px;
        margin: 10px 0;
    }
    
    .mobile-summary-item {
        background: white;
        padding: 8px;
        border: 1px solid #ddd;
        border-radius: 4px;
        text-align: center;
    }
    
    .mobile-summary-label {
        font-size: 10px;
        color: #666;
        margin-bottom: 3px;
    }
    
    .mobile-summary-value {
        font-size: 12px;
        font-weight: bold;
    }
    
    .stats-bar {
        grid-template-columns: repeat(2, 1fr);
        gap: 5px;
        padding: 8px;
    }
    
    .stat-number {
        font-size: 16px;
    }
    
    .action-buttons {
        flex-direction: column;
    }
    
    button {
        width: 100%;
        margin: 2px 0;
    }
    
    /* Mobile logo sizing */
    .report-logo {
        max-width: 50px;
    }
    
    /* Mobile performance sections */
    .performance-summary,
    .teacher-comments {
        font-size: 11px;
        padding: 8px;
    }
    
    .signature-table td {
        padding: 10px 3px 3px 3px;
    }
    
    .signature-line {
        width: 120px;
    }
}

/* Small mobile devices */
@media screen and (max-width: 480px) {
    .mobile-subjects {
        grid-template-columns: 1fr;
    }
    
    .mobile-summary {
        grid-template-columns: 1fr;
    }
    
    .stats-bar {
        grid-template-columns: 1fr;
    }
    
    h2 {
        font-size: 16px;
    }
    
    .report-card {
        padding: 6px;
    }
    
    .report-logo {
        max-width: 45px;
    }
    
    .mobile-student-header {
        gap: 8px;
    }
    
    .mobile-student-name {
        font-size: 13px;
    }
    
    .signature-table {
        font-size: 9px;
    }
    
    .signature-line {
        width: 100px;
    }
}

/* Desktop styles */
@media screen and (min-width: 769px) {
    .stats-bar {
        grid-template-columns: repeat(4, 1fr);
    }
    
    .action-buttons {
        flex-direction: row;
        justify-content: center;
    }
    
    button {
        width: auto;
    }
    
    /* Hide mobile header on desktop */
    .mobile-student-info {
        display: none;
    }
    
    /* Show desktop header */
    .student-header {
        display: flex;
    }
}
</style>
</head>
<body>
<div class="container">
<h2>All Student Reports - Grade <?= htmlspecialchars($selected_class) ?></h2>

<!-- Class Information -->
<div style="background:#e7f3ff; padding:12px; border-radius:5px; margin-bottom:15px; border-left:4px solid #1a4b8c;">
    <h3 style="margin:0 0 8px 0; color:#1a4b8c; font-size:16px;">📊 Midterm Test Information</h3>
    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:8px; font-size:12px;">
        <div><strong>Class:</strong> Grade <?= htmlspecialchars($selected_class) ?></div>
        <div><strong>Subjects Assessed:</strong> <?= count($active_subjects) ?> out of 10</div>
        <div><strong>Total Students:</strong> <?= count($students) ?></div>
        <div><strong>Assessment Date:</strong> <?= date('F Y') ?></div>
    </div>
    <?php if(!empty($active_subjects)): ?>
    <div style="margin-top:8px; padding:8px; background:#d4edda; border-radius:4px;">
        <strong>Active Subjects:</strong> 
        <?= implode(', ', array_map('getSubjectDisplayName', array_keys($active_subjects))) ?>
    </div>
    <?php else: ?>
    <div style="margin-top:8px; padding:8px; background:#f8d7da; border-radius:4px;">
        <strong>⚠️ No subjects configured:</strong> Teacher needs to set maximum marks for this midterm.
    </div>
    <?php endif; ?>
</div>

<!-- Statistics Bar -->
<div class="stats-bar">
    <div class="stat-item">
        <div class="stat-number"><?= count($students) ?></div>
        <div class="stat-label">Total Students</div>
    </div>
    <div class="stat-item">
        <div class="stat-number"><?= count($active_subjects) ?></div>
        <div class="stat-label">Active Subjects</div>
    </div>
    <div class="stat-item">
        <div class="stat-number"><?= $students_with_marks ?></div>
        <div class="stat-label">With Marks</div>
    </div>
    <div class="stat-item">
        <div class="stat-number"><?= $students_with_marks > 0 ? round(($students_with_marks / count($students)) * 100) : 0 ?>%</div>
        <div class="stat-label">Completion</div>
    </div>
</div>

<!-- Top Students Section -->
<?php if(!empty($top_students)): ?>
<div class="top-students">
    <h3 style="text-align:center; margin-top:0; color:#856404; font-size:14px;">🏆 Top Performing Students</h3>
    <div>
        <?php foreach($top_students as $top_student): ?>
        <div class="top-student-item">
            <div class="top-student-rank">
                <?php if($top_student['rank'] == 1): ?>🥇
                <?php elseif($top_student['rank'] == 2): ?>🥈
                <?php elseif($top_student['rank'] == 3): ?>🥉
                <?php endif; ?>
                #<?= $top_student['rank'] ?>
            </div>
            <div><strong><?= htmlspecialchars($top_student['first_name'].' '.$top_student['last_name']) ?></strong></div>
            <div><?= number_format($top_student['average'], 1) ?>%</div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<?php if(empty($students)): ?>
    <p style="text-align:center; color:red;">No students found in this class.</p>
<?php else: ?>
    <?php foreach($students as $student): 
        // Safely extract marks with proper defaults
        $marks = [
            'math' => isset($student['math']) ? intval($student['math']) : 0,
            'srs' => isset($student['srs']) ? intval($student['srs']) : 0,
            'eng' => isset($student['eng']) ? intval($student['eng']) : 0,
            'set_subject' => isset($student['set_subject']) ? intval($student['set_subject']) : 0,
            'kiny' => isset($student['kiny']) ? intval($student['kiny']) : 0,
            'art' => isset($student['art']) ? intval($student['art']) : 0,
            'franc' => isset($student['franc']) ? intval($student['franc']) : 0,
            'pes' => isset($student['pes']) ? intval($student['pes']) : 0,
            'religion' => isset($student['religion']) ? intval($student['religion']) : 0,
            'kisw' => isset($student['kisw']) ? intval($student['kisw']) : 0,
            'total' => isset($student['total']) ? intval($student['total']) : 0,
            'average' => isset($student['average']) ? floatval($student['average']) : 0,
            'grade' => $student['grade'] ?? '',
            'rank' => $student['rank'] ?? '-',
            'teacher_comment' => $student['teacher_comment'] ?? ''
        ];
        $has_marks = $marks['total'] > 0;
        
        // Calculate performance message
        $performance_data = $has_marks ? getPerformanceMessage($marks['average'], $marks['grade']) : ["No marks recorded for this term.", "#6c757d"];
        $performance_message = $performance_data[0];
        $performance_color = $performance_data[1];
    ?>
    <div class="report-card" style="<?= !$has_marks ? 'opacity:0.7; background:#f8f9fa;' : '' ?>">
        
        <!-- Desktop Header -->
        <div class="student-header">
            <img src="logo.jpg" alt="School Logo" class="desktop-logo">
            <div class="student-info-right">
                <div class="student-name">
                    <?= htmlspecialchars($student['first_name'].' '.$student['last_name']) ?>
                    <?php if($has_marks && $student['rank']): ?>
                    <span class="rank-badge" style="background:<?= getRankBadgeColor($student['rank']) ?>; margin-left: 10px;">
                        Rank <?= $student['rank'] ?>
                    </span>
                    <?php endif; ?>
                </div>
                <div class="student-details">
                    <div><strong>Admission:</strong> <?= htmlspecialchars($student['student_number']) ?></div>
                    <div><strong>Class:</strong> <?= htmlspecialchars($selected_class) ?></div>
                    <div><strong>Birth Year:</strong> <?= htmlspecialchars($student['birth_year']) ?></div>
                    <div><strong>Father Phone:</strong> <?= htmlspecialchars($student['father_phone']) ?></div>
                </div>
                <?php if(!$has_marks): ?>
                <div style="margin-top:5px;">
                    <span style="color:#dc3545; font-weight:bold; font-size:11px;">⚠️ No Marks Entered</span>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Mobile Header -->
        <div class="mobile-student-info">
            <div class="mobile-student-header">
                <img src="logo.jpg" alt="School Logo" class="report-logo">
                <div class="mobile-student-info-right">
                    <div class="mobile-student-name">
                        <?= htmlspecialchars($student['first_name'].' '.$student['last_name']) ?>
                        <?php if($has_marks && $student['rank']): ?>
                        <span class="rank-badge" style="background:<?= getRankBadgeColor($student['rank']) ?>; margin-left: 5px;">
                            Rank #<?= $student['rank'] ?>
                        </span>
                        <?php endif; ?>
                    </div>
                    <div style="font-size:11px;">
                        <strong>Admission:</strong> <?= htmlspecialchars($student['student_number']) ?> | 
                        <strong>Class:</strong> <?= htmlspecialchars($selected_class) ?>
                    </div>
                    <?php if(!$has_marks): ?>
                    <div style="margin-top:3px;">
                        <span style="color:#dc3545; font-weight:bold; font-size:10px;">⚠️ No Marks Entered</span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 5px; font-size: 11px; margin-top: 8px;">
                <div><strong>Birth Year:</strong> <?= htmlspecialchars($student['birth_year']) ?></div>
                <div><strong>Father Phone:</strong> <?= htmlspecialchars($student['father_phone']) ?></div>
                <div><strong>Mother Phone:</strong> <?= htmlspecialchars($student['mother_phone']) ?></div>
            </div>
        </div>

        <?php if($has_marks): ?>
        <!-- Mobile Subjects Grid -->
        <div class="mobile-subjects">
            <?php foreach($active_subjects as $subject => $max_mark): ?>
                <?php 
                $marks_obtained = $marks[$subject] ?? 0;
                $percentage = calculatePercentage($marks_obtained, $max_mark);
                $grade = getGrade($percentage);
                ?>
                <div class="mobile-subject-item <?= $max_mark > 0 ? 'subject-active' : 'subject-inactive' ?>">
                    <div class="mobile-subject-name"><?= getSubjectDisplayName($subject) ?></div>
                    <div class="max-marks-info">Max: <?= $max_mark ?></div>
                    <div class="mobile-subject-marks">
                        <span>Marks: <?= $marks_obtained ?></span>
                        <span>Grade: <?= $grade ?></span>
                    </div>
                    <div class="mobile-percentage">
                        (<?= $percentage ?>%)
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Mobile Summary -->
        <div class="mobile-summary">
            <div class="mobile-summary-item">
                <div class="mobile-summary-label">Total Marks</div>
                <div class="mobile-summary-value"><?= $marks['total'] ?></div>
            </div>
            <div class="mobile-summary-item">
                <div class="mobile-summary-label">Average</div>
                <div class="mobile-summary-value"><?= number_format($marks['average'], 1) ?>%</div>
            </div>
            <div class="mobile-summary-item">
                <div class="mobile-summary-label">Overall Grade</div>
                <div class="mobile-summary-value"><?= htmlspecialchars($marks['grade']) ?></div>
            </div>
            <div class="mobile-summary-item">
                <div class="mobile-summary-label">Class Rank</div>
                <div class="mobile-summary-value">
                    <?php if($student['rank']): ?>
                        #<?= $student['rank'] ?>
                    <?php else: ?>
                        -
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Desktop Tables (hidden on mobile) -->
        <table style="display: none;">
            <tr>
                <th>Subject</th>
                <th>Marks Obtained</th>
                <th>Maximum Marks</th>
                <th>Percentage</th>
                <th>Grade</th>
            </tr>
            <?php foreach($active_subjects as $subject => $max_mark): ?>
                <?php 
                $marks_obtained = $marks[$subject] ?? 0;
                $percentage = calculatePercentage($marks_obtained, $max_mark);
                $grade = getGrade($percentage);
                ?>
                <tr class="<?= $max_mark > 0 ? 'subject-active' : 'subject-inactive' ?>">
                    <td style="text-align:left;"><?= getSubjectDisplayName($subject) ?></td>
                    <td><?= $marks_obtained ?></td>
                    <td><?= $max_mark ?></td>
                    <td><?= $percentage ?>%</td>
                    <td><?= $grade ?></td>
                </tr>
            <?php endforeach; ?>
        </table>

        <table style="display: none;">
            <tr>
                <th>Total Marks</th>
                <th>Total Maximum</th>
                <th>Average</th>
                <th>Overall Grade</th>
                <th>Class Rank</th>
            </tr>
            <tr>
                <td><?= $marks['total'] ?></td>
                <td><?= array_sum($active_subjects) ?></td>
                <td>
                    <?= number_format($marks['average'], 1) ?>%
                    <span class="percentage-display">(<?= number_format($marks['average'], 1) ?>/100)</span>
                </td>
                <td><?= htmlspecialchars($marks['grade']) ?></td>
                <td>
                    <?php if($student['rank']): ?>
                        <strong><?= $student['rank'] ?></strong>
                    <?php else: ?>
                        -
                    <?php endif; ?>
                </td>
            </tr>
        </table>

        <!-- Performance Summary -->
        <div class="performance-summary">
            <h4>PERFORMANCE SUMMARY:</h4>
            <p style="margin:0 0 8px 0; color:<?= $performance_color ?>; font-weight:bold;">
                <?= $performance_message ?>
            </p>
            <?php if(!empty($active_subjects)): ?>
            <p style="margin:0; font-size:11px;">
                <strong>Subjects included in this midterm:</strong> 
                <?= implode(', ', array_map('getSubjectDisplayName', array_keys($active_subjects))) ?>
            </p>
            <?php endif; ?>
        </div>

        <!-- Teacher Comments -->
        <div class="teacher-comments">
            <h4>TEACHER'S COMMENTS:</h4>
            <p style="margin:0; font-style:italic;">
                <?= !empty($marks['teacher_comment']) ? htmlspecialchars($marks['teacher_comment']) : 'No comments provided.' ?>
            </p>
        </div>

        <!-- Signatures Section -->
        <div class="signature-section">
            <table class="signature-table">
                <tr>
                    <td>
                        <div class="signature-line"></div>
                        <div class="signature-label">Class Teacher's Signature</div>
                    </td>
                    <td>
                        <div class="signature-line"></div>
                        <div class="signature-label">Head Teacher's Signature</div>
                    </td>
                    <td>
                        <div class="signature-line"></div>
                        <div class="signature-label">Date: <?= date('d/m/Y') ?></div>
                    </td>
                </tr>
            </table>
        </div>

        <?php else: ?>
        <!-- No Marks Warning -->
        <div class="no-marks-warning">
            <strong>⚠️ No marks entered for this student.</strong>
            <p style="margin:5px 0 0 0; font-size:12px;">Marks need to be entered in the marks entry system before reports can be generated.</p>
            <?php if(empty($active_subjects)): ?>
            <p style="margin:5px 0 0 0; font-size:12px;"><strong>Note:</strong> No subjects configured for this midterm. Teacher needs to set maximum marks first.</p>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Action Buttons -->
        <div class="action-buttons">
            <?php if($has_marks): ?>
            <button onclick="window.open('individual_report.php?student_id=<?= $student['id'] ?>','_blank')">
                📄 View/Print Individual Report
            </button>
            <?php else: ?>
            <button onclick="window.location='report.php?class=<?= urlencode($selected_class) ?>'" style="background:#dc3545;">
                📝 Enter Marks
            </button>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
<?php endif; ?>

<div class="action-buttons" style="margin-top:20px;">
    <button onclick="window.location='report.php'">← Back to Report Manager</button>
    <button onclick="window.location='report.php?class=<?= urlencode($selected_class) ?>'">📝 Enter/Edit Marks</button>
    <button onclick="window.print()">🖨️ Print All Reports</button>
</div>
</div>

<script>
// Enhanced print functionality to remove URLs completely
document.addEventListener('DOMContentLoaded', function() {
    // Function to clean URL and prepare for printing
    function prepareForPrint() {
        // Remove URL parameters that cause page numbers
        const cleanUrl = window.location.origin + window.location.pathname;
        window.history.replaceState(null, document.title, cleanUrl);
        
        // Add print-specific styles
        const printStyle = document.createElement('style');
        printStyle.innerHTML = `
            @media print {
                /* Completely remove browser headers and footers */
                @page { 
                    margin: 0.5cm !important;
                    size: auto;
                    margin-header: 0 !important;
                    margin-footer: 0 !important;
                }
                
                body { 
                    margin: 0 !important;
                    padding: 10px !important;
                    -webkit-print-color-adjust: exact !important;
                    print-color-adjust: exact !important;
                }
                
                /* Hide any URL displays */
                ::after { content: none !important; }
                ::before { content: none !important; }
                
                /* Remove link URLs */
                a[href]:after { content: none !important; }
            }
        `;
        printStyle.setAttribute('id', 'print-styles');
        document.head.appendChild(printStyle);
        
        // Add printing class to body
        document.body.classList.add('printing-active');
    }

    // Clean up after printing
    function cleanupAfterPrint() {
        const printStyle = document.getElementById('print-styles');
        if (printStyle) {
            printStyle.remove();
        }
        document.body.classList.remove('printing-active');
    }

    // Print all reports button
    const printAllButton = document.querySelector('button[onclick="window.print()"]');
    if (printAllButton) {
        printAllButton.addEventListener('click', function(e) {
            e.preventDefault();
            prepareForPrint();
            setTimeout(() => {
                window.print();
                setTimeout(cleanupAfterPrint, 500);
            }, 100);
        });
    }

    // Keyboard shortcut for printing
    document.addEventListener('keydown', function(e) {
        if ((e.ctrlKey || e.metaKey) && e.key === 'p') {
            e.preventDefault();
            prepareForPrint();
            setTimeout(() => {
                window.print();
                setTimeout(cleanupAfterPrint, 500);
            }, 100);
        }
    });

    // Show/hide tables based on screen size
    function handleResponsiveLayout() {
        const isMobile = window.innerWidth <= 768;
        const tables = document.querySelectorAll('table');
        const mobileElements = document.querySelectorAll('.mobile-student-info, .mobile-subjects, .mobile-summary');
        const desktopHeaders = document.querySelectorAll('.student-header');
        const mobileHeaders = document.querySelectorAll('.mobile-student-header');
        
        if (isMobile) {
            tables.forEach(table => table.style.display = 'none');
            mobileElements.forEach(el => el.style.display = 'block');
            desktopHeaders.forEach(header => header.style.display = 'none');
            mobileHeaders.forEach(header => header.style.display = 'flex');
        } else {
            tables.forEach(table => table.style.display = '');
            mobileElements.forEach(el => el.style.display = 'none');
            desktopHeaders.forEach(header => header.style.display = 'flex');
            mobileHeaders.forEach(header => header.style.display = 'none');
        }
    }
    
    // Initial check
    handleResponsiveLayout();
    window.addEventListener('resize', handleResponsiveLayout);
    
    // Highlight top 3 students
    const rankBadges = document.querySelectorAll('.rank-badge');
    rankBadges.forEach(badge => {
        const rankText = badge.textContent;
        if (rankText.includes('#1') || rankText.includes('#2') || rankText.includes('#3')) {
            const reportCard = badge.closest('.report-card');
            reportCard.style.border = '2px solid #ffc107';
            reportCard.style.background = '#fffdf6';
        }
    });
});
</script>
</body>
</html>