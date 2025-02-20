<?php

require_once __DIR__ . '/../models/Comment.php';

class CommentController {
    private $commentModel;

    public function __construct() {
        $this->commentModel = new Comment();
    }

    public function getComments() {
        header('Content-Type: application/json; charset=utf-8');

        if (!isset($_GET['image_id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'ID d\'image manquant']);
            return;
        }

        $imageId = filter_var($_GET['image_id'], FILTER_VALIDATE_INT);
        $comments = $this->commentModel->getComments($imageId);
        
        echo json_encode(['success' => true, 'comments' => $comments]);
    }

    public function addComment() {
        header('Content-Type: application/json; charset=utf-8');

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
        $content = trim(filter_input(INPUT_POST, 'content', FILTER_SANITIZE_STRING));

        if (!$imageId || empty($content)) {
            http_response_code(400);
            echo json_encode(['error' => 'Données invalides']);
            return;
        }

        $result = $this->commentModel->addComment($imageId, $_SESSION['user']['id'], $content);

        if (!$result) {
            http_response_code(500);
            echo json_encode(['error' => 'Erreur lors de l\'ajout du commentaire']);
            return;
        }

        // Vérifier si l'utilisateur propriétaire de l'image veut recevoir des notifications
        if ($result['image_owner']['email_notifications'] && 
            $result['image_owner']['user_id'] !== $_SESSION['user']['id']) {
            $this->sendNotificationEmail(
                $result['image_owner']['email'],
                $result['image_owner']['username'],
                $_SESSION['user']['username']
            );
        }

        $comments = $this->commentModel->getComments($imageId);
        echo json_encode([
            'success' => true,
            'comments' => $comments
        ]);
    }

    private function sendNotificationEmail($to, $recipientName, $commenterName) {
        // Sujet de l'email
        $subject = "Nouveau commentaire sur votre photo";

        // Message HTML
        $message = "
        <html>
        <head>
            <title>Nouveau commentaire sur votre photo</title>
        </head>
        <body>
            <h2>Bonjour $recipientName !</h2>
            <p>$commenterName a commenté votre photo sur Camagru.</p>
            <p>Connectez-vous pour voir le commentaire !</p>
            <p><a href='http://localhost:8080/gallery'>Voir la photo</a></p>
        </body>
        </html>
        ";

        // En-têtes pour un email HTML
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $headers .= "From: no-reply@camagru.com" . "\r\n";
        $headers .= "Reply-To: no-reply@camagru.com" . "\r\n";

        // Envoi de l'email
        if (mail($to, $subject, $message, $headers)) {
            error_log("✅ Notification email envoyée à $to");
        } else {
            error_log("❌ Erreur lors de l'envoi de l'email à $to");
        }
    }

    public function deleteComment() {
        header('Content-Type: application/json');

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

        $commentId = filter_input(INPUT_POST, 'comment_id', FILTER_VALIDATE_INT);
        
        if (!$commentId) {
            http_response_code(400);
            echo json_encode(['error' => 'ID de commentaire invalide']);
            return;
        }

        $success = $this->commentModel->deleteComment($commentId, $_SESSION['user']['id']);
        
        if ($success) {
            echo json_encode(['success' => true]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Erreur lors de la suppression']);
        }
    }
}
?>