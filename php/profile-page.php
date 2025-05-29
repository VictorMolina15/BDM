<?php require_once 'back-end/connection.php';
require_once 'back-end/verified-session.php';
$db = new DBConnection();
$conn = $db->getConnection();

// Obtener el usuario desde la URL
$viewedUserId = $_GET['user'] ?? null;

if (!$viewedUserId) {
    echo "
    <script>alert('No se especificó un usuario.'); window.location.href = 'home-page.php';</script>";
    exit;
}

$stmt = $conn->prepare("SELECT * FROM users WHERE id_name = ?");
$stmt->execute([$viewedUserId]);
$userData = $stmt->fetch(PDO::FETCH_ASSOC);

if ($userData) {
    // procesar los datos del usuario

    $userID = htmlspecialchars($userData['id_name']);
    $userName = htmlspecialchars($userData['username']);
    $profilepic_URL = (!empty($userData['profile_picture']) && $userData['profile_picture'] !== null)
        ? '../assets/profile_pics/' . htmlspecialchars($userData['profile_picture'])
        : '../assets/profile_pics/default-profile.png';
    $profilecover_URL = (!empty($userData['cover_picture']) && $userData['cover_picture'] !== null)
        ? '../assets/cover_img/' . htmlspecialchars($userData['cover_picture'])
        : '../assets/cover_img/fondo2.png';
} else {
    echo "
    <script>alert('El usuario no existe.'); window.location.href = 'home-page.php';</script>";
    exit;
}

// Verificamos si el perfil visitado es el mismo que el usuario en sesión
$isOwnProfile = isset($_SESSION['id_name']) && $_SESSION['id_name'] === $viewedUserId;
$friendship_status = 'loading';

// Procesar mensajes de feedback de la sesión
$editMsg = null;
$editMsgType = null;
if (isset($_SESSION['edit_feedback_msg'])) {
    $editMsg = $_SESSION['edit_feedback_msg'];
    $editMsgType = $_SESSION['edit_feedback_type'] ?? 'error'; // Asume error si no se especifica
    unset($_SESSION['edit_feedback_msg']);
    unset($_SESSION['edit_feedback_type']);

    // Prepara el script para mostrar el modal con el mensaje
    echo "
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const feedbackDiv = document.getElementById('edit-profile-feedback');
            if (feedbackDiv) {
                feedbackDiv.innerHTML = '" . addslashes($editMsg) . "';
                feedbackDiv.className = 'msg " . htmlspecialchars($editMsgType) . "'; // Aplicar clases para estilo
                feedbackDiv.style.backgroundColor = '" . ($editMsgType === "success" ? "#d4edda" : "#f8d7da") . "';
                feedbackDiv.style.color = '" . ($editMsgType === "success" ? "#155724" : "#721c24") . "';
                feedbackDiv.style.display = 'block';
                const modal = document.getElementById('modal-edit-profile');
                if (modal) modal.style.display = 'block'; // Asegúrate que el modal se muestre
            }
        });
    </script>";
}

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
                        <img class="edit-p-cover" src="<?= $profilecover_URL ?? '../assets/cover-img/fondo2.png' ?>"
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
                        <img src="<?= $profilecover_URL ?>" alt="">
                    </div>
                    <div class="bottom">
                        <div class="profile-img">
                            <img src="<?= $profilepic_URL ?>">
                        </div>
                        <div class="card">
                            <div class="name">
                                <h2><?= $userName ?></h2>
                                <p>@<?= $userID ?></p>
                            </div>
                            <div class="actions">
                                <?php if ($isOwnProfile): ?>
                                    <button class="btn-secondary">Subir historia</button>
                                    <button id="edit-profile" class="btn-secondary">Editar Perfil</button>
                                <?php else: ?>
                                    <button id="friendship-action-btn" class="btn-primary"
                                        data-profile-id="<?= htmlspecialchars($userData['id_name']) ?>">
                                        Cargando...
                                    </button>
                                    <button id="initiate-chat-btn-profile" class="btn-secondary"
                                        data-other-user-id="<?= htmlspecialchars($userData['id_name']) ?>"
                                        data-other-user-name="<?= htmlspecialchars($userData['username']) ?>"
                                        data-other-user-avatar="<?= htmlspecialchars($userData['profile_picture'] ?? '') ?>">
                                        Mensaje
                                    </button>
                                    <button id="block-user-profile-btn" class="btn btn-danger"
                                        data-profile-id="<?= htmlspecialchars($userData['id_name']) ?>">
                                        Bloquear Usuario
                                    </button>
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
                        <p> <?= htmlspecialchars($userData['biography']) ?></p>
                        <p>Vive en <?= htmlspecialchars($userData['location']) ?></p>
                        <p>Estudió en <?= htmlspecialchars($userData['education']) ?></p>
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
    // Abrir el modal de edición de perfil
    const editProfileButton = document.querySelector('#edit-profile');
    const modalEditProfile = document.getElementById('modal-edit-profile');
    const closeModalButton = document.querySelector('#close-modal');
    const feedbackDiv = document.getElementById('edit-profile-feedback'); // Para limpiar al abrir

    if (editProfileButton) {
        editProfileButton.addEventListener('click', function () {
            modalEditProfile.style.display = 'block';
            feedbackDiv.style.display = 'none'; // Ocultar mensajes previos al abrir
            feedbackDiv.textContent = '';
        });
    }

    if (closeModalButton) {
        closeModalButton.addEventListener('click', function () {
            modalEditProfile.style.display = 'none';
        });
    }

    // Cerrar el modal si se hace clic fuera de él
    window.addEventListener('click', function (event) {
        if (event.target == modalEditProfile) {
            modalEditProfile.style.display = 'none';
        }
    });
    const profilePicInput = document.getElementById('profile_pic_input');
    const profilePicPreview = document.querySelector('.edit-p-img'); // Imagen de perfil en el modal

    const coverPicInput = document.getElementById('cover_pic_input');
    const coverPicPreview = document.querySelector('.edit-p-cover'); // Imagen de portada en el modal

    if (profilePicInput && profilePicPreview) {
        profilePicInput.addEventListener('change', function (event) {
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function (e) {
                    profilePicPreview.src = e.target.result;
                }
                reader.readAsDataURL(file);
            } else {
                // Opcional: Restablecer a la imagen original si se cancela la selección
                profilePicPreview.src = "<?= $profilepic_URL ?>";
            }
        });
    }

    if (coverPicInput && coverPicPreview) {
        coverPicInput.addEventListener('change', function (event) {
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function (e) {
                    coverPicPreview.src = e.target.result;
                }
                reader.readAsDataURL(file);
            } else {
                // Opcional: Restablecer
                coverPicPreview.src = "<?= $profilecover_URL ?>";
            }
        });
    }
