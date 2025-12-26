<?php
declare(strict_types=1);

// Load dependencies
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/Auth/SessionManager.php';
require_once __DIR__ . '/../src/Auth/AuthService.php';
require_once __DIR__ . '/../src/Security/InputValidator.php';

use App\Auth\SessionManager;
use App\Auth\AuthService;
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
        $username = $_POST['username'] ?? '';
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        if (empty($username) || empty($email) || empty($password) || empty($confirmPassword)) {
            $error = 'Please fill in all fields.';
        } elseif ($password !== $confirmPassword) {
            $error = 'Passwords do not match.';
        } else {
            try {
                $db = Database::getInstance();
                $authService = new AuthService($db);
                $result = $authService->register($username, $email, $password);
                
                if ($result['success']) {
                    $success = $result['message'];
                } else {
                    $error = $result['message'];
                }
            } catch (Exception $e) {
                error_log('Registration error: ' . $e->getMessage());
                $error = 'An error occurred. Please try again later.';
            }
        }
    }
}

$pageTitle = 'Register';
require_once __DIR__ . '/../templates/header.php';
?>

<div class="form-container">
    <h2>Create Your Account</h2>
    
    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo InputValidator::escapeHtml($error); ?></div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="alert alert-success">
            <?php echo InputValidator::escapeHtml($success); ?>
            <p style="margin-top: 1rem;">
                <a href="/public/login.php">Click here to login</a>
            </p>
        </div>
    <?php else: ?>
        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?php echo InputValidator::generateCsrfToken(); ?>">
            
            <div class="form-group">
                <label for="username">Username</label>
                <input 
                    type="text" 
                    id="username" 
                    name="username" 
                    required 
                    autocomplete="username"
                    pattern="[a-zA-Z0-9_]{3,50}"
                    title="3-50 characters, alphanumeric and underscores only"
                    value="<?php echo InputValidator::getPostValue('username'); ?>"
                >
            </div>
            
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
            
            <div class="form-group">
                <label for="password">Password</label>
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
                <label for="confirm_password">Confirm Password</label>
                <input 
                    type="password" 
                    id="confirm_password" 
                    name="confirm_password" 
                    required 
                    autocomplete="new-password"
                    minlength="8"
                >
            </div>
            
            <button type="submit" class="btn">Register</button>
        </form>
    <?php endif; ?>
    
    <div class="form-links">
        <p>Already have an account? <a href="/public/login.php">Login here</a></p>
    </div>
</div>

<?php require_once __DIR__ . '/../templates/footer.php'; ?>
