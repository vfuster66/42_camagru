<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Page non trouvée - Camagru</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <?php include 'partials/header.php'; ?>
    <main>
        <div class="error-container">
            <h1>404</h1>
            <h2>Page non trouvée</h2>
            <p>La page que vous recherchez n'existe pas ou a été déplacée.</p>
            <a href="/" class="button">Retour à l'accueil</a>
        </div>
    </main>
    <?php include 'partials/footer.php'; ?>
</body>
</html>