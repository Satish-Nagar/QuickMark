<?php
require_once '../includes/functions.php';
requireAdmin();
require_once '../vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$admin_id = $_SESSION['user_id'];
$db = getDB();
$stmt = $db->prepare("SELECT profile_picture FROM admins WHERE id = ?");
$stmt->execute([$admin_id]);
$admin = $stmt->fetch();
$profile_picture = $admin && $admin['profile_picture']
    ? '../uploads/' . htmlspecialchars($admin['profile_picture'])
    : 'https://via.placeholder.com/40x40.png?text=Admin';

// Assignment filters
$assignment_colleges = $db->query("SELECT DISTINCT college FROM operation_managers ORDER BY college")->fetchAll();
$assignment_college = isset($_GET['assignment_college']) ? $_GET['assignment_college'] : '';
$assignment_date = isset($_GET['assignment_date']) ? $_GET['assignment_date'] : '';
$assignment_dates = [];
if ($assignment_college) {
    $stmt = $db->prepare("SELECT DISTINCT DATE(a.created_at) FROM assignments a JOIN sections s ON a.section_id = s.id JOIN operation_managers om ON s.om_id = om.id WHERE om.college = ? ORDER BY DATE(a.created_at) DESC");
    $stmt->execute([$assignment_college]);
    $assignment_dates = $stmt->fetchAll(PDO::FETCH_COLUMN);
}

// Assignment Data
$assignment_sections = [];
$assignment_submitted = 0;
$assignment_total = 0;
$show_all_colleges_today = false;
$all_colleges = [];
$all_submitted = 0;
$all_total = 0;
$today = date('Y-m-d');
if (!$assignment_college && !$assignment_date) {
    // Default: show all colleges for today
    $show_all_colleges_today = true;
    $stmt = $db->prepare("SELECT om.college, SUM(a.status) as submitted, COUNT(a.id) as total, AVG(a.score) as avg_score FROM assignments a JOIN sections s ON a.section_id = s.id JOIN operation_managers om ON s.om_id = om.id WHERE DATE(a.created_at) = ? GROUP BY om.college");
    $stmt->execute([$today]);
    $all_colleges = $stmt->fetchAll();
    $all_submitted = array_sum(array_column($all_colleges, 'submitted'));
    $all_total = array_sum(array_column($all_colleges, 'total'));
} elseif ($assignment_college && $assignment_date) {
    // Get all sections for the selected college
    $stmt = $db->prepare("SELECT s.id, s.name FROM sections s JOIN operation_managers om ON s.om_id = om.id WHERE om.college = ? ORDER BY s.name");
    $stmt->execute([$assignment_college]);
    $all_sections = $stmt->fetchAll(PDO::FETCH_ASSOC);
    // Get assignment scores for each section (LEFT JOIN to include sections with no assignments)
    $stmt = $db->prepare("SELECT s.id as section_id, s.name as section_name, AVG(a.score) as avg_score, COUNT(a.id) as total FROM sections s JOIN operation_managers om ON s.om_id = om.id LEFT JOIN assignments a ON a.section_id = s.id AND DATE(a.created_at) = ? WHERE om.college = ? GROUP BY s.id, s.name ORDER BY s.name");
    $stmt->execute([$assignment_date, $assignment_college]);
    $assignment_raw = $stmt->fetchAll(PDO::FETCH_ASSOC);
    // Build a map of section_id to assignment
    $assignment_map = [];
    foreach ($assignment_raw as $row) {
        $assignment_map[$row['section_id']] = $row;
    }
    // Ensure every section is present in the final array
    $assignment_sections = [];
    foreach ($all_sections as $section) {
        $sid = $section['id'];
        if (isset($assignment_map[$sid])) {
            $assignment_sections[] = $assignment_map[$sid];
            $assignment_submitted += (int)($assignment_map[$sid]['submitted'] ?? 0);
            $assignment_total += (int)($assignment_map[$sid]['total'] ?? 0);
        } else {
            $assignment_sections[] = [
                'section_id' => $sid,
                'section_name' => $section['name'],
                'avg_score' => 0,
                'submitted' => 0,
                'total' => 0
            ];
        }
    }
}

