<?php
session_start();
require_once '../config/database.php';

if (!isVolunteer()) {
    redirect('../signin.php');
}

$requestId = intval($_GET['id']);
$volunteerId = $_SESSION['user_id'];

$stmt = $conn->prepare("UPDATE service_requests SET volunteer_id = ?, status = 'accepted' WHERE id = ?");
$stmt->bind_param("ii", $volunteerId, $requestId);
$stmt->execute();

redirect('dashboard.php');
