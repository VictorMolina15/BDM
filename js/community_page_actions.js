document.addEventListener('DOMContentLoaded', function() {
    const joinButton = document.getElementById('join-community-btn');
    const leaveButton = document.getElementById('leave-community-btn');
    // const createPostInCommunityButton = document.getElementById('create-post-in-community-btn'); // Manejo ya sugerido arriba

    async function handleCommunityMembership(action, communityId, buttonElement) {
        buttonElement.disabled = true;
        buttonElement.textContent = 'Procesando...';

        const formData = new FormData();
        formData.append('action', action); // 'join_community' o 'leave_community'
        formData.append('community_id', communityId);

        try {
            const response = await fetch('back-end/community_actions_ajax.php', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();

            if (result.status === 'success') {
                alert(result.message);
                window.location.reload(); // Recargar la página para ver el cambio de estado del botón y contenido
            } else {
                alert(result.message || 'Ocurrió un error.');
                buttonElement.disabled = false;
                // Restaurar texto original del botón según la acción
                buttonElement.textContent = (action === 'join_community') ? 'Unirse a la Comunidad' : 'Abandonar Comunidad';
            }
        } catch (error) {
            console.error('Error en la acción de membresía:', error);
            alert('Error de conexión.');
            buttonElement.disabled = false;
            buttonElement.textContent = (action === 'join_community') ? 'Unirse a la Comunidad' : 'Abandonar Comunidad';
        }
    }

    if (joinButton) {
        joinButton.addEventListener('click', function() {
            const communityId = this.dataset.communityId;
            handleCommunityMembership('join_community', communityId, this);
        });
    }

    if (leaveButton) {
        leaveButton.addEventListener('click', function() {
            if (confirm('¿Estás seguro de que quieres abandonar esta comunidad?')) {
                const communityId = this.dataset.communityId;
                handleCommunityMembership('leave_community', communityId, this);
            }
        });
    }
    
    // Manejo del botón "Crear Post Aquí" (ya integrado en el HTML de community-page.php y explicado antes)
    // Este código es para asegurar que el modal de post sepa a qué comunidad postear.
    const createPostInCommunityBtn = document.getElementById('create-post-in-community-btn');
    if (createPostInCommunityBtn) {
        createPostInCommunityBtn.addEventListener('click', function() {
            const communityId = this.dataset.communityId;
            const communityName = this.dataset.communityName;
            
            const createPostModal = document.getElementById('create-post-modal');
            const createPostForm = document.getElementById('create-post-form'); // El formulario DENTRO del modal de post
            const postCaptionTextarea = document.getElementById('post-caption');

            if (createPostModal && createPostForm && postCaptionTextarea) {
                postCaptionTextarea.placeholder = `¿Qué estás pensando para la comunidad "${communityName}"?`;
                
                // Añadir o actualizar el input oculto para el community_id en el formulario de post
                let communityIdInput = createPostForm.querySelector('input[name="post_target_community_id"]');
                if (!communityIdInput) {
                    communityIdInput = document.createElement('input');
                    communityIdInput.type = 'hidden';
                    communityIdInput.name = 'post_target_community_id';
                    createPostForm.appendChild(communityIdInput);
                }
                communityIdInput.value = communityId;
                
                // Lógica para abrir y resetear el modal de post (basada en tu modal-post.php)
                // Asumo que tienes una función similar a `openAndResetCreatePostModal` o puedes adaptarla.
                // Esta es una simplificación, necesitas tu lógica exacta de apertura de modal.
                createPostForm.reset(); // Resetear campos del formulario de post
                // Resetear previews de imágenes si los tienes en el modal de post
                const imagePreview = document.getElementById('media-preview'); // Del modal-post.php
                const videoPreview = document.getElementById('video-preview'); // Del modal-post.php
                if (imagePreview) { imagePreview.style.display = 'none'; imagePreview.src = '#'; }
                if (videoPreview) { videoPreview.style.display = 'none'; videoPreview.src = ''; }
                const createPostFeedback = document.getElementById('create-post-feedback'); // Del modal-post.php
                if(createPostFeedback) { createPostFeedback.style.display = 'none'; createPostFeedback.textContent = '';}


                createPostModal.style.display = 'grid'; 
            } else {
                console.error("No se encontraron los elementos del modal de creación de post.");
            }
        });
    }
});