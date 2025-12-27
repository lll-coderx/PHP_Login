# PHP Login & Password Reset System

A secure, production-ready PHP login and password reset system implementing OWASP Top 10 security best practices.

![PHP Version](https://img.shields.io/badge/PHP-8.4%2B-blue)
![License](https://img.shields.io/badge/license-MIT-green)
![Security](https://img.shields.io/badge/security-OWASP%20Top%2010-red)

## 🔒 Security Features

This system implements comprehensive security measures to protect against common vulnerabilities:

### 1. SQL Injection Prevention
- ✅ PDO with prepared statements for all database queries
- ✅ Parameterized queries with type binding
- ✅ No string concatenation in SQL queries

### 2. Password Security
- ✅ Argon2ID hashing algorithm (strongest available in PHP)
- ✅ Automatic password rehashing on algorithm upgrades
- ✅ Password strength validation (min 8 chars, uppercase, lowercase, numbers)

### 3. Session Security
- ✅ Secure session cookie configuration
- ✅ HttpOnly flag (prevents JavaScript access)
- ✅ SameSite=Strict (CSRF protection)
- ✅ Session ID regeneration after login
- ✅ Periodic session regeneration (every 30 minutes)

### 4. XSS Prevention
- ✅ All output escaped with `htmlspecialchars()`
- ✅ ENT_QUOTES and UTF-8 encoding
- ✅ Consistent sanitization across all templates

### 5. CSRF Protection
- ✅ Cryptographically secure tokens
- ✅ Token validation on all form submissions
- ✅ Token regeneration per session

### 6. Rate Limiting
- ✅ Login attempts: 5 per 15 minutes per IP
- ✅ Password reset requests: 3 per hour per email
- ✅ Automatic cleanup of old attempt records

### 7. Secure Password Reset
- ✅ Cryptographically secure tokens (64 hex characters)
- ✅ Tokens hashed (SHA-256) before storage
- ✅ 30-minute expiry on reset links
- ✅ Single-use tokens (deleted after use)

### 8. Information Leakage Prevention
- ✅ Generic error messages
- ✅ No disclosure of email existence
- ✅ Consistent response times

## 📋 Requirements

- **PHP**: 8.4 or higher
- **MySQL**: 8.0+ or MariaDB 10.5+
- **Apache**: 2.4+ (with mod_rewrite)
- **PHP Extensions**: PDO, pdo_mysql, mbstring, openssl

## 🚀 Installation

### Step 1: Clone the Repository

```bash
git clone https://github.com/lll-coderx/PHP_Login.git
cd PHP_Login
```

### Step 2: Install Dependencies

```bash
composer install
```

### Step 3: Configure Environment

```bash
cp .env.example .env
```

Edit `.env` with your database and SMTP credentials:

```env
# Database Configuration
DB_HOST=localhost
DB_NAME=php_login_db
DB_USER=root
DB_PASS=your_password

# SMTP Configuration (for Password Reset Emails)
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USERNAME=your-email@gmail.com
SMTP_PASSWORD=your-app-password
SMTP_FROM_EMAIL=noreply@yourdomain.com
SMTP_FROM_NAME=PHP Login System

# Application Configuration
APP_URL=http://localhost
APP_NAME=PHP Login System
```

### Step 4: Create Database

```bash
mysql -u root -p
```

```sql
CREATE DATABASE php_login_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE php_login_db;
SOURCE database/schema.sql;
```

### Step 5: Configure Web Server

**Apache Configuration:**

Add to your virtual host configuration:

```apache
<VirtualHost *:80>
    DocumentRoot "/path/to/PHP_Login/public"
    ServerName localhost
    
    <Directory "/path/to/PHP_Login/public">
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

Restart Apache:

```bash
sudo systemctl restart apache2
```

### Step 6: Set File Permissions

```bash
chmod 644 .env
chmod 755 public
find . -type f -exec chmod 644 {} \;
find . -type d -exec chmod 755 {} \;
```

## 📁 Project Structure

```
PHP_Login/
├── config/
│   ├── database.php          # PDO Database Connection (Singleton)
│   └── config.php            # Application Configuration
├── src/
│   ├── Auth/
│   │   ├── AuthService.php   # Authentication Logic
│   │   └── SessionManager.php # Session Management
│   ├── Recovery/
│   │   ├── PasswordResetService.php # Password Reset
│   │   └── TokenGenerator.php       # Token Generation
│   ├── Mail/
│   │   └── MailService.php   # Email Sending
│   └── Security/
│       ├── RateLimiter.php   # Brute Force Protection
│       └── InputValidator.php # Input Validation
├── public/
│   ├── index.php             # Entry Point
│   ├── login.php             # Login Page
│   ├── register.php          # Registration Page
│   ├── logout.php            # Logout Handler
│   ├── dashboard.php         # Protected Dashboard
│   ├── forgot-password.php   # Forgot Password Page
│   ├── reset-password.php    # Reset Password Page
│   └── assets/
│       └── css/
│           └── style.css     # Styling
├── templates/
│   ├── header.php            # Common Header
│   ├── footer.php            # Common Footer
│   └── emails/
│       └── reset-password.html # Email Template
├── database/
│   └── schema.sql            # Database Schema
├── composer.json             # Dependencies
├── .htaccess                 # Apache Security
├── .env.example              # Environment Template
└── README.md                 # Documentation
```

## 🎯 Usage

### User Registration

1. Navigate to `/public/register.php`
2. Fill in username, email, and password
3. Password must meet strength requirements:
   - At least 8 characters
   - At least one uppercase letter
   - At least one lowercase letter
   - At least one number

### User Login

1. Navigate to `/public/login.php`
2. Enter email and password
3. System will verify credentials and create secure session

### Password Reset

1. Navigate to `/public/forgot-password.php`
2. Enter your email address
3. Check your email for reset link
4. Click the link and enter new password
5. Link expires in 30 minutes

## 🔐 Security Best Practices

### For Developers

1. **Never log sensitive data**: Passwords, tokens, or session IDs
2. **Always use prepared statements**: Never concatenate user input into SQL
3. **Escape all output**: Use `InputValidator::escapeHtml()` for all user data
4. **Validate input**: Server-side validation is mandatory
5. **Use CSRF tokens**: On all state-changing operations

### For Deployment

1. **Use HTTPS**: Enable SSL/TLS in production
2. **Enable secure cookies**: Set `session.cookie_secure = 1`
3. **Hide PHP version**: Set `expose_php = Off`
4. **Restrict file access**: Use proper file permissions
5. **Enable error logging**: Set `display_errors = Off` and `log_errors = On`
6. **Keep dependencies updated**: Run `composer update` regularly

### Production Checklist

- [ ] Set `APP_URL` to your production domain
- [ ] Configure valid SMTP credentials
- [ ] Enable HTTPS and update `.htaccess` redirect
- [ ] Set proper file permissions (644 for files, 755 for directories)
- [ ] Configure proper error logging
- [ ] Set `display_errors = Off` in php.ini
- [ ] Enable all security headers in `.htaccess`
- [ ] Use strong database passwords
- [ ] Regularly backup database
- [ ] Monitor rate limiting logs

## 🧪 Testing

### Manual Testing

1. **Test Registration**:
   ```bash
   curl -X POST http://localhost/public/register.php \
     -d "username=testuser&email=test@example.com&password=TestPass123!&confirm_password=TestPass123!"
   ```

2. **Test Login**:
   ```bash
   curl -X POST http://localhost/public/login.php \
     -d "email=test@example.com&password=TestPass123!"
   ```

3. **Test Rate Limiting**: Try 6 failed login attempts rapidly

4. **Test Password Reset**: Request reset and verify email

### Creating Test User

```sql
INSERT INTO users (username, email, password_hash) 
VALUES (
    'testuser', 
    'test@example.com', 
    '$argon2id$v=19$m=65536,t=4,p=1$YourHashHere'
);
```

## 📊 Database Schema

### Users Table
- `id`: User ID (Primary Key)
- `username`: Unique username
- `email`: Unique email address
- `password_hash`: Argon2ID hashed password
- `created_at`: Account creation timestamp
- `updated_at`: Last update timestamp

### Password Resets Table
- `id`: Reset request ID (Primary Key)
- `email`: User email (Foreign Key)
- `token_hash`: SHA-256 hashed token
- `expiry`: Token expiration datetime
- `created_at`: Request timestamp

### Login Attempts Table
- `id`: Attempt ID (Primary Key)
- `ip_address`: Client IP address
- `email`: Attempted email (optional)
- `attempt_time`: Attempt timestamp

## 🛠️ Troubleshooting

### Database Connection Error
- Verify database credentials in `.env`
- Ensure MySQL service is running
- Check database exists and schema is imported

### Email Not Sending
- Verify SMTP credentials in `.env`
- Check firewall allows outbound connections on port 587
- For Gmail, use App Password (not regular password)
- Check error logs for PHPMailer errors

### Session Issues
- Ensure PHP session directory is writable
- Check `session.save_path` in php.ini
- Verify cookies are enabled in browser

### Rate Limiting Too Strict
- Adjust limits in `.env`:
  ```env
  RATE_LIMIT_LOGIN=10
  RATE_LIMIT_LOGIN_WINDOW=1800
  ```

## 📝 License

MIT License - See LICENSE file for details

## 👥 Contributing

1. Fork the repository
2. Create a feature branch
3. Commit your changes
4. Push to the branch
5. Create a Pull Request

## 🔗 References

- [OWASP Top 10](https://owasp.org/www-project-top-ten/)
- [OWASP PHP Security Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/PHP_Configuration_Cheat_Sheet.html)
- [PHP Password Hashing](https://www.php.net/manual/en/function.password-hash.php)
- [PDO Prepared Statements](https://www.php.net/manual/en/pdo.prepared-statements.php)

## 📧 Support

For issues and questions, please open an issue on GitHub.

---

**Built with security in mind. Stay safe! 🔒**
