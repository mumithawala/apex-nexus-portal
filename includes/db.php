<?php
/**
 * Database connection using PDO
 * Supports both local and live database configurations
 */

// Include URL configuration to detect environment
require_once __DIR__ . '/urls.php';

class Database {
    private $host;
    private $db_name;
    private $username;
    private $password;
    private $charset = 'utf8mb4';
    private $pdo;

    public function __construct() {
        // Set database credentials based on environment
        if ($GLOBALS['is_local']) {
            // Local Database Credentials
            $this->host = 'localhost';
            $this->db_name = 'apex-recruit';
            $this->username = 'root';
            $this->password = '';
        } else {
            // Live Database Credentials
            $this->host = 'localhost'; // Update with your live host
            $this->db_name = 'u976011089_apex_nexus_app';
            $this->username = 'u976011089_apex_nexus_app';
            $this->password = '=Y9nwbZ@lu';
        }
    }

    public function getConnection() {
        $this->pdo = null;

        try {
            $dsn = "mysql:host={$this->host};dbname={$this->db_name};charset={$this->charset}";
            
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];

            $this->pdo = new PDO($dsn, $this->username, $this->password, $options);
            
        } catch(PDOException $exception) {
            // Log error and display user-friendly message
            error_log("Database Connection Error: " . $exception->getMessage());
            
            // In production, you might want to show a generic error message
            if ($GLOBALS['is_local']) {
                die("Local Database Connection Failed: " . $exception->getMessage());
            } else {
                die("Database connection failed. Please try again later.");
            }
        }

        return $this->pdo;
    }
}

// Create a global database connection function for convenience
function getDB() {
    $database = new Database();
    return $database->getConnection();
}
?>
