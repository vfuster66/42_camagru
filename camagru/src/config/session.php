<?php
class SessionManager {
    public static function init() {
        if (session_status() === PHP_SESSION_NONE) {
            ini_set('session.cookie_httponly', 1);
            ini_set('session.use_strict_mode', 1);
            ini_set('session.cookie_samesite', 'Lax');
            if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
                ini_set('session.cookie_secure', 1);
            }
            
            session_start();
            
            if (!isset($_SESSION['last_regeneration'])) {
                self::regenerateSession();
            } else {
                $interval = 60 * 30;
                if (time() - $_SESSION['last_regeneration'] >= $interval) {
                    self::regenerateSession();
                }
            }
        }
    }

    private static function regenerateSession() {
        session_regenerate_id(true);
        $_SESSION['last_regeneration'] = time();
    }

    public static function destroy() {
        $_SESSION = array();
        session_destroy();
        setcookie(session_name(), '', time() - 3600, '/');
    }

    public static function isAuthenticated() {
        return isset($_SESSION['user']) && $_SESSION['user']['is_verified'];
    }
}