<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Accueil - Camagru</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Pacifico&display=swap" rel="stylesheet">
    <link rel="icon" type="image/x-icon" href="/assets/images/favicon.ico">

</head>
<body>

    <?php include 'partials/header.php'; ?>

    <main>
        <h1>Bienvenue sur Camagru</h1>
        <p>Éditez vos photos, superposez des images et partagez-les avec vos amis !</p>
    </main>

    <?php include 'partials/footer.php'; ?>

</body>
</html>
