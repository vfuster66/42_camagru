<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user'])) {
    header("Location: /login");
    exit;
}

$user = $_SESSION['user'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Mon Profil - Camagru</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>

    <?php include 'partials/header.php'; ?>

    <main>
        <h1>Bienvenue, <?php echo htmlspecialchars($user['username']); ?> !</h1>
        <p>Voici votre espace personnel.</p>
        <p><a href="/upload">Ajouter une photo</a></p>
    </main>

    <?php include 'partials/footer.php'; ?>

</body>
</html>
