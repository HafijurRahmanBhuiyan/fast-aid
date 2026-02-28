<?php
require_once '../config/database.php';

requireRole('admin');

$flash = getFlashMessage();

$filter = isset($_GET['filter']) && in_array($_GET['filter'], ['all', 'pending', 'accepted', 'completed']) ? $_GET['filter'] : 'all';
$where = '';
if ($filter === 'pending') {
    $where = "WHERE sr.status = 'pending'";
} elseif ($filter === 'accepted') {
    $where = "WHERE sr.status = 'accepted'";
} elseif ($filter === 'completed') {
    $where = "WHERE sr.status = 'completed'";
}

$requests = $conn->query("
    SELECT sr.*, 
        p.name as patient_name, p.email as patient_email, p.phone as patient_phone, p.location as patient_location,
        v.name as volunteer_name, v.phone as volunteer_phone
    FROM service_requests sr
    JOIN patients p ON sr.patient_id = p.id
    LEFT JOIN volunteers v ON sr.volunteer_id = v.id
    $where 
    ORDER BY sr.request_time DESC
");

$stats = [
    'pending' => 0,
    'accepted' => 0,
    'completed' => 0,
    'total' => 0
];

$pendingResult = $conn->query("SELECT COUNT(*) as c FROM service_requests WHERE status = 'pending'");
if ($pendingResult) $stats['pending'] = $pendingResult->fetch_assoc()['c'];

$acceptedResult = $conn->query("SELECT COUNT(*) as c FROM service_requests WHERE status = 'accepted'");
if ($acceptedResult) $stats['accepted'] = $acceptedResult->fetch_assoc()['c'];

$completedResult = $conn->query("SELECT COUNT(*) as c FROM service_requests WHERE status = 'completed'");
if ($completedResult) $stats['completed'] = $completedResult->fetch_assoc()['c'];

$totalResult = $conn->query("SELECT COUNT(*) as c FROM service_requests");
if ($totalResult) $stats['total'] = $totalResult->fetch_assoc()['c'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Service Requests - FastAid Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .sidebar { min-height: 100vh; background: #1a1a2e; color: white; }
        .sidebar a { color: rgba(255,255,255,0.7); text-decoration: none; padding: 12px 20px; display: block; transition: all 0.3s; }
        .sidebar a:hover, .sidebar a.active { background: #dc3545; color: white; }
        .status-badge { padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-accepted { background: #d4edda; color: #155724; }
        .status-completed { background: #cce5ff; color: #004085; }
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
                <a href="patients.php"><i class="fas fa-users me-2"></i> Patients</a>
                <a href="requests.php" class="active"><i class="fas fa-file-medical me-2"></i> Requests</a>
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
                    <h2>Service Requests</h2>
                    <div class="badge bg-danger fs-6">Total: <?php echo (int)$stats['total']; ?></div>
                </div>
                
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="card border-0 bg-warning text-white">
                            <div class="card-body">
                                <h6>Pending</h6>
                                <h3><?php echo (int)$stats['pending']; ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card border-0 bg-success text-white">
                            <div class="card-body">
                                <h6>Accepted</h6>
                                <h3><?php echo (int)$stats['accepted']; ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card border-0 bg-info text-white">
                            <div class="card-body">
                                <h6>Completed</h6>
                                <h3><?php echo (int)$stats['completed']; ?></h3>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header bg-white">
                        <ul class="nav nav-pills">
                            <li class="nav-item">
                                <a class="nav-link <?php echo $filter === 'all' ? 'active' : ''; ?>" href="?filter=all">All</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo $filter === 'pending' ? 'active' : ''; ?>" href="?filter=pending">Pending</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo $filter === 'accepted' ? 'active' : ''; ?>" href="?filter=accepted">Accepted</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo $filter === 'completed' ? 'active' : ''; ?>" href="?filter=completed">Completed</a>
                            </li>
                        </ul>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table mb-0">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Patient</th>
                                        <th>Patient Phone</th>
                                        <th>Location</th>
                                        <th>Volunteer</th>
                                        <th>Volunteer Phone</th>
                                        <th>Status</th>
                                        <th>Request Time</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    if ($requests && $requests->num_rows > 0):
                                        while ($r = $requests->fetch_assoc()):
                                    ?>
                                    <tr>
                                        <td>#<?php echo (int)$r['id']; ?></td>
                                        <td><?php echo htmlEscape($r['patient_name']); ?></td>
                                        <td><?php echo htmlEscape($r['patient_phone']); ?></td>
                                        <td><?php echo htmlEscape($r['location']); ?></td>
                                        <td>
                                            <?php if ($r['volunteer_name']): ?>
                                                <?php echo htmlEscape($r['volunteer_name']); ?>
                                            <?php else: ?>
                                                <span class="text-muted">Not assigned</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($r['volunteer_phone']): ?>
                                                <?php echo htmlEscape($r['volunteer_phone']); ?>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="status-badge status-<?php echo htmlEscape($r['status']); ?>">
                                                <?php echo ucfirst(htmlEscape($r['status'])); ?>
                                            </span>
                                        </td>
                                        <td><?php echo htmlEscape(date('M d, Y H:i', strtotime($r['request_time']))); ?></td>
                                    </tr>
                                    <?php 
                                        endwhile;
                                    else:
                                    ?>
                                    <tr><td colspan="8" class="text-center py-4 text-muted">No requests found</td></tr>
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
