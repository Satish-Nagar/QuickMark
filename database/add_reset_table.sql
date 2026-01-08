-- Add password reset tokens table to existing database
USE attendance_system;

-- Create Password Reset Tokens table
CREATE TABLE IF NOT EXISTS password_reset_tokens (
    id INT PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(100) NOT NULL,
    user_type ENUM('admin', 'om') NOT NULL,
    token VARCHAR(255) UNIQUE NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    used BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create indexes for better performance
CREATE INDEX IF NOT EXISTS idx_reset_token ON password_reset_tokens(token);
CREATE INDEX IF NOT EXISTS idx_reset_email ON password_reset_tokens(email);

-- Add profile_picture column to admins table if it doesn't exist
ALTER TABLE admins ADD COLUMN IF NOT EXISTS profile_picture VARCHAR(255) DEFAULT NULL; 