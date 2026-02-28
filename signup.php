<?php
session_start();
require_once 'config/database.php';

if (isLoggedIn()) {
    redirect('index.php');
}

$error = '';
$role = isset($_GET['role']) ? $_GET['role'] : 'patient';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $role = sanitize($_POST['role']);
    $name = sanitize($_POST['name']);
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    if ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        if ($role === 'admin') {
            $error = "Invalid registration.";
        } elseif ($role === 'volunteer') {
            $phone = sanitize($_POST['phone']);
            $location = sanitize($_POST['location']);
            $degree = sanitize($_POST['degree']);
            
            $checkEmail = $conn->prepare("SELECT id FROM volunteers WHERE email = ?");
            $checkEmail->bind_param("s", $email);
            $checkEmail->execute();
            
            if ($checkEmail->get_result()->num_rows > 0) {
                $error = "Email already registered.";
            } else {
                $stmt = $conn->prepare("INSERT INTO volunteers (name, email, password, phone, location, degree, status) VALUES (?, ?, ?, ?, ?, ?, 'pending')");
                $stmt->bind_param("ssssss", $name, $email, $hashed_password, $phone, $location, $degree);
                
                if ($stmt->execute()) {
                    redirect('signin.php?registered=volunteer');
                } else {
                    $error = "Registration failed. Please try again.";
                }
            }
        } elseif ($role === 'patient') {
            $phone = sanitize($_POST['phone']);
            $age = intval($_POST['age']);
            $gender = sanitize($_POST['gender']);
            $location = sanitize($_POST['location']);
            
            $checkEmail = $conn->prepare("SELECT id FROM patients WHERE email = ?");
            $checkEmail->bind_param("s", $email);
            $checkEmail->execute();
            
            if ($checkEmail->get_result()->num_rows > 0) {
                $error = "Email already registered.";
            } else {
                $stmt = $conn->prepare("INSERT INTO patients (name, email, password, phone, age, gender, location) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("ssssiss", $name, $email, $hashed_password, $phone, $age, $gender, $location);
                
                if ($stmt->execute()) {
                    redirect('signin.php?registered=patient');
                } else {
                    $error = "Registration failed. Please try again.";
                }
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-box">
            <div class="text-center mb-4">
                <i class="fas fa-ambulance fa-3x text-danger"></i>
                <h2 class="mt-3">FastAid</h2>
                <p class="text-muted">Create your account</p>
            </div>
            
            <ul class="nav nav-pills mb-4 justify-content-center">
                <li class="nav-item">
                    <a class="nav-link <?php echo $role === 'patient' ? 'active' : ''; ?>" href="?role=patient">Patient</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $role === 'volunteer' ? 'active' : ''; ?>" href="?role=volunteer">Volunteer</a>
                </li>
            </ul>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <input type="hidden" name="role" value="<?php echo $role; ?>">
                
                <div class="mb-3">
                    <label class="form-label">Full Name</label>
                    <input type="text" name="name" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Phone</label>
                    <input type="tel" name="phone" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Location</label>
                    <input type="text" name="location" class="form-control" placeholder="Address or area" required>
                </div>
                
                <?php if ($role === 'volunteer'): ?>
                    <div class="mb-3">
                        <label class="form-label">Medical Degree/Qualification</label>
                        <input type="text" name="degree" class="form-control" placeholder="e.g., MD, RN, Paramedic" required>
                    </div>
                <?php else: ?>
                    <div class="mb-3">
                        <label class="form-label">Age</label>
                        <input type="number" name="age" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Gender</label>
                        <select name="gender" class="form-select" required>
                            <option value="">Select</option>
                            <option value="male">Male</option>
                            <option value="female">Female</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                <?php endif; ?>
                
                <div class="mb-3">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Confirm Password</label>
                    <input type="password" name="confirm_password" class="form-control" required>
                </div>
                
                <button type="submit" class="btn btn-danger w-100">Sign Up</button>
            </form>
            
            <p class="text-center mt-3">
                Already have an account? <a href="signin.php">Sign In</a>
            </p>
            <p class="text-center">
                <a href="index.php">Back to Home</a>
            </p>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
