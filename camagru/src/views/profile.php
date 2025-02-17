<?php
session_start();

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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon Profil - Camagru</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="/assets/css/profile.css">
</head>
<body>
    <?php include 'partials/header.php'; ?>

    <main class="profile-container">
        <h1>Mon Profil</h1>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="success-message">
                <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="error-message">
                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <div class="profile-sections">

            <section class="profile-section">
                <h2>Informations du compte</h2>
                <div class="info-item">
                    <span class="label">Nom d'utilisateur :</span>
                    <span class="value"><?php echo htmlspecialchars($user['username']); ?></span>
                    <button class="edit-btn" onclick="toggleForm('username-form')">Modifier</button>
                </div>
                <form id="username-form" class="edit-form" style="display: none;">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <input type="text" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                    <button type="submit">Enregistrer</button>
                    <button type="button" onclick="toggleForm('username-form')">Annuler</button>
                </form>

                <div class="info-item">
                    <span class="label">Email :</span>
                    <span class="value"><?php echo htmlspecialchars($user['email']); ?></span>
                    <button class="edit-btn" onclick="toggleForm('email-form')">Modifier</button>
                </div>
                <form id="email-form" class="edit-form" style="display: none;">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                    <button type="submit">Enregistrer</button>
                    <button type="button" onclick="toggleForm('email-form')">Annuler</button>
                </form>
            </section>

            <section class="profile-section">
                <h2>Sécurité</h2>
                <button class="edit-btn" onclick="toggleForm('password-form')">Changer le mot de passe</button>
                <form id="password-form" class="edit-form" style="display: none;">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <input type="password" name="current_password" placeholder="Mot de passe actuel" required>
                    <input type="password" name="new_password" placeholder="Nouveau mot de passe" required>
                    <input type="password" name="confirm_password" placeholder="Confirmer le mot de passe" required>
                    <div class="password-requirements">
                        <small>Le mot de passe doit contenir au moins :</small>
                        <ul>
                            <li>8 caractères</li>
                            <li>Une majuscule</li>
                            <li>Un chiffre</li>
                            <li>Un caractère spécial</li>
                        </ul>
                    </div>
                    <button type="submit">Enregistrer</button>
                    <button type="button" onclick="toggleForm('password-form')">Annuler</button>
                </form>
            </section>

            <section class="profile-section">
                <h2>Préférences</h2>
                <div class="notification-settings">
                    <label class="switch-label">
                        <span>Notifications par email</span>
                        <label class="switch">
                            <input type="checkbox" id="email-notifications" <?php echo $user['email_notifications'] ? 'checked' : ''; ?>>
                            <span class="slider round"></span>
                        </label>
                    </label>
                    <small>Recevoir des notifications quand quelqu'un commente vos photos</small>
                </div>
            </section>

            <section class="profile-section">
                <h2>Supprimer mon compte</h2>
                <p class="warning-text">Cette action est irréversible.</p>
                <button id="delete-account-btn" class="delete-btn">Supprimer mon compte</button>
            </section>
        </div>
    </main>

    <?php include 'partials/footer.php'; ?>

    <script>
        function toggleForm(formId) {
            const form = document.getElementById(formId);
            form.style.display = form.style.display === 'none' ? 'block' : 'none';
        }

        document.querySelectorAll('.edit-form').forEach(form => {
            form.addEventListener('submit', async function(e) {
                e.preventDefault();
                const formId = this.id;
                const formData = new FormData(this);

                const routes = {
                    'username-form': '/update-username',
                    'email-form': '/update-email',
                    'password-form': '/update-password'
                };

                try {
                    const response = await fetch(routes[formId], {
                        method: 'POST',
                        body: formData
                    });

                    const result = await response.json();

                    if (result.success) {
                        location.reload();
                    } else {
                        alert(result.error || 'Une erreur est survenue');
                    }
                } catch (error) {
                    console.error('Erreur:', error);
                    alert('Une erreur est survenue');
                }
            });
        });

        document.getElementById('email-notifications').addEventListener('change', async function() {
            const formData = new FormData();
            formData.append('csrf_token', '<?php echo $_SESSION['csrf_token']; ?>');
            formData.append('email_notifications', this.checked ? 1 : 0);

            try {
                const response = await fetch('/update-notifications', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (!result.success) {
                    this.checked = !this.checked;
                    alert(result.error || 'Une erreur est survenue');
                }
            } catch (error) {
                console.error('Erreur:', error);
                this.checked = !this.checked;
                alert('Une erreur est survenue');
            }
        });

        document.getElementById('delete-account-btn').addEventListener('click', async function() {
            if (!confirm("Êtes-vous sûr de vouloir supprimer votre compte ? Cette action est irréversible.")) {
                return;
            }

            const formData = new FormData();
            formData.append('csrf_token', '<?php echo $_SESSION['csrf_token']; ?>');

            try {
                const response = await fetch('/delete-account', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    alert(result.message);
                    window.location.href = "/login";
                } else {
                    alert(result.error || "Une erreur est survenue.");
                }
            } catch (error) {
                console.error("Erreur:", error);
                alert("Une erreur est survenue.");
            }
        });
    </script>
</body>
</html>
