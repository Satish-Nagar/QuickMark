<?php
require_once '../includes/functions.php';
requireOM();

$om_id = $_SESSION['user_id'];

// Fetch OM profile picture for header
$db = getDB();
$stmt = $db->prepare("SELECT profile_picture FROM operation_managers WHERE id = ?");
$stmt->execute([$om_id]);
$om_profile = $stmt->fetch();
$profile_picture = $om_profile && $om_profile['profile_picture'] ? '../uploads/' . htmlspecialchars($om_profile['profile_picture']) : 'https://via.placeholder.com/40x40.png?text=OM';

$section_id = isset($_GET['section_id']) ? (int)$_GET['section_id'] : 0;

if (!$section_id) {
    setFlashMessage('error', 'Section ID is required');
    redirect('dashboard.php');
}

$db = getDB();

// Verify section belongs to this OM
$stmt = $db->prepare("SELECT * FROM sections WHERE id = ? AND om_id = ?");
$stmt->execute([$section_id, $om_id]);
$section = $stmt->fetch();

if (!$section) {
    setFlashMessage('error', 'Section not found or access denied');
    redirect('dashboard.php');
}

// Get students in this section
$stmt = $db->prepare("SELECT * FROM students WHERE section_id = ? ORDER BY name");
$stmt->execute([$section_id]);
$students = $stmt->fetchAll();

if (empty($students)) {
    setFlashMessage('error', 'No students found in this section');
    redirect('dashboard.php');
}

