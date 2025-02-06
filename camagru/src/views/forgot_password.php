<?php session_start(); ?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Mot de passe oublié</title>
</head>
<body>

    <?php include 'partials/header.php'; ?>

    <main>
    <h2>Mot de passe oublié</h2>
        <?php if (isset($_SESSION['error'])): ?>
            <p style="color:red;"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></p>
        <?php endif; ?>
        <?php if (isset($_SESSION['success'])): ?>
            <p style="color:green;"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></p>
        <?php endif; ?>
        <form action="/forgot_password" method="POST">
            <input type="email" name="email" placeholder="Entrez votre email" required>
            <button type="submit">Réinitialiser le mot de passe</button>
        </form>
        <p><a href="/login">Retour à la connexion</a></p>
    </main>

    <?php include 'partials/footer.php'; ?>
</body>
</html>
