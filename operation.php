<?php
session_start();
// if (!isset($_SESSION['teacher_username'])) {
//     header('Location: teacher-login.php');
//     exit;
// }

// DB connection
$servername = "localhost";
$dbuser = "root";
$dbpass = "";
$dbname = "mejecres_db";
$conn = new mysqli($servername, $dbuser, $dbpass, $dbname);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);
$conn->set_charset("utf8mb4");

$teacher_username = $_SESSION['teacher_username'];

// Get all students - FIXED: Using first_name and last_name
$students = [];
$stmt = $conn->prepare("SELECT id, first_name, last_name, class FROM students ORDER BY class, first_name, last_name");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $students[] = $row;
}
$stmt->close();

// Handle save operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['save_marks'])) {
        $marks_data = json_decode($_POST['marks_data'], true);
        
        // Create student_marks table if it doesn't exist
        $create_table_sql = "CREATE TABLE IF NOT EXISTS student_marks (
            id INT AUTO_INCREMENT PRIMARY KEY,
            student_id INT NOT NULL,
            subject VARCHAR(100) NOT NULL,
            marks DECIMAL(5,2) NOT NULL,
            exam_type VARCHAR(50) NOT NULL,
            max_marks INT DEFAULT 100,
            created_by VARCHAR(100) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";
        $conn->query($create_table_sql);
        
        foreach ($marks_data as $student_id => $marks) {
            // Save to database
            $stmt = $conn->prepare("INSERT INTO student_marks (student_id, subject, marks, exam_type, created_by) 
                                  VALUES (?, ?, ?, ?, ?) 
                                  ON DUPLICATE KEY UPDATE marks = ?");
            $stmt->bind_param("isdisi", $student_id, $marks['subject'], $marks['marks'], $marks['exam_type'], $teacher_username, $marks['marks']);
            $stmt->execute();
            $stmt->close();
        }
        
        echo json_encode(['success' => true, 'message' => 'Marks saved successfully!']);
        exit;
    }
    
    if (isset($_POST['export_excel'])) {
        $marks_data = json_decode($_POST['marks_data'], true);
        
        // Generate Excel file
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename="student_marks_' . date('Y-m-d') . '.xls"');
        
        echo "Student Marks Report\n\n";
        echo "Student Name\tClass\tSubject\tMarks\tGrade\tPosition\n";
        
        // Sort students by marks
        $sorted_students = [];
        foreach ($marks_data as $student_id => $data) {
            $student_info = array_filter($students, function($s) use ($student_id) {
                return $s['id'] == $student_id;
            });
            $student_info = reset($student_info);
            
            $full_name = $student_info['first_name'] . ' ' . $student_info['last_name'];
            $sorted_students[] = [
                'name' => $full_name,
                'class' => $student_info['class'],
                'subject' => $data['subject'],
                'marks' => $data['marks'],
                'grade' => calculateGrade($data['marks'])
            ];
        }
        
        // Sort by marks descending
        usort($sorted_students, function($a, $b) {
            return $b['marks'] - $a['marks'];
        });
        
        // Add position
        foreach ($sorted_students as $index => $student) {
            echo $student['name'] . "\t" . 
                 $student['class'] . "\t" . 
                 $student['subject'] . "\t" . 
                 $student['marks'] . "\t" . 
                 $student['grade'] . "\t" . 
                 ($index + 1) . "\n";
        }
        exit;
    }
}

$conn->close();

