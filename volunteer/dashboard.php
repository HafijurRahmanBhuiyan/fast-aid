<?php
require_once '../config/database.php';

requireRole('volunteer');

$flash = getFlashMessage();
$userId = (int)$_SESSION['user_id'];

$checkStatus = $conn->prepare("SELECT * FROM volunteers WHERE id = ?");
$checkStatus->bind_param("i", $userId);
$checkStatus->execute();
$volunteerResult = $checkStatus->get_result();

if ($volunteerResult->num_rows === 0) {
    setFlashMessage("Volunteer not found.", "danger");
    redirect('../signin.php');
}

$volunteer = $volunteerResult->fetch_assoc();

if ($volunteer['status'] !== 'approved') {
    setFlashMessage("Your account is " . htmlEscape($volunteer['status']) . ". Please wait for admin approval.", "warning");
    redirect('../signin.php');
}

$csrfToken = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Volunteer Dashboard - FastAid</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .sidebar { min-height: 100vh; background: linear-gradient(180deg, #1a1a2e 0%, #16213e 100%); color: white; }
        .sidebar a { color: rgba(255,255,255,0.7); text-decoration: none; padding: 15px 20px; display: block; transition: all 0.3s; border-left: 3px solid transparent; }
        .sidebar a:hover, .sidebar a.active { background: rgba(220, 53, 69, 0.2); color: white; border-left-color: #dc3545; }
        .profile-card { background: linear-gradient(135deg, #dc3545 0%, #a71d2a 100%); border-radius: 15px; padding: 20px; color: white; }
        .stat-card { border-radius: 12px; padding: 20px; transition: transform 0.3s; }
        .stat-card:hover { transform: translateY(-5px); }
        .request-card { border: none; border-radius: 12px; transition: all 0.3s; }
        .request-card:hover { box-shadow: 0 5px 20px rgba(0,0,0,0.1); }
        .status-badge { padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-accepted { background: #d4edda; color: #155724; }
        .status-completed { background: #cce5ff; color: #004085; }
        #map { height: 400px; width: 100%; border-radius: 10px; }
        .notification-badge { animation: pulse 2s infinite; }
        @keyframes pulse { 0%, 100% { transform: scale(1); } 50% { transform: scale(1.1); } }
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
                <a href="dashboard.php" class="active"><i class="fas fa-tachometer-alt me-2"></i> Dashboard</a>
                <a href="profile.php"><i class="fas fa-user me-2"></i> My Profile</a>
                <a href="requests.php"><i class="fas fa-file-medical-alt me-2"></i> My Requests</a>
                <a href="../includes/logout.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a>
                
                <div class="mt-auto p-3 border-top border-secondary">
                    <div class="profile-card text-center">
                        <div class="mb-2">
                            <i class="fas fa-user-circle fa-3x"></i>
                        </div>
                        <h6 class="mb-1"><?php echo htmlEscape($volunteer['name']); ?></h6>
                        <small class="text-white-50"><?php echo htmlEscape($volunteer['degree']); ?></small>
                    </div>
                </div>
            </div>
            
            <div class="col-md-9 p-4" style="background: #f8f9fa; min-height: 100vh;">
                <div id="alertContainer"></div>
                
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2 class="mb-0">Dashboard</h2>
                    <div>
                        <span class="loading-spinner active me-2">
                            <i class="fas fa-spinner fa-spin text-primary"></i>
                        </span>
                        <span class="badge bg-success"><i class="fas fa-check-circle me-1"></i> Approved Volunteer</span>
                    </div>
                </div>
                
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="stat-card card border-0 bg-warning text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-0">Available</h6>
                                        <h3 class="mb-0" id="availableCount">0</h3>
                                    </div>
                                    <i class="fas fa-bell fa-2x opacity-50"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stat-card card border-0 bg-success text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-0">Accepted</h6>
                                        <h3 class="mb-0" id="acceptedCount">0</h3>
                                    </div>
                                    <i class="fas fa-hand-holding-medical fa-2x opacity-50"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stat-card card border-0 bg-info text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-0">Completed</h6>
                                        <h3 class="mb-0" id="completedCount">0</h3>
                                    </div>
                                    <i class="fas fa-check-circle fa-2x opacity-50"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row mb-4">
                    <div class="col-md-8">
                        <div class="card request-card">
                            <div class="card-header bg-danger text-white d-flex justify-content-between align-items-center">
                                <h5 class="mb-0"><i class="fas fa-bell me-2"></i> Emergency Requests</h5>
                                <span class="badge bg-white text-danger notification-badge" id="newBadge" style="display: none;">
                                    <span id="newCount">0</span> New
                                </span>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table mb-0">
                                        <thead>
                                            <tr>
                                                <th>Patient</th>
                                                <th>Age</th>
                                                <th>Location</th>
                                                <th>Phone</th>
                                                <th>Time</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody id="availableRequestsTable"></tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card request-card">
                            <div class="card-header bg-white">
                                <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Status</h5>
                            </div>
                            <div class="card-body text-center p-4">
                                <i class="fas fa-bell fa-3x text-primary mb-3"></i>
                                <p class="text-muted">Emergency requests will appear here automatically.</p>
                                <small class="text-muted">Requests refresh every 10 seconds.</small>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="card request-card">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="fas fa-tasks me-2"></i> My Active Requests</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table mb-0">
                                <thead>
                                    <tr>
                                        <th>Patient</th>
                                        <th>Age</th>
                                        <th>Location</th>
                                        <th>Phone</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody id="myRequestsTable"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const csrfToken = '<?php echo $csrfToken; ?>';
        
        function loadAvailableRequests() {
            fetch('../api/requests.php?action=get_nearby_requests')
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        updateAvailableRequests(data.data);
                    }
                });
        }
        
        function updateAvailableRequests(requests) {
            document.getElementById('availableCount').textContent = requests.length;
            
            if (requests.length > 0) {
                document.getElementById('newBadge').style.display = 'inline';
                document.getElementById('newCount').textContent = requests.length;
            } else {
                document.getElementById('newBadge').style.display = 'none';
            }
            
            const tbody = document.getElementById('availableRequestsTable');
            
            if (requests.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="6" class="text-center py-4 text-muted">
                            <i class="fas fa-check-circle fa-2x mb-2 d-block text-success"></i>
                            No pending requests
                        </td>
                    </tr>`;
                return;
            }
            
            tbody.innerHTML = requests.map(r => `
                <tr>
                    <td><strong>${escapeHtml(r.patient_name)}</strong></td>
                    <td>${r.patient_age} yrs</td>
                    <td>${escapeHtml(r.patient_location)}</td>
                    <td><a href="tel:${escapeHtml(r.patient_phone)}" class="text-danger"><i class="fas fa-phone me-1"></i>${escapeHtml(r.patient_phone)}</a></td>
                    <td><small class="text-muted">${new Date(r.request_time).toLocaleString()}</small></td>
                    <td>
                        <button class="btn btn-sm btn-success" onclick="acceptRequest(${r.id})">
                            <i class="fas fa-check me-1"></i> Accept
                        </button>
                    </td>
                </tr>`).join('');
        }
        
        function loadMyRequests() {
            fetch('../api/requests.php?action=get_my_requests', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `csrf_token=${csrfToken}`
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    updateMyRequests(data.data || []);
                }
            });
        }
        
        function updateMyRequests(requests) {
            document.getElementById('acceptedCount').textContent = requests.filter(r => r.status === 'accepted').length;
            document.getElementById('completedCount').textContent = requests.filter(r => r.status === 'completed').length;
            
            const tbody = document.getElementById('myRequestsTable');
            
            const activeRequests = requests.filter(r => r.status !== 'completed');
            
            if (activeRequests.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="6" class="text-center py-4 text-muted">
                            <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
                            No active requests
                        </td>
                    </tr>`;
                return;
            }
            
            tbody.innerHTML = activeRequests.map(r => `
                <tr>
                    <td><strong>${escapeHtml(r.patient_name)}</strong></td>
                    <td>${r.patient_age} yrs</td>
                    <td>${escapeHtml(r.patient_location)}</td>
                    <td><a href="tel:${escapeHtml(r.patient_phone)}" class="text-danger"><i class="fas fa-phone me-1"></i>${escapeHtml(r.patient_phone)}</a></td>
                    <td><span class="status-badge status-${r.status}">${r.status.charAt(0).toUpperCase() + r.status.slice(1)}</span></td>
                    <td>
                        ${r.status === 'accepted' ? `
                        <button class="btn btn-sm btn-primary" onclick="completeRequest(${r.id})">
                            <i class="fas fa-check-double me-1"></i> Complete
                        </button>` : ''}
                    </td>
                </tr>
            `).join('');
        }
        
        function acceptRequest(requestId) {
            if (!confirm('Accept this emergency request?')) return;
            
            let lat = null, lng = null;
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition((pos) => {
                    lat = pos.coords.latitude;
                    lng = pos.coords.longitude;
                    submitAcceptRequest(requestId, lat, lng);
                }, () => submitAcceptRequest(requestId, null, null));
            } else {
                submitAcceptRequest(requestId, null, null);
            }
        }
        
        function submitAcceptRequest(requestId, lat, lng) {
            const formData = new FormData();
            formData.append('action', 'accept_request');
            formData.append('csrf_token', csrfToken);
            formData.append('request_id', requestId);
            if (lat) formData.append('lat', lat);
            if (lng) formData.append('lng', lng);
            
            fetch('../api/requests.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                showAlert(data.message, data.success ? 'success' : 'danger');
                if (data.success) {
                    loadAvailableRequests();
                    loadMyRequests();
                }
            });
        }
        
        function completeRequest(requestId) {
            if (!confirm('Mark this request as completed?')) return;
            
            const formData = new FormData();
            formData.append('action', 'complete_request');
            formData.append('csrf_token', csrfToken);
            formData.append('request_id', requestId);
            
            fetch('../api/requests.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                showAlert(data.message, data.success ? 'success' : 'danger');
                if (data.success) {
                    loadMyRequests();
                }
            });
        }
        
        function startPolling() {
            setInterval(() => {
                loadAvailableRequests();
                loadMyRequests();
            }, 10000);
        }
        
        function showAlert(message, type) {
            const container = document.getElementById('alertContainer');
            container.innerHTML = `
                <div class="alert alert-${type} alert-dismissible fade show">
                    ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>`;
            setTimeout(() => container.innerHTML = '', 5000);
        }
        
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text || '';
            return div.innerHTML;
        }
        
        window.addEventListener('load', function() {
            loadAvailableRequests();
            loadMyRequests();
            startPolling();
        });
    </script>
</body>
</html>
