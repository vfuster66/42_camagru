<?php

require_once __DIR__ . '/../models/Like.php';

class LikeController {
    private $likeModel;

    public function __construct() {
        $this->likeModel = new Like();
    }

    public function toggleLike() {
        if (!isset($_SESSION['user'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Non autorisé']);
            return;
        }

        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            http_response_code(403);
            echo json_encode(['error' => 'Token CSRF invalide']);
            return;
        }

        $imageId = filter_input(INPUT_POST, 'image_id', FILTER_VALIDATE_INT);
        if (!$imageId) {
            http_response_code(400);
            echo json_encode(['error' => 'ID d\'image invalide']);
            return;
        }

        $result = $this->likeModel->toggleLike($imageId, $_SESSION['user']['id']);
        
        if ($result === false) {
            http_response_code(500);
            echo json_encode(['error' => 'Erreur lors du traitement du like']);
            return;
        }

        echo json_encode($result);
    }
}