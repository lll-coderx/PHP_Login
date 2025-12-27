<?php
declare(strict_types=1);

// Load dependencies
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/Auth/SessionManager.php';
require_once __DIR__ . '/../src/Auth/AuthService.php';

use App\Auth\SessionManager;
use App\Auth\AuthService;

// Start session
SessionManager::start();

// Logout user
try {
    $db = Database::getInstance();
    $authService = new AuthService($db);
    $authService->logout();
} catch (Exception $e) {
    error_log('Logout error: ' . $e->getMessage());
}

// Redirect to login page
header('Location: /public/login.php');
exit;
