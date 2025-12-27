<?php
declare(strict_types=1);

namespace App\Security;

use PDO;

/**
 * Rate Limiter
 * 
 * Implements rate limiting to prevent brute force attacks
 * Security: Protects against automated attack attempts
 */
class RateLimiter
{
    private PDO $db;
    
    public function __construct(PDO $db)
    {
        $this->db = $db;
    }
    
    /**
     * Check if login attempts are within limit
     * 
     * @param string $ipAddress IP address to check
     * @param int $maxAttempts Maximum allowed attempts
     * @param int $windowSeconds Time window in seconds
     * @return bool True if within limit
     */
    public function checkLoginAttempts(string $ipAddress, int $maxAttempts = 5, int $windowSeconds = 900): bool
    {
        // Clean old attempts
        $this->cleanOldAttempts($windowSeconds);
        
        // Count attempts within window
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) as count FROM login_attempts 
             WHERE ip_address = :ip_address 
             AND attempt_time > DATE_SUB(NOW(), INTERVAL :window SECOND)'
        );
        
        $stmt->execute([
            'ip_address' => $ipAddress,
            'window' => $windowSeconds
        ]);
        
        $result = $stmt->fetch();
        
        return ($result['count'] < $maxAttempts);
    }
    
    /**
     * Record login attempt
     * 
     * @param string $ipAddress IP address
     * @param string|null $email Email address (optional)
     * @return void
     */
    public function recordLoginAttempt(string $ipAddress, ?string $email = null): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO login_attempts (ip_address, email) VALUES (:ip_address, :email)'
        );
        
        $stmt->execute([
            'ip_address' => $ipAddress,
            'email' => $email
        ]);
    }
    
    /**
     * Check if password reset requests are within limit
     * 
     * @param string $email Email address to check
     * @param int $maxAttempts Maximum allowed attempts
     * @param int $windowSeconds Time window in seconds
     * @return bool True if within limit
     */
    public function checkResetAttempts(string $email, int $maxAttempts = 3, int $windowSeconds = 3600): bool
    {
        // Clean old reset requests
        $this->cleanOldResets();
        
        // Count reset requests within window
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) as count FROM password_resets 
             WHERE email = :email 
             AND created_at > DATE_SUB(NOW(), INTERVAL :window SECOND)'
        );
        
        $stmt->execute([
            'email' => $email,
            'window' => $windowSeconds
        ]);
        
        $result = $stmt->fetch();
        
        return ($result['count'] < $maxAttempts);
    }
    
    /**
     * Clean old login attempts
     * 
     * @param int $windowSeconds Time window in seconds
     * @return void
     */
    private function cleanOldAttempts(int $windowSeconds): void
    {
        $stmt = $this->db->prepare(
            'DELETE FROM login_attempts 
             WHERE attempt_time < DATE_SUB(NOW(), INTERVAL :window SECOND)'
        );
        
        $stmt->execute(['window' => $windowSeconds * 2]); // Keep double the window
    }
    
    /**
     * Clean expired password resets
     * 
     * @return void
     */
    private function cleanOldResets(): void
    {
        $stmt = $this->db->prepare('DELETE FROM password_resets WHERE expiry < NOW()');
        $stmt->execute();
    }
    
    /**
     * Get client IP address
     * 
     * @return string IP address
     */
    public static function getClientIp(): string
    {
        // Check for proxy headers (use with caution in production)
        $headers = [
            'HTTP_CF_CONNECTING_IP', // Cloudflare
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        ];
        
        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ip = $_SERVER[$header];
                
                // Handle comma-separated IPs
                if (str_contains($ip, ',')) {
                    $ip = explode(',', $ip)[0];
                }
                
                $ip = trim($ip);
                
                // Validate IP address
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }
        
        return '0.0.0.0';
    }
}
