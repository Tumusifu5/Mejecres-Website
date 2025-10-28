<?php
// models/Student.php
class Student {
    private $conn;
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    public function getClassesGrouped() {
        try {
            $result = $this->conn->query("
                SELECT DISTINCT CLASS 
                FROM students 
                WHERE CLASS IS NOT NULL AND CLASS != '' 
                AND CLASS NOT REGEXP '^[0-9]{1,2}/[0-9]{1,2}/[0-9]{4}$'  -- Exclude dates
                ORDER BY 
                    CASE 
                        -- Nursery classes first: N1, N1A, N1B, N2, N2A, etc.
                        WHEN CLASS LIKE 'N%' THEN 1
                        -- Primary classes next: P1, P1A, P1B, P2, P2A, etc.  
                        WHEN CLASS LIKE 'P%' THEN 2
                        ELSE 3
                    END,
                    CLASS
            ");
            
            $classes = [];
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $classValue = $row['CLASS'];
                    
                    // Include all classes except dates
                    if (!preg_match('/^[0-9]{1,2}\/[0-9]{1,2}\/[0-9]{4}$/', $classValue)) {
                        $classes[] = $classValue;
                    }
                }
            }
            
            return $classes;
            
        } catch (Exception $e) {
            error_log("Student model error: " . $e->getMessage());
            return [];
        }
    }
    
    public function getByClass($className) {
        try {
            $stmt = $this->conn->prepare("
                SELECT 
                    CONCAT(COALESCE(first_name, ''), ' ', COALESCE(last_name, '')) as name,
                    CLASS 
                FROM students 
                WHERE CLASS = ? 
                ORDER BY first_name, last_name
            ");
            
            if ($stmt) {
                $stmt->bind_param("s", $className);
                $stmt->execute();
                $result = $stmt->get_result();
                
                $students = [];
                while ($row = $result->fetch_assoc()) {
                    // Clean up the name
                    $name = trim($row['name']);
                    if (empty($name)) {
                        $name = 'Unknown Student';
                    }
                    
                    $students[] = [
                        'name' => $name,
                        'class' => $row['CLASS']
                    ];
                }
                
                return $students;
            }
            
        } catch (Exception $e) {
            error_log("Student model error: " . $e->getMessage());
        }
        
        return [];
    }
    
    public function getAll() {
        try {
            $result = $this->conn->query("
                SELECT 
                    CONCAT(COALESCE(first_name, ''), ' ', COALESCE(last_name, '')) as name,
                    CLASS 
                FROM students 
                WHERE CLASS IS NOT NULL AND CLASS != '' 
                AND CLASS NOT REGEXP '^[0-9]{1,2}/[0-9]{1,2}/[0-9]{4}$'
                ORDER BY CLASS, first_name, last_name
            ");
            
            $students = [];
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $name = trim($row['name']);
                    if (!empty($name)) {
                        $students[] = [
                            'name' => $name,
                            'class' => $row['CLASS']
                        ];
                    }
                }
            }
            
            return $students;
            
        } catch (Exception $e) {
            error_log("Student model error: " . $e->getMessage());
            return [];
        }
    }
    
    public function search($searchTerm) {
        try {
            $stmt = $this->conn->prepare("
                SELECT 
                    CONCAT(COALESCE(first_name, ''), ' ', COALESCE(last_name, '')) as name,
                    CLASS 
                FROM students 
                WHERE (first_name LIKE ? OR last_name LIKE ? OR CLASS LIKE ?)
                AND CLASS NOT REGEXP '^[0-9]{1,2}/[0-9]{1,2}/[0-9]{4}$'
                ORDER BY CLASS, first_name, last_name
            ");
            
            $searchPattern = "%$searchTerm%";
            $stmt->bind_param("sss", $searchPattern, $searchPattern, $searchPattern);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $students = [];
            while ($row = $result->fetch_assoc()) {
                $name = trim($row['name']);
                if (!empty($name)) {
                    $students[] = [
                        'name' => $name,
                        'class' => $row['CLASS']
                    ];
                }
            }
            
            return $students;
            
        } catch (Exception $e) {
            error_log("Student model error: " . $e->getMessage());
            return [];
        }
    }
}
?>