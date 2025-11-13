<?php
// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "mejecres_db";

$conn = new mysqli($servername, $username, $password, $dbname);
if($conn->connect_error) die("Connection failed: ".$conn->connect_error);

$student_id = $_GET['student_id'] ?? 0;

// If no student_id provided, redirect back to report page with error
if(!$student_id) {
    header("Location: report.php?error=No student selected");
    exit();
}

// Fetch student data
$stmt = $conn->prepare("SELECT * FROM students WHERE id=?");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();

if(!$student) {
    header("Location: report.php?error=Student not found");
    exit();
}

// Fetch overall marks
$stmt = $conn->prepare("SELECT * FROM marks WHERE student_id=?");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$marks_result = $stmt->get_result();
$marks = $marks_result->fetch_assoc();

// Fetch maximum marks for this class
$stmt = $conn->prepare("SELECT * FROM class_max_marks WHERE class=?");
$stmt->bind_param("s", $student['class']);
$stmt->execute();
$max_marks_result = $stmt->get_result();
$max_marks_data = $max_marks_result->fetch_assoc();

// Initialize max_marks with proper defaults
$max_marks = [
    'math' => 0, 'srs' => 0, 'eng' => 0, 'set_subject' => 0, 'kiny' => 0, 
    'art' => 0, 'franc' => 0, 'pes' => 0, 'religion' => 0, 'kisw' => 0
];

if($max_marks_data) {
    // Safely extract and convert to integers, only for actual subjects
    foreach($max_marks as $subject => $value) {
        $max_marks[$subject] = isset($max_marks_data[$subject]) ? intval($max_marks_data[$subject]) : 0;
    }
}

// Determine active subjects (subjects with max marks > 0)
$active_subjects = array_filter($max_marks, function($mark) {
    return $mark > 0;
});

// Calculate total maximum marks (sum of active subjects)
$total_maximum_marks = array_sum($active_subjects);

// If no marks found, create empty array
if(!$marks) {
    $marks = [
        'math'=>0, 'srs'=>0, 'eng'=>0, 'set_subject'=>0, 'kiny'=>0,
        'art'=>0, 'franc'=>0, 'pes'=>0, 'religion'=>0, 'kisw'=>0,
        'total'=>0, 'average'=>0, 'grade'=>'', 'rank'=>'-', 'teacher_comment'=>''
    ];
}   

// Helper functions
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

