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
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        
        if (empty($email) || empty($password)) {
            $error = 'Please fill in all fields.';
        } else {
            try {
                $db = Database::getInstance();
                $authService = new AuthService($db);
                $result = $authService->login($email, $password);
                
                if ($result['success']) {
                    header('Location: /public/dashboard.php');
                    exit;
                } else {
                    $error = $result['message'];
                }
            } catch (Exception $e) {
                error_log('Login error: ' . $e->getMessage());
                $error = 'An error occurred. Please try again later.';
            }
        }
    }
}

$pageTitle = 'Login';
require_once __DIR__ . '/../templates/header.php';
?>

<div class="form-container">
    <h2>Login to Your Account</h2>
    
    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo InputValidator::escapeHtml($error); ?></div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo InputValidator::escapeHtml($success); ?></div>
    <?php endif; ?>
    
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
        
        <div class="form-group">
            <label for="password">Password</label>
            <input 
                type="password" 
                id="password" 
                name="password" 
                required 
                autocomplete="current-password"
            >
        </div>
        
        <button type="submit" class="btn">Login</button>
    </form>
    
    <div class="form-links">
        <p><a href="/public/forgot-password.php">Forgot your password?</a></p>
        <p>Don't have an account? <a href="/public/register.php">Register here</a></p>
    </div>
</div>

<?php require_once __DIR__ . '/../templates/footer.php'; ?>
