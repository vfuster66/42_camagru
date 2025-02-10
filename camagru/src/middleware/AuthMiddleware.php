<?php
class AuthMiddleware {
    public static function requireAuth() {
        if (!SessionManager::isAuthenticated()) {
            $_SESSION['error'] = "Vous devez être connecté pour accéder à cette page.";
            header('Location: /login');
            exit;
        }
    }

    public static function requireVerified() {
        if (!isset($_SESSION['user']) || !$_SESSION['user']['is_verified']) {
            $_SESSION['error'] = "Vous devez vérifier votre compte pour accéder à cette page.";
            header('Location: /login');
            exit;
        }
    }

    public static function requireAdmin() {
        if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
            $_SESSION['error'] = "Accès non autorisé.";
            header('Location: /');
            exit;
        }
    }
}