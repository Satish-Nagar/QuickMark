<?php
/**
 * setup_reset_table.php
 * Script to add password reset tokens table to the database
 */

require_once 'config/database.php';

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    // Create password reset tokens table
    $sql = "CREATE TABLE IF NOT EXISTS password_reset_tokens (
        id INT PRIMARY KEY AUTO_INCREMENT,
        email VARCHAR(100) NOT NULL,
        user_type ENUM('admin', 'om') NOT NULL,
        token VARCHAR(255) UNIQUE NOT NULL,
        expires_at TIMESTAMP NOT NULL,
        used BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    $pdo->exec($sql);
    echo "✓ Password reset tokens table created successfully<br>";
    
    // Create indexes
    $indexes = [
        "CREATE INDEX IF NOT EXISTS idx_reset_token ON password_reset_tokens(token)",
        "CREATE INDEX IF NOT EXISTS idx_reset_email ON password_reset_tokens(email)"
    ];
    
    foreach ($indexes as $index_sql) {
        $pdo->exec($index_sql);
    }
    echo "✓ Indexes created successfully<br>";
    
    // Add profile_picture column to admins table if it doesn't exist
    $check_column = "SHOW COLUMNS FROM admins LIKE 'profile_picture'";
    $result = $pdo->query($check_column);
    
    if ($result->rowCount() == 0) {
        $alter_sql = "ALTER TABLE admins ADD COLUMN profile_picture VARCHAR(255) DEFAULT NULL";
        $pdo->exec($alter_sql);
        echo "✓ Profile picture column added to admins table<br>";
    } else {
        echo "✓ Profile picture column already exists in admins table<br>";
    }
    
    echo "<br><strong>Setup completed successfully!</strong><br>";
    echo "<a href='index.php'>Go to Login Page</a> | ";
    echo "<a href='test_reset.php'>Test Password Reset</a>";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?> 