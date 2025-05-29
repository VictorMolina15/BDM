<?php
require_once 'connection.php';
require_once 'verified-session.php'; // Asegura que el usuario esté logueado
error_reporting(E_ALL);
ini_set('display_errors', 1); // Muestra errores en la salida, útil para AJAX

$db = new DBConnection();
$conn = $db->getConnection();
$final_response = ['status' => 'error', 'message' => 'Acción no procesada o no reconocida.'];
$current_user_id = $_SESSION['id_name'];

if (isset($_POST['action'])) {
    $action = $_POST['action'];

    $db_community_pic_name = null;
    $db_cover_pic_name = null;

    try {
        switch ($action) {
            case 'create_community':
                $name_comm = trim($_POST['name_comm'] ?? '');
                $descrip = trim($_POST['descrip'] ?? '');
                $community_pic_file = $_FILES['community_picture'] ?? null; // Para el icono
                $cover_pic_file = $_FILES['cover_picture'] ?? null;       // Para el banner


                // --- VALIDACIONES INICIALES ---
                if (empty($name_comm)) {
                    $final_response = ['status' => 'error', 'message' => 'El nombre de la comunidad es obligatorio.'];
                    break; // Sale del switch
                }
                if (strlen($name_comm) > 100) {
                    $final_response = ['status' => 'error', 'message' => 'El nombre de la comunidad no puede exceder los 100 caracteres.'];
                    break; // Sale del switch
                }
                if (strlen($descrip) > 65535) {
                    $final_response = ['status' => 'error', 'message' => 'La descripción es demasiado larga.'];
                    break; // Sale del switch
                }


                // Función auxiliar para manejar la subida de imágenes
                function handleCommunityImageUpload($file_input, $target_folder_name, $file_prefix, $max_size_mb, $entity_name_for_msg)
                {
                    global $current_user_id; // Acceder a la variable global

                    if ($file_input && $file_input['error'] == UPLOAD_ERR_OK && $file_input['size'] > 0) {
                        $target_dir = "../../assets/" . $target_folder_name . "/";
                        if (!is_dir($target_dir)) {
                            if (!mkdir($target_dir, 0777, true)) {
                                return ['error' => "Error: No se pudo crear el directorio de subida para {$entity_name_for_msg}."];
                            }
                        }

                        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
                        $file_type = mime_content_type($file_input['tmp_name']);
                        $file_size = $file_input['size'];

                        if (!in_array($file_type, $allowed_types)) {
                            return ['error' => "Tipo de archivo no permitido para {$entity_name_for_msg}. Sube JPG, PNG o GIF."];
                        }
                        if ($file_size > $max_size_mb * 1024 * 1024) {
                            return ['error' => "El archivo para {$entity_name_for_msg} excede los {$max_size_mb}MB."];
                        }

                        $file_extension = strtolower(pathinfo($file_input["name"], PATHINFO_EXTENSION));
                        // Usar el nombre de la comunidad (sanitizado) y el id del creador para unicidad podría ser una opción,
                        // o simplemente un uniqid como antes.
                        $filename = $file_prefix . "_" . $current_user_id . "_" . time() . "." . $file_extension;
                        $target_file_path = $target_dir . $filename;

                        if (move_uploaded_file($file_input["tmp_name"], $target_file_path)) {
                            return ['filename' => $filename];
                        } else {
                            // Intentar obtener más detalles del error de subida si es posible
                            $upload_errors = [
                                UPLOAD_ERR_INI_SIZE => "El archivo excede la directiva upload_max_filesize en php.ini.",
                                UPLOAD_ERR_FORM_SIZE => "El archivo excede la directiva MAX_FILE_SIZE especificada en el formulario HTML.",
                                UPLOAD_ERR_PARTIAL => "El archivo se subió solo parcialmente.",
                                UPLOAD_ERR_NO_FILE => "No se subió ningún archivo.",
                                UPLOAD_ERR_NO_TMP_DIR => "Falta una carpeta temporal.",
                                UPLOAD_ERR_CANT_WRITE => "No se pudo escribir el archivo en el disco.",
                                UPLOAD_ERR_EXTENSION => "Una extensión de PHP detuvo la subida del archivo."
                            ];
                            $error_message = $upload_errors[$file_input['error']] ?? "Error desconocido al subir {$entity_name_for_msg}.";
                            return ['error' => $error_message . " (Código: " . $file_input['error'] . ")"];
                        }
                    }
                    return ['filename' => null]; // No hay archivo o no hubo error explícito al no haber archivo
                }

                // Subir imagen de perfil de la comunidad (icono)
                if ($community_pic_file && $community_pic_file['size'] > 0) {
                    $uploadResult = handleCommunityImageUpload($community_pic_file, "community_pics", "icon", 5, "el icono de la comunidad");
                    if (isset($uploadResult['error'])) {
                        $response['message'] = $uploadResult['error'];
                        break;
                    }
                    $db_community_pic_name = $uploadResult['filename'];
                }

                // Subir imagen de fondo de la comunidad (banner)
                if ($cover_pic_file && $cover_pic_file['size'] > 0) {
                    $uploadResult = handleCommunityImageUpload($cover_pic_file, "community_covers", "cover", 10, "el banner de la comunidad");
                    if (isset($uploadResult['error'])) {
                        $response['message'] = $uploadResult['error'];
                        // Si el icono se subió pero el banner falló, eliminar el icono para consistencia
                        if ($db_community_pic_name && file_exists("../../assets/community_pics/" . $db_community_pic_name)) {
                            unlink("../../assets/community_pics/" . $db_community_pic_name);
                        }
                        break;
                    }
                    $db_cover_pic_name = $uploadResult['filename'];
                }

                // --- LLAMADA AL STORED PROCEDURE ---
                // El SP sp_CreateCommunity tiene un parámetro OUT @out_community_id, pero también devuelve un SELECT.
                // Nos enfocaremos en el SELECT devuelto.
                $stmt = $conn->prepare("CALL sp_CreateCommunity(?, ?, ?, ?, ?, @p_community_id_placeholder)"); // El nombre del OUT param en CALL no necesita coincidir exactamente con el del SP si solo lees el SELECT.
                // Usamos un placeholder aquí porque CALL espera el número correcto de placeholders.

                $stmt->bindParam(1, $current_user_id, PDO::PARAM_STR);
                $stmt->bindParam(2, $name_comm, PDO::PARAM_STR);
                $stmt->bindParam(3, $descrip, PDO::PARAM_STR);
                $stmt->bindParam(4, $db_community_pic_name, $db_community_pic_name === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
                $stmt->bindParam(5, $db_cover_pic_name, $db_cover_pic_name === null ? PDO::PARAM_NULL : PDO::PARAM_STR);

                $stmt->execute();

                // El SP ejecuta un SELECT que devuelve 'status', 'message', y 'community_id'.
                // Este es el primer (y único) result set que esperamos.
                $result_sp = $stmt->fetch(PDO::FETCH_ASSOC);
                $stmt->closeCursor(); // Esencial si el SP pudiera tener más result sets o para liberar la conexión.

                if ($result_sp && isset($result_sp['status']) && $result_sp['status'] === 'success' && isset($result_sp['community_id'])) {
                    $final_response = [
                        'status' => 'success',
                        'message' => $result_sp['message'],
                        'community_id' => $result_sp['community_id']
                    ];
                } else {
                    $final_response = [
                        'status' => 'error_sp_logic',
                        'message' => $result_sp['message'] ?? 'El SP no devolvió un éxito o el ID de comunidad esperado.',
                        'sp_result_debug' => $result_sp // Incluye lo que devolvió el SP para depurar
                    ];
                    // Limpiar archivos si el SP no tuvo éxito en su lógica interna
                    if ($db_community_pic_name && file_exists("../../assets/community_pics/" . $db_community_pic_name)) {
                        unlink("../../assets/community_pics/" . $db_community_pic_name);
                    }
                    if ($db_cover_pic_name && file_exists("../../assets/community_covers/" . $db_cover_pic_name)) {
                        unlink("../../assets/community_covers/" . $db_cover_pic_name);
                    }
                }
                break;

            case 'join_community':
                if (isset($_POST['community_id']) && !empty($_POST['community_id']) && is_numeric($_POST['community_id'])) {
                    $community_id_to_join = (int)$_POST['community_id'];

                    $stmt = $conn->prepare("CALL sp_JoinCommunity(?, ?)");
                    $stmt->bindParam(1, $current_user_id, PDO::PARAM_STR);
                    $stmt->bindParam(2, $community_id_to_join, PDO::PARAM_INT);
                    $stmt->execute();
                    $result_sp = $stmt->fetch(PDO::FETCH_ASSOC);
                    $stmt->closeCursor();

                    if ($result_sp && isset($result_sp['status'])) {
                        $final_response = [
                            'status' => $result_sp['status'], // 'success' o 'error' desde el SP
                            'message' => $result_sp['message']
                        ];
                    } else {
                        $final_response = ['status' => 'error', 'message' => 'Respuesta inesperada al intentar unirse a la comunidad.'];
                    }
                } else {
                    $final_response = ['status' => 'error', 'message' => 'ID de comunidad no válido o no proporcionado.'];
                }
                break;

            case 'leave_community':
                if (isset($_POST['community_id']) && !empty($_POST['community_id']) && is_numeric($_POST['community_id'])) {
                    $community_id_to_leave = (int)$_POST['community_id'];

                    $stmt = $conn->prepare("CALL sp_LeaveCommunity(?, ?)");
                    $stmt->bindParam(1, $current_user_id, PDO::PARAM_STR);
                    $stmt->bindParam(2, $community_id_to_leave, PDO::PARAM_INT);
                    $stmt->execute();
                    $result_sp = $stmt->fetch(PDO::FETCH_ASSOC);
                    $stmt->closeCursor();
                    
                    if ($result_sp && isset($result_sp['status'])) {
                         $final_response = [
                            'status' => $result_sp['status'], // 'success' o 'error' desde el SP
                            'message' => $result_sp['message']
                        ];
                    } else {
                        $final_response = ['status' => 'error', 'message' => 'Respuesta inesperada al intentar abandonar la comunidad.'];
                    }
                } else {
                    $final_response = ['status' => 'error', 'message' => 'ID de comunidad no válido o no proporcionado.'];
                }
                break;

            default:
                $final_response = ['status' => 'error', 'message' => 'Acción de comunidad no reconocida.'];
                break;
        }
    } catch (PDOException $e) {
        $errorInfo = $e->errorInfo;
        $message = "Error general de base de datos.";
        if (isset($errorInfo[1]) && $errorInfo[1] == 1644) { // Error desde SIGNAL
            $message = $errorInfo[2];
        } else {
            $message = "Error PDO: " . $e->getMessage();
        }
        $final_response = [
            'status' => 'error_pdo_exception',
            'message' => $message,
            // 'pdo_error_code' => $e->getCode(), // Descomentar para más detalles
            // 'pdo_error_info' => $errorInfo    // Descomentar para más detalles
        ];
        // Limpiar archivos en caso de excepción de BD durante la llamada al SP
        if ($db_community_pic_name && file_exists("../../assets/community_pics/" . $db_community_pic_name)) {
            unlink("../../assets/community_pics/" . $db_community_pic_name);
        }
        if ($db_cover_pic_name && file_exists("../../assets/community_covers/" . $db_cover_pic_name)) {
            unlink("../../assets/community_covers/" . $db_cover_pic_name);
        }
    } catch (Exception $e) { // Capturar otras excepciones generales
        $final_response = ['status' => 'error_general_exception', 'message' => "Error general: " . $e->getMessage()];
    }
} else {
    $final_response = ['status' => 'error', 'message' => 'Parámetro "action" no recibido.'];
}

header('Content-Type: application/json');
echo json_encode($final_response);
exit();
?>