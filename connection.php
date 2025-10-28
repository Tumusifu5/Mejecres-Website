<?php
// connection.php

class DatabaseConnection {
    private static $connection = null;
    
    public static function getConnection() {
        if (self::$connection === null) {
            $servername = "localhost";
            $dbuser = "root";
            $dbpass = "";
            $dbname = "mejecres_db";
            
            try {
                self::$connection = new mysqli($servername, $dbuser, $dbpass, $dbname);
                
                if (self::$connection->connect_error) {
                    throw new Exception("Connection failed: " . self::$connection->connect_error);
                }
                
                self::$connection->set_charset("utf8mb4");
                
            } catch (Exception $e) {
                error_log("Database connection error: " . $e->getMessage());
                throw $e;
            }
        }
        
        return self::$connection;
    }
    
    public static function closeConnection() {
        if (self::$connection !== null) {
            self::$connection->close();
            self::$connection = null;
        }
    }
}

// Create connection using singleton pattern
$conn = DatabaseConnection::getConnection();
?>