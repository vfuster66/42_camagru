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
    <title>Connexion - Camagru</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Pacifico&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/auth.css">
</head>

<body>
    <?php include 'partials/header.php'; ?>
    <main>
        <div class="auth-container">
            <h2>Connexion</h2>

            <?php if (isset($_SESSION['success'])): ?>
                <div class="success-message verification-notice">
                    <?php echo $_SESSION['success'];
                    unset($_SESSION['success']); ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="error-message">
                    <?php echo $_SESSION['error'];
                    unset($_SESSION['error']); ?>
                </div>
            <?php endif; ?>

            <form action="/login_action" method="POST" class="auth-form">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email"
                        id="email"
                        name="email"
                        placeholder="Votre email"
                        required
                        value="<?php echo isset($_SESSION['old_email']) ? htmlspecialchars($_SESSION['old_email']) : ''; ?>">
                </div>

                <div class="form-group">
                    <label for="password">Mot de passe</label>
                    <input type="password"
                        id="password"
                        name="password"
                        placeholder="Votre mot de passe"
                        required>
                </div>

                <button type="submit" class="button-primary">Se connecter</button>
            </form>

            <div class="auth-links">
                <a href="/forgot_password" class="forgot-password">Mot de passe oublié ?</a>
                <p class="register-link">
                    Pas encore de compte ?
                    <a href="/register">S'inscrire</a>
                </p>
            </div>
        </div>
    </main>
    <?php include 'partials/footer.php'; ?>
</body>

</html>