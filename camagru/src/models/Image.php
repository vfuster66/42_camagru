<?php

require_once __DIR__ . '/../config/database.php';

class Image
{
    private $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance();
    }

    public function saveImage($userId, $imagePath)
    {
        try {
            error_log("Tentative de sauvegarde d'image - userId: $userId, imagePath: $imagePath");

            $filePath = dirname(__DIR__, 2) . '/public/uploads/' . $imagePath;
            $imageInfo = getimagesize($filePath);

            if (!$imageInfo) {
                error_log("Impossible d'obtenir les informations de l'image");
                return false;
            }

            $sql = "INSERT INTO images (
                user_id, 
                image_path, 
                original_name,
                mime_type,
                file_size,
                width,
                height,
                is_public
            ) VALUES (
                :user_id, 
                :image_path, 
                :original_name,
                :mime_type,
                :file_size,
                :width,
                :height,
                :is_public
            )";

            $stmt = $this->pdo->prepare($sql);

            $fileSize = filesize($filePath);

            $params = [
                'user_id' => $userId,
                'image_path' => $imagePath,
                'original_name' => pathinfo($filePath, PATHINFO_BASENAME),
                'mime_type' => $imageInfo['mime'],
                'file_size' => $fileSize,
                'width' => $imageInfo[0],
                'height' => $imageInfo[1],
                'is_public' => true
            ];

            error_log("Paramètres de la requête: " . print_r($params, true));

            $result = $stmt->execute($params);

            if (!$result) {
                error_log("Erreur SQL: " . print_r($stmt->errorInfo(), true));
                return false;
            }

            error_log("Image sauvegardée avec succès !");
            return true;
        } catch (PDOException $e) {
            error_log("Erreur PDO lors de la sauvegarde de l'image: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            return false;
        }
    }

    public function getImageById($imageId)
    {
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
    
            $images = $stmt->fetchAll(PDO::FETCH_ASSOC);
            error_log("📸 Images retournées pour la page $page : " . count($images));
    
            return $images;
        } catch (PDOException $e) {
            error_log("❌ Erreur dans getAllImages : " . $e->getMessage());
            return false;
        }
    }    

    public function deleteImage($imageId, $userId)
    {
        try {

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

    public function getTotalImagesCount()
    {
        try {
            $sql = "SELECT COUNT(*) FROM images";
            $stmt = $this->pdo->query($sql);
            return $stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Erreur lors du comptage des images: " . $e->getMessage());
            return 0;
        }
    }

    public function getImagesByUserId($userId)
    {
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

    public function getUserImagesCount($userId)
    {
        try {
            $sql = "SELECT COUNT(*) FROM images WHERE user_id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$userId]);
            return $stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Erreur dans getUserImagesCount: " . $e->getMessage());
            return 0;
        }
    }
}
