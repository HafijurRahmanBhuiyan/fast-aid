<?php
require_once '../config/database.php';

requireRole('admin');

$flash = getFlashMessage();

$filter = isset($_GET['filter']) && in_array($_GET['filter'], ['all', 'pending', 'approved', 'rejected']) ? $_GET['filter'] : 'all';
$where = '';
if ($filter === 'pending') {
    $where = "WHERE v.status = 'pending'";
} elseif ($filter === 'approved') {
    $where = "WHERE v.status = 'approved'";
} elseif ($filter === 'rejected') {
    $where = "WHERE v.status = 'rejected'";
}

$volunteers = $conn->query("SELECT v.*, 
    (SELECT COUNT(*) FROM service_requests sr WHERE sr.volunteer_id = v.id) as total_requests
    FROM volunteers v $where ORDER BY v.created_at DESC");

$stats = ['pending' => 0, 'approved' => 0, 'rejected' => 0];
$statsResult = $conn->query("SELECT status, COUNT(*) as count FROM volunteers GROUP BY status");
if ($statsResult) {
    while ($row = $statsResult->fetch_assoc()) {
        $stats[$row['status']] = $row['count'];
    }
}

$csrfToken = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Volunteers - FastAid Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .sidebar { min-height: 100vh; background: #1a1a2e; color: white; }
        .sidebar a { color: rgba(255,255,255,0.7); text-decoration: none; padding: 12px 20px; display: block; transition: all 0.3s; }
        .sidebar a:hover, .sidebar a.active { background: #dc3545; color: white; }
        .status-badge { padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-approved { background: #d4edda; color: #155724; }
        .status-rejected { background: #f8d7da; color: #721c24; }
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
                <a href="volunteers.php" class="active"><i class="fas fa-user-md me-2"></i> Volunteers</a>
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
                
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>Volunteer Management</h2>
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
                                <h6>Approved</h6>
                                <h3><?php echo (int)$stats['approved']; ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card border-0 bg-danger text-white">
                            <div class="card-body">
                                <h6>Rejected</h6>
                                <h3><?php echo (int)$stats['rejected']; ?></h3>
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
                                <a class="nav-link <?php echo $filter === 'approved' ? 'active' : ''; ?>" href="?filter=approved">Approved</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo $filter === 'rejected' ? 'active' : ''; ?>" href="?filter=rejected">Rejected</a>
                            </li>
                        </ul>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table mb-0">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Phone</th>
                                        <th>Location</th>
                                        <th>Degree</th>
                                        <th>Certificate</th>
                                        <th>Status</th>
                                        <th>Requests</th>
                                        <th>Registered</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    if ($volunteers && $volunteers->num_rows > 0):
                                        while ($v = $volunteers->fetch_assoc()):
                                    ?>
                                    <tr>
                                        <td><?php echo htmlEscape($v['name']); ?></td>
                                        <td><?php echo htmlEscape($v['email']); ?></td>
                                        <td><?php echo htmlEscape($v['phone']); ?></td>
                                        <td><?php echo htmlEscape($v['location']); ?></td>
                                        <td><?php echo htmlEscape($v['degree']); ?></td>
                                        <td>
                                            <?php if ($v['certificate_file']): ?>
                                                <a href="../<?php echo htmlEscape($v['certificate_file']); ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-file-alt"></i> View
                                                </a>
                                            <?php else: ?>
                                                <span class="text-muted">No file</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="status-badge status-<?php echo htmlEscape($v['status']); ?>">
                                                <?php echo ucfirst(htmlEscape($v['status'])); ?>
                                            </span>
                                        </td>
                                        <td><?php echo (int)$v['total_requests']; ?></td>
                                        <td><?php echo htmlEscape(date('M d, Y', strtotime($v['created_at']))); ?></td>
                                        <td>
                                            <?php if ($v['status'] === 'pending'): ?>
                                                <form method="POST" action="approve_volunteer.php" style="display:inline;">
                                                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                                    <input type="hidden" name="id" value="<?php echo (int)$v['id']; ?>">
                                                    <input type="hidden" name="action" value="approve">
                                                    <button type="submit" class="btn btn-sm btn-success" onclick="return confirm('Approve this volunteer?')">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                </form>
                                                <form method="POST" action="approve_volunteer.php" style="display:inline;">
                                                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                                    <input type="hidden" name="id" value="<?php echo (int)$v['id']; ?>">
                                                    <input type="hidden" name="action" value="reject">
                                                    <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Reject this volunteer?')">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                </form>
                                            <?php elseif ($v['status'] === 'approved'): ?>
                                                <form method="POST" action="approve_volunteer.php" style="display:inline;">
                                                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                                    <input type="hidden" name="id" value="<?php echo (int)$v['id']; ?>">
                                                    <input type="hidden" name="action" value="reject">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Reject this volunteer?')">
                                                        <i class="fas fa-ban"></i>
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <form method="POST" action="approve_volunteer.php" style="display:inline;">
                                                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                                    <input type="hidden" name="id" value="<?php echo (int)$v['id']; ?>">
                                                    <input type="hidden" name="action" value="approve">
                                                    <button type="submit" class="btn btn-sm btn-outline-success" onclick="return confirm('Approve this volunteer?')">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php 
                                        endwhile;
                                    else:
                                    ?>
                                    <tr><td colspan="10" class="text-center py-4 text-muted">No volunteers found</td></tr>
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
