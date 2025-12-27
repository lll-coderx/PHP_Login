<?php
declare(strict_types=1);

/**
 * Database Configuration and Connection
 * 
 * Implements Singleton Pattern for PDO connection
 * Security: Uses PDO with prepared statements to prevent SQL injection
 */
class Database
{
    private static ?PDO $instance = null;
    
    /**
     * Private constructor to prevent direct instantiation
     */
    private function __construct()
    {
    }
    
    /**
     * Get PDO instance (Singleton Pattern)
     * 
     * @return PDO Database connection instance
     * @throws PDOException If connection fails
     */
    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            // Load environment variables
            self::loadEnv();
            
            // Validate required environment variables
            $requiredVars = ['DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASS'];
            foreach ($requiredVars as $var) {
                if (!isset($_ENV[$var])) {
                    throw new RuntimeException("Missing required environment variable: {$var}");
                }
            }
            
            // Build DSN with utf8mb4 charset
            $dsn = sprintf(
                'mysql:host=%s;dbname=%s;charset=utf8mb4',
                $_ENV['DB_HOST'],
                $_ENV['DB_NAME']
            );
            
            try {
                // Create PDO instance with security settings
                self::$instance = new PDO(
                    $dsn,
                    $_ENV['DB_USER'],
                    $_ENV['DB_PASS'],
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES => false, // Use real prepared statements
                        PDO::ATTR_PERSISTENT => false,
                        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
                    ]
                );
            } catch (PDOException $e) {
                // Log error without exposing sensitive information
                error_log('Database connection failed: ' . $e->getMessage());
                throw new PDOException('Database connection failed. Please check your configuration.');
            }
        }
        
        return self::$instance;
    }
    
    /**
     * Load environment variables from .env file
     */
    private static function loadEnv(): void
    {
        $envFile = __DIR__ . '/../.env';
        
        if (!file_exists($envFile)) {
            throw new RuntimeException('.env file not found. Please copy .env.example to .env and configure it.');
        }
        
        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        foreach ($lines as $line) {
            // Skip comments
            if (str_starts_with(trim($line), '#')) {
                continue;
            }
            
            // Parse KEY=VALUE
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
    
    /**
     * Prevent cloning of the instance
     */
    private function __clone(): void
    {
    }
    
    /**
     * Prevent unserialization of the instance
     */
    public function __wakeup(): void
    {
        throw new Exception("Cannot unserialize singleton");
    }
}
