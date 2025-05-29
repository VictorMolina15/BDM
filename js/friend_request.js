document.addEventListener('DOMContentLoaded', function() {
    const pendingRequestsContainer = document.getElementById('pending-requests-container');

    function getProfilePicUrl(filename) {
        const defaultPic = '../assets/profile_pics/default-profile.png';
        return (filename && filename !== 'null' && filename !== '') ? `../assets/profile_pics/${encodeURIComponent(filename)}` : defaultPic;
    }

    function displayPendingRequests(requests) {
        if (!pendingRequestsContainer) return;
        pendingRequestsContainer.innerHTML = ''; // Limpiar

        if (requests.length === 0) {
            pendingRequestsContainer.innerHTML = '<p class="text-muted" style="padding: 1rem;">No tienes solicitudes de amistad pendientes.</p>';
            return;
        }

        requests.forEach(req => {
            const requestDiv = document.createElement('div');
            requestDiv.className = 'request';
            requestDiv.setAttribute('data-requester-id', req.requester_id);

            const avatarUrl = getProfilePicUrl(req.requester_avatar);

            requestDiv.innerHTML = `
                <div class="info">
                    <div class="profile-photo">
                        <img src="${avatarUrl}" alt="${encodeURIComponent(req.requester_username)}">
                    </div>
                    <div>
                        <a href="profile-page.php?user=${req.requester_id}" style="color: var(--color-dark)"><h5>${req.requester_username}</h5>
                        <p class="text-muted">@${req.requester_id}</p><a>
                    </div>
                </div>
                <div class="action">
                    <button class="btn btn-primary btn-accept-request" data-requester-id="${req.requester_id}">
                        Aceptar
                    </button>
                    <button class="btn btn-reject-request" data-requester-id="${req.requester_id}">
                        Rechazar
                    </button>
                </div>
            `;
            pendingRequestsContainer.appendChild(requestDiv);
        });

        // Añadir event listeners para los nuevos botones
        document.querySelectorAll('.btn-accept-request').forEach(button => {
            button.addEventListener('click', function() {
                handleFriendRequestAction('accept_friend_request', this.dataset.requesterId, this.closest('.request'));
            });
        });
        document.querySelectorAll('.btn-reject-request').forEach(button => {
            button.addEventListener('click', function() {
                handleFriendRequestAction('reject_friend_request', this.dataset.requesterId, this.closest('.request'));
            });
        });
    }

    async function handleFriendRequestAction(action, requesterId, requestElement) {
        // Deshabilitar botones para evitar clics múltiples
        requestElement.querySelectorAll('button').forEach(btn => btn.disabled = true);

        const formData = new FormData();
        formData.append('action', action);
        formData.append('requester_id', requesterId);

        try {
            const response = await fetch('back-end/friend_actions_ajax.php', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();

            if (result.status === 'success') {
                requestElement.innerHTML = `<p class="text-muted" style="padding: 0.5rem;">${action === 'accept_friend_request' ? 'Amistad aceptada.' : 'Solicitud rechazada.'}</p>`;
                setTimeout(() => { // Opcional: remover después de un tiempo
                     requestElement.remove();
                     // Si se eliminan todos los elementos, mostrar mensaje de "no hay solicitudes"
                     if (pendingRequestsContainer.children.length === 0) {
                        pendingRequestsContainer.innerHTML = '<p class="text-muted" style="padding: 1rem;">No tienes solicitudes de amistad pendientes.</p>';
                     }
                }, 2000);
            } else {
                alert('Error: ' + result.message);
                requestElement.querySelectorAll('button').forEach(btn => btn.disabled = false); // Rehabilitar
            }
        } catch (error) {
            console.error('Error al procesar la solicitud:', error);
            alert('Ocurrió un error.');
            requestElement.querySelectorAll('button').forEach(btn => btn.disabled = false); // Rehabilitar
        }
    }

    function fetchPendingRequests() {
        if (!pendingRequestsContainer) return;
        
        fetch('back-end/friend_actions_ajax.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, // O FormData
            body: new URLSearchParams({ action: 'get_pending_requests' })
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success' && data.requests) {
                displayPendingRequests(data.requests);
            } else {
                 pendingRequestsContainer.innerHTML = `<p class="text-muted" style="padding: 1rem;">Error al cargar solicitudes: ${data.message || ''}</p>`;
            }
        })
        .catch(error => {
            console.error('Error al obtener solicitudes pendientes:', error);
            if(pendingRequestsContainer) pendingRequestsContainer.innerHTML = '<p class="text-muted" style="padding: 1rem;">Error de red al cargar solicitudes.</p>';
        });
    }

    fetchPendingRequests(); // Cargar al inicio
});