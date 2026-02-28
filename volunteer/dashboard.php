<?php
session_start();
require_once '../config/database.php';

if (!isVolunteer()) {
    redirect('../signin.php');
}

$userId = $_SESSION['user_id'];

$myRequests = $conn->query("
    SELECT sr.*, p.name as patient_name, p.location as patient_location, p.phone as patient_phone
    FROM service_requests sr
    JOIN patients p ON sr.patient_id = p.id
    WHERE sr.volunteer_id = $userId
    ORDER BY sr.request_time DESC
");

$availableRequests = $conn->query("
    SELECT sr.*, p.name as patient_name, p.location as patient_location, p.phone as patient_phone
    FROM service_requests sr
    JOIN patients p ON sr.patient_id = p.id
    WHERE sr.status = 'pending'
    ORDER BY sr.request_time DESC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Volunteer Dashboard - FastAid</title>
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
                    <h5 class="mt-2">FastAid Volunteer</h5>
                    <small><?php echo $_SESSION['name']; ?></small>
                </div>
                <a href="dashboard.php" class="active"><i class="fas fa-tachometer-alt me-2"></i> Dashboard</a>
                <a href="requests.php"><i class="fas fa-file-medical me-2"></i> My Requests</a>
                <a href="../includes/logout.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a>
            </div>
            
            <div class="col-md-9 p-4">
                <h2 class="mb-4">Welcome, <?php echo $_SESSION['name']; ?>!</h2>
                
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="card bg-success text-white">
                            <div class="card-body">
                                <h6>Accepted Requests</h6>
                                <h3><?php echo $conn->query("SELECT COUNT(*) as c FROM service_requests WHERE volunteer_id = $userId AND status = 'accepted'")->fetch_assoc()['c']; ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-info text-white">
                            <div class="card-body">
                                <h6>Completed</h6>
                                <h3><?php echo $conn->query("SELECT COUNT(*) as c FROM service_requests WHERE volunteer_id = $userId AND status = 'completed'")->fetch_assoc()['c']; ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-warning text-white">
                            <div class="card-body">
                                <h6>Available</h6>
                                <h3><?php echo $conn->query("SELECT COUNT(*) as c FROM service_requests WHERE status = 'pending'")->fetch_assoc()['c']; ?></h3>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="card mb-4">
                    <div class="card-header bg-danger text-white">
                        <h5 class="mb-0"><i class="fas fa-bell me-2"></i> Available Emergency Requests</h5>
                    </div>
                    <div class="card-body">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Patient</th>
                                    <th>Location</th>
                                    <th>Phone</th>
                                    <th>Time</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                if ($availableRequests->num_rows > 0):
                                    while ($r = $availableRequests->fetch_assoc()):
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($r['patient_name']); ?></td>
                                    <td><?php echo htmlspecialchars($r['patient_location']); ?></td>
                                    <td><?php echo htmlspecialchars($r['patient_phone']); ?></td>
                                    <td><?php echo date('M d, H:i', strtotime($r['request_time'])); ?></td>
                                    <td>
                                        <a href="accept_request.php?id=<?php echo $r['id']; ?>" class="btn btn-sm btn-success">Accept</a>
                                    </td>
                                </tr>
                                <?php 
                                    endwhile;
                                else:
                                ?>
                                <tr><td colspan="5" class="text-center">No pending requests</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h5>My Accepted Requests</h5>
                    </div>
                    <div class="card-body">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Patient</th>
                                    <th>Location</th>
                                    <th>Phone</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                if ($myRequests->num_rows > 0):
                                    while ($r = $myRequests->fetch_assoc()):
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($r['patient_name']); ?></td>
                                    <td><?php echo htmlspecialchars($r['patient_location']); ?></td>
                                    <td><?php echo htmlspecialchars($r['patient_phone']); ?></td>
                                    <td><span class="status-badge status-<?php echo $r['status']; ?>"><?php echo ucfirst($r['status']); ?></span></td>
                                    <td>
                                        <?php if ($r['status'] === 'accepted'): ?>
                                            <a href="complete_request.php?id=<?php echo $r['id']; ?>" class="btn btn-sm btn-primary">Mark Complete</a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php 
                                    endwhile;
                                else:
                                ?>
                                <tr><td colspan="5" class="text-center">No accepted requests</td></tr>
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
