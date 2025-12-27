<?php
declare(strict_types=1);

// Load dependencies
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/Auth/SessionManager.php';
require_once __DIR__ . '/../src/Recovery/PasswordResetService.php';
require_once __DIR__ . '/../src/Recovery/TokenGenerator.php';
require_once __DIR__ . '/../src/Mail/MailService.php';
require_once __DIR__ . '/../src/Security/InputValidator.php';

use App\Auth\SessionManager;
use App\Recovery\PasswordResetService;
use App\Mail\MailService;
use App\Security\InputValidator;

// Start session
SessionManager::start();

// Redirect if already logged in
if (SessionManager::isLoggedIn()) {
    header('Location: /public/dashboard.php');
    exit;
}

$error = '';
$success = '';
$token = $_GET['token'] ?? '';

if (empty($token)) {
    header('Location: /public/forgot-password.php');
    exit;
}

// Verify token first
try {
    // Check if vendor autoload exists
    $autoloadPath = __DIR__ . '/../vendor/autoload.php';
    if (file_exists($autoloadPath)) {
        require_once $autoloadPath;
    }
    
    $db = Database::getInstance();
    $mailService = new MailService();
    $resetService = new PasswordResetService($db, $mailService);
    
    $verification = $resetService->verifyToken($token);
    
    if (!$verification['valid']) {
        $error = 'Invalid or expired reset token. Please request a new password reset.';
    }
} catch (Exception $e) {
    error_log('Token verification error: ' . $e->getMessage());
    $error = 'An error occurred. Please try again later.';
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($error)) {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || !InputValidator::verifyCsrfToken($_POST['csrf_token'])) {
        $error = 'Invalid request. Please try again.';
    } else {
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        if (empty($password) || empty($confirmPassword)) {
            $error = 'Please fill in all fields.';
        } elseif ($password !== $confirmPassword) {
            $error = 'Passwords do not match.';
        } else {
            try {
                $result = $resetService->resetPassword($token, $password);
                
                if ($result['success']) {
                    $success = $result['message'];
                    // Clear token from URL
                    $token = '';
                } else {
                    $error = $result['message'];
                }
            } catch (Exception $e) {
                error_log('Password reset error: ' . $e->getMessage());
                $error = 'An error occurred. Please try again later.';
            }
        }
    }
}

$pageTitle = 'Reset Password';
require_once __DIR__ . '/../templates/header.php';
?>

<div class="form-container">
    <h2>Reset Your Password</h2>
    
    <?php if ($error): ?>
        <div class="alert alert-error">
            <?php echo InputValidator::escapeHtml($error); ?>
            <?php if (str_contains($error, 'Invalid or expired')): ?>
                <p style="margin-top: 1rem;">
                    <a href="/public/forgot-password.php">Request a new reset link</a>
                </p>
            <?php endif; ?>
        </div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="alert alert-success">
            <?php echo InputValidator::escapeHtml($success); ?>
            <p style="margin-top: 1rem;">
                <a href="/public/login.php">Click here to login</a>
            </p>
        </div>
    <?php elseif (!$error || !str_contains($error, 'Invalid or expired')): ?>
        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?php echo InputValidator::generateCsrfToken(); ?>">
            
            <div class="form-group">
                <label for="password">New Password</label>
                <input 
                    type="password" 
                    id="password" 
                    name="password" 
                    required 
                    autocomplete="new-password"
                    minlength="8"
                >
                <div class="password-requirements">
                    <ul>
                        <li>At least 8 characters</li>
                        <li>At least one uppercase letter</li>
                        <li>At least one lowercase letter</li>
                        <li>At least one number</li>
                    </ul>
                </div>
            </div>
            
            <div class="form-group">
                <label for="confirm_password">Confirm New Password</label>
                <input 
                    type="password" 
                    id="confirm_password" 
                    name="confirm_password" 
                    required 
                    autocomplete="new-password"
                    minlength="8"
                >
            </div>
            
            <button type="submit" class="btn">Reset Password</button>
        </form>
    <?php endif; ?>
    
    <div class="form-links">
        <p><a href="/public/login.php">Back to Login</a></p>
    </div>
</div>

<?php require_once __DIR__ . '/../templates/footer.php'; ?>
