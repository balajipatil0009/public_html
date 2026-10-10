<?php
/**
 * Mailer Class for sending emails via Gmail SMTP
 * Uses native PHP sockets - no external dependencies required
 */

require_once __DIR__ . '/../config/EnvLoader.php';

class Mailer
{
    private $smtpHost;
    private $smtpPort;
    private $smtpUsername;
    private $smtpPassword;
    private $fromEmail;
    private $fromName;
    private $adminEmail;
    private $socket;
    private $debug = false; // Disable debug for production
    private $lastError = '';

    public function __construct()
    {
        // Load credentials from environment
        $this->smtpHost = EnvLoader::get('MAIL_HOST', 'smtp.gmail.com');
        $this->smtpPort = (int) EnvLoader::get('MAIL_PORT', 587);
        $this->smtpUsername = EnvLoader::require('MAIL_USERNAME');
        $this->smtpPassword = EnvLoader::require('MAIL_PASSWORD');
        $this->fromEmail = EnvLoader::get('MAIL_FROM_EMAIL', $this->smtpUsername);
        $this->fromName = EnvLoader::get('MAIL_FROM_NAME', 'IT Informer');
        $this->adminEmail = EnvLoader::get('MAIL_TO_ADMIN', 'admin@example.com');
    }

    /**
     * Get last error message
     */
    public function getLastError()
    {
        return $this->lastError;
    }

    /**
     * Send an email notification for a new enquiry
     */
    public function sendEnquiryNotification($name, $email, $phone, $courseInterest, $message)
    {
        $to = $this->adminEmail; // Send notification to admin from env config
        $subject = "New Enquiry from {$name} - ITINFO Academy";

        $body = $this->buildEnquiryEmailHtml($name, $email, $phone, $courseInterest, $message);

        return $this->sendEmail($to, $subject, $body);
    }

    /**
     * Build HTML email template for enquiry notification
     */
    private function buildEnquiryEmailHtml($name, $email, $phone, $courseInterest, $message)
    {
        return "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #002147, #003366); color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
                .content { background: #f9f9f9; padding: 20px; border: 1px solid #ddd; }
                .field { margin-bottom: 15px; }
                .label { font-weight: bold; color: #002147; }
                .value { margin-top: 5px; padding: 10px; background: white; border-radius: 4px; }
                .footer { text-align: center; padding: 15px; font-size: 12px; color: #666; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2 style='margin: 0;'>New Enquiry Received</h2>
                </div>
                <div class='content'>
                    <div class='field'>
                        <div class='label'>Name:</div>
                        <div class='value'>{$name}</div>
                    </div>
                    <div class='field'>
                        <div class='label'>Email:</div>
                        <div class='value'><a href='mailto:{$email}'>{$email}</a></div>
                    </div>
                    <div class='field'>
                        <div class='label'>Phone:</div>
                        <div class='value'>" . ($phone ?: 'Not provided') . "</div>
                    </div>
                    <div class='field'>
                        <div class='label'>Interested Course:</div>
                        <div class='value'>" . ($courseInterest ?: 'Not specified') . "</div>
                    </div>
                    <div class='field'>
                        <div class='label'>Message:</div>
                        <div class='value'>" . nl2br(htmlspecialchars($message)) . "</div>
                    </div>
                </div>
                <div class='footer'>
                    This is an automated notification from ITINFO Academy Website.
                </div>
            </div>
        </body>
        </html>
        ";
    }

    /**
     * Core email sending function using SMTP
     */
    public function sendEmail($to, $subject, $body)
    {
        try {
            // Connect to SMTP server
            $this->socket = @fsockopen('tls://' . $this->smtpHost, 465, $errno, $errstr, 30);

            if (!$this->socket) {
                // Try alternative port with STARTTLS
                $this->socket = @fsockopen($this->smtpHost, $this->smtpPort, $errno, $errstr, 30);
                if (!$this->socket) {
                    throw new Exception("Connection failed: $errstr ($errno)");
                }
                $this->getResponse(); // Get initial response
                $this->sendCommand("EHLO " . gethostname());
                $this->sendCommand("STARTTLS");

                // Enable TLS encryption
                stream_socket_enable_crypto($this->socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
                $this->sendCommand("EHLO " . gethostname());
            } else {
                $this->getResponse(); // Get initial response
                $this->sendCommand("EHLO " . gethostname());
            }

            // Authenticate
            $this->sendCommand("AUTH LOGIN");
            $this->sendCommand(base64_encode($this->smtpUsername));
            $this->sendCommand(base64_encode($this->smtpPassword));

            // Set sender and recipient
            $this->sendCommand("MAIL FROM:<{$this->fromEmail}>");
            $this->sendCommand("RCPT TO:<{$to}>");

            // Send email data
            $this->sendCommand("DATA");

            // Build email headers and body
            $headers = "From: {$this->fromName} <{$this->fromEmail}>\r\n";
            $headers .= "To: {$to}\r\n";
            $headers .= "Subject: {$subject}\r\n";
            $headers .= "MIME-Version: 1.0\r\n";
            $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
            $headers .= "\r\n";

            $emailContent = $headers . $body . "\r\n.";
            $this->sendCommand($emailContent, false);

            // Quit
            $this->sendCommand("QUIT");

            fclose($this->socket);
            return true;

        } catch (Exception $e) {
            $this->lastError = $e->getMessage();
            error_log("Mailer Error: " . $e->getMessage());
            if ($this->socket) {
                fclose($this->socket);
            }
            return false;
        }
    }

    /**
     * Send command to SMTP server
     */
    private function sendCommand($command, $expectResponse = true)
    {
        fputs($this->socket, $command . "\r\n");
        if ($this->debug) {
            echo "SENT: $command\n";
        }
        if ($expectResponse) {
            return $this->getResponse();
        }
        return true;
    }

    /**
     * Get response from SMTP server
     */
    private function getResponse()
    {
        $response = '';
        while ($line = fgets($this->socket, 515)) {
            $response .= $line;
            if (substr($line, 3, 1) == ' ')
                break;
        }
        if ($this->debug) {
            echo "RESPONSE: $response\n";
        }
        return $response;
    }
}
?>