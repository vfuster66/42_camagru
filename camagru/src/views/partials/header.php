<header>
    <nav>
        <div class="logo">
            <h1><a href="/">Camagru</a></h1>
        </div>
        
        <div class="burger-menu">
            <span></span>
            <span></span>
            <span></span>
        </div>

        <ul class="nav-links">
            <li><a href="/">Accueil</a></li>
            <li><a href="/gallery">Galerie</a></li>
            <?php if (isset($_SESSION['user'])): ?>
                <li><a href="/editor">Éditeur</a></li>
                <li><a href="/profile">Mon Profil</a></li>
                <li><a href="/logout" class="logout-btn">Déconnexion</a></li>
            <?php else: ?>
                <li><a href="/login" class="login-btn">Connexion</a></li>
                <li><a href="/register" class="register-btn">Inscription</a></li>
            <?php endif; ?>
        </ul>
    </nav>
</header>

<script>
document.querySelector('.burger-menu').addEventListener('click', function() {
    this.classList.toggle('active');
    document.querySelector('.nav-links').classList.toggle('active');
});

document.querySelectorAll('.nav-links a').forEach(link => {
    link.addEventListener('click', () => {
        document.querySelector('.burger-menu').classList.remove('active');
        document.querySelector('.nav-links').classList.remove('active');
    });
});
</script>