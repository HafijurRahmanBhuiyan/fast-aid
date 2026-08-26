<?php
require_once '../config/database.php';

requireRole('volunteer');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    setFlashMessage("Invalid request method.", "danger");
    redirect('dashboard.php');
}

if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
    setFlashMessage("Invalid CSRF token. Please try again.", "danger");
    redirect('dashboard.php');
}

$requestId = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$volunteerId = (int)$_SESSION['user_id'];

if (!$requestId) {
    setFlashMessage("Invalid request.", "danger");
    redirect('dashboard.php');
}

$checkRequest = $conn->prepare("SELECT id FROM service_requests WHERE id = ? AND volunteer_id = ? AND status = 'accepted'");
$checkRequest->bind_param("ii", $requestId, $volunteerId);
$checkRequest->execute();

if ($checkRequest->get_result()->num_rows === 0) {
    setFlashMessage("Request not found or already completed.", "warning");
    redirect('dashboard.php');
}

$stmt = $conn->prepare("UPDATE service_requests SET status = 'completed' WHERE id = ? AND volunteer_id = ? AND status = 'accepted'");
$stmt->bind_param("ii", $requestId, $volunteerId);

if ($stmt->execute()) {
    setFlashMessage("Request marked as completed. Great job saving lives!", "success");
} else {
    setFlashMessage("Failed to update request.", "danger");
}

redirect('dashboard.php');
