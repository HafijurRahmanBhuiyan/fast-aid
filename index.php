<?php
session_start();
require_once 'config/database.php';

if (isLoggedIn()) {
    switch ($_SESSION['role']) {
        case 'admin':
            redirect('admin/dashboard.php');
            break;
        case 'volunteer':
            redirect('volunteer/dashboard.php');
            break;
        case 'patient':
            redirect('patient/dashboard.php');
            break;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FastAid - Emergency Medical Assistance</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-danger">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-ambulance"></i> FastAid
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="signin.php">Sign In</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="signup.php">Sign Up</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="hero-section">
        <div class="container">
            <div class="row align-items-center min-vh-100">
                <div class="col-lg-6">
                    <h1 class="display-3 fw-bold text-white">FastAid</h1>
                    <h3 class="text-white-50 mb-4">Emergency Medical Assistance</h3>
                    <p class="lead text-white mb-4">
                        Connect with nearby medical volunteers instantly during emergencies. 
                        Get the help you need when you need it most.
                    </p>
                    <div class="d-flex gap-3">
                        <a href="signup.php?role=patient" class="btn btn-light btn-lg">Get Help</a>
                        <a href="signup.php?role=volunteer" class="btn btn-outline-light btn-lg">Become a Volunteer</a>
                    </div>
                </div>
                <div class="col-lg-6 text-center">
                    <i class="fas fa-heartbeat text-white" style="font-size: 200px; opacity: 0.3;"></i>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
