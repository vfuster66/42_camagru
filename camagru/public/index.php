<?php

/**
 * Point d'entrée de l'application
 * Gère le routage et l'initialisation
 */

// Démarrage de la session sécurisée
require_once __DIR__ . '/../src/config/session.php';
SessionManager::init();

// Gestion des erreurs
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/error.log');

// Chargement automatique des classes
spl_autoload_register(function ($class) {
    $paths = [
        __DIR__ . '/../src/controllers/',
        __DIR__ . '/../src/models/',
        __DIR__ . '/../src/middleware/',
        __DIR__ . '/../src/utils/',
        __DIR__ . '/../src/config/'
    ];

    foreach ($paths as $path) {
        $file = $path . $class . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
    error_log("Classe non trouvée: $class");
});

// Initialisation des contrôleurs
try {
    $authController = new AuthController();
    $imageController = new ImageController();
} catch (Exception $e) {
    error_log("Erreur d'initialisation des contrôleurs: " . $e->getMessage());
    die("Une erreur est survenue");
}

// Configuration des routes protégées
$protectedRoutes = [
    '/editor' => ['auth' => true, 'verified' => true],
    '/profile' => ['auth' => true, 'verified' => true],
    '/upload-image' => ['auth' => true, 'verified' => true],
    '/capture-image' => ['auth' => true, 'verified' => true],
    '/delete-image' => ['auth' => true, 'verified' => true],
    '/api/user-images' => ['auth' => true, 'verified' => true]
];

// Récupération de l'URL demandée
$request = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Vérification des permissions pour les routes protégées
if (isset($protectedRoutes[$request])) {
    if ($protectedRoutes[$request]['auth']) {
        AuthMiddleware::requireAuth();
    }
    if ($protectedRoutes[$request]['verified']) {
        AuthMiddleware::requireVerified();
    }
}

// Gestion des routes
try {
    switch ($request) {
            // Routes publiques
        case '/':
            require __DIR__ . '/../src/views/home.php';
            break;

        case '/register':
            require __DIR__ . '/../src/views/register.php';
            break;

        case '/login':
            require __DIR__ . '/../src/views/login.php';
            break;

        case '/forgot_password':
            require __DIR__ . '/../src/views/forgot_password.php';
            break;

        case '/reset_password':
            require __DIR__ . '/../src/views/reset_password.php';
            break;

        case '/verify_email':
            require __DIR__ . '/../src/views/verify_email.php';
            break;

        case '/verify_email_action':
            $authController->verifyEmail();
            break;

            // Routes d'authentification
        case '/register_action':
            $authController->register();
            break;

        case '/login_action':
            $authController->login();
            break;

        case '/logout':
            $authController->logout();
            break;

        case '/forgot_password_action':
            $authController->forgotPassword();
            break;

        case '/reset_password_action':
            $authController->resetPassword();
            break;

        case '/profile':
            require __DIR__ . '/../src/views/profile.php';
            break;

        case '/editor':
            $imageController->showEditor();
            break;

        case '/api/filters':
            header('Content-Type: application/json');
            $imageController->getFilters();
            break;

        case '/api/user-images':
            header('Content-Type: application/json');
            $imageController->getUserImages();
            break;

        case '/capture-image':
            header('Content-Type: application/json');
            $imageController->captureImage();
            break;

        case '/upload-image':
            header('Content-Type: application/json');
            $imageController->uploadImage();
            break;

        case '/delete-image':
            header('Content-Type: application/json');
            $imageController->deleteImage();
            break;

            // Pages d'erreur
        default:
            header("HTTP/1.0 404 Not Found");
            require __DIR__ . '/../src/views/404.php';
            break;
    }
} catch (Exception $e) {
    error_log("Erreur lors du traitement de la route $request : " . $e->getMessage());
    header("HTTP/1.0 500 Internal Server Error");
    require __DIR__ . '/../src/views/500.php';
}
