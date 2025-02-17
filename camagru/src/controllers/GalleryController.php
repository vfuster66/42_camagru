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
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $page = max(1, $page);

        $images = $this->imageModel->getAllImages($page, $this->imagesPerPage);
        $totalImages = $this->imageModel->getTotalImagesCount();
        $totalPages = ceil($totalImages / $this->imagesPerPage);

        $pagination = [
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'hasNextPage' => $page < $totalPages,
            'hasPrevPage' => $page > 1
        ];

        $viewData = [
            'images' => $images,
            'pagination' => $pagination,
            'user' => isset($_SESSION['user']) ? $_SESSION['user'] : null
        ];

        require_once __DIR__ . '/../views/gallery.php';
    }

    public function ajaxLoadImages() {
        header('Content-Type: application/json');
        
        try {
            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $images = $this->imageModel->getAllImages($page, $this->imagesPerPage);
            $totalImages = $this->imageModel->getTotalImagesCount();
            $totalPages = ceil($totalImages / $this->imagesPerPage);

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
            echo json_encode([
                'success' => false,
                'error' => 'Erreur lors du chargement des images'
            ]);
        }
    }
}