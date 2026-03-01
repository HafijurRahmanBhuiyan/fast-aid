<?php
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'fastaid_db');
define('DB_SOCKET', '/Applications/XAMPP/xamppfiles/var/mysql/mysql.sock');

ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function generateCSRFToken() {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCSRFToken($token) {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function htmlEscape($data) {
    if (is_array($data)) {
        return array_map('htmlEscape', $data);
    }
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}

function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function validatePhone($phone) {
    return preg_match('/^[0-9+\-\s()]{7,20}$/', $phone);
}

function validateRequired($data) {
    return isset($data) && trim($data) !== '';
}

function validateLength($data, $min = 1, $max = 255) {
    $len = strlen(trim($data));
    return $len >= $min && $len <= $max;
}

function validateAge($age) {
    return is_numeric($age) && $age >= 1 && $age <= 150;
}

function validateGender($gender) {
    return in_array($gender, ['male', 'female', 'other']);
}

function sanitizeInput($data) {
    return trim($data);
}

function validateFileUpload($file, $allowedTypes = ['application/pdf', 'image/jpeg', 'image/jpg', 'image/png'], $maxSize = 2097152) {
    $errors = [];
    
    if (!isset($file['error']) || is_array($file['error'])) {
        $errors[] = 'Invalid file upload.';
        return ['valid' => false, 'errors' => $errors];
    }
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors[] = 'File upload error: ' . $file['error'];
        return ['valid' => false, 'errors' => $errors];
    }
    
    if ($file['size'] > $maxSize) {
        $errors[] = 'File size exceeds maximum allowed size of ' . ($maxSize / 1048576) . 'MB.';
    }
    
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($file['tmp_name']);
    
    if (!in_array($mimeType, $allowedTypes)) {
        $errors[] = 'Invalid file type. Allowed: PDF, JPG, PNG.';
    }
    
    $allowedExtensions = ['pdf', 'jpg', 'jpeg', 'png'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowedExtensions)) {
        $errors[] = 'Invalid file extension.';
    }
    
    return [
        'valid' => empty($errors),
        'errors' => $errors,
        'mime' => $mimeType,
        'ext' => $ext
    ];
}

function uploadFile($file, $directory) {
    $validation = validateFileUpload($file);
    
    if (!$validation['valid']) {
        return ['success' => false, 'errors' => $validation['errors']];
    }
    
    if (!is_dir($directory)) {
        if (!mkdir($directory, 0755, true)) {
            return ['success' => false, 'errors' => ['Failed to create upload directory.']];
        }
    }
    
    $newFilename = time() . '_' . bin2hex(random_bytes(8)) . '.' . $validation['ext'];
    $destination = $directory . '/' . $newFilename;
    
    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        return ['success' => false, 'errors' => ['Failed to move uploaded file.']];
    }
    
    return [
        'success' => true,
        'filename' => $newFilename,
        'path' => $directory . '/' . $newFilename
    ];
}

$conn = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME, 3306, DB_SOCKET);

if (!$conn) {
    error_log("Database connection failed: " . mysqli_connect_error());
    die("Database connection error. Please try again later.");
}

mysqli_set_charset($conn, 'utf8mb4');

function sanitize($data) {
    global $conn;
    if (is_array($data)) {
        return array_map('sanitize', $data);
    }
    $data = trim($data);
    return mysqli_real_escape_string($conn, $data);
}

function redirect($url) {
    header("Location: $url");
    exit();
}

function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['role']);
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function isVolunteer() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'volunteer';
}

function isPatient() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'patient';
}

function setFlashMessage($message, $type = 'danger') {
    $_SESSION['flash_message'] = $message;
    $_SESSION['flash_type'] = $type;
}

function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        $type = $_SESSION['flash_type'] ?? 'danger';
        unset($_SESSION['flash_message'], $_SESSION['flash_type']);
        return ['message' => $message, 'type' => $type];
    }
    return null;
}

function hasRole($role) {
    return isset($_SESSION['role']) && $_SESSION['role'] === $role;
}

function requireLogin() {
    if (!isLoggedIn()) {
        redirect('signin.php');
    }
}

function requireRole($role) {
    requireLogin();
    if (!hasRole($role)) {
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
            default:
                redirect('signin.php');
        }
    }
}

function generateSessionToken() {
    return bin2hex(random_bytes(32));
}

function logActivity($userId, $action, $details = '') {
    global $conn;
    $stmt = $conn->prepare("INSERT INTO activity_logs (user_id, action, details, ip_address, user_agent) VALUES (?, ?, ?, ?, ?)");
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    $stmt->bind_param("issss", $userId, $action, $details, $ip, $ua);
    $stmt->execute();
}
