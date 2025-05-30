<!----------------- MODAL PARA CREAR PUBLICACIÓN --------------->
<div class="modal" id="create-post-modal" style="display: none; z-index: 1005;">
    <div class="card" style="width: 60%; max-width: 550px; padding: 20px; top: auto; right: auto;">
        <div class="modal-header"
            style="display: flex; justify-content: space-between; align-items: center; padding-bottom: 10px; border-bottom: 1px solid var(--color-light); margin-bottom:15px;">
            <h2>Crear Publicación</h2>
            <span class="close-modal-btn" data-modal-id="create-post-modal"
                style="font-size: 1.8rem; cursor: pointer; font-weight:bold;">&times;</span>
        </div>
        <form id="create-post-form" enctype="multipart/form-data">
            <div class="form-group">
                <label for="post-caption">Descripción:</label>
                <textarea name="caption" id="post-caption"
                    placeholder="¿Qué estás pensando, <?= htmlspecialchars($_SESSION['username'] ?? 'Usuario') ?>?"
                    style="width: 100%; min-height: 80px; padding: 8px; background: var(--color-light); border: 1px solid var(--color-light); border-radius: var(--card-border-radius); resize: vertical;"></textarea>
            </div>
            <div class="form-group" style="margin-top: 15px;">
                <label for="post-media">Multimedia (imagen o video, máx. 40MB):</label>
                <input type="file" name="media" id="post-media"
                    accept="image/jpeg,image/png,image/gif,video/mp4,video/webm,video/ogg,video/quicktime"
                    style="display: block; margin-top: 5px;">
                <img id="media-preview" src="#" alt="Media preview"
                    style="max-width: 100%; max-height: 200px; margin-top: 10px; display: none; border-radius: var(--card-border-radius);" />
                <video id="video-preview" controls
                    style="max-width: 100%; max-height: 200px; margin-top: 10px; display: none; border-radius: var(--card-border-radius);"></video>
            </div>
            <div id="create-post-feedback" class="msg"
                style="display:none; padding: 10px; margin-top: 15px; border-radius: 5px;"></div>
            <div class="form-actions" style="margin-top: 20px; text-align: right;">
                <button type="button" class="btn btn-secondary close-modal-btn" data-modal-id="create-post-modal"
                    style="margin-right: 10px;">Cancelar</button>
                <button type="submit" class="btn btn-primary">Publicar</button>
            </div>
        </form>
    </div>
</div>
<style>
    /* Styles for Create Post Modal (if not covered by existing .modal/.card) */
    #create-post-modal .form-group {
        margin-bottom: 15px;
    }

    #create-post-modal label {
        display: block;
        margin-bottom: 5px;
        font-weight: 500;
        color: var(--color-dark);
    }

    #create-post-modal textarea,
    #create-post-modal input[type="file"] {
        width: 100%;
        padding: 8px;
        border: 1px solid var(--color-grey);
        border-radius: calc(var(--card-border-radius) / 2);
        font-family: inherit;
        font-size: 0.9rem;
    }

    #create-post-modal input[type="file"] {
        padding: 5px;
        /* Minor adjustment for file input */
    }

    #create-post-modal #media-preview,
    #create-post-modal #video-preview {
        display: none;
        /* Hidden by default */
        margin-top: 10px;
        border-radius: calc(var(--card-border-radius) / 2);
        object-fit: cover;
    }

    /* Feedback message styling */
    #create-post-feedback.success {
        background-color: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }

    #create-post-feedback.error {
        background-color: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }
</style>
<script>

