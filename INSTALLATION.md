# Installation Guide

## Quick Start Guide for PHP Login System

This guide will walk you through setting up the secure PHP login system from scratch.

## Prerequisites

Before you begin, ensure you have:

- **PHP 8.4 or higher** installed
- **MySQL 8.0+** or **MariaDB 10.5+**
- **Apache** with mod_rewrite enabled
- **Composer** for dependency management
- **Git** for version control

### Check Your PHP Version

```bash
php --version
```

You should see PHP 8.4.0 or higher.

### Check Required PHP Extensions

```bash
php -m | grep -E 'PDO|pdo_mysql|mbstring|openssl'
```

All four extensions should be listed.

## Step-by-Step Installation

### 1. Clone the Repository

```bash
git clone https://github.com/lll-coderx/PHP_Login.git
cd PHP_Login
```

### 2. Install PHP Dependencies

```bash
composer install
```

This will install PHPMailer and other required dependencies.

### 3. Configure Environment Variables

```bash
cp .env.example .env
```

Edit the `.env` file with your configuration:

```env
# Database Configuration
DB_HOST=localhost
DB_NAME=php_login_db
DB_USER=root
DB_PASS=your_database_password

# SMTP Configuration (Gmail example)
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USERNAME=your-email@gmail.com
SMTP_PASSWORD=your-app-password
SMTP_FROM_EMAIL=noreply@yourdomain.com
SMTP_FROM_NAME=PHP Login System

# Application Configuration
APP_URL=http://localhost
APP_NAME=My Secure Login
```

#### Setting Up Gmail SMTP (Optional)

If using Gmail for password reset emails:

1. Go to https://myaccount.google.com/security
2. Enable 2-Step Verification
3. Generate an App Password
4. Use the App Password in `SMTP_PASSWORD`

### 4. Create Database

Log into MySQL:

```bash
mysql -u root -p
```

Create the database:

```sql
CREATE DATABASE php_login_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Grant privileges:

```sql
GRANT ALL PRIVILEGES ON php_login_db.* TO 'your_user'@'localhost';
FLUSH PRIVILEGES;
```

Exit MySQL:

```sql
EXIT;
```

### 5. Import Database Schema

```bash
mysql -u root -p php_login_db < database/schema.sql
```

Verify tables were created:

```bash
mysql -u root -p php_login_db -e "SHOW TABLES;"
```

You should see:
- `login_attempts`
- `password_resets`
- `users`

### 6. Configure Apache

#### Option A: Using Virtual Host (Recommended)

Edit Apache configuration (e.g., `/etc/apache2/sites-available/000-default.conf`):

```apache
<VirtualHost *:80>
    ServerName localhost
    DocumentRoot /path/to/PHP_Login/public
    
    <Directory /path/to/PHP_Login/public>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/php-login-error.log
    CustomLog ${APACHE_LOG_DIR}/php-login-access.log combined
</VirtualHost>
```

Enable mod_rewrite if not already enabled:

```bash
sudo a2enmod rewrite
```

Restart Apache:

```bash
sudo systemctl restart apache2
```

#### Option B: Using .htaccess (Already configured)

The `.htaccess` file in the root directory is already configured with security headers.

### 7. Set File Permissions

```bash
# Set directory permissions
find . -type d -exec chmod 755 {} \;

# Set file permissions
find . -type f -exec chmod 644 {} \;

# Secure the .env file
chmod 600 .env

