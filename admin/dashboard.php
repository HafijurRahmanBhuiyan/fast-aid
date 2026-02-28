<?php
session_start();
require_once '../config/database.php';

if (!isAdmin()) {
    redirect('../signin.php');
}

$volunteerStats = [
    'pending' => 0,
    'approved' => 0,
    'rejected' => 0
];

$statsQuery = $conn->query("SELECT status, COUNT(*) as count FROM volunteers GROUP BY status");
while ($row = $statsQuery->fetch_assoc()) {
    $volunteerStats[$row['status']] = $row['count'];
}

$totalPatients = $conn->query("SELECT COUNT(*) as count FROM patients")->fetch_assoc()['count'];
$totalRequests = $conn->query("SELECT COUNT(*) as count FROM service_requests")->fetch_assoc()['count'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - FastAid</title>
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
                    <h5 class="mt-2">FastAid Admin</h5>
                </div>
                <a href="dashboard.php" class="active"><i class="fas fa-tachometer-alt me-2"></i> Dashboard</a>
                <a href="volunteers.php"><i class="fas fa-user-md me-2"></i> Volunteers</a>
                <a href="patients.php"><i class="fas fa-users me-2"></i> Patients</a>
                <a href="requests.php"><i class="fas fa-file-medical me-2"></i> Requests</a>
                <a href="../includes/logout.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a>
            </div>
            
            <div class="col-md-9 p-4">
                <h2 class="mb-4">Dashboard</h2>
                
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card bg-primary text-white">
                            <div class="card-body">
                                <h6>Pending Volunteers</h6>
                                <h3><?php echo $volunteerStats['pending']; ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-success text-white">
                            <div class="card-body">
                                <h6>Approved Volunteers</h6>
                                <h3><?php echo $volunteerStats['approved']; ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-info text-white">
                            <div class="card-body">
                                <h6>Total Patients</h6>
                                <h3><?php echo $totalPatients; ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-warning text-white">
                            <div class="card-body">
                                <h6>Total Requests</h6>
                                <h3><?php echo $totalRequests; ?></h3>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h5>Recent Pending Volunteers</h5>
                    </div>
                    <div class="card-body">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Degree</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $pending = $conn->query("SELECT * FROM volunteers WHERE status = 'pending' LIMIT 5");
                                while ($v = $pending->fetch_assoc()):
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($v['name']); ?></td>
                                    <td><?php echo htmlspecialchars($v['email']); ?></td>
                                    <td><?php echo htmlspecialchars($v['phone']); ?></td>
                                    <td><?php echo htmlspecialchars($v['degree']); ?></td>
                                    <td>
                                        <a href="approve_volunteer.php?id=<?php echo $v['id']; ?>&action=approve" class="btn btn-sm btn-success">Approve</a>
                                        <a href="approve_volunteer.php?id=<?php echo $v['id']; ?>&action=reject" class="btn btn-sm btn-danger">Reject</a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
