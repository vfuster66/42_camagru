<?php
class Database {
    private static $instance = null;
    private $pdo;

    private function __construct() {
        $host = 'camagru-mysql'; // Nom du conteneur MySQL
        $dbname = 'camagru';
        $username = 'vfuster';
        $password = 'Bonjour42';

        try {
            $this->pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            die("Erreur de connexion à la base de données: " . $e->getMessage());
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance->pdo;
    }
}
