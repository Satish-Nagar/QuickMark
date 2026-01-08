<?php
require_once '../includes/functions.php';
requireAdmin();

$admin_id = $_SESSION['user_id'];
$db = getDB();
$stmt = $db->prepare("SELECT profile_picture FROM admins WHERE id = ?");
$stmt->execute([$admin_id]);
$admin = $stmt->fetch();
$profile_picture = $admin && $admin['profile_picture']
    ? '../uploads/' . htmlspecialchars($admin['profile_picture'])
    : 'https://via.placeholder.com/40x40.png?text=Admin';
$current_page = basename(__FILE__);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About & Help - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .navbar { background: linear-gradient(45deg, #667eea, #764ba2); box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
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
        .sidebar .nav-link.active, .sidebar .nav-link:hover { 
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
        }
        .sidebar .nav-link.profile-active { 
            background: linear-gradient(45deg, #43e97b, #38f9d7);
            color: white;
        }
        .main-content { 
            margin-left: 250px;
            margin-top: 70px;
            padding: 30px 24px 30px 24px;
            min-height: calc(100vh - 70px);
            background: #f8f9fa;
        }
        .container {
            max-width: 1100px;
            margin: 0 auto;
        }
        .footer {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            text-align: center;
            padding: 20px;
            margin-top: 50px;
            border-radius: 10px;
        }
        .footer h6 {
            margin-bottom: 5px;
            font-weight: 600;
        }
        .footer p {
            margin-bottom: 5px;
            opacity: 0.9;
        }
        .about-hero {
            background: linear-gradient(90deg, #667eea 0%, #43e97b 100%);
            color: #fff;
            border-radius: 18px;
            padding: 36px 32px 28px 32px;
            margin-bottom: 32px;
            box-shadow: 0 8px 32px rgba(60,60,120,0.10);
            display: flex;
            align-items: center;
            gap: 24px;
            animation: fadeInDown 1s cubic-bezier(.39,.575,.56,1.000);
        }
        .about-hero i { font-size: 3rem; margin-right: 18px; }
        .about-hero .about-title { font-size: 2.2rem; font-weight: bold; margin-bottom: 0; }
        @keyframes fadeInDown {
            0% { opacity: 0; transform: translateY(-40px); }
            100% { opacity: 1; transform: translateY(0); }
        }
        .about-card { background: #fff; border-radius: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.08); padding: 32px 28px; margin-bottom: 32px; animation: fadeInUp 1s cubic-bezier(.39,.575,.56,1.000); }
        @keyframes fadeInUp {
            0% { opacity: 0; transform: translateY(40px); }
            100% { opacity: 1; transform: translateY(0); }
        }
        .feature-icon { font-size: 2rem; color: #667eea; margin-right: 12px; }
        .about-section-title { font-size: 1.2rem; font-weight: 600; color: #764ba2; margin-top: 24px; margin-bottom: 10px; }
        .about-list { margin-bottom: 0; }
        .about-list li { margin-bottom: 8px; }
        .faq-question { font-weight: 600; color: #333; }
        .faq-answer { color: #555; margin-bottom: 18px; }
        .doc-link { font-weight: 600; color: #667eea; text-decoration: underline; }
        .doc-link:hover { color: #43e97b; }
        .quickstart-step { margin-bottom: 12px; }
        .troubleshoot-card { background: #f8f9fa; border-left: 4px solid #667eea; border-radius: 8px; padding: 16px 18px; margin-bottom: 16px; }
        .accordion-button:not(.collapsed) { color: #fff; background: linear-gradient(90deg, #667eea 0%, #43e97b 100%); }
        
    /* Mobile Sidebar Offcanvas */
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
             margin-left: 0; /* Remove margin-left on mobile */
         }
         
         .footer {
             margin-top: 30px;
             padding: 15px;
         }
     }
     
     /* Desktop specific styles */
     @media (min-width: 769px) {
         .main-content {
             margin-left: 250px; /* Restore margin-left on desktop */
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
                        <a class="nav-link<?php if ($current_page == 'dashboard.php') echo ' active'; ?>" href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                        <a class="nav-link<?php if ($current_page == 'register_om.php') echo ' active'; ?>" href="register_om.php"><i class="fas fa-user-plus"></i> Register OM</a>
                        <a class="nav-link<?php if ($current_page == 'bulk_register.php') echo ' active'; ?>" href="bulk_register.php"><i class="fas fa-upload"></i> Bulk Register</a>
                        <a class="nav-link<?php if ($current_page == 'manage_oms.php') echo ' active'; ?>" href="manage_oms.php"><i class="fas fa-users-cog"></i> Manage OMs</a>
                        <a class="nav-link<?php if ($current_page == 'attendance_report.php') echo ' active'; ?>" href="attendance_report.php"><i class="fas fa-chart-bar"></i> Attendance Report</a>
                        <a class="nav-link<?php if ($current_page == 'assignment_report.php') echo ' active'; ?>" href="assignment_report.php"><i class="fas fa-chart-bar"></i> Assignment Report</a>
                        <a class="nav-link<?php if ($current_page == 'settings.php') echo ' active'; ?>" href="settings.php"><i class="fas fa-cog"></i> Settings</a>
                       
                        <a class="nav-link<?php if ($current_page == 'about.php') echo ' active'; ?>" href="about.php"><i class="fas fa-question-circle"></i> About & Help</a>
                    </nav>
                </div>
            </div>
            <div class="col-md-9 col-lg-10 main-content">
                <div class="about-hero">
                    <i class="fas fa-question-circle"></i>
                    <div>
                        <div class="about-title">About & Help</div>
                        <div>Welcome to the <strong>Admin Panel</strong> of the Smart Attendance Automation System. Here you’ll find everything you need to manage, analyze, and support your institution’s attendance and assignment workflows.</div>
                    </div>
                </div>
                <div class="about-card">
                    <div class="about-section-title"><i class="fas fa-bolt feature-icon"></i> Quick Start for Admins</div>
                    <div class="quickstart-step"><strong>1.</strong> Use the sidebar to navigate between Dashboard, OM management, Reports, and Settings.</div>
                    <div class="quickstart-step"><strong>2.</strong> Register new OMs individually or in bulk from the respective pages.</div>
                    <div class="quickstart-step"><strong>3.</strong> Analyze attendance and assignment data using the Reports section.</div>
                    <div class="quickstart-step"><strong>4.</strong> Update your profile and system settings as needed.</div>
                    <div class="quickstart-step"><strong>5.</strong> For any issues, refer to the FAQ or contact support below.</div>
                </div>
                <div class="about-card">
                    <div class="about-section-title"><i class="fas fa-star feature-icon"></i> Feature Highlights</div>
                    <ul class="about-list">
                        <li><i class="fas fa-users-cog feature-icon"></i> Register, manage, and bulk import Operation Managers (OMs)</li>
                        <li><i class="fas fa-chart-bar feature-icon"></i> Real-time attendance and assignment analytics with export options</li>
                        <li><i class="fas fa-user-shield feature-icon"></i> Role-based access and security controls</li>
                        <li><i class="fas fa-cogs feature-icon"></i> System-wide configuration and settings</li>
                        <li><i class="fas fa-user feature-icon"></i> Admin profile management</li>
                        <li><i class="fas fa-envelope feature-icon"></i> Automated email notifications and password resets</li>
                    </ul>
                </div>
                <div class="about-card">
                    <div class="about-section-title"><i class="fas fa-book-open feature-icon"></i> Documentation</div>
                    <div class="accordion" id="docAccordion">
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingReadme">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseReadme" aria-expanded="false" aria-controls="collapseReadme">
                                    <i class="fas fa-file-alt me-2"></i> README
                                </button>
                            </h2>
                            <div id="collapseReadme" class="accordion-collapse collapse" aria-labelledby="headingReadme" data-bs-parent="#docAccordion">
                                <div class="accordion-body">
                                    <p>The <span class="doc-link">README</span> provides a complete technical and functional overview of the system, including setup instructions, database schema, security features, and troubleshooting tips.</p>
                                    <a href="../PASSWORD_RESET_README.md" target="_blank" class="doc-link"><i class="fas fa-external-link-alt me-1"></i> View README</a>
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingManual">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseManual" aria-expanded="false" aria-controls="collapseManual">
                                    <i class="fas fa-book me-2"></i> User Manual
                                </button>
                            </h2>
                            <div id="collapseManual" class="accordion-collapse collapse" aria-labelledby="headingManual" data-bs-parent="#docAccordion">
                                <div class="accordion-body">
                                    <p>The <span class="doc-link">User Manual</span> offers a step-by-step guide for all admin panel features, including screenshots and best practices for daily use.</p>
                                    <a href="../admin/about.php#" class="doc-link"><i class="fas fa-external-link-alt me-1"></i> View User Manual (Coming Soon)</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="about-card">
                    <div class="about-section-title"><i class="fas fa-tools feature-icon"></i> Troubleshooting</div>
                    <div class="troubleshoot-card"><strong>Problem:</strong> Email not received after OM registration.<br><strong>Solution:</strong> Check spam folder, verify SMTP settings in <code>config/mail.php</code>, and ensure your server can send emails.</div>
                    <div class="troubleshoot-card"><strong>Problem:</strong> Unable to login as admin.<br><strong>Solution:</strong> Use the password reset feature on the login page. If issues persist, contact support.</div>
                    <div class="troubleshoot-card"><strong>Problem:</strong> Data not updating or saving.<br><strong>Solution:</strong> Check your internet connection, reload the page, and ensure all required fields are filled.</div>
                </div>
                <div class="about-card">
                    <div class="about-section-title"><i class="fas fa-question feature-icon"></i> Frequently Asked Questions (FAQ)</div>
                    <div class="accordion" id="faqAccordion">
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="faq1">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFaq1" aria-expanded="false" aria-controls="collapseFaq1">
                                    How do I add a new Operation Manager?
                                </button>
                            </h2>
                            <div id="collapseFaq1" class="accordion-collapse collapse" aria-labelledby="faq1" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">Go to <strong>Register OM</strong> or <strong>Bulk Register</strong> in the sidebar and fill in the required details.</div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="faq2">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFaq2" aria-expanded="false" aria-controls="collapseFaq2">
                                    How can I view attendance reports?
                                </button>
                            </h2>
                            <div id="collapseFaq2" class="accordion-collapse collapse" aria-labelledby="faq2" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">Navigate to <strong>Attendance Report</strong> in the sidebar. Use filters to view data by college, section, or date.</div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="faq3">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFaq3" aria-expanded="false" aria-controls="collapseFaq3">
                                    How do I change my admin profile picture?
                                </button>
                            </h2>
                            <div id="collapseFaq3" class="accordion-collapse collapse" aria-labelledby="faq3" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">Go to <strong>Profile</strong> in the sidebar and upload a new image.</div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="faq4">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFaq4" aria-expanded="false" aria-controls="collapseFaq4">
                                    Who do I contact for technical support?
                                </button>
                            </h2>
                            <div id="collapseFaq4" class="accordion-collapse collapse" aria-labelledby="faq4" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">See the contact section below.</div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="faq5">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFaq5" aria-expanded="false" aria-controls="collapseFaq5">
                                    Where can I find more technical documentation?
                                </button>
                            </h2>
                            <div id="collapseFaq5" class="accordion-collapse collapse" aria-labelledby="faq5" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">See the <strong>Documentation</strong> section above for links to the README and user manual.</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="about-card">
                    <div class="about-section-title"><i class="fas fa-headset feature-icon"></i> Contact & Support</div>
                    <ul class="about-list">
                        <li><strong>Email:</strong> <a href="mailto:satish@geeksofgurukul.com" class="doc-link">satish@geeksofgurukul.com</a></li>
                        <!-- <li><strong>Documentation:</strong> <a href="../PASSWORD_RESET_README.md" target="_blank" class="doc-link">See the README and user manual for more details</a>.</li> -->
                                         </ul>
                 </div>
                 
                 <!-- Footer with Developer Information -->
                 <div class="footer">
                     <h6><i class="fas fa-code me-2"></i>Developed by</h6>
                     <p><strong>Satish Nagar</strong></p>
                     <p>Role: Operations Analyst</p>
                     <p>Organization: Geek of Gurukul</p>
                     <p><i class="fas fa-envelope me-1"></i>satish@geeksofgurukul.com</p>
                 </div>
             </div>
         </div>
     </div>
     <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
 </body>
 </html> 