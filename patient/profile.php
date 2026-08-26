<?php
require_once '../config/database.php';

requireRole('patient');

$flash = getFlashMessage();
$userId = (int)$_SESSION['user_id'];

$patientResult = $conn->prepare("SELECT * FROM patients WHERE id = ?");
$patientResult->bind_param("i", $userId);
$patientResult->execute();
$patient = $patientResult->get_result()->fetch_assoc();

if (!$patient) {
    setFlashMessage("Patient not found.", "danger");
    redirect('../signin.php');
}

$csrfToken = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - FastAid</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .sidebar { min-height: 100vh; background: #1a1a2e; color: white; }
        .sidebar a { color: rgba(255,255,255,0.7); text-decoration: none; padding: 12px 20px; display: block; transition: all 0.3s; }
        .sidebar a:hover, .sidebar a.active { background: #dc3545; color: white; }
        .profile-header { background: linear-gradient(135deg, #dc3545 0%, #a71d2a 100%); border-radius: 15px; padding: 30px; color: white; }
        .info-card { border: none; border-radius: 12px; }
        .stat-card { border-radius: 12px; padding: 20px; }
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
                <a href="dashboard.php"><i class="fas fa-tachometer-alt me-2"></i> Dashboard</a>
                <a href="profile.php" class="active"><i class="fas fa-user me-2"></i> My Profile</a>
                <a href="../includes/logout.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a>
            </div>
            
            <div class="col-md-9 p-4">
                <div id="alertContainer"></div>
                
                <div class="profile-header mb-4">
                    <div class="row align-items-center">
                        <div class="col-md-2 text-center">
                            <i class="fas fa-user-circle fa-5x"></i>
                        </div>
                        <div class="col-md-10">
                            <h2 class="mb-1"><?php echo htmlEscape($patient['name']); ?></h2>
                            <p class="mb-2"><i class="fas fa-map-marker-alt me-2"></i><?php echo htmlEscape($patient['location']); ?></p>
                            <span class="badge bg-white text-danger">
                                <i class="fas fa-user me-1"></i> Patient Account
                            </span>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-4">
                        <div class="card info-card">
                            <div class="card-header bg-white">
                                <h5 class="mb-0"><i class="fas fa-user-edit me-2"></i>Edit Profile</h5>
                            </div>
                            <div class="card-body">
                                <form id="profileForm">
                                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Full Name</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fas fa-user"></i></span>
                                            <input type="text" name="name" id="nameInput" class="form-control" value="<?php echo htmlEscape($patient['name']); ?>" required>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Email</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                            <input type="email" class="form-control" value="<?php echo htmlEscape($patient['email']); ?>" disabled>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Phone</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fas fa-phone"></i></span>
                                            <input type="text" name="phone" id="phoneInput" class="form-control" value="<?php echo htmlEscape($patient['phone']); ?>" required>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Location</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fas fa-map-marker-alt"></i></span>
                                            <input type="text" name="location" id="locationInput" class="form-control" value="<?php echo htmlEscape($patient['location']); ?>" required>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Age</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fas fa-birthday-cake"></i></span>
                                            <input type="number" name="age" id="ageInput" class="form-control" value="<?php echo (int)$patient['age']; ?>" min="1" max="150" required>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Gender</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fas fa-venus-mars"></i></span>
                                            <input type="text" class="form-control" value="<?php echo ucfirst(htmlEscape($patient['gender'])); ?>" disabled>
                                        </div>
                                    </div>
                                    <button type="submit" class="btn btn-danger w-100" id="updateProfileBtn">
                                        <i class="fas fa-save me-2"></i>Update Profile
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6 mb-4">
                        <div class="card info-card">
                            <div class="card-header bg-white">
                                <h5 class="mb-0"><i class="fas fa-lock me-2"></i>Change Password</h5>
                            </div>
                            <div class="card-body">
                                <form id="passwordForm">
                                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Current Password</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                            <input type="password" name="current_password" class="form-control" required>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">New Password</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fas fa-key"></i></span>
                                            <input type="password" name="new_password" class="form-control" minlength="6" required>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Confirm New Password</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fas fa-key"></i></span>
                                            <input type="password" name="confirm_password" class="form-control" required>
                                        </div>
                                    </div>
                                    <button type="submit" class="btn btn-outline-danger w-100" id="changePasswordBtn">
                                        <i class="fas fa-key me-2"></i>Change Password
                                    </button>
                                </form>
                            </div>
                        </div>
                        
                        <div class="card info-card mt-4">
                            <div class="card-header bg-white">
                                <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Account Information</h5>
                            </div>
                            <div class="card-body">
                                <table class="table table-borderless">
                                    <tr>
                                        <td><strong>Member Since</strong></td>
                                        <td><?php echo htmlEscape(date('F d, Y', strtotime($patient['created_at']))); ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Account Status</strong></td>
                                        <td><span class="badge bg-success">Active</span></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const csrfToken = '<?php echo $csrfToken; ?>';
        
        document.getElementById('profileForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            formData.append('action', 'update_profile');
            
            const btn = document.getElementById('updateProfileBtn');
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Updating...';
            
            fetch('../api/requests.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                showAlert(data.message, data.success ? 'success' : 'danger');
                if (data.success) {
                    document.getElementById('nameInput').value = formData.get('name');
                    document.getElementById('phoneInput').value = formData.get('phone');
                    document.getElementById('locationInput').value = formData.get('location');
                    document.getElementById('ageInput').value = formData.get('age');
                }
            })
            .finally(() => {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-save me-2"></i>Update Profile';
            });
        });
        
        document.getElementById('passwordForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            formData.append('action', 'change_password');
            
            if (formData.get('new_password') !== formData.get('confirm_password')) {
                showAlert('New passwords do not match', 'danger');
                return;
            }
            
            const btn = document.getElementById('changePasswordBtn');
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Changing...';
            
            fetch('../api/requests.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                showAlert(data.message, data.success ? 'success' : 'danger');
                if (data.success) {
                    this.reset();
                }
            })
            .finally(() => {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-key me-2"></i>Change Password';
            });
        });
        
        function showAlert(message, type) {
            const container = document.getElementById('alertContainer');
            container.innerHTML = `
                <div class="alert alert-${type} alert-dismissible fade show">
                    ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>`;
            setTimeout(() => container.innerHTML = '', 5000);
        }
    </script>
</body>
</html>
