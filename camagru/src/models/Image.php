<?php

require_once __DIR__ . '/../config/database.php';

class Image {
    private $pdo;

    public function __construct() {
        $this->pdo = Database::getInstance();
    }

    /**
     * Sauvegarde une nouvelle image
     */
    public function saveImage($userId, $imagePath) {
        try {
            $sql = "INSERT INTO images (user_id, image_path) VALUES (:user_id, :image_path)";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([
                'user_id' => $userId,
                'image_path' => $imagePath
            ]);
        } catch (PDOException $e) {
            error_log("Erreur lors de la sauvegarde de l'image: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Récupère une image par son ID
     */
    public function getImageById($imageId) {
        try {
            $sql = "SELECT i.*, u.username 
                    FROM images i 
                    JOIN users u ON i.user_id = u.id 
                    WHERE i.id = :id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(['id' => $imageId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération de l'image: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Récupère toutes les images pour la galerie avec pagination
     */
    public function getAllImages($page = 1, $limit = 5) {
        try {
            $offset = ($page - 1) * $limit;
            $sql = "SELECT i.*, u.username, 
                    (SELECT COUNT(*) FROM likes WHERE image_id = i.id) as likes_count,
                    (SELECT COUNT(*) FROM comments WHERE image_id = i.id) as comments_count
                    FROM images i 
                    JOIN users u ON i.user_id = u.id 
                    ORDER BY i.created_at DESC 
                    LIMIT :limit OFFSET :offset";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des images: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Supprime une image
     */
    public function deleteImage($imageId, $userId) {
        try {
            // Vérifie que l'utilisateur est bien le propriétaire de l'image
            $sql = "DELETE FROM images WHERE id = :id AND user_id = :user_id";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([
                'id' => $imageId,
                'user_id' => $userId
            ]);
        } catch (PDOException $e) {
            error_log("Erreur lors de la suppression de l'image: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Récupère le nombre total d'images
     */
    public function getTotalImagesCount() {
        try {
            $sql = "SELECT COUNT(*) FROM images";
            $stmt = $this->pdo->query($sql);
            return $stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Erreur lors du comptage des images: " . $e->getMessage());
            return 0;
        }
    }
    public function getImagesByUserId($userId) {
        try {
            $sql = "SELECT * FROM images WHERE user_id = :user_id ORDER BY created_at DESC";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(['user_id' => $userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des images: " . $e->getMessage());
            return false;
        }
    }
}