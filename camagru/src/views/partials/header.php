<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<header>
    <nav>
        <div class="logo">
            <h1><a href="/">Camagru</a></h1>
        </div>
        <ul class="nav-links">
            <li><a href="/">Accueil</a></li>
            <li><a href="/gallery">Galerie</a></li>
            <?php if (isset($_SESSION['user'])): ?>
                <li><a href="/profile">Mon Profil</a></li>
                <li><a href="/logout" class="logout-btn">Déconnexion</a></li>
            <?php else: ?>
                <li><a href="/login" class="login-btn">Connexion</a></li>
                <li><a href="/register" class="register-btn">Inscription</a></li>
            <?php endif; ?>
        </ul>
    </nav>
</header>
