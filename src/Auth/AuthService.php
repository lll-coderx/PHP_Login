<?php
declare(strict_types=1);

namespace App\Auth;

use PDO;
use App\Security\InputValidator;
use App\Security\RateLimiter;

/**
 * Authentication Service
 * 
 * Handles user authentication (login/logout)
 * Security: Implements secure password verification and session management
 */
class AuthService
{
    private PDO $db;
    private RateLimiter $rateLimiter;
    
    public function __construct(PDO $db)
    {
        $this->db = $db;
        $this->rateLimiter = new RateLimiter($db);
    }
    
    /**
     * Authenticate user
     * 
     * @param string $email User email
     * @param string $password User password
     * @return array ['success' => bool, 'message' => string]
     */
    public function login(string $email, string $password): array
    {
        // 1. Validate input
        $email = InputValidator::validateEmail($email);
        if (!$email) {
            return ['success' => false, 'message' => 'Invalid email format.'];
        }
        
        // 2. Check rate limiting
        $ipAddress = RateLimiter::getClientIp();
        require_once __DIR__ . '/../../config/config.php';
        \Config::load();
        
        $maxAttempts = \Config::get('security.rate_limit_login', 5);
        $windowSeconds = \Config::get('security.rate_limit_login_window', 900);
        
        if (!$this->rateLimiter->checkLoginAttempts($ipAddress, $maxAttempts, $windowSeconds)) {
            return [
                'success' => false,
                'message' => 'Too many login attempts. Please try again later.'
            ];
        }
        
        // 3. Retrieve user with prepared statement
        $stmt = $this->db->prepare(
            'SELECT id, username, email, password_hash FROM users WHERE email = :email LIMIT 1'
        );
        
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();
        
        // 4. Record login attempt
        $this->rateLimiter->recordLoginAttempt($ipAddress, $email);
        
        // 5. Verify user exists and password is correct
        if (!$user || !password_verify($password, $user['password_hash'])) {
            // Generic error message to prevent information leakage
            return [
                'success' => false,
                'message' => 'Invalid email or password.'
            ];
        }
        
        // 6. Check if password needs rehashing (algorithm upgrade)
        if (password_needs_rehash($user['password_hash'], PASSWORD_ARGON2ID)) {
            $newHash = password_hash($password, PASSWORD_ARGON2ID);
            $updateStmt = $this->db->prepare('UPDATE users SET password_hash = :hash WHERE id = :id');
            $updateStmt->execute([
                'hash' => $newHash,
                'id' => $user['id']
            ]);
        }
        
        // 7. Regenerate session ID to prevent session fixation
        SessionManager::regenerate();
        
        // 8. Set session variables
        SessionManager::set('user_id', $user['id']);
        SessionManager::set('user_email', $user['email']);
        SessionManager::set('user_username', $user['username']);
        
        return [
            'success' => true,
            'message' => 'Login successful.'
        ];
    }
    
    /**
     * Log out user
     * 
     * @return void
     */
    public function logout(): void
    {
        SessionManager::destroy();
    }
    
    /**
     * Register new user
     * 
     * @param string $username Username
     * @param string $email Email address
     * @param string $password Password
     * @return array ['success' => bool, 'message' => string]
     */
    public function register(string $username, string $email, string $password): array
    {
        // Validate input
        $username = InputValidator::validateUsername($username);
        if (!$username) {
            return [
                'success' => false,
                'message' => 'Invalid username. Must be 3-50 characters, alphanumeric and underscores only.'
            ];
        }
        
        $email = InputValidator::validateEmail($email);
        if (!$email) {
            return ['success' => false, 'message' => 'Invalid email format.'];
        }
        
        if (!InputValidator::validatePassword($password)) {
            return [
                'success' => false,
                'message' => 'Password must be at least 8 characters with uppercase, lowercase, and numbers.'
            ];
        }
        
        // Check if username or email already exists
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) as count FROM users WHERE username = :username OR email = :email'
        );
        $stmt->execute(['username' => $username, 'email' => $email]);
        $result = $stmt->fetch();
        
        if ($result['count'] > 0) {
            return [
                'success' => false,
                'message' => 'Username or email already exists.'
            ];
        }
        
        // Hash password with ARGON2ID
        $passwordHash = password_hash($password, PASSWORD_ARGON2ID);
        
        // Insert user
        $stmt = $this->db->prepare(
            'INSERT INTO users (username, email, password_hash) VALUES (:username, :email, :password_hash)'
        );
        
        try {
            $stmt->execute([
                'username' => $username,
                'email' => $email,
                'password_hash' => $passwordHash
            ]);
            
            return [
                'success' => true,
                'message' => 'Registration successful. You can now login.'
            ];
        } catch (\PDOException $e) {
            error_log('Registration error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Registration failed. Please try again.'
            ];
        }
    }
    
    /**
     * Check if user is authenticated
     * 
     * @return bool True if user is logged in
     */
    public function isAuthenticated(): bool
    {
        return SessionManager::isLoggedIn();
    }
}
