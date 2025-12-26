<?php
declare(strict_types=1);

namespace App\Recovery;

use PDO;
use App\Security\InputValidator;
use App\Security\RateLimiter;
use App\Mail\MailService;

/**
 * Password Reset Service
 * 
 * Handles password reset requests and token verification
 * Security: Uses secure tokens with expiry and rate limiting
 */
class PasswordResetService
{
    private PDO $db;
    private RateLimiter $rateLimiter;
    private MailService $mailService;
    
    public function __construct(PDO $db, MailService $mailService)
    {
        $this->db = $db;
        $this->rateLimiter = new RateLimiter($db);
        $this->mailService = $mailService;
    }
    
    /**
     * Request password reset
     * 
     * @param string $email User email
     * @return array ['success' => bool, 'message' => string]
     */
    public function requestReset(string $email): array
    {
        // Validate email
        $email = InputValidator::validateEmail($email);
        if (!$email) {
            return ['success' => false, 'message' => 'Invalid email format.'];
        }
        
        // Check rate limiting
        require_once __DIR__ . '/../../config/config.php';
        \Config::load();
        
        $maxAttempts = \Config::get('security.rate_limit_reset', 3);
        $windowSeconds = \Config::get('security.rate_limit_reset_window', 3600);
        
        if (!$this->rateLimiter->checkResetAttempts($email, $maxAttempts, $windowSeconds)) {
            return [
                'success' => false,
                'message' => 'Too many reset requests. Please try again later.'
            ];
        }
        
        // Check if user exists (without revealing if email exists)
        $stmt = $this->db->prepare('SELECT id, email, username FROM users WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();
        
        // Always return generic success message to prevent information leakage
        if (!$user) {
            return [
                'success' => true,
                'message' => 'If an account with that email exists, a password reset link has been sent.'
            ];
        }
        
        // Delete old reset tokens for this email
        $deleteStmt = $this->db->prepare('DELETE FROM password_resets WHERE email = :email');
        $deleteStmt->execute(['email' => $email]);
        
        // Generate token with expiry
        $expiryMinutes = (int)(\Config::get('security.password_reset_expiry', 1800) / 60);
        $tokenData = TokenGenerator::generateWithExpiry($expiryMinutes);
        
        // Store hashed token in database
        $stmt = $this->db->prepare(
            'INSERT INTO password_resets (email, token_hash, expiry) VALUES (:email, :token_hash, :expiry)'
        );
        
        $stmt->execute([
            'email' => $email,
            'token_hash' => $tokenData['hash'],
            'expiry' => $tokenData['expiry']
        ]);
        
        // Send reset email with raw token
        $this->mailService->sendPasswordResetEmail($email, $user['username'], $tokenData['token']);
        
        return [
            'success' => true,
            'message' => 'If an account with that email exists, a password reset link has been sent.'
        ];
    }
    
    /**
     * Verify reset token
     * 
     * @param string $token Reset token
     * @return array ['valid' => bool, 'email' => string|null]
     */
    public function verifyToken(string $token): array
    {
        $tokenHash = TokenGenerator::hashToken($token);
        
        // Find valid token
        $stmt = $this->db->prepare(
            'SELECT email FROM password_resets 
             WHERE token_hash = :token_hash 
             AND expiry > NOW() 
             LIMIT 1'
        );
        
        $stmt->execute(['token_hash' => $tokenHash]);
        $result = $stmt->fetch();
        
        if (!$result) {
            return ['valid' => false, 'email' => null];
        }
        
        return ['valid' => true, 'email' => $result['email']];
    }
    
    /**
     * Reset password
     * 
     * @param string $token Reset token
     * @param string $newPassword New password
     * @return array ['success' => bool, 'message' => string]
     */
    public function resetPassword(string $token, string $newPassword): array
    {
        // Validate password
        if (!InputValidator::validatePassword($newPassword)) {
            return [
                'success' => false,
                'message' => 'Password must be at least 8 characters with uppercase, lowercase, and numbers.'
            ];
        }
        
        // Verify token
        $verification = $this->verifyToken($token);
        if (!$verification['valid']) {
            return [
                'success' => false,
                'message' => 'Invalid or expired reset token.'
            ];
        }
        
        $email = $verification['email'];
        
        // Hash new password
        $passwordHash = password_hash($newPassword, PASSWORD_ARGON2ID);
        
        // Update password
        $stmt = $this->db->prepare('UPDATE users SET password_hash = :password_hash WHERE email = :email');
        
        try {
            $stmt->execute([
                'password_hash' => $passwordHash,
                'email' => $email
            ]);
            
            // Delete used token
            $deleteStmt = $this->db->prepare('DELETE FROM password_resets WHERE email = :email');
            $deleteStmt->execute(['email' => $email]);
            
            return [
                'success' => true,
                'message' => 'Password has been reset successfully. You can now login with your new password.'
            ];
        } catch (\PDOException $e) {
            error_log('Password reset error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to reset password. Please try again.'
            ];
        }
    }
}
