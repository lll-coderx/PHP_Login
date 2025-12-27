# Quick Reference Guide

## Common Tasks

### Adding a New User
```php
require_once 'config/database.php';
require_once 'src/Auth/AuthService.php';

$db = Database::getInstance();
$auth = new App\Auth\AuthService($db);

$result = $auth->register('username', 'email@example.com', 'Password123');
if ($result['success']) {
    echo "User created successfully!";
}
```

### Checking Authentication
```php
require_once 'src/Auth/SessionManager.php';
use App\Auth\SessionManager;

SessionManager::start();

if (SessionManager::isLoggedIn()) {
    $userId = SessionManager::getUserId();
    $userEmail = SessionManager::getUserEmail();
    // User is authenticated
}
```

### Validating Input
```php
use App\Security\InputValidator;

// Email
$email = InputValidator::validateEmail($_POST['email']);
if (!$email) {
    die('Invalid email');
}

// Username
$username = InputValidator::validateUsername($_POST['username']);
if (!$username) {
    die('Invalid username');
}

// Password strength
if (!InputValidator::validatePassword($password)) {
    die('Weak password');
}

// Escape output
echo InputValidator::escapeHtml($userInput);

// Get and escape POST value
$value = InputValidator::getPostValue('field_name');
```

### CSRF Protection
```php
// Generate token in form
<input type="hidden" name="csrf_token" value="<?php echo InputValidator::generateCsrfToken(); ?>">

// Verify token on submission
if (!InputValidator::verifyCsrfToken($_POST['csrf_token'])) {
    die('Invalid CSRF token');
}
```

### Database Queries
```php
$db = Database::getInstance();

// SELECT with prepared statement
$stmt = $db->prepare('SELECT * FROM users WHERE email = :email');
$stmt->execute(['email' => $email]);
$user = $stmt->fetch();

// INSERT
$stmt = $db->prepare('INSERT INTO users (username, email, password_hash) VALUES (:username, :email, :hash)');
$stmt->execute([
    'username' => $username,
    'email' => $email,
    'hash' => password_hash($password, PASSWORD_ARGON2ID)
]);

// UPDATE
$stmt = $db->prepare('UPDATE users SET password_hash = :hash WHERE id = :id');
$stmt->execute(['hash' => $newHash, 'id' => $userId]);

// DELETE
$stmt = $db->prepare('DELETE FROM users WHERE id = :id');
$stmt->execute(['id' => $userId]);
```

### Password Hashing
```php
// Hash password
$hash = password_hash($password, PASSWORD_ARGON2ID);

// Verify password
if (password_verify($password, $hash)) {
    echo "Password correct";
}

// Check if rehash needed
if (password_needs_rehash($hash, PASSWORD_ARGON2ID)) {
    $newHash = password_hash($password, PASSWORD_ARGON2ID);
    // Update database with new hash
}
```

### Token Generation
```php
use App\Recovery\TokenGenerator;

// Generate token
$token = TokenGenerator::generateToken(); // 64 characters

// Hash token
$hash = TokenGenerator::hashToken($token);

// Verify token
if (TokenGenerator::verifyToken($token, $hash)) {
    echo "Token valid";
}

// Generate with expiry
$data = TokenGenerator::generateWithExpiry(30); // 30 minutes
// Returns: ['token' => raw, 'hash' => hashed, 'expiry' => datetime]
```

### Rate Limiting
```php
use App\Security\RateLimiter;

$db = Database::getInstance();
$rateLimiter = new RateLimiter($db);

// Check login attempts
$ip = RateLimiter::getClientIp();
if (!$rateLimiter->checkLoginAttempts($ip, 5, 900)) {
    die('Too many attempts');
}

// Record attempt
$rateLimiter->recordLoginAttempt($ip, $email);

// Check reset attempts
if (!$rateLimiter->checkResetAttempts($email, 3, 3600)) {
    die('Too many reset requests');
}
```

### Sending Emails
```php
use App\Mail\MailService;

$mailService = new MailService();
$mailService->sendPasswordResetEmail($email, $username, $token);
```

### Configuration
```php
require_once 'config/config.php';
Config::load();

// Get configuration
$appName = Config::get('app.name');
$appUrl = Config::get('app.url');
$smtpHost = Config::get('mail.smtp_host');
$rateLimit = Config::get('security.rate_limit_login', 5);
```

## Security Checklist for New Features

When adding new features, ensure:

- [ ] All user input is validated
- [ ] All output is escaped with `InputValidator::escapeHtml()`
- [ ] All SQL queries use prepared statements
- [ ] CSRF tokens are used for state-changing operations
- [ ] Rate limiting is applied where appropriate
- [ ] Error messages don't leak sensitive information
- [ ] Sessions are properly managed
- [ ] Passwords are never stored in plain text
- [ ] Files have proper permissions
- [ ] Code is tested before deployment

