<?php
// Simple database test
require_once '../includes/db.php';

echo "<h2>Database Test</h2>";

try {
    $database = new Database();
    $pdo = $database->getConnection();
    echo "<p style='color: green;'>✅ Database connection successful</p>";
    
    // Check if job_categories table exists
    $stmt = $pdo->prepare("SHOW TABLES LIKE 'job_categories'");
    $stmt->execute();
    $tableExists = $stmt->fetch();
    
    if ($tableExists) {
        echo "<p style='color: green;'>✅ job_categories table exists</p>";
        
        // Show table structure
        $stmt = $pdo->prepare("DESCRIBE job_categories");
        $stmt->execute();
        $columns = $stmt->fetchAll();
        
        echo "<h3>job_categories Table Structure:</h3>";
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
        foreach ($columns as $column) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($column['Field']) . "</td>";
            echo "<td>" . htmlspecialchars($column['Type']) . "</td>";
            echo "<td>" . htmlspecialchars($column['Null']) . "</td>";
            echo "<td>" . htmlspecialchars($column['Key']) . "</td>";
            echo "<td>" . htmlspecialchars($column['Default']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Show all records
        $stmt = $pdo->prepare("SELECT * FROM job_categories");
        $stmt->execute();
        $categories = $stmt->fetchAll();
        
        echo "<h3>job_categories Records (" . count($categories) . "):</h3>";
        if (count($categories) > 0) {
            echo "<table border='1' cellpadding='5'>";
            echo "<tr>";
            foreach (array_keys($categories[0]) as $key) {
                echo "<th>" . htmlspecialchars($key) . "</th>";
            }
            echo "</tr>";
            foreach ($categories as $category) {
                echo "<tr>";
                foreach ($category as $value) {
                    echo "<td>" . htmlspecialchars($value ?? 'NULL') . "</td>";
                }
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p style='color: orange;'>⚠️ No records found in job_categories table</p>";
        }
        
    } else {
        echo "<p style='color: red;'>❌ job_categories table does not exist</p>";
    }
    
    echo "<hr>";
    
    // Check if job_departments table exists
    $stmt = $pdo->prepare("SHOW TABLES LIKE 'job_departments'");
    $stmt->execute();
    $deptTableExists = $stmt->fetch();
    
    if ($deptTableExists) {
        echo "<p style='color: green;'>✅ job_departments table exists</p>";
        
        // Show table structure
        $stmt = $pdo->prepare("DESCRIBE job_departments");
        $stmt->execute();
        $columns = $stmt->fetchAll();
        
        echo "<h3>job_departments Table Structure:</h3>";
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
        foreach ($columns as $column) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($column['Field']) . "</td>";
            echo "<td>" . htmlspecialchars($column['Type']) . "</td>";
            echo "<td>" . htmlspecialchars($column['Null']) . "</td>";
            echo "<td>" . htmlspecialchars($column['Key']) . "</td>";
            echo "<td>" . htmlspecialchars($column['Default']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Show all records
        $stmt = $pdo->prepare("SELECT * FROM job_departments WHERE is_deleted = 0");
        $stmt->execute();
        $departments = $stmt->fetchAll();
        
        echo "<h3>job_departments Records (" . count($departments) . "):</h3>";
        if (count($departments) > 0) {
            echo "<table border='1' cellpadding='5'>";
            echo "<tr>";
            foreach (array_keys($departments[0]) as $key) {
                echo "<th>" . htmlspecialchars($key) . "</th>";
            }
            echo "</tr>";
            foreach ($departments as $department) {
                echo "<tr>";
                foreach ($department as $value) {
                    echo "<td>" . htmlspecialchars($value ?? 'NULL') . "</td>";
                }
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p style='color: orange;'>⚠️ No records found in job_departments table</p>";
        }
        
    } else {
        echo "<p style='color: red;'>❌ job_departments table does not exist</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