$best_section = null; $best_val = -1;
$low_section = null; $low_val = 101;
$overall_avg_score = 0;
if ($show_all_colleges_today) {
    $overall_avg_score = $all_total > 0 ? round(array_sum(array_map(fn($r) => $r['avg_score'] * $r['total'], $all_colleges)) / $all_total, 2) : 0;
    foreach ($all_colleges as $row) {
        $score = isset($row['avg_score']) ? (float)$row['avg_score'] : 0;
        if ($score > $best_val) { $best_val = $score; $best_section = $row['college']; }
        if ($score < $low_val) { $low_val = $score; $low_section = $row['college']; }
    }
} elseif ($assignment_college && $assignment_date) {
    $overall_avg_score = $assignment_total > 0 ? round(array_sum(array_map(fn($r) => $r['avg_score'] * $r['total'], $assignment_sections)) / $assignment_total, 2) : 0;
    foreach ($assignment_sections as $row) {
        $score = isset($row['avg_score']) ? (float)$row['avg_score'] : 0;
        if ($score > $best_val) { $best_val = $score; $best_section = $row['section_name']; }
        if ($score < $low_val) { $low_val = $score; $low_section = $row['section_name']; }
    }
}
?>
<?php
// Optional: define the current page for sidebar highlighting
$current_page = 'assignment_report.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Assignment Report - Smart Attendance System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet" />

    <style>
    /* Base styles */
    * {
        box-sizing: border-box;
    }

    body {
        margin: 0;
        background-color: #f8f9fa;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        overflow-x: hidden;
    }

    /* --- HEADER/NAVBAR FIX --- */
    .navbar {
        background: linear-gradient(45deg, #667eea, #764ba2);
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        position: fixed;
        top: 0;
        width: 100%;
        z-index: 1030;
    }
    /* --- MAIN CONTENT FIX --- */
    .main-content {
        margin-left: 10px;
        margin-top: 76px;
        /* padding: 30px 24px 30px 24px; */
        min-height: calc(100vh - 70px);
        background: #f8f9fa;
    }
    .report-card {
        background: #fff;
        border-radius: 15px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
        padding: 32px 28px;
        margin-bottom: 32px;
        margin-top: 0;
        animation: fadeInUp 1s cubic-bezier(.39,.575,.56,1.000);
    }
    .summary-cards .card {
        min-height: 120px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(102, 126, 234, 0.08);
    }
    .summary-cards .card .card-title {
        font-size: 1rem;
        font-weight: 600;
    }
    .summary-cards .card .card-text {
        font-size: 1.5rem;
        font-weight: 700;
    }
    .filter-form {
        background: #f4f6fb;
        border-radius: 10px;
        padding: 18px 18px 0 18px;
        margin-bottom: 30px;
    }
    @keyframes fadeInUp {
        0% { opacity: 0; transform: translateY(40px); }
        100% { opacity: 1; transform: translateY(0); }
    }
    /* --- LOGO SIZE FIX --- */
    .navbar-brand img {
        height: 40px !important;
        width: auto;
        margin-right: 10px;
        border-radius: 25%;
    }
    /* --- RESPONSIVE --- */
    @media (max-width: 991.98px) {
        .main-content {
            margin-left: 0;
            margin-top: 70px;
            padding: 15px;
        }
        .report-card {
            padding: 18px 8px;
        }
    }


    /* Layout container */
    .layout {
        display: flex;
        flex-wrap: nowrap;
        min-height: 100vh;
    }

    /* Sidebar */
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

    .sidebar .nav-link.active,
    .sidebar .nav-link:hover {
        background: linear-gradient(45deg, #667eea, #764ba2);
        color: white;
    }

    /* Main content */
    .main-content {
        margin-left: 80px;
        margin-top: 60px;
        padding: 30px;
    }


    .report-card {
        background: #fff;
        border-radius: 16px;
        box-shadow: 0 4px 24px rgba(102, 126, 234, 0.08);
        padding: 32px 24px;
        margin-top: 24px;
    }

    .summary-cards {
        margin-bottom: 24px;
    }

    /* Responsive Styles */
    @media (max-width: 991.98px) {
        .layout {
            flex-direction: column;
        }

        .sidebar {
            width: 100%;
            padding: 10px 0;
            display: flex;
            flex-direction: row;
            flex-wrap: wrap;
            justify-content: space-around;
            box-shadow: none;
            border-bottom: 1px solid #ddd;
        }

        .sidebar .nav-link {
            flex: 1 1 auto;
            margin: 5px;
            text-align: center;
            font-size: 0.95rem;
        }

        .main-content {
            padding: 15px;
        }
    }


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
            margin-left: 10px;
        }
    }
    </style>


    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
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
                        style="height:35px;width:auto;margin-right:10px;border-radius:25%;" />
                    <span class="fw-bold text-white">QuickMark</span>
                </a>
            </div>

            <!-- Profile dropdown -->
            <div class="nav-item dropdown d-flex align-items-center profile-dropdown">
                <img src="<?php echo $profile_picture; ?>" alt="Profile" class="rounded-circle me-2"
                    style="width:36px;height:36px;object-fit:cover;" />
                <a class="nav-link dropdown-toggle fw-bold text-white" href="#" role="button" data-bs-toggle="dropdown">
                    Admin
                </a>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user"></i> Profile</a></li>
                    <li>
                        <hr class="dropdown-divider" />
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
                        <a class="nav-link <?php echo $current_page == 'about.php' ? 'active' : ''; ?>"
                            href="about.php">
                            <i class="fas fa-question-circle"></i> About & Help
                        </a>
                    </nav>
                </div>
            </div>

            <!-- Main Content -->
            <div class="main-content">
                <div class="container" style="max-width: 1100px;">
                    <a href="dashboard.php" class="btn btn-secondary btn-sm mb-3">&larr; Back to Dashboard</a>
                    <div class="report-card">
                        <h2 class="mb-4">Assignment Report</h2>
                        <form class="row g-3 mb-4 filter-form" method="get" id="assignment-filter-form">
                            <div class="col-md-4">
                                <label for="assignment_college" class="form-label">College</label>
                                <select name="assignment_college" id="assignment_college" class="form-select">
                                    <option value="">Select College</option>
                                    <?php foreach (
                                        $assignment_colleges as $c): ?>
                                    <option value="<?php echo htmlspecialchars($c['college']); ?>"
                                        <?php if ($assignment_college == $c['college']) echo 'selected'; ?>>
                                        <?php echo htmlspecialchars($c['college']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="assignment_date" class="form-label">Date</label>
                                <select name="assignment_date" id="assignment_date" class="form-select"
                                    <?php if (!$assignment_college) echo 'disabled'; ?>>
                                    <option value="">Select Date</option>
                                    <?php if ($assignment_college): ?>
                                    <?php foreach ($assignment_dates as $d): ?>
                                    <option value="<?php echo $d; ?>"
                                        <?php if ($assignment_date == $d) echo 'selected'; ?>><?php echo $d; ?></option>
                                    <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </div>
                            <div class="col-md-4 align-self-end">
                                <button type="submit" class="btn btn-primary w-100">Show Report</button>
                            </div>
                        </form>
                        <!-- Summary Cards -->
                        <div class="row mb-4 summary-cards">
                            <div class="col-md-3">
                                <div class="card text-bg-primary mb-3">
                                    <div class="card-body text-center">
                                        <h6 class="card-title">Overall Avg. Score</h6>
                                        <p class="card-text fs-4"><?php echo $overall_avg_score; ?></p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card text-bg-success mb-3">
                                    <div class="card-body text-center">
                                        <h6 class="card-title">Total Submitted</h6>
                                        <p class="card-text fs-4">
                                            <?php echo $show_all_colleges_today ? $all_submitted : $assignment_submitted; ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card text-bg-danger mb-3">
                                    <div class="card-body text-center">
                                        <h6 class="card-title">Total Not Submitted</h6>
                                        <p class="card-text fs-4">
                                            <?php echo $show_all_colleges_today ? ($all_total - $all_submitted) : ($assignment_total - $assignment_submitted); ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card text-bg-info mb-3">
                                    <div class="card-body text-center">
                                        <h6 class="card-title">Best Section</h6>
                                        <p class="card-text fs-5">
                                            <?php echo $best_section ? htmlspecialchars($best_section) . ' (' . round($best_val, 2) . ')' : 'N/A'; ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- Assignment Table -->
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Section</th>
                                        <th>Average Score</th>
                                        <th>Submitted</th>
                                        <th>Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($assignment_sections as $row): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($row['section_name'] ?? $row['college'] ?? ''); ?></td>
                                        <td><?php echo isset($row['avg_score']) ? round($row['avg_score'], 2) : 0; ?></td>
                                        <td><?php echo $row['submitted'] ?? ($row['total'] ?? 0); ?></td>
                                        <td><?php echo $row['total'] ?? 0; ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script>
    $(function() {
        $('#assignment_college').on('change', function() {
            var college = $(this).val();
            if (college) {
                $.get('get_dates.php', {
                    college: college,
                    type: 'assignment'
                }, function(data) {
                    let dates = JSON.parse(data);
                    let options = '<option value="">Select Date</option>';
                    if (dates.length === 0) {
                        options += '<option>No data available</option>';
                    } else {
                        $.each(dates, function(i, d) {
                            options += `<option value="${d}">${d}</option>`;
                        });
                    }
                    $('#assignment_date').html(options).prop('disabled', false);
                });
            } else {
                $('#assignment_date').html('<option value="">Select Date</option>').prop('disabled', true);
            }
        });
    });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>


</body>

</html>