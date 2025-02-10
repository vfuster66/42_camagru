<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Vérification du compte - Camagru</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="/assets/css/auth.css">
</head>
<body>
    <?php include 'partials/header.php'; ?>
    <main>
        <div class="auth-container">
            <h2>Vérification du compte</h2>
            
            <?php if (isset($_SESSION['error'])): ?>
                <div class="error-message">
                    <?php 
                        echo $_SESSION['error'];
                        unset($_SESSION['error']);
                    ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['success'])): ?>
                <div class="success-message">
                    <?php 
                        echo $_SESSION['success'];
                        unset($_SESSION['success']);
                    ?>
                </div>
                <p>
                    <a href="/login" class="button">Se connecter</a>
                </p>
            <?php endif; ?>

            <?php if (!isset($_SESSION['success']) && !isset($_SESSION['error'])): ?>
                <div class="loading-message">
                    <p>Vérification de votre compte en cours...</p>
                </div>
            <?php endif; ?>
        </div>
    </main>
    <?php include 'partials/footer.php'; ?>

    <script>
        // Si on est sur la page de chargement, rediriger vers la vérification
        if (document.querySelector('.loading-message')) {
            const urlParams = new URLSearchParams(window.location.search);
            const token = urlParams.get('token');
            if (token) {
                window.location.href = `/verify_email_action?token=${token}`;
            }
        }
    </script>
</body>
</html>