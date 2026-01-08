<?php
require_once '../includes/functions.php';
requireAdmin();

$db = getDB();

// Get statistics
$stmt = $db->query("SELECT COUNT(*) as total_oms FROM operation_managers WHERE is_active = 1");
$total_oms = $stmt->fetch()['total_oms'];

$stmt = $db->query("SELECT COUNT(*) as total_sections FROM sections");
$total_sections = $stmt->fetch()['total_sections'];

$stmt = $db->query("SELECT COUNT(*) as total_students FROM students");
$total_students = $stmt->fetch()['total_students'];

$stmt = $db->query("SELECT COUNT(*) as total_attendance FROM attendance");
$total_attendance = $stmt->fetch()['total_attendance'];

// Get recent OMs
$stmt = $db->query("SELECT * FROM operation_managers ORDER BY created_at DESC LIMIT 5");
$recent_oms = $stmt->fetchAll();

$admin_id = $_SESSION['user_id'];
// Fetch admin profile picture
$stmt = $db->prepare("SELECT profile_picture FROM admins WHERE id = ?");
$stmt->execute([$admin_id]);
$admin_profile = $stmt->fetch();
$profile_picture = $admin_profile && $admin_profile['profile_picture'] ? '../uploads/' . htmlspecialchars($admin_profile['profile_picture']) : 'https://via.placeholder.com/40x40.png?text=Admin';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Smart Attendance System</title>
    
    <!-- Favicon and App Icons -->
    <link rel="icon" type="image/x-icon" href="../download.png">
    <link rel="shortcut icon" type="image/x-icon" href="../download.png">
    <link rel="apple-touch-icon" sizes="180x180" href="../download.png">
    <link rel="icon" type="image/png" sizes="32x32" href="../download.png">
    <link rel="icon" type="image/png" sizes="16x16" href="../download.png">
    
    <!-- Web App Manifest -->
    <link rel="manifest" href="../manifest.json">
    <meta name="theme-color" content="#667eea">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="QuickMark">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
    body {
        background-color: #f8f9fa;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    .navbar {
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
        margin-left: 15px;
        margin-top: 70px;
        padding: 30px 24px 30px 24px;
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
            padding: 18px 6px;
        }
    }

    .stat-card {
        background: white;
        border-radius: 15px;
        padding: 25px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        transition: transform 0.3s ease;
    }

    .stat-card:hover {
        transform: translateY(-5px);
    }

    .stat-icon {
        font-size: 2.5rem;
        margin-bottom: 15px;
    }

    .stat-number {
        font-size: 2rem;
        font-weight: bold;
        margin-bottom: 5px;
    }

    .recent-oms {
        background: white;
        border-radius: 15px;
        padding: 25px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    }

    .table th {
        border-top: none;
        font-weight: 600;
        color: #667eea;
    }
    .alert-dismissible {
    padding-right: 3rem;
    margin-top: 76px;
}

    /* Mobile Sidebar Offcanvas */
    /* Default mobile-first */
    .profile-dropdown {
        margin-right: auto;
        padding-left: 60px;
    }

    /* Desktop view: 992px and above (Bootstrap's lg breakpoint) */
    @media (min-width: 992px) {
        .profile-dropdown {
            margin-right: auto;
            margin-left: 0;
        }
    }
    @media (min-width: 992px) {
    .profile-dropdown {
        padding-left: 0;       /* Remove left padding */
        margin-left: auto;     /* Pushes to right side */
        margin-right: 0;       /* Optional: reset margin */
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
        }
    }
    </style>
</head>

