<?php
require_once 'back-end/connection.php';
require_once 'back-end/verified-session.php'; // Asume que el usuario debe estar logueado para ver comunidades
$db = new DBConnection();
$conn = $db->getConnection();

// Obtener el ID de la comunidad desde la URL
$viewedCommunityId = $_GET['id'] ?? null; // Cambiado de 'user' a 'id'

if (!$viewedCommunityId || !is_numeric($viewedCommunityId)) { // Validar que sea numérico
    echo "<script>alert('No se especificó un ID de comunidad válido.'); window.location.href = 'home-page.php';</script>";
    exit;
}

// Llamar a sp_GetCommunityDetails para obtener la información de la comunidad
// y el estado de membresía del usuario actual.
$stmtCommunity = $conn->prepare("CALL sp_GetCommunityDetails(?, ?)");
$stmtCommunity->execute([$viewedCommunityId, $_SESSION['id_name']]);
$communityData = $stmtCommunity->fetch(PDO::FETCH_ASSOC);
$stmtCommunity->closeCursor();

if (!$communityData) {
    echo "<script>alert('La comunidad no existe o no se pudo cargar.'); window.location.href = 'home-page.php';</script>";
    exit;
}

// Variables para la vista (adapta según los nombres de columna de tu SP y tabla communities)
$communityID = htmlspecialchars($communityData['id']);
$communityName = htmlspecialchars($communityData['name_comm']);
$communityDescrip = nl2br(htmlspecialchars($communityData['descrip'])); // nl2br para saltos de línea
$communityCreatorId = htmlspecialchars($communityData['creator_id']);
$communityCreatorName = htmlspecialchars($communityData['creator_username']);
$memberCount = (int) ($communityData['member_count'] ?? 0);
$currentUserRole = $communityData['current_user_role'] ?? null; // 'admin', 'member', o null
$isMember = (bool) ($communityData['is_member'] ?? false);

$communityPic_URL = (!empty($communityData['community_picture']))
    ? '../assets/community_pics/' . htmlspecialchars($communityData['community_picture'])
    : '../assets/profile_pics/default-profile.png'; // Un default para comunidades

$communityCover_URL = (!empty($communityData['cover_picture']))
    ? '../assets/community_covers/' . htmlspecialchars($communityData['cover_picture'])
    : '../assets/cover_img/fondo2.png'; // Un default para portadas de comunidades

// Determinar si el usuario actual es el creador/admin para mostrar opciones de edición
// $isOwnerOrAdmin = ($_SESSION['id_name'] === $communityCreatorId || $currentUserRole === 'admin');
// Por ahora, simplifiquemos: solo el admin puede editar.
$canEditCommunity = ($currentUserRole === 'admin');

