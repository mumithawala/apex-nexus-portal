<?php
/**
 * Database connection using PDO
 * Supports both local and live database configurations
 */

class Database {
    // Live Database Credentials (Currently Active)
    // private $host = 'localhost';
    // private $db_name = 'u976011089_apex_nexus_app';
    // private $username = 'u976011089_apex_nexus_app';
    // private $password = '=Y9nwbZ@lu';
    
    // Local Database Credentials (Commented out for local use)
    private $host = 'localhost';
    private $db_name = 'apex-recruit';
    private $username = 'root';
    private $password = '';
    
    private $charset = 'utf8mb4';
    private $pdo;

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
            die("Database connection failed. Please try again later.");
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
