<?php
// api.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

include 'connection.php';

// Check if model files exist
$modelsExist = file_exists('models/Student.php') && file_exists('models/Attendance.php');

if ($modelsExist) {
    include 'models/Student.php';
    include 'models/Attendance.php';
    $studentModel = new Student($conn);
    $attendanceModel = new Attendance($conn);
}

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

$method = $_SERVER['REQUEST_METHOD'];
$request_uri = $_SERVER['REQUEST_URI'];
$path = parse_url($request_uri, PHP_URL_PATH);

// Extract the endpoint from the path
$endpoint = basename($path);

// Also check for query parameter endpoints
if (isset($_GET['endpoint'])) {
    $endpoint = $_GET['endpoint'];
}

function sendResponse($data, $status = 200) {
    http_response_code($status);
    echo json_encode($data);
    exit;
}

function getRequestData() {
    $input = file_get_contents('php://input');
    if (!empty($input)) {
        return json_decode($input, true);
    }
    return $_POST;
}

// Direct database functions
function getClassesFromDatabase($conn) {
    try {
        // First, let's check what columns exist in the students table
        $result = $conn->query("SHOW COLUMNS FROM students");
        $columns = [];
        while ($row = $result->fetch_assoc()) {
            $columns[] = $row['Field'];
        }
        
        error_log("Students table columns: " . implode(', ', $columns));
        
        // Try different possible class column names
        $possibleClassColumns = ['class', 'class_name', 'class_level', 'grade', 'level', 'CLASS'];
        $classColumn = null;
        
        foreach ($possibleClassColumns as $col) {
            if (in_array($col, $columns)) {
                $classColumn = $col;
                break;
            }
        }
        
        if (!$classColumn) {
            error_log("No class column found in students table");
            return [];
        }
        
        error_log("Using class column: $classColumn");
        
        $result = $conn->query("
            SELECT DISTINCT $classColumn as class 
            FROM students 
            WHERE $classColumn IS NOT NULL AND $classColumn != '' 
            ORDER BY $classColumn
        ");
        
        $classes = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $classValue = $row['class'];
                // Only add if it looks like a class name (not a date)
                if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $classValue) && 
                    !preg_match('/^\d{1,2}\/\d{1,2}\/\d{4}$/', $classValue)) {
                    $classes[] = $classValue;
                }
            }
        }
        
        error_log("Found classes: " . implode(', ', $classes));
        return $classes;
        
    } catch (Exception $e) {
        error_log("Database classes error: " . $e->getMessage());
        return [];
    }
}

function getStudentsFromDatabase($className, $conn) {
    try {
        // First, let's check what columns exist in the students table
        $result = $conn->query("SHOW COLUMNS FROM students");
        $columns = [];
        while ($row = $result->fetch_assoc()) {
            $columns[] = $row['Field'];
        }
        
        error_log("Available columns in students table: " . implode(', ', $columns));
        
        // Try different possible column names
        $possibleClassColumns = ['class', 'class_name', 'class_level', 'grade', 'level', 'CLASS'];
        $possibleNameColumns = ['name', 'student_name', 'fullname', 'full_name', 'first_name', 'NAME', 'StudentName', 'Student Name'];
        $possibleIdColumns = ['id', 'student_id', 'NO', 'student_no'];
        
        $classColumn = null;
        $nameColumn = null;
        $idColumn = null;
        
        foreach ($possibleClassColumns as $col) {
            if (in_array($col, $columns)) {
                $classColumn = $col;
                break;
            }
        }
        
        foreach ($possibleNameColumns as $col) {
            if (in_array($col, $columns)) {
                $nameColumn = $col;
                break;
            }
        }
        
        foreach ($possibleIdColumns as $col) {
            if (in_array($col, $columns)) {
                $idColumn = $col;
                break;
            }
        }
        
        if (!$classColumn || !$nameColumn) {
            error_log("Required columns not found. Class: $classColumn, Name: $nameColumn");
            return [];
        }
        
        error_log("Using - Class column: $classColumn, Name column: $nameColumn, ID column: " . ($idColumn ?: 'none'));
        
        // Build query to get students with all available data
        $query = "SELECT * FROM students WHERE $classColumn = ? ORDER BY $nameColumn";
        $stmt = $conn->prepare($query);
        
        if ($stmt) {
            $stmt->bind_param("s", $className);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $students = [];
            $count = 0;
            while ($row = $result->fetch_assoc()) {
                $count++;
                // Extract the actual name and class from the row
                $studentData = [
                    'id' => $idColumn ? ($row[$idColumn] ?? $count) : $count,
                    'name' => $row[$nameColumn] ?? 'Unknown Student',
                    'class' => $row[$classColumn] ?? $className,
                    'raw_data' => $row // Include all raw data for debugging
                ];
                $students[] = $studentData;
            }
            
            error_log("Found $count students for class: $className");
            
            // Log first student for debugging
            if ($count > 0) {
                error_log("First student data: " . json_encode($students[0]));
            }
            
            return $students;
        } else {
            error_log("Failed to prepare statement: " . $conn->error);
        }
        
    } catch (Exception $e) {
        error_log("Database students error: " . $e->getMessage());
    }
    
    return [];
}

