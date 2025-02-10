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
    <title>500 - Erreur serveur - Camagru</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <?php include 'partials/header.php'; ?>
    <main>
        <div class="error-container">
            <h1>500</h1>
            <h2>Erreur serveur</h2>
            <p>Une erreur inattendue s'est produite. Veuillez réessayer plus tard.</p>
            <a href="/" class="button">Retour à l'accueil</a>
        </div>
    </main>
    <?php include 'partials/footer.php'; ?>
</body>
</html>