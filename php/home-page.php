<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">

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
    <link rel="stylesheet" href="../css/messaging.css">
</head>

<body>
    <?php include 'navbar.php'; ?>
    <!-------------------------------- MAIN ----------------------------------->
    <main>
        <div class="container">
            <!----------------- IZQUIERDA -------------------->
            <div class="left">
                <a class="profile" href="profile-page.php?user=<?= $_SESSION['id_name'] ?>"
                    style="text-decoration: none; color: var(--color-dark);">
                    <div class="profile-photo">
                        <img src="<?= (!empty($_SESSION['avatar']) && $_SESSION['avatar'] !== null)
                            ? '../assets/profile_pics/' . htmlspecialchars($_SESSION['avatar'])
                            : '../assets/profile_pics/default-profile.png' ?>" alt="">
                    </div>
                    <div class="handle">
                        <h4> <?php echo $_SESSION['username'] ?? "Usuario"; ?> </h4>
                        <p class="text-muted">
                            <?php echo $_SESSION['id_name'] ?? "Usuario_gg"; ?>
                        </p>
                    </div>
                </a>

                <!----------------- SIDEBAR -------------------->
                <div class="sidebar">
                    <a class="menu-item active">
                        <span><i class="uil uil-home"></i></span>
                        <h3>Inicio</h3>
                    </a>
                    <a class="menu-item">
                        <span><i class="uil uil-compass"></i></span>
                        <h3>Descubrir</h3>
                    </a>
                    <a class="menu-item" id="notifications">
                        <span><i class="uil uil-bell"><small class="notification-count">9+</small></i></span>
                        <h3>Notificaciones</h3>
                        <!--------------- BARRA DE NOTIS --------------->
                        <div class="notifications-popup">
                            <div>
                                <div class="profile-photo">
                                    <img src="../assets/profile_pics/profile-2.jpg" alt="">
                                </div>
                                <div class="notification-body">
                                    <b>Algun random</b> aceptó tu solicitud de amistad
                                    <small class="text-muted">Hace 2 día(s)</small>
                                </div>
                            </div>
                            <div>
                                <div class="profile-photo">
                                    <img src="../assets/profile_pics/profile-3.jpg">
                                </div>
                                <div class="notification-body">
                                    <b>Otro random</b> comentó en tu post
                                    <small class="text-muted">Hace 1 hora</small>
                                </div>
                            </div>
                            <div>
                                <div class="profile-photo">
                                    <img src="../assets/profile_pics/profile-4.jpg">
                                </div>
                                <div class="notification-body">
                                    <b>Random 3</b> y <b>Otros 283</b> les gustó tu post
                                    <small class="text-muted">Hace 4 minutos</small>
                                </div>
                            </div>
                            <div>
                                <div class="profile-photo">
                                    <img src="../assets/profile_pics/profile-5.jpeg">
                                </div>
                                <div class="notification-body">
                                    <b>Random 4</b> comentó en un post donde se te etiquetó
                                    <small class="text-muted">Hace 2 día(s)</small>
                                </div>
                            </div>
                            <div>
                                <div class="profile-photo">
                                    <img src="../assets/profile_pics/profile-6.png">
                                </div>
                                <div class="notification-body">
                                    <b>Random 5</b> comentó en un post donde se te etiquetó
                                    <small class="text-muted">Hace 1 hora</small>
                                </div>
                            </div>
                            <div>
                                <div class="profile-photo">
                                    <img src="../assets/profile_pics/profile-7.png">
                                </div>
                                <div class="notification-body">
                                    <b>Random 6</b> comento en un post donde se te etiquetó
                                    <small class="text-muted">Hace 1 hora</small>
                                </div>
                            </div>
                        </div>
                        <!--------------- SE TERMINA LA BARRA DE NOTIS --------------->
                    </a>
                    <a class="menu-item" id="messages-notifications">
                        <span><i class="uil uil-envelope-alt"><small class="notification-count">6</small></i></span>
                        <h3>Mensajes</h3>
                    </a>
                    <a class="menu-item">
                        <span><i class="uil uil-bookmark"></i></span>
                        <h3>Guardados</h3>
                    </a>
                    <a class="menu-item" id="theme">
                        <span><i class="uil uil-palette"></i></span>
                        <h3>Tema</h3>
                    </a>
                    <a class="menu-item" id="settings">
                        <span><i class="uil uil-setting"></i></span>
                        <h3>Ajustes</h3>
                    </a>
                </div>
                <!----------------- BARRA DE ALADO XD -------------------->
                <label class="btn btn-primary" for="create-post">Crear Post</label>
            </div>
            <!-----------------TOP PAGE -------------------->
            <div class="middle">
                <!----------------- HISTORIAS -------------------->
                <div class="stories">
                    <div class="story">
                        <div class="profile-photo">
                            <img src="../assets/profile_pics/profile-1.png">
                        </div>
                        <p class="name">Tu historia</p>
                    </div>
                    <div class="story">
                        <div class="profile-photo">
                            <img src="../assets/profile_pics/profile-2.jpg">
                        </div>
                        <p class="name">Random</p>
                    </div>
                    <div class="story">
                        <div class="profile-photo">
                            <img src="../assets/profile_pics/profile-7.png">
                        </div>
                        <p class="name">Random</p>
                    </div>
                    <div class="story">
                        <div class="profile-photo">
                            <img src="../assets/profile_pics/profile-4.jpg">
                        </div>
                        <p class="name">Random</p>
                    </div>
                    <div class="story">
                        <div class="profile-photo">
                            <img src="../assets/profile_pics/profile-6.png">
                        </div>
                        <p class="name">Random</p>
                    </div>
                    <div class="story">
                        <div class="profile-photo">
                            <img src="../assets/profile_pics/profile-3.jpg">
                        </div>
                        <p class="name">Random</p>
                    </div>
                </div>
                <!----------------- FIN DE HISTORIAS -------------------->
                <form action="" class="create-post">
                    <div class="profile-photo">
                        <img src="../assets/profile_pics/profile-1.png">
                    </div>
                    <input type="text" placeholder="Crea un post, usuario." id="create-post">
                    <input type="submit" value="Publicar" class="btn btn-primary">
                </form>
                <!----------------- FEEDS -------------------->
                <div class="feeds">
                    <!----------------- FEED 1 -------------------->
                    <div class="feed">
                        <div class="head">
                            <div class="user">
                                <div class="profile-photo">
                                    <img src="../assets/profile_pics/profile-2.jpg">
                                </div>
                                <div class="info">
                                    <a href="profile-page.php?user=BrandNew_gg">
                                        <h3>Brandonsito</h3>
                                    </a>
                                    <small>Monterrey, Nuevo León. Hace 15 minuto(s)</small>
                                </div>
                            </div>
                            <span class="edit">
                                <i class="uil uil-ellipsis-h"></i>
                            </span>
                        </div>
                        <div class="description">
                            <p>Así la ciudad de Monterrey esta mañana</p>
                        </div>
                        <div class="photo">
                            <img src="../assets/post_img/post-1.jpg">
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
                            <span><img src="../assets/profile_pics/profile-3.jpg"></span>
                            <span><img src="../assets/profile_pics/profile-4.jpg"></span>
                            <span><img src="../assets/profile_pics/profile-6.png"></span>
                            <p>Le gusta a <b>Random</b> y <b>Otros 2,342</b></p>
                        </div>

                        <div class="caption">
                            <p><b>Random</b> ta duro el asunto.
                                <span class="harsh-tag">#MeDuelesMTY</span>
                            </p>
                        </div>

                        <div class="comments text-muted">
                            Ver todos los 100 comentarios
                        </div>
                    </div>
                    <!----------------- FIN DE FEED 1 -------------------->

                    <!----------------- FEED 2 -------------------->
                    <div class="feed">
                        <div class="head">
                            <div class="user">
                                <div class="profile-photo">
                                    <img src="../assets/profile_pics/profile-4.jpg">
                                </div>
                                <div class="info">
                                    <h3>Random</h3>
                                    <small>Hace 2 horas</small>
                                </div>
                            </div>
                            <span class="edit">
                                <i class="uil uil-ellipsis-h"></i>
                            </span>
                        </div>
                        <div class="description">
                            <p></p>
                        </div>
                        <div class="photo">
                            <img src="../assets/post_img/post-2.jpg">
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
                            <span><img src="../assets/profile_pics/profile-11.jpg"></span>
                            <span><img src="../assets/profile_pics/profile-5.jpeg"></span>
                            <span><img src="../assets/profile_pics/profile-12.jpg"></span>
                            <p>Le gusta a <b>Random</b> y <b>Otros 1,323</b></p>
                        </div>

                        <div class="caption">
                            <p><b>Random</b> Yo viendo que saqué 2 en el examen que dije que estaba de agua.
                                <span class="harsh-tag"></span>
                            </p>
                        </div>

                        <div class="comments text-muted">
                            Ver todos los 50 comentarios
                        </div>
                    </div>
                    <!----------------- FIN DE FEED 2 -------------------->

                    <!----------------- FEED 3 -------------------->
                    <div class="feed">
                        <div class="head">
                            <div class="user">
                                <div class="profile-photo">
                                    <img src="../assets/profile_pics/profile-7.png">
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
                    <!----------------- FIN DE FEED 3 -------------------->
                </div>
                <!----------------- FIN DE FEEDS -------------------->
            </div>
            <!----------------- FIN DE MEDIO -------------------->

            <!----------------- DERECHA -------------------->
            <div class="right">
                <!------- MENSAJES ------->
                <div class="messages">
                    <div class="heading">
                        <h4>Mensajes</h4>
                        <i class="uil uil-edit"></i>
                    </div>
                    <!------- BARRA BUSQUEDA ------->
                    <div class="search-bar">
                        <i class="uil uil-search"></i>
                        <input type="search" placeholder="Buscar mensajes" id="message-search">
                    </div>
                    <!------- MENSAJES CATEGORIA ------->
                    <div class="category">
                        <h6 class="active">Principal</h6>
                        <h6>General</h6>
                        <h6 class="message-requests">solicitudes (7)</h6>
                    </div>
                </div>
                <!------- FIN DE MENSAJES ------->

                <!------- SOLICITUDES ------->
                <div class="friend-requests">
                    <h4>Solicitudes de amistad</h4>
                    <div class="request">
                        <div class="info">
                            <div class="profile-photo">
                                <img src="../assets/profile_pics/profile-12.jpg">
                            </div>
                            <div>
                                <h5>Random</h5>
                                <p class="text-muted">8 amigos en común</p>
                            </div>
                        </div>
                        <div class="action">
                            <button class="btn btn-primary">
                                Aceptar
                            </button>
                            <button class="btn">
                                Rechazar
                            </button>
                        </div>
                    </div>
                    <div class="request">
                        <div class="info">
                            <div class="profile-photo">
                                <img src="../assets/profile_pics/profile-11.jpg">
                            </div>
                            <div>
                                <h5>Random</h5>
                                <p class="text-muted">2 amigos en común</p>
                            </div>
                        </div>
                        <div class="action">
                            <button class="btn btn-primary">
                                Aceptar
                            </button>
                            <button class="btn">
                                Rechazar
                            </button>
                        </div>
                    </div>
                    <div class="request">
                        <div class="info">
                            <div class="profile-photo">
                                <img src="../assets/profile_pics/profile-9.jpg">
                            </div>
                            <div>
                                <h5>Random</h5>
                                <p class="text-muted">5 amigos en común</p>
                            </div>
                        </div>
                        <div class="action">
                            <button class="btn btn-primary">
                                Aceptar
                            </button>
                            <button class="btn">
                                Rechazar
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <!----------------- FIN DE DERECHA -------------------->
        </div>
    </main>

    <!----------------- CUSTOM DEL TEMA -------------------->
    <div class="customize-theme">
        <div class="card">
            <h2>Personaliza la apariencia</h2>
            <p class="text-muted">Elige el tamaño de la fuente, color y fondo</p>

            <!----------- TAMANO FUENTE ----------->
            <div class="font-size">
                <h4>Tamaño de la fuente</h4>
                <div>
                    <h6>Aa</h6>
                    <div class="choose-size">
                        <span class="font-size-1"></span>
                        <span class="font-size-2 active"></span>
                        <span class="font-size-3"></span>
                        <span class="font-size-4"></span>
                        <span class="font-size-5"></span>
                    </div>
                    <h3>Aa</h3>
                </div>
            </div>

            <!----------- COLORES PRIMARIOS ----------->
            <div class="color">
                <h4>Color</h4>
                <div class="choose-color">
                    <span class="color-1 active"></span>
                    <span class="color-2"></span>
                    <span class="color-3"></span>
                    <span class="color-4"></span>
                    <span class="color-5"></span>
                </div>
            </div>

            <!----------- FONDO ----------->
            <div class="background">
                <h4>Fondo</h4>
                <div class="choose-bg">
                    <div class="bg-1 active">
                        <span></span>
                        <h5 for="bg-1">Claro</h5>
                    </div>
                    <div class="bg-2">
                        <span></span>
                        <h5 for="bg-2">Nocturno</h5>
                    </div>
                    <div class="bg-3">
                        <span></span>
                        <h5 for="bg-3">Oscuro</h5>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!----------------- MODAL DE AJUSTES --------------->
    <div class="settings-modal">
        <div class="card">
            <h2>Ajustes</h2><br>
            <ul>
                <li>
                    <p>Configuración de Notificaciones</p>
                </li>
                <li>
                    <p>Administrar Bloqueos/Reportes</p>
                </li>
                <li>
                    <p>Ver actividad Reciente</p>
                </li>
                <li onclick="location='back-end/session-end.php'">
                    <p>Cerrar Sesión</p>
                </li>
            </ul>
        </div>
    </div>
    <script src="../js/homepage.js"></script>
    <script>
        const currentLoggedInUserId = "<?php echo htmlspecialchars($_SESSION['id_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>";
        const loggedInUser = {
            id_name: "<?php echo htmlspecialchars($_SESSION['id_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>",
            username: "<?php echo htmlspecialchars($_SESSION['username'] ?? '', ENT_QUOTES, 'UTF-8'); ?>",
            // Ensure 'avatar' is the correct session variable key for the profile picture filename
            avatar: "<?php echo htmlspecialchars($_SESSION['avatar'] ?? 'default-profile.png', ENT_QUOTES, 'UTF-8'); ?>"
        };
    </script>
    <script src="../js/messaging.js"></script>
</body>

</html>