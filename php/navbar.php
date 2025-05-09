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
            <label class="btn btn-primary" for="create-post">Crear</label>
            <div class="profile-photo" style="cursor:pointer" id="user-top-photo">
                <img src="../assets/profile_pics/profile-1.png" alt="">
            </div>
        </div>
    </div>
</nav>
<!----------------- MODAL DE PERFIL --------------->
<div class="profile-modal">
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
<script>
    //Profile photo
    const userTopPhoto = document.querySelector('#user-top-photo');
    const profileModal = document.querySelector('.profile-modal');//modal perfil
    /* ================== PROFILE MODAL ================== */
    const openProfileModal = () => {
        profileModal.style.display = 'grid';
    }
    const closeProfileModal = (e) => {
        if (e.target.classList.contains('profile-modal')) {
            profileModal.style.display = 'none';
        }
    }
    userTopPhoto.addEventListener('click', openProfileModal);
    profileModal.addEventListener('click', closeProfileModal);
</script>