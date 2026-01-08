<?php
date_default_timezone_set('Asia/Kolkata');
/**
 * password_reset.php
 * Password Reset Utility for Smart Attendance System
 * Handles token generation, validation, and password updates
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/mail.php';

class PasswordReset {
    private $pdo;
    
    public function __construct() {
        $database = new Database();
        $this->pdo = $database->getConnection();
    }
    
    /**
     * Generate a secure random token
     */
    private function generateToken() {
        return bin2hex(random_bytes(32));
    }
    
    /**
     * Check if email exists in either admin or OM table
     */
    public function checkEmailExists($email) {
        $email = trim(strtolower($email));
        
        // Check in admins table
        $stmt = $this->pdo->prepare("SELECT id, 'admin' as user_type FROM admins WHERE email = ?");
        $stmt->execute([$email]);
        $admin = $stmt->fetch();
        
        if ($admin) {
            return $admin;
        }
        
        // Check in operation_managers table
        $stmt = $this->pdo->prepare("SELECT id, 'om' as user_type FROM operation_managers WHERE email = ?");
        $stmt->execute([$email]);
        $om = $stmt->fetch();
        
        if ($om) {
            return $om;
        }
        
        return false;
    }
    
    /**
     * Create a password reset token
     */
    public function createResetToken($email) {
        $user = $this->checkEmailExists($email);
        if (!$user) {
            return false;
        }
        
        // Delete any existing tokens for this email
        $stmt = $this->pdo->prepare("DELETE FROM password_reset_tokens WHERE email = ?");
        $stmt->execute([$email]);
        
        // Generate new token
        $token = $this->generateToken();
        $expires_at = date('Y-m-d H:i:s', strtotime('+1 hour')); // Token expires in 1 hour
        
        // Insert token
        $stmt = $this->pdo->prepare("INSERT INTO password_reset_tokens (email, user_type, token, expires_at) VALUES (?, ?, ?, ?)");
        $result = $stmt->execute([$email, $user['user_type'], $token, $expires_at]);
        
        if ($result) {
            return $token;
        }
        
        return false;
    }
    
    /**
     * Validate reset token
     */
    public function validateToken($token) {
        $stmt = $this->pdo->prepare("SELECT * FROM password_reset_tokens WHERE token = ? AND used = 0 AND expires_at > NOW()");
        $stmt->execute([$token]);
        return $stmt->fetch();
    }
    
    /**
     * Update password using token
     */
    public function updatePassword($token, $new_password) {
        $token_data = $this->validateToken($token);
        if (!$token_data) {
            return false;
        }
        
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        
        // Update password based on user type
        if ($token_data['user_type'] === 'admin') {
            $stmt = $this->pdo->prepare("UPDATE admins SET password = ? WHERE email = ?");
        } else {
            $stmt = $this->pdo->prepare("UPDATE operation_managers SET password = ? WHERE email = ?");
        }
        
        $result = $stmt->execute([$hashed_password, $token_data['email']]);
        
        if ($result) {
            // Mark token as used
            $stmt = $this->pdo->prepare("UPDATE password_reset_tokens SET used = TRUE WHERE token = ?");
            $stmt->execute([$token]);
            return true;
        }
        
        return false;
    }
    
    /**
     * Send password reset email
     */
    public function sendResetEmail($email) {
        $token = $this->createResetToken($email);
        if (!$token) {
            return false;
        }
        
        $reset_link = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . "/reset_password.php?token=" . $token;
        
        $subject = "Password Reset Request - QuickMark";
        $body = "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
            <div style='background: #007bff; color: white; padding: 20px; text-align: center;'>
                <h2>QuickMark</h2>
                <p>Smart Attendance Automation System</p>
            </div>
            
            <div style='padding: 30px; background: #f8f9fa;'>
                <h3>Password Reset Request</h3>
                <p>Hello,</p>
                <p>We received a request to reset your password for your QuickMark account.</p>
                
                <div style='text-align: center; margin: 30px 0;'>
                    <a href='{$reset_link}' 
                       style='background: #007bff; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; display: inline-block;'>
                        Reset Password
                    </a>
                </div>
                
                <p><strong>Or copy this link:</strong></p>
                <p style='word-break: break-all; color: #666;'>{$reset_link}</p>
                
                <div style='background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; margin: 20px 0; border-radius: 5px;'>
                    <p><strong>Important:</strong></p>
                    <ul>
                        <li>This link will expire in 1 hour</li>
                        <li>If you didn't request this reset, please ignore this email</li>
                        <li>For security, this link can only be used once</li>
                    </ul>
                </div>
                
                <p>Best regards,<br>Technical Team,<br> Geeks of Gurukul</p>
            </div>
            
            <div style='background: #343a40; color: white; padding: 20px; text-align: center; font-size: 12px;'>
                <p>This is an automated message. Please do not reply to this email.</p>
            </div>
        </div>";
        
        return Mail::sendEmail($email, 'User', $subject, $body);
    }
    
    /**
     * Clean expired tokens
     */
    public function cleanExpiredTokens() {
        $stmt = $this->pdo->prepare("DELETE FROM password_reset_tokens WHERE expires_at < NOW() OR used = TRUE");
        return $stmt->execute();
    }
}
?> 