<body>
    <nav class="navbar navbar-expand-lg navbar-dark"
        style="background: linear-gradient(45deg, #667eea, #764ba2); position: fixed; top: 0; width: 100%; z-index: 1030; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);">
        <div class="container-fluid d-flex align-items-center justify-content-between flex-wrap">
            <div class="d-flex align-items-center">
                <button class="mobile-sidebar-toggle btn btn-link text-white me-2" type="button"
                    data-bs-toggle="offcanvas" data-bs-target="#mobileSidebar" style="font-size: 1.4rem;">
                    <i class="fas fa-bars"></i>
                </button>
                <a class="navbar-brand d-flex align-items-center" href="#">
                    <img src="images/download.png" alt="Logo"
                        style="height:40px;width:auto;margin-right:10px;border-radius:25%;">
                    <span class="fw-bold text-white">QuickMark</span>
                </a>
            </div>

            <div class="nav-item dropdown d-flex align-items-center profile-dropdown">
                <img src="<?php echo $profile_picture; ?>" alt="Profile" class="rounded-circle me-2"
                    style="width:36px;height:36px;object-fit:cover;">
                <a class="nav-link dropdown-toggle fw-bold text-white" href="#" role="button" data-bs-toggle="dropdown">
                    Admin
                </a>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user"></i> Profile</a></li>
                    <li>
                        <hr class="dropdown-divider">
                    </li>
                    <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                </ul>
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
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-3 col-lg-2 px-0">
                <div class="sidebar">
                    <nav class="nav flex-column">
                        <a class="nav-link active" href="dashboard.php"><i class="fas fa-tachometer-alt"></i>
                            Dashboard</a>
                        <a class="nav-link" href="register_om.php"><i class="fas fa-user-plus"></i> Register OM</a>
                        <a class="nav-link" href="bulk_register.php"><i class="fas fa-upload"></i> Bulk Register</a>
                        <a class="nav-link" href="manage_oms.php"><i class="fas fa-users-cog"></i> Manage OMs</a>
                        <a class="nav-link" href="attendance_report.php"><i class="fas fa-chart-bar"></i> Attendance
                            Report</a>
                        <a class="nav-link" href="assignment_report.php"><i class="fas fa-chart-bar"></i> Assignment
                            Report</a>
                        <a class="nav-link" href="settings.php"><i class="fas fa-cog"></i> Settings</a>
                        <!-- <a class="nav-link" href="profile.php"><i class="fas fa-user"></i> Profile</a> -->
                        <a class="nav-link<?php if ($current_page == 'about.php') echo ' active'; ?>" href="about.php"><i class="fas fa-question-circle"></i> About & Help</a>
                    </nav>
                </div>
            </div>
            <div class="col-md-9 col-lg-10">
                <div class="main-content">
                    <?php $flash = getFlashMessage(); ?>
                    <?php if ($flash): ?>
                    <div class="alert alert-<?php echo $flash['type']; ?> alert-dismissible fade show" role="alert">
                        <?php echo $flash['message']; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php endif; ?>
                    <h2 class="mb-4"><i class="fas fa-tachometer-alt"></i> Admin Dashboard</h2>
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="stat-card text-center">
                                <div class="stat-icon text-primary"><i class="fas fa-users"></i></div>
                                <div class="stat-number text-primary"><?php echo $total_oms; ?></div>
                                <div class="text-muted">Active OMs</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card text-center">
                                <div class="stat-icon text-success"><i class="fas fa-layer-group"></i></div>
                                <div class="stat-number text-success"><?php echo $total_sections; ?></div>
                                <div class="text-muted">Total Sections</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card text-center">
                                <div class="stat-icon text-info"><i class="fas fa-user-graduate"></i></div>
                                <div class="stat-number text-info"><?php echo $total_students; ?></div>
                                <div class="text-muted">Total Students</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card text-center">
                                <div class="stat-icon text-warning"><i class="fas fa-clipboard-list"></i></div>
                                <div class="stat-number text-warning"><?php echo $total_attendance; ?></div>
                                <div class="text-muted">Attendance Records</div>
                            </div>
                        </div>
                    </div>
                    <div class="row mb-4">
                        <div class="col-12 col-md mb-2 mb-md-0">
                            <button class="btn btn-primary w-100" onclick="location.href='register_om.php'">
                                Register New OM
                            </button>
                        </div>
                        <div class="col-12 col-md mb-2 mb-md-0">
                            <button class="btn btn-success w-100" onclick="location.href='bulk_register.php'">
                                Bulk Register OMs
                            </button>
                        </div>
                        <div class="col-12 col-md mb-2 mb-md-0">
                            <button class="btn btn-info w-100" onclick="location.href='manage_oms.php'">
                                Manage OMs
                            </button>
                        </div>
                        <div class="col-12 col-md mb-2 mb-md-0">
                            <button class="btn btn-warning w-100" onclick="location.href='attendance_report.php'">
                                <i class="fas fa-chart-bar"></i> Attendance Report
                            </button>
                        </div>
                        <div class="col-12 col-md">
                            <button class="btn btn-secondary w-100" onclick="location.href='assignment_report.php'">
                                <i class="fas fa-chart-bar"></i> Assignment Report
                            </button>
                        </div>
                    </div>


                    <div class="row">
                        <div class="col-12">
                            <div class="recent-oms">
                                <h5 class="mb-3"><i class="fas fa-clock"></i> Recently Registered OMs</h5>
                                <?php if (empty($recent_oms)): ?>
                                <p class="text-muted">No OMs registered yet.</p>
                                <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Name</th>
                                                <th>Email</th>
                                                <th>College</th>
                                                <th>Status</th>
                                                <th>Registered</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($recent_oms as $om): ?>
                                            <tr>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($om['name']); ?></strong><br>
                                                    <small
                                                        class="text-muted"><?php echo htmlspecialchars($om['designation']); ?></small>
                                                </td>
                                                <td><?php echo htmlspecialchars($om['email']); ?></td>
                                                <td><?php echo htmlspecialchars($om['college']); ?></td>
                                                <td>
                                                    <?php if ($om['is_active']): ?>
                                                    <span class="badge bg-success">Active</span>
                                                    <?php else: ?>
                                                    <span class="badge bg-danger">Inactive</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo formatDate($om['created_at']); ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <footer class="text-center mt-5 mb-3 text-muted small py-3 px-2"
        style="background-color: #f1f1f1; border-top: 1px solid #ddd; border-radius: 8px;">
        &lt;GoG&gt; Smart Attendance Tracker Presented By <strong>Satish Nagar</strong>
    </footer>


    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>