<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once '../includes/functions.php';
requireOM();

$db = getDB();
$om_id = $_SESSION['user_id'];

// Fetch OM profile picture
$stmt = $db->prepare("SELECT profile_picture FROM operation_managers WHERE id = ?");
$stmt->execute([$om_id]);
$om_profile = $stmt->fetch();
$profile_picture = $om_profile && $om_profile['profile_picture'] ? '../uploads/' . htmlspecialchars($om_profile['profile_picture']) : 'https://via.placeholder.com/40x40.png?text=OM';

// Get section ID and validate ownership
$section_id = isset($_GET['section_id']) ? (int)$_GET['section_id'] : 0;
if (!$section_id) {
    header('Location: sections.php');
    exit;
}

// Check if section belongs to this OM
$stmt = $db->prepare("SELECT * FROM sections WHERE id = ? AND om_id = ?");
$stmt->execute([$section_id, $om_id]);
$section = $stmt->fetch();

if (!$section) {
    header('Location: sections.php');
    exit;
}

// Handle form submissions
$success = '';
$error = '';

// Add new student
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $name = trim($_POST['name']);
    $roll_no = trim($_POST['roll_no']);
    $contact = trim($_POST['contact']);
    
    if ($name && $roll_no) {
        // Check for duplicate roll number in this section
        $stmt = $db->prepare("SELECT id FROM students WHERE section_id = ? AND roll_no = ?");
        $stmt->execute([$section_id, $roll_no]);
        if ($stmt->fetch()) {
            $error = 'Roll number already exists in this section.';
        } else {
            $stmt = $db->prepare("INSERT INTO students (section_id, name, roll_no, contact) VALUES (?, ?, ?, ?)");
            if ($stmt->execute([$section_id, $name, $roll_no, $contact])) {
                $success = 'Student added successfully!';
            } else {
                $error = 'Failed to add student.';
            }
        }
    } else {
        $error = 'Name and Roll Number are required.';
    }
}

// Update student
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update') {
    $student_id = (int)$_POST['student_id'];
    $name = trim($_POST['name']);
    $roll_no = trim($_POST['roll_no']);
    $contact = trim($_POST['contact']);
    
    if ($name && $roll_no) {
        // Check for duplicate roll number (excluding current student)
        $stmt = $db->prepare("SELECT id FROM students WHERE section_id = ? AND roll_no = ? AND id != ?");
        $stmt->execute([$section_id, $roll_no, $student_id]);
        if ($stmt->fetch()) {
            $error = 'Roll number already exists in this section.';
        } else {
            $stmt = $db->prepare("UPDATE students SET name = ?, roll_no = ?, contact = ? WHERE id = ? AND section_id = ?");
            if ($stmt->execute([$name, $roll_no, $contact, $student_id, $section_id])) {
                $success = 'Student updated successfully!';
            } else {
                $error = 'Failed to update student.';
            }
        }
    } else {
        $error = 'Name and Roll Number are required.';
    }
}

// Delete student
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $student_id = (int)$_POST['student_id'];
    $stmt = $db->prepare("DELETE FROM students WHERE id = ? AND section_id = ?");
    if ($stmt->execute([$student_id, $section_id])) {
        $success = 'Student deleted successfully!';
    } else {
        $error = 'Failed to delete student.';
    }
}

