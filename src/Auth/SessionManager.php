<?php
declare(strict_types=1);

namespace App\Auth;

/**
 * Session Manager
 * 
 * Manages secure sessions with proper security flags
 * Security: Implements OWASP session management best practices
 */
class SessionManager
{
    /**
     * Start secure session
     * 
     * @return void
     */
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            // Set secure session cookie parameters
            session_set_cookie_params([
                'lifetime' => 0, // Session cookie
                'path' => '/',
                'domain' => '',
                'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on', // HTTPS only in production
                'httponly' => true, // Prevent JavaScript access
                'samesite' => 'Strict' // CSRF protection
            ]);
            
            // Use strict session ID format
            ini_set('session.use_strict_mode', '1');
            
            // Prevent session fixation
            ini_set('session.use_only_cookies', '1');
            
            // Use strong session ID
            ini_set('session.sid_length', '48');
            ini_set('session.sid_bits_per_character', '6');
            
            session_start();
            
            // Regenerate session ID periodically
            if (!isset($_SESSION['created'])) {
                $_SESSION['created'] = time();
            } elseif (time() - $_SESSION['created'] > 1800) {
                // Regenerate session ID every 30 minutes
                self::regenerate();
            }
        }
    }
    
    /**
     * Regenerate session ID
     * 
     * @return void
     */
    public static function regenerate(): void
    {
        session_regenerate_id(true);
        $_SESSION['created'] = time();
    }
    
    /**
     * Destroy session
     * 
     * @return void
     */
    public static function destroy(): void
    {
        $_SESSION = [];
        
        // Delete session cookie
        if (isset($_COOKIE[session_name()])) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }
        
        session_destroy();
    }
    
    /**
     * Set session variable
     * 
     * @param string $key Variable key
     * @param mixed $value Variable value
     * @return void
     */
    public static function set(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }
    
    /**
     * Get session variable
     * 
     * @param string $key Variable key
     * @param mixed $default Default value if key not found
     * @return mixed Variable value or default
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }
    
    /**
     * Check if session variable exists
     * 
     * @param string $key Variable key
     * @return bool True if variable exists
     */
    public static function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }
    
    /**
     * Remove session variable
     * 
     * @param string $key Variable key
     * @return void
     */
    public static function remove(string $key): void
    {
        unset($_SESSION[$key]);
    }
    
    /**
     * Check if user is logged in
     * 
     * @return bool True if user is logged in
     */
    public static function isLoggedIn(): bool
    {
        return self::has('user_id') && self::has('user_email');
    }
    
    /**
     * Get logged in user ID
     * 
     * @return int|null User ID or null if not logged in
     */
    public static function getUserId(): ?int
    {
        return self::get('user_id');
    }
    
    /**
     * Get logged in user email
     * 
     * @return string|null User email or null if not logged in
     */
    public static function getUserEmail(): ?string
    {
        return self::get('user_email');
    }
}
