<?php
class Database {
    private static $instance = null;
    private $pdo;

    private function __construct() {
        $env = parse_ini_file(__DIR__ . '/../.env');

        $host = 'camagru-mysql';
        $dbname = $env['DB_DATABASE'] ?? 'camagru';
        $username = $env['DB_USERNAME'] ?? 'root';
        $password = $env['DB_PASSWORD'] ?? '';

        try {
            $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_PERSISTENT         => true
            ];

            $this->pdo = new PDO($dsn, $username, $password, $options);
        } catch (PDOException $e) {
            error_log("❌ Erreur de connexion à la base de données: " . $e->getMessage());
            die("Erreur de connexion à la base de données. Veuillez réessayer plus tard.");
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance->pdo;
    }
}
