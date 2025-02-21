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
    <link rel="icon" type="image/x-icon" href="/assets/images/favicon.ico">

</head>

<body>
    <?php include 'partials/header.php'; ?>

    <main class="editor-container">
        <div class="editor-main">
            <div class="capture-container">
                <h3>Prendre une photo</h3>

                <div class="webcam-container">
                    <video id="webcam" autoplay playsinline></video>
                    <div id="overlay-container"></div>
                    <canvas id="canvas" style="display: none;"></canvas>
                </div>
                <button id="capture-btn" class="btn" disabled>Prendre une photo</button>
            </div>

            <div class="filters-container">
                <h3>Superpositions disponibles</h3>
                <div class="filters-grid">
                </div>
            </div>

            <div class="upload-container">
                <h3>Ou uploadez une image</h3>
                <form id="upload-form" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

                    <label for="image-upload" class="upload-wrapper">
                        <span id="file-name">Aucun fichier sélectionné</span>
                        <span class="upload-icon">📁</span>
                    </label>
                    <input type="file" name="image" id="image-upload" accept="image/*">

                    <div id="preview-container">
                        <img id="upload-preview">
                        <div id="preview-overlay-container"></div>
                    </div>

                    <button type="submit" class="btn">Uploader</button>
                </form>
            </div>

            <div class="editor-sidebar">
                <h3>Vos photos récentes</h3>
                <div id="thumbnails-container">
                </div>
            </div>

        </div>
    </main>

    <?php include 'partials/footer.php'; ?>

    <script>
        class OverlayController {
            constructor(containerElement, options = {}) {
                this.container = containerElement;
                this.overlay = null;
                this.isDragging = false;
                this.isResizing = false;
                this.isRotating = false;
                this.currentHandle = null;
                this.startX = 0;
                this.startY = 0;
                this.currentX = 0;
                this.currentY = 0;
                this.scale = 1;
                this.rotation = 0;
                this.initialDistance = 0;
                this.initialScale = 1;
                this.initialRotation = 0;

                this.options = {
                    initialScale: 1.0,
                    minScale: 0.1,
                    maxScale: 1.0,
                    ...options
                };

                this.setupOverlay();
                this.setupEventListeners();
                this.hasRotation = false;
            }

            setupOverlay() {
                this.overlayWrapper = document.createElement('div');
                this.overlayWrapper.className = 'overlay-wrapper';
                this.overlayWrapper.style.position = 'absolute';
                this.overlayWrapper.style.cursor = 'move';
                this.updateTransformOrigin();

                this.overlay = document.createElement('img');
                this.overlay.className = 'overlay-image';
                this.overlay.style.width = '100%';
                this.overlay.style.height = '100%';
                this.overlay.style.pointerEvents = 'none';
                this.overlayWrapper.appendChild(this.overlay);

                const handles = ['top-left', 'top-right', 'bottom-left', 'bottom-right'];
                handles.forEach(position => {
                    const handle = document.createElement('div');
                    handle.className = `resize-handle ${position}`;
                    this.overlayWrapper.appendChild(handle);
                });

                const rotateHandle = document.createElement('div');
                rotateHandle.className = 'rotate-handle';
                this.overlayWrapper.appendChild(rotateHandle);

                this.container.appendChild(this.overlayWrapper);
            }

            handleMouseMove(e) {
                if (this.isDragging) {
                    e.preventDefault();

                    const deltaX = e.clientX - this.startX;
                    const deltaY = e.clientY - this.startY;

                    const containerRect = this.container.getBoundingClientRect();
                    const wrapperRect = this.overlayWrapper.getBoundingClientRect();

                    let newX = this.currentX + deltaX;
                    let newY = this.currentY + deltaY;

                    const maxX = containerRect.width - (wrapperRect.width * this.scale);
                    const maxY = containerRect.height - (wrapperRect.height * this.scale);

                    newX = Math.max(0, Math.min(newX, maxX));
                    newY = Math.max(0, Math.min(newY, maxY));

                    this.currentX = newX;
                    this.currentY = newY;

                    this.updateTransform();

                    this.startX = e.clientX;
                    this.startY = e.clientY;
                } else if (this.isResizing) {
                    e.preventDefault();

                    const deltaX = e.clientX - this.startX;
                    const deltaY = e.clientY - this.startY;
                    const distance = Math.sqrt(deltaX * deltaX + deltaY * deltaY);
                    const scaleFactor = 1 + (distance / 100) * (deltaY > 0 ? 1 : -1);

                    this.scale = Math.max(
                        this.options.minScale,
                        Math.min(this.options.maxScale, this.initialScale * scaleFactor)
                    );

                    this.updateTransform();
                } else if (this.isRotating) {
                    e.preventDefault();

                    const rect = this.overlayWrapper.getBoundingClientRect();
                    const centerX = rect.left + rect.width / 2;
                    const centerY = rect.top + rect.height / 2;

                    const currentRotation = Math.atan2(e.clientY - centerY, e.clientX - centerX);
                    const rotationDelta = (currentRotation - this.initialRotation) * (180 / Math.PI);

                    this.rotation = (this.rotation + rotationDelta) % 360;
                    this.initialRotation = currentRotation;

                    this.updateTransform();
                }
            }

            startDragging(e) {
                if (e.target.classList.contains('resize-handle') || e.target.classList.contains('rotate-handle')) {
                    return;
                }
                this.isDragging = true;
                this.startX = e.clientX;
                this.startY = e.clientY;
            }

            startResizing(e, handle) {
                this.isResizing = true;
                this.currentHandle = handle;
                this.startX = e.clientX;
                this.startY = e.clientY;
                this.initialScale = this.scale;
            }

            startRotating(e) {
                e.stopPropagation();
                this.isRotating = true;
                this.hasRotation = true;
                const rect = this.overlayWrapper.getBoundingClientRect();
                const centerX = rect.left + rect.width / 2;
                const centerY = rect.top + rect.height / 2;
                this.initialRotation = Math.atan2(e.clientY - centerY, e.clientX - centerX);
                this.updateTransformOrigin();
            }

            updateTransformOrigin() {
                if (this.hasRotation) {
                    this.overlayWrapper.style.transformOrigin = 'left top';
                } else {
                    this.overlayWrapper.style.transformOrigin = 'left top';
                }
            }

            stopDragging() {
                this.isDragging = false;
                this.isResizing = false;
                this.isRotating = false;
            }

            setupEventListeners() {
                this.overlayWrapper.addEventListener('mousedown', this.startDragging.bind(this));
                document.addEventListener('mousemove', this.handleMouseMove.bind(this));
                document.addEventListener('mouseup', this.stopDragging.bind(this));

                const resizeHandles = this.overlayWrapper.querySelectorAll('.resize-handle');
                resizeHandles.forEach(handle => {
                    handle.addEventListener('mousedown', (e) => {
                        e.stopPropagation();
                        this.startResizing(e, handle);
                    });
                });

                const rotateHandle = this.overlayWrapper.querySelector('.rotate-handle');
                rotateHandle.addEventListener('mousedown', (e) => {
                    e.stopPropagation();
                    this.startRotating(e);
                });
            }

            setImage(src) {
                this.overlay.src = src;
                this.scale = this.options.initialScale;
                this.rotation = 0;

                const containerRect = this.container.getBoundingClientRect();
                this.currentX = (containerRect.width - this.overlayWrapper.offsetWidth) / 2;
                this.currentY = (containerRect.height - this.overlayWrapper.offsetHeight) / 2;
                this.updateTransform();
            }

            updateTransform() {
                const transform = `translate(${this.currentX}px, ${this.currentY}px) rotate(${this.rotation}deg) scale(${this.scale})`;
                this.overlayWrapper.style.transform = transform;
            }

            getTransformations() {
                const containerRect = this.container.getBoundingClientRect();
                const overlayRect = this.overlayWrapper.getBoundingClientRect();

                const scaleFactorX = containerRect.width / WEBCAM_WIDTH;
                const scaleFactorY = containerRect.height / WEBCAM_HEIGHT;

                const W = overlayRect.width;
                const H = overlayRect.height;

                const currentLeft = this.currentX;
                const currentTop = this.currentY;

                const centerX = currentLeft + (W * this.scale) / 2;
                const centerY = currentTop + (H * this.scale) / 2;

                if (this.rotation === 0) {
                    return {
                        x: centerX / scaleFactorX,
                        y: centerY / scaleFactorY,
                        scale: this.scale,
                        rotation: 0
                    };
                }

                const angleRad = this.rotation * Math.PI / 180;

                const pivotX = currentLeft;
                const pivotY = currentTop;

                const translatedX = centerX - pivotX;
                const translatedY = centerY - pivotY;

                const rotatedX = pivotX + (translatedX * Math.cos(angleRad) - translatedY * Math.sin(angleRad));
                const rotatedY = pivotY + (translatedX * Math.sin(angleRad) + translatedY * Math.cos(angleRad));

                const finalX = rotatedX / scaleFactorX;
                const finalY = rotatedY / scaleFactorY;

                return {
                    x: finalX,
                    y: finalY,
                    scale: this.scale,
                    rotation: this.rotation
                };
            }


        }

        const WEBCAM_WIDTH = 800;
        const WEBCAM_HEIGHT = 600;

        document.addEventListener('DOMContentLoaded', function() {
            const video = document.getElementById('webcam');
            const canvas = document.getElementById('canvas');
            const captureBtn = document.getElementById('capture-btn');
            const context = canvas.getContext('2d');
            let selectedFilter = null;

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

                    video.width = WEBCAM_WIDTH;
                    video.height = WEBCAM_HEIGHT;

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

            let webcamOverlayController = null;
            let previewOverlayController = null;

            function selectFilter(filter) {
                selectedFilter = filter;

                document.querySelectorAll('.filter-item').forEach(item => {
                    item.classList.remove('active');
                });
                const filterElement = document.querySelector(`[data-filter="${filter.id}"]`);
                if (filterElement) {
                    filterElement.parentElement.classList.add('active');
                }

                if (!webcamOverlayController) {
                    webcamOverlayController = new OverlayController(document.getElementById('overlay-container'), {
                        initialScale: 1.0,
                        minScale: 0.1,
                        maxScale: 2.0
                    });
                }

                if (!previewOverlayController && document.getElementById('preview-overlay-container')) {
                    previewOverlayController = new OverlayController(document.getElementById('preview-overlay-container'), {
                        initialScale: 1.0,
                        minScale: 0.1,
                        maxScale: 2.0
                    });
                }

                webcamOverlayController.setImage(filter.path);
                if (previewOverlayController) {
                    previewOverlayController.setImage(filter.path);
                }

                document.getElementById('capture-btn').disabled = false;
            }

            captureBtn.addEventListener('click', async () => captureImage());

            async function captureImage() {
                if (!selectedFilter) {
                    showMessage('Veuillez sélectionner un filtre', 'error');
                    return;
                }

                try {
                    const tempCanvas = document.createElement('canvas');
                    const tempCtx = tempCanvas.getContext('2d');

                    tempCanvas.width = WEBCAM_WIDTH;
                    tempCanvas.height = WEBCAM_HEIGHT;

                    tempCtx.drawImage(video, 0, 0, WEBCAM_WIDTH, WEBCAM_HEIGHT);

                    const filterImg = new Image();
                    filterImg.crossOrigin = "anonymous";

                    await new Promise((resolve, reject) => {
                        filterImg.onload = () => {
                            const transforms = webcamOverlayController.getTransformations();

                            tempCtx.save();
                            tempCtx.translate(transforms.x, transforms.y);
                            tempCtx.rotate(transforms.rotation * Math.PI / 180);
                            tempCtx.scale(transforms.scale, transforms.scale);
                            tempCtx.drawImage(filterImg, -filterImg.width / 2, -filterImg.height / 2);
                            tempCtx.restore();
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

                    const response = await fetch('/capture-image', {
                        method: 'POST',
                        body: formData
                    });

                    if (!response.ok) {
                        throw new Error(`Erreur serveur: ${response.status}`);
                    }

                    const result = await response.json();

                    if (result.success) {
                        await loadThumbnails();
                        showMessage('Photo capturée avec succès !', 'success');
                    } else {
                        throw new Error(result.error || 'Erreur lors de la sauvegarde');
                    }

                } catch (err) {
                    console.error('Erreur détaillée:', err);
                    showMessage(`Erreur lors de la capture : ${err.message}`, 'error');
                }
            }

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

                    tempCanvas.width = WEBCAM_WIDTH;
                    tempCanvas.height = WEBCAM_HEIGHT;

                    const uploadedImg = new Image();
                    uploadedImg.src = URL.createObjectURL(file);

                    await new Promise((resolve) => {
                        uploadedImg.onload = resolve;
                    });

                    tempCtx.drawImage(uploadedImg, 0, 0, WEBCAM_WIDTH, WEBCAM_HEIGHT);

                    if (previewOverlayController) {
                        const filterImg = new Image();
                        filterImg.crossOrigin = "anonymous";

                        await new Promise((resolve, reject) => {
                            filterImg.onload = () => {
                                const transforms = previewOverlayController.getTransformations();

                                tempCtx.save();
                                tempCtx.translate(transforms.x, transforms.y);
                                tempCtx.rotate(transforms.rotation * Math.PI / 180);
                                tempCtx.scale(transforms.scale, transforms.scale);
                                tempCtx.drawImage(filterImg, -filterImg.width / 2, -filterImg.height / 2);
                                tempCtx.restore();
                                resolve();
                            };
                            filterImg.onerror = reject;
                            filterImg.src = selectedFilter.path;
                        });
                    }

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

            imageUpload.addEventListener('change', async (e) => {
                const file = e.target.files[0];

                if (!file) {
                    previewContainer.style.display = 'none';
                    return;
                }

                try {
                    const reader = new FileReader();
                    reader.onload = (e) => {
                        uploadPreview.src = e.target.result;
                        previewContainer.style.display = 'block';

                        if (selectedFilter && !previewOverlayController) {
                            previewOverlayController = new OverlayController(document.getElementById('preview-overlay-container'), {
                                initialScale: 1.0,
                                minScale: 0.1,
                                maxScale: 2.0
                            });
                            previewOverlayController.setImage(selectedFilter.path);
                        }
                    };
                    reader.readAsDataURL(file);

                } catch (err) {
                    showMessage('Erreur lors de la prévisualisation', 'error');
                    console.error('Erreur prévisualisation:', err);
                }
            });

            async function loadThumbnails() {
                try {
                    const response = await fetch('/api/user-images');
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    const data = await response.json();

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

                    document.querySelectorAll('.delete-btn').forEach(btn => {
                        btn.addEventListener('click', deleteImage);
                    });
                } catch (err) {
                    console.error('Erreur chargement miniatures:', err);
                }
            }

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

            function showMessage(message, type) {
                const messageDiv = document.createElement('div');
                messageDiv.className = `${type}-message`;
                messageDiv.textContent = message;
                document.querySelector('.editor-main').prepend(messageDiv);
                setTimeout(() => messageDiv.remove(), 3000);
            }

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
                    fileNameDisplay.textContent = file.name;

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
    </script>
</body>

</html>