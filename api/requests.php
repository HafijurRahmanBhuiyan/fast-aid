<?php
require_once '../config/database.php';
require_once '../config/notifications.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => '', 'data' => []];

// Debug - check what's in session
$debug_info = [
    'session_status' => session_status(),
    'session_id' => session_id(),
    'session_keys' => array_keys($_SESSION ?? []),
    'cookie' => $_COOKIE['PHPSESSID'] ?? 'none'
];
file_put_contents('/tmp/api_debug.log', print_r($debug_info, true));

// Simple auth check - just check if user_id and role exist
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized', 'debug' => $debug_info]);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'create_request':
        handleCreateRequest();
        break;
    case 'get_request_status':
        handleGetRequestStatus();
        break;
    case 'accept_request':
        handleAcceptRequest();
        break;
    case 'complete_request':
        handleCompleteRequest();
        break;
    case 'get_volunteers':
        handleGetVolunteers();
        break;
    case 'update_profile':
        handleUpdateProfile();
        break;
    case 'change_password':
        handleChangePassword();
        break;
    case 'get_nearby_requests':
        handleGetNearbyRequests();
        break;
    case 'get_volunteer_stats':
        handleGetVolunteerStats();
        break;
    case 'get_patient_requests':
        handleGetPatientRequests();
        break;
    case 'get_my_requests':
        handleGetMyRequests();
        break;
    case 'cancel_request':
        handleCancelRequest();
        break;
    default:
        $response['message'] = 'Invalid action';
}

echo json_encode($response);

function handleCreateRequest() {
    global $conn, $response, $notificationService;
    
    if (!isPatient()) {
        $response['message'] = 'Only patients can create requests';
        return;
    }
    
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        $response['message'] = 'Invalid CSRF token';
        return;
    }
    
    $userId = (int)$_SESSION['user_id'];
    $volunteerId = isset($_POST['volunteer_id']) ? (int)$_POST['volunteer_id'] : 0;
    $location = sanitizeInput($_POST['location'] ?? '');
    $lat = isset($_POST['lat']) ? (float)$_POST['lat'] : null;
    $lng = isset($_POST['lng']) ? (float)$_POST['lng'] : null;
    
    if (empty($location) || empty($volunteerId)) {
        $response['message'] = 'Location and volunteer are required';
        return;
    }
    
    $pendingCheck = $conn->prepare("SELECT id FROM service_requests WHERE patient_id = ? AND status IN ('pending', 'accepted')");
    $pendingCheck->bind_param("i", $userId);
    $pendingCheck->execute();
    
    if ($pendingCheck->get_result()->num_rows > 0) {
        $response['message'] = 'You already have an active request';
        return;
    }
    
    $stmt = $conn->prepare("INSERT INTO service_requests (patient_id, volunteer_id, location, patient_lat, patient_lng) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("iissd", $userId, $volunteerId, $location, $lat, $lng);
    
    if ($stmt->execute()) {
        $requestId = $stmt->insert_id;
        $notificationService->sendEmergencyRequestSMS($requestId);
        
        $response['success'] = true;
        $response['message'] = 'Request sent successfully';
        $response['data'] = ['request_id' => $requestId];
    } else {
        $response['message'] = 'Failed to create request';
    }
}

function handleGetRequestStatus() {
    global $conn, $response;
    
    if (!isPatient()) {
        $response['message'] = 'Unauthorized';
        return;
    }
    
    $userId = (int)$_SESSION['user_id'];
    
    $stmt = $conn->prepare("
        SELECT sr.*, v.name as volunteer_name, v.phone as volunteer_phone, v.location as volunteer_location,
               v.lat as volunteer_lat, v.lng as volunteer_lng
        FROM service_requests sr
        LEFT JOIN volunteers v ON sr.volunteer_id = v.id
        WHERE sr.patient_id = ? AND sr.status IN ('pending', 'accepted', 'cancelled')
        ORDER BY sr.request_time DESC
        LIMIT 1
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $request = $result->fetch_assoc();
        
        $response['success'] = true;
        $response['data'] = [
            'id' => (int)$request['id'],
            'status' => $request['status'],
            'location' => $request['location'],
            'patient_lat' => $request['patient_lat'],
            'patient_lng' => $request['patient_lng'],
            'volunteer_name' => $request['volunteer_name'],
            'volunteer_phone' => $request['volunteer_phone'],
            'volunteer_location' => $request['volunteer_location'],
            'volunteer_lat' => $request['volunteer_lat'],
            'volunteer_lng' => $request['volunteer_lng'],
            'request_time' => $request['request_time'],
            'accepted_at' => $request['accepted_at']
        ];
    } else {
        $response['success'] = true;
        $response['data'] = null;
    }
}

