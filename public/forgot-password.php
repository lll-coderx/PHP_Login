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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || !InputValidator::verifyCsrfToken($_POST['csrf_token'])) {
        $error = 'Invalid request. Please try again.';
    } else {
        $email = $_POST['email'] ?? '';
        
        if (empty($email)) {
            $error = 'Please enter your email address.';
        } else {
            try {
                // Check if vendor autoload exists
                $autoloadPath = __DIR__ . '/../vendor/autoload.php';
                if (file_exists($autoloadPath)) {
                    require_once $autoloadPath;
                }
                
                $db = Database::getInstance();
                $mailService = new MailService();
                $resetService = new PasswordResetService($db, $mailService);
                
                $result = $resetService->requestReset($email);
                
                if ($result['success']) {
                    $success = $result['message'];
                } else {
                    $error = $result['message'];
                }
            } catch (Exception $e) {
                error_log('Password reset request error: ' . $e->getMessage());
                $error = 'An error occurred. Please try again later.';
            }
        }
    }
}

$pageTitle = 'Forgot Password';
require_once __DIR__ . '/../templates/header.php';
?>

<div class="form-container">
    <h2>Forgot Your Password?</h2>
    
    <p style="text-align: center; margin-bottom: 1.5rem; color: #666;">
        Enter your email address and we'll send you a link to reset your password.
    </p>
    
    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo InputValidator::escapeHtml($error); ?></div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo InputValidator::escapeHtml($success); ?></div>
    <?php else: ?>
        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?php echo InputValidator::generateCsrfToken(); ?>">
            
            <div class="form-group">
                <label for="email">Email Address</label>
                <input 
                    type="email" 
                    id="email" 
                    name="email" 
                    required 
                    autocomplete="email"
                    value="<?php echo InputValidator::getPostValue('email'); ?>"
                >
            </div>
            
            <button type="submit" class="btn">Send Reset Link</button>
        </form>
    <?php endif; ?>
    
    <div class="form-links">
        <p>Remember your password? <a href="/public/login.php">Login here</a></p>
    </div>
</div>

<?php require_once __DIR__ . '/../templates/footer.php'; ?>
