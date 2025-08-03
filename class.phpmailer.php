<?php
/**
 * Minimal PHPMailer Fallback Class
 * This is a basic implementation for testing purposes only.
 * Please install the official PHPMailer for production use.
 */

class PHPMailer {
    const ENCRYPTION_STARTTLS = "tls";
    const ENCRYPTION_SMTPS = "ssl";
    
    public $isSMTP = false;
    public $Host = "";
    public $SMTPAuth = false;
    public $Username = "";
    public $Password = "";
    public $SMTPSecure = "";
    public $Port = 25;
    public $From = "";
    public $FromName = "";
    public $Subject = "";
    public $Body = "";
    public $AltBody = "";
    public $ErrorInfo = "";
    public $Timeout = 300;
    
    private $recipients = [];
    private $smtp_connection = null;
    
    public function __construct($exceptions = false) {
        // Basic constructor
    }
    
    public function isSMTP() {
        $this->isSMTP = true;
    }
    
    public function addAddress($address, $name = "") {
        $this->recipients[] = ["email" => $address, "name" => $name];
    }
    
    public function setFrom($address, $name = "") {
        $this->From = $address;
        $this->FromName = $name;
    }
    
    public function isHTML($isHtml = true) {
        // HTML mode setting
    }
    
    public function smtpConnect($options = []) {
        try {
            $context = stream_context_create([
                "ssl" => [
                    "verify_peer" => false,
                    "verify_peer_name" => false,
                    "allow_self_signed" => true
                ]
            ]);
            
            $host = $this->Host;
            $port = $this->Port;
            
            if ($this->SMTPSecure === "ssl") {
                $host = "ssl://" . $host;
            }
            
            $this->smtp_connection = stream_socket_client(
                "$host:$port",
                $errno,
                $errstr,
                $this->Timeout,
                STREAM_CLIENT_CONNECT,
                $context
            );
            
            if (!$this->smtp_connection) {
                $this->ErrorInfo = "Connection failed: $errstr ($errno)";
                return false;
            }
            
            // Read greeting
            $response = fgets($this->smtp_connection, 512);
            if (substr($response, 0, 3) !== "220") {
                $this->ErrorInfo = "Invalid SMTP greeting: $response";
                return false;
            }
            
            // EHLO command
            fwrite($this->smtp_connection, "EHLO localhost\r\n");
            $response = fgets($this->smtp_connection, 512);
            
            // STARTTLS if required
            if ($this->SMTPSecure === "tls") {
                fwrite($this->smtp_connection, "STARTTLS\r\n");
                $response = fgets($this->smtp_connection, 512);
                if (substr($response, 0, 3) !== "220") {
                    $this->ErrorInfo = "STARTTLS failed: $response";
                    return false;
                }
                
                if (!stream_socket_enable_crypto($this->smtp_connection, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                    $this->ErrorInfo = "TLS encryption failed";
                    return false;
                }
                
                // EHLO again after STARTTLS
                fwrite($this->smtp_connection, "EHLO localhost\r\n");
                $response = fgets($this->smtp_connection, 512);
            }
            
            // Authentication
            if ($this->SMTPAuth && $this->Username && $this->Password) {
                fwrite($this->smtp_connection, "AUTH LOGIN\r\n");
                $response = fgets($this->smtp_connection, 512);
                if (substr($response, 0, 3) !== "334") {
                    $this->ErrorInfo = "AUTH LOGIN failed: $response";
                    return false;
                }
                
                fwrite($this->smtp_connection, base64_encode($this->Username) . "\r\n");
                $response = fgets($this->smtp_connection, 512);
                if (substr($response, 0, 3) !== "334") {
                    $this->ErrorInfo = "Username authentication failed: $response";
                    return false;
                }
                
                fwrite($this->smtp_connection, base64_encode($this->Password) . "\r\n");
                $response = fgets($this->smtp_connection, 512);
                if (substr($response, 0, 3) !== "235") {
                    $this->ErrorInfo = "Password authentication failed: $response";
                    return false;
                }
            }
            
            return true;
            
        } catch (Exception $e) {
            $this->ErrorInfo = "Connection error: " . $e->getMessage();
            return false;
        }
    }
    
    public function smtpClose() {
        if ($this->smtp_connection) {
            fwrite($this->smtp_connection, "QUIT\r\n");
            fclose($this->smtp_connection);
            $this->smtp_connection = null;
        }
    }
    
    public function send() {
        // For testing purposes, we'll use PHP's mail() function as fallback
        // In production, use the real PHPMailer library
        
        if (empty($this->recipients)) {
            $this->ErrorInfo = "No recipients specified";
            return false;
        }
        
        $to = $this->recipients[0]["email"];
        $subject = $this->Subject;
        $message = $this->Body;
        $headers = "From: " . $this->From;
        
        if ($this->FromName) {
            $headers = "From: " . $this->FromName . " <" . $this->From . ">";
        }
        
        $headers .= "\r\nMIME-Version: 1.0";
        $headers .= "\r\nContent-type: text/html; charset=UTF-8";
        
        if (function_exists("mail")) {
            return mail($to, $subject, $message, $headers);
        } else {
            $this->ErrorInfo = "PHP mail() function not available and PHPMailer not properly installed";
            return false;
        }
    }
}

class Exception extends \Exception {
    // Basic exception class
}
?>