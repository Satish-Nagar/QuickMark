<?php
require_once '../includes/functions.php';
requireOM();

$db = getDB();
$om_id = $_SESSION['user_id'];
// Fetch OM profile picture
$stmt = $db->prepare("SELECT profile_picture FROM operation_managers WHERE id = ?");
$stmt->execute([$om_id]);
$om_profile = $stmt->fetch();
$profile_picture = $om_profile && $om_profile['profile_picture'] ? '../uploads/' . htmlspecialchars($om_profile['profile_picture']) : 'https://via.placeholder.com/40x40.png?text=OM';

// Get OM's sections
$stmt = $db->prepare("SELECT s.*, 
                             (SELECT COUNT(*) FROM students WHERE section_id = s.id) as student_count,
                             (SELECT COUNT(*) FROM attendance WHERE section_id = s.id) as attendance_count
                      FROM sections s 
                      WHERE s.om_id = ? 
                      ORDER BY s.created_at DESC");
$stmt->execute([$om_id]);
$sections = $stmt->fetchAll();

// Get recent attendance records
$stmt = $db->prepare("SELECT a.*, s.name as section_name 
                      FROM attendance a 
                      JOIN sections s ON a.section_id = s.id 
                      WHERE s.om_id = ? 
                      ORDER BY a.date DESC 
                      LIMIT 5");
$stmt->execute([$om_id]);
$recent_attendance = $stmt->fetchAll();

// Get total statistics
$stmt = $db->prepare("SELECT 
                        COUNT(DISTINCT s.id) as total_sections,
                        COUNT(DISTINCT st.id) as total_students,
                        COUNT(DISTINCT a.id) as total_attendance
                      FROM sections s 
                      LEFT JOIN students st ON s.id = st.section_id 
                      LEFT JOIN attendance a ON s.id = a.section_id 
                      WHERE s.om_id = ?");
$stmt->execute([$om_id]);
$stats = $stmt->fetch();

$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>OM Dashboard - Smart Attendance System</title>
    
    <!-- Favicon and App Icons -->
    <link rel="icon" type="image/x-icon" href="../download.png">
    <link rel="shortcut icon" type="image/x-icon" href="../download.png">
    <link rel="apple-touch-icon" sizes="180x180" href="../download.png">
    <link rel="icon" type="image/png" sizes="32x32" href="../download.png">
    <link rel="icon" type="image/png" sizes="16x16" href="../download.png">
    
    <!-- Web App Manifest -->
    <link rel="manifest" href="../manifest.json">
    <meta name="theme-color" content="#28a745">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="QuickMark">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet" />
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        /* Header Bar */
        .om-header-bar {
            background: linear-gradient(90deg, #28a745 0%, #20c997 100%);
            color: white;
            padding: 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: fixed;
            top: 0;
            width: 100%;
            z-index: 1030;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        .om-header-bar .header-title {
            font-weight: bold;
            font-size: 1.4rem;
            display: flex;
            align-items: center;
        }
        .om-header-bar img {
            height: 40px;
            width: auto;
            margin-right: 10px;
            border-radius: 25%;
        }
        .profile-dropdown .dropdown-toggle {
            color: white;
            /* background: transparent; */  
            border: none;
        }
        
        /* Profile image styling for PNG transparency */
        .profile-dropdown img[src*=".png"] {
            background-color: white;
        }
        /* Sidebar */
        .sidebar {
            background: white;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
            min-height: 100vh;
            padding: 16px 10px;
            border-right: none;
            margin-top: 70;
            width: 100%;
        }
        .btn-sidebar {
            color: #333;
            padding: 10px 10px;
            border-radius: 8px;
            margin: 1px 0;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 10px;
            border: none;
            background: none;
            width: 100%;
            text-align: left;
            transition: all 0.3s ease;
            justify-content: flex-start;
        }
        .btn-sidebar.active, .btn-sidebar:hover {
            background: linear-gradient(90deg, #28a745 0%, #20c997 100%);
            color: white;
        }
        /* Main Content */
        .main-content-fixed {
            padding: 100px 30px 30px 30px;
        }
        .main-content {
            padding: 0;
        }
        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
            margin-bottom: 20px;
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
        .overview-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            padding: 25px;
            margin-bottom: 24px;
        }
        .section-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            padding: 24px 18px 18px 18px;
            margin-bottom: 24px;
            transition: box-shadow 0.2s, transform 0.2s;
            min-height: 210px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .section-card:hover {
            box-shadow: 0 8px 24px rgba(102, 126, 234, 0.18);
            transform: translateY(-4px) scale(1.01);
        }
        .section-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 8px;
        }
        .section-header h5 {
            font-size: 1.15rem;
            font-weight: 600;
            margin-bottom: 0;
        }
        .section-stats {
            display: flex;
            gap: 24px;
            margin-bottom: 10px;
        }
        .section-stat {
            text-align: center;
        }
        .section-stat .fw-bold {
            font-size: 1.1rem;
        }
        .section-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 12px;
        }
        /* Offcanvas Sidebar */
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
            width: 100%;
            margin: 1px 10px;
            padding: 10px 12px;
            box-sizing: border-box;
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
            .mobile-sidebar-toggle {
                display: block !important;
                position: absolute;
                right: 1px;
                top: 15px;
            }
            .main-content-fixed {
                padding: 50px 15px 15px 15px;
            }
            .main-content {
                padding: 0px 15px;
                margin-top: 70px;
            }
        }
        @media (min-width: 769px) {
            .mobile-sidebar-toggle {
                display: none !important;
            }
        }
        @media (max-width: 991.98px) {
            .sidebar-col {
                display: none !important;
            }
            .profile-dropdown .dropdown-toggle::after {
                margin-right: 30px !important;
            }
        }
        @media (min-width: 992px) {
            .sidebar-col {
                display: block !important;
            }
            .sidebar {
                position: fixed;
                top: 70px;
                left: 0;
                height: calc(100vh - 70px);
                width: 220px;
                overflow-y: auto;
                margin-top: 0;
                z-index: 1020;
            }
            .main-content-fixed {
                margin-left: 220px;
                padding: 100px 30px 30px 30px;
            }
        }
        @media (min-width: 992px) {
            .offcanvas {
                display: none !important;
            }
        }
        footer {
            background-color: #f1f1f1;
            border-top: 1px solid #ddd;
            border-radius: 8px;
            padding: 16px 0 8px 0;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="om-header-bar d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center">
            <div class="header-title">
                <img src="images/download.png" alt="Logo" style="height: 40px; width: auto; margin-right: 10px; border-radius: 25%;" />
                QuickMark
            </div>
        </div>
        <!-- Mobile: user + menu together, right-aligned -->
        <div class="d-flex align-items-center ms-auto me-2 d-lg-none">
            <div class="nav-item dropdown d-flex align-items-center profile-dropdown">
                <img src="<?php echo $profile_picture; ?>" alt="Profile" class="rounded-circle" style="width:36px;height:36px;margin-right: -10px;">
                <a class="nav-link dropdown-toggle fw-bold text-white ms-3" href="#" role="button" data-bs-toggle="dropdown">
                    <span class="d-none d-lg-inline"><?php echo htmlspecialchars($_SESSION['username'] ?? 'OM'); ?></span>
                    </a>
                    <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user"></i> Profile</a></li>
                        <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <a class="dropdown-item" href="#" id="themeToggle">
                            <i class="fas fa-moon me-2"></i> <span id="themeToggleText">Dark Mode</span>
                        </a>
                    </li>
                    </ul>
                </div>
            <div class="menu-card">
                <button class="mobile-sidebar-toggle btn btn-link text-white" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileSidebar" aria-controls="mobileSidebar">
                    <i class="fa fa-bars"></i>
                </button>
            </div>
        </div>
        <!-- Desktop Profile Dropdown -->
        <div class="nav-item dropdown d-flex align-items-center profile-dropdown d-none d-lg-flex me-4">
            <img src="<?php echo $profile_picture; ?>" alt="Profile" class="rounded-circle me-2" style="width:36px;height:36px; margin-right: 16px;">
            <a class="nav-link dropdown-toggle fw-bold text-white" href="#" role="button" data-bs-toggle="dropdown">
                <span class="d-none d-lg-inline"><?php echo htmlspecialchars($_SESSION['username'] ?? 'OM'); ?></span>
            </a>
            <ul class="dropdown-menu">
                <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user"></i> Profile</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                <li><hr class="dropdown-divider"></li>
                <li>
                    <a class="dropdown-item" href="#" id="themeToggleDesktop">
                        <i class="fas fa-moon me-2"></i> <span id="themeToggleTextDesktop">Dark Mode</span>
                    </a>
                </li>
            </ul>
        </div>
    </div>
    <!-- Offcanvas Mobile Sidebar -->
    <div class="offcanvas offcanvas-end" tabindex="-1" id="mobileSidebar">
        <div class="offcanvas-header">
            <h5 class="offcanvas-title">Menu</h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body">
            <?php $current_page = basename($_SERVER['PHP_SELF']); ?>
            <a class="btn btn-sidebar w-100 text-start <?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>" href="dashboard.php"><i class="fas fa-tachometer-alt me-2"></i> Dashboard</a>
            <a class="btn btn-sidebar w-100 text-start <?php echo $current_page == 'sections.php' ? 'active' : ''; ?>" href="sections.php"><i class="fas fa-layer-group me-2"></i> Sections</a>
            <a class="btn btn-sidebar w-100 text-start <?php echo $current_page == 'add_section.php' ? 'active' : ''; ?>" href="add_section.php"><i class="fas fa-plus me-2"></i> Add Section</a>
            <!-- <a class="btn btn-sidebar w-100 text-start <?php echo $current_page == 'mark_attendance.php' ? 'active' : ''; ?>" href="mark_attendance.php"><i class="fas fa-user-check me-2"></i> Mark Attendance</a> -->
            <a class="btn btn-sidebar w-100 text-start <?php echo $current_page == 'attendance_history.php' ? 'active' : ''; ?>" href="attendance_history.php"><i class="fas fa-history me-2"></i> Attendance History</a>
            <!-- <a class="btn btn-sidebar w-100 text-start <?php echo $current_page == 'mark_assignment.php' ? 'active' : ''; ?>" href="mark_assignment.php"><i class="fas fa-book me-2"></i> Mark Assignment</a> -->
            <a class="btn btn-sidebar w-100 text-start <?php echo $current_page == 'assignment_history.php' ? 'active' : ''; ?>" href="assignment_history.php"><i class="fas fa-list me-2"></i> Assignment History</a>
            <a class="btn btn-sidebar w-100 text-start <?php echo $current_page == 'profile.php' ? 'active' : ''; ?>" href="profile.php"><i class="fas fa-user me-2"></i> Profile</a>
            <a class="btn btn-sidebar w-100 text-start <?php echo $current_page == 'about.php' ? 'active' : ''; ?>" href="about.php"><i class="fas fa-question-circle me-2"></i> About & Help</a>
            <!-- <a class="btn btn-sidebar w-100 text-start <?php echo $current_page == 'logout.php' ? 'active' : ''; ?>" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a> -->
        </div>
    </div>
    <!-- Main Content -->
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar (desktop) -->
           
            <div class="col-md-3 col-lg-2 sidebar-col px-0 d-none d-lg-block">
                <div class="sidebar">
                    <?php $current_page = basename($_SERVER['PHP_SELF']); ?>
                    <a class="btn btn-sidebar w-100 text-start <?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>" href="dashboard.php"><i class="fas fa-tachometer-alt me-2"></i> Dashboard</a>
                    <a class="btn btn-sidebar w-100 text-start <?php echo $current_page == 'sections.php' ? 'active' : ''; ?>" href="sections.php"><i class="fas fa-layer-group me-2"></i> Sections</a>
                    <a class="btn btn-sidebar w-100 text-start <?php echo $current_page == 'add_section.php' ? 'active' : ''; ?>" href="add_section.php"><i class="fas fa-plus me-2"></i> Add Section</a>
                    <!-- <a class="btn btn-sidebar w-100 text-start <?php echo $current_page == 'mark_attendance.php' ? 'active' : ''; ?>" href="mark_attendance.php"><i class="fas fa-user-check me-2"></i> Mark Attendance</a> -->
                    <a class="btn btn-sidebar w-100 text-start <?php echo $current_page == 'attendance_history.php' ? 'active' : ''; ?>" href="attendance_history.php"><i class="fas fa-history me-2"></i> Attendance History</a>
                    <!-- <a class="btn btn-sidebar w-100 text-start <?php echo $current_page == 'mark_assignment.php' ? 'active' : ''; ?>" href="mark_assignment.php"><i class="fas fa-book me-2"></i> Mark Assignment</a> -->
                    <a class="btn btn-sidebar w-100 text-start <?php echo $current_page == 'assignment_history.php' ? 'active' : ''; ?>" href="assignment_history.php"><i class="fas fa-list me-2"></i> Assignment History</a>
                    <a class="btn btn-sidebar w-100 text-start <?php echo $current_page == 'profile.php' ? 'active' : ''; ?>" href="profile.php"><i class="fas fa-user me-2"></i> Profile</a>
                    <a class="btn btn-sidebar w-100 text-start <?php echo $current_page == 'about.php' ? 'active' : ''; ?>" href="about.php"><i class="fas fa-question-circle me-2"></i> About & Help</a>
                    <!-- <a class="btn btn-sidebar w-100 text-start <?php echo $current_page == 'logout.php' ? 'active' : ''; ?>" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a> -->
                </div>
            </div>
            <div class="col-md-9 col-lg-10 main-content-fixed">
                <div class="main-content">
                    <!-- Flash Messages -->
                    <?php $flash = getFlashMessage(); ?>
                    <?php if ($flash): ?>
                        <div class="alert alert-<?php echo $flash['type']; ?> alert-dismissible fade show" role="alert">
                            <?php echo $flash['message']; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <h2 class="mb-4">
                        <i class="fas fa-tachometer-alt"></i> Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!
                    </h2>

                    <!-- Statistics Cards -->
                    <div class="overview-card p-3 mb-4">
                        <div class="row">
                        <div class="col-md-4">
                            <div class="stat-card text-center">
                                    <div class="stat-icon text-primary"><i class="fas fa-layer-group"></i></div>
                                <div class="stat-number text-primary"><?php echo $stats['total_sections']; ?></div>
                                <div class="text-muted">Total Sections</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="stat-card text-center">
                                    <div class="stat-icon text-success"><i class="fas fa-user-graduate"></i></div>
                                <div class="stat-number text-success"><?php echo $stats['total_students']; ?></div>
                                <div class="text-muted">Total Students</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="stat-card text-center">
                                    <div class="stat-icon text-info"><i class="fas fa-clipboard-list"></i></div>
                                    <div class="stat-number text-info"><?php echo $stats['total_attendance']; ?></div>
                                    <div class="text-muted">Attendance Records</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0"><i class="fas fa-bolt"></i> Quick Actions</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-3">
                                            <a href="add_section.php" class="btn btn-primary w-100 mb-2"><i class="fas fa-plus"></i> Add New Section</a>
                                        </div>
                                        <div class="col-md-3">
                                            <a href="sections.php" class="btn btn-success w-100 mb-2"><i class="fas fa-layer-group"></i> Manage Sections</a>
                                        </div>
                                        <div class="col-md-3">
                                            <a href="attendance_history.php" class="btn btn-info w-100 mb-2"><i class="fas fa-history"></i> View History</a>
                                        </div>
                                        <div class="col-md-3">
                                            <a href="profile.php" class="btn btn-warning w-100 mb-2"><i class="fas fa-user"></i> Update Profile</a>
                                        </div>
                                    </div>
                                    <!-- <div class="row mt-2">
                                        <div class="col-md-3">
                                            <a href="about.php" class="btn btn-secondary w-100 mb-2"><i class="fas fa-question-circle"></i> About & Help</a>
                                        </div>
                                    </div> -->
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Sections Overview -->
                    <div class="row">
                        <div class="col-12">
                            <h4 class="mb-3"><i class="fas fa-layer-group"></i> My Sections</h4>
                            <?php if (empty($sections)): ?>
                                <div class="alert alert-info" role="alert">
                                    <i class="fas fa-info-circle"></i> You haven't created any sections yet. 
                                    <a href="add_section.php" class="alert-link">Create your first section</a> to get started.
                                </div>
                            <?php else: ?>
                                <div class="row">
                                    <?php foreach ($sections as $section): ?>
                                        <div class="col-md-6 col-lg-4">
                                            <div class="section-card">
                                                <div class="section-header">
                                                    <h5 class="mb-0"><?php echo htmlspecialchars($section['name']); ?></h5>
                                                    <span class="badge bg-primary"><?php echo $section['student_count']; ?> students</span>
                                                </div>
                                                <?php if ($section['description']): ?>
                                                    <p class="text-muted mb-3"><?php echo htmlspecialchars($section['description']); ?></p>
                                                <?php endif; ?>
                                                <div class="section-stats">
                                                    <div class="section-stat">
                                                        <div class="fw-bold"><?php echo $section['student_count']; ?></div>
                                                        <small class="text-muted">Students</small>
                                                    </div>
                                                    <div class="section-stat">
                                                        <div class="fw-bold"><?php echo $section['attendance_count']; ?></div>
                                                        <small class="text-muted">Records</small>
                                                    </div>
                                                </div>
                                                <div class="section-actions">
                                                    <a href="view_section.php?id=<?php echo $section['id']; ?>" class="btn btn-info btn-action"><i class="fas fa-eye"></i> View</a>
                                                    <a href="mark_attendance.php?section_id=<?php echo $section['id']; ?>" class="btn btn-success btn-action"><i class="fas fa-check"></i> Mark Attendance</a>
                                                    <a href="mark_assignment.php?section_id=<?php echo $section['id']; ?>" class="btn btn-warning btn-action"><i class="fas fa-book"></i> Mark Assignment</a>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Recent Attendance -->
                    <?php if (!empty($recent_attendance)): ?>
                        <div class="row mt-4">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="mb-0"><i class="fas fa-history"></i> Recent Attendance Records</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-hover">
                                                <thead>
                                                    <tr>
                                                        <th>Section</th>
                                                        <th>Date</th>
                                                        <th>Present</th>
                                                        <th>Total</th>
                                                        <th>Percentage</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($recent_attendance as $record): ?>
                                                        <tr>
                                                            <td><?php echo htmlspecialchars($record['section_name']); ?></td>
                                                            <td><?php echo formatDate($record['date']); ?></td>
                                                            <td><?php echo $record['present_count']; ?></td>
                                                            <td><?php echo $record['total_count']; ?></td>
                                                            <td>
                                                                <?php 
                                                                $percentage = $record['total_count'] > 0 ? round(($record['present_count'] / $record['total_count']) * 100, 1) : 0; 
                                                                echo $percentage . '%'; ?>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <!-- Footer -->
    <footer class="text-center mt-5 mb-3 text-muted small">
        &lt;GoG&gt; Smart Attendance Tracker Presented By Satish Nagar
    </footer>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Theme toggle logic
    function applyTheme(theme) {
        if (theme === 'dark') {
            document.body.classList.add('dark-mode');
            // Update mobile dropdown
            const mobileToggle = document.getElementById('themeToggle');
            if (mobileToggle) {
                document.getElementById('themeToggleText').textContent = 'Light Mode';
                mobileToggle.querySelector('i').className = 'fas fa-sun me-2';
            }
            // Update desktop dropdown
            const desktopToggle = document.getElementById('themeToggleDesktop');
            if (desktopToggle) {
                document.getElementById('themeToggleTextDesktop').textContent = 'Light Mode';
                desktopToggle.querySelector('i').className = 'fas fa-sun me-2';
            }
        } else {
            document.body.classList.remove('dark-mode');
            // Update mobile dropdown
            const mobileToggle = document.getElementById('themeToggle');
            if (mobileToggle) {
                document.getElementById('themeToggleText').textContent = 'Dark Mode';
                mobileToggle.querySelector('i').className = 'fas fa-moon me-2';
            }
            // Update desktop dropdown
            const desktopToggle = document.getElementById('themeToggleDesktop');
            if (desktopToggle) {
                document.getElementById('themeToggleTextDesktop').textContent = 'Dark Mode';
                desktopToggle.querySelector('i').className = 'fas fa-moon me-2';
            }
        }
    }
    function getTheme() {
        return localStorage.getItem('theme') || 'light';
    }
    function setTheme(theme) {
        localStorage.setItem('theme', theme);
        applyTheme(theme);
    }
    document.addEventListener('DOMContentLoaded', function() {
        // Apply theme on load
        applyTheme(getTheme());
        
        // Toggle theme on mobile dropdown click
        const mobileToggle = document.getElementById('themeToggle');
        if (mobileToggle) {
            mobileToggle.addEventListener('click', function(e) {
                e.preventDefault();
                const current = getTheme();
                setTheme(current === 'dark' ? 'light' : 'dark');
            });
        }
        
        // Toggle theme on desktop dropdown click
        const desktopToggle = document.getElementById('themeToggleDesktop');
        if (desktopToggle) {
            desktopToggle.addEventListener('click', function(e) {
                e.preventDefault();
                const current = getTheme();
                setTheme(current === 'dark' ? 'light' : 'dark');
            });
        }
    });
    </script>
    <style>
    body.dark-mode {
        background-color: #181a1b !important;
        color: #e0e0e0 !important;
    }
    body.dark-mode .card, body.dark-mode .about-card, body.dark-mode .history-card, body.dark-mode .attendance-card, body.dark-mode .assignment-card {
        background: #23272b !important;
        color: #e0e0e0 !important;
        box-shadow: 0 5px 15px rgba(0,0,0,0.3);
    }
    body.dark-mode .sidebar, body.dark-mode .offcanvas {
        background: #23272b !important;
        color: #e0e0e0 !important;
    }
    body.dark-mode .btn, body.dark-mode .btn-sidebar {
        background: #23272b !important;
        color: #e0e0e0 !important;
        border-color: #444 !important;
    }
    body.dark-mode .btn:hover, body.dark-mode .btn-sidebar:hover, body.dark-mode .btn.active {
        background: #333 !important;
        color: #fff !important;
    }
    body.dark-mode .dropdown-menu {
        background: #23272b !important;
        color: #e0e0e0 !important;
    }
    body.dark-mode .dropdown-item {
        color: #e0e0e0 !important;
    }
    body.dark-mode .dropdown-item:hover {
        background: #333 !important;
        color: #fff !important;
    }
    body.dark-mode .form-control, body.dark-mode input, body.dark-mode textarea, body.dark-mode select {
        background: #23272b !important;
        color: #e0e0e0 !important;
        border-color: #444 !important;
    }
    body.dark-mode .form-control:focus {
        background: #23272b !important;
        color: #fff !important;
        border-color: #666 !important;
    }
    body.dark-mode footer {
        background: #23272b !important;
        color: #aaa !important;
        border-top: 1px solid #444 !important;
    }
    </style>
</body>
</html> 