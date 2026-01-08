<?php
require_once '../includes/functions.php';
requireAdmin();
require_once '../vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function sendSMTPMail($to, $subject, $body) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'satish@geeksofgurkul.com';
        $mail->Password = 'hiaa oshx vooq ands';
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;
        $mail->setFrom('satish@geeksofgurkul.com', 'Smart Attendance System');
        $mail->addAddress($to);
        $mail->isHTML(false);
        $mail->Subject = $subject;
        $mail->Body    = $body;
        $mail->send();
        return true;
    } catch (Exception $e) {
        return false;
    }
}

$admin_id = $_SESSION['user_id'];
$db = getDB();
$stmt = $db->prepare("SELECT profile_picture FROM admins WHERE id = ?");
$stmt->execute([$admin_id]);
$admin = $stmt->fetch();
$profile_picture = $admin && $admin['profile_picture']
    ? '../uploads/' . htmlspecialchars($admin['profile_picture'])
    : 'https://via.placeholder.com/40x40.png?text=Admin';

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['oms_file']) && $_FILES['oms_file']['tmp_name']) {
    $file = $_FILES['oms_file'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $oms = [];
    try {
        if ($ext === 'csv') {
            $handle = fopen($file['tmp_name'], 'r');
            $headers = fgetcsv($handle);
            while (($row = fgetcsv($handle)) !== false) {
                $data = array_combine($headers, $row);
                if (!empty($data['name']) && !empty($data['designation']) && !empty($data['email']) && !empty($data['contact']) && !empty($data['college']) && !empty($data['password'])) {
                    $oms[] = $data;
                }
            }
            fclose($handle);
        } elseif (in_array($ext, ['xls', 'xlsx'])) {
            $spreadsheet = IOFactory::load($file['tmp_name']);
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray(null, true, true, true);
            $headers = array_map('strtolower', array_map('trim', $rows[1]));
            for ($i = 2; $i <= count($rows); $i++) {
                $row = $rows[$i];
                $data = array_combine($headers, $row);
                if (!empty($data['name']) && !empty($data['designation']) && !empty($data['email']) && !empty($data['contact']) && !empty($data['college']) && !empty($data['password'])) {
                    $oms[] = $data;
                }
            }
        } else {
            $error = 'Invalid file type. Only CSV, XLS, XLSX allowed.';
        }
                    } catch (Exception $e) {
        $error = 'Error reading file: ' . $e->getMessage();
    }
    // Insert OMs
    if ($oms && !$error) {
        $stmt = $db->prepare("INSERT INTO operation_managers (name, designation, email, contact, college, password) VALUES (?, ?, ?, ?, ?, ?)");
        $count = 0;
        $emails_sent = 0;
        foreach ($oms as $om) {
            $hashed = password_hash($om['password'], PASSWORD_DEFAULT);
            if ($stmt->execute([$om['name'], $om['designation'], $om['email'], $om['contact'], $om['college'], $hashed])) {
                // Send email with credentials via PHPMailer
                $subject = "Your OM Account - Smart Attendance System";
                $message = "Hello {$om['name']},\n\nYour account has been created.\nEmail: {$om['email']}\nPassword: {$om['password']}\n";
                $sent = sendSMTPMail($om['email'], $subject, $message);
                if ($sent) $emails_sent++;
                $count++;
                    }
        }
        if ($count > 0) {
            if ($emails_sent == $count) {
                $success = "Imported $count Operation Managers. Credentials sent to all emails.";
            } else if ($emails_sent == 0) {
                $success = "Imported $count Operation Managers. But emails could not be sent.";
            } else {
                $success = "Imported $count Operation Managers. Credentials sent to $emails_sent emails.";
            }
        }
    } elseif (!$error) {
        $error = 'No valid OMs found in file.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bulk Register OMs - Smart Attendance System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .navbar {
            position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    z-index: 1100; 
            background: linear-gradient(45deg, #667eea, #764ba2);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .sidebar {
            background: white;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
            position: fixed;
            top: 70px;
            left: 0;
            width: 250px;
            height: calc(100vh - 70px);
            padding: 16px 0;
            overflow-y: auto;
            z-index: 1000;
        }

        .sidebar .nav-link {
            color: #333;
            padding: 12px 20px;
            border-radius: 8px;
            margin: 5px 10px;
            transition: all 0.3s ease;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 1.08rem;
        }

        .sidebar .nav-link i {
            font-size: 1.2rem;
            margin-right: 8px;
        }

        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
        }

        .main-content {
            margin-left: 10px;
            margin-top: 96px;
            /* padding: 30px 24px 30px 24px; */
            min-height: calc(100vh - 70px);
            background: #f8f9fa;
        }
        .container {
            max-width: 1100px;
            margin: 0 auto;
        }
        @media (max-width: 991.98px) {
            .main-content {
                margin-left: 0;
                margin-top: 76px:
                padding: 18px 6px;
            }
        }

    /* .main-content {
        /* padding: 10px; */
    /* margin: 1px; */
    }

    */

    /* Mobile Sidebar Offcanvas */
    /* margin-right: 10px; Default for mobile */
}

