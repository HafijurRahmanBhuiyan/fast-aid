<?php
require_once '../config/database.php';

requireRole('volunteer');

$flash = getFlashMessage();
$userId = (int)$_SESSION['user_id'];

$checkStatus = $conn->prepare("SELECT status FROM volunteers WHERE id = ?");
$checkStatus->bind_param("i", $userId);
$checkStatus->execute();
$volunteerResult = $checkStatus->get_result();

if ($volunteerResult->num_rows === 0) {
    setFlashMessage("Volunteer not found.", "danger");
    redirect('../signin.php');
}

$volunteerData = $volunteerResult->fetch_assoc();
$volunteerStatus = $volunteerData['status'];

if ($volunteerStatus !== 'approved') {
    setFlashMessage("Your account is " . htmlEscape($volunteerStatus) . ". Please wait for admin approval.", "warning");
    redirect('../signin.php');
}

$filter = isset($_GET['filter']) && in_array($_GET['filter'], ['all', 'accepted', 'completed']) ? $_GET['filter'] : 'all';
$where = "WHERE sr.volunteer_id = ?";
if ($filter === 'accepted') {
    $where .= " AND sr.status = 'accepted'";
} elseif ($filter === 'completed') {
    $where .= " AND sr.status = 'completed'";
}

$requests = $conn->prepare("
    SELECT sr.*, p.name as patient_name, p.phone as patient_phone, p.location as patient_location, p.age as patient_age, p.gender as patient_gender
    FROM service_requests sr
    JOIN patients p ON sr.patient_id = p.id
    $where
    ORDER BY sr.request_time DESC
");
$requests->bind_param("i", $userId);
$requests->execute();
$requestsResult = $requests->get_result();

$allCount = $conn->prepare("SELECT COUNT(*) as c FROM service_requests WHERE volunteer_id = ?");
$allCount->bind_param("i", $userId);
$allCount->execute();
$allCountResult = $allCount->get_result()->fetch_assoc()['c'] ?? 0;

$acceptedCount = $conn->prepare("SELECT COUNT(*) as c FROM service_requests WHERE volunteer_id = ? AND status = 'accepted'");
$acceptedCount->bind_param("i", $userId);
$acceptedCount->execute();
$acceptedCountResult = $acceptedCount->get_result()->fetch_assoc()['c'] ?? 0;

$completedCount = $conn->prepare("SELECT COUNT(*) as c FROM service_requests WHERE volunteer_id = ? AND status = 'completed'");
$completedCount->bind_param("i", $userId);
$completedCount->execute();
$completedCountResult = $completedCount->get_result()->fetch_assoc()['c'] ?? 0;

$csrfToken = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Requests - FastAid Volunteer</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .sidebar { min-height: 100vh; background: linear-gradient(180deg, #1a1a2e 0%, #16213e 100%); color: white; }
        .sidebar a { color: rgba(255,255,255,0.7); text-decoration: none; padding: 15px 20px; display: block; transition: all 0.3s; border-left: 3px solid transparent; }
        .sidebar a:hover, .sidebar a.active { background: rgba(220, 53, 69, 0.2); color: white; border-left-color: #dc3545; }
        .request-card { border: none; border-radius: 12px; transition: all 0.3s; }
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
                    <h5 class="mt-2">FastAid</h5>
                    <small class="text-white-50">Volunteer Panel</small>
                </div>
                <a href="dashboard.php"><i class="fas fa-tachometer-alt me-2"></i> Dashboard</a>
                <a href="profile.php"><i class="fas fa-user me-2"></i> My Profile</a>
                <a href="requests.php" class="active"><i class="fas fa-file-medical-alt me-2"></i> My Requests</a>
                <a href="../includes/logout.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a>
            </div>
            
            <div class="col-md-9 p-4" style="background: #f8f9fa; min-height: 100vh;">
                <?php if ($flash): ?>
                    <div class="alert alert-<?php echo htmlEscape($flash['type']); ?> alert-dismissible fade show">
                        <?php echo htmlEscape($flash['message']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2 class="mb-0"><i class="fas fa-file-medical-alt me-2"></i>My Requests</h2>
                </div>
                
                <div class="row mb-4">
                    <div class="col-md-4">
                        <a href="?filter=all" class="text-decoration-none">
                            <div class="card border-0 bg-primary text-white stat-card">
                                <div class="card-body">
                                    <h6>All Requests</h6>
                                    <h3 class="mb-0"><?php echo (int)$allCountResult; ?></h3>
                                </div>
                            </div>
                        </a>
                    </div>
                    <div class="col-md-4">
                        <a href="?filter=accepted" class="text-decoration-none">
                            <div class="card border-0 bg-warning text-white stat-card">
                                <div class="card-body">
                                    <h6>Accepted</h6>
                                    <h3 class="mb-0"><?php echo (int)$acceptedCountResult; ?></h3>
                                </div>
                            </div>
                        </a>
                    </div>
                    <div class="col-md-4">
                        <a href="?filter=completed" class="text-decoration-none">
                            <div class="card border-0 bg-success text-white stat-card">
                                <div class="card-body">
                                    <h6>Completed</h6>
                                    <h3 class="mb-0"><?php echo (int)$completedCountResult; ?></h3>
                                </div>
                            </div>
                        </a>
                    </div>
                </div>
                
                <div class="card request-card">
                    <div class="card-header bg-white">
                        <ul class="nav nav-pills">
                            <li class="nav-item">
                                <a class="nav-link <?php echo $filter === 'all' ? 'active' : ''; ?>" href="?filter=all">All</a>
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
                                        <th>#</th>
                                        <th>Patient</th>
                                        <th>Age/Gender</th>
                                        <th>Location</th>
                                        <th>Phone</th>
                                        <th>Status</th>
                                        <th>Time</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    if ($requestsResult && $requestsResult->num_rows > 0):
                                        while ($r = $requestsResult->fetch_assoc()):
                                    ?>
                                    <tr>
                                        <td><strong>#<?php echo (int)$r['id']; ?></strong></td>
                                        <td><?php echo htmlEscape($r['patient_name']); ?></td>
                                        <td><?php echo (int)$r['patient_age']; ?>/<span class="text-capitalize"><?php echo htmlEscape($r['patient_gender']); ?></span></td>
                                        <td><?php echo htmlEscape($r['patient_location']); ?></td>
                                        <td>
                                            <a href="tel:<?php echo htmlEscape($r['patient_phone']); ?>" class="text-danger">
                                                <i class="fas fa-phone me-1"></i><?php echo htmlEscape($r['patient_phone']); ?>
                                            </a>
                                        </td>
                                        <td>
                                            <span class="status-badge status-<?php echo htmlEscape($r['status']); ?>">
                                                <?php echo ucfirst(htmlEscape($r['status'])); ?>
                                            </span>
                                        </td>
                                        <td><small class="text-muted"><?php echo htmlEscape(date('M d, Y H:i', strtotime($r['request_time']))); ?></small></td>
                                        <td>
                                            <?php if ($r['status'] === 'accepted'): ?>
                                                <form method="POST" action="complete_request.php" style="display:inline;">
                                                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                                    <input type="hidden" name="id" value="<?php echo (int)$r['id']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-success" onclick="return confirm('Mark this request as completed?')">
                                                        <i class="fas fa-check-double me-1"></i> Complete
                                                    </button>
                                                </form>
                                            <?php elseif ($r['status'] === 'completed'): ?>
                                                <span class="text-success"><i class="fas fa-check-circle"></i> Done</span>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php 
                                        endwhile;
                                    else:
                                    ?>
                                    <tr>
                                        <td colspan="8" class="text-center py-4 text-muted">
                                            <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
                                            No requests found
                                        </td>
                                    </tr>
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