function calculateGrade($marks) {
    if ($marks >= 80) return 'A';
    if ($marks >= 70) return 'B';
    if ($marks >= 60) return 'C';
    if ($marks >= 50) return 'D';
    return 'F';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Marks Management - MEJECRES SCHOOL</title>
    <link rel="icon" href="logo.jpg" type="image/x-icon">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --primary: #4361ee;
            --primary-dark: #3a56d4;
            --secondary: #7209b7;
            --success: #4cc9f0;
            --info: #4895ef;
            --warning: #f72585;
            --danger: #e63946;
            --light: #f8f9fa;
            --dark: #212529;
            --bg: #f5f7fa;
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
            min-height: 100vh;
        }
        
        .header {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            padding: 1.5rem 2rem;
            box-shadow: var(--card-shadow);
        }
        
        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .user-avatar {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: rgba(255,255,255,0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            border: 2px solid rgba(255,255,255,0.3);
        }
        
        .container {
            max-width: 1400px;
            margin: 2rem auto;
            padding: 0 2rem;
        }
        
        .controls-panel {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: var(--card-shadow);
            margin-bottom: 2rem;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        
        .form-group label {
            font-weight: 500;
            color: var(--dark);
            font-size: 0.9rem;
        }
        
        input, select, button {
            padding: 10px 12px;
            border-radius: 8px;
            border: 1px solid #ddd;
            font-size: 14px;
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
        
        button.success {
            background: linear-gradient(135deg, #4caf50, #2e7d32);
        }
        
        button.warning {
            background: linear-gradient(135deg, #ff9800, #e65100);
        }
        
        button.danger {
            background: linear-gradient(135deg, var(--danger), #c1121f);
        }
        
        .spreadsheet-container {
            background: white;
            border-radius: 12px;
            box-shadow: var(--card-shadow);
            overflow: hidden;
        }
        
        .spreadsheet-header {
            background: var(--primary);
            color: white;
            padding: 1rem;
            display: grid;
            grid-template-columns: 80px 1fr 100px 120px 100px 100px 150px;
            gap: 1px;
            font-weight: 600;
            font-size: 0.9rem;
        }
        
        .spreadsheet-body {
            max-height: 600px;
            overflow-y: auto;
        }
        
        .student-row {
            display: grid;
            grid-template-columns: 80px 1fr 100px 120px 100px 100px 150px;
            gap: 1px;
            border-bottom: 1px solid #eee;
        }
        
        .student-row:nth-child(even) {
            background: #f8f9fa;
        }
        
        .student-row:hover {
            background: #e3f2fd;
        }
        
        .cell {
            padding: 12px 8px;
            border-right: 1px solid #eee;
            display: flex;
            align-items: center;
            font-size: 0.9rem;
        }
        
        .cell input {
            width: 100%;
            border: 1px solid transparent;
            background: transparent;
            padding: 8px;
            font-size: 0.9rem;
        }
        
        .cell input:focus {
            border-color: var(--primary);
            background: white;
        }
        
        .cell.readonly {
            background: #f5f5f5;
            color: #666;
        }
        
        .formula-panel {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: var(--card-shadow);
            margin-top: 2rem;
        }
        
        .formula-list {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }
        
        .formula-item {
            background: #e8f5e9;
            padding: 1rem;
            border-radius: 8px;
            border-left: 4px solid #4caf50;
        }
        
        .formula-item h4 {
            color: #2e7d32;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .formula-item code {
            background: white;
            padding: 8px;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
            display: block;
            margin-top: 8px;
            font-size: 0.8rem;
        }
        
        .action-buttons {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            margin: 15px 0;
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 500;
        }
        
        .alert.success {
            background: #e8f5e9;
            color: #2e7d32;
            border-left: 4px solid #4caf50;
        }
        
        .alert.error {
            background: #ffebee;
            color: #c62828;
            border-left: 4px solid #f44336;
        }
        
        .position-badge {
            background: var(--primary);
            color: white;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .grade-A { color: #4caf50; font-weight: 600; }
        .grade-B { color: #ff9800; font-weight: 600; }
        .grade-C { color: #ff5722; font-weight: 600; }
        .grade-D { color: #f44336; font-weight: 600; }
        .grade-F { color: #d32f2f; font-weight: 600; }
        
        @media (max-width: 768px) {
            .spreadsheet-header,
            .student-row {
                grid-template-columns: 60px 1fr 80px 80px 80px 80px 100px;
                font-size: 0.8rem;
            }
            
            .cell {
                padding: 8px;
            }
            
            .container {
                padding: 0 1rem;
            }
            
            .action-buttons {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <h1><i class="fas fa-calculator"></i> Marks Management System</h1>
            <div class="user-info">
                <div class="user-avatar">
                    <?= strtoupper(substr($teacher_username, 0, 1)) ?>
                </div>
                <div>
                    <div style="font-weight: 600;"><?= htmlspecialchars($teacher_username) ?></div>
                    <div style="font-size: 0.85rem; opacity: 0.9;">Teacher</div>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <div id="alertMessage"></div>
        
        <div class="controls-panel">
            <div class="form-group">
                <label for="subject"><i class="fas fa-book"></i> Subject</label>
                <select id="subject">
                    <option value="Mathematics">Mathematics</option>
                    <option value="English">English</option>
                    <option value="Science">Science</option>
                    <option value="Social Studies">Social Studies</option>
                    <option value="Kinyarwanda">Kinyarwanda</option>
                    <option value="French">French</option>
                    <option value="ICT">ICT</option>
                    <option value="CRE">CRE</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="examType"><i class="fas fa-file-alt"></i> Exam Type</label>
                <select id="examType">
                    <option value="Test 1">Test 1</option>
                    <option value="Test 2">Test 2</option>
                    <option value="Mid Term">Mid Term</option>
                    <option value="Final Exam">Final Exam</option>
                    <option value="Assignment">Assignment</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="maxMarks"><i class="fas fa-chart-line"></i> Maximum Marks</label>
                <input type="number" id="maxMarks" value="100" min="1" max="1000">
            </div>
            
            <div class="form-group">
                <label for="classFilter"><i class="fas fa-filter"></i> Filter by Class</label>
                <select id="classFilter">
                    <option value="all">All Classes</option>

                    <option value="N1 N1A">N1 N1A</option>
                    <option value="N1 N1B">N1 N1B</option>
                    <option value="N2">N2</option>
                    <option value="N3">N3</option>
                    <option value="P1 A">P1 A</option>
                    <option value="P1 B">P1 B</option>
                    <option value="P1 C">P1 C </option>
                    <option value="P2 A">P2 A</option>
                    <option value="P2 B">P2 B</option>
                    <option value="P2 C">P2 C</option>
                    <option value="P3 A">P3 A</option>
                    <option value="P3 B">P3 B</option>
                    <option value="P3 C">P3 C</option>
                    <option value="P4 A">P4 A</option>
                    <option value="P4 B">P4 B</option>
                    <option value="P4 C">P4 C</option>
                    <option value="P5 A">P5 A</option>
                    <option value="P5 B">P5 B</option>
                    <option value="P5 C">P5 C</option>
                    <option value="P6 A">P6 A</option>
                    <option value="P6 B">P6 B</option>
                    <option value="P6 C">P6 C</option>
                </select>
            </div>
        </div>

        <div class="spreadsheet-container">
            <div class="spreadsheet-header">
                <div class="cell">No.</div>
                <div class="cell">Student Name</div>
                <div class="cell">Class</div>
                <div class="cell">Marks</div>
                <div class="cell">Grade</div>
                <div class="cell">Position</div>
                <div class="cell">Remarks</div>
            </div>
            
            <div class="spreadsheet-body" id="spreadsheetBody">
                <?php if (empty($students)): ?>
                    <div style="text-align: center; padding: 2rem; color: #666;">
                        <i class="fas fa-users" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.5;"></i>
                        <p>No students found in the database.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($students as $index => $student): ?>
                        <div class="student-row" data-student-id="<?= $student['id'] ?>">
                            <div class="cell readonly"><?= $index + 1 ?></div>
                            <div class="cell readonly">
                                <?= htmlspecialchars($student['first_name'] . ' ' . $student['last_name']) ?>
                            </div>
                            <div class="cell readonly"><?= htmlspecialchars($student['class']) ?></div>
                            <div class="cell">
                                <input type="number" class="marks-input" min="0" max="100" 
                                       placeholder="Enter marks" data-student-id="<?= $student['id'] ?>">
                            </div>
                            <div class="cell grade-cell" id="grade-<?= $student['id'] ?>">-</div>
                            <div class="cell position-cell" id="position-<?= $student['id'] ?>">-</div>
                            <div class="cell remarks-cell" id="remarks-<?= $student['id'] ?>">-</div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="formula-panel">
            <h3><i class="fas fa-magic"></i> Available Formulas & Operations</h3>
            <div class="formula-list">
                <div class="formula-item">
                    <h4><i class="fas fa-plus"></i> Basic Arithmetic</h4>
                    <p>Use standard operators: +, -, *, /</p>
                    <code>=A1+B1  =C1*0.4  =(D1+E1)/2</code>
                </div>
                
                <div class="formula-item">
                    <h4><i class="fas fa-sort-amount-down"></i> Ranking</h4>
                    <p>Automatically ranks students by marks</p>
                    <code>Position = RANK(marks)</code>
                </div>
                
                <div class="formula-item">
                    <h4><i class="fas fa-chart-bar"></i> Grading System</h4>
                    <p>A: 80-100, B: 70-79, C: 60-69, D: 50-59, F: 0-49</p>
                    <code>Grade = IF(marks>=80,"A",IF(marks>=70,"B",...))</code>
                </div>
                
                <div class="formula-item">
                    <h4><i class="fas fa-percentage"></i> Percentage & Average</h4>
                    <p>Calculate percentages and averages</p>
                    <code>Percentage = (marks/max)*100</code>
                </div>
            </div>
        </div>

        <div class="action-buttons">
            <button class="success" onclick="calculateAll()">
                <i class="fas fa-calculator"></i> Calculate All
            </button>
            <button class="warning" onclick="saveMarks()">
                <i class="fas fa-save"></i> Save Marks
            </button>
            <button class="success" onclick="exportToExcel()">
                <i class="fas fa-download"></i> Export to Excel
            </button>
            <button class="danger" onclick="clearAll()">
                <i class="fas fa-trash"></i> Clear All
            </button>
        </div>
    </div>

    <script>
        let marksData = {};
        
        function calculateGrade(marks) {
            if (marks >= 80) return { grade: 'A', class: 'grade-A' };
            if (marks >= 70) return { grade: 'B', class: 'grade-B' };
            if (marks >= 60) return { grade: 'C', class: 'grade-C' };
            if (marks >= 50) return { grade: 'D', class: 'grade-D' };
            return { grade: 'F', class: 'grade-F' };
        }
        
        function getRemarks(marks) {
            if (marks >= 80) return 'Excellent';
            if (marks >= 70) return 'Very Good';
            if (marks >= 60) return 'Good';
            if (marks >= 50) return 'Average';
            return 'Needs Improvement';
        }
        
        function calculateAll() {
            const marksInputs = document.querySelectorAll('.marks-input');
            const marksArray = [];
            
            // Collect all marks
            marksInputs.forEach(input => {
                const studentId = input.dataset.studentId;
                const marks = parseFloat(input.value) || 0;
                marksData[studentId] = marks;
                marksArray.push({ studentId, marks });
            });
            
            // Sort by marks descending
            marksArray.sort((a, b) => b.marks - a.marks);
            
            // Update positions and grades
            marksArray.forEach((item, index) => {
                const gradeInfo = calculateGrade(item.marks);
                const remarks = getRemarks(item.marks);
                
                document.getElementById(`grade-${item.studentId}`).innerHTML = 
                    `<span class="${gradeInfo.class}">${gradeInfo.grade}</span>`;
                document.getElementById(`position-${item.studentId}`).innerHTML = 
                    `<span class="position-badge">${index + 1}</span>`;
                document.getElementById(`remarks-${item.studentId}`).textContent = remarks;
            });
            
            showAlert('Calculation completed successfully!', 'success');
        }
        
        function saveMarks() {
            const subject = document.getElementById('subject').value;
            const examType = document.getElementById('examType').value;
            const maxMarks = document.getElementById('maxMarks').value;
            
            const dataToSave = {};
            Object.keys(marksData).forEach(studentId => {
                dataToSave[studentId] = {
                    subject: subject,
                    marks: marksData[studentId],
                    exam_type: examType,
                    max_marks: maxMarks
                };
            });
            
            fetch('teacher-marks.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'save_marks=1&marks_data=' + encodeURIComponent(JSON.stringify(dataToSave))
            })
            .then(response => response.json())
            .then(data => {
                showAlert(data.message, 'success');
            })
            .catch(error => {
                showAlert('Error saving marks: ' + error, 'error');
            });
        }
        
        function exportToExcel() {
            const subject = document.getElementById('subject').value;
            const examType = document.getElementById('examType').value;
            
            const dataToExport = {};
            Object.keys(marksData).forEach(studentId => {
                dataToExport[studentId] = {
                    subject: subject,
                    marks: marksData[studentId],
                    exam_type: examType
                };
            });
            
            fetch('teacher-marks.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'export_excel=1&marks_data=' + encodeURIComponent(JSON.stringify(dataToExport))
            })
            .then(response => response.blob())
            .then(blob => {
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.style.display = 'none';
                a.href = url;
                a.download = `student_marks_${subject}_${examType}_${new Date().toISOString().split('T')[0]}.xls`;
                document.body.appendChild(a);
                a.click();
                window.URL.revokeObjectURL(url);
                showAlert('Excel file downloaded successfully!', 'success');
            })
            .catch(error => {
                showAlert('Error exporting to Excel: ' + error, 'error');
            });
        }
        
        function clearAll() {
            if (confirm('Are you sure you want to clear all marks?')) {
                document.querySelectorAll('.marks-input').forEach(input => {
                    input.value = '';
                });
                document.querySelectorAll('.grade-cell, .position-cell, .remarks-cell').forEach(cell => {
                    cell.textContent = '-';
                });
                marksData = {};
                showAlert('All marks cleared!', 'success');
            }
        }
        
        function showAlert(message, type) {
            const alertDiv = document.getElementById('alertMessage');
            alertDiv.innerHTML = `
                <div class="alert ${type}">
                    <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
                    ${message}
                </div>
            `;
            setTimeout(() => {
                alertDiv.innerHTML = '';
            }, 5000);
        }
        
        // Event listeners for real-time calculation
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.marks-input').forEach(input => {
                input.addEventListener('input', function() {
                    const studentId = this.dataset.studentId;
                    const marks = parseFloat(this.value) || 0;
                    marksData[studentId] = marks;
                    
                    // Update individual grade and remarks
                    const gradeInfo = calculateGrade(marks);
                    const remarks = getRemarks(marks);
                    
                    document.getElementById(`grade-${studentId}`).innerHTML = 
                        `<span class="${gradeInfo.class}">${gradeInfo.grade}</span>`;
                    document.getElementById(`remarks-${studentId}`).textContent = remarks;
                });
            });
            
            // Class filter
            document.getElementById('classFilter').addEventListener('change', function() {
                const selectedClass = this.value;
                document.querySelectorAll('.student-row').forEach(row => {
                    const studentClass = row.querySelector('.cell:nth-child(3)').textContent;
                    if (selectedClass === 'all' || studentClass === selectedClass) {
                        row.style.display = 'grid';
                    } else {
                        row.style.display = 'none';
                    }
                });
            });
        });
        
        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            if (e.ctrlKey || e.metaKey) {
                switch(e.key) {
                    case 's':
                        e.preventDefault();
                        saveMarks();
                        break;
                    case 'e':
                        e.preventDefault();
                        exportToExcel();
                        break;
                    case 'c':
                        e.preventDefault();
                        calculateAll();
                        break;
                }
            }
        });
    </script>
</body>
</html>