function handleAcceptRequest() {
    global $conn, $response, $notificationService;
    
    if (!isVolunteer()) {
        $response['message'] = 'Only volunteers can accept requests';
        return;
    }
    
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        $response['message'] = 'Invalid CSRF token';
        return;
    }
    
    $requestId = isset($_POST['request_id']) ? (int)$_POST['request_id'] : 0;
    $volunteerId = (int)$_SESSION['user_id'];
    $lat = isset($_POST['lat']) ? (float)$_POST['lat'] : null;
    $lng = isset($_POST['lng']) ? (float)$_POST['lng'] : null;
    
    if (!$requestId) {
        $response['message'] = 'Invalid request ID';
        return;
    }
    
    $checkStmt = $conn->prepare("SELECT id, status FROM service_requests WHERE id = ? AND status = 'pending'");
    $checkStmt->bind_param("i", $requestId);
    $checkStmt->execute();
    
    if ($checkStmt->get_result()->num_rows === 0) {
        $response['message'] = 'Request not found or already accepted';
        return;
    }
    
    $stmt = $conn->prepare("UPDATE service_requests SET volunteer_id = ?, status = 'accepted', accepted_at = NOW(), volunteer_lat = ?, volunteer_lng = ? WHERE id = ? AND status = 'pending'");
    $stmt->bind_param("iddi", $volunteerId, $lat, $lng, $requestId);
    
    if ($stmt->execute()) {
        $notificationService->sendRequestAcceptedSMS($requestId);
        
        $response['success'] = true;
        $response['message'] = 'Request accepted successfully';
    } else {
        $response['message'] = 'Failed to accept request';
    }
}

function handleCompleteRequest() {
    global $conn, $response, $notificationService;
    
    if (!isVolunteer()) {
        $response['message'] = 'Only volunteers can complete requests';
        return;
    }
    
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        $response['message'] = 'Invalid CSRF token';
        return;
    }
    
    $requestId = isset($_POST['request_id']) ? (int)$_POST['request_id'] : 0;
    $volunteerId = (int)$_SESSION['user_id'];
    
    if (!$requestId) {
        $response['message'] = 'Invalid request ID';
        return;
    }
    
    $checkStmt = $conn->prepare("SELECT id FROM service_requests WHERE id = ? AND volunteer_id = ? AND status = 'accepted'");
    $checkStmt->bind_param("ii", $requestId, $volunteerId);
    $checkStmt->execute();
    
    if ($checkStmt->get_result()->num_rows === 0) {
        $response['message'] = 'Request not found or not accepted by you';
        return;
    }
    
    $stmt = $conn->prepare("UPDATE service_requests SET status = 'completed', completed_at = NOW() WHERE id = ? AND volunteer_id = ? AND status = 'accepted'");
    $stmt->bind_param("ii", $requestId, $volunteerId);
    
    if ($stmt->execute()) {
        $notificationService->sendRequestCompletedSMS($requestId);
        
        $response['success'] = true;
        $response['message'] = 'Request completed successfully';
    } else {
        $response['message'] = 'Failed to complete request';
    }
}

function handleGetVolunteers() {
    global $conn, $response;
    
    $location = sanitizeInput($_GET['location'] ?? '');
    
    if (empty($location)) {
        $response['message'] = 'Location is required';
        return;
    }
    
    $stmt = $conn->prepare("SELECT id, name, phone, location, degree FROM volunteers WHERE status = 'approved' AND location = ? ORDER BY created_at DESC");
    $stmt->bind_param("s", $location);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $volunteers = [];
    while ($row = $result->fetch_assoc()) {
        $volunteers[] = $row;
    }
    
    $response['success'] = true;
    $response['data'] = $volunteers;
}

