document.addEventListener('DOMContentLoaded', function () {
    // (Keep your existing homepage.js code like sidebar, theme, settings modals etc.)

    // --- POST INTERACTION LOGIC ---
    const feedsContainer = document.querySelector('.feeds'); // Assuming posts are within a .feeds container

    if (feedsContainer) {
        // Event delegation for like, comment buttons, comment forms, and edit menus
        feedsContainer.addEventListener('click', function (event) {
            // Like button
            if (event.target.closest('.like-btn')) {
                const likeBtn = event.target.closest('.like-btn');
                const postId = likeBtn.dataset.postId;
                togglePostLike(postId, likeBtn); // Ya no se pasa 'isCurrentlyLiked'
            }

            // Comment button (could open a comment input area or focus on it)
            if (event.target.closest('.comment-btn')) {
                const postId = event.target.closest('.comment-btn').dataset.postId;
                const commentInput = document.querySelector(`.comment-form[data-post-id="${postId}"] .comment-input`);
                if (commentInput) commentInput.focus();
                loadComments(postId, true); // Load initial set of comments
            }

            // "View more comments" button
            if (event.target.classList.contains('view-more-comments-btn')) {
                const btn = event.target;
                const postId = btn.dataset.postId;
                loadComments(postId, false, parseInt(btn.dataset.offset));
            }

            // Post edit menu toggle (ellipsis)
            const editMenuIcon = event.target.closest('.feed .head .edit > i.uil-ellipsis-h');
            if (editMenuIcon) {
                const menu = editMenuIcon.nextElementSibling;
                if (menu && menu.classList.contains('edit-menu')) {
                    menu.style.display = menu.style.display === 'none' || menu.style.display === '' ? 'block' : 'none';
                }
                event.stopPropagation(); // Prevent document click from closing it immediately
            }

            // Block post option from dropdown
            if (event.target.classList.contains('block-post-option')) {
                const postIdToBlock = event.target.dataset.postId;
                const reason = prompt("Motivo del bloqueo/reporte de la publicación (opcional):");
                if (reason === null) return; // User cancelled

                blockPost(postIdToBlock, reason, event.target.closest('.feed'));
                event.target.closest('.edit-menu').style.display = 'none'; // Close menu
            }

        });
        // Handle comment form submission using event delegation
        feedsContainer.addEventListener('submit', function (event) {
            if (event.target.classList.contains('comment-form')) {
                event.preventDefault();
                const form = event.target;
                const postId = form.dataset.postId;
                const commentInput = form.querySelector('.comment-input');
                const commentContent = commentInput.value.trim();
                if (commentContent) {
                    addComment(postId, commentContent, commentInput);
                }
            }
        });
        // Auto-load initial comments for all visible posts (simplified)
        document.querySelectorAll('.feed[data-post-id]').forEach(feedDiv => {
            loadComments(feedDiv.dataset.postId, true, 0, 2); // Load initial 2 comments
        });
    }
    // Close edit menus if clicked outside
    document.addEventListener('click', function (event) {
        document.querySelectorAll('.feed .head .edit .edit-menu').forEach(menu => {
            if (!menu.parentElement.contains(event.target) && menu.style.display === 'block') {
                menu.style.display = 'none';
            }
        });
    });


    async function togglePostLike(postId, likeBtnElement) { // No necesitamos isLiking desde el cliente
        const formData = new FormData();
        formData.append('action', 'toggle_like_post');
        formData.append('post_id', postId);
        // El user_id se obtiene de la sesión en el backend

        // Opcional: Deshabilitar el botón mientras se procesa
        likeBtnElement.style.pointerEvents = 'none';

        try {
            const response = await fetch('../php/back-end/post_actions_ajax.php', { method: 'POST', body: formData });
            const result = await response.json();

            if (result.status === 'success') {
                const feedElement = document.querySelector(`.feed[data-post-id="${postId}"]`);
                if (feedElement) {
                    const likeCountElement = feedElement.querySelector('.liked-by .like-count');
                    if (likeCountElement) {
                        likeCountElement.textContent = result.new_like_count;
                    }

                    const heartIcon = likeBtnElement.querySelector('i');
                    if (result.liked) { // 'liked' es el nuevo estado devuelto por el SP
                        heartIcon.classList.remove('uil-heart-alt');
                        heartIcon.classList.add('uil-heart'); // Clase para 'liked'
                        heartIcon.style.color = 'var(--color-danger)'; // Color para 'liked'
                    } else {
                        heartIcon.classList.remove('uil-heart');
                        heartIcon.classList.add('uil-heart-alt'); // Clase para 'not liked'
                        heartIcon.style.color = ''; // Restablecer color
                    }
                }
            } else {
                alert(result.message || 'Error al procesar "Me Gusta".');
            }
        } catch (error) {
            console.error('Error toggling like:', error);
            alert('Error de conexión al procesar "Me Gusta".');
        } finally {
            likeBtnElement.style.pointerEvents = 'auto'; // Rehabilitar el botón
        }
    }

    async function addComment(postId, content, commentInputElement) {
        const formData = new FormData();
        formData.append('action', 'add_comment');
        formData.append('post_id', postId);
        formData.append('comment_content', content);
        commentInputElement.disabled = true;

        try {
            const response = await fetch('back-end/post_actions_ajax.php', { method: 'POST', body: formData });
            const result = await response.json();

            if (result.status === 'success' && result.comment_data) {
                const commentsContainer = document.querySelector(`.comments-section[data-post-id="${postId}"] .existing-comments`);
                appendCommentToDOM(result.comment_data, commentsContainer, false); // Append at the end
                commentInputElement.value = ''; // Clear input
            } else {
                alert(result.message || 'Error al añadir comentario.');
            }
        } catch (error) {
            console.error('Error adding comment:', error);
            alert('Error de conexión al añadir comentario.');
        } finally {
            commentInputElement.disabled = false;
        }
    }
    async function loadComments(postId, isInitialLoad = false, offset = 0, limit = 5) {
        const commentsContainer = document.querySelector(`.comments-section[data-post-id="${postId}"] .existing-comments`);
        const viewMoreBtn = document.querySelector(`.view-more-comments-btn[data-post-id="${postId}"]`);

        if (isInitialLoad) {
            commentsContainer.innerHTML = '<p class="text-muted" style="font-size:0.8rem;">Cargando comentarios...</p>';
        }
        if (viewMoreBtn) viewMoreBtn.disabled = true;


        const formData = new FormData();
        formData.append('action', 'get_comments');
        formData.append('post_id', postId);
        formData.append('limit', limit);
        formData.append('offset', offset);

        try {
            const response = await fetch('back-end/post_actions_ajax.php', { method: 'POST', body: formData });
            const result = await response.json();

            if (result.status === 'success') {
                if (isInitialLoad) commentsContainer.innerHTML = ''; // Clear "loading"

                if (result.comments.length === 0 && isInitialLoad) {
                    commentsContainer.innerHTML = '<p class="text-muted" style="font-size:0.8rem; padding: 5px 0;">No hay comentarios aún.</p>';
                    if (viewMoreBtn) viewMoreBtn.style.display = 'none';
                } else {
                    result.comments.forEach(comment => {
                        appendCommentToDOM(comment, commentsContainer, false); // Append to end
                    });

                    if (viewMoreBtn) {
                        if (result.comments.length < limit) {
                            viewMoreBtn.style.display = 'none'; // No more comments
                        } else {
                            viewMoreBtn.style.display = 'block';
                            viewMoreBtn.dataset.offset = offset + result.comments.length;
                        }
                    }
                }
            } else {
                if (isInitialLoad) commentsContainer.innerHTML = `<p class="text-danger" style="font-size:0.8rem;">${result.message || 'Error al cargar comentarios.'}</p>`;
            }
        } catch (error) {
            console.error('Error loading comments:', error);
            if (isInitialLoad) commentsContainer.innerHTML = '<p class="text-danger" style="font-size:0.8rem;">Error de conexión al cargar comentarios.</p>';
        } finally {
            if (viewMoreBtn) viewMoreBtn.disabled = false;
        }
    }
    function appendCommentToDOM(commentData, container, prepend = false) {
        const commentDiv = document.createElement('div');
        commentDiv.className = 'comment-item'; // Add styling for this class
        commentDiv.setAttribute('data-comment-id', commentData.id);
        commentDiv.style.padding = "5px 0";
        commentDiv.style.borderBottom = "1px solid var(--color-light)";
        commentDiv.style.fontSize = "0.85rem";


        const commenterAvatar = commentData.commenter_avatar || 'default-profile.png';
        const avatarUrl = `../assets/profile_pics/${commenterAvatar}`;
        const commentDate = new Date(commentData.created_at).toLocaleDateString([], { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });


        commentDiv.innerHTML = `
            <div style="display: flex; align-items: flex-start; gap: 8px;">
                <img src="${avatarUrl}" alt="${commentData.commenter_username}" style="width: 24px; height: 24px; border-radius: 50%;">
                <div>
                    <a href="profile-page.php?user=${commentData.user_id}" style="font-weight: bold; color: var(--color-dark); text-decoration: none;">${commentData.commenter_username}</a>
                    <p style="margin: 2px 0; color: var(--color-dark);">${nl2br(htmlspecialchars(commentData.content))}</p>
                    <small class="text-muted">${commentDate}</small>
                </div>
            </div>
        `;
        if (prepend) {
            container.insertBefore(commentDiv, container.firstChild);
        } else {
            container.appendChild(commentDiv);
        }
    }
    // Utility to prevent XSS on client side display if needed
    function htmlspecialchars(str) {
        if (typeof str !== 'string') return '';
        return str.replace(/[&<>"']/g, function (match) {
            return {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            }[match];
        });
    }
    function nl2br(str) {
        return str.replace(/(?:\r\n|\r|\n)/g, '<br>');
    }


    async function blockPost(postId, reason, feedElement) {
        const formData = new FormData();
        formData.append('action', 'block_post'); // This action should be in block_report_ajax.php
        formData.append('reported_post_id', postId);
        if (reason) {
            formData.append('reason', reason);
        }

        try {
            const response = await fetch('back-end/block_report_ajax.php', { // Make sure this path is correct
                method: 'POST',
                body: formData
            });
            const result = await response.json();
            alert(result.message);
            if (result.status === 'success' && feedElement) {
                // feedElement.style.display = 'none'; // Simple hide
                feedElement.innerHTML = '<p class="text-muted" style="padding:1rem; text-align:center;">Esta publicación ha sido bloqueada y no se mostrará.</p>';
            }
        } catch (error) {
            console.error('Error al bloquear post:', error);
            alert('Error de red al bloquear la publicación.');
        }
    }
});