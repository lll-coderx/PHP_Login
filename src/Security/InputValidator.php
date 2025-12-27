<?php
declare(strict_types=1);

namespace App\Security;

/**
 * Input Validator
 * 
 * Validates and sanitizes user input to prevent XSS and other injection attacks
 * Security: Implements OWASP input validation best practices
 */
class InputValidator
{
    /**
     * Validate and sanitize email
     * 
     * @param string $email Email address to validate
     * @return string|null Sanitized email or null if invalid
     */
    public static function validateEmail(string $email): ?string
    {
        $email = trim($email);
        $email = filter_var($email, FILTER_SANITIZE_EMAIL);
        
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $email;
        }
        
        return null;
    }
    
    /**
     * Validate username
     * 
     * @param string $username Username to validate
     * @return string|null Sanitized username or null if invalid
     */
    public static function validateUsername(string $username): ?string
    {
        $username = trim($username);
        
        // Username must be 3-50 characters, alphanumeric and underscores only
        if (preg_match('/^[a-zA-Z0-9_]{3,50}$/', $username)) {
            return $username;
        }
        
        return null;
    }
    
    /**
     * Validate password strength
     * 
     * @param string $password Password to validate
     * @return bool True if password meets strength requirements
     */
    public static function validatePassword(string $password): bool
    {
        // Password must be at least 8 characters
        if (strlen($password) < 8) {
            return false;
        }
        
        // Password must contain at least one uppercase letter
        if (!preg_match('/[A-Z]/', $password)) {
            return false;
        }
        
        // Password must contain at least one lowercase letter
        if (!preg_match('/[a-z]/', $password)) {
            return false;
        }
        
        // Password must contain at least one number
        if (!preg_match('/[0-9]/', $password)) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Sanitize output to prevent XSS
     * 
     * @param string $string String to sanitize
     * @return string Sanitized string
     */
    public static function escapeHtml(string $string): string
    {
        return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Get and escape POST value
     * 
     * @param string $key POST key
     * @param string $default Default value if key not found
     * @return string Escaped value
     */
    public static function getPostValue(string $key, string $default = ''): string
    {
        $value = $_POST[$key] ?? $default;
        return self::escapeHtml($value);
    }
    
    /**
     * Generate CSRF token
     * 
     * @return string CSRF token
     */
    public static function generateCsrfToken(): string
    {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        
        return $_SESSION['csrf_token'];
    }
    
    /**
     * Verify CSRF token
     * 
     * @param string $token Token to verify
     * @return bool True if token is valid
     */
    public static function verifyCsrfToken(string $token): bool
    {
        if (!isset($_SESSION['csrf_token'])) {
            return false;
        }
        
        return hash_equals($_SESSION['csrf_token'], $token);
    }
}