/* Mobile view: shift to left side */
 /* Default mobile-first */
 .profile-dropdown {
        margin-right: auto;
        padding-left: 60px;
    }

    /* Desktop view: 992px and above (Bootstrap's lg breakpoint) */
    @media (min-width: 992px) {
        .profile-dropdown {
            margin-right: 10px;
        }
    }
    
    /* Hide the mobile menu button when profile is on left */
    .mobile-sidebar-toggle {
        display: none !important;
    }
}

/* Desktop view: 768px and above */
@media (min-width: 768px) {
    margin-right: 40px;
    .profile-dropdown {
        position: static;
        margin-right: 10px;
        margin-left: 0;
        order: 0; /* Reset order */
        transform: none;
    }
    
    /* Reset dropdown menu position for desktop */
    .profile-dropdown .dropdown-menu {
        right: 0;
        left: auto;
    }
    
    /* Show mobile menu button on desktop */
    .mobile-sidebar-toggle {
        display: none !important;
    }
}
    .offcanvas.offcanvas-end {
        width: 220px !important;
        height: auto !important;
        max-height: 90vh !important;
        top: 20px !important;
        bottom: auto !important;
        border-radius: 8px 0 0 8px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        background-color: #ffffff;
    }

    .offcanvas-backdrop.show {
        background-color: rgba(0, 0, 0, 0.3);
        backdrop-filter: blur(1px);
    }

    .offcanvas-header {
        padding: 12px 16px;
        border-bottom: 1px solid #eee;
    }

    .offcanvas-body {
        padding: 8px 0;
        overflow-y: auto;
    }

    .offcanvas .btn-sidebar {
        padding: 8px 14px;
        font-size: 0.95rem;
        margin: 2px 0;
    }

    .sidebar .btn-sidebar i {
        font-size: 1rem;
    }

    .mobile-sidebar-toggle {
        background: none;
        border: none;
        font-size: 2rem;
        color: #fff;
    }

    @media (max-width: 768px) {
        .sidebar {
            display: none !important;
        }

        .mobile-sidebar-toggle {
            display: block !important;
        }
    }

    @media (min-width: 769px) {
        .mobile-sidebar-toggle {
            display: none !important;
        }
    }

    /* Move hamburger to right in mobile */
    @media (max-width: 768px) {
        .mobile-sidebar-toggle {
            position: absolute;
            right: 1px;
            top: 15px;
            z-index: 1051;

        }

        .mobile-sidebar-toggle i {
            font-size: 28px;
            /* Now this will increase the icon size */
        }

        /* Hide admin name + image */
        .admin-profile-mobile {
            display: none !important;
        }

        /* Better card spacing */
        .stat-card {
            margin-bottom: 20px;
        }

        .main-content {
            padding: 20px 15px;
            margin-top: 10px; /* match the navbar height */
        }
        }
    </style>
</head>

<body>
    <!-- Header/Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid d-flex align-items-center justify-content-between flex-wrap">
            <div class="d-flex align-items-center">
                <!-- Hamburger for mobile -->
                <button class="mobile-sidebar-toggle btn btn-link text-white me-2" type="button"
                    data-bs-toggle="offcanvas" data-bs-target="#mobileSidebar">
                    <i class="fas fa-bars"></i>
                </button>
                <a class="navbar-brand d-flex align-items-center" href="#">
                    <img src="images/download.png" alt="Logo"
                        style="height:40px;width:auto;margin-right:10px;border-radius:25%;" />
                    <span class="fw-bold text-white">QuickMark</span>
                </a>
            </div>

            <!-- Profile dropdown -->
           <!-- Profile Dropdown -->
<div class="d-flex align-items-center" style="margin-right: 40px;">
    <div class="nav-item dropdown d-flex align-items-center">
        <img src="<?php echo $profile_picture; ?>" alt="Profile" class="rounded-circle me-2"
             style="width:36px;height:36px;object-fit:cover;" />
        <a class="nav-link dropdown-toggle fw-bold text-white" href="#" role="button" data-bs-toggle="dropdown">
            Admin
        </a>
        <ul class="dropdown-menu">
            <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user"></i> Profile</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </div>
