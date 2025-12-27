<?php
declare(strict_types=1);

namespace App\Recovery;

/**
 * Token Generator
 * 
 * Generates cryptographically secure tokens for password reset
 * Security: Uses random_bytes for secure token generation
 */
class TokenGenerator
{
    /**
     * Generate secure random token
     * 
     * @param int $length Token length (default: 32 bytes = 64 hex characters)
     * @return string Raw token (not hashed)
     */
    public static function generateToken(int $length = 32): string
    {
        return bin2hex(random_bytes($length));
    }
    
    /**
     * Hash token for storage
     * 
     * @param string $token Raw token
     * @return string Hashed token
     */
    public static function hashToken(string $token): string
    {
        return hash('sha256', $token);
    }
    
    /**
     * Generate token with expiry
     * 
     * @param int $expiryMinutes Expiry time in minutes (default: 30)
     * @return array ['token' => raw token, 'hash' => hashed token, 'expiry' => expiry datetime]
     */
    public static function generateWithExpiry(int $expiryMinutes = 30): array
    {
        $token = self::generateToken();
        $hash = self::hashToken($token);
        $expiry = date('Y-m-d H:i:s', time() + ($expiryMinutes * 60));
        
        return [
            'token' => $token,
            'hash' => $hash,
            'expiry' => $expiry
        ];
    }
    
    /**
     * Verify token against hash
     * 
     * @param string $token Raw token
     * @param string $hash Stored hash
     * @return bool True if token matches hash
     */
    public static function verifyToken(string $token, string $hash): bool
    {
        return hash_equals($hash, self::hashToken($token));
    }
}