function handleUpdateProfile() {
    global $conn, $response;
    
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        $response['message'] = 'Invalid CSRF token';
        return;
    }
    
    $userId = (int)$_SESSION['user_id'];
    $role = $_SESSION['role'];
    
    if ($role === 'volunteer') {
        $name = sanitizeInput($_POST['name'] ?? '');
        $phone = sanitizeInput($_POST['phone'] ?? '');
        $location = sanitizeInput($_POST['location'] ?? '');
        $degree = sanitizeInput($_POST['degree'] ?? '');
        
        if (empty($name) || empty($phone) || empty($location) || empty($degree)) {
            $response['message'] = 'All fields are required';
            return;
        }
        
        $stmt = $conn->prepare("UPDATE volunteers SET name = ?, phone = ?, location = ?, degree = ? WHERE id = ?");
        $stmt->bind_param("ssssi", $name, $phone, $location, $degree, $userId);
        
        if ($stmt->execute()) {
            $_SESSION['name'] = $name;
            $response['success'] = true;
            $response['message'] = 'Profile updated successfully';
        } else {
            $response['message'] = 'Failed to update profile';
        }
    } elseif ($role === 'patient') {
        $name = sanitizeInput($_POST['name'] ?? '');
        $phone = sanitizeInput($_POST['phone'] ?? '');
        $location = sanitizeInput($_POST['location'] ?? '');
        $age = isset($_POST['age']) ? (int)$_POST['age'] : 0;
        
        if (empty($name) || empty($phone) || empty($location) || $age < 1) {
            $response['message'] = 'All fields are required';
            return;
        }
        
        $stmt = $conn->prepare("UPDATE patients SET name = ?, phone = ?, location = ?, age = ? WHERE id = ?");
        $stmt->bind_param("sssii", $name, $phone, $location, $age, $userId);
        
        if ($stmt->execute()) {
            $_SESSION['name'] = $name;
            $response['success'] = true;
            $response['message'] = 'Profile updated successfully';
        } else {
            $response['message'] = 'Failed to update profile';
        }
    }
}

function handleChangePassword() {
    global $conn, $response;
    
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        $response['message'] = 'Invalid CSRF token';
        return;
    }
    
    $userId = (int)$_SESSION['user_id'];
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $role = $_SESSION['role'];
    
    if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
        $response['message'] = 'All password fields are required';
        return;
    }
    
    if ($newPassword !== $confirmPassword) {
        $response['message'] = 'New passwords do not match';
        return;
    }
    
    if (strlen($newPassword) < 6) {
        $response['message'] = 'Password must be at least 6 characters';
        return;
    }
    
    $table = $role === 'volunteer' ? 'volunteers' : ($role === 'patient' ? 'patients' : 'admins');
    
    $stmt = $conn->prepare("SELECT password FROM {$table} WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $response['message'] = 'User not found';
        return;
    }
    
    $user = $result->fetch_assoc();
    
    if (!password_verify($currentPassword, $user['password'])) {
        $response['message'] = 'Current password is incorrect';
        return;
    }
    
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
    $updateStmt = $conn->prepare("UPDATE {$table} SET password = ? WHERE id = ?");
    $updateStmt->bind_param("si", $hashedPassword, $userId);
    
    if ($updateStmt->execute()) {
        $response['success'] = true;
        $response['message'] = 'Password changed successfully';
    } else {
        $response['message'] = 'Failed to change password';
    }
}

