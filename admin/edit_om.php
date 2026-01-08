<?php
require_once '../includes/functions.php';
requireAdmin();

$db = getDB();
$om_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$om_id) {
    header('Location: manage_oms.php');
    exit;
}

// Fetch OM details
$stmt = $db->prepare('SELECT * FROM operation_managers WHERE id = ?');
$stmt->execute([$om_id]);
$om = $stmt->fetch();
if (!$om) {
    header('Location: manage_oms.php');
    exit;
}

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitizeInput($_POST['name'] ?? '');
    $designation = sanitizeInput($_POST['designation'] ?? '');
    $contact = sanitizeInput($_POST['contact'] ?? '');
    $college = sanitizeInput($_POST['college'] ?? '');
    $profile_picture = $om['profile_picture'] ?? null;
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['tmp_name']) {
        $upload = uploadFile($_FILES['profile_picture'], '../uploads', ['jpg', 'jpeg', 'png']);
        if ($upload) {
            $profile_picture = $upload;
        } else {
            $error = 'Invalid profile picture.';
        }
    }
    if (!$error) {
        $stmt = $db->prepare('UPDATE operation_managers SET name=?, designation=?, contact=?, college=?, profile_picture=? WHERE id=?');
        if ($stmt->execute([$name, $designation, $contact, $college, $profile_picture, $om_id])) {
            $success = 'Operation Manager updated successfully!';
            // Refresh OM data
            $stmt = $db->prepare('SELECT * FROM operation_managers WHERE id = ?');
            $stmt->execute([$om_id]);
            $om = $stmt->fetch();
        } else {
            $error = 'Failed to update Operation Manager.';
        }
    }
}
$profile_picture_url = !empty($om['profile_picture']) ? '../uploads/' . $om['profile_picture'] : 'https://via.placeholder.com/120x120.png?text=OM';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Operation Manager - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .main-content { padding: 30px; margin-top: 70px; }
        .card { max-width: 600px; margin: 0 auto; }
        /* Mobile Sidebar Offcanvas */
        /* Default mobile-first */
        .profile-dropdown {
            margin-right: auto;
            padding-left: 80px;
        }

        /* Desktop view: 992px and above (Bootstrap's lg breakpoint) */
        @media (min-width: 992px) {
            .profile-dropdown {
                margin-right: 10px;
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
            margin-left: 250px;
            margin-top: 70px;
            padding: 30px;
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
<nav class="navbar navbar-expand-lg navbar-dark" style="background: linear-gradient(45deg, #667eea, #764ba2); position: fixed; top: 0; width: 100%; z-index: 1030; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);">
    <div class="container-fluid d-flex align-items-center justify-content-between flex-wrap">
        <div class="d-flex align-items-center">
            <button class="mobile-sidebar-toggle btn btn-link text-white me-2" type="button"
                data-bs-toggle="offcanvas" data-bs-target="#mobileSidebar" style="font-size: 1.4rem;">
                <i class="fas fa-bars"></i>
            </button>
            <a class="navbar-brand d-flex align-items-center" href="dashboard.php">
                <img src="images/download.png" alt="Logo" style="height:40px;width:auto;margin-right:10px;border-radius:25%;">
                <span class="fw-bold text-white">QuickMark</span>
            </a>
        </div>
        <div class="nav-item dropdown d-flex align-items-center profile-dropdown">
            <?php
            $admin_id = $_SESSION['user_id'];
            $stmt = $db->prepare('SELECT * FROM admins WHERE id = ?');
            $stmt->execute([$admin_id]);
            $admin = $stmt->fetch();
            $admin_profile_picture = !empty($admin['profile_picture']) ? '../uploads/' . $admin['profile_picture'] : 'https://via.placeholder.com/120x120.png?text=Admin';
            ?>
            <img src="<?php echo $admin_profile_picture; ?>" alt="Profile" class="rounded-circle me-2" style="width:36px;height:36px;object-fit:cover;">
            <a class="nav-link dropdown-toggle fw-bold text-white" href="#" role="button" data-bs-toggle="dropdown">Admin</a>
            <ul class="dropdown-menu">
                <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user"></i> Profile</a></li>
                <li><hr class="dropdown-divider"></li>
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
        <a class="btn btn-sidebar w-100 text-start <?php echo $current_page == 'profile.php' ? 'active' : ''; ?>"
            href="profile.php"><i class="fas fa-user me-2"></i> Profile</a>
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
                    <a class="nav-link <?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>" href="dashboard.php">
                        <i class="fas fa-tachometer-alt"></i> Dashboard
                    </a>
                    <a class="nav-link <?php echo $current_page == 'register_om.php' ? 'active' : ''; ?>" href="register_om.php">
                        <i class="fas fa-user-plus"></i> Register OM
                    </a>
                    <a class="nav-link <?php echo $current_page == 'bulk_register.php' ? 'active' : ''; ?>" href="bulk_register.php">
                        <i class="fas fa-upload"></i> Bulk Register
                    </a>
                    <a class="nav-link <?php echo $current_page == 'manage_oms.php' ? 'active' : ''; ?>" href="manage_oms.php">
                        <i class="fas fa-users-cog"></i> Manage OMs
                    </a>
                    <a class="nav-link <?php echo $current_page == 'attendance_report.php' ? 'active' : ''; ?>" href="attendance_report.php">
                        <i class="fas fa-chart-bar"></i> Attendance Report
                    </a>
                    <a class="nav-link <?php echo $current_page == 'assignment_report.php' ? 'active' : ''; ?>" href="assignment_report.php">
                        <i class="fas fa-chart-bar"></i> Assignment Report
                    </a>
                    <a class="nav-link <?php echo $current_page == 'settings.php' ? 'active' : ''; ?>" href="settings.php">
                        <i class="fas fa-cog"></i> Settings
                    </a>
                    <a class="nav-link <?php echo $current_page == 'profile.php' ? 'active' : ''; ?>" href="profile.php">
                        <i class="fas fa-user"></i> Profile
                    </a>
                    <a class="nav-link <?php echo $current_page == 'about.php' ? 'active' : ''; ?>" href="about.php">
                        <i class="fas fa-question-circle"></i> About & Help
                    </a>
                </nav>
            </div>
        </div>

        <!-- Main Content -->
        <div class="col-md-9 col-lg-10">
            <div class="main-content">
                <div class="container">
    <a href="manage_oms.php" class="btn btn-secondary btn-sm mb-3">&larr; Back to Manage OMs</a>
    <div class="card">
        <div class="card-header">Edit Operation Manager</div>
        <div class="card-body">
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php elseif ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            <form method="POST" enctype="multipart/form-data">
                <div class="mb-3 text-center">
                    <img src="<?php echo $profile_picture_url; ?>" class="img-thumbnail mb-2" style="max-width:120px;">
                    <input type="file" name="profile_picture" class="form-control form-control-sm mt-2">
                </div>
                <div class="mb-3">
                    <label class="form-label">Name</label>
                    <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($om['name']); ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Designation</label>
                    <input type="text" name="designation" class="form-control" value="<?php echo htmlspecialchars($om['designation']); ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <input type="email" class="form-control" value="<?php echo htmlspecialchars($om['email']); ?>" readonly>
                </div>
                <div class="mb-3">
                    <label class="form-label">Contact</label>
                    <input type="text" name="contact" class="form-control" value="<?php echo htmlspecialchars($om['contact']); ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">College</label>
                    <input type="text" name="college" class="form-control" value="<?php echo htmlspecialchars($om['college']); ?>" required>
                </div>
                <button type="submit" class="btn btn-primary">Update OM</button>
            </form>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 