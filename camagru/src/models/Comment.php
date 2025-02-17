<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/User.php';

class Comment
{
    private $pdo;
    private $userModel;

    public function __construct()
    {
        $this->pdo = Database::getInstance();
        $this->pdo->exec("SET NAMES utf8mb4");
        $this->userModel = new User();
    }

    public function addComment($imageId, $userId, $content)
    {
        try {
            $this->pdo->beginTransaction();

            $sql = "INSERT INTO comments (image_id, user_id, content) VALUES (?, ?, ?)";
            $stmt = $this->pdo->prepare($sql);
            $success = $stmt->execute([$imageId, $userId, $content]);

            if (!$success) {
                throw new Exception("Erreur lors de l'ajout du commentaire");
            }

            $commentId = $this->pdo->lastInsertId();

            $sql = "SELECT i.*, u.email, u.email_notifications, u.username 
                FROM images i 
                JOIN users u ON i.user_id = u.id 
                WHERE i.id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$imageId]);
            $imageInfo = $stmt->fetch(PDO::FETCH_ASSOC);

            $this->pdo->commit();

            return [
                'comment_id' => $commentId,
                'image_owner' => $imageInfo
            ];
        } catch (Exception $e) {
            $this->pdo->rollBack();
            error_log("Erreur dans addComment: " . $e->getMessage());
            return false;
        }
    }

    public function getComments($imageId)
    {
        try {
            $sql = "SELECT c.*, u.username 
                FROM comments c 
                JOIN users u ON c.user_id = u.id 
                WHERE c.image_id = ? AND c.is_inappropriate = FALSE 
                ORDER BY c.created_at DESC";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$imageId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur dans getComments: " . $e->getMessage());
            return [];
        }
    }

    public function deleteComment($commentId, $userId)
    {
        try {
            $sql = "DELETE FROM comments WHERE id = ? AND user_id = ?";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([$commentId, $userId]);
        } catch (PDOException $e) {
            error_log("Erreur dans deleteComment: " . $e->getMessage());
            return false;
        }
    }

    public function reportComment($commentId)
    {
        try {
            $sql = "UPDATE comments 
                SET reported_count = reported_count + 1,
                    is_inappropriate = CASE WHEN reported_count >= 5 THEN TRUE ELSE FALSE END
                WHERE id = ?";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([$commentId]);
        } catch (PDOException $e) {
            error_log("Erreur dans reportComment: " . $e->getMessage());
            return false;
        }
    }
}
