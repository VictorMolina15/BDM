<nav>
    <div class="container">
        <a href="home-page.php" class="logo">
            <h2 class="logo">
                HiJinx
            </h2>
        </a>
        <div class="search-bar" style="position:relative;"> <!--Ensure this div has position: relative; in your CSS if not already  -->
            <i class="uil uil-search"></i>
            <input type="search" id="global-search-input" placeholder="Busca personas y comunidades"> 
            <div id="global-search-results-container" class="global-search-results"></div> 
        </div>
        <div class="create">
            <label id="btn-crear" class="btn btn-primary">Crear</label>
            <div class="profile-photo" style="cursor:pointer" id="user-top-photo">
                <img src="<?= (!empty($_SESSION['avatar']) && $_SESSION['avatar'] !== null)
                    ? '../assets/profile_pics/' . htmlspecialchars($_SESSION['avatar'])
                    : '../assets/profile_pics/default-profile.png' ?>" alt="User Avatar">
            </div>
        </div>
    </div>
</nav>
<!----------------- MODAL DE PERFIL --------------->
<div class="modal profile-modal">
    <div class="card">
        <ul>
            <li><a class="menu-item" href="profile-page.php?user=<?= htmlspecialchars($_SESSION['id_name'] ?? '') ?>">
                    <p>Ver Perfil</p>
                </a></li>
            <!-- <li><a class="menu-item" href="#">Configuración de Cuenta</a></li> -->
            <li><a class="menu-item" href="back-end/session-end.php">Cerrar Sesión</a></li>
        </ul>
    </div>
</div>
<div class="modal create-modal">
    <div class="card">
        <ul>
            <li id="nav-create-post"><a class="menu-item" href="#">Crear publicación</a></li>
            <li id="nav-create-community"><a class="menu-item" href="#">Crear Comunidad</a></li>
            <!-- <li><a class="menu-item" href="#">Subir historia</a></li> -->
        </ul>
    </div>
