<?php
/**
 * Database Configuration
 * Smart Attendance Automation System
 */

class Database {
    private $host = 'localhost';
    private $db_name = 'attendance_system';
    private $username = 'root';
    private $password = '';
    private $conn;
    // private $host = 'sql305.infinityfree.com';
    // private $db_name = 'if0_39567190_QuickMark';
    // private $username = 'if0_39567190';
    // private $password = 'GjuzG99A9ZRcgHH'; // Replace with your actual vPanel password
    // private $conn;

    public function getConnection() {
        $this->conn = null;

        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name,
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch(PDOException $exception) {
            echo "Connection error: " . $exception->getMessage();
        }

        return $this->conn;
    }
}
?> 