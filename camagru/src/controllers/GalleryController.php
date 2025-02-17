<?php

require_once __DIR__ . '/../models/Image.php';
require_once __DIR__ . '/../models/User.php';

class GalleryController {
    private $imageModel;
    private $userModel;
    private $imagesPerPage = 5;

    public function __construct() {
        $this->imageModel = new Image();
        $this->userModel = new User();
    }

    public function showGallery() {
        $images = $this->imageModel->getAllImages(1, $this->imagesPerPage);
        $totalImages = $this->imageModel->getTotalImagesCount();
        $totalPages = ceil($totalImages / $this->imagesPerPage);

        $viewData = [
            'images' => $images,
            'pagination' => [
                'totalPages' => $totalPages,
                'hasMore' => $totalPages > 1
            ],
            'user' => $_SESSION['user'] ?? null
        ];

        require_once __DIR__ . '/../views/gallery.php';
    }

    public function ajaxLoadImages() {
        header('Content-Type: application/json');
    
        try {
            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            error_log("🔍 Chargement des images pour la page : " . $page);
    
            $images = $this->imageModel->getAllImages($page, $this->imagesPerPage);
            $totalImages = $this->imageModel->getTotalImagesCount();
            $totalPages = ceil($totalImages / $this->imagesPerPage);
    
            error_log("📸 Nombre d'images récupérées : " . count($images));
    
            echo json_encode([
                'success' => true,
                'images' => $images,
                'pagination' => [
                    'currentPage' => $page,
                    'totalPages' => $totalPages,
                    'hasMore' => $page < $totalPages
                ]
            ]);
        } catch (Exception $e) {
            error_log("❌ Erreur ajaxLoadImages : " . $e->getMessage());
            echo json_encode([
                'success' => false,
                'error' => 'Erreur lors du chargement des images'
            ]);
        }
    }    
}
