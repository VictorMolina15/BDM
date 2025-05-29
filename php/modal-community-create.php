<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<div class="modal" id="create-community-modal" style="display: none; z-index: 1006;">
    <div class="card"
        style="width: 60%; max-width: 600px; padding: 20px; top: auto; right: auto; margin-top: 5vh; margin-bottom: 5vh; overflow-y: auto;">
        <div class="modal-header"
            style="display: flex; justify-content: space-between; align-items: center; padding-bottom: 10px; border-bottom: 1px solid var(--color-light); margin-bottom:15px;">
            <h2>Crear Nueva Comunidad</h2>
            <span class="close-modal-btn" data-modal-id="create-community-modal"
                style="font-size: 1.8rem; cursor: pointer; font-weight:bold;">&times;</span>
        </div>
        <form id="create-community-form" enctype="multipart/form-data">
            <div class="form-group">
                <label for="community-name">Nombre de la Comunidad:</label>
                <input type="text" name="name_comm" id="community-name" placeholder="Ej: Amantes del Senderismo"
                    required
                    style="width: 100%; padding: 8px; border: 1px solid var(--color-grey); border-radius: var(--card-border-radius); margin-top: 5px;">
            </div>

            <div class="form-group" style="margin-top: 15px;">
                <label for="community-description">Descripción:</label>
                <textarea name="descrip" id="community-description" placeholder="Describe de qué trata tu comunidad..."
                    style="width: 100%; min-height: 100px; padding: 8px; border: 1px solid var(--color-grey); border-radius: var(--card-border-radius); resize: vertical; margin-top: 5px;"></textarea>
            </div>

            <div class="form-group" style="margin-top: 15px;">
                <label for="community-profile-pic">Icono de la Comunidad (opcional, máx. 5MB):</label>
                <input type="file" name="community_picture" id="community-profile-pic-input"
                    accept="image/jpeg,image/png,image/gif" style="display: block; margin-top: 5px;">
                <img id="community-profile-preview" src="#" alt="Icono preview"
                    style="width: 100px; height: 100px; border-radius: 50%; object-fit: cover; margin-top: 10px; display: none; border: 1px solid var(--color-light);" />
            </div>

            <div class="form-group" style="margin-top: 15px;">
                <label for="community-cover-pic">Banner de la Comunidad (opcional, máx. 10MB):</label>
                <input type="file" name="cover_picture" id="community-cover-pic-input"
                    accept="image/jpeg,image/png,image/gif" style="display: block; margin-top: 5px;">
                <img id="community-cover-preview" src="#" alt="Banner preview"
                    style="width: 100%; max-height: 150px; object-fit: cover; margin-top: 10px; display: none; border-radius: var(--card-border-radius);" />
            </div>

            <div id="create-community-feedback" class="msg"
                style="display:none; padding: 10px; margin-top: 15px; border-radius: 5px; text-align:center;"></div>

            <div class="form-actions" style="margin-top: 20px; text-align: right;">
                <button type="button" class="btn btn-secondary close-modal-btn" data-modal-id="create-community-modal"
                    style="margin-right: 10px;">Cancelar</button>
                <button type="submit" class="btn btn-primary">Crear Comunidad</button>
            </div>
        </form>
    </div>