$success = '';
$error = '';
$preview_data = [];
$summary = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $assignment_name = sanitizeInput($_POST['assignment_name'] ?? '');
    $assignment_date = sanitizeInput($_POST['assignment_date'] ?? '');
    $input_scores = trim($_POST['input_scores'] ?? '');

    if (!$assignment_name || !$assignment_date) {
        $error = 'Assignment name and date are required.';
    } elseif (empty($input_scores)) {
        $error = 'Please enter roll number and score pairs.';
    } else {
        // Parse input: expected format "ROLL<TAB or SPACE or COMMA>SCORE" per line
        $lines = preg_split('/\r?\n/', $input_scores);
        $score_map = [];
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') continue;
            // Split by tab, then by comma, then by space
            if (strpos($line, "\t") !== false) {
                $parts = explode("\t", $line);
            } elseif (strpos($line, ",") !== false) {
                $parts = explode(",", $line);
            } else {
                $parts = preg_split('/\s+/', $line);
            }
            $parts = array_map(function($v) { return strtoupper(trim($v)); }, $parts);
            if (count($parts) == 2 && strlen($parts[0]) > 0 && is_numeric($parts[1])) {
                $score_map[$parts[0]] = floatval($parts[1]);
            }
        }
        // Prepare preview and DB data
        $attempted = 0;
        $total_score = 0;
        $preview_data = [];
        foreach ($students as $student) {
            $roll = strtoupper(trim($student['roll_no']));
            $score = isset($score_map[$roll]) ? $score_map[$roll] : 0;
            $status = isset($score_map[$roll]) ? 1 : 0;
            if ($status) {
                $attempted++;
                $total_score += $score;
            }
            $preview_data[] = [
                'id' => $student['id'],
                'roll_no' => $student['roll_no'],
                'name' => $student['name'],
                'score' => $score,
                'status' => $status
            ];
        }
        $summary = [
            'attempted' => $attempted,
            'total' => count($students),
            'average' => $attempted ? round($total_score / $attempted, 2) : 0
        ];

        // Save to DB if requested
        if (isset($_POST['save_assignment'])) {
            // Check for duplicate
            $stmt = $db->prepare("SELECT id FROM assignments WHERE section_id = ? AND assignment_name = ? AND assignment_date = ? LIMIT 1");
            $stmt->execute([$section_id, $assignment_name, $assignment_date]);
            if ($stmt->fetch()) {
                $error = 'Assignment for this section, name, and date already exists.';
            } else {
                try {
                    $db->beginTransaction();
                    foreach ($preview_data as $row) {
                        $stmt = $db->prepare("INSERT INTO assignments (section_id, assignment_name, assignment_date, student_id, score, status) VALUES (?, ?, ?, ?, ?, ?)");
                        $stmt->execute([$section_id, $assignment_name, $assignment_date, $row['id'], $row['score'], $row['status']]);
                    }
                    $db->commit();
                    $success = 'Assignment scores saved successfully!';
                } catch (Exception $e) {
                    $db->rollBack();
                    $error = 'Error saving assignment: ' . $e->getMessage();
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
    <title>Mark Assignment - <?php echo htmlspecialchars($section['name']); ?></title>
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
                    <div class="assignment-card">
                    <a href="dashboard.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
                        <h3 class="mb-4"><i class="fas fa-book"></i> Mark Assignment - <?php echo htmlspecialchars($section['name']); ?></h3>
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
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="assignment_name" class="form-label">Assignment Name *</label>
                                    <input type="text" class="form-control" id="assignment_name" name="assignment_name" value="<?php echo htmlspecialchars($_POST['assignment_name'] ?? ''); ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="assignment_date" class="form-label">Assignment Date *</label>
                                    <input type="date" class="form-control" id="assignment_date" name="assignment_date" value="<?php echo htmlspecialchars($_POST['assignment_date'] ?? date('Y-m-d')); ?>" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="input_scores" class="form-label">Enter Roll No and Score Pairs *</label>
                                <textarea class="form-control" id="input_scores" name="input_scores" rows="4" placeholder="e.g. 0128CS221026\t20\n0128CS221027\t21\n0128CS221028\t22" required><?php echo htmlspecialchars($_POST['input_scores'] ?? ''); ?></textarea>
                                <div class="form-text">Paste roll number and score pairs from Excel or Google Sheets || Use Formula for extract score if need =IF(C2<>"", LEFT(C2, FIND("/", C2)-1), "") || Where : C2(score cell)                            .</div>
                            </div>
                            <div class="d-flex justify-content-between">
                                
                                <button type="submit" name="save_assignment" class="btn btn-primary btn-submit"><i class="fas fa-save"></i> Save Assignment</button>
                            </div>
                        </form>
                        <?php if (!empty($preview_data)): ?>
                            <div class="mt-4">
                                <h5><i class="fas fa-eye"></i> Preview</h5>
                                <div class="table-responsive preview-table">
                                    <table class="table table-bordered table-hover">
                                        <thead>
                                            <tr>
                                                <th>Roll No</th>
                                                <th>Name</th>
                                                <th>Score</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($preview_data as $row): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($row['roll_no']); ?></td>
                                                    <td><?php echo htmlspecialchars($row['name']); ?></td>
                                                    <td><?php echo $row['score']; ?></td>
                                                    <td><?php echo $row['status']; ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="mt-3">
                                    <strong>Summary:</strong> <?php echo $summary['attempted']; ?> attempted out of <?php echo $summary['total']; ?> students. Average Score: <?php echo $summary['average']; ?>
                                </div>
                                <div class="mt-3">
                                    <button class="btn btn-info me-2" onclick="copyScores()"><i class="fas fa-copy"></i> Copy Scores</button>
                                    <button class="btn btn-info" onclick="copyStatuses()"><i class="fas fa-copy"></i> Copy Statuses</button>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <footer class="text-center mt-5 mb-3 text-muted small">&lt;GoG&gt; Smart Attendance Tracker Presented By Satish Nagar</footer>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function copyScores() {
        var scores = [];
        <?php if (!empty($preview_data)): ?>
            <?php foreach ($preview_data as $row): ?>
                scores.push('<?php echo $row['score']; ?>');
            <?php endforeach; ?>
        <?php endif; ?>
        if (scores.length) {
            navigator.clipboard.writeText(scores.join("\n")).then(function() {
                alert('Scores copied!');
            });
        }
    }
    function copyStatuses() {
        var statuses = [];
        <?php if (!empty($preview_data)): ?>
            <?php foreach ($preview_data as $row): ?>
                statuses.push('<?php echo $row['status']; ?>');
            <?php endforeach; ?>
        <?php endif; ?>
        if (statuses.length) {
            navigator.clipboard.writeText(statuses.join("\n")).then(function() {
                alert('Statuses copied!');
            });
        }
    }
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