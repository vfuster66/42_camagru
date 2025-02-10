<?php

require_once __DIR__ . '/../models/Image.php';

class ImageController {
    private $uploadDir;
    private $filtersDir;
    private $imageModel;
    private $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
    private $maxFileSize = 2000000;

    public function __construct() {
        // Chemin absolu pour les uploads
        $this->uploadDir = dirname(__DIR__, 2) . '/public/uploads/';
        // Chemin absolu pour les filtres
        $this->filtersDir = dirname(__DIR__, 2) . '/public/filters/';
        $this->imageModel = new Image();
    }

    /**
     * Affiche la page d'édition d'image
     */
    public function showEditor() {
        if (!isset($_SESSION['user'])) {
            $_SESSION['error'] = "Vous devez être connecté pour accéder à l'éditeur.";
            header('Location: /login');
            exit;
        }
        require_once __DIR__ . '/../views/editor.php';
    }

    /**
     * Traite l'upload d'une image
     */
    public function uploadImage() {
        if (!isset($_SESSION['user'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Non autorisé']);
            return;
        }
    
        try {
            error_log("Début de uploadImage");
            
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
    
            // Vérification du dossier uploads
            if (!file_exists($this->uploadDir)) {
                if (!mkdir($this->uploadDir, 0777, true)) {
                    throw new Exception('Impossible de créer le dossier uploads');
                }
            }
    
            if (!is_writable($this->uploadDir)) {
                throw new Exception('Le dossier uploads n\'est pas accessible en écriture');
            }
    
            $fileName = uniqid() . '_' . time() . '.png';
            $filePath = $this->uploadDir . $fileName;
    
            error_log("Sauvegarde de l'image vers: $filePath");
            
            if (file_put_contents($filePath, $imageDecoded) === false) {
                throw new Exception('Erreur lors de la sauvegarde du fichier');
            }
    
            // Vérification que le fichier a bien été créé
            if (!file_exists($filePath)) {
                throw new Exception('Le fichier n\'a pas été créé');
            }
    
            error_log("Fichier sauvegardé avec succès, tentative de sauvegarde en BDD...");
            
            // Sauvegarde en base de données
            if ($this->imageModel->saveImage($_SESSION['user']['id'], $fileName)) {
                error_log("Sauvegarde en BDD réussie !");
                echo json_encode(['success' => true, 'path' => $fileName]);
            } else {
                unlink($filePath); // Supprime le fichier si erreur BDD
                throw new Exception('Erreur lors de la sauvegarde en base de données');
            }
    
        } catch (Exception $e) {
            error_log("Erreur dans uploadImage: " . $e->getMessage());
            http_response_code(400);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    /**
     * Capture et sauvegarde une image depuis la webcam
     */
    public function captureImage() {
        header('Content-Type: application/json');
        error_log("Début de captureImage");
    
        if (!isset($_SESSION['user'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Non autorisé']);
            return;
        }
    
        try {
            // Vérifions que tout le dossier existe avec les bonnes permissions
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
    
            // Vérification des données reçues
            error_log("POST reçu: " . print_r($_POST, true));
    
            if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
                throw new Exception('Token CSRF invalide');
            }
    
            if (!isset($_POST['image_data'])) {
                throw new Exception('Données d\'image manquantes');
            }
    
            $imageData = $_POST['image_data'];
            error_log("Longueur des données image: " . strlen($imageData));
    
            // Vérification du format des données
            if (strpos($imageData, 'data:image/png;base64,') !== 0) {
                throw new Exception('Format de données incorrect');
            }
    
            // Nettoyage et décodage
            $imageData = str_replace('data:image/png;base64,', '', $imageData);
            $imageData = str_replace(' ', '+', $imageData);
            $imageDecoded = base64_decode($imageData);
    
            if ($imageDecoded === false) {
                throw new Exception('Échec du décodage base64');
            }
    
            // Sauvegarde
            $fileName = uniqid() . '_' . time() . '.png';
            $filePath = $this->uploadDir . $fileName;
    
            error_log("Tentative de sauvegarde vers: " . $filePath);
    
            if (file_put_contents($filePath, $imageDecoded) === false) {
                error_log("Erreur lors de l'écriture du fichier. Permissions actuelles: " . substr(sprintf('%o', fileperms(dirname($filePath))), -4));
                throw new Exception('Erreur lors de la sauvegarde du fichier');
            }
    
            // Vérification de l'image créée
            if (!file_exists($filePath)) {
                throw new Exception('Le fichier n\'a pas été créé');
            }
    
            error_log("Fichier créé avec succès: " . $filePath);
            error_log("Taille du fichier: " . filesize($filePath));
    
            // Sauvegarde en base de données
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

    /**
     * Supprime une image
     */
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

            // Vérification du token CSRF
            if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
                throw new Exception('Token CSRF invalide');
            }

            // Récupération de l'image
            $image = $this->imageModel->getImageById($imageId);
            if (!$image) {
                throw new Exception('Image non trouvée');
            }

            // Vérification que l'utilisateur est le propriétaire
            if ($image['user_id'] !== $_SESSION['user']['id']) {
                throw new Exception('Vous n\'êtes pas autorisé à supprimer cette image');
            }

            // Suppression du fichier
            $filePath = $this->uploadDir . $image['image_path'];
            if (file_exists($filePath)) {
                unlink($filePath);
            }

            // Suppression en base de données
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
            // Créer le dossier s'il n'existe pas
            if (!file_exists($this->filtersDir)) {
                mkdir($this->filtersDir, 0755, true);
            }
    
            $filters = [];
            $allowedExtensions = ['svg', 'png', 'jpg', 'jpeg'];
            
            // Lire le contenu du dossier filters
            $files = scandir($this->filtersDir);
            
            foreach ($files as $file) {
                // Ignorer . et .. et vérifier l'extension
                if ($file !== '.' && $file !== '..') {
                    $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                    if (in_array($extension, $allowedExtensions)) {
                        // Utiliser le nom du fichier (sans extension) comme nom du filtre
                        $name = pathinfo($file, PATHINFO_FILENAME);
                        // Formater le nom pour l'affichage (remplacer les _ par des espaces et capitaliser)
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
            error_log("Récupération des images pour l'utilisateur: " . $_SESSION['user']['id']);
            
            // Récupérer les images de l'utilisateur depuis la base de données
            $images = $this->imageModel->getImagesByUserId($_SESSION['user']['id']);
            
            if ($images === false) {
                throw new Exception('Erreur lors de la récupération des images');
            }
            
            error_log("Images trouvées: " . print_r($images, true));
            
            // Formater les données pour le front-end
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
}