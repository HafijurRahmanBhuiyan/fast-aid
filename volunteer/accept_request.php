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

$checkRequest = $conn->prepare("SELECT id, status FROM service_requests WHERE id = ? AND status = 'pending'");
$checkRequest->bind_param("i", $requestId);
$checkRequest->execute();

if ($checkRequest->get_result()->num_rows === 0) {
    setFlashMessage("Request not found or already accepted.", "warning");
    redirect('dashboard.php');
}

$stmt = $conn->prepare("UPDATE service_requests SET volunteer_id = ?, status = 'accepted' WHERE id = ? AND status = 'pending'");
$stmt->bind_param("ii", $volunteerId, $requestId);

if ($stmt->execute()) {
    setFlashMessage("Request accepted! Please proceed to the patient's location.", "success");
} else {
    setFlashMessage("Failed to accept request.", "danger");
}

redirect('dashboard.php');
