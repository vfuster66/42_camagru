<?php

require_once __DIR__ . '/../config/database.php';

class User
{
    private $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance();
    }

    public function createUser($username, $email, $password)
    {
        try {
            $this->pdo->beginTransaction();

            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
            $verificationToken = bin2hex(random_bytes(32));

            $sql = "INSERT INTO users (username, email, password, verification_token, is_verified) 
                VALUES (:username, :email, :password, :verification_token, FALSE)";

            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute([
                'username' => $username,
                'email' => $email,
                'password' => $hashedPassword,
                'verification_token' => $verificationToken
            ]);

            if (!$result) {
                $this->pdo->rollBack();
                error_log("Database error: " . print_r($stmt->errorInfo(), true));
                return false;
            }

            $userId = $this->pdo->lastInsertId();
            $this->pdo->commit();

            return [
                'user_id' => $userId,
                'verification_token' => $verificationToken
            ];
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            error_log("PDO error in createUser: " . $e->getMessage());
            return false;
        }
    }

    public function getUserByEmail($email)
    {
        try {
            $sql = "SELECT * FROM users WHERE email = :email";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(['email' => $email]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("PDO error in getUserByEmail: " . $e->getMessage());
            return false;
        }
    }

    public function storeResetToken($email, $token)
    {
        try {
            $sql = "UPDATE users 
                SET reset_token = :token,
                    reset_expires = DATE_ADD(NOW(), INTERVAL 1 HOUR)
                WHERE email = :email";

            $stmt = $this->pdo->prepare($sql);
            $success = $stmt->execute([
                'token' => $token,
                'email' => $email
            ]);

            if (!$success) {
                error_log("Reset token update failed for email: $email");
                error_log("PDO Error Info: " . print_r($stmt->errorInfo(), true));
            }

            return $success;
        } catch (PDOException $e) {
            error_log("PDO error in storeResetToken: " . $e->getMessage());
            return false;
        }
    }

    public function getUserByToken($token)
    {
        try {
            $sql = "SELECT * FROM users 
                WHERE reset_token = :token 
                AND reset_expires > NOW()";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(['token' => $token]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("PDO error in getUserByToken: " . $e->getMessage());
            return false;
        }
    }

    public function updatePassword($email, $newPassword)
    {
        try {
            $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);

            $sql = "UPDATE users 
                SET password = :password,
                    reset_token = NULL,
                    reset_expires = NULL
                WHERE email = :email";

            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([
                'password' => $hashedPassword,
                'email' => $email
            ]);
        } catch (PDOException $e) {
            error_log("PDO error in updatePassword: " . $e->getMessage());
            return false;
        }
    }

    public function loginUser($email, $password)
    {
        try {
            $user = $this->getUserByEmail($email);

            if (!$user || !password_verify($password, $user['password'])) {
                return false;
            }

            $sql = "UPDATE users SET last_login = NOW() WHERE id = :id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(['id' => $user['id']]);

            return $user;
        } catch (PDOException $e) {
            error_log("PDO error in loginUser: " . $e->getMessage());
            return false;
        }
    }

    public function getUserByVerificationToken($token)
    {
        error_log("=== Recherche utilisateur par token ===");
        try {
            $sql = "SELECT * FROM users WHERE verification_token = :token";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(['token' => $token]);

            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            error_log("Résultat de la requête: " . ($user ? json_encode($user) : "Aucun utilisateur trouvé"));

            return $user;
        } catch (PDOException $e) {
            error_log("❌ Erreur PDO dans getUserByVerificationToken: " . $e->getMessage());
            error_log("SQL State: " . $e->errorInfo[0]);
            error_log("Code erreur: " . $e->errorInfo[1]);
            error_log("Message: " . $e->errorInfo[2]);
            return false;
        }
    }

    public function verifyUser($token)
    {
        error_log("=== Début de la vérification utilisateur ===");
        try {
            error_log("Début de la transaction");
            $this->pdo->beginTransaction();

            $sql = "UPDATE users 
                    SET is_verified = TRUE, 
                        verification_token = NULL
                    WHERE verification_token = :token 
                    AND is_verified = FALSE";

            error_log("SQL préparé: " . $sql);
            error_log("Token utilisé: " . $token);

            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute(['token' => $token]);

            error_log("Résultat de l'exécution: " . ($result ? "Succès" : "Échec"));
            error_log("Nombre de lignes affectées: " . $stmt->rowCount());

            if ($stmt->rowCount() === 0) {
                error_log("❌ Aucune ligne mise à jour");
                $this->pdo->rollBack();
                return false;
            }

            error_log("✅ Commit de la transaction");
            $this->pdo->commit();
            return true;
        } catch (PDOException $e) {
            error_log("❌ Erreur PDO dans verifyUser: " . $e->getMessage());
            error_log("SQL State: " . $e->errorInfo[0]);
            error_log("Code erreur: " . $e->errorInfo[1]);
            error_log("Message: " . $e->errorInfo[2]);
            $this->pdo->rollBack();
            return false;
        }
    }

    public function updateUsername($userId, $newUsername)
    {
        try {
            $sql = "SELECT COUNT(*) FROM users WHERE username = ? AND id != ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$newUsername, $userId]);

            if ($stmt->fetchColumn() > 0) {
                return ['success' => false, 'error' => 'Ce nom d\'utilisateur est déjà pris.'];
            }

            $sql = "UPDATE users SET username = ? WHERE id = ?";
            $stmt = $this->pdo->prepare($sql);
            $success = $stmt->execute([$newUsername, $userId]);

            return ['success' => $success];
        } catch (PDOException $e) {
            error_log("Erreur dans updateUsername: " . $e->getMessage());
            return ['success' => false, 'error' => 'Erreur lors de la mise à jour du nom d\'utilisateur.'];
        }
    }

    public function updateEmail($userId, $newEmail) {
        try {
            error_log("Début updateEmail dans le modèle - userId: $userId, newEmail: $newEmail");
            
            $sql = "SELECT COUNT(*) FROM users WHERE email = ? AND id != ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$newEmail, $userId]);
            
            if ($stmt->fetchColumn() > 0) {
                error_log("Email déjà utilisé");
                return ['success' => false, 'error' => 'Cet email est déjà utilisé.'];
            }
    
            $sql = "UPDATE users SET email = ? WHERE id = ?";
            $stmt = $this->pdo->prepare($sql);
            $success = $stmt->execute([$newEmail, $userId]);
    
            error_log("Résultat de la mise à jour: " . ($success ? "succès" : "échec"));
    
            if ($success) {
                return [
                    'success' => true,
                    'email' => $newEmail
                ];
            }
            return ['success' => false, 'error' => 'Erreur lors de la mise à jour de l\'email.'];
        } catch (PDOException $e) {
            error_log("Erreur PDO dans updateEmail: " . $e->getMessage());
            return ['success' => false, 'error' => 'Erreur lors de la mise à jour de l\'email.'];
        }
    }    

    public function changePassword($userId, $currentPassword, $newPassword)
    {
        try {
            $sql = "SELECT password FROM users WHERE id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$userId]);
            $user = $stmt->fetch();

            if (!$user || !password_verify($currentPassword, $user['password'])) {
                return ['success' => false, 'error' => 'Mot de passe actuel incorrect.'];
            }

            $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
            $sql = "UPDATE users SET password = ? WHERE id = ?";
            $stmt = $this->pdo->prepare($sql);
            $success = $stmt->execute([$hashedPassword, $userId]);

            return ['success' => $success];
        } catch (PDOException $e) {
            error_log("Erreur dans changePassword: " . $e->getMessage());
            return ['success' => false, 'error' => 'Erreur lors de la mise à jour du mot de passe.'];
        }
    }

    public function updateNotificationPreferences($userId, $emailNotifications)
    {
        try {
            $sql = "UPDATE users SET email_notifications = ? WHERE id = ?";
            $stmt = $this->pdo->prepare($sql);
            $success = $stmt->execute([(int)$emailNotifications, $userId]);
    
            return ['success' => $success];
        } catch (PDOException $e) {
            error_log("Erreur dans updateNotificationPreferences: " . $e->getMessage());
            return ['success' => false, 'error' => 'Erreur lors de la mise à jour des préférences.'];
        }
    }

    public function deleteAccount($userId)
    {
        try {
            $this->pdo->beginTransaction();
    
            $sql = "DELETE FROM users WHERE id = ?";
            $stmt = $this->pdo->prepare($sql);
            $success = $stmt->execute([$userId]);
    
            if ($success) {
                $this->pdo->commit();
                return ['success' => true];
            } else {
                $this->pdo->rollBack();
                return ['success' => false, 'error' => 'Erreur lors de la suppression du compte.'];
            }
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            error_log("Erreur dans deleteAccount: " . $e->getMessage());
            return ['success' => false, 'error' => 'Une erreur est survenue.'];
        }
    }    
}
