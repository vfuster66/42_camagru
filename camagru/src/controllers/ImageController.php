<?php

require_once __DIR__ . '/../models/Image.php';

class ImageController {
    private $uploadDir;
    private $filtersDir;
    private $imageModel;

    private const MAX_FILE_SIZE = 5 * 1024 * 1024;
    private const MAX_USER_UPLOADS = 50;
    private const ALLOWED_MIME_TYPES = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif'
    ];
    private const MAX_TEMP_AGE = 3600;

    public function __construct() {
        $this->uploadDir = dirname(__DIR__, 2) . '/public/uploads/';
        $this->filtersDir = dirname(__DIR__, 2) . '/public/filters/';
        $this->imageModel = new Image();

        $this->cleanupTempFiles();
    }

    public function showEditor() {
        if (!isset($_SESSION['user'])) {
            $_SESSION['error'] = "Vous devez être connecté pour accéder à l'éditeur.";
            header('Location: /login');
            exit;
        }
        require_once __DIR__ . '/../views/editor.php';
    }

    public function uploadImage() {
        if (!isset($_SESSION['user'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Non autorisé']);
            return;
        }

        try {
            error_log("Début de uploadImage");

            $this->checkUserUploadLimit($_SESSION['user']['id']);

            if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
                throw new Exception('Token CSRF invalide');
            }

            if (!isset($_POST['image_data'])) {
                throw new Exception('Données d\'image manquantes');
            }

            $imageData = $_POST['image_data'];
            if (strpos($imageData, 'data:image/png;base64,') !== 0) {
                throw new Exception('Format de données incorrect');
            }

            $imageData = str_replace('data:image/png;base64,', '', $imageData);
            $imageData = str_replace(' ', '+', $imageData);
            $imageDecoded = base64_decode($imageData);

            if ($imageDecoded === false) {
                throw new Exception('Échec du décodage base64');
            }

            if (strlen($imageDecoded) > self::MAX_FILE_SIZE) {
                throw new Exception("L'image est trop volumineuse. Maximum " . (self::MAX_FILE_SIZE / 1024 / 1024) . "MB");
            }

            if (!file_exists($this->uploadDir)) {
                if (!mkdir($this->uploadDir, 0777, true)) {
                    throw new Exception('Impossible de créer le dossier uploads');
                }
            }

            if (!is_writable($this->uploadDir)) {
                throw new Exception('Le dossier uploads n\'est pas accessible en écriture');
            }

            $tempFileName = 'temp_' . uniqid() . '_' . time() . '.png';
            $tempFilePath = $this->uploadDir . $tempFileName;

            if (file_put_contents($tempFilePath, $imageDecoded) === false) {
                throw new Exception('Erreur lors de la sauvegarde du fichier temporaire');
            }

            try {
                if (!getimagesize($tempFilePath)) {
                    throw new Exception("Le fichier n'est pas une image valide");
                }

                $cleanFilePath = $this->sanitizeImage($tempFilePath);

                $finalFileName = 'img_' . uniqid() . '_' . time() . '.png';
                $finalFilePath = $this->uploadDir . $finalFileName;

                if (!rename($cleanFilePath, $finalFilePath)) {
                    throw new Exception('Erreur lors du déplacement du fichier final');
                }

                if ($this->imageModel->saveImage($_SESSION['user']['id'], $finalFileName)) {
                    error_log("Sauvegarde en BDD réussie !");

                    $this->cleanupTempFiles();

                    echo json_encode(['success' => true, 'path' => $finalFileName]);
                } else {
                    unlink($finalFilePath);
                    throw new Exception('Erreur lors de la sauvegarde en base de données');
                }

            } catch (Exception $e) {
                if (file_exists($tempFilePath)) unlink($tempFilePath);
                if (isset($cleanFilePath) && file_exists($cleanFilePath)) unlink($cleanFilePath);
                throw $e;
            }

        } catch (Exception $e) {
            error_log("Erreur dans uploadImage: " . $e->getMessage());
            http_response_code(400);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    public function captureImage() {
        header('Content-Type: application/json');
        error_log("Début de captureImage");

        if (!isset($_SESSION['user'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Non autorisé']);
            return;
        }

        try {
            if (!file_exists($this->uploadDir)) {
                if (!mkdir($this->uploadDir, 0777, true)) {
                    throw new Exception('Impossible de créer le dossier uploads');
                }
            }

            if (!is_writable($this->uploadDir)) {
                throw new Exception('Le dossier uploads n\'est pas accessible en écriture');
            }

            error_log("Dossier upload: " . $this->uploadDir);
            error_log("Permissions: " . substr(sprintf('%o', fileperms($this->uploadDir)), -4));

            error_log("POST reçu: " . print_r($_POST, true));

            if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
                throw new Exception('Token CSRF invalide');
            }

            if (!isset($_POST['image_data'])) {
                throw new Exception('Données d\'image manquantes');
            }

            $imageData = $_POST['image_data'];
            error_log("Longueur des données image: " . strlen($imageData));

            if (strpos($imageData, 'data:image/png;base64,') !== 0) {
                throw new Exception('Format de données incorrect');
            }

            $imageData = str_replace('data:image/png;base64,', '', $imageData);
            $imageData = str_replace(' ', '+', $imageData);
            $imageDecoded = base64_decode($imageData);

            if ($imageDecoded === false) {
                throw new Exception('Échec du décodage base64');
            }

            $fileName = uniqid() . '_' . time() . '.png';
            $filePath = $this->uploadDir . $fileName;

            error_log("Tentative de sauvegarde vers: " . $filePath);

            if (file_put_contents($filePath, $imageDecoded) === false) {
                error_log("Erreur lors de l'écriture du fichier. Permissions actuelles: " . substr(sprintf('%o', fileperms(dirname($filePath))), -4));
                throw new Exception('Erreur lors de la sauvegarde du fichier');
            }

            if (!file_exists($filePath)) {
                throw new Exception('Le fichier n\'a pas été créé');
            }

            error_log("Fichier créé avec succès: " . $filePath);
            error_log("Taille du fichier: " . filesize($filePath));

            $saved = $this->imageModel->saveImage($_SESSION['user']['id'], $fileName);
            if (!$saved) {
                throw new Exception('Erreur lors de la sauvegarde en base de données');
            }

            $response = [
                'success' => true,
                'path' => $fileName,
                'message' => 'Capture réussie'
            ];

            error_log("Réponse envoyée: " . json_encode($response));
            echo json_encode($response);

        } catch (Exception $e) {
            error_log("Erreur dans captureImage: " . $e->getMessage());
            http_response_code(400);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    public function deleteImage() {
        if (!isset($_SESSION['user'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Non autorisé']);
            return;
        }

        try {
            $imageId = $_POST['image_id'] ?? null;
            if (!$imageId) {
                throw new Exception('ID d\'image manquant');
            }

            if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
                throw new Exception('Token CSRF invalide');
            }

            $image = $this->imageModel->getImageById($imageId);
            if (!$image) {
                throw new Exception('Image non trouvée');
            }

            if ($image['user_id'] !== $_SESSION['user']['id']) {
                throw new Exception('Vous n\'êtes pas autorisé à supprimer cette image');
            }

            $filePath = $this->uploadDir . $image['image_path'];
            if (file_exists($filePath)) {
                unlink($filePath);
            }

            if ($this->imageModel->deleteImage($imageId, $_SESSION['user']['id'])) {
                echo json_encode(['success' => true]);
            } else {
                throw new Exception('Erreur lors de la suppression de l\'image');
            }

        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    public function getFilters() {
        header('Content-Type: application/json');
        try {
            if (!file_exists($this->filtersDir)) {
                mkdir($this->filtersDir, 0755, true);
            }

            $filters = [];
            $allowedExtensions = ['svg', 'png', 'jpg', 'jpeg'];

            $files = scandir($this->filtersDir);

            foreach ($files as $file) {
                if ($file !== '.' && $file !== '..') {
                    $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                    if (in_array($extension, $allowedExtensions)) {
                        $name = pathinfo($file, PATHINFO_FILENAME);
                        $displayName = ucwords(str_replace('_', ' ', $name));

                        $filters[] = [
                            'id' => count($filters) + 1,
                            'name' => $displayName,
                            'path' => '/filters/' . $file,
                            'thumbnail' => '/filters/' . $file
                        ];
                    }
                }
            }

            if (empty($filters)) {
                error_log("Aucun filtre trouvé dans " . $this->filtersDir);
            } else {
                error_log("Filtres trouvés : " . print_r($filters, true));
            }

            echo json_encode($filters);
        } catch (Exception $e) {
            error_log("Erreur dans getFilters: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    public function getUserImages() {
        header('Content-Type: application/json');
    
        if (!isset($_SESSION['user'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Non autorisé']);
            return;
        }
    
        try {
            error_log("Récupération des 6 dernières images pour l'utilisateur: " . $_SESSION['user']['id']);

            $images = $this->imageModel->getImagesByUserId($_SESSION['user']['id'], 6);
    
            if ($images === false) {
                throw new Exception('Erreur lors de la récupération des images');
            }
    
            error_log("Images trouvées: " . print_r($images, true));
    
            $formattedImages = array_map(function($image) {
                return [
                    'id' => $image['id'],
                    'path' => $image['image_path'],
                    'created_at' => $image['created_at']
                ];
            }, $images);
    
            echo json_encode([
                'success' => true,
                'images' => $formattedImages
            ]);
    
        } catch (Exception $e) {
            error_log("Erreur dans getUserImages: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    private function cleanupTempFiles() {
        try {
            $files = glob($this->uploadDir . 'temp_*');
            $now = time();

            foreach ($files as $file) {
                if ($now - filemtime($file) > self::MAX_TEMP_AGE) {
                    unlink($file);
                    error_log("Fichier temporaire supprimé : $file");
                }
            }
        } catch (Exception $e) {
            error_log("Erreur lors du nettoyage des fichiers temporaires : " . $e->getMessage());
        }
    }

    private function checkUserUploadLimit($userId) {
        $userImagesCount = $this->imageModel->getUserImagesCount($userId);
        if ($userImagesCount >= self::MAX_USER_UPLOADS) {
            throw new Exception("Limite d'upload atteinte. Maximum " . self::MAX_USER_UPLOADS . " images par utilisateur.");
        }
        return true;
    }

    private function validateFileType($file) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file);
        finfo_close($finfo);

        if (!array_key_exists($mimeType, self::ALLOWED_MIME_TYPES)) {
            throw new Exception("Type de fichier non autorisé. Types acceptés : JPG, PNG, GIF");
        }

        return self::ALLOWED_MIME_TYPES[$mimeType];
    }

    private function sanitizeImage($filePath) {
        if (filesize($filePath) > self::MAX_FILE_SIZE) {
            unlink($filePath);
            throw new Exception("Le fichier est trop volumineux. Maximum " . (self::MAX_FILE_SIZE / 1024 / 1024) . "MB");
        }

        if (!getimagesize($filePath)) {
            unlink($filePath);
            throw new Exception("Le fichier n'est pas une image valide");
        }

        $imageInfo = getimagesize($filePath);
        $mimeType = $imageInfo['mime'];

        switch ($mimeType) {
            case 'image/jpeg':
                $image = imagecreatefromjpeg($filePath);
                break;
            case 'image/png':
                $image = imagecreatefrompng($filePath);
                break;
            case 'image/gif':
                $image = imagecreatefromgif($filePath);
                break;
            default:
                unlink($filePath);
                throw new Exception("Format d'image non supporté");
        }

        $newImage = imagecreatetruecolor($imageInfo[0], $imageInfo[1]);
        imagecopy($newImage, $image, 0, 0, 0, 0, $imageInfo[0], $imageInfo[1]);

        $newFilePath = $this->uploadDir . 'clean_' . basename($filePath);
        imagepng($newImage, $newFilePath);

        imagedestroy($image);
        imagedestroy($newImage);
        unlink($filePath);

        return $newFilePath;
    }
}