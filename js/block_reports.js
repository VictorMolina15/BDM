document.addEventListener('DOMContentLoaded', function() {
    // MANEJO DEL MODAL DE AJUSTES Y BLOQUEOS
const settingsModal = document.querySelector('.settings-modal'); // Ya lo tienes
const manageBlocksLink = document.getElementById('manage-blocks-link'); 
const blocksReportsModal = document.getElementById('blocks-reports-modal');
const blocksReportsListContainer = document.getElementById('blocks-reports-list-container');
const closeBlocksReportsModalBtn = blocksReportsModal.querySelector('.close-modal-btn');


function getProfilePicUrl_Block(filename) { // Helper para evitar conflictos si ya existe en otro scope
    const defaultPic = '../assets/profile_pics/default-profile.png';
    return (filename && filename !== 'null' && filename !== '') ? `../assets/profile_pics/${encodeURIComponent(filename)}` : defaultPic;
}


function displayMyBlocks(blocks) {
    blocksReportsListContainer.innerHTML = '';
    if (blocks.length === 0) {
        blocksReportsListContainer.innerHTML = '<p class="text-muted">No has bloqueado/reportado a ningún usuario o publicación.</p>';
        return;
    }

    blocks.forEach(block => {
        const itemDiv = document.createElement('div');
        itemDiv.className = 'blocked-item'; // Añade CSS para esta clase
        itemDiv.style.display = 'flex';
        itemDiv.style.justifyContent = 'space-between';
        itemDiv.style.alignItems = 'center';
        itemDiv.style.padding = '10px 8px';
        itemDiv.style.borderBottom = '1px solid var(--color-light)';

        let contentHtml = '';
        let unblockAction = '';
        let unblockTargetId = '';

        if (block.reported_user_id) { // Es un usuario bloqueado
            const avatarUrl = getProfilePicUrl_Block(block.reported_user_avatar);
            contentHtml = `
                <div style="display:flex; align-items:center; gap:10px;">
                    <img src="${avatarUrl}" alt="${block.reported_username || 'Usuario'}" style="width:40px; height:40px; border-radius:50%; object-fit:cover;">
                    <div>
                        <strong>Usuario: ${block.reported_username || block.reported_user_id}</strong><br>
                        <small class="text-muted">Razón: ${block.reason || 'No especificada'}</small><br>
                        <small class="text-muted">Fecha: ${new Date(block.created_at).toLocaleDateString()}</small>
                    </div>
                </div>
            `;
            unblockAction = 'unblock_user';
            unblockTargetId = block.reported_user_id;
        } else if (block.reporting_post_id) { // Es una publicación bloqueada
            contentHtml = `
                <div>
                    <strong>Publicación ID: ${block.reporting_post_id}</strong>
                    (Autor: ${block.post_author_username || 'Desconocido'})<br>
                    <small class="text-muted">Contenido: "${block.post_content_preview || 'N/A'}"...</small><br>
                    <small class="text-muted">Razón: ${block.reason || 'No especificada'}</small><br>
                    <small class="text-muted">Fecha: ${new Date(block.created_at).toLocaleDateString()}</small>
                </div>
            `;
            unblockAction = 'unblock_post';
            unblockTargetId = block.reporting_post_id;
        }

        itemDiv.innerHTML = `
            <div style="flex-grow:1;">${contentHtml}</div>
            <button class="btn btn-danger btn-unblock" data-action="${unblockAction}" data-id="${unblockTargetId}" data-report-id="${block.report_id}" style="margin-left:15px; padding: 5px 10px; font-size:0.8rem;">
                Desbloquear
            </button>
        `;
        blocksReportsListContainer.appendChild(itemDiv);
    });

    // Add event listeners to new unblock buttons
    blocksReportsListContainer.querySelectorAll('.btn-unblock').forEach(button => {
        button.addEventListener('click', function() {
            const action = this.dataset.action;
            const id = this.dataset.id;
            const reportId = this.dataset.reportId; // El ID del registro en la tabla reports
            confirmUnblock(action, id, reportId, this.closest('.blocked-item'));
        });
    });
}

async function fetchMyBlocks() {
    blocksReportsListContainer.innerHTML = '<p class="text-muted">Cargando...</p>';
    try {
        const response = await fetch('back-end/block_report_ajax.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded'},
            body: new URLSearchParams({ action: 'get_my_blocks' })
        });
        const result = await response.json();
        if (result.status === 'success' && result.blocks) {
            displayMyBlocks(result.blocks);
        } else {
            blocksReportsListContainer.innerHTML = `<p class="text-muted">Error al cargar bloqueos: ${result.message}</p>`;
        }
    } catch (error) {
        console.error("Error fetching blocks:", error);
        blocksReportsListContainer.innerHTML = '<p class="text-muted">Error de red al cargar bloqueos.</p>';
    }
}

function confirmUnblock(action, id, reportId, itemElement) {
    if (!confirm('¿Estás seguro de que quieres desbloquear este elemento?')) {
        return;
    }
    performUnblock(action, id, reportId, itemElement);
}

async function performUnblock(action, id, reportId, itemElement) {
    const formData = new FormData();
    formData.append('action', action);
    if (action === 'unblock_user') {
        formData.append('reported_user_id', id);
    } else if (action === 'unblock_post') {
        formData.append('reported_post_id', id);
    }

    try {
        const response = await fetch('back-end/block_report_ajax.php', {
            method: 'POST',
            body: formData
        });
        const result = await response.json();
        if (result.status === 'success') {
            alert(result.message);
            itemElement.remove(); // Eliminar el item de la lista visual
            if (blocksReportsListContainer.children.length === 0) {
                 blocksReportsListContainer.innerHTML = '<p class="text-muted">No has bloqueado/reportado a ningún usuario o publicación.</p>';
            }
            // Aquí deberías también refrescar las vistas (feed, búsquedas) si es necesario,
            // aunque el impacto principal será la próxima vez que se carguen.
        } else {
            alert('Error al desbloquear: ' + result.message);
        }
    } catch (error) {
        console.error("Error unblocking:", error);
        alert('Error de red al intentar desbloquear.');
    }
}

if (manageBlocksLink) {
    document.getElementById('manage-blocks-link').addEventListener('click', () => {
        settingsModal.style.display = 'none'; // Ocultar modal de ajustes
        blocksReportsModal.style.display = 'grid';
        fetchMyBlocks();
    });
}


// Event Listener para cerrar el modal de bloqueos
if(closeBlocksReportsModalBtn) {
    closeBlocksReportsModalBtn.addEventListener('click', () => {
        blocksReportsModal.style.display = 'none';
    });
}

// Cerrar modales al hacer clic fuera (generalizado)
window.addEventListener('click', (event) => {
    if (event.target == blocksReportsModal) {
        blocksReportsModal.style.display = 'none';
    }

});
});