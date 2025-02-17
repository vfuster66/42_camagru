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
    <title>Galerie - Camagru</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="/assets/css/gallery.css">
</head>
<body>
    <?php include 'partials/header.php'; ?>
    
    <main class="gallery-container">
        <h1>Galerie Photos</h1>

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
                        <div class="interaction-buttons">
                            <button class="like-btn <?php echo isset($_SESSION['user']) && $image['user_liked'] ? 'liked' : ''; ?>" 
                                    data-image-id="<?php echo $image['id']; ?>">
                                ❤️ <span class="likes-count"><?php echo $image['likes_count']; ?></span>
                            </button>
                            <button class="comment-btn" data-image-id="<?php echo $image['id']; ?>">
                                💬 <span class="comments-count"><?php echo $image['comments_count']; ?></span>
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if ($viewData['pagination']['totalPages'] > 1): ?>
            <div class="pagination">
                <?php if ($viewData['pagination']['hasPrevPage']): ?>
                    <a href="?page=<?php echo $viewData['pagination']['currentPage'] - 1; ?>" class="page-link">← Précédent</a>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $viewData['pagination']['totalPages']; $i++): ?>
                    <a href="?page=<?php echo $i; ?>" 
                        class="page-link <?php echo $i === $viewData['pagination']['currentPage'] ? 'active' : ''; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>

                <?php if ($viewData['pagination']['hasNextPage']): ?>
                    <a href="?page=<?php echo $viewData['pagination']['currentPage'] + 1; ?>" class="page-link">Suivant →</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
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

            document.querySelectorAll('.like-btn').forEach(button => {
                button.addEventListener('click', async function(e) {
                    e.preventDefault();

                    if (!isAuthenticated) {
                        window.location.href = '/login';
                        return;
                    }

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

            document.querySelectorAll('.comment-btn').forEach(button => {
                button.addEventListener('click', async function() {
                    if (!isAuthenticated) {
                        window.location.href = '/login';
                        return;
                    }
                    
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
        });
    </script>
</body>
</html>