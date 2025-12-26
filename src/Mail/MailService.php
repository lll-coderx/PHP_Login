<?php
declare(strict_types=1);

namespace App\Mail;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

/**
 * Mail Service
 * 
 * Handles email sending using PHPMailer
 * Security: Uses SMTP with authentication
 */
class MailService
{
    private array $config;
    
    public function __construct()
    {
        require_once __DIR__ . '/../../config/config.php';
        \Config::load();
        
        $this->config = [
            'host' => \Config::get('mail.smtp_host'),
            'port' => \Config::get('mail.smtp_port'),
            'username' => \Config::get('mail.smtp_username'),
            'password' => \Config::get('mail.smtp_password'),
            'from_email' => \Config::get('mail.from_email'),
            'from_name' => \Config::get('mail.from_name'),
        ];
    }
    
    /**
     * Send password reset email
     * 
     * @param string $to Recipient email
     * @param string $username Recipient username
     * @param string $token Reset token
     * @return bool True if email sent successfully
     */
    public function sendPasswordResetEmail(string $to, string $username, string $token): bool
    {
        require_once __DIR__ . '/../../config/config.php';
        \Config::load();
        
        $appUrl = \Config::get('app.url', 'http://localhost');
        $resetLink = $appUrl . '/public/reset-password.php?token=' . urlencode($token);
        
        // Load email template
        $template = file_get_contents(__DIR__ . '/../../templates/emails/reset-password.html');
        
        // Replace placeholders
        $body = str_replace(
            ['{{username}}', '{{reset_link}}', '{{app_name}}'],
            [
                htmlspecialchars($username, ENT_QUOTES, 'UTF-8'), 
                htmlspecialchars($resetLink, ENT_QUOTES, 'UTF-8'), 
                htmlspecialchars(\Config::get('app.name', 'PHP Login System'), ENT_QUOTES, 'UTF-8')
            ],
            $template
        );
        
        return $this->send(
            $to,
            'Password Reset Request',
            $body
        );
    }
    
    /**
     * Send email
     * 
     * @param string $to Recipient email
     * @param string $subject Email subject
     * @param string $body Email body (HTML)
     * @return bool True if email sent successfully
     */
    private function send(string $to, string $subject, string $body): bool
    {
        // Check if SMTP is configured
        if (empty($this->config['host']) || empty($this->config['username'])) {
            error_log('SMTP not configured. Email not sent to: ' . $to);
            // In development, log email instead of sending
            error_log("Email would be sent:\nTo: {$to}\nSubject: {$subject}\n");
            return true; // Return true for development
        }
        
        $mail = new PHPMailer(true);
        
        try {
            // Server settings
            $mail->SMTPDebug = SMTP::DEBUG_OFF;
            $mail->isSMTP();
            $mail->Host = $this->config['host'];
            $mail->SMTPAuth = true;
            $mail->Username = $this->config['username'];
            $mail->Password = $this->config['password'];
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = $this->config['port'];
            
            // Recipients
            $mail->setFrom($this->config['from_email'], $this->config['from_name']);
            $mail->addAddress($to);
            
            // Content
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $body;
            $mail->AltBody = strip_tags($body);
            
            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log('Email error: ' . $mail->ErrorInfo);
            return false;
        }
    }
}
