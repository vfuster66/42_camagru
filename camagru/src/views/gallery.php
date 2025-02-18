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

    <?php

    $ngrok_url = "https://b408-91-151-126-62.ngrok-free.app";
    $currentImagePath = isset($viewData['images'][0]['image_path']) ? $viewData['images'][0]['image_path'] : '';
    $currentUrl = $ngrok_url . "/uploads/" . $currentImagePath;
    ?>

    <meta property="og:title" content="✨ Découvrez ma création sur Camagru !">
    <meta property="og:description" content="Une superbe création partagée via Camagru">
    <meta property="og:image" content="<?php echo $currentUrl; ?>">
    <meta property="og:url" content="<?php echo $currentUrl; ?>">
    <meta property="og:type" content="article">

    <title>Galerie - Camagru</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="icon" type="image/x-icon" href="/assets/images/favicon.ico">
    <link rel="stylesheet" href="/assets/css/gallery.css">
    <script src="https://kit.fontawesome.com/4193962862.js" crossorigin="anonymous"></script>
</head>

<body>
    <?php include 'partials/header.php'; ?>

    <main class="gallery-container">
        <h1>Galerie Photos</h1>

        <div class="gallery-grid" id="gallery-grid">
            <?php foreach ($viewData['images'] as $image): ?>
                <div class="gallery-item">
                    <div class="image-container">
                        <img src="/uploads/<?php echo htmlspecialchars($image['image_path']); ?>"
                            alt="Photo par <?php echo htmlspecialchars($image['username']); ?>"
                            loading="lazy">
                    </div>
                    <div class="image-info">
                        <div class="user-info">
                            <span class="username"><?php echo htmlspecialchars($image['username']); ?></span>
                            <span class="date"><?php echo date('d/m/Y', strtotime($image['created_at'])); ?></span>
                        </div>

                        <div class="interaction-container">
                            <div class="interaction-buttons">
                                <!-- Like -->
                                <button class="like-btn" data-image-id="<?php echo $image['id']; ?>">
                                    <i class="fa-solid fa-heart"></i> <span class="likes-count"><?php echo $image['likes_count']; ?></span>
                                </button>

                                <!-- Commentaire -->
                                <button class="comment-btn" data-image-id="<?php echo $image['id']; ?>">
                                    <i class="fa-regular fa-comment-dots"></i> <span class="comments-count"><?php echo $image['comments_count']; ?></span>
                                </button>

                                <!-- Suppression -->
                                <?php if (isset($_SESSION['user']) && $_SESSION['user']['id'] === $image['user_id']): ?>
                                    <button class="delete-image-btn" data-id="<?php echo $image['id']; ?>">
                                        <i class="fa-regular fa-trash-can"></i>
                                    </button>
                                <?php endif; ?>

                                <!-- Partage sur Facebook -->
                                <button class="share-btn facebook" onclick="shareOnFacebook('<?php echo htmlspecialchars($image['image_path']); ?>')" data-image-path="<?php echo htmlspecialchars($image['image_path']); ?>">
                                    <i class="fa-brands fa-facebook"></i>
                                </button>

                            </div>
                        </div>

                    </div>
                </div>


            <?php endforeach; ?>
        </div>

        <div id="loading" class="loading">Chargement...</div>
    </main>

    <div id="comment-modal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>Commentaires</h2>
            <div class="comments-list"></div>

            <?php if (isset($_SESSION['user'])): ?>
                <form id="comment-form" class="comment-form">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <input type="hidden" name="image_id" id="comment-image-id">
                    <textarea name="content" placeholder="Ajouter un commentaire..." required></textarea>
                    <button type="submit">Commenter</button>
                </form>
            <?php else: ?>
                <p class="login-prompt">Connectez-vous pour commenter</p>
            <?php endif; ?>
        </div>
    </div>

    <?php include 'partials/footer.php'; ?>

    <script>
        const isAuthenticated = <?php echo isset($_SESSION['user']) ? 'true' : 'false'; ?>;
        const csrfToken = '<?php echo $_SESSION['csrf_token']; ?>';

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        document.addEventListener('DOMContentLoaded', function() {
            const galleryContainer = document.getElementById("gallery-grid");
            const loadingIndicator = document.getElementById("loading");
            let page = 2;
            let isLoading = false;
            let hasMoreImages = <?php echo isset($viewData['pagination']['hasMore']) ? json_encode($viewData['pagination']['hasMore']) : 'false'; ?>;

            async function loadImages() {
                if (isLoading || !hasMoreImages) return;
                isLoading = true;
                loadingIndicator.style.display = "block";

                try {
                    const response = await fetch(`/api/gallery/load?page=${page}`);
                    const data = await response.json();

                    if (!data.success) {
                        console.error("Erreur lors du chargement des images");
                        return;
                    }

                    if (data.images.length === 0) {
                        hasMoreImages = false;
                        loadingIndicator.style.display = "none";
                        return;
                    }

                    data.images.forEach(image => {
                        const imageElement = document.createElement("div");
                        imageElement.className = "gallery-item";
                        imageElement.innerHTML = `
                            <div class="image-container">
                                <img src="/uploads/${image.image_path}" alt="Photo par ${image.username}" loading="lazy">
                            </div>
                            <div class="image-info">
                                <div class="user-info">
                                    <span class="username">${image.username}</span>
                                    <span class="date">${new Date(image.created_at).toLocaleDateString()}</span>
                                </div>
                                <div class="interaction-container">
                                    <div class="interaction-buttons">
                                        <button class="like-btn" data-image-id="${image.id}">
                                            <i class="fa-solid fa-heart"></i> <span class="likes-count">${image.likes_count}</span>
                                        </button>
                                        <button class="comment-btn" data-image-id="${image.id}">
                                            <i class="fa-regular fa-comment-dots"></i> <span class="comments-count">${image.comments_count}</span>
                                        </button>
                                        ${isAuthenticated && image.user_id === <?php echo $_SESSION['user']['id'] ?? 'null'; ?> ? 
                                            `<button class="delete-image-btn" data-id="${image.id}">
                                                <i class="fa-regular fa-trash-can"></i>
                                            </button>` : ''}
                                        <button class="share-btn facebook" onclick="shareOnFacebook('${image.image_path}')">
                                            <i class="fa-brands fa-facebook"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        `;
                        galleryContainer.appendChild(imageElement);
                    });

                    page++;
                    hasMoreImages = data.pagination.hasMore;

                    if (!hasMoreImages) {
                        observer.disconnect();
                        loadingIndicator.style.display = "none";
                    }

                    attachEventListeners();

                } catch (error) {
                    console.error("Erreur lors de la récupération des images :", error);
                } finally {
                    isLoading = false;
                    loadingIndicator.style.display = hasMoreImages ? "block" : "none";
                }
            }

            const observer = new IntersectionObserver(entries => {
                if (entries[0].isIntersecting) {
                    loadImages();
                }
            }, {
                root: null,
                rootMargin: "0px",
                threshold: 0.1
            });

            const observerTarget = document.createElement("div");
            observerTarget.id = "observer-target";
            galleryContainer.after(observerTarget);
            observer.observe(observerTarget);

            if (!hasMoreImages) {
                loadingIndicator.style.display = "none";
            }
        });

        if ('IntersectionObserver' in window) {
            const imageObserver = new IntersectionObserver((entries, observer) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const img = entry.target;
                        if (img.dataset.src) {
                            img.src = img.dataset.src;
                            img.classList.remove('lazy');
                            observer.unobserve(img);
                        }
                    }
                });
            });

            document.querySelectorAll('img[data-src]').forEach(img => imageObserver.observe(img));
        }

        document.querySelectorAll('.like-btn, .comment-btn').forEach(button => {
            if (button.disabled) {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    alert('Vous devez être connecté(e) pour interagir avec les photos.');
                });

                button.addEventListener('mouseenter', function() {
                    const tooltip = document.createElement('div');
                    tooltip.className = 'tooltip';
                    tooltip.textContent = 'Connectez-vous pour utiliser cette fonctionnalité.';
                    tooltip.style.position = 'absolute';
                    tooltip.style.background = '#333';
                    tooltip.style.color = '#fff';
                    tooltip.style.padding = '5px';
                    tooltip.style.borderRadius = '5px';
                    tooltip.style.fontSize = '12px';
                    tooltip.style.zIndex = '1000';
                    tooltip.style.whiteSpace = 'nowrap';
                    tooltip.style.top = `${button.getBoundingClientRect().top - 30}px`;
                    tooltip.style.left = `${button.getBoundingClientRect().left}px`;
                    tooltip.id = 'tooltip-message';
                    document.body.appendChild(tooltip);
                });

                button.addEventListener('mouseleave', function() {
                    const tooltip = document.getElementById('tooltip-message');
                    if (tooltip) {
                        tooltip.remove();
                    }
                });
            }
        });

        document.querySelectorAll('.like-btn:not([disabled])').forEach(button => {
            button.addEventListener('click', async function(e) {
                e.preventDefault();
                const imageId = this.dataset.imageId;
                const likesCountElement = this.querySelector('.likes-count');

                try {
                    const formData = new FormData();
                    formData.append('image_id', imageId);
                    formData.append('csrf_token', csrfToken);

                    const response = await fetch('/toggle-like', {
                        method: 'POST',
                        body: formData
                    });

                    if (!response.ok) {
                        throw new Error('Erreur lors du like');
                    }

                    const result = await response.json();
                    likesCountElement.textContent = result.likes_count;

                    if (result.action === 'liked') {
                        this.classList.add('liked');
                    } else {
                        this.classList.remove('liked');
                    }
                } catch (error) {
                    console.error('Erreur:', error);
                    alert('Une erreur est survenue');
                }
            });
        });

        const modal = document.getElementById('comment-modal');
        const commentsList = document.querySelector('.comments-list');
        const commentForm = document.getElementById('comment-form');
        let currentImageId = null;

        document.querySelectorAll('.comment-btn:not([disabled])').forEach(button => {
            button.addEventListener('click', async function() {
                currentImageId = this.dataset.imageId;
                if (commentForm) {
                    document.getElementById('comment-image-id').value = currentImageId;
                }

                try {
                    const response = await fetch(`/comments?image_id=${currentImageId}`);
                    const data = await response.json();

                    if (data.success) {
                        displayComments(data.comments);
                        modal.style.display = 'block';
                    }
                } catch (error) {
                    console.error('Erreur:', error);
                    alert('Erreur lors du chargement des commentaires');
                }
            });
        });

        document.querySelector('.close').addEventListener('click', () => {
            modal.style.display = 'none';
        });

        window.addEventListener('click', (e) => {
            if (e.target === modal) {
                modal.style.display = 'none';
            }
        });

        function displayComments(comments) {
            function decodeHtml(html) {
                const txt = document.createElement('textarea');
                txt.innerHTML = html;
                return txt.value;
            }

            commentsList.innerHTML = comments.length ? '' : '<p>Aucun commentaire pour le moment</p>';

            comments.forEach(comment => {
                const commentElement = document.createElement('div');
                commentElement.className = 'comment';
                commentElement.innerHTML = `
                    <div class="comment-header">
                        <strong>${decodeHtml(comment.username)}</strong>
                        <span>${new Date(comment.created_at).toLocaleDateString()}</span>
                    </div>
                    <div class="comment-content">${decodeHtml(comment.content)}</div>
                    ${comment.user_id === <?php echo isset($_SESSION['user']) ? $_SESSION['user']['id'] : 'null'; ?> ? 
                        `<button class="delete-comment" data-id="${comment.id}">Supprimer</button>` : 
                        ''}
                `;
                commentsList.appendChild(commentElement);
            });
        }

        if (commentForm) {
            commentForm.addEventListener('submit', async function(e) {
                e.preventDefault();

                const content = this.querySelector('textarea').value.trim();
                if (!content) return;

                try {
                    const formData = new FormData(this);
                    const response = await fetch('/add-comment', {
                        method: 'POST',
                        body: formData
                    });

                    const data = await response.json();

                    if (data.success) {
                        displayComments(data.comments);
                        this.reset();
                        const commentBtn = document.querySelector(`.comment-btn[data-image-id="${currentImageId}"]`);
                        const countSpan = commentBtn.querySelector('.comments-count');
                        countSpan.textContent = data.comments.length;
                    }
                } catch (error) {
                    console.error('Erreur:', error);
                    alert('Erreur lors de l\'ajout du commentaire');
                }
            });
        }

        commentsList.addEventListener('click', async function(e) {
            if (e.target.classList.contains('delete-comment')) {
                if (!confirm('Voulez-vous vraiment supprimer ce commentaire ?')) return;

                const commentId = e.target.dataset.id;
                try {
                    const formData = new FormData();
                    formData.append('comment_id', commentId);
                    formData.append('csrf_token', csrfToken);

                    const response = await fetch('/delete-comment', {
                        method: 'POST',
                        body: formData
                    });

                    if (response.ok) {
                        const commentsResponse = await fetch(`/comments?image_id=${currentImageId}`);
                        const data = await commentsResponse.json();
                        displayComments(data.comments);

                        const commentBtn = document.querySelector(`.comment-btn[data-image-id="${currentImageId}"]`);
                        const countSpan = commentBtn.querySelector('.comments-count');
                        countSpan.textContent = data.comments.length;
                    }
                } catch (error) {
                    console.error('Erreur:', error);
                    alert('Erreur lors de la suppression du commentaire');
                }
            }
        });

        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.delete-image-btn').forEach(button => {
                button.addEventListener('click', async function() {
                    if (!confirm('Voulez-vous vraiment supprimer cette image ?')) return;

                    const imageId = this.dataset.imageId;
                    const imageElement = this.closest('.gallery-item');

                    try {
                        const formData = new FormData();
                        formData.append('image_id', imageId);
                        formData.append('csrf_token', '<?php echo $_SESSION["csrf_token"]; ?>');

                        const response = await fetch('/delete-image', {
                            method: 'POST',
                            body: formData
                        });

                        const result = await response.json();

                        if (result.success) {
                            imageElement.remove();
                        } else {
                            alert('Erreur lors de la suppression de l\'image.');
                        }
                    } catch (error) {
                        console.error('Erreur:', error);
                        alert('Une erreur est survenue.');
                    }
                });
            });
        });

        const ngrokUrl = "https://b408-91-151-126-62.ngrok-free.app";

        function constructShareUrl(imagePath) {
            return `${ngrokUrl}/uploads/${imagePath}`;
        }

        function shareOnFacebook(imagePath) {
            const shareUrl = constructShareUrl(imagePath);
            window.open(`https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(shareUrl)}`, '_blank');
        }

        function attachEventListeners() {
            document.querySelectorAll('.delete-image-btn').forEach(button => {
                button.removeEventListener('click', handleDeleteImage);
                button.addEventListener('click', handleDeleteImage);
            });

            document.querySelectorAll('.share-btn.facebook').forEach(button => {
                button.removeEventListener('click', handleFacebookShare);
                button.addEventListener('click', handleFacebookShare);
            });
        }

        async function handleDeleteImage(event) {
            if (!confirm('Voulez-vous vraiment supprimer cette image ?')) return;

            const imageId = event.currentTarget.dataset.id;
            const imageElement = event.currentTarget.closest('.gallery-item');

            try {
                const formData = new FormData();
                formData.append('image_id', imageId);
                formData.append('csrf_token', csrfToken);

                const response = await fetch('/delete-image', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    imageElement.remove();
                } else {
                    alert('Erreur lors de la suppression de l\'image.');
                }
            } catch (error) {
                console.error('Erreur:', error);
                alert('Une erreur est survenue.');
            }
        }

        function handleFacebookShare(event) {
            const imagePath = event.currentTarget.dataset.imagePath;
            shareOnFacebook(imagePath);
        }
    </script>

</body>

</html>