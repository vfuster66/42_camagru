<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if (!isset($_SESSION['user'])) {
    header('Location: /login');
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Éditeur Photo - Camagru</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="/assets/css/editor.css">
</head>

<body>
    <?php include 'partials/header.php'; ?>

    <main class="editor-container">
        <div class="editor-main">
            <div class="capture-container">
                <h3>Prendre une photo</h3>
                <!-- Zone de la webcam -->
                <div class="webcam-container">
                    <video id="webcam" autoplay playsinline></video>
                    <div id="overlay-container"></div>
                    <canvas id="canvas" style="display: none;"></canvas>
                </div>
                <button id="capture-btn" class="btn" disabled>Prendre une photo</button>
            </div>

            <!-- Zone des filtres/superpositions -->
            <div class="filters-container">
                <h3>Superpositions disponibles</h3>
                <div class="filters-grid">
                    <!-- Les filtres seront chargés dynamiquement via JavaScript -->
                </div>
            </div>

            <!-- Zone d'upload alternative -->
            <div class="upload-container">
                <h3>Ou uploadez une image</h3>
                <form id="upload-form" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

                    <!-- Bouton et nom du fichier sur la même ligne -->
                    <label for="image-upload" class="upload-wrapper">
                        <span id="file-name">Aucun fichier sélectionné</span>
                        <span class="upload-icon">📁</span>
                    </label>
                    <input type="file" name="image" id="image-upload" accept="image/*">
                    
                    <!-- Prévisualisation -->
                    <div id="preview-container">
                        <img id="upload-preview">
                    </div>

                    <!-- Bouton d'upload -->
                    <button type="submit" class="btn">Uploader</button>
                </form>
            </div>

            <!-- Sidebar avec les miniatures -->
            <div class="editor-sidebar">
                <h3>Vos photos récentes</h3>
                <div id="thumbnails-container">
                    <!-- Miniatures dynamiques -->
                </div>
            </div>

        </div>
    </main>

    <?php include 'partials/footer.php'; ?>

    <script>
        const WEBCAM_WIDTH = 800;
        const WEBCAM_HEIGHT = 600;

        document.addEventListener('DOMContentLoaded', function() {
            // Configuration de la webcam
            const video = document.getElementById('webcam');
            const canvas = document.getElementById('canvas');
            const captureBtn = document.getElementById('capture-btn');
            const context = canvas.getContext('2d');
            let selectedFilter = null;

            // Initialisation de la webcam
            async function initWebcam() {
                try {
                    const stream = await navigator.mediaDevices.getUserMedia({
                        video: {
                            width: {
                                ideal: WEBCAM_WIDTH
                            },
                            height: {
                                ideal: WEBCAM_HEIGHT
                            }
                        }
                    });
                    video.srcObject = stream;

                    // Définir les dimensions exactes de la vidéo
                    video.width = WEBCAM_WIDTH;
                    video.height = WEBCAM_HEIGHT;

                    // Définir aussi les dimensions du canvas
                    canvas.width = WEBCAM_WIDTH;
                    canvas.height = WEBCAM_HEIGHT;

                    captureBtn.disabled = false;
                } catch (err) {
                    console.error('Erreur webcam:', err);
                    document.querySelector('.webcam-container').innerHTML = `
                        <div class="error-message">
                            <p>Impossible d'accéder à la webcam...</p>
                        </div>
                    `;
                }
            }

            // Chargement des filtres disponibles
            async function loadFilters() {
                try {
                    const response = await fetch('/api/filters');
                    const filters = await response.json();
                    const filtersGrid = document.querySelector('.filters-grid');

                    filters.forEach(filter => {
                        const filterItem = document.createElement('div');
                        filterItem.className = 'filter-item';
                        filterItem.innerHTML = `
                            <img src="${filter.thumbnail}" alt="${filter.name}" data-filter="${filter.id}">
                            <p>${filter.name}</p>
                        `;
                        filterItem.addEventListener('click', () => selectFilter(filter));
                        filtersGrid.appendChild(filterItem);
                    });
                } catch (err) {
                    console.error('Erreur chargement filtres:', err);
                }
            }

            // Sélection d'un filtre
            function selectFilter(filter) {
                selectedFilter = filter;

                // Mise à jour de l'interface
                document.querySelectorAll('.filter-item').forEach(item => {
                    item.classList.remove('active');
                });

                const filterElement = document.querySelector(`[data-filter="${filter.id}"]`);
                if (filterElement) {
                    filterElement.parentElement.classList.add('active');
                }

                // Créer et configurer l'overlay
                const overlay = document.createElement('img');
                overlay.src = filter.path;
                overlay.style.position = 'absolute';
                overlay.style.top = '0';
                overlay.style.left = '0';
                overlay.style.width = '100%';
                overlay.style.height = '100%';
                overlay.style.pointerEvents = 'none';
                overlay.style.objectFit = 'cover'; // Important pour le cadrage

                const overlayContainer = document.getElementById('overlay-container');
                overlayContainer.innerHTML = '';
                overlayContainer.appendChild(overlay);

                // Activer le bouton de capture
                document.getElementById('capture-btn').disabled = false;
            }

            captureBtn.addEventListener('click', async () => captureImage());

            async function captureImage() {
                if (!selectedFilter) {
                    showMessage('Veuillez sélectionner un filtre', 'error');
                    return;
                }

                try {
                    // Créer un canvas temporaire pour la composition finale
                    const tempCanvas = document.createElement('canvas');
                    const tempCtx = tempCanvas.getContext('2d');

                    // Définir des dimensions fixes pour la capture
                    const CAPTURE_WIDTH = 800;
                    const CAPTURE_HEIGHT = 600;

                    tempCanvas.width = CAPTURE_WIDTH;
                    tempCanvas.height = CAPTURE_HEIGHT;

                    // Dessiner la vidéo sur le canvas en respectant les dimensions
                    tempCtx.drawImage(video, 0, 0, CAPTURE_WIDTH, CAPTURE_HEIGHT);

                    // Préparer l'image du filtre
                    const filterImg = new Image();
                    filterImg.crossOrigin = "anonymous"; // Permet l'utilisation de fichiers externes

                    // Attendre que le filtre soit chargé avant de continuer
                    await new Promise((resolve, reject) => {
                        filterImg.onload = () => {
                            // Dessiner le filtre par dessus avec les mêmes dimensions
                            tempCtx.drawImage(
                                filterImg,
                                0, 0,
                                CAPTURE_WIDTH, CAPTURE_HEIGHT
                            );
                            resolve();
                        };
                        filterImg.onerror = (e) => {
                            console.error('Erreur de chargement du filtre:', e);
                            reject(new Error('Impossible de charger le filtre'));
                        };
                        filterImg.src = selectedFilter.path;
                    });

                    // Convertir le canvas en base64
                    const imageData = tempCanvas.toDataURL('image/png');

                    // Préparer les données pour l'envoi
                    const formData = new FormData();
                    formData.append('image_data', imageData);
                    formData.append('filter_id', selectedFilter.id);
                    formData.append('csrf_token', document.querySelector('[name="csrf_token"]').value);

                    console.log('Envoi de la photo au serveur...');

                    // Envoyer l'image au serveur
                    const response = await fetch('/capture-image', {
                        method: 'POST',
                        body: formData
                    });

                    if (!response.ok) {
                        const errorText = await response.text();
                        console.error('Réponse serveur:', errorText);
                        throw new Error(`Erreur serveur: ${response.status}`);
                    }

                    const result = await response.json();
                    console.log('Réponse du serveur:', result);

                    if (result.success) {
                        await loadThumbnails(); // Recharger les miniatures
                        showMessage('Photo capturée avec succès !', 'success');
                    } else {
                        throw new Error(result.error || 'Erreur lors de la sauvegarde');
                    }

                } catch (err) {
                    console.error('Erreur détaillée:', err);
                    showMessage(`Erreur lors de la capture : ${err.message}`, 'error');
                }
            }

            // Gestion de l'upload d'image
            const uploadForm = document.getElementById('upload-form');
            uploadForm.addEventListener('submit', async (e) => {
                e.preventDefault();

                if (!selectedFilter) {
                    showMessage('Veuillez sélectionner une superposition', 'error');
                    return;
                }

                const file = document.getElementById('image-upload').files[0];
                if (!file) {
                    showMessage('Veuillez sélectionner une image', 'error');
                    return;
                }

                try {
                    const tempCanvas = document.createElement('canvas');
                    const tempCtx = tempCanvas.getContext('2d');

                    const uploadedImg = new Image();
                    uploadedImg.src = URL.createObjectURL(file);

                    await new Promise((resolve) => {
                        uploadedImg.onload = resolve;
                    });

                    tempCanvas.width = WEBCAM_WIDTH;
                    tempCanvas.height = WEBCAM_HEIGHT;

                    tempCtx.drawImage(uploadedImg, 0, 0, WEBCAM_WIDTH, WEBCAM_HEIGHT);

                    const filterImg = new Image();
                    filterImg.crossOrigin = "anonymous";

                    await new Promise((resolve, reject) => {
                        filterImg.onload = () => {
                            tempCtx.drawImage(filterImg, 0, 0, WEBCAM_WIDTH, WEBCAM_HEIGHT);
                            resolve();
                        };
                        filterImg.onerror = reject;
                        filterImg.src = selectedFilter.path;
                    });

                    const imageData = tempCanvas.toDataURL('image/png');

                    const formData = new FormData();
                    formData.append('image_data', imageData);
                    formData.append('filter_id', selectedFilter.id);
                    formData.append('csrf_token', document.querySelector('[name="csrf_token"]').value);

                    const response = await fetch('/upload-image', {
                        method: 'POST',
                        body: formData
                    });

                    const result = await response.json();
                    if (result.success) {
                        await loadThumbnails();
                        showMessage('Image uploadée avec succès !', 'success');
                        uploadForm.reset();
                        previewContainer.style.display = 'none';
                    } else {
                        throw new Error(result.error || 'Erreur lors de l\'upload');
                    }
                } catch (err) {
                    console.error('Erreur:', err);
                    showMessage(`Erreur lors de l'upload : ${err.message}`, 'error');
                }
            });

            const imageUpload = document.getElementById('image-upload');
            const previewContainer = document.getElementById('preview-container');
            const uploadPreview = document.getElementById('upload-preview');

            // Ajouter cet event listener pour la prévisualisation
            imageUpload.addEventListener('change', async (e) => {
                const file = e.target.files[0];
                console.log('Fichier sélectionné:', file); // Debug

                if (!file) {
                    previewContainer.style.display = 'none';
                    return;
                }

                if (!selectedFilter) {
                    showMessage('Veuillez d\'abord sélectionner un filtre', 'error');
                    return;
                }

                try {
                    console.log('Début du processus de prévisualisation'); // Debug
                    const tempCanvas = document.createElement('canvas');
                    const tempCtx = tempCanvas.getContext('2d');

                    const uploadedImg = new Image();
                    uploadedImg.src = URL.createObjectURL(file);

                    await new Promise((resolve) => {
                        uploadedImg.onload = () => {
                            console.log('Image source chargée'); // Debug
                            resolve();
                        };
                    });

                    tempCanvas.width = WEBCAM_WIDTH;
                    tempCanvas.height = WEBCAM_HEIGHT;

                    tempCtx.drawImage(uploadedImg, 0, 0, WEBCAM_WIDTH, WEBCAM_HEIGHT);
                    console.log('Image dessinée sur le canvas'); // Debug

                    const filterImg = new Image();
                    filterImg.crossOrigin = "anonymous";

                    await new Promise((resolve, reject) => {
                        filterImg.onload = () => {
                            console.log('Filtre chargé'); // Debug
                            tempCtx.drawImage(filterImg, 0, 0, WEBCAM_WIDTH, WEBCAM_HEIGHT);
                            resolve();
                        };
                        filterImg.onerror = (e) => {
                            console.error('Erreur chargement filtre:', e); // Debug
                            reject(e);
                        };
                        filterImg.src = selectedFilter.path;
                    });

                    const imageData = tempCanvas.toDataURL('image/png');
                    uploadPreview.src = imageData;
                    previewContainer.style.display = 'block';
                    console.log('Prévisualisation mise à jour'); // Debug

                } catch (err) {
                    console.error('Erreur complète:', err); // Debug
                    showMessage('Erreur lors de la prévisualisation', 'error');
                }
            });

            // Chargement des miniatures
            async function loadThumbnails() {
                try {
                    console.log('Chargement des miniatures...');
                    const response = await fetch('/api/user-images');
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    const data = await response.json();
                    console.log('Données miniatures reçues:', data);

                    if (!data.success) {
                        throw new Error(data.error || 'Erreur inconnue');
                    }

                    const container = document.getElementById('thumbnails-container');
                    container.innerHTML = '';

                    data.images.forEach(image => {
                        const thumbnail = document.createElement('div');
                        thumbnail.className = 'thumbnail';
                        thumbnail.innerHTML = `
                        <img src="/uploads/${image.path}" alt="Photo">
                        <button class="delete-btn" data-id="${image.id}">&times;</button>
                    `;
                        container.appendChild(thumbnail);
                    });

                    // Ajouter les événements de suppression
                    document.querySelectorAll('.delete-btn').forEach(btn => {
                        btn.addEventListener('click', deleteImage);
                    });
                } catch (err) {
                    console.error('Erreur chargement miniatures:', err);
                }
            }

            // Suppression d'image
            async function deleteImage(e) {
                const imageId = e.target.dataset.id;
                if (!confirm('Êtes-vous sûr de vouloir supprimer cette image ?')) {
                    return;
                }

                try {
                    const formData = new FormData();
                    formData.append('image_id', imageId);
                    formData.append('csrf_token', document.querySelector('[name="csrf_token"]').value);

                    const response = await fetch('/delete-image', {
                        method: 'POST',
                        body: formData
                    });

                    const result = await response.json();
                    if (result.success) {
                        loadThumbnails();
                        showMessage('Image supprimée avec succès !', 'success');
                    } else {
                        throw new Error(result.error);
                    }
                } catch (err) {
                    showMessage(`Erreur lors de la suppression : ${err.message}`, 'error');
                }
            }

            // Affichage des messages
            function showMessage(message, type) {
                const messageDiv = document.createElement('div');
                messageDiv.className = `${type}-message`;
                messageDiv.textContent = message;
                document.querySelector('.editor-main').prepend(messageDiv);
                setTimeout(() => messageDiv.remove(), 3000);
            }

            // Initialisation
            initWebcam();
            loadFilters();
            loadThumbnails();
        });
        document.addEventListener("DOMContentLoaded", function() {
            const fileInput = document.getElementById("image-upload");
            const previewContainer = document.getElementById("preview-container");
            const previewImage = document.getElementById("upload-preview");
            const fileNameDisplay = document.getElementById("file-name");

            fileInput.addEventListener("change", function(event) {
                const file = event.target.files[0];

                if (file) {
                    // Afficher le nom du fichier
                    fileNameDisplay.textContent = file.name;

                    // Générer la prévisualisation
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        previewImage.src = e.target.result;
                        previewContainer.style.display = "block";
                    };
                    reader.readAsDataURL(file);
                } else {
                    fileNameDisplay.textContent = "Aucun fichier sélectionné";
                    previewContainer.style.display = "none";
                }
            });
        });

        document.addEventListener("DOMContentLoaded", function() {
            // Charger les miniatures
            async function loadThumbnails() {
                try {
                    console.log('Chargement des miniatures...');
                    const response = await fetch('/api/user-images');
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    const data = await response.json();
                    console.log('Données miniatures reçues:', data);

                    if (!data.success) {
                        throw new Error(data.error || 'Erreur inconnue');
                    }

                    const container = document.getElementById('thumbnails-container');
                    container.innerHTML = '';

                    data.images.forEach(image => {
                        const thumbnail = document.createElement('div');
                        thumbnail.className = 'thumbnail';
                        thumbnail.innerHTML = `
                            <img src="/uploads/${image.path}" alt="Photo">
                            <button class="delete-btn" data-id="${image.id}">&times;</button>
                        `;
                        container.appendChild(thumbnail);
                    });

                    // Ajouter les événements de suppression
                    document.querySelectorAll('.delete-btn').forEach(btn => {
                        btn.addEventListener('click', deleteImage);
                    });
                } catch (err) {
                    console.error('Erreur chargement miniatures:', err);
                }
            }

            // Suppression d'image
            async function deleteImage(e) {
                const imageId = e.target.dataset.id;
                if (!confirm('Êtes-vous sûr de vouloir supprimer cette image ?')) {
                    return;
                }

                try {
                    const formData = new FormData();
                    formData.append('image_id', imageId);
                    formData.append('csrf_token', document.querySelector('[name="csrf_token"]').value);

                    const response = await fetch('/delete-image', {
                        method: 'POST',
                        body: formData
                    });

                    const result = await response.json();
                    if (result.success) {
                        loadThumbnails();
                        showMessage('Image supprimée avec succès !', 'success');
                    } else {
                        throw new Error(result.error);
                    }
                } catch (err) {
                    showMessage(`Erreur lors de la suppression : ${err.message}`, 'error');
                }
            }

            // Fonction pour afficher un message
            function showMessage(message, type) {
                const messageDiv = document.createElement('div');
                messageDiv.className = `${type}-message`;
                messageDiv.textContent = message;
                document.querySelector('.editor-main').prepend(messageDiv);
                setTimeout(() => messageDiv.remove(), 3000);
            }

            // Initialisation
            loadThumbnails();
        });


    </script>
</body>

</html>