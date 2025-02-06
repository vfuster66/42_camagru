<?php

require_once __DIR__ . '/../models/User.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

class AuthController {
    private $userModel;

    public function __construct() {
        $this->userModel = new User();
    }

    /**
     * Gère l'inscription d'un utilisateur
     */
    public function register() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = trim($_POST['username']);
            $email = trim($_POST['email']);
            $password = $_POST['password'];
            $confirmPassword = $_POST['confirm_password'];
    
            // Vérification des champs vides
            if (empty($username) || empty($email) || empty($password) || empty($confirmPassword)) {
                $_SESSION['error'] = "Tous les champs sont obligatoires.";
                header("Location: /register");
                exit;
            }
    
            // Vérification de l'email
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $_SESSION['error'] = "Email invalide.";
                header("Location: /register");
                exit;
            }
    
            // Vérification du mot de passe
            if ($password !== $confirmPassword) {
                $_SESSION['error'] = "Les mots de passe ne correspondent pas.";
                header("Location: /register");
                exit;
            }
    
            // Vérification si l'utilisateur existe déjà
            if ($this->userModel->getUserByEmail($email)) {
                $_SESSION['error'] = "Cet email est déjà utilisé.";
                header("Location: /register");
                exit;
            }
    
            // Création de l'utilisateur
            if ($this->userModel->createUser($username, $email, $password)) {
                $_SESSION['success'] = "Inscription réussie ! Connectez-vous.";
                header("Location: /register");
                exit;
            } else {
                $_SESSION['error'] = "Erreur lors de l'inscription.";
                header("Location: /register");
                exit;
            }
        }
    }    

    /**
     * Gère la connexion d'un utilisateur
     */
    public function login() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = trim($_POST['email']);
            $password = $_POST['password'];

            $user = $this->userModel->loginUser($email, $password);
            if ($user) {
                $_SESSION['user'] = $user;
                header("Location: /");
                exit;
            } else {
                $_SESSION['error'] = "Identifiants incorrects.";
                header("Location: /login");
                exit;
            }
        }
    }

    public function forgotPassword() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = trim($_POST['email']);
    
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $_SESSION['error'] = "Email invalide.";
                header("Location: /forgot_password");
                exit;
            }
    
            $user = $this->userModel->getUserByEmail($email);
            if (!$user) {
                $_SESSION['error'] = "Aucun compte trouvé avec cet email.";
                header("Location: /forgot_password");
                exit;
            }
    
            // Générer un token unique
            $token = bin2hex(random_bytes(50));
            $_SESSION['success'] = "Un email de réinitialisation a été envoyé.";
            // Ici, on stocke ce token en base et on envoie un email (à implémenter)
            header("Location: /forgot_password");
            exit;
        }
    }    

    /**
     * Gère la déconnexion
     */
    public function logout() {
        session_destroy();
        header("Location: /login");
        exit;
    }
}
