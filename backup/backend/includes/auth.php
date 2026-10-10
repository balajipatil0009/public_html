<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

class Auth {
    private $db;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->connect();
    }
    
    // Login user
    public function login($username, $password) {
        // Check if database connection is available
        if ($this->db === null) {
            return false;
        }
        
        try {
            $query = "SELECT * FROM admin_users WHERE (username = :username OR email = :username) AND is_active = 1";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':username', $username);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                $user = $stmt->fetch();
                
                if (password_verify($password, $user['password'])) {
                    // Update last login
                    $updateQuery = "UPDATE admin_users SET last_login = NOW() WHERE id = :id";
                    $updateStmt = $this->db->prepare($updateQuery);
                    $updateStmt->bindParam(':id', $user['id']);
                    $updateStmt->execute();
                    
                    // Set session
                    $_SESSION['admin_id'] = $user['id'];
                    $_SESSION['admin_username'] = $user['username'];
                    $_SESSION['admin_role'] = $user['role'];
                    $_SESSION['admin_name'] = $user['full_name'];
                    $_SESSION['login_time'] = time();
                    
                    return true;
                }
            }
        } catch (Exception $e) {
            error_log("Login error: " . $e->getMessage());
        }
        
        return false;
    }
    
    // Check if user is logged in
    public function isLoggedIn() {
        if (isset($_SESSION['admin_id']) && isset($_SESSION['login_time'])) {
            // Check session timeout
            if (time() - $_SESSION['login_time'] > SESSION_TIMEOUT) {
                $this->logout();
                return false;
            }
            return true;
        }
        return false;
    }
    
    // Logout user
    public function logout() {
        session_unset();
        session_destroy();
    }
    
    // Check user role
    public function hasRole($role) {
        return isset($_SESSION['admin_role']) && $_SESSION['admin_role'] === $role;
    }
    
    // Require login
    public function requireLogin() {
        if (!$this->isLoggedIn()) {
            header('Location: login.php');
            exit();
        }
    }
}
?>
