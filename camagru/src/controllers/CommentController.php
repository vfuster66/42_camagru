<?php

require_once __DIR__ . '/../models/Comment.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

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
        try {
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = $_ENV['GMAIL_ADDRESS'];
            $mail->Password = $_ENV['GMAIL_PASSWORD'];
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;
            $mail->CharSet = 'UTF-8';

            $mail->setFrom('no-reply@camagru.com', 'Camagru');
            $mail->addAddress($to, $recipientName);

            $mail->isHTML(true);
            $mail->Subject = "Nouveau commentaire sur votre photo";
            $mail->Body = "
                <h2>Bonjour $recipientName !</h2>
                <p>$commenterName a commenté votre photo sur Camagru.</p>
                <p>Connectez-vous pour voir le commentaire !</p>
            ";

            $mail->AltBody = "Bonjour $recipientName ! $commenterName a commenté votre photo sur Camagru.";

            $mail->send();
        } catch (Exception $e) {
            error_log("Erreur d'envoi d'email: " . $e->getMessage());
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