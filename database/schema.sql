-- ========================================
-- PHP Login System Database Schema
-- ========================================
-- Version: 1.0
-- Description: Secure database schema for login and password reset system
-- Security: SQL Injection Prevention via PDO Prepared Statements
-- ========================================

-- Drop tables if they exist (for clean setup)
DROP TABLE IF EXISTS login_attempts;
DROP TABLE IF EXISTS password_resets;
DROP TABLE IF EXISTS users;

-- ========================================
-- 1. Users Table
-- ========================================
-- Stores user account information with secure password hashing
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- 2. Password Resets Table
-- ========================================
-- Stores password reset tokens (hashed for security)
CREATE TABLE password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100) NOT NULL,
    token_hash VARCHAR(64) NOT NULL UNIQUE,
    expiry DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_token_hash (token_hash),
    INDEX idx_expiry (expiry),
    FOREIGN KEY (email) REFERENCES users(email) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- 3. Login Attempts Table
-- ========================================
-- Tracks login attempts for rate limiting and brute force protection
CREATE TABLE login_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ip_address VARCHAR(45) NOT NULL,
    email VARCHAR(100),
    attempt_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_ip_address (ip_address),
    INDEX idx_email (email),
    INDEX idx_attempt_time (attempt_time)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- Sample User (for testing)
-- ========================================
-- Username: testuser
-- Password: TestPassword123!
-- Note: This is hashed with PASSWORD_ARGON2ID
-- INSERT INTO users (username, email, password_hash) 
-- VALUES ('testuser', 'test@example.com', '$argon2id$v=19$m=65536,t=4,p=1$...');
