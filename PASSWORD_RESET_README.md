# Password Reset System - QuickMark

## Overview
A complete password reset system has been implemented for the QuickMark Smart Attendance Automation System. This system allows both Admin and Operation Manager users to reset their passwords securely via email.

## Features

### 🔐 **Secure Token-Based Reset**
- Generates cryptographically secure random tokens
- Tokens expire after 1 hour for security
- One-time use tokens (automatically invalidated after use)
- Email verification required

### 📧 **Professional Email Templates**
- Beautiful HTML email design with QuickMark branding
- Clear instructions and security warnings
- Mobile-responsive email layout
- Includes both button and direct link options

### 🛡️ **Security Features**
- Password strength validation
- Secure password hashing using PHP's `password_hash()`
- Case-insensitive email matching
- No information disclosure about email existence
- Automatic cleanup of expired tokens

### 🎨 **User-Friendly Interface**
- Animated, professional reset password page
- Password strength indicator
- Show/hide password toggles
- Responsive design for all devices
- Clear error and success messages

## Files Created/Modified

### New Files:
1. **`includes/password_reset.php`** - Core password reset utility class
2. **`reset_password.php`** - Password reset page for setting new passwords
3. **`setup_reset_table.php`** - Database setup script
4. **`test_reset.php`** - Testing script for the reset system
5. **`database/add_reset_table.sql`** - SQL script for database changes

### Modified Files:
1. **`index.php`** - Updated with complete login and reset logic
2. **`database/attendance_system.sql`** - Added password_reset_tokens table

## Database Changes

### New Table: `password_reset_tokens`
```sql
CREATE TABLE password_reset_tokens (
    id INT PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(100) NOT NULL,
    user_type ENUM('admin', 'om') NOT NULL,
    token VARCHAR(255) UNIQUE NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    used BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

### Modified Table: `admins`
- Added `profile_picture VARCHAR(255) DEFAULT NULL` column

## Setup Instructions

### 1. Database Setup
Run the setup script to create the required database table:
```
http://your-domain/setup_reset_table.php
```

### 2. Test the System
Run the test script to verify everything is working:
```
http://your-domain/test_reset.php
```

### 3. Email Configuration
Ensure your email settings in `config/mail.php` are correct:
- SMTP host, port, and credentials
- From email and name
- App password for Gmail (if using Gmail SMTP)

## How It Works

### 1. **Request Password Reset**
- User clicks "Forgot password?" on login page
- Enters their email address
- System checks if email exists in either admin or OM table
- If found, generates secure token and sends email
- If not found, shows generic success message (security)

### 2. **Email Delivery**
- Professional HTML email sent with reset link
- Link contains secure token as parameter
- Email includes security warnings and instructions

### 3. **Password Reset**
- User clicks link in email
- System validates token (checks expiry and usage)
- User enters new password with confirmation
- Password strength is validated
- New password is hashed and stored
- Token is marked as used

### 4. **Login**
- User can now login with new password
- System automatically redirects to appropriate dashboard

## Security Considerations

### ✅ **Implemented Security Measures**
- Secure token generation using `random_bytes()`
- Token expiration (1 hour)
- One-time use tokens
- Password strength validation
- Secure password hashing
- No email existence disclosure
- Input sanitization and validation

### 🔒 **Additional Recommendations**
- Use HTTPS in production
- Implement rate limiting for reset requests
- Monitor failed reset attempts
- Regular token cleanup
- Consider CAPTCHA for multiple failed attempts

## Testing

### Manual Testing Steps:
1. Go to `index.php`
2. Click "Forgot password?"
3. Enter a registered email address
4. Check email for reset link
5. Click the link and set new password
6. Try logging in with new password

### Automated Testing:
Run `test_reset.php` to verify:
- Database connection
- Token generation
- Token validation
- Email checking functionality

## Troubleshooting

### Common Issues:

1. **Email not received**
   - Check spam folder
   - Verify SMTP settings in `config/mail.php`
   - Check server logs for email errors

2. **Token expired**
   - Request new reset link
   - Tokens expire after 1 hour

3. **Database errors**
   - Run `setup_reset_table.php` to create required table
   - Check database connection settings

4. **Password reset fails**
   - Ensure password meets minimum requirements (6+ characters)
   - Check that passwords match in confirmation field

## API Reference

### PasswordReset Class Methods:

```php
// Check if email exists
$exists = $passwordReset->checkEmailExists($email);

// Create reset token
$token = $passwordReset->createResetToken($email);

// Validate token
$valid = $passwordReset->validateToken($token);

// Update password
$success = $passwordReset->updatePassword($token, $new_password);

// Send reset email
$sent = $passwordReset->sendResetEmail($email);

// Clean expired tokens
$passwordReset->cleanExpiredTokens();
```

## Support

For issues or questions about the password reset system:
- Check the troubleshooting section above
- Review server error logs
- Test with `test_reset.php`
- Contact: satish@geeksofgurukul.com

---

**QuickMark Password Reset System** - Secure, user-friendly, and professional password recovery for your attendance management system. 