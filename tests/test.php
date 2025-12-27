<?php
declare(strict_types=1);

/**
 * Basic Test Suite for PHP Login System
 * 
 * Tests core security components without requiring database
 */

echo "=== PHP Login System Test Suite ===\n\n";

// Test 1: InputValidator
echo "Test 1: InputValidator\n";
require_once __DIR__ . '/../src/Security/InputValidator.php';
use App\Security\InputValidator;

// Test email validation
$validEmail = InputValidator::validateEmail('test@example.com');
$invalidEmail = InputValidator::validateEmail('invalid-email');
assert($validEmail === 'test@example.com', 'Valid email should pass');
assert($invalidEmail === null, 'Invalid email should fail');
echo "✓ Email validation works\n";

// Test username validation
$validUsername = InputValidator::validateUsername('testuser123');
$invalidUsername = InputValidator::validateUsername('ab'); // too short
assert($validUsername === 'testuser123', 'Valid username should pass');
assert($invalidUsername === null, 'Invalid username should fail');
echo "✓ Username validation works\n";

// Test password validation
$validPassword = InputValidator::validatePassword('TestPass123');
$invalidPassword = InputValidator::validatePassword('weak'); // too short, no uppercase, no number
assert($validPassword === true, 'Valid password should pass');
assert($invalidPassword === false, 'Invalid password should fail');
echo "✓ Password validation works\n";

// Test HTML escaping
$unsafe = '<script>alert("XSS")</script>';
$safe = InputValidator::escapeHtml($unsafe);
assert(!str_contains($safe, '<script>'), 'Script tags should be escaped');
echo "✓ HTML escaping works\n";

echo "\n";

// Test 2: TokenGenerator
echo "Test 2: TokenGenerator\n";
require_once __DIR__ . '/../src/Recovery/TokenGenerator.php';
use App\Recovery\TokenGenerator;

// Test token generation
$token1 = TokenGenerator::generateToken();
$token2 = TokenGenerator::generateToken();
assert(strlen($token1) === 64, 'Token should be 64 characters');
assert($token1 !== $token2, 'Tokens should be unique');
echo "✓ Token generation works\n";

// Test token hashing
$hash1 = TokenGenerator::hashToken($token1);
$hash2 = TokenGenerator::hashToken($token1);
assert(strlen($hash1) === 64, 'Hash should be 64 characters (SHA-256)');
assert($hash1 === $hash2, 'Same token should produce same hash');
echo "✓ Token hashing works\n";

// Test token verification
$isValid = TokenGenerator::verifyToken($token1, $hash1);
$isInvalid = TokenGenerator::verifyToken('wrongtoken', $hash1);
assert($isValid === true, 'Correct token should verify');
assert($isInvalid === false, 'Wrong token should not verify');
echo "✓ Token verification works\n";

// Test token with expiry
$tokenData = TokenGenerator::generateWithExpiry(30);
assert(isset($tokenData['token']), 'Token data should have token');
assert(isset($tokenData['hash']), 'Token data should have hash');
assert(isset($tokenData['expiry']), 'Token data should have expiry');
echo "✓ Token with expiry generation works\n";

echo "\n";

// Test 3: Password Hashing
echo "Test 3: Password Security\n";

// Test password hashing
$password = 'TestPassword123!';
$hash = password_hash($password, PASSWORD_ARGON2ID);
assert(password_verify($password, $hash), 'Password should verify against hash');
assert(!password_verify('WrongPassword', $hash), 'Wrong password should not verify');
echo "✓ Password hashing (Argon2ID) works\n";

// Test password needs rehash
$oldHash = password_hash($password, PASSWORD_BCRYPT);
$needsRehash = password_needs_rehash($oldHash, PASSWORD_ARGON2ID);
assert($needsRehash === true, 'BCrypt hash should need rehash to Argon2ID');
echo "✓ Password rehash detection works\n";

echo "\n";

// Test 4: Config Loading
echo "Test 4: Configuration\n";
if (file_exists(__DIR__ . '/../.env')) {
    require_once __DIR__ . '/../config/config.php';
    Config::load();
    $appName = Config::get('app.name', 'Default');
    assert(!empty($appName), 'Config should load app name');
    echo "✓ Configuration loading works\n";
} else {
    echo "⚠ .env file not found, skipping config test\n";
}

echo "\n";

// Test 5: CSRF Token
echo "Test 5: CSRF Protection\n";
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$token = InputValidator::generateCsrfToken();
assert(!empty($token), 'CSRF token should be generated');
assert(strlen($token) === 64, 'CSRF token should be 64 characters');

$isValid = InputValidator::verifyCsrfToken($token);
assert($isValid === true, 'Valid CSRF token should verify');

$isInvalid = InputValidator::verifyCsrfToken('invalid');
assert($isInvalid === false, 'Invalid CSRF token should not verify');
echo "✓ CSRF protection works\n";

echo "\n";

// Summary
echo "=== Test Summary ===\n";
echo "✅ All core security components are working correctly!\n";
echo "\nComponents tested:\n";
echo "- Input validation (email, username, password)\n";
echo "- XSS prevention (HTML escaping)\n";
echo "- Token generation and verification\n";
echo "- Password hashing (Argon2ID)\n";
echo "- CSRF protection\n";
echo "- Configuration loading\n";
echo "\nNext steps:\n";
echo "1. Copy .env.example to .env and configure\n";
echo "2. Create database and import schema.sql\n";
echo "3. Install dependencies: composer install\n";
echo "4. Configure web server to point to /public directory\n";