function handleGetNearbyRequests() {
    global $conn, $response;
    
    if (!isVolunteer()) {
        $response['message'] = 'Only volunteers can view requests';
        return;
    }
    
    $userId = (int)$_SESSION['user_id'];
    
    $volunteerStmt = $conn->prepare("SELECT location FROM volunteers WHERE id = ?");
    $volunteerStmt->bind_param("i", $userId);
    $volunteerStmt->execute();
    $volunteerResult = $volunteerStmt->get_result();
    
    if ($volunteerResult->num_rows === 0) {
        $response['message'] = 'Volunteer not found';
        return;
    }
    
    $volunteer = $volunteerResult->fetch_assoc();
    $volunteerLocation = $volunteer['location'];
    
    $stmt = $conn->prepare("
        SELECT sr.*, p.name as patient_name, p.location as patient_location, p.phone as patient_phone, 
               p.age as patient_age
        FROM service_requests sr
        JOIN patients p ON sr.patient_id = p.id
        WHERE sr.status = 'pending' AND p.location = ?
        ORDER BY sr.request_time DESC
        LIMIT 20
    ");
    $stmt->bind_param("s", $volunteerLocation);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $requests = [];
    while ($row = $result->fetch_assoc()) {
        $requests[] = $row;
    }
    
    $response['success'] = true;
    $response['data'] = $requests;
}

function handleGetVolunteerStats() {
    global $conn, $response;
    
    if (!isVolunteer()) {
        $response['message'] = 'Only volunteers can view stats';
        return;
    }
    
    $userId = (int)$_SESSION['user_id'];
    
    $totalStmt = $conn->prepare("SELECT COUNT(*) as c FROM service_requests WHERE volunteer_id = ?");
    $totalStmt->bind_param("i", $userId);
    $totalStmt->execute();
    $total = $totalStmt->get_result()->fetch_assoc()['c'] ?? 0;
    
    $acceptedStmt = $conn->prepare("SELECT COUNT(*) as c FROM service_requests WHERE volunteer_id = ? AND status = 'accepted'");
    $acceptedStmt->bind_param("i", $userId);
    $acceptedStmt->execute();
    $accepted = $acceptedStmt->get_result()->fetch_assoc()['c'] ?? 0;
    
    $completedStmt = $conn->prepare("SELECT COUNT(*) as c FROM service_requests WHERE volunteer_id = ? AND status = 'completed'");
    $completedStmt->bind_param("i", $userId);
    $completedStmt->execute();
    $completed = $completedStmt->get_result()->fetch_assoc()['c'] ?? 0;
    
    $response['success'] = true;
    $response['data'] = [
        'total' => $total,
        'accepted' => $accepted,
        'completed' => $completed
    ];
}

function handleGetPatientRequests() {
    global $conn, $response;
    
    if (!isPatient()) {
        $response['message'] = 'Unauthorized';
        return;
    }
    
    $userId = (int)$_SESSION['user_id'];
    
    $stmt = $conn->prepare("
        SELECT sr.*, v.name as volunteer_name
        FROM service_requests sr
        LEFT JOIN volunteers v ON sr.volunteer_id = v.id
        WHERE sr.patient_id = ?
        ORDER BY sr.request_time DESC
        LIMIT 20
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $requests = [];
    while ($row = $result->fetch_assoc()) {
        $requests[] = $row;
    }
    
    $response['success'] = true;
    $response['data'] = $requests;
}

function handleGetMyRequests() {
    global $conn, $response;
    
    if (!isVolunteer()) {
        $response['message'] = 'Only volunteers can view their requests';
        return;
    }
    
    $userId = (int)$_SESSION['user_id'];
    
    $stmt = $conn->prepare("
        SELECT sr.*, p.name as patient_name, p.location as patient_location, p.phone as patient_phone, p.age as patient_age
        FROM service_requests sr
        JOIN patients p ON sr.patient_id = p.id
        WHERE sr.volunteer_id = ? AND sr.status IN ('accepted', 'completed')
        ORDER BY sr.request_time DESC
        LIMIT 20
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $requests = [];
    while ($row = $result->fetch_assoc()) {
        $requests[] = $row;
    }
    
    $response['success'] = true;
    $response['data'] = $requests;
}

function handleCancelRequest() {
    global $conn, $response;
    
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'patient') {
        $response['message'] = 'Unauthorized - Please login as patient';
        return;
    }
    
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        $response['message'] = 'Invalid CSRF token';
        return;
    }
    
    $requestId = isset($_POST['request_id']) ? (int)$_POST['request_id'] : 0;
    $userId = (int)$_SESSION['user_id'];
    
    if (!$requestId) {
        $response['message'] = 'Invalid request ID';
        return;
    }
    
    $checkStmt = $conn->prepare("SELECT id, status FROM service_requests WHERE id = ? AND patient_id = ?");
    $checkStmt->bind_param("ii", $requestId, $userId);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    
    if ($checkResult->num_rows === 0) {
        $response['message'] = 'Request not found or not owned by you';
        return;
    }
    
    $request = $checkResult->fetch_assoc();
    
    if ($request['status'] !== 'pending') {
        $response['message'] = 'You can only cancel pending requests. Current status: ' . $request['status'];
        return;
    }
    
    $stmt = $conn->prepare("DELETE FROM service_requests WHERE id = ? AND patient_id = ? AND status = 'pending'");
    $stmt->bind_param("ii", $requestId, $userId);
    
    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            $response['success'] = true;
            $response['message'] = 'Request cancelled successfully';
        } else {
            $response['message'] = 'Could not cancel. Request may have changed.';
        }
    } else {
        $response['message'] = 'Error: ' . $conn->error;
    }
}