</script>
<script>
    const currentLoggedInUserId = "<?php echo htmlspecialchars($_SESSION['id_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>";
    const loggedInUser = {
        id_name: "<?php echo htmlspecialchars($_SESSION['id_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>",
        username: "<?php echo htmlspecialchars($_SESSION['username'] ?? '', ENT_QUOTES, 'UTF-8'); ?>",
        // Ensure 'avatar' is the correct session variable key for the profile picture filename
        avatar: "<?php echo htmlspecialchars($_SESSION['avatar'] ?? 'default-profile.png', ENT_QUOTES, 'UTF-8'); ?>"
    };

    const friendshipBtn = document.getElementById('friendship-action-btn');
    const loggedInUserId = "<?php echo htmlspecialchars($_SESSION['id_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>";

    function updateFriendshipButton(status, profileId) {
        if (!friendshipBtn) return;
        friendshipBtn.disabled = false;
        friendshipBtn.textContent = 'Error al cargar estado'; // Default

        switch (status) {
            case 'friends':
                friendshipBtn.textContent = 'Amigos';
                friendshipBtn.className = 'btn btn-secondary'; // Cambia clase si son amigos
                friendshipBtn.disabled = true;
                break;
            case 'request_sent': // El usuario actual envió una solicitud
                friendshipBtn.textContent = 'Solicitud Enviada';
                friendshipBtn.className = 'btn btn-secondary';
                friendshipBtn.disabled = true; // O permitir cancelar solicitud
                break;
            case 'request_received': // El usuario actual recibió una solicitud de este perfil
                friendshipBtn.textContent = 'Responder Solicitud';
                friendshipBtn.className = 'btn btn-success'; // O alguna clase distintiva
                // Podrías aquí añadir lógica para mostrar botones de Aceptar/Rechazar
                // o redirigir/mostrar un modal. Por ahora, solo texto.
                // Para responder, usualmente se haría desde la lista de solicitudes.
                // Si quieres manejarlo aquí, necesitarías dos botones o un dropdown.
                // Por simplicidad, asumimos que "Responder" es un indicador.
                // O mejor, mostrar "Aceptar Solicitud"
                // friendshipBtn.textContent = 'Aceptar Solicitud';
                // friendshipBtn.onclick = () => handleFriendAction('accept_friend_request', profileId);
                // O simplemente indicar que tiene una solicitud pendiente de este usuario
                friendshipBtn.textContent = 'Solicitud Recibida';
                friendshipBtn.disabled = true; // No se acciona desde aquí directamente.
                break;
            case 'not_friends':
            default:
                friendshipBtn.textContent = 'Agregar Amigo';
                friendshipBtn.className = 'btn btn-primary';
                friendshipBtn.onclick = () => handleFriendAction('send_friend_request', profileId);
                break;
        }
    }

    async function handleFriendAction(action, targetUserId) {
        if (friendshipBtn) friendshipBtn.disabled = true;

        const formData = new FormData();
        formData.append('action', action);
        if (action === 'send_friend_request') {
            formData.append('receiver_id', targetUserId);
        } else { // Para accept/reject, el targetUserId es el requester_id
            formData.append('requester_id', targetUserId);
        }

        try {
            const response = await fetch('back-end/friend_actions_ajax.php', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();

            if (result.status === 'success') {
                // Actualizar el botón basado en el nuevo estado si está disponible
                if (result.new_friendship_status) {
                    updateFriendshipButton(result.new_friendship_status, targetUserId);
                } else if (action === 'send_friend_request') {
                    updateFriendshipButton('request_sent', targetUserId); // Asumir que se envió
                }
                // Podrías mostrar un mensaje de éxito pequeño si lo deseas
                // alert(result.message); 
            } else {
                alert('Error: ' + result.message);
                if (friendshipBtn) friendshipBtn.disabled = false; // Re-habilitar si falló
            }
        } catch (error) {
            console.error('Error en la acción de amistad:', error);
            alert('Ocurrió un error al procesar la solicitud.');
            if (friendshipBtn) friendshipBtn.disabled = false;
        }
    }

    function fetchFriendshipStatus() {
        if (!friendshipBtn || loggedInUserId === friendshipBtn.dataset.profileId) {
            if (friendshipBtn && loggedInUserId === friendship_btn.dataset.profileId) friendshipBtn.style.display = 'none'; // No mostrar botón en propio perfil
            return;
        }

        const profileId = friendshipBtn.dataset.profileId;
        if (!profileId) return;

        friendshipBtn.disabled = true;
        friendshipBtn.textContent = 'Cargando...';

        const formData = new FormData();
        formData.append('action', 'get_friendship_status');
        formData.append('profile_id', profileId);

        fetch('back-end/friend_actions_ajax.php', {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success' && data.data && data.data.friendship_status) {
                    updateFriendshipButton(data.data.friendship_status, profileId);
                } else {
                    updateFriendshipButton('not_friends', profileId); // Fallback
                    console.error("Error fetching status or status not found: ", data.message);
                }
            })
            .catch(error => {
                console.error('Error al obtener estado de amistad:', error);
                updateFriendshipButton('not_friends', profileId); // Fallback en caso de error de red
            });
    }

    if (!<?php echo json_encode($isOwnProfile); ?>) { // Solo ejecutar si no es el perfil propio
        fetchFriendshipStatus();
    }
    const blockUserProfileBtn = document.getElementById('block-user-profile-btn');

    if (blockUserProfileBtn) {
        blockUserProfileBtn.addEventListener('click', function() {
            const profileId = this.dataset.profileId;
            const reason = prompt("Motivo del bloqueo/reporte (opcional):");
            // No enviar si el usuario cancela el prompt
            if (reason === null) {
                return;
            }
            
            const formData = new FormData();
            formData.append('action', 'block_user');
            formData.append('reported_user_id', profileId);
            if (reason) { // Solo añadir si no es vacío
                formData.append('reason', reason);
            }

            fetch('back-end/block_report_ajax.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(result => {
                alert(result.message);
                if (result.status === 'success') {
                    // Podrías cambiar el texto del botón o deshabilitarlo
                    this.textContent = 'Usuario Bloqueado';
                    this.disabled = true;
                }
            })
            .catch(error => {
                console.error('Error al bloquear usuario:', error);
                alert('Error de red al bloquear usuario.');
            });
        });
    }
</script>
<script src="../js/messaging.js"></script>

</html>