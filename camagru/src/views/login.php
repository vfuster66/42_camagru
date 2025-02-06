<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Connexion</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Pacifico&display=swap" rel="stylesheet">

</head>
<body>

    <?php include 'partials/header.php'; ?> 

    <main>
        <h2>Connexion</h2>
        <?php if (isset($_SESSION['error'])): ?>
            <p style="color:red;"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></p>
        <?php endif; ?>
        <form action="/login" method="POST">
            <input type="email" name="email" placeholder="Email" required>
            <input type="password" name="password" placeholder="Mot de passe" required>
            <button type="submit">Se connecter</button>
        </form>
        <p><a href="/forgot_password">Mot de passe oublié ?</a></p>
        <p>Pas encore de compte ? <a href="/register">S'inscrire</a></p>
    </main>

    <?php include 'partials/footer.php'; ?>

</body>
</html>
