<?php
require_once '../includes/functions.php';
requireOM();

$om_id = $_SESSION['user_id'];

// Fetch OM profile picture for header
$db = getDB(); // Re-declare $db to ensure it's available for the profile picture query
$stmt = $db->prepare("SELECT profile_picture FROM operation_managers WHERE id = ?");
$stmt->execute([$om_id]);
$om_profile = $stmt->fetch();
$profile_picture = $om_profile && $om_profile['profile_picture'] ? '../uploads/' . htmlspecialchars($om_profile['profile_picture']) : 'https://via.placeholder.com/40x40.png?text=OM';

$section_id = isset($_GET['section_id']) ? (int)$_GET['section_id'] : 0;

if (!$section_id) {
    setFlashMessage('error', 'Section ID is required');
    redirect('dashboard.php');
}

// Verify section belongs to this OM
$stmt = $db->prepare("SELECT * FROM sections WHERE id = ? AND om_id = ?");
$stmt->execute([$section_id, $om_id]);
$section = $stmt->fetch();

if (!$section) {
    setFlashMessage('error', 'Section not found or access denied');
    redirect('dashboard.php');
}

// Get students in this section
$stmt = $db->prepare("SELECT * FROM students WHERE section_id = ? ORDER BY roll_no");
$stmt->execute([$section_id]);
$students = $stmt->fetchAll();

if (empty($students)) {
    setFlashMessage('error', 'No students found in this section');
    redirect('dashboard.php');
}

