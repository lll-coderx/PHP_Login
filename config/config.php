<?php
declare(strict_types=1);

/**
 * Application Configuration
 * 
 * Loads and provides access to application settings
 */
class Config
{
    private static ?array $config = null;
    
    /**
     * Load configuration from environment
     */
    public static function load(): void
    {
        if (self::$config === null) {
            // Ensure environment variables are loaded
            $envFile = __DIR__ . '/../.env';
            
            if (file_exists($envFile)) {
                $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                
                foreach ($lines as $line) {
                    if (str_starts_with(trim($line), '#')) {
                        continue;
                    }
                    
                    if (str_contains($line, '=')) {
                        [$key, $value] = explode('=', $line, 2);
                        $key = trim($key);
                        $value = trim($value);
                        
                        if (!array_key_exists($key, $_ENV)) {
                            $_ENV[$key] = $value;
                            putenv("{$key}={$value}");
                        }
                    }
                }
            }
            
            self::$config = [
                'app' => [
                    'name' => $_ENV['APP_NAME'] ?? 'PHP Login System',
                    'url' => $_ENV['APP_URL'] ?? 'http://localhost',
                ],
                'session' => [
                    'lifetime' => (int)($_ENV['SESSION_LIFETIME'] ?? 7200),
                ],
                'security' => [
                    'rate_limit_login' => (int)($_ENV['RATE_LIMIT_LOGIN'] ?? 5),
                    'rate_limit_login_window' => (int)($_ENV['RATE_LIMIT_LOGIN_WINDOW'] ?? 900),
                    'rate_limit_reset' => (int)($_ENV['RATE_LIMIT_RESET'] ?? 3),
                    'rate_limit_reset_window' => (int)($_ENV['RATE_LIMIT_RESET_WINDOW'] ?? 3600),
                    'password_reset_expiry' => (int)($_ENV['PASSWORD_RESET_EXPIRY'] ?? 1800),
                ],
                'mail' => [
                    'smtp_host' => $_ENV['SMTP_HOST'] ?? '',
                    'smtp_port' => (int)($_ENV['SMTP_PORT'] ?? 587),
                    'smtp_username' => $_ENV['SMTP_USERNAME'] ?? '',
                    'smtp_password' => $_ENV['SMTP_PASSWORD'] ?? '',
                    'from_email' => $_ENV['SMTP_FROM_EMAIL'] ?? '',
                    'from_name' => $_ENV['SMTP_FROM_NAME'] ?? 'PHP Login System',
                ],
            ];
        }
    }
    
    /**
     * Get configuration value
     * 
     * @param string $key Configuration key (dot notation supported)
     * @param mixed $default Default value if key not found
     * @return mixed Configuration value
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        self::load();
        
        $keys = explode('.', $key);
        $value = self::$config;
        
        foreach ($keys as $k) {
            if (!isset($value[$k])) {
                return $default;
            }
            $value = $value[$k];
        }
        
        return $value;
    }
}
