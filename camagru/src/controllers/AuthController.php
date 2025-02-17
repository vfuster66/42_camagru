<?php

require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../config/csrf.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(dirname(__DIR__, 2));
$dotenv->load();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

class AuthController
{
    private $userModel;

    public function __construct()
    {
        require_once __DIR__ . '/../models/User.php';
        $this->userModel = new User();
    }

    public function getUserModel()
    {
        return $this->userModel;
    }

    public function register()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
                die("Erreur CSRF, requête invalide.");
            }

            $username = htmlspecialchars(trim($_POST['username']), ENT_QUOTES, 'UTF-8');
            $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
            $password = $_POST['password'];
            $confirmPassword = $_POST['confirm_password'];

            if (empty($username) || empty($email) || empty($password) || empty($confirmPassword)) {
                $_SESSION['error'] = "Tous les champs sont obligatoires.";
                header("Location: /register");
                exit;
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $_SESSION['error'] = "Email invalide.";
                header("Location: /register");
                exit;
            }

            if (!preg_match('/^(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/', $password)) {
                $_SESSION['error'] = "Le mot de passe doit contenir au moins 8 caractères, une majuscule, un chiffre et un caractère spécial.";
                header("Location: /register");
                exit;
            }

            if ($password !== $confirmPassword) {
                $_SESSION['error'] = "Les mots de passe ne correspondent pas.";
                header("Location: /register");
                exit;
            }

            if ($this->userModel->getUserByEmail($email)) {
                $_SESSION['error'] = "Cet email est déjà utilisé.";
                header("Location: /register");
                exit;
            }

            try {
                $result = $this->userModel->createUser($username, $email, $password);
                if (!$result) {
                    throw new Exception("Erreur lors de la création de l'utilisateur");
                }

                $mail = new PHPMailer(true);
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = $_ENV['GMAIL_ADDRESS'];
                $mail->Password = $_ENV['GMAIL_PASSWORD'];
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = 587;
                $mail->CharSet = 'UTF-8';

                $mail->setFrom('no-reply@camagru.com', 'Camagru');
                $mail->addAddress($email, $username);

                $mail->isHTML(true);
                $mail->Subject = "Vérification de votre compte Camagru";
                $verificationLink = "http://localhost:8080/verify_email?token=" . $result['verification_token'];

                $mail->Body = "
                <h2>Bienvenue sur Camagru, {$username} !</h2>
                <p>Merci de votre inscription. Pour activer votre compte, veuillez cliquer sur le lien ci-dessous :</p>
                <p><a href='{$verificationLink}'>Vérifier mon compte</a></p>
                <p><strong>Important :</strong> Ce lien est valable pendant 24 heures.</p>
                <p>Si vous n'avez pas créé de compte sur Camagru, vous pouvez ignorer cet email.</p>
            ";

                $mail->AltBody = "
                Bienvenue sur Camagru, {$username} !
                Pour activer votre compte, copiez et collez ce lien dans votre navigateur :
                {$verificationLink}
                Ce lien est valable pendant 24 heures.
            ";

                $mail->send();

                $_SESSION['success'] = "Inscription réussie ! Un email de confirmation a été envoyé à {$email}. 
                                Veuillez cliquer sur le lien dans l'email pour activer votre compte.
                                N'oubliez pas de vérifier vos spams si vous ne trouvez pas l'email.";

                header("Location: /login");
                exit;
            } catch (Exception $e) {
                error_log("Erreur lors de l'inscription: " . $e->getMessage());
                $_SESSION['error'] = "Une erreur est survenue lors de l'inscription. Veuillez réessayer.";
                header("Location: /register");
                exit;
            }
        }
    }

    public function login()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
                die("Erreur CSRF, requête invalide.");
            }

            $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
            $password = $_POST['password'];

            $user = $this->userModel->loginUser($email, $password);

            if ($user) {
                $_SESSION['user'] = $user;
                header("Location: /profile");
                exit;
            } else {
                $_SESSION['error'] = "Identifiants incorrects.";
                header("Location: /login");
                exit;
            }
        }
    }

    public function forgotPassword()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            error_log("DEBUG: Requête reçue pour forgotPassword");

            if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
                error_log("❌ ERREUR: CSRF token invalide !");
                die("Erreur CSRF, requête invalide.");
            }

            $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
            error_log("DEBUG: Email reçu -> " . $email);

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                error_log("❌ ERREUR: Email invalide !");
                $_SESSION['error'] = "Email invalide.";
                header("Location: /forgot_password");
                exit;
            }

            $user = $this->userModel->getUserByEmail($email);
            if (!$user) {
                error_log("❌ ERREUR: Aucun utilisateur trouvé pour cet email.");
                $_SESSION['error'] = "Aucun compte trouvé avec cet email.";
                header("Location: /forgot_password");
                exit;
            }

            $token = bin2hex(random_bytes(50));
            error_log("DEBUG: Token généré -> " . $token);

            $this->userModel->storeResetToken($email, $token);

            $storedToken = $this->userModel->getUserByToken($token);
            if (!$storedToken) {
                error_log("❌ ERREUR: Le token n'a pas été enregistré correctement !");
            } else {
                error_log("✅ SUCCÈS: Token enregistré en base.");
            }

            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = $_ENV['GMAIL_ADDRESS'];
                $mail->Password = $_ENV['GMAIL_PASSWORD'];
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = 587;

                $mail->setFrom('no-reply@camagru.com', 'Camagru');
                $mail->addAddress($email);

                $mail->isHTML(true);
                $mail->Subject = "Réinitialisation de votre mot de passe";
                $mail->Body = "Cliquez sur ce lien pour réinitialiser votre mot de passe : 
                    <a href='http://localhost:8080/reset_password?token=$token'>Réinitialiser mon mot de passe</a>";

                $mail->send();
                error_log("✅ SUCCÈS: Email envoyé avec le token.");

                $_SESSION['success'] = "Un email de réinitialisation a été envoyé.";
                header("Location: /forgot_password");
                exit;
            } catch (Exception $e) {
                error_log("❌ ERREUR: L'email n'a pas pu être envoyé. Erreur: " . $mail->ErrorInfo);
                $_SESSION['error'] = "L'email n'a pas pu être envoyé.";
                header("Location: /forgot_password");
                exit;
            }
        }
    }

    public function resetPassword()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
                die("Erreur CSRF, requête invalide.");
            }

            $token = $_POST['token'];
            $newPassword = $_POST['new_password'];
            $confirmPassword = $_POST['confirm_password'];

            if (!preg_match('/^(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/', $newPassword)) {
                $_SESSION['error'] = "Le mot de passe doit contenir au moins 8 caractères, une majuscule, un chiffre et un caractère spécial.";
                header("Location: /reset_password?token=" . $token);
                exit;
            }

            if ($newPassword !== $confirmPassword) {
                $_SESSION['error'] = "Les mots de passe ne correspondent pas.";
                header("Location: /reset_password?token=" . $token);
                exit;
            }

            $user = $this->userModel->getUserByToken($token);

            if (!$user) {
                $_SESSION['error'] = "Token invalide ou expiré.";
                header("Location: /forgot_password");
                exit;
            }

            if ($this->userModel->updatePassword($user['email'], $newPassword)) {
                $_SESSION['success'] = "Mot de passe réinitialisé avec succès !";
                header("Location: /login");
                exit;
            } else {
                $_SESSION['error'] = "Erreur lors de la réinitialisation.";
                header("Location: /reset_password?token=" . $token);
                exit;
            }
        }
    }

    public function logout()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $_SESSION = [];
        session_destroy();

        if (ini_get("session.use_cookies")) {
            setcookie(session_name(), '', time() - 42000, '/');
        }

        header("Location: /login");
        exit;
    }

    public function verifyEmail() 
    {
        error_log("=== Début de la vérification d'email ===");
    
        try {
            if (!isset($_GET['token'])) {
                error_log("❌ Token manquant dans l'URL");
                throw new Exception("Token de vérification manquant.");
            }
    
            $token = htmlspecialchars(trim($_GET['token']));
            error_log("Token reçu: " . $token);
    
            $user = $this->userModel->getUserByVerificationToken($token);
    
            if (!$user) {
                error_log("❌ Aucun utilisateur trouvé avec ce token");
                throw new Exception("Token de vérification invalide ou déjà utilisé.");
            }
    
            error_log("✅ Utilisateur trouvé: " . json_encode($user));

            $tokenCreationTime = strtotime($user['created_at']);
            $timeDiff = time() - $tokenCreationTime;
            error_log("Temps écoulé depuis la création: " . $timeDiff . " secondes");
    
            if ($timeDiff > 24 * 3600) {
                error_log("❌ Token expiré");
                throw new Exception("Le lien de vérification a expiré. Veuillez vous réinscrire.");
            }
    
            error_log("Tentative de vérification du compte...");
            $verificationSuccess = $this->userModel->verifyUser($token);
            error_log("Résultat de la vérification: " . ($verificationSuccess ? "Succès" : "Échec"));
    
            if (!$verificationSuccess) {
                error_log("❌ Échec de la mise à jour en base de données");
                throw new Exception("Erreur lors de la vérification du compte.");
            }
    
            session_unset();
            session_destroy();
            session_start();
    
            $_SESSION['success'] = "Votre email a été vérifié avec succès. Vous pouvez maintenant vous connecter.";
    
            error_log("✅ Vérification réussie! Redirection vers login.");
            
        } catch (Exception $e) {
            error_log("❌ ERREUR: " . $e->getMessage());
            $_SESSION['error'] = $e->getMessage();
        }
    
        error_log("=== Fin de la vérification d'email ===");
        header('Location: /login');
        exit;
    }    
}