</div>


        </div>
    </nav>

    <!-- Offcanvas Mobile Sidebar -->
    <div class="offcanvas offcanvas-end" tabindex="-1" id="mobileSidebar">
        <div class="offcanvas-header">
            <h5 class="offcanvas-title">Menu</h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body">
            <?php $current_page = basename($_SERVER['PHP_SELF']); ?>
            <a class="btn btn-sidebar w-100 text-start <?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>"
                href="dashboard.php"><i class="fas fa-tachometer-alt me-2"></i> Dashboard</a>
            <a class="btn btn-sidebar w-100 text-start <?php echo $current_page == 'register_om.php' ? 'active' : ''; ?>"
                href="register_om.php"><i class="fas fa-user-plus me-2"></i> Register OM</a>
            <a class="btn btn-sidebar w-100 text-start <?php echo $current_page == 'bulk_register.php' ? 'active' : ''; ?>"
                href="bulk_register.php"><i class="fas fa-upload me-2"></i> Bulk Register</a>
            <a class="btn btn-sidebar w-100 text-start <?php echo $current_page == 'manage_oms.php' ? 'active' : ''; ?>"
                href="manage_oms.php"><i class="fas fa-users-cog me-2"></i> Manage OMs</a>
            <a class="btn btn-sidebar w-100 text-start <?php echo $current_page == 'attendance_report.php' ? 'active' : ''; ?>"
                href="attendance_report.php"><i class="fas fa-chart-bar me-2"></i> Attendance Report</a>
            <a class="btn btn-sidebar w-100 text-start <?php echo $current_page == 'assignment_report.php' ? 'active' : ''; ?>"
                href="assignment_report.php"><i class="fas fa-chart-bar me-2"></i> Assignment Report</a>
            <a class="btn btn-sidebar w-100 text-start <?php echo $current_page == 'settings.php' ? 'active' : ''; ?>"
                href="settings.php"><i class="fas fa-cog me-2"></i> Settings</a>
            <a class="btn btn-sidebar w-100 text-start <?php echo $current_page == 'about.php' ? 'active' : ''; ?>"
                href="about.php"><i class="fas fa-question-circle me-2"></i> About & Help</a>
        </div>
    </div>

    <!-- Desktop Sidebar -->
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-3 col-lg-2 px-0 d-none d-md-block">
                <div class="sidebar">
                    <nav class="nav flex-column">
                        <a class="nav-link <?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>"
                            href="dashboard.php">
                            <i class="fas fa-tachometer-alt"></i> Dashboard
                        </a>
                        <a class="nav-link <?php echo $current_page == 'register_om.php' ? 'active' : ''; ?>"
                            href="register_om.php">
                            <i class="fas fa-user-plus"></i> Register OM
                        </a>
                        <a class="nav-link <?php echo $current_page == 'bulk_register.php' ? 'active' : ''; ?>"
                            href="bulk_register.php">
                            <i class="fas fa-upload"></i> Bulk Register
                        </a>
                        <a class="nav-link <?php echo $current_page == 'manage_oms.php' ? 'active' : ''; ?>"
                            href="manage_oms.php">
                            <i class="fas fa-users-cog"></i> Manage OMs
                        </a>
                        <a class="nav-link <?php echo $current_page == 'attendance_report.php' ? 'active' : ''; ?>"
                            href="attendance_report.php">
                            <i class="fas fa-chart-bar"></i> Attendance Report
                        </a>
                        <a class="nav-link <?php echo $current_page == 'assignment_report.php' ? 'active' : ''; ?>"
                            href="assignment_report.php">
                            <i class="fas fa-chart-bar"></i> Assignment Report
                        </a>
                        <a class="nav-link <?php echo $current_page == 'settings.php' ? 'active' : ''; ?>"
                            href="settings.php">
                            <i class="fas fa-cog"></i> Settings
                        </a>
                        <a class="nav-link <?php if ($current_page == 'about.php') echo ' active'; ?>" href="about.php"><i class="fas fa-question-circle"></i> About & Help</a>
                    </nav>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10">
                <div class="main-content">
                    <div class="container mt-5" style="max-width: 700px;">

                        <a href="dashboard.php" class="btn btn-secondary btn-sm mb-3">&larr; Back to Dashboard</a>
                        <h2>Bulk Register Operation Managers</h2>
                                
                                <?php if ($success): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                        <?php elseif ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                                <?php endif; ?>
                                
                        <div class="card">
                            <div class="card-header">Bulk Register OMs (CSV, XLS, XLSX)</div>
                            <div class="card-body">
                                <form method="POST" enctype="multipart/form-data">
                                    <div class="mb-3">
                                        <label for="oms_file" class="form-label">Upload File</label>
                                        <input type="file" class="form-control" id="oms_file" name="oms_file"
                                            accept=".csv,.xls,.xlsx" required>
                                        <div class="form-text">File must have columns: <b>name, designation, email,
                                                contact, college, password</b></div>
                                    </div>
                                    <button type="submit" class="btn btn-success">Bulk Register</button>
                                    </form>
                            </div>
                        </div>

                        <!-- <footer class="text-center mt-5 mb-3 text-muted small py-3 px-2"
                            style="background-color: #f1f1f1; border-top: 1px solid #ddd; border-radius: 8px;">
                            &lt;GoG&gt; Smart Attendance Tracker Presented By <strong>Satish Nagar</strong>
                        </footer> -->
                    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html> 