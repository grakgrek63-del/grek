<?php
/**
 * Database Configuration
 * Majelis Dzikir Management System
 */

class Database {
    private $host = 'localhost';
    private $db_name = 'majelis_dzikir';
    private $username = 'root';
    private $password = '';
    private $charset = 'utf8mb4';

    public $conn;

    public function getConnection() {
        $this->conn = null;

        try {
            $dsn = "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=" . $this->charset;
            $this->conn = new PDO($dsn, $this->username, $this->password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $this->conn->exec("set names utf8mb4");
        } catch(PDOException $exception) {
            echo "Connection error: " . $exception->getMessage();
        }

        return $this->conn;
    }

    public function testConnection() {
        try {
            $conn = $this->getConnection();
            return $conn ? true : false;
        } catch(PDOException $e) {
            return false;
        }
    }
}
?>