<?php
require_once '../includes/functions.php';
requireOM();

$db = getDB();
$om_id = $_SESSION['user_id'];

// Fetch OM profile picture for header
$stmt = $db->prepare("SELECT profile_picture FROM operation_managers WHERE id = ?");
$stmt->execute([$om_id]);
$om_profile = $stmt->fetch();
$profile_picture = $om_profile && $om_profile['profile_picture'] ? '../uploads/' . htmlspecialchars($om_profile['profile_picture']) : 'https://via.placeholder.com/40x40.png?text=OM';

$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About & Help - Smart Attendance System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
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
        .about-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }
        .feature-icon {
            font-size: 2.5rem;
            color: #28a745;
            margin-bottom: 15px;
        }
        .step-number {
            background: linear-gradient(90deg, #28a745 0%, #20c997 100%);
            color: white;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin-right: 15px;
        }
        .code-block {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 15px;
            font-family: 'Courier New', monospace;
            font-size: 0.9rem;
            margin: 10px 0;
        }
        .tip-box {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 8px;
            padding: 15px;
            margin: 15px 0;
        }
        .warning-box {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            border-radius: 8px;
            padding: 15px;
            margin: 15px 0;
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
        /* Sticky Footer */
        body {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }
        .container-fluid {
            flex: 1 0 auto;
        }
        footer {
            flex-shrink: 0;
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
                    <span class="d-none d-lg-inline"><?php echo htmlspecialchars($_SESSION['om_name'] ?? 'OM'); ?></span>
                </a>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user"></i> Profile</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
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
                <span class="d-none d-lg-inline"><?php echo htmlspecialchars($_SESSION['om_name'] ?? 'OM'); ?></span>
            </a>
            <ul class="dropdown-menu">
                <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user"></i> Profile</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
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
            <a class="btn btn-sidebar w-100 text-start <?php echo $current_page == 'attendance_history.php' ? 'active' : ''; ?>" href="attendance_history.php"><i class="fas fa-history me-2"></i> Attendance History</a>
            <a class="btn btn-sidebar w-100 text-start <?php echo $current_page == 'assignment_history.php' ? 'active' : ''; ?>" href="assignment_history.php"><i class="fas fa-list me-2"></i> Assignment History</a>
            <a class="btn btn-sidebar w-100 text-start <?php echo $current_page == 'profile.php' ? 'active' : ''; ?>" href="profile.php"><i class="fas fa-user me-2"></i> Profile</a>
            <a class="btn btn-sidebar w-100 text-start <?php echo $current_page == 'about.php' ? 'active' : ''; ?>" href="about.php"><i class="fas fa-question-circle me-2"></i> About & Help</a>
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
                    <a class="btn btn-sidebar w-100 text-start <?php echo $current_page == 'attendance_history.php' ? 'active' : ''; ?>" href="attendance_history.php"><i class="fas fa-history me-2"></i> Attendance History</a>
                    <a class="btn btn-sidebar w-100 text-start <?php echo $current_page == 'assignment_history.php' ? 'active' : ''; ?>" href="assignment_history.php"><i class="fas fa-list me-2"></i> Assignment History</a>
                    <a class="btn btn-sidebar w-100 text-start <?php echo $current_page == 'profile.php' ? 'active' : ''; ?>" href="profile.php"><i class="fas fa-user me-2"></i> Profile</a>
                    <a class="btn btn-sidebar w-100 text-start <?php echo $current_page == 'about.php' ? 'active' : ''; ?>" href="about.php"><i class="fas fa-question-circle me-2"></i> About & Help</a>
                </div>
            </div>
            <div class="col-md-9 col-lg-10 main-content-fixed">
                <div class="main-content">
                    <a href="dashboard.php" class="btn btn-secondary btn-sm mb-3">&larr; Back to Dashboard</a>
                    
                    <!-- About System -->
                    <div class="about-card">
                        <div class="text-center mb-4">
                            <i class="fas fa-graduation-cap feature-icon"></i>
                            <h2>QuickMark - Smart Attendance System</h2>
                            <p class="lead">Complete User Manual & Feature Guide for Operation Managers</p>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <h4><i class="fas fa-info-circle text-primary"></i> About the System</h4>
                                <p>QuickMark is a comprehensive attendance and assignment tracking system designed for educational institutions. As an Operation Manager (OM), you have access to powerful tools for managing student attendance, assignments, and academic records.</p>
                            </div>
                            <div class="col-md-6">
                                <h4><i class="fas fa-user-tie text-success"></i> Your Role</h4>
                                <p>As an Operation Manager, you can create and manage sections, track student attendance, grade assignments, and maintain comprehensive records of academic activities.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Start Guide -->
                    <div class="about-card">
                        <h3><i class="fas fa-rocket text-warning"></i> Quick Start Guide</h3>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="d-flex align-items-start mb-3">
                                    <div class="step-number">1</div>
                                    <div>
                                        <h5>Create Your First Section</h5>
                                        <p>Go to "Add Section" and create a new section with student details.</p>
                                    </div>
                                </div>
                                <div class="d-flex align-items-start mb-3">
                                    <div class="step-number">2</div>
                                    <div>
                                        <h5>Mark Attendance</h5>
                                        <p>Use the attendance marking feature to track daily student presence.</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="d-flex align-items-start mb-3">
                                    <div class="step-number">3</div>
                                    <div>
                                        <h5>Grade Assignments</h5>
                                        <p>Record assignment scores and track student performance.</p>
                                    </div>
                                </div>
                                <div class="d-flex align-items-start mb-3">
                                    <div class="step-number">4</div>
                                    <div>
                                        <h5>View Reports</h5>
                                        <p>Access attendance and assignment history for comprehensive reporting.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Page-by-Page Guide -->
                    <div class="about-card">
                        <h3><i class="fas fa-book text-info"></i> Complete Feature Guide</h3>
                        
                        <!-- Dashboard -->
                        <div class="mb-4">
                            <h4><i class="fas fa-tachometer-alt text-primary"></i> Dashboard</h4>
                            <p><strong>Purpose:</strong> Your main control center showing overview statistics and quick access to all features.</p>
                            <div class="row">
                                <div class="col-md-6">
                                    <h6>Features:</h6>
                                    <ul>
                                        <li>Total sections, students, and attendance count</li>
                                        <li>Recent attendance records</li>
                                        <li>Quick action buttons</li>
                                        <li>Section cards with student counts</li>
                                    </ul>
                                </div>
                                <div class="col-md-6">
                                    <h6>How to Use:</h6>
                                    <ul>
                                        <li>View statistics at a glance</li>
                                        <li>Click section cards to view details</li>
                                        <li>Use quick action buttons for common tasks</li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <!-- Sections Management -->
                        <div class="mb-4">
                            <h4><i class="fas fa-layer-group text-success"></i> Sections Management</h4>
                            <p><strong>Purpose:</strong> Create and manage student sections with comprehensive student data.</p>
                            
                            <h5>Add Section Page</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <h6>Features:</h6>
                                    <ul>
                                        <li>Section creation with name and description</li>
                                        <li>Bulk student import via paste</li>
                                        <li>Preview functionality with validation</li>
                                        <li>Duplicate roll number detection</li>
                                    </ul>
                                </div>
                                <div class="col-md-6">
                                    <h6>Student Data Format:</h6>
                                    <div class="code-block">
                                        <div>Name    Roll_Number    Contact</div>
                                        <div>Satish Nagar  0128CS221001    john@email.com</div>
                                        <div>Jane Smith    0128CS221002    jane@email.com</div>
                                    </div>
                                    <p><small>Separate columns with Tab, Comma, or Space</small></p>
                                </div>
                            </div>

                            <h5>Manage Sections Page</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <h6>Features:</h6>
                                    <ul>
                                        <li>View all sections in card format</li>
                                        <li>Edit students within sections</li>
                                        <li>Delete sections with confirmation</li>
                                        <li>View student lists</li>
                                    </ul>
                                </div>
                                <div class="col-md-6">
                                    <h6>Actions:</h6>
                                    <ul>
                                        <li><strong>View Students:</strong> See all students in a section</li>
                                        <li><strong>Edit Students:</strong> Add, edit, or delete individual students</li>
                                        <li><strong>Delete Section:</strong> Remove entire section (with confirmation)</li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <!-- Attendance Management -->
                        <div class="mb-4">
                            <h4><i class="fas fa-user-check text-warning"></i> Attendance Management</h4>
                            <p><strong>Purpose:</strong> Mark and track daily student attendance with binary data generation.</p>
                            
                            <h5>Mark Attendance</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <h6>Features:</h6>
                                    <ul>
                                        <li>Section and date selection</li>
                                        <li>Roll number input (last 3 digits)</li>
                                        <li>Auto-padding for single/double digits</li>
                                        <li>Binary output generation</li>
                                        <li>Copy to clipboard functionality</li>
                                    </ul>
                                </div>
                                <div class="col-md-6">
                                    <h6>Input Examples:</h6>
                                    <div class="code-block">
                                        <div>Single digit: 5 → 005</div>
                                        <div>Double digit: 55 → 055</div>
                                        <div>Triple digit: 555 → 555</div>
                                        <div>Mixed separators: 4, 5 10, 50 11</div>
                                    </div>
                                </div>
                            </div>

                            <div class="tip-box">
                                <strong><i class="fas fa-lightbulb"></i> Pro Tip:</strong> Use the copy function to save binary data for future reference or re-use.
                            </div>

                            <h5>Attendance History</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <h6>Features:</h6>
                                    <ul>
                                        <li>Historical attendance records</li>
                                        <li>Section filtering</li>
                                        <li>Binary data column</li>
                                        <li>Copy binary data functionality</li>
                                        <li>Attendance statistics</li>
                                    </ul>
                                </div>
                                <div class="col-md-6">
                                    <h6>Statistics Display:</h6>
                                    <ul>
                                        <li>Present count vs total students</li>
                                        <li>Attendance percentage</li>
                                        <li>Date-wise records</li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <!-- Assignment Management -->
                        <div class="mb-4">
                            <h4><i class="fas fa-book text-info"></i> Assignment Management</h4>
                            <p><strong>Purpose:</strong> Record and track assignment scores with comprehensive student mapping.</p>
                            
                            <h5>Mark Assignment</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <h6>Features:</h6>
                                    <ul>
                                        <li>Assignment name and date input</li>
                                        <li>Roll number and score pairs</li>
                                        <li>Multiple input format support</li>
                                        <li>Preview before saving</li>
                                        <li>Automatic student mapping</li>
                                    </ul>
                                </div>
                                <div class="col-md-6">
                                    <h6>Input Format Examples:</h6>
                                    <div class="code-block">
                                        <div>Tab separated:</div>
                                        <div>0128CS221001    85</div>
                                        <div>0128CS221002    92</div>
                                        <div></div>
                                        <div>Comma separated:</div>
                                        <div>0128CS221001,85</div>
                                        <div>0128CS221002,92</div>
                                        <div></div>
                                        <div>Space separated:</div>
                                        <div>0128CS221001 85</div>
                                        <div>0128CS221002 92</div>
                                    </div>
                                </div>
                            </div>

                            <div class="warning-box">
                                <strong><i class="fas fa-exclamation-triangle"></i> Important:</strong> Use full roll numbers (not just last 3 digits) for assignment marking.
                            </div>

                            <h5>Assignment History</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <h6>Features:</h6>
                                    <ul>
                                        <li>Assignment summary list</li>
                                        <li>Section filtering</li>
                                        <li>Detailed student scores</li>
                                        <li>Copy scores/statuses</li>
                                        <li>Performance statistics</li>
                                    </ul>
                                </div>
                                <div class="col-md-6">
                                    <h6>Statistics:</h6>
                                    <ul>
                                        <li>Attempted vs total students</li>
                                        <li>Average scores</li>
                                        <li>Individual student performance</li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <!-- Profile Management -->
                        <div class="mb-4">
                            <h4><i class="fas fa-user text-success"></i> Profile Management</h4>
                            <p><strong>Purpose:</strong> Update personal information and profile picture.</p>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <h6>Features:</h6>
                                    <ul>
                                        <li>Update name and contact details</li>
                                        <li>Profile picture upload</li>
                                        <li>Supported formats: JPG, JPEG, PNG</li>
                                        <li>Real-time validation</li>
                                        <li>Session data update</li>
                                    </ul>
                                </div>
                                <div class="col-md-6">
                                    <h6>How to Use:</h6>
                                    <ul>
                                        <li>Fill in your details</li>
                                        <li>Choose a profile picture</li>
                                        <li>Click "Update Profile"</li>
                                        <li>Changes reflect immediately</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tips and Best Practices -->
                    <div class="about-card">
                        <h3><i class="fas fa-star text-warning"></i> Tips & Best Practices</h3>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <h5><i class="fas fa-check-circle text-success"></i> Do's</h5>
                                <ul>
                                    <li>Use consistent roll number formats</li>
                                    <li>Preview data before saving</li>
                                    <li>Regularly backup your data</li>
                                    <li>Use descriptive section names</li>
                                    <li>Keep student contact information updated</li>
                                    <li>Use the copy functions for data portability</li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <h5><i class="fas fa-times-circle text-danger"></i> Don'ts</h5>
                                <ul>
                                    <li>Don't use future dates for attendance</li>
                                    <li>Don't duplicate roll numbers</li>
                                    <li>Don't leave required fields empty</li>
                                    <li>Don't use special characters in roll numbers</li>
                                    <li>Don't delete sections without confirmation</li>
                                    <li>Don't upload large image files</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Troubleshooting -->
                    <div class="about-card">
                        <h3><i class="fas fa-tools text-info"></i> Troubleshooting</h3>
                        
                        <div class="accordion" id="troubleshootingAccordion">
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne">
                                        <i class="fas fa-question-circle me-2"></i>Common Issues & Solutions
                                    </button>
                                </h2>
                                <div id="collapseOne" class="accordion-collapse collapse show" data-bs-parent="#troubleshootingAccordion">
                                    <div class="accordion-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <h6>Attendance Issues:</h6>
                                                <ul>
                                                    <li><strong>Roll number not found:</strong> Check if using last 3 digits only</li>
                                                    <li><strong>Duplicate attendance:</strong> Each date can only have one record</li>
                                                    <li><strong>Future date error:</strong> Cannot mark attendance for future dates</li>
                                                </ul>
                                            </div>
                                            <div class="col-md-6">
                                                <h6>Assignment Issues:</h6>
                                                <ul>
                                                    <li><strong>Student not found:</strong> Use full roll number, not last 3 digits</li>
                                                    <li><strong>Duplicate assignment:</strong> Same name/date combination not allowed</li>
                                                    <li><strong>Invalid scores:</strong> Scores must be numeric values</li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Contact Information -->
                    <div class="about-card">
                        <h3><i class="fas fa-envelope text-primary"></i> Need Help?</h3>
                        <div class="row">
                            <div class="col-md-6">
                                <h5>System Information</h5>
                                <ul>
                                    <li><strong>System:</strong> QuickMark Smart Attendance System</li>
                                    <li><strong>Version:</strong> 1.0</li>
                                    <li><strong>Developer:</strong> Satish Nagar</li>
                                    <li><strong>Role:</strong> Operations Analyst</li>
                                    <li><strong>Organization:</strong> Geeks of Gurukul</li>
                                    <li><strong>Email:</strong> satish@geeksofgurukul.com</li>
                                   
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <h5>Support</h5>
                                <ul>
                                    <li>For technical issues, contact your system administrator</li>
                                    <li>Check this help page for common solutions</li>
                                    <li>Ensure you're using a supported browser</li>
                                    <li>Clear browser cache if experiencing issues</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <footer class="text-center mt-5 mb-3 text-muted small">
        &lt;GoG&gt; Smart Attendance Tracker Presented By Satish Nagar
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 