</div>
 <?php include 'modal-post.php';?>
 <?php include 'modal-community-create.php'; ?>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        /* ================== PROFILE MODAL ================== */
        const userTopPhoto = document.querySelector('#user-top-photo');
        const profileModal = document.querySelector('.profile-modal');

        const openProfileModal = () => {
            profileModal.style.display = 'grid';
            CreateModal.style.display = 'none';
        }
        const closeProfileModal = (e) => {
            if (e.target.classList.contains('profile-modal')) {
                profileModal.style.display = 'none';
            }
        }
        userTopPhoto.addEventListener('click', openProfileModal);
        profileModal.addEventListener('click', closeProfileModal);

        /* ================== CREATE MODAL ================== */
        const CreateModal = document.querySelector('.create-modal');
        const btnCreateModal = document.querySelector('#btn-crear');

        const openCreateModal = () => {
            CreateModal.style.display = 'grid';
            profileModal.style.display = 'none';
        }
        const closeCreateModal = (e) => {
            if (e.target.classList.contains('create-modal')) {
                CreateModal.style.display = 'none';
            }
        }
        btnCreateModal.addEventListener('click', openCreateModal);
        CreateModal.addEventListener('click', closeCreateModal);

        /* ================== GLOBAL SEARCH SCRIPT ================== */

        const searchInput = document.getElementById('global-search-input');
        const resultsContainer = document.getElementById('global-search-results-container');
        let debounceTimer;

        if (searchInput && resultsContainer) {
            searchInput.addEventListener('focus', () => {
                if (searchInput.value.trim().length > 1) {
                    fetchSearchResults(searchInput.value.trim());
                }
            });
            searchInput.addEventListener('input', () => {
                clearTimeout(debounceTimer);
                const searchTerm = searchInput.value.trim();

                if (searchTerm.length > 1) { // Only search if term is 2+ characters
                    debounceTimer = setTimeout(() => {
                        fetchSearchResults(searchTerm);
                    }, 300); // 300ms debounce
                } else {
                    resultsContainer.innerHTML = '';
                    resultsContainer.style.display = 'none';
                }
            });

            // Close dropdown if clicked outside
            document.addEventListener('click', (event) => {
                if (resultsContainer && !searchInput.contains(event.target) && !resultsContainer.contains(event.target)) {
                    resultsContainer.style.display = 'none';
                }
            });
        }

        async function fetchSearchResults(term) {
            if (!resultsContainer) return;
            try {
                const formData = new FormData();
                formData.append('action', 'global_search');
                formData.append('search_term', term);

                // Path to the AJAX handler (relative to the php/ folder where navbar.php is)
                const response = await fetch('back-end/search_handler.php', {
                    method: 'POST',
                    body: formData
                });

                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                const data = await response.json();
                displaySearchResults(data);
            } catch (error) {
                console.error('Error fetching search results:', error);
                resultsContainer.innerHTML = '<div class="search-result-item text-muted">Error al buscar.</div>';
                resultsContainer.style.display = 'block';
            }
        }

        function displaySearchResults(data) {
            if (!resultsContainer) return;
            resultsContainer.innerHTML = ''; // Clear previous results

            if ((!data.users || data.users.length === 0) && (!data.communities || data.communities.length === 0)) {
                resultsContainer.innerHTML = '<div class="search-result-item text-muted" style="padding: 0.5rem;">No se encontraron resultados.</div>';
                resultsContainer.style.display = 'block';
                return;
            }

            if (data.users && data.users.length > 0) {
                const usersHeader = document.createElement('h6');
                usersHeader.textContent = 'Personas';
                usersHeader.className = 'search-results-header';
                resultsContainer.appendChild(usersHeader);

                data.users.forEach(user => {
                    const item = document.createElement('a');
                    item.href = `profile-page.php?user=${encodeURIComponent(user.id_name)}`;
                    item.className = 'search-result-item user-result';
                    const profilePicSrc = (user.profile_picture && user.profile_picture !== 'null' && user.profile_picture !== '')
                        ? `../assets/profile_pics/${encodeURIComponent(user.profile_picture)}`
                        : '../assets/profile_pics/default-profile.png';

                    item.innerHTML = `
                        <div class="profile-photo small-profile-photo">
                            <img src="${profilePicSrc}" alt="${encodeURIComponent(user.username)}">
                        </div>
                        <div class="search-result-info">
                            <h5>${user.username}</h5>
                            <p class="text-muted">@${user.id_name}</p>
                        </div>
                    `;
                    resultsContainer.appendChild(item);
                });
            }

            if (data.communities && data.communities.length > 0) {
                if (data.users && data.users.length > 0) {
                    const separator = document.createElement('hr');
                    separator.className = 'search-results-separator';
                    resultsContainer.appendChild(separator);
                }
                const communitiesHeader = document.createElement('h6');
                communitiesHeader.textContent = 'Comunidades';
                communitiesHeader.className = 'search-results-header';
                resultsContainer.appendChild(communitiesHeader);

                data.communities.forEach(community => {
                    const item = document.createElement('a');
                    // IMPORTANT: You'll need to create 'community-page.php' or similar
                    // For now, this link might not lead to a working page.
                    item.href = `community-page.php?id=${encodeURIComponent(community.id)}`;
                    item.className = 'search-result-item community-result';
                    const communityPicSrc = (community.community_picture && community.community_picture !== 'null' && community.community_picture !== '')
                        ? `../assets/community_pics/${encodeURIComponent(community.community_picture)}` // Assumes folder 'community_pics'
                        : '../assets/profile_pics/default-profile.png'; // Default/placeholder icon

                    item.innerHTML = `
                        <div class="profile-photo small-profile-photo">
                            <img src="${communityPicSrc}" alt="${encodeURIComponent(community.name_comm)}">
                        </div>
                        <div class="search-result-info">
                            <h5>${community.name_comm}</h5>
                        </div>
                    `;
                    resultsContainer.appendChild(item);
                });
            }
            resultsContainer.style.display = 'block';
        }
    });
</script>