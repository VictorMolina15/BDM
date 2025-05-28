<nav>
    <div class="container">
        <a href="home-page.php" class="logo">
        <h2 class="logo">
            HiJinx
        </h2>
        </a>
        <div class="search-bar">
            <i class="uil uil-search"></i>
            <input type="search" placeholder="Busca personas, comunidades, posts, etc.">
        </div>
        <div class="create">
            <label id="btn-crear" class="btn btn-primary">Crear</label>
            <div class="profile-photo" style="cursor:pointer" id="user-top-photo">
                <img src="<?=(!empty($_SESSION['avatar']) && $_SESSION['avatar'] !== null) 
                ? '../assets/profile_pics/' . htmlspecialchars($_SESSION['avatar']) 
                : '../assets/profile_pics/default-profile.png' ?>" alt="">
            </div>
        </div>
    </div>
</nav>
<!----------------- MODAL DE PERFIL --------------->
<div class="modal profile-modal">
    <div class="card">
        <ul>
            <li><a class="menu-item" href="profile-page.php?user=<?= $_SESSION['id_name'] ?>">
                    <p>Ver Perfil</p>
                </a></li>
            <li><a class="menu-item" href="#">Configuración de Cuenta</a></li>
            <li><a class="menu-item" href="back-end/session-end.php">Cerrar Sesión</a></li>
        </ul>
    </div>
</div>
<div class="modal create-modal">
    <div class="card">
        <ul>
            <li><a class="menu-item" href="#">Crear publicación</a></li>
            <li><a class="menu-item" href="#">Crear Comunidad</a></li>
            <li><a class="menu-item" href="#">Subir historia</a></li>
        </ul>
    </div>
</div>
<script>
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
</script>