document.addEventListener('DOMContentLoaded', function() {
    // --- CREATE POST MODAL LOGIC ---
    const createPostModal = document.getElementById('create-post-modal');
    
    // Button on home-page.php directly opens the post creation modal
    const homePageCreatePostButton = document.querySelector('.left .btn.btn-primary[for="create-post"]'); 
    
    // Menu item inside navbar's "create-modal" opens the post creation modal
    const navMenuCreatePostItem = document.getElementById('nav-create-post');

    const closeCreatePostModalBtns = createPostModal.querySelectorAll('.close-modal-btn');
    const createPostForm = document.getElementById('create-post-form');
    const mediaInput = document.getElementById('post-media');
    const imagePreview = document.getElementById('media-preview');
    const videoPreview = document.getElementById('video-preview');
    const createPostFeedback = document.getElementById('create-post-feedback');

    // Function to open the create post modal and reset its form
    const openAndResetCreatePostModal = () => {
        createPostModal.style.display = 'grid';
        createPostForm.reset();
        imagePreview.style.display = 'none';
        imagePreview.src = '#';
        videoPreview.style.display = 'none';
        videoPreview.src = '';
        createPostFeedback.style.display = 'none';
        createPostFeedback.textContent = '';
    };

    if (homePageCreatePostButton) {
        homePageCreatePostButton.addEventListener('click', () => {
            openAndResetCreatePostModal();
        });
    }

    if (navMenuCreatePostItem) {
        navMenuCreatePostItem.addEventListener('click', (event) => {
            event.preventDefault(); // Prevent default anchor behavior
            // Close the navbar's "create-modal" first
            const navCreateModal = document.querySelector('.modal.create-modal');
            if (navCreateModal) {
                navCreateModal.style.display = 'none';
            }
            // Then open the actual post creation modal
            openAndResetCreatePostModal();
        });
    }

    closeCreatePostModalBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            createPostModal.style.display = 'none';
        });
    });

    // Close modal if clicked outside content
    window.addEventListener('click', (event) => {
        if (event.target == createPostModal) {
            createPostModal.style.display = 'none';
        }
    });

    if (mediaInput) {
        mediaInput.addEventListener('change', function(event) {
            // ... (código de previsualización de media sin cambios)
            const file = event.target.files[0];
            imagePreview.style.display = 'none';
            videoPreview.style.display = 'none';
            if (file) {
                const reader = new FileReader();
                if (file.type.startsWith('image/')) {
                    reader.onload = function(e) {
                        imagePreview.src = e.target.result;
                        imagePreview.style.display = 'block';
                    }
                    reader.readAsDataURL(file);
                } else if (file.type.startsWith('video/')) {
                     reader.onload = function(e) {
                        videoPreview.src = e.target.result; // For local preview
                        videoPreview.style.display = 'block';
                    }
                    reader.readAsDataURL(file); // Some browsers might not fully support video preview this way
                }
            }
        });
    }

    if (createPostForm) {
        createPostForm.addEventListener('submit', async function(event) {
            // ... (código del submit del formulario sin cambios)
            event.preventDefault();
            createPostFeedback.style.display = 'none';
            createPostFeedback.textContent = '';
            createPostFeedback.className = 'msg'; // Reset classes


            const formData = new FormData(createPostForm);
            formData.append('action', 'create_post');

            // Client-side file validation (basic)
            const mediaFile = mediaInput.files[0];
            if (mediaFile) {
                if (mediaFile.size > 40 * 1024 * 1024) { // 40MB
                    createPostFeedback.textContent = 'El archivo multimedia no debe exceder los 40MB.';
                    createPostFeedback.classList.add('error');
                    createPostFeedback.style.display = 'block';
                    return;
                }
            }
             const submitButton = createPostForm.querySelector('button[type="submit"]');
             submitButton.disabled = true;
             submitButton.textContent = 'Publicando...';

            try {
                const response = await fetch('back-end/post_actions_ajax.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();

                if (result.status === 'success') {
                    createPostFeedback.textContent = result.message;
                    createPostFeedback.classList.add('success');
                    createPostFeedback.style.display = 'block';
                    setTimeout(() => {
                        createPostModal.style.display = 'none';
                        window.location.reload(); 
                    }, 1500);
                } else {
                    createPostFeedback.textContent = result.message || 'Error al crear la publicación.';
                    createPostFeedback.classList.add('error');
                    createPostFeedback.style.display = 'block';
                }
            } catch (error) {
                console.error('Error submitting post:', error);
                createPostFeedback.textContent = 'Error de conexión al crear la publicación.';
                createPostFeedback.classList.add('error');
                createPostFeedback.style.display = 'block';
            } finally {
                 submitButton.disabled = false;
                 submitButton.textContent = 'Publicar';
            }
        });
    }
    // --- END CREATE POST MODAL LOGIC ---
});
</script>