</div>
<script>
    // Es importante que este script se ejecute después de que el DOM esté cargado.
    // Si lo pones en un archivo .js separado, asegúrate de envolverlo en DOMContentLoaded.
    document.addEventListener('DOMContentLoaded', function () {
        const createCommunityModal = document.getElementById('create-community-modal');
        // El ID 'nav-create-community' se añadirá al <li> en navbar.php
        const navCreateCommunityItem = document.getElementById('nav-create-community');
        const closeCreateCommunityModalBtns = createCommunityModal.querySelectorAll('.close-modal-btn');
        const createCommunityForm = document.getElementById('create-community-form');
        const communityProfilePicInput = document.getElementById('community-profile-pic-input');
        const communityProfilePreview = document.getElementById('community-profile-preview');
        const communityCoverPicInput = document.getElementById('community-cover-pic-input');
        const communityCoverPreview = document.getElementById('community-cover-preview');
        const createCommunityFeedback = document.getElementById('create-community-feedback');

        const openAndResetCreateCommunityModal = () => {
            if (createCommunityModal && createCommunityForm) {
                createCommunityModal.style.display = 'grid';
                createCommunityForm.reset(); // Resetea el formulario
                if (communityProfilePreview) {
                    communityProfilePreview.style.display = 'none';
                    communityProfilePreview.src = '#';
                }
                if (communityCoverPreview) {
                    communityCoverPreview.style.display = 'none';
                    communityCoverPreview.src = '#';
                }
                if (createCommunityFeedback) {
                    createCommunityFeedback.style.display = 'none';
                    createCommunityFeedback.textContent = '';
                    createCommunityFeedback.className = 'msg'; // Resetea clases de feedback
                }
            }
        };

        if (navCreateCommunityItem) {
            navCreateCommunityItem.addEventListener('click', (event) => {
                event.preventDefault(); // Prevenir comportamiento de ancla si es un <a>
                const navCreateModal = document.querySelector('.modal.create-modal'); // El modal general "Crear"
                if (navCreateModal) {
                    navCreateModal.style.display = 'none'; // Ocultar el modal general "Crear"
                }
                openAndResetCreateCommunityModal(); // Abrir el modal específico de comunidad
            });
        }

        if (closeCreateCommunityModalBtns) {
            closeCreateCommunityModalBtns.forEach(btn => {
                btn.addEventListener('click', () => {
                    if (createCommunityModal) createCommunityModal.style.display = 'none';
                });
            });
        }

        // Cerrar modal si se hace clic fuera del contenido
        window.addEventListener('click', (event) => {
            if (createCommunityModal && event.target == createCommunityModal) {
                createCommunityModal.style.display = 'none';
            }
        });

        // Función genérica para previsualizar imágenes
        function setupImagePreview(fileInputElement, previewImgElement, feedbackElement, maxSizeMB, entityName) {
            if (fileInputElement && previewImgElement) {
                fileInputElement.addEventListener('change', function (event) {
                    const file = event.target.files[0];
                    previewImgElement.style.display = 'none'; // Ocultar preview anterior

                    if (file) {
                        if (!['image/jpeg', 'image/png', 'image/gif'].includes(file.type)) {
                            if (feedbackElement) {
                                feedbackElement.textContent = `Tipo de archivo no permitido para ${entityName}. Sube JPG, PNG o GIF.`;
                                feedbackElement.className = 'msg error';
                                feedbackElement.style.display = 'block';
                            }
                            fileInputElement.value = ''; // Limpiar input
                            return;
                        }
                        if (file.size > maxSizeMB * 1024 * 1024) {
                            if (feedbackElement) {
                                feedbackElement.textContent = `El archivo para ${entityName} excede los ${maxSizeMB}MB.`;
                                feedbackElement.className = 'msg error';
                                feedbackElement.style.display = 'block';
                            }
                            fileInputElement.value = ''; // Limpiar input
                            return;
                        }
                        if (feedbackElement) feedbackElement.style.display = 'none'; // Ocultar feedback si todo OK

                        const reader = new FileReader();
                        reader.onload = function (e) {
                            previewImgElement.src = e.target.result;
                            previewImgElement.style.display = 'block';
                        }
                        reader.readAsDataURL(file);
                    }
                });
            }
        }

        setupImagePreview(communityProfilePicInput, communityProfilePreview, createCommunityFeedback, 5, "el icono");
        setupImagePreview(communityCoverPicInput, communityCoverPreview, createCommunityFeedback, 10, "el banner");

        if (createCommunityForm) {
            createCommunityForm.addEventListener('submit', async function (event) {
                event.preventDefault();
                if (createCommunityFeedback) {
                    createCommunityFeedback.style.display = 'none';
                    createCommunityFeedback.textContent = '';
                    createCommunityFeedback.className = 'msg';
                }

                const formData = new FormData(createCommunityForm);
                formData.append('action', 'create_community'); // Acción para el backend

                const submitButton = createCommunityForm.querySelector('button[type="submit"]');
                if (submitButton) {
                    submitButton.disabled = true;
                    submitButton.textContent = 'Creando...';
                }

                try {
                    // Asegúrate que la ruta a community_actions_ajax.php sea correcta desde donde se sirve este HTML/JS
                    const response = await fetch('back-end/community_actions_ajax.php', {
                        method: 'POST',
                        body: formData
                    });
                    const result = await response.json();

                    if (result.status === 'success') {
                        if (createCommunityFeedback) {
                            createCommunityFeedback.textContent = result.message + (result.community_id ? ` Redirigiendo...` : '');
                            createCommunityFeedback.className = 'msg success';
                            createCommunityFeedback.style.display = 'block';
                        }
                        setTimeout(() => {
                            if (createCommunityModal) createCommunityModal.style.display = 'none';
                            if (result.community_id) {
                                window.location.href = `community-page.php?id=${result.community_id}`;
                            } else {
                                // Considera recargar o actualizar la UI de otra forma si no hay ID
                                // window.location.reload(); 
                            }
                        }, 2000);
                    } else {
                        if (createCommunityFeedback) {
                            createCommunityFeedback.textContent = result.message || 'Error al crear la comunidad.';
                            createCommunityFeedback.className = 'msg error';
                            createCommunityFeedback.style.display = 'block';
                        }
                    }
                } catch (error) {
                    console.error('Error submitting community form:', error);
                    if (createCommunityFeedback) {
                        createCommunityFeedback.textContent = 'Error de conexión al crear la comunidad.';
                        createCommunityFeedback.className = 'msg error';
                        createCommunityFeedback.style.display = 'block';
                    }
                } finally {
                    if (submitButton) {
                        submitButton.disabled = false;
                        submitButton.textContent = 'Crear Comunidad';
                    }
                }
            });
        }
    });
</script>