# Security Guidelines

## Overview

This document outlines the security measures implemented in the PHP Login System and provides guidelines for maintaining security.

## OWASP Top 10 Coverage

### ✅ 1. Broken Access Control
**Implemented Protections**:
- Session-based authentication with secure flags
- Protected routes check authentication status
- Session ID regeneration after login
- Proper logout with session destruction

**Code Example**:
```php
// In protected pages
if (!SessionManager::isLoggedIn()) {
    header('Location: /public/login.php');
    exit;
}
```

### ✅ 2. Cryptographic Failures
**Implemented Protections**:
- Argon2ID password hashing (strongest available)
- Secure token generation with `random_bytes()`
- Token hashing with SHA-256 before storage
- HTTPS recommended for production

**Code Example**:
```php
// Password hashing
$hash = password_hash($password, PASSWORD_ARGON2ID);

// Token generation
$token = bin2hex(random_bytes(32));
$hash = hash('sha256', $token);
```

### ✅ 3. Injection (SQL)
**Implemented Protections**:
- PDO prepared statements for all queries
- Parameter binding with type hints
- No string concatenation in SQL
- `PDO::ATTR_EMULATE_PREPARES = false`

**Code Example**:
```php
// Safe query with prepared statement
$stmt = $db->prepare('SELECT * FROM users WHERE email = :email');
$stmt->execute(['email' => $email]);

// NEVER do this:
// $query = "SELECT * FROM users WHERE email = '$email'"; // VULNERABLE!
```

### ✅ 4. Insecure Design
**Implemented Protections**:
- Rate limiting on authentication attempts
- Generic error messages (no information leakage)
- Token expiry on password resets
- Single-use reset tokens

### ✅ 5. Security Misconfiguration
**Implemented Protections**:
- Security headers in `.htaccess`
- Secure session cookie configuration
- PHP error display disabled in production
- File permissions properly set

**Security Headers**:
```apache
X-Content-Type-Options: nosniff
X-Frame-Options: SAMEORIGIN
X-XSS-Protection: 1; mode=block
Referrer-Policy: strict-origin-when-cross-origin
Content-Security-Policy: default-src 'self'
```

### ✅ 6. Vulnerable and Outdated Components
**Implemented Protections**:
- Composer for dependency management
- PHPMailer (latest version)
- PHP 8.1+ requirement
- Regular update recommendations

**Update Command**:
```bash
composer update
```

### ✅ 7. Identification and Authentication Failures
**Implemented Protections**:
- Strong password requirements
- Rate limiting (5 attempts per 15 minutes)
- Secure session management
- Account lockout after failed attempts

**Password Requirements**:
- Minimum 8 characters
- At least one uppercase letter
- At least one lowercase letter
- At least one number

### ✅ 8. Software and Data Integrity Failures
**Implemented Protections**:
- CSRF tokens on all forms
- Token validation before processing
- Secure token generation
- No unsigned or unverified code execution

**CSRF Protection**:
```php
// Generate token
<input type="hidden" name="csrf_token" value="<?php echo InputValidator::generateCsrfToken(); ?>">

// Verify token
if (!InputValidator::verifyCsrfToken($_POST['csrf_token'])) {
    die('Invalid request');
}
```

### ✅ 9. Security Logging and Monitoring Failures
**Implemented Protections**:
- Login attempts logged to database
- Error logging to PHP error log
- IP address tracking
- Failed attempt monitoring

**Monitoring Query**:
```sql
SELECT ip_address, COUNT(*) as attempts 
FROM login_attempts 
WHERE attempt_time > DATE_SUB(NOW(), INTERVAL 1 HOUR)
GROUP BY ip_address 
HAVING attempts > 5;
```

### ✅ 10. Server-Side Request Forgery (SSRF)
**Implemented Protections**:
- No external URL requests based on user input
- Email validation and sanitization
- Input validation on all user data

## Additional Security Measures

### Input Validation

All user input is validated and sanitized:

```php
// Email validation
$email = InputValidator::validateEmail($_POST['email']);

// Username validation
$username = InputValidator::validateUsername($_POST['username']);

// Password validation
if (!InputValidator::validatePassword($password)) {
    // Reject weak password
}
```

### Output Encoding (XSS Prevention)

All output is escaped:

```php
// Always escape output
echo InputValidator::escapeHtml($userInput);

// Equivalent to:
echo htmlspecialchars($userInput, ENT_QUOTES, 'UTF-8');
```

### Rate Limiting

Brute force protection:

```php
// Login rate limiting
if (!$rateLimiter->checkLoginAttempts($ip, 5, 900)) {
    // Block attempt
}

// Password reset rate limiting
if (!$rateLimiter->checkResetAttempts($email, 3, 3600)) {
    // Block attempt
}
```

## Security Best Practices

### For Developers

1. **Never Trust User Input**
   - Always validate and sanitize
   - Use whitelisting over blacklisting
   - Validate on server-side, not just client-side

2. **Secure Password Handling**
   ```php
   // Good
   $hash = password_hash($password, PASSWORD_ARGON2ID);
   if (password_verify($password, $hash)) { }
   
   // Bad - NEVER do this
   if ($password === $storedPassword) { } // Plain text
   if (md5($password) === $hash) { } // Weak hashing
   ```

