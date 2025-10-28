<?php
// models/Attendance.php
class Attendance {
    private $conn;
    
    public function __construct($db) {
        $this->conn = $db;
        $this->createTableIfNotExists();
    }
    
    private function createTableIfNotExists() {
        $this->conn->query("
            CREATE TABLE IF NOT EXISTS attendance (
                id INT AUTO_INCREMENT PRIMARY KEY,
                student_name VARCHAR(100) NOT NULL,
                class VARCHAR(50) NOT NULL,
                status ENUM('Present', 'Absent') NOT NULL,
                date DATE NOT NULL,
                teacher_id INT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_date_class (date, class)
            )
        ");
    }
    
    public function createMultiple($attendanceData) {
        try {
            $this->conn->begin_transaction();
            
            $date = date('Y-m-d');
            $stmt = $this->conn->prepare("
                INSERT INTO attendance (student_name, class, status, date, teacher_id) 
                VALUES (?, ?, ?, ?, ?)
            ");
            
            foreach ($attendanceData as $record) {
                $teacher_id = $record['teacher_id'] ?? $_POST['teacher_id'] ?? 1;
                $stmt->bind_param(
                    "ssssi", 
                    $record['name'], 
                    $record['class'], 
                    $record['status'], 
                    $date, 
                    $teacher_id
                );
                $stmt->execute();
            }
            
            $this->conn->commit();
            return ['success' => true, 'message' => 'Attendance saved successfully'];
            
        } catch (Exception $e) {
            $this->conn->rollback();
            error_log("Attendance model error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to save attendance: ' . $e->getMessage()];
        }
    }
    
    public function getAll($filters = []) {
        try {
            $whereClauses = [];
            $params = [];
            $types = "";
            
            if (!empty($filters['class_name'])) {
                $whereClauses[] = "class = ?";
                $params[] = $filters['class_name'];
                $types .= "s";
            }
            
            if (!empty($filters['today_only'])) {
                $whereClauses[] = "date = CURDATE()";
            }
            
            $where = "";
            if (!empty($whereClauses)) {
                $where = "WHERE " . implode(" AND ", $whereClauses);
            }
            
            $query = "SELECT * FROM attendance $where ORDER BY date DESC, class, student_name";
            $stmt = $this->conn->prepare($query);
            
            if (!empty($params)) {
                $stmt->bind_param($types, ...$params);
            }
            
            $stmt->execute();
            $result = $stmt->get_result();
            
            $attendance = [];
            while ($row = $result->fetch_assoc()) {
                $attendance[] = $row;
            }
            
            return $attendance;
            
        } catch (Exception $e) {
            error_log("Attendance model error: " . $e->getMessage());
            return [];
        }
    }
    
    public function getById($id) {
        try {
            $stmt = $this->conn->prepare("SELECT * FROM attendance WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            return $result->fetch_assoc();
            
        } catch (Exception $e) {
            error_log("Attendance model error: " . $e->getMessage());
            return null;
        }
    }
    
    public function delete($id) {
        try {
            $stmt = $this->conn->prepare("DELETE FROM attendance WHERE id = ?");
            $stmt->bind_param("i", $id);
            return $stmt->execute();
            
        } catch (Exception $e) {
            error_log("Attendance model error: " . $e->getMessage());
            return false;
        }
    }
}
?>