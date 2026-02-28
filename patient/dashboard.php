<?php
require_once '../config/database.php';

requireRole('patient');

$flash = getFlashMessage();
$userId = (int)$_SESSION['user_id'];

$patientResult = $conn->prepare("SELECT location, phone FROM patients WHERE id = ?");
$patientResult->bind_param("i", $userId);
$patientResult->execute();
$patientData = $patientResult->get_result()->fetch_assoc();

if (!$patientData) {
    setFlashMessage("Patient not found.", "danger");
    redirect('../signin.php');
}

$patientLocation = $patientData['location'] ?? '';
$patientPhone = $patientData['phone'] ?? '';
$csrfToken = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Dashboard - FastAid</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .sidebar { min-height: 100vh; background: #1a1a2e; color: white; }
        .sidebar a { color: rgba(255,255,255,0.7); text-decoration: none; padding: 12px 20px; display: block; transition: all 0.3s; }
        .sidebar a:hover, .sidebar a.active { background: #dc3545; color: white; }
        .volunteer-card { transition: transform 0.2s, box-shadow 0.2s; cursor: pointer; border: 2px solid transparent; }
        .volunteer-card:hover { transform: translateY(-5px); box-shadow: 0 10px 30px rgba(0,0,0,0.15); }
        .volunteer-card.selected { border-color: #dc3545; background: #fff5f5; }
        .volunteer-card .degree-badge { background: #e9ecef; color: #495057; font-size: 0.75rem; padding: 4px 10px; border-radius: 20px; }
        .emergency-btn { background: linear-gradient(135deg, #dc3545, #c82333); border: none; font-weight: 600; padding: 12px 30px; }
        .emergency-btn:hover { background: linear-gradient(135deg, #c82333, #bd2130); }
        .emergency-btn:disabled { background: #6c757d; cursor: not-allowed; }
        .status-pending { background: #ffc107; color: #000; }
        .status-accepted { background: #28a745; color: #fff; }
        .status-completed { background: #17a2b8; color: #fff; }
        .status-cancelled { background: #dc3545; color: #fff; }
        .request-status-card { border-left: 4px solid #dc3545; }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-3 sidebar p-0">
                <div class="p-3 text-center border-bottom border-secondary">
                    <i class="fas fa-heart-pulse fa-2x text-danger"></i>
                    <h5 class="mt-2">FastAid Patient</h5>
                    <small class="text-white-50"><?php echo htmlEscape($_SESSION['name']); ?></small>
                </div>
                <a href="dashboard.php" class="active"><i class="fas fa-tachometer-alt me-2"></i> Dashboard</a>
                <a href="profile.php"><i class="fas fa-user me-2"></i> My Profile</a>
                <a href="../includes/logout.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a>
                
                <div class="p-3 mt-4 border-top border-secondary">
                    <small class="text-white-50">Your Location</small>
                    <div class="text-white fw-bold"><?php echo htmlEscape($patientLocation); ?></div>
                </div>
            </div>
            
            <div class="col-md-9 p-4">
                <div id="alertContainer"></div>
                
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2 class="mb-0">Emergency Assistance</h2>
                    <div>
                        <span class="loading-spinner active me-2">
                            <i class="fas fa-spinner fa-spin text-primary"></i>
                        </span>
                        <small class="text-muted"><i class="fas fa-sync me-1"></i> Live</small>
                    </div>
                </div>
                
                <div id="currentRequestSection"></div>
                
                <div id="requestFormSection">
                    <div class="card mb-4 bg-danger text-white">
                        <div class="card-body text-center py-4">
                            <i class="fas fa-ambulance fa-3x mb-3"></i>
                            <h4>Request Emergency Help</h4>
                            <p class="mb-4">Select a volunteer from nearby to send your emergency request</p>
                            
                            <form id="emergencyForm">
                                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                <input type="hidden" name="volunteer_id" id="selectedVolunteerId" required>
                                <div class="row justify-content-center">
                                    <div class="col-md-5">
                                        <input type="text" name="location" id="locationInput" class="form-control form-control-lg" 
                                               placeholder="Enter your current location" 
                                               value="<?php echo htmlEscape($patientLocation); ?>" required>
                                    </div>
                                    <div class="col-md-3">
                                        <button type="submit" name="send_request" class="btn btn-light btn-lg emergency-btn w-100" id="sendBtn" disabled>
                                            <i class="fas fa-paper-plane me-2"></i>Send Request
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <div class="card">
                        <div class="card-header bg-white d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><i class="fas fa-users text-danger me-2"></i>Nearby Volunteers</h5>
                            <span class="badge bg-secondary" id="volunteerCount">Loading...</span>
                        </div>
                        <div class="card-body">
                            <div id="volunteersList" class="row g-3"></div>
                        </div>
                    </div>
                </div>
                
                <div class="card mt-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="fas fa-history me-2"></i>Recent Requests</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table mb-0">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Location</th>
                                        <th>Volunteer</th>
                                        <th>Status</th>
                                        <th>Time</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody id="recentRequestsTable"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let selectedVolunteerId = null;
        const csrfToken = '<?php echo $csrfToken; ?>';
        const patientLocation = '<?php echo htmlEscape($patientLocation); ?>';
        
        function loadVolunteers() {
            const location = document.getElementById('locationInput').value || patientLocation;
            
            fetch(`../api/requests.php?action=get_volunteers&location=${encodeURIComponent(location)}`)
                .then(res => res.json())
                .then(data => {
                    const container = document.getElementById('volunteersList');
                    document.getElementById('volunteerCount').textContent = data.data ? data.data.length : 0;
                    
                    if (!data.data || data.data.length === 0) {
                        container.innerHTML = `
                            <div class="text-center py-5 text-muted w-100">
                                <i class="fas fa-user-slash fa-3x mb-3"></i>
                                <h5>No Volunteers Available</h5>
                                <p>No approved volunteers found in your area.</p>
                            </div>`;
                        return;
                    }
                    
                    container.innerHTML = data.data.map(v => `
                        <div class="col-md-6 col-lg-4">
                            <div class="card volunteer-card h-100" onclick="selectVolunteer(${v.id}, '${escapeHtml(v.name)}')">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div>
                                            <h6 class="mb-0">${escapeHtml(v.name)}</h6>
                                            <span class="degree-badge">${escapeHtml(v.degree)}</span>
                                        </div>
                                        <i class="fas fa-user-md fa-2x text-success"></i>
                                    </div>
                                    <div class="mt-3">
                                        <div class="d-flex align-items-center text-muted small mb-1">
                                            <i class="fas fa-phone me-2"></i>${escapeHtml(v.phone)}
                                        </div>
                                        <div class="d-flex align-items-center text-muted small">
                                            <i class="fas fa-map-marker-alt me-2"></i>${escapeHtml(v.location)}
                                        </div>
                                    </div>
                                    <div class="mt-3 text-center selected-indicator" id="indicator-${v.id}" style="display: none;">
                                        <span class="badge bg-danger"><i class="fas fa-check me-1"></i>Selected</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `).join('');
                })
                .catch(err => {
                    document.getElementById('volunteerCount').textContent = 'Error';
                });
        }
        
        function selectVolunteer(id, name) {
            document.querySelectorAll('.volunteer-card').forEach(card => card.classList.remove('selected'));
            document.querySelectorAll('.selected-indicator').forEach(ind => ind.style.display = 'none');
            
            event.currentTarget.classList.add('selected');
            document.getElementById('indicator-' + id).style.display = 'block';
            
            selectedVolunteerId = id;
            document.getElementById('selectedVolunteerId').value = id;
            document.getElementById('sendBtn').disabled = false;
            document.getElementById('sendBtn').innerHTML = `<i class="fas fa-paper-plane me-2"></i>Send to ${name}`;
        }
        
        document.getElementById('emergencyForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            formData.append('action', 'create_request');
            
            document.getElementById('sendBtn').disabled = true;
            document.getElementById('sendBtn').innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Sending...';
            
            fetch('../api/requests.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    showAlert('Emergency request sent successfully!', 'success');
                    document.getElementById('requestFormSection').style.display = 'none';
                    startStatusPolling();
                } else {
                    showAlert(data.message, 'danger');
                }
                document.getElementById('sendBtn').disabled = false;
                document.getElementById('sendBtn').innerHTML = '<i class="fas fa-paper-plane me-2"></i>Send Request';
            })
            .catch(err => {
                showAlert('Failed to send request. Please try again.', 'danger');
                document.getElementById('sendBtn').disabled = false;
                document.getElementById('sendBtn').innerHTML = '<i class="fas fa-paper-plane me-2"></i>Send Request';
            });
        });
        
        function startStatusPolling() {
            checkRequestStatus();
            setInterval(checkRequestStatus, 5000);
        }
        
        function checkRequestStatus() {
            fetch('../api/requests.php?action=get_request_status', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `csrf_token=${csrfToken}`
            })
            .then(res => res.json())
            .then(data => {
                if (data.success && data.data) {
                    updateRequestStatus(data.data);
                } else if (data.success && !data.data) {
                    document.getElementById('currentRequestSection').innerHTML = '';
                    document.getElementById('requestFormSection').style.display = 'block';
                }
            });
        }
        
        function cancelRequest(requestId) {
            if (!confirm('Are you sure you want to cancel this request? This action cannot be undone.')) {
                return;
            }
            
            const formData = new FormData();
            formData.append('action', 'cancel_request');
            formData.append('csrf_token', csrfToken);
            formData.append('request_id', requestId);
            
            fetch('../api/requests.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    showAlert('Request cancelled successfully', 'success');
                    document.getElementById('currentRequestSection').innerHTML = '';
                    document.getElementById('requestFormSection').style.display = 'block';
                    loadRecentRequests();
                } else {
                    showAlert(data.message, 'danger');
                }
            })
            .catch(err => {
                showAlert('Failed to cancel request. Please try again.', 'danger');
            });
        }
        
        function updateRequestStatus(request) {
            const section = document.getElementById('currentRequestSection');
            const statusBadge = request.status === 'pending' ? 'warning' : (request.status === 'accepted' ? 'success' : request.status === 'cancelled' ? 'danger' : 'info');
            const statusText = request.status === 'pending' ? 'Waiting for Volunteer' : 
                              request.status === 'accepted' ? 'Volunteer Accepted' :
                              request.status === 'cancelled' ? 'Cancelled' : 'Completed';
            
            const cancelButton = request.status === 'pending' ? `
                <button class="btn btn-outline-danger btn-sm ms-2" onclick="cancelRequest(${request.id})">
                    <i class="fas fa-times me-1"></i> Cancel Request
                </button>
            ` : '';
            
            section.innerHTML = `
                <div class="card request-status-card mb-4">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <h5 class="mb-2"><i class="fas fa-bell text-danger me-2"></i>Your Current Request #${request.id}</h5>
                                <div class="row">
                                    <div class="col-md-4">
                                        <small class="text-muted">Location</small>
                                        <div class="fw-bold">${escapeHtml(request.location)}</div>
                                    </div>
                                    <div class="col-md-4">
                                        <small class="text-muted">Volunteer</small>
                                        <div class="fw-bold">
                                            ${request.volunteer_name ? escapeHtml(request.volunteer_name) : '<span class="text-warning">Searching...</span>'}
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <small class="text-muted">Volunteer Phone</small>
                                        <div class="fw-bold">
                                            ${request.volunteer_phone ? `<a href="tel:${request.volunteer_phone}">${escapeHtml(request.volunteer_phone)}</a>` : '-'}
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4 text-md-end mt-3 mt-md-0">
                                <span class="badge fs-6 bg-${statusBadge} px-3 py-2">
                                    <i class="fas fa-${request.status === 'pending' ? 'clock' : request.status === 'accepted' ? 'check-circle' : request.status === 'cancelled' ? 'times-circle' : 'check-double'} me-1"></i> ${statusText}
                                </span>
                                ${cancelButton}
                                <div class="mt-2 text-muted">
                                    <small>Requested: ${new Date(request.request_time).toLocaleString()}</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            if (request.status === 'pending' || request.status === 'accepted' || request.status === 'cancelled') {
                document.getElementById('requestFormSection').style.display = 'none';
            }
        }
        
        function loadRecentRequests() {
            fetch('../api/requests.php?action=get_patient_requests', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `csrf_token=${csrfToken}`
            })
            .then(res => res.json())
            .then(data => {
                const tbody = document.getElementById('recentRequestsTable');
                if (data.success && data.data && data.data.length > 0) {
                    tbody.innerHTML = data.data.map(r => {
                        const cancelBtn = r.status === 'pending' ? `
                            <button class="btn btn-outline-danger btn-sm" onclick="cancelRequest(${r.id})">
                                <i class="fas fa-times"></i> Cancel
                            </button>
                        ` : '';
                        return `
                        <tr>
                            <td>#${r.id}</td>
                            <td>${escapeHtml(r.location)}</td>
                            <td>${r.volunteer_name ? escapeHtml(r.volunteer_name) : '<span class="text-muted">-</span>'}</td>
                            <td><span class="badge bg-${r.status === 'pending' ? 'warning' : r.status === 'accepted' ? 'success' : r.status === 'cancelled' ? 'danger' : 'info'}">${r.status.charAt(0).toUpperCase() + r.status.slice(1)}</span></td>
                            <td>${new Date(r.request_time).toLocaleString()}</td>
                            <td>${cancelBtn}</td>
                        </tr>
                    `}).join('');
                } else {
                    tbody.innerHTML = '<tr><td colspan="6" class="text-center py-4 text-muted">No requests yet</td></tr>';
                }
            })
            .catch(err => {
                document.getElementById('recentRequestsTable').innerHTML = '<tr><td colspan="6" class="text-center py-4 text-muted">Error loading requests</td></tr>';
            });
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
            loadVolunteers();
            loadRecentRequests();
            startStatusPolling();
        });
    </script>
</body>
</html>