3. **SQL Injection Prevention**
   ```php
   // Good - Prepared statement
   $stmt = $db->prepare('SELECT * FROM users WHERE id = :id');
   $stmt->execute(['id' => $id]);
   
   // Bad - NEVER do this
   $query = "SELECT * FROM users WHERE id = $id"; // Vulnerable
   ```

4. **XSS Prevention**
   ```php
   // Good
   echo htmlspecialchars($userInput, ENT_QUOTES, 'UTF-8');
   
   // Bad
   echo $userInput; // Vulnerable
   ```

5. **Session Security**
   ```php
   // Always regenerate session ID after login
   session_regenerate_id(true);
   
   // Use secure session configuration
   session_set_cookie_params([
       'secure' => true,
       'httponly' => true,
       'samesite' => 'Strict'
   ]);
   ```

### For System Administrators

1. **Server Configuration**
   - Use HTTPS (TLS 1.2 or higher)
   - Disable directory listing
   - Hide server version information
   - Set proper file permissions

2. **PHP Configuration** (php.ini)
   ```ini
   display_errors = Off
   log_errors = On
   error_log = /var/log/php/error.log
   expose_php = Off
   session.cookie_httponly = 1
   session.cookie_secure = 1
   session.cookie_samesite = Strict
   ```

3. **Database Security**
   - Use least privilege principle
   - Separate database user for application
   - Regular backups
   - Strong passwords

4. **File Permissions**
   ```bash
   # Files: 644
   find . -type f -exec chmod 644 {} \;
   
   # Directories: 755
   find . -type d -exec chmod 755 {} \;
   
   # .env: 600 (most restrictive)
   chmod 600 .env
   ```

## Security Checklist

### Development
- [ ] All user input validated
- [ ] All output escaped
- [ ] Prepared statements for SQL
- [ ] CSRF tokens on forms
- [ ] Strong password requirements
- [ ] Secure session configuration
- [ ] Error logging enabled

### Testing
- [ ] Test SQL injection attempts
- [ ] Test XSS payloads
- [ ] Test CSRF attacks
- [ ] Test rate limiting
- [ ] Test password strength validation
- [ ] Test session security
- [ ] Test authentication bypass attempts

### Deployment
- [ ] HTTPS enabled
- [ ] display_errors disabled
- [ ] Secure session cookies
- [ ] File permissions set
- [ ] .env secured
- [ ] Security headers enabled
- [ ] Database secured
- [ ] Regular backups configured

### Monitoring
- [ ] Check error logs daily
- [ ] Monitor failed login attempts
- [ ] Review rate limiting blocks
- [ ] Check for suspicious patterns
- [ ] Keep dependencies updated
- [ ] Regular security audits

## Common Vulnerabilities and Prevention

### SQL Injection

**Vulnerable Code**:
```php
$sql = "SELECT * FROM users WHERE username = '$username'";
$result = $db->query($sql);
```

**Attack**: `admin' OR '1'='1`

**Secure Code**:
```php
$stmt = $db->prepare('SELECT * FROM users WHERE username = :username');
$stmt->execute(['username' => $username]);
```

### XSS (Cross-Site Scripting)

**Vulnerable Code**:
```php
echo "Welcome " . $_GET['name'];
```

**Attack**: `<script>alert('XSS')</script>`

**Secure Code**:
```php
echo "Welcome " . htmlspecialchars($_GET['name'], ENT_QUOTES, 'UTF-8');
```

### CSRF (Cross-Site Request Forgery)

**Vulnerable Code**:
```php
// No CSRF protection
if ($_POST['action'] === 'delete') {
    deleteUser($_POST['id']);
}
```

**Attack**: Malicious site submits form to your site

**Secure Code**:
```php
if (InputValidator::verifyCsrfToken($_POST['csrf_token'])) {
    if ($_POST['action'] === 'delete') {
        deleteUser($_POST['id']);
    }
}
```

## Incident Response

If you suspect a security breach:

1. **Immediate Actions**
   - Disable affected accounts
   - Change all passwords
   - Revoke active sessions
   - Block suspicious IP addresses

2. **Investigation**
   - Review error logs
   - Check login attempt history
   - Analyze database for unauthorized changes
   - Review file modification times

3. **Recovery**
   - Restore from clean backup if needed
   - Patch vulnerabilities
   - Update all dependencies
   - Reset all user passwords

4. **Prevention**
   - Document the incident
   - Update security measures
   - Improve monitoring
   - Train team on lessons learned

## Resources

- [OWASP Top 10](https://owasp.org/www-project-top-ten/)
- [PHP Security Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/PHP_Configuration_Cheat_Sheet.html)
- [Password Storage Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/Password_Storage_Cheat_Sheet.html)
- [Session Management Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/Session_Management_Cheat_Sheet.html)

## Reporting Security Issues

If you discover a security vulnerability:

1. **DO NOT** open a public issue
2. Email security concerns to the maintainer
3. Provide detailed information about the vulnerability
4. Allow time for the issue to be patched before disclosure

---

**Security is a continuous process, not a one-time implementation. Stay vigilant!**
