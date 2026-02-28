<?php
require_once 'database.php';

class NotificationService {
    
    private $conn;
    private $fromEmail = 'noreply@fastaid.com';
    private $fromName = 'FastAid Emergency';
    
    public function __construct() {
        global $conn;
        $this->conn = $conn;
    }
    
    public function getVolunteerApprovalEmail($volunteerId) {
        global $conn;
        
        $stmt = $conn->prepare("SELECT name, email FROM volunteers WHERE id = ?");
        $stmt->bind_param("i", $volunteerId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) return false;
        
        $volunteer = $result->fetch_assoc();
        
        $to = $volunteer['email'];
        $subject = "Your FastAid Volunteer Account Has Been Approved!";
        $message = $this->getApprovalEmailTemplate($volunteer['name']);
        
        return $this->sendEmail($to, $subject, $message);
    }
    
    public function sendEmergencyRequestSMS($requestId) {
        global $conn;
        
        $stmt = $conn->prepare("
            SELECT sr.*, v.phone as volunteer_phone, v.name as volunteer_name,
                   p.name as patient_name, p.phone as patient_phone
            FROM service_requests sr
            JOIN patients p ON sr.patient_id = p.id
            LEFT JOIN volunteers v ON sr.volunteer_id = v.id
            WHERE sr.id = ?
        ");
        $stmt->bind_param("i", $requestId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) return false;
        
        $request = $result->fetch_assoc();
        
        $smsMessages = [];
        
        if ($request['volunteer_phone']) {
            $smsMessages[] = [
                'to' => $request['volunteer_phone'],
                'message' => "EMERGENCY: New request from {$request['patient_name']} at {$request['location']}. Phone: {$request['patient_phone']}"
            ];
        }
        
        $stmt2 = $conn->prepare("
            SELECT phone FROM volunteers 
            WHERE status = 'approved' AND location = ?
        ");
        $stmt2->bind_param("s", $request['location']);
        $stmt2->execute();
        $volunteers = $stmt2->get_result();
        
        while ($v = $volunteers->fetch_assoc()) {
            if ($v['phone'] !== $request['volunteer_phone']) {
                $smsMessages[] = [
                    'to' => $v['phone'],
                    'message' => "EMERGENCY: Someone needs help at {$request['location']}. Please check the app."
                ];
            }
        }
        
        foreach ($smsMessages as $sms) {
            $this->sendSMS($sms['to'], $sms['message']);
        }
        
        return true;
    }
    
    public function sendRequestAcceptedSMS($requestId) {
        global $conn;
        
        $stmt = $conn->prepare("
            SELECT sr.*, p.phone as patient_phone, v.name as volunteer_name, v.phone as volunteer_phone
            FROM service_requests sr
            JOIN patients p ON sr.patient_id = p.id
            JOIN volunteers v ON sr.volunteer_id = v.id
            WHERE sr.id = ?
        ");
        $stmt->bind_param("i", $requestId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) return false;
        
        $request = $result->fetch_assoc();
        
        return $this->sendSMS($request['patient_phone'], 
            "Good news! {$request['volunteer_name']} has accepted your emergency request. " .
            "Contact: {$request['volunteer_phone']}"
        );
    }
    
    public function sendRequestCompletedSMS($requestId) {
        global $conn;
        
        $stmt = $conn->prepare("
            SELECT sr.*, p.phone as patient_phone, v.name as volunteer_name
            FROM service_requests sr
            JOIN patients p ON sr.patient_id = p.id
            JOIN volunteers v ON sr.volunteer_id = v.id
            WHERE sr.id = ?
        ");
        $stmt->bind_param("i", $requestId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) return false;
        
        $request = $result->fetch_assoc();
        
        return $this->sendSMS($request['patient_phone'],
            "Your emergency request has been completed by {$request['volunteer_name']}. Stay safe!"
        );
    }
    
    public function sendEmail($to, $subject, $message, $isHtml = true) {
        $headers = "From: {$this->fromName} <{$this->fromEmail}>\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        
        if ($isHtml) {
            $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        } else {
            $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
        }
        
        return mail($to, $subject, $message, $headers);
    }
    
    public function sendSMS($to, $message) {
        $to = preg_replace('/[^0-9]/', '', $to);
        
        if (strlen($to) < 10) return false;
        
        error_log("SMS to $to: $message");
        
        return true;
    }
    
    private function getApprovalEmailTemplate($name) {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #dc3545; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; background: #f9f9f9; }
                .btn { display: inline-block; padding: 12px 30px; background: #dc3545; color: white; text-decoration: none; border-radius: 5px; margin-top: 20px; }
                .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>FastAid</h1>
                    <p>Emergency Medical Assistance</p>
                </div>
                <div class='content'>
                    <h2>Welcome, {$name}!</h2>
                    <p>Great news! Your volunteer application to FastAid has been <strong>approved</strong>.</p>
                    <p>You can now:</p>
                    <ul>
                        <li>Accept emergency requests from patients</li>
                        <li>View your dashboard and stats</li>
                        <li>Update your profile and availability</li>
                    </ul>
                    <a href='https://fastaid.com/signin.php' class='btn'>Sign In Now</a>
                </div>
                <div class='footer'>
                    <p>&copy; " . date('Y') . " FastAid. All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }
}

$notificationService = new NotificationService();