# Make public directory accessible
chmod 755 public
```

### 8. Test the Installation

Run the test suite:

```bash
php tests/test.php
```

You should see all tests passing.

### 9. Access the Application

Open your browser and navigate to:

```
http://localhost/public/login.php
```

or if configured with virtual host:

```
http://localhost/login.php
```

## Creating Your First User

### Method 1: Using the Registration Page

1. Navigate to `/public/register.php`
2. Fill in the form with:
   - Username (3-50 characters, alphanumeric and underscores)
   - Email
   - Password (min 8 chars, uppercase, lowercase, number)
3. Click Register
4. Login with your credentials

### Method 2: Using SQL Insert

```sql
INSERT INTO users (username, email, password_hash)
VALUES (
    'admin',
    'admin@example.com',
    '$argon2id$v=19$m=65536,t=4,p=1$...'  -- Use password_hash() function
);
```

To generate a password hash:

```bash
php -r "echo password_hash('YourPassword123', PASSWORD_ARGON2ID);"
```

## Testing Password Reset

1. Navigate to `/public/forgot-password.php`
2. Enter your email address
3. Check your email for the reset link
4. Click the link and enter a new password
5. The link expires in 30 minutes

## Troubleshooting

### Database Connection Error

**Error**: `Database connection failed`

**Solutions**:
- Verify database credentials in `.env`
- Check MySQL service is running: `sudo systemctl status mysql`
- Test connection: `mysql -u your_user -p -h localhost`

### Email Not Sending

**Error**: `Email error: SMTP connect() failed`

**Solutions**:
- Verify SMTP credentials in `.env`
- For Gmail, use App Password not regular password
- Check firewall allows port 587: `telnet smtp.gmail.com 587`
- Check PHP mail function: `php -r "print_r(mail('test@test.com', 'Test', 'Body'));"`

### Permission Denied

**Error**: `Permission denied` when accessing files

**Solutions**:
```bash
sudo chown -R www-data:www-data /path/to/PHP_Login
sudo chmod -R 755 /path/to/PHP_Login
sudo chmod 644 /path/to/PHP_Login/.env
```

### Apache 404 Error

**Error**: `404 Not Found` when accessing pages

**Solutions**:
- Check DocumentRoot points to `/public` directory
- Verify mod_rewrite is enabled: `apache2ctl -M | grep rewrite`
- Check `.htaccess` file exists in public directory
- Review Apache error logs: `tail -f /var/log/apache2/error.log`

### Session Issues

**Error**: Session not persisting

**Solutions**:
```bash
# Check session directory permissions
ls -ld /var/lib/php/sessions

# If needed, create and set permissions
sudo mkdir -p /var/lib/php/sessions
sudo chown www-data:www-data /var/lib/php/sessions
sudo chmod 700 /var/lib/php/sessions
```

## Production Deployment Checklist

Before deploying to production:

- [ ] Set `APP_URL` to production domain
- [ ] Enable HTTPS (SSL/TLS certificate)
- [ ] Update `.htaccess` to force HTTPS redirect
- [ ] Set `session.cookie_secure = 1` in php.ini
- [ ] Set `display_errors = Off` in php.ini
- [ ] Set `log_errors = On` in php.ini
- [ ] Configure proper SMTP credentials
- [ ] Use strong database password
- [ ] Set proper file permissions (644/755)
- [ ] Secure `.env` file (chmod 600)
- [ ] Enable all security headers
- [ ] Set up regular database backups
- [ ] Configure fail2ban for additional protection
- [ ] Test rate limiting
- [ ] Review error logs regularly

## Security Headers for Production

Add to your Apache configuration:

```apache
<IfModule mod_headers.c>
    Header always set X-Content-Type-Options "nosniff"
    Header always set X-Frame-Options "SAMEORIGIN"
    Header always set X-XSS-Protection "1; mode=block"
    Header always set Referrer-Policy "strict-origin-when-cross-origin"
    Header always set Content-Security-Policy "default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline';"
    Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains"
</IfModule>
```

## Monitoring and Maintenance

### Check Login Attempts

```sql
SELECT ip_address, COUNT(*) as attempts, MAX(attempt_time) as last_attempt
FROM login_attempts
WHERE attempt_time > DATE_SUB(NOW(), INTERVAL 1 HOUR)
GROUP BY ip_address
ORDER BY attempts DESC;
```

### Clean Old Data

```sql
-- Remove expired password resets
DELETE FROM password_resets WHERE expiry < NOW();

-- Remove old login attempts (older than 24 hours)
DELETE FROM login_attempts WHERE attempt_time < DATE_SUB(NOW(), INTERVAL 24 HOUR);
```

### Monitor Error Logs

```bash
tail -f /var/log/apache2/error.log
```

## Getting Help

- Review the README.md for detailed documentation
- Check the test suite: `php tests/test.php`
- Open an issue on GitHub
- Review OWASP security guidelines

## Next Steps

After successful installation:

1. Test all functionality (login, register, password reset)
2. Review security settings
3. Customize templates and styling
4. Add additional features as needed
5. Set up monitoring and backups
6. Deploy to production with proper security

---

**Remember**: Security is an ongoing process. Keep dependencies updated and review security logs regularly.