function saveAttendanceToDatabase($data, $conn) {
    try {
        $timestamp = date('Y-m-d H:i:s');
        $today = date('Y-m-d');
        
        // Check if attendance table exists, create if not with correct structure
        $tableCheck = $conn->query("SHOW TABLES LIKE 'attendance'");
        if ($tableCheck->num_rows === 0) {
            // Create attendance table with the correct column names from your database
            $createTable = $conn->query("
                CREATE TABLE attendance (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    student_name VARCHAR(255) NOT NULL,
                    class_name VARCHAR(100) NOT NULL,
                    status VARCHAR(20) NOT NULL,
                    teacher_id INT NOT NULL,
                    timestamp DATETIME NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    class VARCHAR(100),
                    INDEX idx_timestamp (timestamp),
                    INDEX idx_teacher (teacher_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");
            
            if (!$createTable) {
                throw new Exception("Failed to create attendance table: " . $conn->error);
            }
        }
        
        $conn->begin_transaction();
        
        // Use the correct column names from your database
        $stmt = $conn->prepare("
            INSERT INTO attendance (student_name, class_name, status, teacher_id, timestamp, class) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        if (!$stmt) {
            throw new Exception("Failed to prepare statement: " . $conn->error);
        }
        
        $teacher_id = intval($data['teacher_id']);
        $saved_count = 0;
        $errors = [];
        
        foreach ($data['attendance'] as $record) {
            $student_name = $record['name'] ?? '';
            $class_name = $record['class'] ?? '';
            $status = $record['status'] ?? 'Present';
            $class = $record['class'] ?? ''; // For the 'class' column
            
            // Validate data
            if (empty($student_name) || empty($class_name)) {
                $errors[] = "Invalid record: Missing name or class for student";
                continue;
            }
            
            // Ensure status is valid
            $status = ($status === 'Present') ? 'Present' : 'Absent';
            
            $stmt->bind_param(
                "sssiss", 
                $student_name, 
                $class_name, 
                $status, 
                $teacher_id,
                $timestamp,
                $class
            );
            
            if ($stmt->execute()) {
                $saved_count++;
            } else {
                $errors[] = "Failed to save: " . $student_name . " - " . $stmt->error;
                error_log("Attendance save error: " . $stmt->error);
            }
        }
        
        $conn->commit();
        
        $response = [
            'success' => true, 
            'message' => "Attendance saved successfully for $saved_count students",
            'saved_count' => $saved_count,
            'timestamp' => $timestamp
        ];
        
        if (!empty($errors)) {
            $response['warnings'] = $errors;
        }
        
        return $response;
        
    } catch (Exception $e) {
        if ($conn) {
            $conn->rollback();
        }
        error_log("Database attendance error: " . $e->getMessage());
        return [
            'success' => false, 
            'message' => 'Failed to save attendance: ' . $e->getMessage(),
            'error_details' => $e->getMessage()
        ];
    } finally {
        if (isset($stmt)) {
            $stmt->close();
        }
    }
}

try {
    // Handle both path-based and query parameter endpoints
    
    // Debug endpoint
    if (($endpoint === 'debug' || (isset($_GET['endpoint']) && $_GET['endpoint'] === 'debug')) && $method === 'GET') {
        $debugInfo = [];
        
        // Check students table structure and data
        $result = $conn->query("SHOW TABLES LIKE 'students'");
        $debugInfo['students_table_exists'] = $result && $result->num_rows > 0;
        
        if ($debugInfo['students_table_exists']) {
            // Get table structure
            $result = $conn->query("DESCRIBE students");
            $debugInfo['students_structure'] = [];
            while ($row = $result->fetch_assoc()) {
                $debugInfo['students_structure'][] = $row;
            }
            
            // Get sample data
            $result = $conn->query("SELECT * FROM students LIMIT 5");
            $debugInfo['sample_students'] = [];
            while ($row = $result->fetch_assoc()) {
                $debugInfo['sample_students'][] = $row;
            }
            
            // Get all classes
            $result = $conn->query("SELECT DISTINCT class FROM students WHERE class IS NOT NULL ORDER BY class LIMIT 10");
            $debugInfo['all_classes'] = [];
            while ($row = $result->fetch_assoc()) {
                $debugInfo['all_classes'][] = $row['class'];
            }
        }
        
        // Check attendance table
        $result = $conn->query("SHOW TABLES LIKE 'attendance'");
        $debugInfo['attendance_table_exists'] = $result && $result->num_rows > 0;
        
        if ($debugInfo['attendance_table_exists']) {
            // Get attendance table structure
            $result = $conn->query("DESCRIBE attendance");
            $debugInfo['attendance_structure'] = [];
            while ($row = $result->fetch_assoc()) {
                $debugInfo['attendance_structure'][] = $row;
            }
            
            // Get recent attendance
            $result = $conn->query("SELECT * FROM attendance ORDER BY id DESC LIMIT 5");
            $debugInfo['recent_attendance'] = [];
            while ($row = $result->fetch_assoc()) {
                $debugInfo['recent_attendance'][] = $row;
            }
        }
        
        sendResponse($debugInfo);
    }
    
    // Get all classes - Support both path and query parameter
    else if (($endpoint === 'classes' || (isset($_GET['endpoint']) && $_GET['endpoint'] === 'classes')) && $method === 'GET') {
        if ($modelsExist) {
            $classes = $studentModel->getClassesGrouped();
        } else {
            $classes = getClassesFromDatabase($conn);
        }
        sendResponse($classes);
    }
    
    // Get students by class - Support both path and query parameter
    else if (($method === 'GET') && 
             (preg_match('#^students/(.+)$#', $path, $matches) || 
              (isset($_GET['endpoint']) && $_GET['endpoint'] === 'students' && isset($_GET['class'])))) {
        
        $className = '';
        
        // Get class name from path
        if (preg_match('#^students/(.+)$#', $path, $matches)) {
            $className = urldecode($matches[1]);
        }
        // Get class name from query parameter
        else if (isset($_GET['class'])) {
            $className = urldecode($_GET['class']);
        }
        
        if (empty($className)) {
            sendResponse(['error' => 'Class parameter is required'], 400);
        }
        
        error_log("Fetching students for class: " . $className);
        
        if ($modelsExist) {
            $students = $studentModel->getByClass($className);
        } else {
            $students = getStudentsFromDatabase($className, $conn);
        }
        
        sendResponse($students);
    }
    
    // Save attendance - Support both path and query parameter
    else if (($endpoint === 'attendance' || (isset($_GET['endpoint']) && $_GET['endpoint'] === 'attendance')) && $method === 'POST') {
        $input = getRequestData();
        
        if (!isset($input['attendance']) || !is_array($input['attendance']) || !isset($input['teacher_id'])) {
            sendResponse(['success' => false, 'message' => 'Invalid attendance data. Required: attendance array and teacher_id'], 400);
        }
        
        if ($modelsExist) {
            $result = $attendanceModel->createMultiple($input['attendance']);
        } else {
            $result = saveAttendanceToDatabase($input, $conn);
        }
        sendResponse($result);
    }
    
    // Handle root access - provide API info
    else if ($endpoint === 'api.php' && $method === 'GET') {
        $apiInfo = [
            'message' => 'MEJECRES SCHOOL API',
            'version' => '1.0',
            'endpoints' => [
                'GET /api.php?endpoint=classes' => 'Get all classes',
                'GET /api.php?endpoint=students&class=CLASS_NAME' => 'Get students by class',
                'POST /api.php?endpoint=attendance' => 'Save attendance',
                'GET /api.php?endpoint=debug' => 'Debug information'
            ],
            'notes' => 'Also supports path-based endpoints: /api.php/classes, /api.php/students/CLASS_NAME'
        ];
        sendResponse($apiInfo);
    }
    
    // Handle other endpoints...
    else {
        sendResponse([
            'message' => 'Endpoint not found: ' . $endpoint, 
            'available_endpoints' => [
                'GET ?endpoint=classes',
                'GET ?endpoint=students&class=CLASS_NAME', 
                'POST ?endpoint=attendance',
                'GET ?endpoint=debug'
            ]
        ], 404);
    }
    
} catch (Exception $e) {
    error_log("API Error: " . $e->getMessage());
    sendResponse([
        'message' => 'Server error: ' . $e->getMessage(),
        'error_type' => get_class($e)
    ], 500);
}
?>