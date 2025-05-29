<?php
session_start();
require_once 'back-end/verified-session.php'
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
    <link rel="stylesheet" href="../css/search_bar.css">
</head>

<body>
    <?php include 'navbar.php'; ?>
    <!-------------------------------- MAIN ----------------------------------->
    <section id="Inicio"></section>
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
                    <a href="#Inicio" class="menu-item active" style="color: var(--color-dark);">
                        <span><i class="uil uil-home"></i></span>
                        <h3>Inicio</h3>
                    </a>
                    <a class="menu-item" id="messages-notifications">
                        <span><i class="uil uil-envelope-alt"></i></span>
                        <h3>Mensajes</h3>
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
                <!-- <div class="stories">

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
                </div> -->
                <!----------------- FIN DE HISTORIAS -------------------->
                <!-- <form action="" class="create-post">
                    <div class="profile-photo">
                        <img src="../assets/profile_pics/profile-1.png">
                    </div>
                    <input type="text" placeholder="Crea un post, usuario." id="create-post">
                    <input type="submit" value="Publicar" class="btn btn-primary">
                </form> -->
                <!----------------- FEEDS -------------------->
                <div class="feeds">
                    <!----------------- FEED DINÁMICO ---------------->
                    <?php
                    require_once 'back-end/connection.php';
                    require_once 'back-end/utils.php';
                    $profile_to_view = null; // For general feed
                    // For profile-page.php, this would be: $profile_to_view = $viewedUserId;
                    
                    $limit = 10; // Or however many posts per page
                    $offset = 0; // Implement pagination later if needed
                    $db = new DBConnection();
                    $conn = $db->getConnection();
                    $stmt_feed = $conn->prepare("CALL sp_GetFeedPosts(?, ?, ?, ?)");
                    $stmt_feed->execute([$_SESSION['id_name'], $profile_to_view, $limit, $offset]);
                    $feed_posts = $stmt_feed->fetchAll(PDO::FETCH_ASSOC);
                    $stmt_feed->closeCursor();

                    if (count($feed_posts) > 0) {
                        foreach ($feed_posts as $post) {
                            $post_id = htmlspecialchars($post['post_id']);
                            $author_id_name = htmlspecialchars($post['author_id']);
                            $author_username = htmlspecialchars($post['author_username']);
                            $author_avatar_filename = $post['author_avatar'] ?? 'default-profile.png';
                            $author_avatar_url = '../assets/profile_pics/' . htmlspecialchars($author_avatar_filename);
                            $post_content = nl2br(htmlspecialchars($post['content'])); // nl2br to respect newlines
                            $post_likes = (int) $post['likes'];
                            $post_created_at = formatTimeAgo($post['created_at']);

                            $media_html = '';
                            if (!empty($post['media_path'])) {
                                $media_url = '../assets/post_media/' . htmlspecialchars($post['media_path']);
                                if ($post['media_type'] === 'image') {
                                    $media_html = "<div class=\"photo\"><img src=\"{$media_url}\" alt=\"Post media\"></div>";
                                } elseif ($post['media_type'] === 'video') {
                                    $media_html = "<div class=\"photo\"><video controls src=\"{$media_url}\" style=\"width:100%; border-radius: var(--card-border-radius);\"></video></div>";
                                }
                            }
                            // Verificar si el usuario actual ha dado "Me gusta" a este post
                            $user_has_liked_post_stmt = $conn->prepare("SELECT func_HasUserLikedPost(:user_id, :post_id) AS has_liked");
                            $user_has_liked_post_stmt->execute(['user_id' => $_SESSION['id_name'], 'post_id' => $post['post_id']]);
                            $like_status = $user_has_liked_post_stmt->fetch(PDO::FETCH_ASSOC);
                            $user_has_liked_this_post = (bool) ($like_status['has_liked'] ?? false);
                            $user_has_liked_post_stmt->closeCursor();
                            ?>
                            <div class="feed" data-post-id="<?= $post_id ?>">
                                <div class="head">
                                    <div class="user">
                                        <div class="profile-photo">
                                            <img src="<?= $author_avatar_url ?>" alt="<?= $author_username ?>">
                                        </div>
                                        <div class="info">
                                            <a href="profile-page.php?user=<?= $author_id_name ?>"
                                                style="color: var(--color-dark); text-decoration: none;">
                                                <h3><?= $author_username ?></h3>
                                            </a>
                                            <small><?= $post_created_at ?></small>
                                        </div>
                                    </div>
                                    <span class="edit">
                                        <i class="uil uil-ellipsis-h"></i>
                                        <ul class="edit-menu"
                                            style="display:none; position:absolute; background:var(--color-white); border-radius:var(--card-border-radius); box-shadow: 0 0 5px rgba(0,0,0,0.1); padding: 5px; right:0; top:100%; z-index:10;">
                                            <?php if ($post['author_id'] === $_SESSION['id_name']): ?>
                                            <?php else: ?>
                                                <li style="padding: 5px 10px; cursor:pointer;" class="block-post-option"
                                                    data-post-id="<?= $post_id ?>">Bloquear Publicación</li>
                                            <?php endif; ?>
                                        </ul>
                                    </span>
                                </div>
                                <?php if (!empty($post_content)): ?>
                                    <div class="description">
                                        <p><?= $post_content ?></p>
                                    </div>
                                <?php endif; ?>
                                <?= $media_html ?>

                                <div class="action-buttons">
                                    <div class="interaction-buttons">
                                        <span class="like-btn" data-post-id="<?= $post_id ?>" title="Me gusta">
                                            <i class="uil <?= $user_has_liked_this_post ? 'uil-heart' : 'uil-heart-alt' ?>"
                                                style="color: <?= $user_has_liked_this_post ? 'var(--color-danger)' : 'inherit' ?>;"></i>
                                        </span>
                                        <span class="comment-btn" data-post-id="<?= $post_id ?>" title="Comentar">
                                            <i class="uil uil-comment-dots"></i>
                                        </span>
                                    </div>
                                </div>

                                <div class="liked-by" data-post-id="<?= $post_id ?>">
                                    <p><b class="like-count"><?= $post_likes ?></b> persona(s) le gusta esto</p>
                                </div>

                                <div class="comments-section" data-post-id="<?= $post_id ?>" style="margin-top:10px;">
                                    <div class="existing-comments">
                                    </div>
                                    <button class="view-more-comments-btn btn text-muted" data-post-id="<?= $post_id ?>"
                                        data-offset="0" style="display:none; margin-top:5px; font-size: 0.8rem;">Ver más
                                        comentarios</button>
                                    <form class="comment-form" data-post-id="<?= $post_id ?>"
                                        style="margin-top: 10px; display: flex; gap: 5px;">
                                        <input type="text" name="comment_content" class="comment-input"
                                            placeholder="Escribe un comentario..."
                                            style="flex-grow: 1; padding: 8px; border: 1px solid var(--color-grey); border-radius: 20px; font-size:0.85rem;">
                                        <button type="submit" class="btn btn-primary"
                                            style="padding: 8px 12px; font-size:0.85rem;">Enviar</button>
                                    </form>
                                </div>
                            </div>
                            <?php
                        } // End foreach
                    } else {
                        echo "<p class='text-muted' style='text-align:center; padding: 2rem;'>No hay publicaciones para mostrar.</p>";
                    }
                    ?>

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
                        <input type="search" placeholder="Buscar usuarios" id="message-search">
                    </div>
                    <!------- MENSAJES CATEGORIA ------->
                    <div class="category">
                    </div>
                </div>
                <!------- FIN DE MENSAJES ------->

                <!------- SOLICITUDES ------->
                <div class="friend-requests">
                    <h4>Solicitudes de amistad</h4>
                    <div id="pending-requests-container">
                        <p class="text-muted" style="padding: 1rem;">Cargando solicitudes...</p>
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
                <li id="manage-blocks-link">
                    <p>Administrar Bloqueos/Reportes</p>
                </li>
                <li id="view-activity-report-link">
                    <p>Ver actividad Reciente</p>
                </li>
                <li onclick="location='back-end/session-end.php'">
                    <p>Cerrar Sesión</p>
                </li>
            </ul>
        </div>
    </div>
    <!----------------- MODAL DE BLOQUEOS/REPORTES --------------->
    <div class="modal" id="blocks-reports-modal" style="z-index: 1005; position: fixed;">
        <div class="card" style="width: 60%; max-width: 700px; padding: 20px; top: auto; right: auto;">
            <div class="modal-header"
                style="display: flex; justify-content: space-between; align-items: center; padding-bottom: 10px; border-bottom: 1px solid var(--color-light); margin-bottom:15px;">
                <h2>Mis Bloqueos / Reportes</h2>
                <span class="close-modal-btn" data-modal-id="blocks-reports-modal"
                    style="font-size: 1.8rem; cursor: pointer; font-weight:bold;">&times;</span>
            </div>
            <div id="blocks-reports-list-container" style="max-height: 400px; overflow-y: auto; padding-right:10px;">
                <p class="text-muted">Cargando...</p>
            </div>
        </div>
    </div>

    <script>
        const currentLoggedInUserId = "<?php echo htmlspecialchars($_SESSION['id_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>";
        const loggedInUser = {
            id_name: "<?php echo htmlspecialchars($_SESSION['id_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>",
            username: "<?php echo htmlspecialchars($_SESSION['username'] ?? '', ENT_QUOTES, 'UTF-8'); ?>",
            // Ensure 'avatar' is the correct session variable key for the profile picture filename
            avatar: "<?php echo htmlspecialchars($_SESSION['avatar'] ?? 'default-profile.png', ENT_QUOTES, 'UTF-8'); ?>"
        };
        const viewActivityReportLink = document.getElementById('view-activity-report-link');
        if (viewActivityReportLink) {
            viewActivityReportLink.addEventListener('click', (event) => {
                event.preventDefault(); // Prevenir comportamiento por defecto si fuera un <a>

                const settingsModal = document.querySelector('.settings-modal');
                if (settingsModal) {
                    settingsModal.style.display = 'none'; // Ocultar el modal de ajustes
                }

                // Abrir el script PHP que generará y servirá el PDF en una nueva pestaña
                window.open('back-end/generate_activity_report.php', '_blank');
            });
        }
    </script>
    <script src="../js/homepage.js"></script>
    <script src="../js/messaging.js"></script>
    <script src="../js/friend_request.js"></script>
    <script src="../js/block_reports.js"></script>
    <script src="../js/post_actions.js"></script>

</body>

</html>