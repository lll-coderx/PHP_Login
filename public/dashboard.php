<?php
declare(strict_types=1);

// Load dependencies
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/Auth/SessionManager.php';
require_once __DIR__ . '/../src/Security/InputValidator.php';

use App\Auth\SessionManager;
use App\Security\InputValidator;

// Start session
SessionManager::start();

// Redirect if not logged in
if (!SessionManager::isLoggedIn()) {
    header('Location: /public/login.php');
    exit;
}

$pageTitle = 'Dashboard';
require_once __DIR__ . '/../templates/header.php';
?>

<div class="dashboard">
    <h2>Welcome to Your Dashboard</h2>
    
    <div class="alert alert-success">
        You have successfully logged in!
    </div>
    
    <div style="margin-top: 2rem;">
        <h3>Account Information</h3>
        <table style="width: 100%; margin-top: 1rem; border-collapse: collapse;">
            <tr style="border-bottom: 1px solid #ddd;">
                <td style="padding: 0.75rem; font-weight: bold;">User ID:</td>
                <td style="padding: 0.75rem;"><?php echo InputValidator::escapeHtml((string)SessionManager::getUserId()); ?></td>
            </tr>
            <tr style="border-bottom: 1px solid #ddd;">
                <td style="padding: 0.75rem; font-weight: bold;">Username:</td>
                <td style="padding: 0.75rem;"><?php echo InputValidator::escapeHtml(SessionManager::get('user_username')); ?></td>
            </tr>
            <tr>
                <td style="padding: 0.75rem; font-weight: bold;">Email:</td>
                <td style="padding: 0.75rem;"><?php echo InputValidator::escapeHtml(SessionManager::getUserEmail()); ?></td>
            </tr>
        </table>
    </div>
    
    <div style="margin-top: 2rem;">
        <h3>Security Features</h3>
        <ul style="margin-left: 1.5rem; color: #555; line-height: 2;">
            <li>✓ SQL Injection Prevention (PDO Prepared Statements)</li>
            <li>✓ Password Security (Argon2ID Hashing)</li>
            <li>✓ Session Security (Secure, HttpOnly, SameSite flags)</li>
            <li>✓ XSS Prevention (Output Escaping)</li>
            <li>✓ CSRF Protection (Token Validation)</li>
            <li>✓ Rate Limiting (Brute Force Protection)</li>
            <li>✓ Secure Password Reset (Token-based)</li>
        </ul>
    </div>
    
    <div style="margin-top: 2rem;">
        <a href="/public/logout.php" class="btn" style="display: inline-block; width: auto; padding: 0.75rem 2rem; text-decoration: none;">Logout</a>
    </div>
</div>

<?php require_once __DIR__ . '/../templates/footer.php'; ?>
