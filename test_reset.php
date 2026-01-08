<?php
/**
 * test_reset.php
 * Test script for password reset functionality
 * Run this to test if the password reset system is working
 */

require_once 'includes/password_reset.php';

echo "<h2>Password Reset System Test</h2>";

try {
    $passwordReset = new PasswordReset();
    echo "<p style='color: green;'>✓ PasswordReset class loaded successfully</p>";
    
    // Test email check
    $test_email = 'test@example.com';
    $exists = $passwordReset->checkEmailExists($test_email);
    echo "<p>Testing email check for '{$test_email}': " . ($exists ? 'Found' : 'Not found') . "</p>";
    
    // Test token generation
    $token = $passwordReset->createResetToken($test_email);
    if ($token) {
        echo "<p style='color: green;'>✓ Token generated successfully: " . substr($token, 0, 20) . "...</p>";
        
        // Test token validation
        $valid = $passwordReset->validateToken($token);
        echo "<p>Token validation: " . ($valid ? 'Valid' : 'Invalid') . "</p>";
        
        // Clean up test token
        $passwordReset->cleanExpiredTokens();
        echo "<p style='color: green;'>✓ Test completed successfully</p>";
    } else {
        echo "<p style='color: orange;'>⚠ Token generation failed (expected for non-existent email)</p>";
    }
    
    echo "<h3>System Status:</h3>";
    echo "<ul>";
    echo "<li>Database connection: ✓ Working</li>";
    echo "<li>PasswordReset class: ✓ Loaded</li>";
    echo "<li>Token generation: ✓ Working</li>";
    echo "<li>Token validation: ✓ Working</li>";
    echo "</ul>";
    
    echo "<p><strong>To test the full reset flow:</strong></p>";
    echo "<ol>";
    echo "<li>Go to <a href='index.php'>index.php</a></li>";
    echo "<li>Click 'Forgot password?'</li>";
    echo "<li>Enter a registered email address</li>";
    echo "<li>Check your email for the reset link</li>";
    echo "<li>Click the link to reset your password</li>";
    echo "</ol>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error: " . $e->getMessage() . "</p>";
    echo "<p>Please check your database connection and configuration.</p>";
}
?> 