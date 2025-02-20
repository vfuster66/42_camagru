<?php

require_once __DIR__ . '/../models/User.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class UserController {
    private $userModel;

    public function __construct() {
        $this->userModel = new User();
    }

    public function showProfile() {
        if (!isset($_SESSION['user'])) {
            $_SESSION['error'] = "Vous devez être connecté pour accéder à cette page.";
            header('Location: /login');
            exit;
        }
        require_once __DIR__ . '/../views/profile.php';
    }


    public function updateUsername() {
        if (!isset($_SESSION['user'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Non autorisé']);
            return;
        }

        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            http_response_code(403);
            echo json_encode(['error' => 'Token CSRF invalide']);
            return;
        }

        $newUsername = trim(filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING));
        
        if (empty($newUsername) || strlen($newUsername) < 3) {
            http_response_code(400);
            echo json_encode(['error' => 'Le nom d\'utilisateur doit contenir au moins 3 caractères.']);
            return;
        }

        $result = $this->userModel->updateUsername($_SESSION['user']['id'], $newUsername);
        
        if ($result['success']) {
            $_SESSION['user']['username'] = $newUsername;
            echo json_encode(['success' => true]);
        } else {
            http_response_code(400);
            echo json_encode(['error' => $result['error']]);
        }
    }

    public function updateEmail() {
        header('Content-Type: application/json');
    
        if (!isset($_SESSION['user'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Non autorisé']);
            return;
        }
    
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            http_response_code(403);
            echo json_encode(['error' => 'Token CSRF invalide']);
            return;
        }
    
        $newEmail = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
        
        if (!$newEmail) {
            http_response_code(400);
            echo json_encode(['error' => 'Email invalide']);
            return;
        }
    
        $result = $this->userModel->updateEmail($_SESSION['user']['id'], $newEmail);
    
        if ($result['success']) {
            $_SESSION['user']['email'] = $newEmail;
            echo json_encode(['success' => true, 'message' => 'Votre email a été mis à jour avec succès.']);
        } else {
            http_response_code(400);
            echo json_encode(['error' => $result['error']]);
        }
    }    

    public function updatePassword() {
        if (!isset($_SESSION['user'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Non autorisé']);
            return;
        }

        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            http_response_code(403);
            echo json_encode(['error' => 'Token CSRF invalide']);
            return;
        }

        $currentPassword = $_POST['current_password'];
        $newPassword = $_POST['new_password'];
        $confirmPassword = $_POST['confirm_password'];

        if ($newPassword !== $confirmPassword) {
            http_response_code(400);
            echo json_encode(['error' => 'Les mots de passe ne correspondent pas.']);
            return;
        }

        if (!preg_match('/^(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/', $newPassword)) {
            http_response_code(400);
            echo json_encode(['error' => 'Le mot de passe doit contenir au moins 8 caractères, une majuscule, un chiffre et un caractère spécial.']);
            return;
        }

        $result = $this->userModel->changePassword($_SESSION['user']['id'], $currentPassword, $newPassword);
        
        if ($result['success']) {
            echo json_encode(['success' => true]);
        } else {
            http_response_code(400);
            echo json_encode(['error' => $result['error']]);
        }
    }

    public function updateNotificationPreferences() {
        header('Content-Type: application/json');
    
        if (!isset($_SESSION['user'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Non autorisé']);
            return;
        }
    
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            http_response_code(403);
            echo json_encode(['error' => 'Token CSRF invalide']);
            return;
        }
    
        if (!isset($_POST['email_notifications'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Paramètre email_notifications manquant.']);
            return;
        }

        $emailNotifications = filter_var($_POST['email_notifications'], FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
    
        $result = $this->userModel->updateNotificationPreferences($_SESSION['user']['id'], $emailNotifications);
    
        if ($result['success']) {
            $_SESSION['user']['email_notifications'] = $emailNotifications;
            echo json_encode(['success' => true, 'message' => 'Préférences mises à jour.']);
        } else {
            http_response_code(400);
            echo json_encode(['error' => $result['error']]);
        }
    }

    public function deleteAccount() {
        if (!isset($_SESSION['user'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Non autorisé']);
            return;
        }
    
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            http_response_code(403);
            echo json_encode(['error' => 'Token CSRF invalide']);
            return;
        }
    
        $userId = $_SESSION['user']['id'];
    
        $result = $this->userModel->deleteAccount($userId);
    
        if ($result['success']) {
            session_unset();
            session_destroy();
            echo json_encode(['success' => true, 'message' => 'Compte supprimé avec succès.']);
        } else {
            http_response_code(400);
            echo json_encode(['error' => $result['error']]);
        }
    }    
}