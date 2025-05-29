<?php
require_once 'connection.php';
require_once 'verified-session.php'; // Ensures user is logged in

$db = new DBConnection();
$conn = $db->getConnection();
$response = ['status' => 'error', 'message' => 'Acción no válida.'];
$current_user_id = $_SESSION['id_name'];

if (isset($_POST['action'])) {
    $action = $_POST['action'];

    try {
        switch ($action) {
            case 'create_post':
                $content = trim($_POST['caption'] ?? '');
                $media_file = $_FILES['media'] ?? null;
                $media_path = null;
                $media_type = 'none'; // Default if no media
                $target_community_id = isset($_POST['post_target_community_id']) && !empty($_POST['post_target_community_id']) 
                ? (int)$_POST['post_target_community_id'] : null; // Obtener el ID de la comunidad

                if (empty($content) && !$media_file) {
                    $response['message'] = 'El post no puede estar vacío (sin texto ni multimedia).';
                    break;
                }

                if ($media_file && $media_file['error'] == UPLOAD_ERR_OK) {
                    // File validation
                    $allowed_image_types = ['image/jpeg', 'image/png', 'image/gif'];
                    $allowed_video_types = ['video/mp4', 'video/webm', 'video/ogg', 'video/quicktime']; // Added quicktime
                    $max_file_size = 40 * 1024 * 1024; // 40MB

                    $file_type = mime_content_type($media_file['tmp_name']);
                    $file_size = $media_file['size'];

                    if ($file_size > $max_file_size) {
                        $response['message'] = 'El archivo multimedia no debe exceder los 40MB.';
                        break;
                    }

                    if (in_array($file_type, $allowed_image_types)) {
                        $media_type = 'image';
                    } elseif (in_array($file_type, $allowed_video_types)) {
                        $media_type = 'video';
                    } else {
                        $response['message'] = 'Tipo de archivo no permitido. Sube imágenes (jpg, png, gif) o videos (mp4, webm, ogg, mov).';
                        break;
                    }

                    $upload_dir = '../../assets/post_media/';
                    if (!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }
                    $file_extension = pathinfo($media_file['name'], PATHINFO_EXTENSION);
                    $unique_filename = $current_user_id . '_' . time() . '_' . uniqid() . '.' . $file_extension;
                    $media_path_on_server = $upload_dir . $unique_filename;

                    if (move_uploaded_file($media_file['tmp_name'], $media_path_on_server)) {
                        $media_path = $unique_filename; // Store only filename in DB
                    } else {
                        $response['message'] = 'Error al subir el archivo multimedia.';
                        break;
                    }
                } elseif ($media_file && $media_file['error'] != UPLOAD_ERR_NO_FILE) {
                     $response['message'] = 'Error con el archivo multimedia: ' . $media_file['error'];
                     break;
                }


                $stmt = $conn->prepare("CALL sp_CreatePost(?, ?, ?, ?, ?)");
                $stmt->execute([$current_user_id, $content, $media_path, $media_type, $target_community_id]);
                $new_post_data = $stmt->fetch(PDO::FETCH_ASSOC);
                $stmt->closeCursor();

                if ($new_post_data) {
                    $response = ['status' => 'success', 'message' => '¡Publicación creada!', 'post_data' => $new_post_data];
                } else {
                    $response['message'] = 'Error al crear la publicación en la base de datos.';
                }
                break;

            case 'add_comment':
                if (isset($_POST['post_id'], $_POST['comment_content'])) {
                    $post_id = (int)$_POST['post_id'];
                    $comment_content = trim($_POST['comment_content']);

                    if (empty($comment_content)) {
                        $response['message'] = 'El comentario no puede estar vacío.';
                        break;
                    }

                    $stmt = $conn->prepare("CALL sp_AddComment(?, ?, ?, @p_comment_id)");
                    $stmt->execute([$post_id, $current_user_id, $comment_content]);
                    $new_comment_data = $stmt->fetch(PDO::FETCH_ASSOC);
                    $stmt->closeCursor();
                    // $outStmt = $conn->query("SELECT @p_comment_id AS comment_id"); // if SP only returns ID via OUT
                    // $comment_id_result = $outStmt->fetch(PDO::FETCH_ASSOC);

                    if ($new_comment_data) {
                        $response = ['status' => 'success', 'message' => 'Comentario añadido.', 'comment_data' => $new_comment_data];
                    } else {
                        $response['message'] = 'Error al añadir comentario.';
                    }
                } else {
                    $response['message'] = 'Faltan datos para el comentario.';
                }
                break;

            case 'get_comments':
                 if (isset($_POST['post_id'])) {
                    $post_id = (int)$_POST['post_id'];
                    $limit = isset($_POST['limit']) ? (int)$_POST['limit'] : 5; // Default limit
                    $offset = isset($_POST['offset']) ? (int)$_POST['offset'] : 0;

                    $stmt = $conn->prepare("CALL sp_GetCommentsForPost(?, ?, ?)");
                    $stmt->execute([$post_id, $limit, $offset]);
                    $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    $stmt->closeCursor();
                    $response = ['status' => 'success', 'comments' => $comments];
                 } else {
                    $response['message'] = 'Falta el ID del post.';
                 }
                break;

            case 'toggle_like_post':
                 if (isset($_POST['post_id'])) { // Ya no necesitamos 'is_liking' desde el cliente
                    $post_id = (int)$_POST['post_id'];
                    // $current_user_id ya está definido desde verified-session.php

                    $stmt = $conn->prepare("CALL sp_TogglePostLike(?, ?)");
                    $stmt->execute([$post_id, $current_user_id]);
                    $result = $stmt->fetch(PDO::FETCH_ASSOC); // El SP ahora devuelve new_like_count y liked_status
                    $stmt->closeCursor();

                    if ($result) {
                        $response = [
                            'status' => 'success',
                            'new_like_count' => $result['new_like_count'],
                            'liked' => (bool)$result['liked_status'] // El SP devuelve 0 o 1, convertir a booleano
                        ];
                    } else {
                        $response['message'] = 'Error al actualizar me gusta.';
                    }
                 } else {
                    $response['message'] = 'Falta el ID del post.';
                 }
                break;

            // block_post action can be handled by block_report_ajax.php as it's already set up for it.
            // Just ensure the JS calls that script for blocking posts.

            default:
                $response['message'] = 'Acción no reconocida.';
                break;
        }
    } catch (PDOException $e) {
        $errorInfo = $e->errorInfo;
        if (isset($errorInfo[1]) && $errorInfo[1] == 1644) { // Error desde SIGNAL
            $response['message'] = $errorInfo[2];
        } else {
            $response['message'] = "Error en la base de datos: " . $e->getMessage();
        }
    } catch (Exception $e) {
        $response['message'] = "Error general: " . $e->getMessage();
    }
}

header('Content-Type: application/json');
echo json_encode($response);
exit();
?>