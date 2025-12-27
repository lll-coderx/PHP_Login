<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
Config::load();

$pageTitle = $pageTitle ?? 'PHP Login System';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title><?php echo htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8'); ?></title>
    <link rel="stylesheet" href="<?php echo htmlspecialchars(Config::get('app.url', ''), ENT_QUOTES, 'UTF-8'); ?>/public/assets/css/style.css">
</head>
<body>
    <header>
        <div class="container">
            <h1><?php echo htmlspecialchars(Config::get('app.name', 'PHP Login System'), ENT_QUOTES, 'UTF-8'); ?></h1>
            <nav>
                <?php
                require_once __DIR__ . '/../src/Auth/SessionManager.php';
                use App\Auth\SessionManager;
                SessionManager::start();
                
                if (SessionManager::isLoggedIn()): ?>
                    <span>Welcome, <?php echo htmlspecialchars(SessionManager::get('user_username'), ENT_QUOTES, 'UTF-8'); ?></span>
                    <a href="<?php echo htmlspecialchars(Config::get('app.url', ''), ENT_QUOTES, 'UTF-8'); ?>/public/logout.php">Logout</a>
                <?php else: ?>
                    <a href="<?php echo htmlspecialchars(Config::get('app.url', ''), ENT_QUOTES, 'UTF-8'); ?>/public/login.php">Login</a>
                <?php endif; ?>
            </nav>
        </div>
    </header>
    <main class="container">
