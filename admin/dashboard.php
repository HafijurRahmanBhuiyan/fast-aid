<?php
require_once '../config/database.php';

requireRole('admin');

$flash = getFlashMessage();

$volunteerStats = [
    'pending' => 0,
    'approved' => 0,
    'rejected' => 0
];

$statsQuery = $conn->query("SELECT status, COUNT(*) as count FROM volunteers GROUP BY status");
if ($statsQuery) {
    while ($row = $statsQuery->fetch_assoc()) {
        $volunteerStats[$row['status']] = $row['count'];
    }
}

$totalPatients = $conn->query("SELECT COUNT(*) as count FROM patients");
$totalPatients = $totalPatients ? $totalPatients->fetch_assoc()['count'] : 0;

$totalRequests = $conn->query("SELECT COUNT(*) as count FROM service_requests");
$totalRequests = $totalRequests ? $totalRequests->fetch_assoc()['count'] : 0;

$pendingRequests = $conn->query("SELECT COUNT(*) as count FROM service_requests WHERE status = 'pending'");
$pendingRequests = $pendingRequests ? $pendingRequests->fetch_assoc()['count'] : 0;

$csrfToken = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - FastAid</title>
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
                <a href="dashboard.php" class="active"><i class="fas fa-tachometer-alt me-2"></i> Dashboard</a>
                <a href="volunteers.php"><i class="fas fa-user-md me-2"></i> Volunteers</a>
                <a href="patients.php"><i class="fas fa-users me-2"></i> Patients</a>
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
                
                <h2 class="mb-4">Dashboard</h2>
                
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card border-0 bg-warning text-white">
                            <div class="card-body">
                                <h6>Pending Volunteers</h6>
                                <h3><?php echo (int)$volunteerStats['pending']; ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border-0 bg-success text-white">
                            <div class="card-body">
                                <h6>Approved Volunteers</h6>
                                <h3><?php echo (int)$volunteerStats['approved']; ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border-0 bg-info text-white">
                            <div class="card-body">
                                <h6>Total Patients</h6>
                                <h3><?php echo (int)$totalPatients; ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border-0 bg-danger text-white">
                            <div class="card-body">
                                <h6>Pending Requests</h6>
                                <h3><?php echo (int)$pendingRequests; ?></h3>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Pending Volunteer Approvals</h5>
                    </div>
                    <div class="card-body p-0">
                        <table class="table mb-0">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Degree</th>
                                    <th>Registered</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $pending = $conn->query("SELECT * FROM volunteers WHERE status = 'pending' ORDER BY created_at DESC LIMIT 10");
                                if ($pending && $pending->num_rows > 0):
                                    while ($v = $pending->fetch_assoc()):
                                ?>
                                <tr>
                                    <td><?php echo htmlEscape($v['name']); ?></td>
                                    <td><?php echo htmlEscape($v['email']); ?></td>
                                    <td><?php echo htmlEscape($v['phone']); ?></td>
                                    <td><?php echo htmlEscape($v['degree']); ?></td>
                                    <td><?php echo htmlEscape(date('M d, Y', strtotime($v['created_at']))); ?></td>
                                    <td>
                                        <form method="POST" action="approve_volunteer.php" style="display:inline;">
                                            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                            <input type="hidden" name="id" value="<?php echo (int)$v['id']; ?>">
                                            <input type="hidden" name="action" value="approve">
                                            <button type="submit" class="btn btn-sm btn-success" onclick="return confirm('Approve this volunteer?')">
                                                <i class="fas fa-check"></i> Approve
                                            </button>
                                        </form>
                                        <form method="POST" action="approve_volunteer.php" style="display:inline;">
                                            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                            <input type="hidden" name="id" value="<?php echo (int)$v['id']; ?>">
                                            <input type="hidden" name="action" value="reject">
                                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Reject this volunteer?')">
                                                <i class="fas fa-times"></i> Reject
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                <?php 
                                    endwhile;
                                else:
                                ?>
                                <tr><td colspan="6" class="text-center py-4 text-muted">No pending volunteers</td></tr>
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
