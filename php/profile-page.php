<?php
require_once 'back-end/connection.php';
include 'back-end/verified-session.php';
$db = new DBConnection();
$conn = $db->getConnection();

// Obtener el usuario desde la URL
$viewedUserId = $_GET['user'] ?? null;

if (!$viewedUserId) {
    echo "<script>alert('No se especificó un usuario.'); window.location.href = 'home-page.php';</script>";
    exit;
}

$stmt = $conn->prepare("SELECT * FROM users WHERE id_name = ?");
$stmt->execute([$viewedUserId]);
$userData = $stmt->fetch(PDO::FETCH_ASSOC);

if ($userData) {
    //  procesar los datos del usuario
}else{
    echo "<script>alert('El usuario no existe.'); window.location.href = 'home-page.php';</script>";
    exit;
}

// Verificamos si el perfil visitado es el mismo que el usuario en sesión
$isOwnProfile = isset($_SESSION['id_name']) && $_SESSION['id_name'] === $viewedUserId;
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HiJinx - Proyecto BDM y WEB II por Victor y Rebeca</title>
    <link href="https://fonts.googleapis.com/css2?family=Quantico&display=swap" rel="stylesheet">
    <!-- IconScout CDN -->
    <link rel="stylesheet" href="https://unicons.iconscout.com/release/v2.1.6/css/unicons.css">
    <!-- Stylesheet -->
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/profile.css">
    <script src="../js/loadTheme.js"></script>
</head>

<body>

    <!-- Navbar -->
    <?php include 'navbar.php'; ?>
    <!-- Modal Edit Profile -->
    <div class="modal" id="modal-edit-profile">
        <div class="modal-content">
            <span class="close" id="close-modal">&times;</span>
            <h2>Editar Perfil</h2>
            <form action="" method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <div class="section-1">
                        <h3>Foto de perfil:</h3>
                        <img class="edit-p-img" src="../assets/profile_pics/profile-1.png" alt="">
                        <div class="custom-file-input">
                            <input type="file" name="profile-pic" id="profile-pic" accept="image/*">
                            <label for="profile-pic">Subir nueva foto de perfil</label>
                        </div>
                    </div>
                    <div class="section-2">
                        <h3>Foto de portada:</h3>
                        <img class="edit-p-cover" src="../assets/cover-img/fondo2.png" alt="">
                        <div class="custom-file-input">
                            <input type="file" name="cover-pic" id="cover-pic" accept="image/*">
                            <label for="cover-pic">Subir nueva foto de portada</label>
                        </div>
                    </div>
                </div>
                <h3>Nombre de Usuario</h3>
                <input type="text" name="username" id="username" value="<?= htmlspecialchars($userData['username']) ?>" placeholder="Nombre de usuario" required>
                <h3>Biografía</h3>
                <textarea name="bio" id="bio" maxlength="100" placeholder="Escribe aquí..."></textarea>
                <h3>Detalles</h3>
                <p>Vive en <input type="text" name="location" id="location" placeholder="Ubicación"></p>
                <p>Estudió en <input type="text" name="school" id="school" placeholder="Escuela"></p><br>
                <input type="submit" class="btn-secondary" value="Guardar Cambios" />

            </form>
        </div>
    </div>
    <!-- Profile Page -->
    <div class="profile-page">
        <div class="container">
            <div class="top-cont">
                <div class="card">
                    <div class="banner">
                        <img src="../assets/cover-img/fondo2.png" alt="">
                    </div>
                    <div class="bottom">
                        <div class="profile-img">
                            <img src="../assets/profile_pics/profile-1.png">
                        </div>
                        <div class="card">
                            <div class="name">
                                <h2>Usuario</h2>
                                <p>@Usuario_gg</p>
                            </div>
                            <div class="actions">
                                <?php if ($isOwnProfile): ?>
                                    <button class="btn-secondary">Subir historia</button>
                                    <button id="edit-profile" class="btn-secondary">Editar Perfil</button>
                                <?php else: ?>
                                    <button class="btn-primary">Seguir</button>
                                    <button class="btn-secondary">Mensaje</button>
                                    <button class="btn-secondary">Bloquear</button>
                                <?php endif; ?>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="feed-container">
            <div class="left-cont">
                <div class="sidebar">
                    <div class="profile-info">
                        <h2>Detalles</h2>
                        <p>Vive en Cancún</p>
                        <p>Estudió en Universidad de Autónoma de Nuevo León</p>
                        <p>Nacío el 30 de Febrero de 2026</p>
                    </div>
                    <div class="profile-friends">
                        <h3>Amigos</h3>
                        <div class="friend">
                            <div class="profile-photo">
                                <img src="../assets/profile_pics/profile-2.jpg" alt="">
                            </div>
                            <div class="profile-photo">
                                <img src="../assets/profile_pics/profile-4.jpg" alt="">
                            </div>
                            <div class="profile-photo">
                                <img src="../assets/profile_pics/profile-6.png" alt="">
                            </div>
                            <div class="profile-photo">
                                <img src="../assets/profile_pics/profile-7.png" alt="">
                            </div>
                            <div class="profile-photo">
                                <img src="../assets/profile_pics/profile-3.jpg" alt="">
                            </div>
                            <div class="profile-photo">
                                <img src="../assets/profile_pics/profile-8.png" alt="">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="right-cont">
                <div class="profile-content">
                    <h3>Publicaciones</3>
                </div>
                <div class="feeds">
                    <!-------- Feed De Ejemplo -------->
                    <div class="feed">
                        <div class="head">
                            <div class="user">
                                <div class="profile-photo">
                                    <img src="../assets/profile_pics/profile-1.png">
                                </div>
                                <div class="info">
                                    <h3>Random</h3>
                                    <small>Hace 50 minutos</small>
                                </div>
                            </div>
                            <span class="edit">
                                <i class="uil uil-ellipsis-h"></i>
                            </span>
                        </div>
                        <div class="description">
                            <p>Achicopalado</p>
                        </div>
                        <div class="photo">
                            <img src="../assets/post_img/post-3.jpg">
                        </div>

                        <div class="action-buttons">
                            <div class="interaction-buttons">
                                <span><i class="uil uil-heart"></i></span>
                                <span><i class="uil uil-comment-dots"></i></span>
                                <span><i class="uil uil-share-alt"></i></span>
                            </div>
                            <div class="bookmark">
                                <span><i class="uil uil-bookmark-full"></i></span>
                            </div>
                        </div>

                        <div class="liked-by">
                            <span><img src="../assets/profile_pics/profile-12.jpg"></span>
                            <span><img src="../assets/profile_pics/profile-9.jpg"></span>
                            <span><img src="../assets/profile_pics/profile-2.jpg"></span>
                            <p>Le gusta a <b>Random</b> y <b>Otros 5,179</b></p>
                        </div>

                        <div class="caption">
                            <p><b>Random</b> yo ese
                                <span class="harsh-tag"></span>
                            </p>
                        </div>

                        <div class="comments text-muted">
                            Ver todos los 408 comentarios
                        </div>
                    </div>
                    <!-------- Fin del Feed -------->
                </div>
            </div>
        </div>
    </div>

</body>
<script>
    //  Arbrir el modal de edición de perfil
    document.querySelector('#edit-profile').addEventListener('click', function () {
        document.getElementById('modal-edit-profile').style.display = 'block';
    });
    // Cerrar el modal de edición de perfil
    document.querySelector('#close-modal').addEventListener('click', function () {
        document.getElementById('modal-edit-profile').style.display = 'none';
    });
</script>

</html>