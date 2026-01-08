<?php
require_once '../includes/functions.php';
requireAdmin();

$db = getDB();
$college = $_GET['college'] ?? '';
$type = $_GET['type'] ?? 'attendance'; // 'attendance' or 'assignment'

if (!$college) {
    echo json_encode([]);
    exit;
}

$dates = [];

if ($type === 'attendance') {
    // Get attendance dates for the college
    $stmt = $db->prepare("SELECT DISTINCT a.date FROM attendance a 
                         JOIN sections s ON a.section_id = s.id 
                         JOIN operation_managers om ON s.om_id = om.id 
                         WHERE om.college = ? 
                         ORDER BY a.date DESC");
    $stmt->execute([$college]);
    $dates = $stmt->fetchAll(PDO::FETCH_COLUMN);
} else {
    // Get assignment dates for the college
    $stmt = $db->prepare("SELECT DISTINCT DATE(a.created_at) as date FROM assignments a 
                         JOIN sections s ON a.section_id = s.id 
                         JOIN operation_managers om ON s.om_id = om.id 
                         WHERE om.college = ? 
                         ORDER BY DATE(a.created_at) DESC");
    $stmt->execute([$college]);
    $dates = $stmt->fetchAll(PDO::FETCH_COLUMN);
}

header('Content-Type: application/json');
echo json_encode($dates);
?> 