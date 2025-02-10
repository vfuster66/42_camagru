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

    // ✅ Nouvelle méthode pour récupérer `userModel`
    public function getUserModel()
    {
        return $this->userModel;
    }

    /**
     * Gère l'inscription d'un utilisateur
     */
    public function register()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Vérification du token CSRF
            if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
                die("Erreur CSRF, requête invalide.");
            }

            // Nettoyage des entrées utilisateur
            $username = htmlspecialchars(trim($_POST['username']), ENT_QUOTES, 'UTF-8');
            $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
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

            // Vérification du mot de passe sécurisé
            if (!preg_match('/^(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/', $password)) {
                $_SESSION['error'] = "Le mot de passe doit contenir au moins 8 caractères, une majuscule, un chiffre et un caractère spécial.";
                header("Location: /register");
                exit;
            }

            // Vérification que les mots de passe correspondent
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

            try {
                // Création de l'utilisateur
                $result = $this->userModel->createUser($username, $email, $password);
                if (!$result) {
                    throw new Exception("Erreur lors de la création de l'utilisateur");
                }

                // Configuration du mail
                $mail = new PHPMailer(true);
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = $_ENV['GMAIL_ADDRESS'];
                $mail->Password = $_ENV['GMAIL_PASSWORD'];
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = 587;
                $mail->CharSet = 'UTF-8';

                // Configuration des destinataires
                $mail->setFrom('no-reply@camagru.com', 'Camagru');
                $mail->addAddress($email, $username);

                // Configuration du contenu
                $mail->isHTML(true);
                $mail->Subject = "Vérification de votre compte Camagru";
                $verificationLink = "http://localhost:8080/verify_email?token=" . $result['verification_token'];

                // Corps du mail avec plus de détails
                $mail->Body = "
                <h2>Bienvenue sur Camagru, {$username} !</h2>
                <p>Merci de votre inscription. Pour activer votre compte, veuillez cliquer sur le lien ci-dessous :</p>
                <p><a href='{$verificationLink}'>Vérifier mon compte</a></p>
                <p><strong>Important :</strong> Ce lien est valable pendant 24 heures.</p>
                <p>Si vous n'avez pas créé de compte sur Camagru, vous pouvez ignorer cet email.</p>
            ";

                // Version texte pour les clients mail qui ne supportent pas l'HTML
                $mail->AltBody = "
                Bienvenue sur Camagru, {$username} !
                Pour activer votre compte, copiez et collez ce lien dans votre navigateur :
                {$verificationLink}
                Ce lien est valable pendant 24 heures.
            ";

                $mail->send();

                // Message de succès détaillé
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

    /**
     * Gère la connexion d'un utilisateur
     */
    public function login()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            // Vérification du token CSRF
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

    /**
     * Gère la réinitialisation du mot de passe (forgot password)
     */

    public function forgotPassword()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            error_log("DEBUG: Requête reçue pour forgotPassword");

            // Vérification CSRF
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

            // Générer un token unique
            $token = bin2hex(random_bytes(50));
            error_log("DEBUG: Token généré -> " . $token);

            // Stocker le token en base
            $this->userModel->storeResetToken($email, $token);

            // Vérifier si le token est bien enregistré en base
            $storedToken = $this->userModel->getUserByToken($token);
            if (!$storedToken) {
                error_log("❌ ERREUR: Le token n'a pas été enregistré correctement !");
            } else {
                error_log("✅ SUCCÈS: Token enregistré en base.");
            }

            // **Configuration SMTP avec Gmail**
            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = $_ENV['GMAIL_ADDRESS'];
                $mail->Password = $_ENV['GMAIL_PASSWORD'];
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = 587;

                // Destinataire
                $mail->setFrom('no-reply@camagru.com', 'Camagru');
                $mail->addAddress($email);

                // Contenu du mail
                $mail->isHTML(true);
                $mail->Subject = "Réinitialisation de votre mot de passe";
                $mail->Body = "Cliquez sur ce lien pour réinitialiser votre mot de passe : 
                    <a href='http://localhost:8080/reset_password?token=$token'>Réinitialiser mon mot de passe</a>";

                // Envoyer l'email
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

    /**
     * Gère la modification du mot de passe
     */
    public function resetPassword()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Vérification du token CSRF
            if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
                die("Erreur CSRF, requête invalide.");
            }

            $token = $_POST['token'];
            $newPassword = $_POST['new_password'];
            $confirmPassword = $_POST['confirm_password'];

            // Vérification des mots de passe
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

    /**
     * Gère la déconnexion
     */
    public function logout()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Détruire la session et le cookie de session
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
            // Vérification de la présence du token
            error_log("Vérification du token dans l'URL...");
            if (!isset($_GET['token'])) {
                error_log("❌ Token manquant dans l'URL");
                throw new Exception("Token de vérification manquant.");
            }
    
            // Récupération et nettoyage du token
            $token = htmlspecialchars(trim($_GET['token']));
            error_log("Token reçu: " . $token);
    
            // Récupération de l'utilisateur
            error_log("Recherche de l'utilisateur avec ce token...");
            $user = $this->userModel->getUserByVerificationToken($token);
    
            if (!$user) {
                error_log("❌ Aucun utilisateur trouvé avec ce token");
                throw new Exception("Token de vérification invalide ou déjà utilisé.");
            }
    
            error_log("✅ Utilisateur trouvé: " . json_encode($user));
    
            // Vérification de l'expiration du token (24h)
            error_log("Vérification de l'expiration du token...");
            $tokenCreationTime = strtotime($user['created_at']);
            $timeDiff = time() - $tokenCreationTime;
            error_log("Temps écoulé depuis la création: " . $timeDiff . " secondes");
            
            if ($timeDiff > 24 * 3600) {
                error_log("❌ Token expiré");
                throw new Exception("Le lien de vérification a expiré. Veuillez vous réinscrire.");
            }
    
            // Vérification du compte
            error_log("Tentative de vérification du compte...");
            $verificationResult = $this->userModel->verifyUser($token);
            error_log("Résultat de la vérification: " . ($verificationResult ? "Succès" : "Échec"));
    
            if (!$verificationResult) {
                error_log("❌ Échec de la mise à jour en base de données");
                throw new Exception("Erreur lors de la vérification du compte.");
            }
    
            // Succès
            error_log("✅ Vérification réussie!");
            $_SESSION['success'] = "Votre compte a été vérifié avec succès. Vous pouvez maintenant vous connecter.";
            
        } catch (Exception $e) {
            error_log("❌ ERREUR: " . $e->getMessage());
            error_log("Trace: " . $e->getTraceAsString());
            $_SESSION['error'] = $e->getMessage();
        }
    
        error_log("=== Fin de la vérification d'email ===");
        header('Location: /verify_email');
        exit;
    }
}
