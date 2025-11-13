<?php
// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "mejecres_db";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) die("Connection failed: ".$conn->connect_error);

// Create marks table if not exists
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
    total INT DEFAULT 0,
    average DECIMAL(5,2) DEFAULT 0,
    grade VARCHAR(3),
    rank INT DEFAULT NULL
)");

// Fetch classes
$classes = [];
$res = $conn->query("SELECT DISTINCT class FROM students ORDER BY class");
while($row = $res->fetch_assoc()) $classes[] = $row['class'];

// Selected class
$selected_class = $_POST['class'] ?? '';
$students = [];
if($selected_class){
    $stmt = $conn->prepare("SELECT * FROM students WHERE class=? ORDER BY first_name, last_name");
    $stmt->bind_param("s",$selected_class);
    $stmt->execute();
    $res = $stmt->get_result();
    while($row = $res->fetch_assoc()) $students[] = $row;
}

// Handle Save & Rank
$success_message = '';
if(isset($_POST['save_rank'])){
    $marks_data = $_POST['marks'] ?? [];
    foreach($marks_data as $student_id => $subjects){
        $total = array_sum($subjects);
        $average = round($total/count($subjects),1);
        $grade = getGrade($average);
        // Save marks
        $stmt = $conn->prepare("REPLACE INTO marks
            (student_id, math, srs, eng, set_subject, kiny, art, franc, pes, religion, total, average, grade)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)");
        $stmt->bind_param("iiiiiiiiiiidd",
            $student_id,
            $subjects['math'],
            $subjects['srs'],
            $subjects['eng'],
            $subjects['set'],
            $subjects['kiny'],
            $subjects['art'],
            $subjects['franc'],
            $subjects['pes'],
            $subjects['religion'],
            $total,
            $average,
            $grade
        );
        $stmt->execute();
    }

    // Ranking
    $stmt = $conn->prepare("SELECT m.*, s.first_name, s.last_name FROM marks m 
        JOIN students s ON m.student_id = s.id 
        WHERE s.class=? 
        ORDER BY m.total DESC");
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
    $success_message = "Marks saved and ranking updated for class $selected_class!";
}

// Fetch marks for display if not already
if(empty($marks_all_display) && $selected_class){
    $stmt = $conn->prepare("SELECT m.*, s.first_name, s.last_name FROM marks m 
        JOIN students s ON m.student_id = s.id 
        WHERE s.class=? ORDER BY m.rank ASC");
    $stmt->bind_param("s",$selected_class);
    $stmt->execute();
    $res = $stmt->get_result();
    $marks_all_display = [];
    while($row = $res->fetch_assoc()) $marks_all_display[$row['student_id']] = $row;
}

// Grade function
function getGrade($percentage){
    if($percentage >= 91) return "A+";
    if($percentage >= 81) return "A";
    if($percentage >= 71) return "B+";
    if($percentage >= 61) return "B";
    if($percentage >= 51) return "C+";
    if($percentage >= 41) return "C";
    if($percentage >= 33) return "D";
    return "E";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Student Marks & Ranking</title>
<style>
body{font-family:Arial; background:#f0f8ff; padding:15px;}
.container{max-width:1200px; margin:0 auto; background:#fff; padding:15px; border-radius:8px; box-shadow:0 0 10px rgba(0,0,0,0.1);}
h2{color:#1a4b8c;}
table{border-collapse:collapse; width:100%; margin-bottom:15px;}
table, th, td{border:1px solid #ddd;}
th, td{padding:6px; text-align:center; font-size:12px;}
th.sticky, td.sticky{position:sticky; right:0; background:#fff; z-index:1;}
input.marks-input{width:50px; text-align:center;}
button{padding:5px 10px; margin:2px;}
.success{background:#d4edda;color:#155724;padding:8px;margin:10px 0;}
@media screen and (max-width:768px){
  table, th, td{font-size:10px; padding:4px;}
  input.marks-input{width:40px;}
}
</style>
</head>
<body>
<div class="container">
<h2>Class Marks Entry & Ranking</h2>

<?php if($success_message): ?>
<div class="success"><?= $success_message ?></div>
<?php endif; ?>

<form method="POST">
<label>Select Class: </label>
<select name="class" onchange="this.form.submit()">
<option value="">--Select--</option>
<?php foreach($classes as $class): ?>
<option value="<?= $class ?>" <?= ($selected_class==$class)?'selected':'' ?>>Grade <?= $class ?></option>
<?php endforeach; ?>
</select>
</form>

<?php if($selected_class && $students): ?>
<form method="POST">
<div style="overflow-x:auto;">
<table>
<tr>
<th>ID</th>
<th>Name</th>
<th>Math</th>
<th>SRS</th>
<th>English</th>
<th>SET</th>
<th>Kiny</th>
<th>Arts</th>
<th>French</th>
<th>PE</th>
<th>Religion</th>
<th class="sticky">Total</th>
<th class="sticky">Avg</th>
<th class="sticky">Rank</th>
<th class="sticky">Report</th>
</tr>
<?php foreach($students as $stu): 
    $marks = $marks_all_display[$stu['id']] ?? [
        'math'=>0,'srs'=>0,'eng'=>0,'set_subject'=>0,'kiny'=>0,'art'=>0,'franc'=>0,'pes'=>0,'religion'=>0,
        'total'=>0,'average'=>0,'rank'=>'-'
    ];
?>
<tr>
<td><?= $stu['id'] ?></td>
<td><?= $stu['first_name'].' '.$stu['last_name'] ?></td>
<td><input type="number" name="marks[<?= $stu['id'] ?>][math]" class="marks-input" value="<?= $marks['math'] ?>"></td>
<td><input type="number" name="marks[<?= $stu['id'] ?>][srs]" class="marks-input" value="<?= $marks['srs'] ?>"></td>
<td><input type="number" name="marks[<?= $stu['id'] ?>][eng]" class="marks-input" value="<?= $marks['eng'] ?>"></td>
<td><input type="number" name="marks[<?= $stu['id'] ?>][set]" class="marks-input" value="<?= $marks['set_subject'] ?>"></td>
<td><input type="number" name="marks[<?= $stu['id'] ?>][kiny]" class="marks-input" value="<?= $marks['kiny'] ?>"></td>
<td><input type="number" name="marks[<?= $stu['id'] ?>][art]" class="marks-input" value="<?= $marks['art'] ?>"></td>
<td><input type="number" name="marks[<?= $stu['id'] ?>][franc]" class="marks-input" value="<?= $marks['franc'] ?>"></td>
<td><input type="number" name="marks[<?= $stu['id'] ?>][pes]" class="marks-input" value="<?= $marks['pes'] ?>"></td>
<td><input type="number" name="marks[<?= $stu['id'] ?>][religion]" class="marks-input" value="<?= $marks['religion'] ?>"></td>
<td class="sticky"><?= $marks['total'] ?></td>
<td class="sticky"><?= $marks['average'] ?></td>
<td class="sticky"><?= $marks['rank'] ?></td>
<td class="sticky">
<button type="button" onclick="window.open('report.php?student_id=<?= $stu['id'] ?>','_blank')">View Report</button>
</td>
</tr>
<?php endforeach; ?>
</table>
</div>
<button type="submit" name="save_rank">Save & Rank</button>
</form>

<h2>Class Reports</h2>
<div style="overflow-x:auto;">
<table>
<tr>
<th>Rank</th>
<th>Name</th>
<th>Total</th>
<th>Average</th>
<th>Grade</th>
<th>Report</th>
</tr>
<?php foreach($marks_all_display as $m): ?>
<tr>
<td><?= $m['rank'] ?></td>
<td><?= $m['first_name'].' '.$m['last_name'] ?></td>
<td><?= $m['total'] ?></td>
<td><?= $m['average'] ?></td>
<td><?= $m['grade'] ?></td>
<td><button type="button" onclick="window.open('report.php?student_id=<?= $m['student_id'] ?>','_blank')">View Report</button></td>
</tr>
<?php endforeach; ?>
</table>
</div>

<?php endif; ?>
</div>
</body>
</html>
