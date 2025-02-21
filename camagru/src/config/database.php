<?php

class Database {
    private static $instance = null;
    private $pdo;

    private function __construct() {
        $config = parse_ini_file(__DIR__ . '/../../.env');

        if (!$config) {
            die("Erreur : Impossible de charger le fichier .env");
        }

        $host = 'camagru-mysql';
        $dbname = $config['DB_DATABASE'];
        $username = $config['DB_USERNAME'];
        $password = $config['DB_PASSWORD'];

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
?>