# Smart Attendance Automation System - Installation Guide

## Prerequisites

- **Web Server**: Apache/Nginx (XAMPP, WAMP, or similar)
- **PHP**: Version 7.4 or higher
- **MySQL**: Version 5.7 or higher
- **PHP Extensions**: PDO, PDO_MySQL, fileinfo, mbstring

## Installation Steps

### 1. Download and Extract

1. Download the project files
2. Extract to your web server directory (e.g., `htdocs` for XAMPP)
3. Ensure the folder is named `student_attendance_system`

### 2. Database Setup

1. Open your MySQL client (phpMyAdmin, MySQL Workbench, or command line)
2. Create a new database named `attendance_system`
3. Import the database schema:
   ```sql
   -- Run the contents of database/attendance_system.sql
   ```

### 3. Configuration

#### Database Configuration
Edit `config/database.php`:
```php
private $host = 'localhost';        // Your database host
private $db_name = 'attendance_system';  // Database name
private $username = 'root';         // Database username
private $password = '';             // Database password
```

#### Email Configuration (Optional)
Edit `config/mail.php`:
```php
const SMTP_USERNAME = 'your-email@gmail.com';  // Your Gmail address
const SMTP_PASSWORD = 'your-app-password';     // Gmail app password
```

**Note**: For Gmail, you need to:
1. Enable 2-factor authentication
2. Generate an app password
3. Use the app password instead of your regular password

### 4. File Permissions

Ensure the following directories are writable:
```bash
uploads/          # For temporary file uploads
```

### 5. Access the Application

1. Start your web server and MySQL service
2. Open your browser and navigate to:
   ```
   http://localhost/student_attendance_system/
   ```

## Default Login Credentials

### Admin Account
- **Username**: admin
- **Password**: password
- **Email**: admin@attendance.com

**Important**: Change the default admin password after first login!

## Usage Guide

### For Administrators

1. **Login**: Use the admin credentials to access the admin panel
2. **Register OMs**: Add Operation Managers individually or in bulk
3. **Manage OMs**: View, edit, activate/deactivate, or delete OM accounts
4. **Monitor System**: View system statistics and reports

### For Operation Managers (OMs)

1. **Login**: Use credentials provided by admin
2. **Create Sections**: Add new sections for different classes
3. **Upload Students**: Add student lists via CSV or manually
4. **Mark Attendance**: Enter last 3 digits of present students' roll numbers
5. **Export Data**: Copy binary output to Google Sheets

## CSV Templates

### OM Registration Template
Use `templates/om_template.csv` as a reference for bulk OM registration.

### Student Registration Template
Use `templates/student_template.csv` as a reference for student list uploads.

## Features Overview

### Admin Features
- ✅ Register OMs individually
- ✅ Bulk register OMs via CSV
- ✅ Manage OM accounts
- ✅ View system statistics
- ✅ Automated email notifications

### OM Features
- ✅ Create and manage sections
- ✅ Upload student lists via CSV
- ✅ Mark attendance using roll number suffixes
- ✅ Generate Google Sheets compatible binary output
- ✅ View attendance history
- ✅ Profile management

### System Features
- ✅ Secure authentication
- ✅ Role-based access control
- ✅ CSV/Excel file processing
- ✅ Email notifications
- ✅ Responsive design
- ✅ Modern UI/UX

## Troubleshooting

### Common Issues

1. **Database Connection Error**
   - Verify database credentials in `config/database.php`
   - Ensure MySQL service is running
   - Check if database exists

2. **File Upload Issues**
   - Ensure `uploads/` directory is writable
   - Check PHP file upload settings in `php.ini`
   - Verify file size limits

3. **Email Not Working**
   - Configure SMTP settings in `config/mail.php`
   - For Gmail, use app passwords
   - Check if mail server is accessible

4. **Permission Denied**
   - Set proper file permissions for uploads directory
   - Ensure web server has write access

### Error Logs

Check your web server error logs for detailed error messages:
- **XAMPP**: `xampp/apache/logs/error.log`
- **WAMP**: `wamp/logs/apache_error.log`

## Security Considerations

1. **Change Default Passwords**: Update admin password immediately
2. **Secure Database**: Use strong database passwords
3. **HTTPS**: Use SSL certificate in production
4. **File Uploads**: Validate all uploaded files
5. **Input Validation**: All user inputs are sanitized
6. **Session Security**: Sessions are properly managed

## Production Deployment

For production deployment:

1. **Environment**: Use a proper hosting environment
2. **SSL**: Enable HTTPS
3. **Backup**: Set up regular database backups
4. **Monitoring**: Monitor system performance
5. **Updates**: Keep PHP and MySQL updated
6. **Security**: Implement additional security measures

## Support

For technical support or questions:
1. Check the troubleshooting section above
2. Review error logs
3. Ensure all prerequisites are met
4. Verify configuration settings

## License

This project is open source and available under the MIT License. 