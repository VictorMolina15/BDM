<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'connection.php'; //

// Verificar si el usuario está logueado (puedes usar tu script verified-session.php o una verificación similar)
if (!isset($_SESSION['id_name'])) {
    // Guardar un mensaje de error genérico si es necesario y redirigir
    $_SESSION['edit_feedback_msg'] = "Debes iniciar sesión para editar el perfil.";
    $_SESSION['edit_feedback_type'] = "error";
    header("Location: ../login-page.php"); // O a la página que consideres apropiada
    exit;
}

$db = new DBConnection();
$conn = $db->getConnection();
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['profile_id_to_modify'], $_POST['username'])) {
    $profile_id_to_modify = $_POST['profile_id_to_modify'];
    // **CRUCIAL**: Verificar que el usuario logueado ($_SESSION['id_name'])
    // es el mismo que el del perfil que se intenta modificar ($profile_id_to_modify).
    if ($_SESSION['id_name'] !== $profile_id_to_modify) {
        $_SESSION['edit_feedback_msg'] = "Error: No tienes permiso para modificar este perfil.";
        $_SESSION['edit_feedback_type'] = "error";
        header("Location: ../profile-page.php?user=" . urlencode($profile_id_to_modify));
        exit;
    }

    // Obtener los datos actuales del usuario desde la BD para usarlos como default
    // si no se suben nuevas imágenes o para comparar id_name/email.
    $stmtCurrentData = $conn->prepare("SELECT * FROM users WHERE id_name = ?");
    $stmtCurrentData->execute([$_SESSION['id_name']]); // Usar el id_name de la sesión (el dueño)
    $currentUserData = $stmtCurrentData->fetch(PDO::FETCH_ASSOC);

    if (!$currentUserData) {
        $_SESSION['edit_feedback_msg'] = "Error: Usuario no encontrado.";
        $_SESSION['edit_feedback_type'] = "error";
        header("Location: ../home-page.php"); // O a la página de perfil si aún es accesible
        exit;
    }


    // --- Recoger los datos del formulario de edición ---
    $new_id_name = trim($_POST['id_name']);
    $new_username = trim($_POST['username']);
    $new_email = trim($_POST['email']);
    $new_birth = $_POST['birth'];

    $new_password = $_POST['new_password'];
    $confirm_new_password = $_POST['confirm_new_password'];

    $new_location = trim($_POST['location'] ?? $currentUserData['location']); // Usar valor actual si no se envía
    $new_education = trim($_POST['education'] ?? $currentUserData['education']);
    $new_bio = trim($_POST['bio'] ?? $currentUserData['bio']);


    // --- Validaciones en PHP (idénticas a las que tenías en profile-page.php) ---
    $errors = [];

    // Validar id_name (nombre único)
    if (empty($new_id_name)) {
        $errors[] = "El nombre único (ID) no puede estar vacío.";
    } elseif (!preg_match('/^[a-zA-Z0-9_]{3,15}$/', $new_id_name)) {
        $errors[] = "El nombre único (ID) solo puede contener letras, números y guiones bajos, y tener entre 3 y 15 caracteres.";
    }

    // Validar username (nombre visible)
    if (empty($new_username)) {
        $errors[] = "El nombre visible no puede estar vacío.";
    } elseif (strlen($new_username) < 3 || strlen($new_username) > 50) {
        $errors[] = "El nombre visible debe tener entre 3 y 50 caracteres.";
    }

    // Validar email
    if (empty($new_email)) {
        $errors[] = "El correo electrónico no puede estar vacío.";
    } elseif (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "El formato del correo electrónico es inválido.";
    }

    // Validar fecha de nacimiento
    if (empty($new_birth)) {
        $errors[] = "La fecha de nacimiento no puede estar vacía.";
    } else {
        // Puedes añadir más validaciones de fecha si es necesario
    }

    // Validar contraseña (solo si se ingresó una nueva)
    $hashed_new_password = null; // Por defecto, no se cambia la contraseña
    if (!empty($new_password)) {
        if (strlen($new_password) < 8) {
            $errors[] = "La nueva contraseña debe tener al menos 8 caracteres.";
        } elseif ($new_password !== $confirm_new_password) {
            $errors[] = "Las nuevas contraseñas no coinciden.";
        } else {
            $hashed_new_password = password_hash($new_password, PASSWORD_DEFAULT);
        }
    }


    // --- Procesamiento de imágenes ---
    $db_profile_pic_name = $currentUserData['profile_picture']; // Nombre actual en BD o NULL
    if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] == UPLOAD_ERR_OK && $_FILES['profile_pic']['size'] > 0) {
        $target_dir_profile = "../../assets/profile_pics/";
        if (!is_dir($target_dir_profile)) {
            mkdir($target_dir_profile, 0777, true);
        }

        // Nombre único del archivo para evitar colisiones y problemas de caché
        $profile_pic_filename = $_SESSION['id_name'] . "_profile_" . time() . "_" . basename($_FILES["profile_pic"]["name"]);
        $profile_pic_target_file_path = $target_dir_profile . $profile_pic_filename; // Ruta completa para move_uploaded_file

        if (empty($errors) && move_uploaded_file($_FILES["profile_pic"]["tmp_name"], $profile_pic_target_file_path)) {
            $db_profile_pic_name = $profile_pic_filename; // Guardar solo el nombre del archivo en la variable para la BD
            if($currentUserData['profile_picture'] && file_exists($target_dir_profile . $currentUserData['profile_picture'])) {
                unlink($target_dir_profile . $currentUserData['profile_picture']);
            }
        } elseif (empty($errors)) {
            $errors[] = "Error al subir la foto de perfil. Ruta destino: " . $profile_pic_target_file_path;
        }
    }

    $db_cover_pic_name = $currentUserData['cover_picture']; // Nombre actual en BD o NULL
    if (isset($_FILES['cover_pic']) && $_FILES['cover_pic']['error'] == UPLOAD_ERR_OK && $_FILES['cover_pic']['size'] > 0) {
        $target_dir_cover = "../../assets/cover-img/";
        if (!is_dir($target_dir_cover)) {
            mkdir($target_dir_cover, 0777, true);
        }

        $cover_pic_filename = $_SESSION['id_name'] . "_cover_" . time() . "_" . basename($_FILES["cover_pic"]["name"]);
        $cover_pic_target_file_path = $target_dir_cover . $cover_pic_filename; // Ruta completa para move_uploaded_file

        if (empty($errors) && move_uploaded_file($_FILES["cover_pic"]["tmp_name"], $cover_pic_target_file_path)) {
            $db_cover_pic_name = $cover_pic_filename; // Guardar solo el nombre del archivo en la variable para la BD
            if($currentUserData['cover_picture'] && file_exists($target_dir_cover . $currentUserData['cover_picture'])) {
               unlink($target_dir_cover . $currentUserData['cover_picture']);
            }
        } elseif (empty($errors)) {
            $errors[] = "Error al subir la foto de portada. Ruta destino: " . $cover_pic_target_file_path;
        }
    }


    // --- Si no hay errores, llamar al Stored Procedure ---
    if (empty($errors)) {
        try {
            $stmt = $conn->prepare("CALL sp_ModifyUser(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $currentUserData['id_name'], // p_current_id_name (el id_name antes de cualquier cambio)
                ($new_id_name !== $currentUserData['id_name']) ? $new_id_name : $currentUserData['id_name'], // p_new_id_name
                $new_username,
                ($new_email !== $currentUserData['email']) ? $new_email : $currentUserData['email'],       // p_new_email
                $hashed_new_password,   // p_new_pass (será NULL si no se cambia)
                $new_birth,
                $db_profile_pic_name, // Pasa solo el nombre del archivo
                $db_cover_pic_name,   // Pasa solo el nombre del archivo
                $new_location,
                $new_education,
                $new_bio
            ]);

            $_SESSION['edit_feedback_msg'] = "¡Perfil actualizado con éxito!";
            $_SESSION['edit_feedback_type'] = "success";

            $_SESSION['avatar'] = $db_profile_pic_name; //cambiar la ruta de foto de perfil para navbar

            // Si el id_name cambió, actualizar la sesión y la URL de redirección
            $final_id_name_for_redirect = $currentUserData['id_name'];
            if ($new_id_name !== $currentUserData['id_name']) {
                $_SESSION['id_name'] = $new_id_name; // Actualizar el id de la sesión
                $final_id_name_for_redirect = $new_id_name;
            }
            // Actualizar también el nombre de usuario en sesión si cambió
            if ($new_username !== $_SESSION['username']) {
                $_SESSION['username'] = $new_username;
            }

            header("Location: ../profile-page.php?user=" . urlencode($final_id_name_for_redirect));
            exit;

        } catch (PDOException $e) {
            $errorInfo = $e->errorInfo;
            if (isset($errorInfo[1]) && $errorInfo[1] == 1644) { // Error desde SIGNAL
                $errorMessage = $errorInfo[2];
            } else {
                $errorMessage = "Error al actualizar el perfil: " . $e->getMessage();
            }
            $_SESSION['edit_feedback_msg'] = $errorMessage;
            $_SESSION['edit_feedback_type'] = "error";
            header("Location: ../profile-page.php?user=" . urlencode($profile_id_to_modify));
            exit;
        }
    } else {
        // Hubo errores de validación PHP
        $_SESSION['edit_feedback_msg'] = implode("<br>", $errors);
        $_SESSION['edit_feedback_type'] = "error";
        header("Location: ../profile-page.php?user=" . urlencode($profile_id_to_modify));
        exit;
    }

} else {
    // Si no es POST o faltan datos cruciales
    $_SESSION['edit_feedback_msg'] = "Solicitud inválida.";
    $_SESSION['edit_feedback_type'] = "error";
    // Determinar a dónde redirigir. Si tenemos $profile_id_to_modify, usarlo.
    $redirect_user_on_invalid = $_POST['profile_id_to_modify'] ?? $_SESSION['id_name'] ?? null;
    if ($redirect_user_on_invalid) {
        header("Location: ../profile-page.php?user=" . urlencode($redirect_user_on_invalid));
    } else {
        header("Location: ../home-page.php"); // Fallback general
    }
    exit;
}
?>