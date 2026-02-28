<?php
require_once '../config/database.php';
require_once '../config/notifications.php';

requireRole('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    setFlashMessage("Invalid request method.", "danger");
    redirect('dashboard.php');
}

if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
    setFlashMessage("Invalid CSRF token. Please try again.", "danger");
    redirect('dashboard.php');
}

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$action = isset($_POST['action']) ? $_POST['action'] : '';

if (!$id || !in_array($action, ['approve', 'reject'])) {
    setFlashMessage("Invalid request.", "danger");
    redirect('dashboard.php');
}

$checkStmt = $conn->prepare("SELECT name FROM volunteers WHERE id = ?");
$checkStmt->bind_param("i", $id);
$checkStmt->execute();
$result = $checkStmt->get_result();

if ($result->num_rows === 0) {
    setFlashMessage("Volunteer not found.", "danger");
    redirect('dashboard.php');
}

$volunteer = $result->fetch_assoc();

if ($action === 'approve') {
    $stmt = $conn->prepare("UPDATE volunteers SET status = 'approved' WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    
    $notificationService->getVolunteerApprovalEmail($id);
    
    setFlashMessage(htmlEscape($volunteer['name']) . " has been approved! An email notification has been sent.", "success");
} elseif ($action === 'reject') {
    $stmt = $conn->prepare("UPDATE volunteers SET status = 'rejected' WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    setFlashMessage(htmlEscape($volunteer['name']) . " has been rejected.", "warning");
}

redirect('dashboard.php');