function getRemark($grade, $mark) {
    if ($mark == 0) return "No marks";
    if ($grade == "A+" || $grade == "A") return "Excellent";
    if ($grade == "B+" || $grade == "B") return "Very Good";
    if ($grade == "C+" || $grade == "C") return "Good";
    if ($grade == "D") return "Fair";
    if ($grade == "E") return "Needs Improvement";
    return "No marks";
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

$performance_data = getPerformanceMessage($marks['average'], $marks['grade']);
$performance_message = $performance_data[0];
$performance_color = $performance_data[1];

// Function to get subject name from code
function getSubjectName($subject_code) {
    $subjects = [
        'math' => 'Mathematics',
        'srs' => 'Social Studies',
        'eng' => 'English',
        'set_subject' => 'Science & Technology',
        'kiny' => 'Kinyarwanda',
        'kisw' => 'Kiswahili',
        'art' => 'Creative Arts',
        'franc' => 'French',
        'pes' => 'Physical Education',
        'religion' => 'Religion'
    ];
    return $subjects[$subject_code] ?? $subject_code;
}

// Calculate percentage for each subject based on maximum marks
function calculatePercentage($marks_obtained, $max_marks) {
    if ($max_marks == 0) return 0;
    return round(($marks_obtained / $max_marks) * 100, 1);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="icon" href="logo.jpg" type="image/x-icon">

<title>Report Card - <?= htmlspecialchars($student['first_name'].' '.$student['last_name']) ?></title>
<style>
body{font-family:Arial; background:white; margin:0; padding:20px;}
.report-container{max-width:900px; margin:0 auto; border:2px solid #000; padding:20px;}
.header{text-align:center; border-bottom:2px solid #000; padding-bottom:15px; margin-bottom:20px;}
.logo-container{display:flex; align-items:center; justify-content:center; gap:15px; margin-bottom:10px;}
.school-logo{width:80px; height:80px; object-fit:contain;}
.school-name{font-size:24px; font-weight:bold; color:#1a4b8c;}
.term-indicator{font-size:18px; font-weight:bold; color:#d63384; margin:10px 0;}
.student-name{font-size:20px; margin:10px 0;}
table{width:100%; border-collapse:collapse; margin:15px 0;}
th, td{border:1px solid #000; padding:10px; text-align:center;}
th{background:#1a4b8c; color:white;}
.summary-table th{background:#2c5282;}
.lesson-marks-table th{background:#3c663c;}
.footer{margin-top:30px; padding-top:15px; border-top:1px solid #000;}
.signature-table{width:100%; margin-top:40px;}
.signature-table td{text-align:center; border:none; padding:20px 0;}
button{padding:8px 15px; margin:5px; border:none; border-radius:5px; cursor:pointer; font-size:13px;}
.btn-print{background:#1a4b8c; color:white;}
.btn-print:hover{background:#0d3a6b;}
.btn-close{background:#dc3545; color:white;}
.btn-close:hover{background:#c82333;}
.btn-back{background:white; color:black; border:2px solid black;}
.btn-back:hover{background:#f8f9fa;}
.no-marks-warning{background:#fff3cd; border:1px solid #ffeaa7; color:#856404; padding:10px; border-radius:5px; margin:10px 0;}
.grade-badge{padding:2px 6px; border-radius:3px; color:white; font-weight:bold; font-size:11px;}
.grade-aplus{background:#28a745;}
.grade-a{background:#28a745;}
.grade-bplus{background:#20c997;}
.grade-b{background:#20c997;}
.grade-cplus{background:#ffc107;}
.grade-c{background:#ffc107;}
.grade-d{background:#fd7e14;}
.grade-e{background:#dc3545;}
.grade-none{background:#6c757d;}
.section-title{background:#f0f0f0; padding:10px; margin:20px 0 10px 0; border-left:4px solid #1a4b8c; font-weight:bold;}
.max-marks-info{font-size:11px; color:#666; margin-top:2px;}
.subject-inactive{background:#f8f9fa; color:#6c757d;}
.percentage-badge{font-size:10px; color:#666; margin-left:5px;}
.percentage-display{font-size:12px; font-weight:bold; color:#1a4b8c; margin-left:5px;}

/* Enhanced Print Styles */
@media print {
    @page {
        margin: 0.5cm;
        size: auto;
    }
    
    body {
        margin: 0;
        padding: 0;
        background: white !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
        font-family: Arial !important;
    }
    
    .report-container {
        margin: 0 auto;
        padding: 15px;
        border: 2px solid #000;
        box-shadow: none;
        background: white;
        max-width: 100%;
    }
    
    button {
        display: none !important;
    }
    
    .no-marks-warning {
        background: #fff3cd !important;
        border: 1px solid #ffeaa7 !important;
        color: #856404 !important;
        -webkit-print-color-adjust: exact;
    }
    
    /* Remove any URL display */
    .report-container::before,
    .report-container::after {
        content: none !important;
    }
    
    /* Ensure proper colors in print */
    th {
        background: #1a4b8c !important;
        color: white !important;
        -webkit-print-color-adjust: exact;
    }
    
    .summary-table th {
        background: #2c5282 !important;
        -webkit-print-color-adjust: exact;
    }
    
    /* Grade badges in print */
    .grade-aplus, .grade-a { background: #28a745 !important; }
    .grade-bplus, .grade-b { background: #20c997 !important; }
    .grade-cplus, .grade-c { background: #ffc107 !important; }
    .grade-d { background: #fd7e14 !important; }
    .grade-e { background: #dc3545 !important; }
    
    /* Prevent page breaks inside important elements */
    .header, table {
        page-break-inside: avoid;
    }
    
    .student-name {
        page-break-after: avoid;
    }
}
</style>
</head>
<body>
<div class="report-container">
    <!-- Header -->
    <div class="header">
        <div class="logo-container">
            <img src="logo.jpg" alt="School Logo" class="school-logo">
            <div class="school-name">MEJECRES PRIMARY SCHOOL</div>
        </div>
        <div class="term-indicator">MID-TERM TEST REPORT CARD</div>
        <div class="student-name"><?= htmlspecialchars($student['first_name'].' '.$student['last_name']) ?></div>
        <div>Grade: <?= htmlspecialchars($student['class']) ?> | Admission: <?= htmlspecialchars($student['student_number']) ?></div>
        <div style="margin-top:5px; font-size:14px; color:#666;">
            Subjects Assessed: <?= count($active_subjects) ?> out of 10
        </div>
    </div>

    <!-- Student Information -->
    <table>
        <tr>
            <th>Student Name</th>
            <td><?= htmlspecialchars($student['first_name'].' '.$student['last_name']) ?></td>
            <th>Class</th>
            <td><?= htmlspecialchars($student['class']) ?></td>
        </tr>
        <tr>
            <th>Admission Number</th>
            <td><?= htmlspecialchars($student['student_number']) ?></td>
            <th>Birth Year</th>
            <td><?= htmlspecialchars($student['birth_year']) ?></td>
        </tr>
        <tr>
            <th>Father's Phone</th>
            <td><?= htmlspecialchars($student['father_phone'] ?: 'Not provided') ?></td>
            <th>Mother's Phone</th>
            <td><?= htmlspecialchars($student['mother_phone'] ?: 'Not provided') ?></td>
        </tr>
    </table>

    <!-- Academic Performance -->
    <h3 style="text-align:center; margin:20px 0;">ACADEMIC PERFORMANCE - MID-TERM TEST</h3>
    
    <?php if($marks['total'] == 0): ?>
    <div class="no-marks-warning">
        <strong>⚠️ No marks entered for this student.</strong>
        <p style="margin:5px 0 0 0;">Marks need to be entered in the marks entry system before reports can be generated.</p>
    </div>
    <?php endif; ?>
    
    <?php if(empty($active_subjects)): ?>
    <div class="no-marks-warning">
        <strong>⚠️ No subjects configured for this midterm.</strong>
        <p style="margin:5px 0 0 0;">Teacher needs to set maximum marks for subjects in the marks entry system.</p>
    </div>
    <?php else: ?>
    
    <table>
        <tr>
            <th>SUBJECT</th>
            <th>MAXIMUM MARKS</th>
            <th>MARKS OBTAINED</th>
            <th>PERCENTAGE</th>
            <th>GRADE</th>
            <th>REMARKS</th>
        </tr>
        <?php
        $subjects = [
            'math' => 'Mathematics',
            'srs' => ' Social Studies',
            'eng' => 'English',
            'set_subject' => 'Science & Technology',
            'kiny' => 'Kinyarwanda',
            'kisw' => 'Kiswahili',
            'art' => 'Creative Arts',
            'franc' => 'French',
            'pes' => 'Physical Education',
            'religion' => 'Religion'
        ];
        
        foreach($subjects as $subject_code => $subject_name):
            // Skip subjects that are not active in this midterm
            if (($max_marks[$subject_code] ?? 0) == 0) continue;
            
            $max_mark = $max_marks[$subject_code] ?? 0;
            $marks_obtained = $marks[$subject_code] ?? 0;
            $percentage = calculatePercentage($marks_obtained, $max_mark);
            $grade = getGrade($percentage);
            $remark = getRemark($grade, $marks_obtained);
            
            // Determine grade badge class
            $grade_class = 'grade-none';
            if ($grade == 'A+') $grade_class = 'grade-aplus';
            elseif ($grade == 'A') $grade_class = 'grade-a';
            elseif ($grade == 'B+') $grade_class = 'grade-bplus';
            elseif ($grade == 'B') $grade_class = 'grade-b';
            elseif ($grade == 'C+') $grade_class = 'grade-cplus';
            elseif ($grade == 'C') $grade_class = 'grade-c';
            elseif ($grade == 'D') $grade_class = 'grade-d';
            elseif ($grade == 'E') $grade_class = 'grade-e';
        ?>
        <tr>
            <td style="text-align:left;"><?= $subject_name ?></td>
            <td><strong><?= $max_mark ?></strong></td>
            <td><?= $marks_obtained ?></td>
            <td><?= $percentage ?>%</td>
            <td>
                <?php if($grade != '-'): ?>
                    <span class="grade-badge <?= $grade_class ?>"><?= $grade ?></span>
                <?php else: ?>
                    -
                <?php endif; ?>
            </td>
            <td><?= $remark ?></td>
        </tr>
        <?php endforeach; ?>
        
        <!-- Summary row -->
        <tr style="background:#f0f0f0; font-weight:bold;">
            <td style="text-align:left;">TOTAL</td>
            <td><?= $total_maximum_marks ?></td>
            <td><?= $marks['total'] ?></td>
            <td>
                <?= number_format($marks['average'], 1) ?>%
                <span class="percentage-display">(<?= number_format($marks['average'], 1) ?>/100)</span>
            </td>
            <td>
                <?php if($marks['grade'] && $marks['grade'] != '-'): ?>
                    <?php 
                    $overall_grade_class = 'grade-none';
                    if ($marks['grade'] == 'A+') $overall_grade_class = 'grade-aplus';
                    elseif ($marks['grade'] == 'A') $overall_grade_class = 'grade-a';
                    elseif ($marks['grade'] == 'B+') $overall_grade_class = 'grade-bplus';
                    elseif ($marks['grade'] == 'B') $overall_grade_class = 'grade-b';
                    elseif ($marks['grade'] == 'C+') $overall_grade_class = 'grade-cplus';
                    elseif ($marks['grade'] == 'C') $overall_grade_class = 'grade-c';
                    elseif ($marks['grade'] == 'D') $overall_grade_class = 'grade-d';
                    elseif ($marks['grade'] == 'E') $overall_grade_class = 'grade-e';
                    ?>
                    <span class="grade-badge <?= $overall_grade_class ?>"><?= htmlspecialchars($marks['grade']) ?></span>
                <?php else: ?>
                    -
                <?php endif; ?>
            </td>
            <td>Overall Performance</td>
        </tr>
    </table>
    <?php endif; ?>

    <!-- Summary -->
    <table class="summary-table">
        <tr>
            <th>Total Marks Obtained</th>
            <th>Total Maximum Marks</th>
            <th>Average Percentage</th>
            <th>Overall Grade</th>
            <th>Class Rank</th>
        </tr>
        <tr>
            <td><?= $marks['total'] ?></td>
            <td><?= $total_maximum_marks ?></td>
            <td>
                <?= number_format($marks['average'], 1) ?>%
                <span class="percentage-display">(<?= number_format($marks['average'], 1) ?>/100)</span>
            </td>
            <td>
                <?php if($marks['grade'] && $marks['grade'] != '-'): ?>
                    <?php 
                    $overall_grade_class = 'grade-none';
                    if ($marks['grade'] == 'A+') $overall_grade_class = 'grade-aplus';
                    elseif ($marks['grade'] == 'A') $overall_grade_class = 'grade-a';
                    elseif ($marks['grade'] == 'B+') $overall_grade_class = 'grade-bplus';
                    elseif ($marks['grade'] == 'B') $overall_grade_class = 'grade-b';
                    elseif ($marks['grade'] == 'C+') $overall_grade_class = 'grade-cplus';
                    elseif ($marks['grade'] == 'C') $overall_grade_class = 'grade-c';
                    elseif ($marks['grade'] == 'D') $overall_grade_class = 'grade-d';
                    elseif ($marks['grade'] == 'E') $overall_grade_class = 'grade-e';
                    ?>
                    <span class="grade-badge <?= $overall_grade_class ?>"><?= htmlspecialchars($marks['grade']) ?></span>
                <?php else: ?>
                    -
                <?php endif; ?>
            </td>
            <td>
                <?php if($marks['rank'] && $marks['rank'] != '-'): ?>
                    <?= htmlspecialchars($marks['rank']) ?> out of <?= getClassStudentCount($conn, $student['class']) ?>
                <?php else: ?>
                    -
                <?php endif; ?>
            </td>
        </tr>
    </table>

    <!-- Performance Summary -->
    <div style="margin:20px 0; padding:15px; border:1px solid #000; background:#f8f9fa;">
        <h4 style="margin:0 0 10px 0;">PERFORMANCE SUMMARY:</h4>
        <p style="margin:0; color:<?= $performance_color ?>; font-weight:bold;">
            <?= $performance_message ?>
        </p>
        <?php if(!empty($active_subjects)): ?>
        <p style="margin:10px 0 0 0; font-size:14px;">
            <strong>Subjects included in this midterm:</strong> 
            <?= implode(', ', array_map('getSubjectName', array_keys($active_subjects))) ?>
        </p>
        <?php endif; ?>
    </div>

    <!-- Teacher Comments -->
    <div style="margin:20px 0; padding:15px; border:1px solid #000; background:#f9f9f9;">
        <h4 style="margin:0 0 10px 0;">TEACHER'S COMMENTS:</h4>
        <p style="margin:0; font-style:italic;">
            <?= !empty($marks['teacher_comment']) ? htmlspecialchars($marks['teacher_comment']) : 'No comments provided.' ?>
        </p>
    </div>

    <!-- Signatures -->
    <table class="signature-table">
        <tr>
            <td>
                <div style="border-top:1px solid #000; width:200px; margin:0 auto;"></div>
                <div>Class Teacher's Signature</div>
            </td>
            <td>
                <div style="border-top:1px solid #000; width:200px; margin:0 auto;"></div>
                <div>Head Teacher's Signature</div>
            </td>
            <td>
                <div style="border-top:1px solid #000; width:200px; margin:0 auto;"></div>
                <div>Date: <?= date('d/m/Y') ?></div>
            </td>
        </tr>
    </table>

    <!-- Action Buttons -->
    <div style="text-align:center; margin-top:20px;">
        <button class="btn-print" onclick="window.print()">🖨️ Print Report</button>
        <button class="btn-back" onclick="window.location.href='report.php?class=<?= urlencode($student['class']) ?>'">
            📊 Back to Marks Entry
        </button>
        <button class="btn-back" onclick="window.location.href='teachers.php'">
            ⬅️ Back to Teachers
        </button>
        <button class="btn-close" onclick="window.close()">❌ Close</button>
    </div>
</div>

<script>
// Show alert if no marks or no subjects configured
<?php if($marks['total'] == 0 || empty($active_subjects)): ?>
window.onload = function() {
    setTimeout(function() {
        <?php if(empty($active_subjects)): ?>
        alert('⚠️ No subjects configured for this midterm. Teacher needs to set maximum marks first.');
        <?php else: ?>
        alert('⚠️ No marks entered for this student. Please enter marks in the marks entry system.');
        <?php endif; ?>
    }, 500);
};
<?php endif; ?>

// Keyboard shortcuts
document.addEventListener('keydown', function(e) {
    if ((e.ctrlKey || e.metaKey) && e.key === 'p') {
        e.preventDefault();
        window.print();
    }
    if (e.key === 'Escape') {
        window.close();
    }
});
</script>
</body>
</html>

<?php
// Helper function to get class student count
function getClassStudentCount($conn, $class) {
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM students WHERE class=?");
    $stmt->bind_param("s", $class);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    return $result['count'] ?? 0;
}
?>