## Common SQL Queries

### Find user by email
```sql
SELECT id, username, email, password_hash 
FROM users 
WHERE email = :email 
LIMIT 1
```

### Find valid reset token
```sql
SELECT email 
FROM password_resets 
WHERE token_hash = :token_hash 
AND expiry > NOW() 
LIMIT 1
```

### Count login attempts
```sql
SELECT COUNT(*) as count 
FROM login_attempts 
WHERE ip_address = :ip_address 
AND attempt_time > DATE_SUB(NOW(), INTERVAL :window SECOND)
```

### Clean expired tokens
```sql
DELETE FROM password_resets 
WHERE expiry < NOW()
```

### Find users created today
```sql
SELECT id, username, email, created_at 
FROM users 
WHERE DATE(created_at) = CURDATE()
```

## Environment Variables

```env
# Required
DB_HOST=localhost
DB_NAME=php_login_db
DB_USER=root
DB_PASS=your_password

# Optional (for email)
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USERNAME=your_email@gmail.com
SMTP_PASSWORD=your_app_password
SMTP_FROM_EMAIL=noreply@domain.com
SMTP_FROM_NAME=Your App

# Application
APP_URL=http://localhost
APP_NAME=My App
SESSION_LIFETIME=7200

# Security
RATE_LIMIT_LOGIN=5
RATE_LIMIT_LOGIN_WINDOW=900
RATE_LIMIT_RESET=3
RATE_LIMIT_RESET_WINDOW=3600
PASSWORD_RESET_EXPIRY=1800
```

## File Permissions

```bash
# Secure .env
chmod 600 .env

# Standard file permissions
find . -type f -exec chmod 644 {} \;
find . -type d -exec chmod 755 {} \;

# Make scripts executable (if any)
chmod +x scripts/*.sh
```

## Testing

```bash
# Run test suite
php tests/test.php

# Check PHP syntax
find . -name "*.php" -exec php -l {} \;

# Check for common issues
grep -r "echo \$_" . --include="*.php"  # Unescaped output
grep -r "mysql_" . --include="*.php"    # Old MySQL functions
grep -r "md5(" . --include="*.php"      # Weak hashing
```

## Debugging

```bash
# Check Apache error log
tail -f /var/log/apache2/error.log

# Check PHP error log
tail -f /var/log/php/error.log

# Enable PHP errors (development only)
ini_set('display_errors', 1);
error_reporting(E_ALL);
```

## Useful MySQL Commands

```sql
-- Show all users
SELECT id, username, email, created_at FROM users;

-- Show recent login attempts
SELECT ip_address, email, attempt_time 
FROM login_attempts 
ORDER BY attempt_time DESC 
LIMIT 20;

-- Show active reset tokens
SELECT email, expiry 
FROM password_resets 
WHERE expiry > NOW();

-- Clear old data
DELETE FROM login_attempts WHERE attempt_time < DATE_SUB(NOW(), INTERVAL 24 HOUR);
DELETE FROM password_resets WHERE expiry < NOW();

-- Reset failed login attempts
TRUNCATE TABLE login_attempts;
```

## Performance Tips

1. **Database Indexes**: Already created on frequently queried columns
2. **Session Storage**: Consider Redis for high-traffic sites
3. **Rate Limiting**: Add caching layer for better performance
4. **Email Queue**: Use queue system for async email sending
5. **CDN**: Serve static assets from CDN in production

## Common Issues

### Issue: "Headers already sent"
**Cause**: Output before redirect
**Fix**: Check for whitespace before `<?php` tags

### Issue: "Session not persisting"
**Cause**: Session directory permissions
**Fix**: `chmod 700 /var/lib/php/sessions`

### Issue: "CSRF token invalid"
**Cause**: Session not started or token mismatch
**Fix**: Ensure `SessionManager::start()` is called

### Issue: "Password reset email not received"
**Cause**: SMTP configuration or spam folder
**Fix**: Check SMTP credentials and spam folder

## Production Deployment

```bash
# 1. Update environment
cp .env.example .env
nano .env

# 2. Install dependencies
composer install --no-dev --optimize-autoloader

# 3. Set permissions
chmod 600 .env
find . -type f -exec chmod 644 {} \;
find . -type d -exec chmod 755 {} \;

# 4. Import database
mysql -u user -p database < database/schema.sql

# 5. Configure Apache
# Point DocumentRoot to /public directory

# 6. Enable HTTPS
# Update .htaccess to force HTTPS

# 7. Test
php tests/test.php
```

---

For more information, see:
- README.md - Overview and features
- INSTALLATION.md - Detailed setup guide
- SECURITY.md - Security best practices
