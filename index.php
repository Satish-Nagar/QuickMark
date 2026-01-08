<?php
session_start();

// Check if splash screen has been shown
if (!isset($_SESSION['splash_shown'])) {
    $_SESSION['splash_shown'] = true;
    // Show splash screen for first-time visitors
    include 'splash.php';
    exit;
}

// If splash has been shown, redirect to login
header('Location: login.php');
exit;
?> 