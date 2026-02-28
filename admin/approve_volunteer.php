<?php
session_start();
require_once '../config/database.php';

if (!isAdmin()) {
    redirect('../signin.php');
}

$id = intval($_GET['id']);
$action = $_GET['action'];

if ($action === 'approve') {
    $stmt = $conn->prepare("UPDATE volunteers SET status = 'approved' WHERE id = ?");
} elseif ($action === 'reject') {
    $stmt = $conn->prepare("UPDATE volunteers SET status = 'rejected' WHERE id = ?");
}

$stmt->bind_param("i", $id);
$stmt->execute();

redirect('dashboard.php');
