<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../src/controllers/AuthController.php';

$authController = new AuthController();

// Récupérer l'URL demandée
$request = strtok($_SERVER["REQUEST_URI"], '?');

switch ($request) {
    case '/' :
        require __DIR__ . '/../src/views/home.php';
        break;
    case '/register' :
        require __DIR__ . '/../src/views/register.php';
        break;
    case '/login' :
        require __DIR__ . '/../src/views/login.php';
        break;
    case '/forgot_password' :
        require __DIR__ . '/../src/views/forgot_password.php';
        break;
    case '/logout' :
        $authController->logout();
        break;
    case '/register_action' :
        $authController->register();
        break;
    case '/login_action' :
        $authController->login();
        break;
    default:
        http_response_code(404);
        echo "Page non trouvée";
        break;
}
