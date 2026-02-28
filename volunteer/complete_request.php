<?php
session_start();
require_once '../config/database.php';

if (!isVolunteer()) {
    redirect('../signin.php');
}

$requestId = intval($_GET['id']);

$stmt = $conn->prepare("UPDATE service_requests SET status = 'completed' WHERE id = ?");
$stmt->bind_param("i", $requestId);
$stmt->execute();

redirect('dashboard.php');
