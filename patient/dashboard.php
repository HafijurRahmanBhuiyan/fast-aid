<?php
session_start();
require_once '../config/database.php';

if (!isPatient()) {
    redirect('../signin.php');
}

$userId = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_help'])) {
    $location = sanitize($_POST['location']);
    
    $stmt = $conn->prepare("INSERT INTO service_requests (patient_id, location) VALUES (?, ?)");
    $stmt->bind_param("is", $userId, $location);
    $stmt->execute();
    $success = "Emergency request sent to nearby volunteers!";
}

$myRequests = $conn->query("
    SELECT sr.*, v.name as volunteer_name, v.phone as volunteer_phone, v.location as volunteer_location
    FROM service_requests sr
    LEFT JOIN volunteers v ON sr.volunteer_id = v.id
    WHERE sr.patient_id = $userId
    ORDER BY sr.request_time DESC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Dashboard - FastAid</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-3 sidebar p-0">
                <div class="p-3 text-center border-bottom border-secondary">
                    <i class="fas fa-ambulance fa-2x text-danger"></i>
                    <h5 class="mt-2">FastAid Patient</h5>
                    <small><?php echo $_SESSION['name']; ?></small>
                </div>
                <a href="dashboard.php" class="active"><i class="fas fa-tachometer-alt me-2"></i> Dashboard</a>
                <a href="requests.php"><i class="fas fa-file-medical me-2"></i> My Requests</a>
                <a href="../includes/logout.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a>
            </div>
            
            <div class="col-md-9 p-4">
                <h2 class="mb-4">Welcome, <?php echo $_SESSION['name']; ?>!</h2>
                
                <?php if (isset($success)): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <div class="card mb-4 bg-danger text-white">
                    <div class="card-body text-center py-5">
                        <i class="fas fa-ambulance fa-4x mb-3"></i>
                        <h3>Need Emergency Help?</h3>
                        <p class="mb-4">Request assistance from nearby medical volunteers</p>
                        
                        <form method="POST" class="row justify-content-center">
                            <div class="col-md-6">
                                <input type="text" name="location" class="form-control form-control-lg" placeholder="Enter your current location" required>
                            </div>
                            <div class="col-md-3">
                                <button type="submit" name="request_help" class="btn btn-light btn-lg w-100">
                                    <i class="fas fa-paper-plane me-2"></i>Request Help
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h5>My Request History</h5>
                    </div>
                    <div class="card-body">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Request ID</th>
                                    <th>Location</th>
                                    <th>Volunteer</th>
                                    <th>Status</th>
                                    <th>Time</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                if ($myRequests->num_rows > 0):
                                    while ($r = $myRequests->fetch_assoc()):
                                ?>
                                <tr>
                                    <td>#<?php echo $r['id']; ?></td>
                                    <td><?php echo htmlspecialchars($r['location']); ?></td>
                                    <td><?php echo $r['volunteer_name'] ? htmlspecialchars($r['volunteer_name']) : 'Waiting...'; ?></td>
                                    <td><span class="status-badge status-<?php echo $r['status']; ?>"><?php echo ucfirst($r['status']); ?></span></td>
                                    <td><?php echo date('M d, H:i', strtotime($r['request_time'])); ?></td>
                                </tr>
                                <?php 
                                    endwhile;
                                else:
                                ?>
                                <tr><td colspan="5" class="text-center">No requests yet</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
