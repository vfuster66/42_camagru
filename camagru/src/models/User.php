<?php

require_once __DIR__ . '/../config/database.php';

class User {
    private $pdo;

    public function __construct() {
        $this->pdo = Database::getInstance();
    }

    /**
     * Inscription d'un nouvel utilisateur
     */
    public function createUser($username, $email, $password) {
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

        $sql = "INSERT INTO users (username, email, password) VALUES (:username, :email, :password)";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            'username' => $username,
            'email' => $email,
            'password' => $hashedPassword
        ]);
    }

    /**
     * Vérifie si un utilisateur existe par email
     */
    public function getUserByEmail($email) {
        $sql = "SELECT * FROM users WHERE email = :email";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['email' => $email]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Vérifie les identifiants de connexion
     */
    public function loginUser($email, $password) {
        $user = $this->getUserByEmail($email);
        if ($user && password_verify($password, $user['password'])) {
            return $user;
        }
        return false;
    }
}
