<?php
require_once 'config/database.php';

if (isLoggedIn()) {
    redirect('index.php');
}

$error = '';
$success = '';
$role = isset($_GET['role']) && in_array($_GET['role'], ['patient', 'volunteer']) ? $_GET['role'] : 'patient';
$csrfToken = generateCSRFToken();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        $error = "Invalid request. Please try again.";
    } else {
        $role = in_array($_POST['role'], ['patient', 'volunteer']) ? $_POST['role'] : 'patient';
        $name = sanitizeInput($_POST['name'] ?? '');
        $email = sanitizeInput($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        if (!validateRequired($name) || !validateLength($name, 2, 100)) {
            $error = "Please enter a valid name (2-100 characters).";
        } elseif (!validateEmail($email)) {
            $error = "Please enter a valid email address.";
        } elseif (!validateRequired($password) || strlen($password) < 6) {
            $error = "Password must be at least 6 characters.";
        } elseif ($password !== $confirmPassword) {
            $error = "Passwords do not match.";
        } else {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            if ($role === 'volunteer') {
                $phone = sanitizeInput($_POST['phone'] ?? '');
                $location = sanitizeInput($_POST['location'] ?? '');
                $degree = sanitizeInput($_POST['degree'] ?? '');
                
                if (!validateRequired($phone) || !validatePhone($phone)) {
                    $error = "Please enter a valid phone number.";
                } elseif (!validateRequired($location) || !validateLength($location, 2, 255)) {
                    $error = "Please enter a valid location.";
                } elseif (!validateRequired($degree) || !validateLength($degree, 2, 100)) {
                    $error = "Please enter your degree/qualification.";
                } else {
                    $checkEmail = $conn->prepare("SELECT id FROM volunteers WHERE email = ?");
                    $checkEmail->bind_param("s", $email);
                    $checkEmail->execute();
                    
                    if ($checkEmail->get_result()->num_rows > 0) {
                        $error = "Email already registered as a volunteer.";
                    } else {
                        if (!isset($_FILES['certificate']) || $_FILES['certificate']['error'] !== UPLOAD_ERR_OK) {
                            $error = "Certificate upload is required.";
                        } else {
                            $uploadResult = uploadFile($_FILES['certificate'], 'uploads/certificates');
                            
                            if (!$uploadResult['success']) {
                                $error = implode(', ', $uploadResult['errors']);
                            } else {
                                $stmt = $conn->prepare("INSERT INTO volunteers (name, email, password, phone, location, degree, certificate_file, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')");
                                $stmt->bind_param("sssssss", $name, $email, $hashedPassword, $phone, $location, $degree, $uploadResult['path']);
                                
                                if ($stmt->execute()) {
                                    $success = "Registration successful! Your account is pending approval by admin.";
                                } else {
                                    $error = "Registration failed. Please try again.";
                                }
                            }
                        }
                    }
                }
            } elseif ($role === 'patient') {
                $phone = sanitizeInput($_POST['phone'] ?? '');
                $age = isset($_POST['age']) ? (int)$_POST['age'] : 0;
                $gender = sanitizeInput($_POST['gender'] ?? '');
                $location = sanitizeInput($_POST['location'] ?? '');
                
                if (!validateRequired($phone) || !validatePhone($phone)) {
                    $error = "Please enter a valid phone number.";
                } elseif (!validateAge($age)) {
                    $error = "Please enter a valid age (1-150).";
                } elseif (!validateGender($gender)) {
                    $error = "Please select a valid gender.";
                } elseif (!validateRequired($location) || !validateLength($location, 2, 255)) {
                    $error = "Please enter a valid location.";
                } else {
                    $checkEmail = $conn->prepare("SELECT id FROM patients WHERE email = ?");
                    $checkEmail->bind_param("s", $email);
                    $checkEmail->execute();
                    
                    if ($checkEmail->get_result()->num_rows > 0) {
                        $error = "Email already registered as a patient.";
                    } else {
                        $stmt = $conn->prepare("INSERT INTO patients (name, email, password, phone, age, gender, location) VALUES (?, ?, ?, ?, ?, ?, ?)");
                        $stmt->bind_param("ssssiss", $name, $email, $hashedPassword, $phone, $age, $gender, $location);
                        
                        if ($stmt->execute()) {
                            $success = "Registration successful! You can now sign in.";
                        } else {
                            $error = "Registration failed. Please try again.";
                        }
                    }
                }
            } else {
                $error = "Invalid registration type.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - FastAid</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .auth-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #dc3545 0%, #8b1a24 100%);
            padding: 20px;
        }
        .auth-box {
            background: white;
            border-radius: 15px;
            padding: 40px;
            width: 100%;
            max-width: 500px;
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.2);
        }
        .nav-pills .nav-link {
            border-radius: 25px;
            padding: 10px 25px;
            margin: 0 5px;
            color: #6c757d;
            font-weight: 500;
        }
        .nav-pills .nav-link.active {
            background-color: #dc3545;
        }
        .input-group-text {
            background-color: #f8f9fa;
            border-color: #dee2e6;
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="auth-box">
            <div class="text-center mb-4">
                <i class="fas fa-heart-pulse fa-3x text-danger"></i>
                <h2 class="mt-3 fw-bold">FastAid</h2>
                <p class="text-muted">Create your account</p>
            </div>
            
            <ul class="nav nav-pills mb-4 justify-content-center">
                <li class="nav-item">
                    <a class="nav-link <?php echo $role === 'patient' ? 'active' : ''; ?>" href="?role=patient">
                        <i class="fas fa-user me-1"></i>Patient
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $role === 'volunteer' ? 'active' : ''; ?>" href="?role=volunteer">
                        <i class="fas fa-user-md me-1"></i>Volunteer
                    </a>
                </li>
            </ul>
            
            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle me-2"></i><?php echo htmlEscape($error); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle me-2"></i><?php echo htmlEscape($success); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                <input type="hidden" name="role" value="<?php echo htmlEscape($role); ?>">
                
                <div class="mb-3">
                    <label class="form-label">Full Name</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-user"></i></span>
                        <input type="text" name="name" class="form-control" placeholder="Enter your full name" required minlength="2" maxlength="100">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Email Address</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                        <input type="email" name="email" class="form-control" placeholder="Enter your email" required>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Phone Number</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-phone"></i></span>
                        <input type="tel" name="phone" class="form-control" placeholder="Enter phone number" required>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Location / Address</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-map-marker-alt"></i></span>
                        <input type="text" name="location" class="form-control" placeholder="Enter your location" required minlength="2" maxlength="255">
                    </div>
                </div>
                
                <?php if ($role === 'volunteer'): ?>
                    <div class="mb-3">
                        <label class="form-label">Medical Degree / Qualification</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-graduation-cap"></i></span>
                            <input type="text" name="degree" class="form-control" placeholder="e.g., MD, RN, Paramedic, First Aid Certified" required minlength="2" maxlength="100">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Training Certificate</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-file-medical"></i></span>
                            <input type="file" name="certificate" class="form-control" accept=".pdf,.jpg,.jpeg,.png" required>
                        </div>
                        <small class="text-muted">Upload PDF, JPG, or PNG (max 2MB)</small>
                    </div>
                <?php else: ?>
                    <div class="mb-3">
                        <label class="form-label">Age</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-birthday-cake"></i></span>
                            <input type="number" name="age" class="form-control" placeholder="Enter your age" min="1" max="150" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Gender</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-venus-mars"></i></span>
                            <select name="gender" class="form-select" required>
                                <option value="">Select gender</option>
                                <option value="male">Male</option>
                                <option value="female">Female</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                    </div>
                <?php endif; ?>
                
                <div class="mb-3">
                    <label class="form-label">Password</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                        <input type="password" name="password" class="form-control" placeholder="Create a password" minlength="6" required>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Confirm Password</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                        <input type="password" name="confirm_password" class="form-control" placeholder="Confirm your password" required>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-danger w-100 py-2 fw-bold">
                    <i class="fas fa-user-plus me-2"></i>Create Account
                </button>
            </form>
            
            <hr class="my-4">
            
            <div class="text-center">
                <p class="mb-0">Already have an account? <a href="signin.php" class="text-danger text-decoration-none">Sign In</a></p>
            </div>
            
            <p class="text-center mt-3 mb-0">
                <a href="index.php" class="text-muted text-decoration-none">
                    <i class="fas fa-arrow-left me-1"></i>Back to Home
                </a>
            </p>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
