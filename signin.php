<?php
require_once 'config/database.php';

if (isLoggedIn()) {
    switch ($_SESSION['role']) {
        case 'admin':
            redirect('admin/dashboard.php');
            break;
        case 'volunteer':
            redirect('volunteer/dashboard.php');
            break;
        case 'patient':
            redirect('patient/dashboard.php');
            break;
    }
}

$error = '';
$csrfToken = generateCSRFToken();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        $error = "Invalid request. Please try again.";
    } else {
        $email = trim($_POST['email']);
        $password = $_POST['password'];
        
        if (!validateRequired($email) || !validateRequired($password)) {
            $error = "Please enter both email and password.";
        } elseif (!validateEmail($email)) {
            $error = "Please enter a valid email address.";
        } else {
            $stmt = $conn->prepare("SELECT id, name, email, password, 'admin' as role FROM admins WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $foundUser = null;
            $userRole = null;
            
            if ($result->num_rows === 1) {
                $foundUser = $result->fetch_assoc();
                $userRole = 'admin';
            } else {
                $stmt = $conn->prepare("SELECT id, name, email, password, status, 'volunteer' as role FROM volunteers WHERE email = ?");
                $stmt->bind_param("s", $email);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows === 1) {
                    $foundUser = $result->fetch_assoc();
                    $userRole = 'volunteer';
                } else {
                    $stmt = $conn->prepare("SELECT id, name, email, password, 'patient' as role FROM patients WHERE email = ?");
                    $stmt->bind_param("s", $email);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    
                    if ($result->num_rows === 1) {
                        $foundUser = $result->fetch_assoc();
                        $userRole = 'patient';
                    }
                }
            }
            
            if ($foundUser) {
                if ($userRole === 'volunteer' && $foundUser['status'] !== 'approved') {
                    $error = "Your account is pending approval. Please contact admin.";
                } elseif (password_verify($password, $foundUser['password'])) {
                    $_SESSION['user_id'] = $foundUser['id'];
                    $_SESSION['name'] = $foundUser['name'];
                    $_SESSION['email'] = $foundUser['email'];
                    $_SESSION['role'] = $userRole;
                    $_SESSION['session_token'] = generateSessionToken();
                    $_SESSION['login_time'] = time();
                    
                    session_write_close();
                    
                    setFlashMessage("Welcome back, " . htmlEscape($_SESSION['name']) . "!", 'success');
                    
                    switch ($userRole) {
                        case 'admin':
                            redirect('admin/dashboard.php');
                            break;
                        case 'volunteer':
                            redirect('volunteer/dashboard.php');
                            break;
                        case 'patient':
                            redirect('patient/dashboard.php');
                            break;
                    }
                } else {
                    $error = "Invalid email or password.";
                }
            } else {
                $error = "Invalid email or password.";
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
    <title>Sign In - FastAid</title>
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
            max-width: 420px;
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.2);
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="auth-box">
            <div class="text-center mb-4">
                <i class="fas fa-heart-pulse fa-3x text-danger"></i>
                <h2 class="mt-3 fw-bold">FastAid</h2>
                <p class="text-muted">Sign in to your account</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle me-2"></i><?php echo htmlEscape($error); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                
                <div class="mb-3">
                    <label class="form-label">Email Address</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                        <input type="email" name="email" class="form-control" placeholder="Enter your email" required autocomplete="email">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Password</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                        <input type="password" name="password" class="form-control" placeholder="Enter your password" required autocomplete="current-password">
                    </div>
                </div>
                <button type="submit" class="btn btn-danger w-100 py-2 fw-bold">
                    <i class="fas fa-sign-in-alt me-2"></i>Sign In
                </button>
            </form>
            
            <hr class="my-4">
            
            <div class="text-center">
                <p class="mb-2">Don't have an account?</p>
                <div class="d-flex gap-2 justify-content-center">
                    <a href="signup.php?role=patient" class="btn btn-outline-danger btn-sm">
                        <i class="fas fa-user me-1"></i>Register as Patient
                    </a>
                    <a href="signup.php?role=volunteer" class="btn btn-outline-danger btn-sm">
                        <i class="fas fa-user-md me-1"></i>Register as Volunteer
                    </a>
                </div>
            </div>
            
            <p class="text-center mt-4 mb-0">
                <a href="index.php" class="text-muted text-decoration-none">
                    <i class="fas fa-arrow-left me-1"></i>Back to Home
                </a>
            </p>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