?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comunidad: <?= $communityName ?> - HiJinx</title>
    <link href="https://fonts.googleapis.com/css2?family=Quantico&display=swap" rel="stylesheet">
    <!-- IconScout CDN -->
    <link rel="stylesheet" href="https://unicons.iconscout.com/release/v2.1.6/css/unicons.css">
    <!-- Stylesheet -->
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/profile.css">
    <link rel="stylesheet" href="../css/messaging.css">
    <link rel="stylesheet" href="../css/search_bar.css">

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
            <div id="edit-profile-feedback" class="msg"
                style="display:none; padding: 10px; margin-bottom: 15px; border-radius: 5px;"></div>
            <form id="editProfileForm" action="back-end/modify-user.php" method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <div class="section-1">
                        <h3>Foto de perfil:</h3>
                        <img class="edit-p-img"
                            src="<?= $profilepic_URL ?? '../assets/profile_pics/default-profile.png' ?>"
                            alt="Profile Picture">
                        <div class="custom-file-input">
                            <input type="file" name="profile_pic" id="profile_pic_input" accept="image/*">
                            <label for="profile_pic_input">Subir nueva foto de perfil</label>
                        </div>
                    </div>
                    <div class="section-2">
                        <h3>Foto de portada:</h3>
                        <img class="edit-p-cover" src="<?= $profilecover_URL ?? '../assets/cover_img/fondo2.png' ?>"
                            alt="Cover Picture">
                        <div class="custom-file-input">
                            <input type="file" name="cover_pic" id="cover_pic_input" accept="image/*">
                            <label for="cover_pic_input">Subir nueva foto de portada</label>
                        </div>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group-inline">
                        <h3>Nombre Visible</h3>
                        <input type="text" name="username" id="edit_username" value="<?= $userName ?>"
                            placeholder="Nombre visible" required>
                        <small>Nombre que se mostrará en resultados, publicaciones, etc.</small>
                    </div>
                    <div class="form-group-inline">
                        <h3>Nombre Único (ID de usuario)</h3>
                        <input type="text" name="id_name" id="edit_id_name" value="<?= $userID ?>"
                            placeholder="Nombre único (ID)" required>
                        <small>Solo letras, números y guiones bajos. Sin espacios. 3-15 caracteres.</small>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group-inline">
                        <h3>Correo Electrónico</h3>
                        <input type="email" name="email" id="edit_email"
                            value="<?= htmlspecialchars($userData['email']) ?>" placeholder="E-mail" required>
                    </div>
                    <div class="form-group-inline">
                        <h3>Fecha de Nacimiento</h3>
                        <input type="date" name="birth" id="edit_birth"
                            value="<?= htmlspecialchars($userData['birth']) ?>" max="2007-01-01" min="1925-01-01"
                            required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group-inline">
                        <h3>Nueva Contraseña (opcional)</h3>
                        <input type="password" name="new_password" id="edit_new_password"
                            placeholder="Dejar en blanco para no cambiar">
                        <small>Mínimo 8 caracteres.</small>
                    </div>
                    <div class="form-group-inline">
                        <h3>Confirmar Nueva Contraseña</h3>
                        <input type="password" name="confirm_new_password" id="edit_confirm_new_password"
                            placeholder="Confirmar nueva contraseña">
                    </div>
                </div>

                <h3>Biografía</h3>
                <textarea name="bio" id="edit_bio" maxlength="255"
                    placeholder="Escribe aquí..."><?= htmlspecialchars($userData['biography'] ?? '') ?></textarea>

                <h3>Detalles</h3>
                <div class="form-row">
                    <div class="form-group-inline">
                        <p>Vive en <input type="text" name="location" id="edit_location"
                                value="<?= htmlspecialchars($userData['location'] ?? '') ?>" placeholder="Ubicación">
                        </p>
                    </div>
                    <div class="form-group-inline">
                        <p>Estudió en <input type="text" name="education" id="edit_education"
                                value="<?= htmlspecialchars($userData['education'] ?? '') ?>"
                                placeholder="Escuela/Educación">
                        </p>
                    </div>
                </div>
                <input type="hidden" name="profile_id_to_modify" value="<?= htmlspecialchars($viewedUserId) ?>">
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
                        <img src="<?= $communityCover_URL ?>" alt="Portada de la Comunidad">
                    </div>
                    <div class="bottom">
                        <div class="profile-img"> <img src="<?= $communityPic_URL ?>" alt="Icono de la Comunidad">
                        </div>
                        <div class="card">
                            <div class="name">
                                <h2><?= $communityName ?></h2>
                                <small>Comunidad creada por <a class="color: var(--color-dark);"
                                        href="profile-page.php?user=<?= $communityCreatorId ?>"><?= $communityCreatorName ?></a>
                                </sma>
                                <p class="text-muted"><?= $memberCount ?> miembro(s)</p>
                            </div>
                            <div class="actions">
                                <?php if ($canEditCommunity): ?>
                                    <span class="text-muted">Eres Administrador</span><br>
                                    <button id="edit-community-btn" class="btn-secondary">Editar Comunidad</button>
                                <?php endif; ?>

                                <?php if ($isMember): ?>
                                    <?php if ($currentUserRole !== 'admin'): // Los admins no deberían poder "abandonar" fácilmente si son el único ?>
                                        <button id="leave-community-btn" class="btn btn-danger"
                                            data-community-id="<?= $communityID ?>">Abandonar Comunidad</button>
                                    <?php else: ?>
                                    <!-- bruh -->
                                    <?php endif; ?>
                                <?php else: ?>
                                    <button id="join-community-btn" class="btn-primary"
                                        data-community-id="<?= $communityID ?>">Unirse a la Comunidad</button>
                                <?php endif; ?>

                                <button id="create-post-in-community-btn" class="btn btn-success"
                                    data-community-id="<?= $communityID ?>"
                                    data-community-name="<?= $communityName ?>">Crear Post Aquí</button>
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
                        <h2>Acerca de <?= $communityName ?></h2>
                        <p><?= $communityDescrip ?></p>
                        <hr>
                        <h4>Reglas (Ejemplo)</h4>
                        <ul class="text-muted" style="list-style: decimal; padding-left: 20px;">
                            <li>Ser respetuoso.</li>
                            <li>Contenido relevante a la comunidad.</li>
                            <li>No spam.</li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="right-cont">
                <div class="profile-content">
                    <h3>Publicaciones en <?= $communityName ?></h3>
                </div>
                <div class="feeds" id="community-feeds-container"> <?php
                // Carga inicial de posts (o puedes hacerlo enteramente con AJAX)
                require_once 'back-end/utils.php';
                $limit = 10;
                $offset = 0;
                // Llamar a sp_GetCommunityFeedPosts
                $stmt_feed = $conn->prepare("CALL sp_GetCommunityFeedPosts(?, ?, ?, ?)");
                $stmt_feed->execute([$_SESSION['id_name'], $viewedCommunityId, $limit, $offset]);
                $feed_posts = $stmt_feed->fetchAll(PDO::FETCH_ASSOC);
                $stmt_feed->closeCursor();

                if (count($feed_posts) > 0) {
                    foreach ($feed_posts as $post) {

                        // Copia y adapta aquí el HTML de cómo renderizas un post individual
                        // de home-page.php o profile-page.php.
                        // Asegúrate de que las variables ($post_id, $author_username, etc.)
                        // coincidan con las columnas devueltas por view_post_feed_details
                        // que usa sp_GetCommunityFeedPosts.
                
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
                                                <h3>
                                                    <?= $author_username ?>
                                                </h3>
                                            </a>
                                            <small>
                                                <?= $post_created_at ?>
                                            </small>
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
                                        <p>
                                            <?= $post_content ?>
                                        </p>
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
                                    <p><b class="like-count">
                                            <?= $post_likes ?>
                                        </b> persona(s) le gusta esto</p>
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
                    } // Fin foreach post
                } else {
                    echo "<p class='text-muted' style='text-align:center; padding: 2rem;'>Esta comunidad aún no tiene publicaciones.</p>";
                }
                ?>
                </div>
            </div>
        </div>
    </div>
    <script>
        // Variables globales para JS si son necesarias
        const currentCommunityId = <?= json_encode($viewedCommunityId); ?>;
        const currentLoggedInUserId = <?= json_encode($_SESSION['id_name'] ?? null); ?>;
        // loggedInUser ya está definido en el script global si se incluye post_actions.js
    </script>

</body>

<script src="../js/messaging.js"></script>
<script src="../js/community_page_actions.js"></script>
<script src="../js/post_actions.js"></script>

</html>