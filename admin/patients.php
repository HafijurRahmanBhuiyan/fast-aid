<?php
require_once '../config/database.php';

requireRole('admin');

$flash = getFlashMessage();

$patients = $conn->query("
    SELECT p.*, 
        (SELECT COUNT(*) FROM service_requests sr WHERE sr.patient_id = p.id) as total_requests,
        (SELECT COUNT(*) FROM service_requests sr WHERE sr.patient_id = p.id AND sr.status = 'completed') as completed_requests
    FROM patients p 
    ORDER BY p.created_at DESC
");

$totalPatients = $conn->query("SELECT COUNT(*) as count FROM patients");
$totalPatients = $totalPatients ? $totalPatients->fetch_assoc()['count'] : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patients - FastAid Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .sidebar { min-height: 100vh; background: #1a1a2e; color: white; }
        .sidebar a { color: rgba(255,255,255,0.7); text-decoration: none; padding: 12px 20px; display: block; transition: all 0.3s; }
        .sidebar a:hover, .sidebar a.active { background: #dc3545; color: white; }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-3 sidebar p-0">
                <div class="p-3 text-center border-bottom border-secondary">
                    <i class="fas fa-heart-pulse fa-2x text-danger"></i>
                    <h5 class="mt-2">FastAid Admin</h5>
                    <small class="text-white-50"><?php echo htmlEscape($_SESSION['name']); ?></small>
                </div>
                <a href="dashboard.php"><i class="fas fa-tachometer-alt me-2"></i> Dashboard</a>
                <a href="volunteers.php"><i class="fas fa-user-md me-2"></i> Volunteers</a>
                <a href="patients.php" class="active"><i class="fas fa-users me-2"></i> Patients</a>
                <a href="requests.php"><i class="fas fa-file-medical me-2"></i> Requests</a>
                <a href="../includes/logout.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a>
            </div>
            
            <div class="col-md-9 p-4">
                <?php if ($flash): ?>
                    <div class="alert alert-<?php echo htmlEscape($flash['type']); ?> alert-dismissible fade show">
                        <?php echo htmlEscape($flash['message']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>Patient Management</h2>
                    <div class="badge bg-danger fs-6">Total: <?php echo (int)$totalPatients; ?></div>
                </div>
                
                <div class="card">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">All Registered Patients</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table mb-0">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Phone</th>
                                        <th>Age</th>
                                        <th>Gender</th>
                                        <th>Location</th>
                                        <th>Requests</th>
                                        <th>Completed</th>
                                        <th>Registered</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    if ($patients && $patients->num_rows > 0):
                                        while ($p = $patients->fetch_assoc()):
                                    ?>
                                    <tr>
                                        <td>#<?php echo (int)$p['id']; ?></td>
                                        <td><?php echo htmlEscape($p['name']); ?></td>
                                        <td><?php echo htmlEscape($p['email']); ?></td>
                                        <td><?php echo htmlEscape($p['phone']); ?></td>
                                        <td><?php echo (int)$p['age']; ?></td>
                                        <td><span class="text-capitalize"><?php echo htmlEscape($p['gender']); ?></span></td>
                                        <td><?php echo htmlEscape($p['location']); ?></td>
                                        <td><span class="badge bg-primary"><?php echo (int)$p['total_requests']; ?></span></td>
                                        <td><span class="badge bg-success"><?php echo (int)$p['completed_requests']; ?></span></td>
                                        <td><?php echo htmlEscape(date('M d, Y', strtotime($p['created_at']))); ?></td>
                                    </tr>
                                    <?php 
                                        endwhile;
                                    else:
                                    ?>
                                    <tr><td colspan="10" class="text-center py-4 text-muted">No patients found</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
