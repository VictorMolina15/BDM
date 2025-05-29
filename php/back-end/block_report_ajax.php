<?php

require_once 'connection.php';
require_once 'verified-session.php'; // Asegura que el usuario esté logueado

$db = new DBConnection();
$conn = $db->getConnection();
$response = ['status' => 'error', 'message' => 'Acción no válida.'];
$current_user_id = $_SESSION['id_name'];

if (isset($_POST['action'])) {
    $action = $_POST['action'];
    $reason = isset($_POST['reason']) ? trim($_POST['reason']) : NULL;

    try {
        switch ($action) {
            case 'block_user':
                if (isset($_POST['reported_user_id'])) {
                    $reported_user_id = $_POST['reported_user_id'];
                    $stmt = $conn->prepare("CALL sp_BlockOrReportUser(?, ?, ?)");
                    $stmt->execute([$current_user_id, $reported_user_id, $reason]);
                    $result = $stmt->fetch(PDO::FETCH_ASSOC);
                    $response = ['status' => ($result['result_status'] === 'user_blocked' || $result['result_status'] === 'block_updated' ? 'success' : 'error'), 'message' => $result['message']];
                    $stmt->closeCursor();
                } else {
                    $response['message'] = 'Falta el ID del usuario a bloquear.';
                }
                break;

            case 'block_post':
                if (isset($_POST['reported_post_id'])) {
                    $reported_post_id = $_POST['reported_post_id'];
                    $stmt = $conn->prepare("CALL sp_BlockOrReportPost(?, ?, ?)");
                    $stmt->execute([$current_user_id, $reported_post_id, $reason]);
                     $result = $stmt->fetch(PDO::FETCH_ASSOC);
                    $response = ['status' => ($result['result_status'] === 'post_blocked' || $result['result_status'] === 'block_updated' ? 'success' : 'error'), 'message' => $result['message']];
                    $stmt->closeCursor();
                } else {
                    $response['message'] = 'Falta el ID de la publicación a bloquear.';
                }
                break;

            case 'unblock_user':
                if (isset($_POST['reported_user_id'])) {
                    $reported_user_id = $_POST['reported_user_id'];
                    $stmt = $conn->prepare("CALL sp_UnblockUser(?, ?)");
                    $stmt->execute([$current_user_id, $reported_user_id]);
                    $result = $stmt->fetch(PDO::FETCH_ASSOC);
                    $response = ['status' => ($result['result_status'] === 'user_unblocked' ? 'success' : 'error'), 'message' => $result['message']];
                    $stmt->closeCursor();
                } else {
                    $response['message'] = 'Falta el ID del usuario a desbloquear.';
                }
                break;

            case 'unblock_post':
                if (isset($_POST['reported_post_id'])) {
                    $reported_post_id = $_POST['reported_post_id'];
                    $stmt = $conn->prepare("CALL sp_UnblockPost(?, ?)");
                    $stmt->execute([$current_user_id, $reported_post_id]);
                    $result = $stmt->fetch(PDO::FETCH_ASSOC);
                    $response = ['status' => ($result['result_status'] === 'post_unblocked' ? 'success' : 'error'), 'message' => $result['message']];
                    $stmt->closeCursor();
                } else {
                    $response['message'] = 'Falta el ID de la publicación a desbloquear.';
                }
                break;

            case 'get_my_blocks':
                $stmt = $conn->prepare("CALL sp_GetUserBlocksAndReports(?)");
                $stmt->execute([$current_user_id]);
                $blocks = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $response = ['status' => 'success', 'blocks' => $blocks];
                $stmt->closeCursor();
                break;

            default:
                $response['message'] = 'Acción no reconocida.';
                break;
        }
    } catch (PDOException $e) {
        $errorInfo = $e->errorInfo;
        if (isset($errorInfo[1]) && $errorInfo[1] == 1644) {
            $response['message'] = $errorInfo[2];
        } else {
            $response['message'] = "Error en la base de datos: " . $e->getMessage();
        }
    }
}

header('Content-Type: application/json');
echo json_encode($response);
exit();
?>