$success = '';
$error = '';
$binary_output = '';
$attendance_stats = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $present_rolls_input = sanitizeInput($_POST['present_rolls']);
    $date = isset($_POST['attendance_date']) ? sanitizeInput($_POST['attendance_date']) : getCurrentDate();
    
    // Accept both commas and spaces as separators, and allow mixed usage
    $present_rolls_raw = preg_split('/[\s,]+/', $present_rolls_input, -1, PREG_SPLIT_NO_EMPTY);
    $present_rolls = [];
    foreach ($present_rolls_raw as $roll) {
        $roll = trim($roll);
        // Pad to 3 digits if needed
        if (ctype_digit($roll)) {
            $roll = str_pad($roll, 3, '0', STR_PAD_LEFT);
        }
        $present_rolls[] = $roll;
    }
    
    if (empty($present_rolls)) {
        $error = 'Please enter the roll numbers of present students';
    } else {
        // Validate roll numbers (should be 3 digits)
        foreach ($present_rolls as $roll) {
            if (!preg_match('/^\d{3}$/', $roll)) {
                $error = 'Roll numbers should be exactly 3 digits (e.g., 005, 055, 555)';
                break;
            }
        }
        
        if (!$error) {
            if (strtotime($date) > strtotime(getCurrentDate())) {
                $error = 'Cannot mark attendance for a future date.';
            }
        }
        
        if (!$error) {
            // Generate binary string
            $binary_output = generateBinaryString($present_rolls, $students);
            // Convert to row-wise (each value on a new line)
            $binary_output = str_replace(',', "\n", $binary_output);
            $attendance_stats = getAttendanceStats(str_replace("\n", ',', $binary_output));
            
            // Check if attendance already exists for today
            $stmt = $db->prepare("SELECT id FROM attendance WHERE section_id = ? AND date = ?");
            $stmt->execute([$section_id, $date]);
            
            if ($stmt->fetch()) {
                $error = 'Attendance for today has already been marked. You can view it in the attendance history.';
            } else {
                // Save attendance record
                try {
                    $stmt = $db->prepare("INSERT INTO attendance (section_id, date, binary_string, present_count, total_count) VALUES (?, ?, ?, ?, ?)");
                    $stmt->execute([$section_id, $date, $binary_output, $attendance_stats['present'], $attendance_stats['total']]);
                    
                    $success = 'Attendance marked successfully!';
                    
                } catch (Exception $e) {
                    $error = 'Error saving attendance: ' . $e->getMessage();
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mark Attendance - <?php echo htmlspecialchars($section['name']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
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
        .attendance-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        .form-control {
            border-radius: 10px;
            border: 2px solid #e9ecef;
            padding: 12px 15px;
            transition: all 0.3s ease;
        }
        .form-control:focus {
            border-color: #28a745;
            box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.25);
        }
        .btn-submit {
            background: linear-gradient(45deg, #28a745, #20c997);
            border: none;
            border-radius: 10px;
            padding: 12px 30px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
        }
        .binary-output {
            background: #f8f9fa;
            border: 2px solid #28a745;
            border-radius: 10px;
            padding: 15px;
            font-family: 'Courier New', monospace;
            font-size: 1.1rem;
            word-break: break-all;
        }
        .student-list {
            max-height: 400px;
            overflow-y: auto;
        }
        .student-item {
            padding: 8px 12px;
            border-bottom: 1px solid #e9ecef;
        }
        .student-item:last-child {
            border-bottom: none;
        }
        .roll-suffix {
            background: #e9ecef;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 0.9rem;
            font-weight: bold;
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
                    <!-- <a class="btn btn-sidebar w-100 text-start <?php echo $current_page == 'logout.php' ? 'active' : ''; ?>" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a> -->
                </div>
            </div>
            <div class="col-md-9 col-lg-10 main-content-fixed">
                <div class="main-content">
                    <div class="row">
                        <div class="col-md-8">
                        <a href="dashboard.php" class="btn btn-secondary">
                                            <i class="fas fa-arrow-left"></i> Back to Dashboard
                                        </a>
                            <div class="attendance-card">
                                <h3 class="mb-4">
                                    <i class="fas fa-check"></i> Mark Attendance - <?php echo htmlspecialchars($section['name']); ?>
                                </h3>
                                
                                <?php if ($success): ?>
                                    <div class="alert alert-success" role="alert">
                                        <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ($error): ?>
                                    <div class="alert alert-danger" role="alert">
                                        <i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?>
                                    </div>
                                <?php endif; ?>
                                
                                <form method="POST" action="">
                                    <div class="mb-4">
                                        <label for="attendance_date" class="form-label">
                                            Select Date *
                                        </label>
                                        <input type="date" class="form-control" id="attendance_date" name="attendance_date"
                                               value="<?php echo isset($_POST['attendance_date']) ? htmlspecialchars($_POST['attendance_date']) : getCurrentDate(); ?>"
                                               max="<?php echo getCurrentDate(); ?>" required>
                                        <div class="form-text">
                                            You can update attendance for previous days. Future dates are not allowed.
                                        </div>
                                    </div>
                                    <div class="mb-4">
                                        <label for="present_rolls" class="form-label">
                                            Enter Last 3 Digits of Present Students' Roll Numbers *
                                        </label>
                                        <input type="text" class="form-control" id="present_rolls" name="present_rolls" 
                                               value="<?php echo isset($_POST['present_rolls']) ? htmlspecialchars($_POST['present_rolls']) : ''; ?>" 
                                               placeholder="e.g., 123, 125, 141" required>
                                        <div class="form-text">
                                            Enter the last 3 digits of roll numbers separated by commas
                                        </div>
                                    </div>
                                    
                                    <div class="d-flex justify-content-between">
                                       
                                        <button type="submit" class="btn btn-success btn-submit">
                                            <i class="fas fa-check"></i> Mark Attendance
                                        </button>
                                    </div>
                                </form>
                                
                                <?php if ($binary_output): ?>
                                    <div class="mt-4">
                                        <h5><i class="fas fa-clipboard"></i> Generated Binary Output</h5>
                                        <div class="binary-output" id="binaryOutput">
                                            <?php echo $binary_output; ?>
                                        </div>
                                        <div class="mt-3">
                                            <button class="btn btn-primary" onclick="copyToClipboard(event)">
                                                <i class="fas fa-copy"></i> Copy to Clipboard
                                            </button>
                                            <?php if ($attendance_stats): ?>
                                                <div class="mt-2">
                                                    <strong>Statistics:</strong> 
                                                    <?php echo $attendance_stats['present']; ?> present out of 
                                                    <?php echo $attendance_stats['total']; ?> students 
                                                    (<?php echo $attendance_stats['percentage']; ?>%)
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="attendance-card">
                                <h5 class="mb-3">
                                    <i class="fas fa-users"></i> Student List
                                </h5>
                                <div class="student-list">
                                    <?php foreach ($students as $index => $student): ?>
                                        <div class="student-item">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <strong><?php echo htmlspecialchars($student['name']); ?></strong>
                                                    <br>
                                                    <small class="text-muted"><?php echo htmlspecialchars($student['roll_no']); ?></small>
                                                </div>
                                                <span class="roll-suffix">
                                                    <?php echo substr($student['roll_no'], -3); ?>
                                                </span>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <div class="mt-3 text-center">
                                    <small class="text-muted">
                                        Total Students: <?php echo count($students); ?>
                                    </small>
                                </div>
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
    <script>
function copyToClipboard(event) {
            const binaryOutput = document.getElementById('binaryOutput');
    if (!binaryOutput || !binaryOutput.textContent.trim()) {
        alert('Nothing to copy!');
        return;
    }
    const text = binaryOutput.textContent.trim();
            navigator.clipboard.writeText(text).then(function() {
                // Show success message
                const button = event.target;
                const originalText = button.innerHTML;
                button.innerHTML = '<i class="fas fa-check"></i> Copied!';
                button.classList.remove('btn-primary');
                button.classList.add('btn-success');
                setTimeout(function() {
                    button.innerHTML = originalText;
                    button.classList.remove('btn-success');
                    button.classList.add('btn-primary');
                }, 2000);
            }).catch(function(err) {
                console.error('Could not copy text: ', err);
                alert('Failed to copy to clipboard');
            });
        }
    </script>
    <script src="assignment_modal.js"></script>
</body>
</html> 