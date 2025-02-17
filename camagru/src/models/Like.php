<?php

require_once __DIR__ . '/../config/database.php';

class Like {
    private $pdo;

    public function __construct() {
        $this->pdo = Database::getInstance();
    }

    public function toggleLike($imageId, $userId) {
        try {
            $stmt = $this->pdo->prepare("SELECT id FROM likes WHERE image_id = ? AND user_id = ?");
            $stmt->execute([$imageId, $userId]);
            $existingLike = $stmt->fetch();

            $this->pdo->beginTransaction();

            if ($existingLike) {

                $stmt = $this->pdo->prepare("DELETE FROM likes WHERE image_id = ? AND user_id = ?");
                $stmt->execute([$imageId, $userId]);
                $result = ['action' => 'unliked'];
            } else {
                $stmt = $this->pdo->prepare("INSERT INTO likes (image_id, user_id) VALUES (?, ?)");
                $stmt->execute([$imageId, $userId]);
                $result = ['action' => 'liked'];
            }

            $this->pdo->commit();

            $stmt = $this->pdo->prepare("SELECT COUNT(*) as count FROM likes WHERE image_id = ?");
            $stmt->execute([$imageId]);
            $result['likes_count'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

            return $result;
        } catch (PDOException $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            error_log("Erreur dans toggleLike: " . $e->getMessage());
            return false;
        }
    }

    public function hasUserLiked($imageId, $userId) {
        try {
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM likes WHERE image_id = ? AND user_id = ?");
            $stmt->execute([$imageId, $userId]);
            return $stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            error_log("Erreur dans hasUserLiked: " . $e->getMessage());
            return false;
        }
    }

    public function getLikesCount($imageId) {
        try {
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM likes WHERE image_id = ?");
            $stmt->execute([$imageId]);
            return $stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Erreur dans getLikesCount: " . $e->getMessage());
            return 0;
        }
    }
}