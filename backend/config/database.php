<?php
require_once __DIR__ . '/EnvLoader.php';

class Database
{
    private $host;
    private $db_name;
    private $username;
    private $password;
    private $conn;

    public function __construct()
    {
        // Load credentials from environment
        $this->host = EnvLoader::get('DB_HOST', 'localhost');
        $this->db_name = EnvLoader::get('DB_NAME', 'u726446429_itinfo_academy');
        $this->username = EnvLoader::get('DB_USER', 'u726446429_itinfoadmin');
        $this->password = EnvLoader::get('DB_PASS', 'Y2=em^n9*');
    }

    public function connect()
    {
        $this->conn = null;

        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name,
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            // Log error without exposing credentials
            error_log("Database Connection Error: " . $e->getMessage());
            // Don't expose connection details in error messages
            return null;
        }

        return $this->conn;
    }
}