// Fetch all students in this section
$stmt = $db->prepare("SELECT * FROM students WHERE section_id = ? ORDER BY name");
$stmt->execute([$section_id]);
$students = $stmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Students - <?php echo htmlspecialchars($section['name']); ?></title>
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
        
        /* Student Cards */
        .student-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin-bottom: 20px;
            border-left: 4px solid #28a745;
            transition: all 0.3s ease;
        }
        .student-card:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            transform: translateY(-2px);
        }
        .student-name {
            font-size: 1.1rem;
            font-weight: 600;
            color: #28a745;
            margin-bottom: 8px;
        }
        .student-details {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 12px;
        }
        .student-actions {
            display: flex;
            gap: 8px;
        }
        .btn-edit-student {
            background-color: #fff3e0;
            color: #f57c00;
            border: none;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 0.85rem;
            transition: 0.2s ease-in-out;
        }
        .btn-edit-student:hover {
            background-color: #ffe0b2;
            color: #e65100;
        }
        .btn-delete-student {
            background-color: #ffebee;
            color: #c62828;
            border: none;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 0.85rem;
            transition: 0.2s ease-in-out;
        }
        .btn-delete-student:hover {
            background-color: #ffcdd2;
            color: #b71c1c;
        }
        
        /* Add Student Form */
        .add-student-form {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            padding: 25px;
            margin-bottom: 30px;
            border-left: 4px solid #007bff;
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
                padding: 10px 15px 15px 15px;
            }
            .main-content {
                padding: 10px 15px;
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
                    <span class="d-none d-lg-inline"><?php echo htmlspecialchars($_SESSION['om_name'] ?? 'OM'); ?></span>
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
                <span class="d-none d-lg-inline"><?php echo htmlspecialchars($_SESSION['om_name'] ?? 'OM'); ?></span>
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
            <!-- <a class="btn btn-sidebar w-100 text-start <?php echo $current_page == 'logout.php' ? 'active' : ''; ?>" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a> -->
        </div>
    </div>
    
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
                    <!-- <a class="btn btn-sidebar w-100 text-start <?php echo $current_page == 'logout.php' ? 'active' : ''; ?>" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a> -->
                </div>
            </div>
            <div class="col-md-9 col-lg-10 main-content-fixed">
            
                <div class="main-content">
                <a href="sections.php" class="btn btn-secondary btn-sm">&larr; Back to Sections</a>
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2 class="mb-0">
                            <i class="fas fa-users"></i> Edit Students - <?php echo htmlspecialchars($section['name']); ?>
                        </h2>
                        <!-- <a href="sections.php" class="btn btn-secondary btn-sm">&larr; Back to Sections</a> -->
                    </div>
                    
                    <?php if ($success): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <?php echo $success; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?php echo $error; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Add New Student Form -->
                    <div class="add-student-form">
                        <h5 class="mb-3"><i class="fas fa-plus"></i> Add New Student</h5>
                        <form method="POST" class="row g-3">
                            <input type="hidden" name="action" value="add">
                            <div class="col-md-4">
                                <label for="name" class="form-label">Student Name *</label>
                                <input type="text" class="form-control" id="name" name="name" required>
                            </div>
                            <div class="col-md-4">
                                <label for="roll_no" class="form-label">Roll Number *</label>
                                <input type="text" class="form-control" id="roll_no" name="roll_no" required>
                            </div>
                            <div class="col-md-4">
                                <label for="contact" class="form-label">Contact Number</label>
                                <input type="text" class="form-control" id="contact" name="contact">
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-plus"></i> Add Student
                                </button>
                            </div>
                        </form>
                    </div>
                    
                    <!-- Students List -->
                    <div class="row">
                        <div class="col-12">
                            <h5 class="mb-3">
                                <i class="fas fa-list"></i> Students (<?php echo count($students); ?>)
                            </h5>
                            
                            <?php if (empty($students)): ?>
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle"></i> No students found in this section. Add some students above.
                                </div>
                            <?php else: ?>
                                <div class="row">
                                    <?php foreach ($students as $student): ?>
                                        <div class="col-md-6 col-lg-4">
                                            <div class="student-card">
                                                <div class="student-name">
                                                    <i class="fas fa-user-graduate"></i> <?php echo htmlspecialchars($student['name']); ?>
                                                </div>
                                                <div class="student-details">
                                                    <div><i class="fas fa-id-badge"></i> Roll No: <?php echo htmlspecialchars($student['roll_no']); ?></div>
                                                    <?php if ($student['contact']): ?>
                                                        <div><i class="fas fa-phone"></i> Contact: <?php echo htmlspecialchars($student['contact']); ?></div>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="student-actions">
                                                    <button class="btn btn-edit-student" onclick="editStudent(<?php echo $student['id']; ?>, '<?php echo htmlspecialchars($student['name']); ?>', '<?php echo htmlspecialchars($student['roll_no']); ?>', '<?php echo htmlspecialchars($student['contact']); ?>')">
                                                        <i class="fas fa-edit"></i> Edit
                                                    </button>
                                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this student?');">
                                                        <input type="hidden" name="action" value="delete">
                                                        <input type="hidden" name="student_id" value="<?php echo $student['id']; ?>">
                                                        <button type="submit" class="btn btn-delete-student">
                                                            <i class="fas fa-trash"></i> Delete
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Edit Student Modal -->
    <div class="modal fade" id="editStudentModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-edit"></i> Edit Student</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="student_id" id="edit_student_id">
                        <div class="mb-3">
                            <label for="edit_name" class="form-label">Student Name *</label>
                            <input type="text" class="form-control" id="edit_name" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_roll_no" class="form-label">Roll Number *</label>
                            <input type="text" class="form-control" id="edit_roll_no" name="roll_no" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_contact" class="form-label">Contact Number</label>
                            <input type="text" class="form-control" id="edit_contact" name="contact">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Update Student
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Footer -->
    <footer class="text-center mt-5 mb-3 text-muted small">
        &lt;GoG&gt; Smart Attendance Tracker Presented By Satish Nagar
    </footer>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editStudent(id, name, rollNo, contact) {
            document.getElementById('edit_student_id').value = id;
            document.getElementById('edit_name').value = name;
            document.getElementById('edit_roll_no').value = rollNo;
            document.getElementById('edit_contact').value = contact;
            
            var modal = new bootstrap.Modal(document.getElementById('editStudentModal'));
            modal.show();
        }
    </script>
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