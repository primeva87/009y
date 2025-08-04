<?php
// Simple email test script
header('Content-Type: text/html; charset=UTF-8');

// Email configuration
$to = 'skkho87.sm@gmail.com';
$subject = 'Email Test - Webmail Logger';
$from_email = 'noreply@webmail-system.com';
$from_name = 'Webmail Test System';

$message = '
<html>
<head>
    <title>Email Test</title>
</head>
<body>
    <h2>📧 Email Test Successful!</h2>
    <p>This is a test email to verify that your server can send emails.</p>
    
    <div style="background-color: #f0f0f0; padding: 15px; margin: 15px 0;">
        <h3>Server Information:</h3>
        <p><strong>PHP Version:</strong> ' . phpversion() . '</p>
        <p><strong>Server:</strong> ' . ($_SERVER['HTTP_HOST'] ?? 'Unknown') . '</p>
        <p><strong>Timestamp:</strong> ' . date('Y-m-d H:i:s') . '</p>
        <p><strong>IP:</strong> ' . ($_SERVER['REMOTE_ADDR'] ?? 'Unknown') . '</p>
    </div>
    
    <p>If you received this email, your webmail logger email functionality is working correctly!</p>
</body>
</html>';

// Email headers
$headers = array(
    'MIME-Version: 1.0',
    'Content-type: text/html; charset=UTF-8',
    "From: $from_name <$from_email>",
    "Reply-To: $from_email",
    "X-Mailer: PHP/" . phpversion()
);

$header_string = implode("\r\n", $headers);

echo '<h1>Email Test Script</h1>';
echo '<p><strong>Testing email to:</strong> ' . $to . '</p>';
echo '<p><strong>From:</strong> ' . $from_name . ' &lt;' . $from_email . '&gt;</p>';
echo '<p><strong>Subject:</strong> ' . $subject . '</p>';

// Test if mail function exists
if (!function_exists('mail')) {
    echo '<div style="color: red; background: #ffe6e6; padding: 10px; border: 1px solid red; margin: 10px 0;">';
    echo '<strong>ERROR:</strong> The mail() function is not available on this server.';
    echo '</div>';
    exit;
}

// Try to send the email
$result = mail($to, $subject, $message, $header_string);

if ($result) {
    echo '<div style="color: green; background: #e6ffe6; padding: 10px; border: 1px solid green; margin: 10px 0;">';
    echo '<strong>SUCCESS:</strong> Test email sent successfully! Check your inbox at ' . $to;
    echo '</div>';
    
    // Log the success
    $log = date('Y-m-d H:i:s') . " | EMAIL TEST SUCCESS | To: $to | From: $from_email\n";
    @file_put_contents('email_test.log', $log, FILE_APPEND | LOCK_EX);
    
} else {
    echo '<div style="color: red; background: #ffe6e6; padding: 10px; border: 1px solid red; margin: 10px 0;">';
    echo '<strong>FAILED:</strong> Could not send test email. Check server configuration.';
    echo '</div>';
    
    // Log the failure
    $log = date('Y-m-d H:i:s') . " | EMAIL TEST FAILED | To: $to | From: $from_email\n";
    @file_put_contents('email_test.log', $log, FILE_APPEND | LOCK_EX);
}

echo '<h3>Troubleshooting Tips:</h3>';
echo '<ul>';
echo '<li>Check if your server has mail() function enabled</li>';
echo '<li>Verify SMTP settings on your hosting provider</li>';
echo '<li>Check spam/junk folder in your email</li>';
echo '<li>Some servers require proper DNS setup for the FROM domain</li>';
echo '<li>Check the email_test.log file for more details</li>';
echo '</ul>';

echo '<p><a href="index.html">Back to Login Form</a></p>';
?>