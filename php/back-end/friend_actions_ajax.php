<?php
require_once 'connection.php';
require_once 'verified-session.php'; 

$db = new DBConnection();
$conn = $db->getConnection();
$response = ['status' => 'error', 'message' => 'Acción no válida.'];
$current_user_id = $_SESSION['id_name'];

if (isset($_POST['action'])) {
    $action = $_POST['action'];

    try {
        switch ($action) {
            case 'get_friendship_status':
                if (isset($_POST['profile_id'])) {
                    $profile_id = $_POST['profile_id'];
                    $stmt = $conn->prepare("CALL sp_GetFriendshipStatus(?, ?)");
                    $stmt->execute([$current_user_id, $profile_id]);
                    $result = $stmt->fetch(PDO::FETCH_ASSOC);
                    $response = ['status' => 'success', 'data' => $result];
                } else {
                    $response['message'] = 'Falta el ID del perfil.';
                }
                break;

            case 'send_friend_request':
                if (isset($_POST['receiver_id'])) {
                    $receiver_id = $_POST['receiver_id'];
                    $stmt = $conn->prepare("CALL sp_SendFriendRequest(?, ?)");
                    $stmt->execute([$current_user_id, $receiver_id]);
                    $result = $stmt->fetch(PDO::FETCH_ASSOC); // SP ahora devuelve un estado y mensaje
                    if (isset($result['result_status'])) {
                        $response = ['status' => ($result['result_status'] === 'request_not_found_or_not_pending' || $result['result_status'] === 'error' ? 'error' : 'success'), 'message' => $result['message'], 'friendship_status' => $result['result_status']];
                         // Actualizar el estado de amistad para el botón del perfil
                        if($result['result_status'] == 'request_sent' || $result['result_status'] == 'mutual_acceptance'){
                            $statusStmt = $conn->prepare("CALL sp_GetFriendshipStatus(?, ?)");
                            $statusStmt->execute([$current_user_id, $receiver_id]);
                            $newStatus = $statusStmt->fetch(PDO::FETCH_ASSOC);
                            $response['new_friendship_status'] = $newStatus['friendship_status'] ?? 'not_friends';
                            $statusStmt->closeCursor();
                        }
                    } else {
                         // Manejo de error si el SP lanzó una SIGNAL SQLSTATE 45000
                         $errorInfo = $stmt->errorInfo();
                         if ($errorInfo[1] == 1644) { // Error específico de SIGNAL
                             $response['message'] = $errorInfo[2];
                         } else {
                             $response['message'] = 'Error al procesar la solicitud de amistad.';
                         }
                    }
                    $stmt->closeCursor();
                } else {
                    $response['message'] = 'Falta el ID del receptor.';
                }
                break;

            case 'get_pending_requests':
                $stmt = $conn->prepare("CALL sp_GetPendingFriendRequests(?)");
                $stmt->execute([$current_user_id]);
                $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $response = ['status' => 'success', 'requests' => $requests];
                $stmt->closeCursor();
                break;

            case 'accept_friend_request':
                if (isset($_POST['requester_id'])) {
                    $requester_id = $_POST['requester_id'];
                    $stmt = $conn->prepare("CALL sp_AcceptFriendRequest(?, ?)");
                    $stmt->execute([$requester_id, $current_user_id]);
                    $result = $stmt->fetch(PDO::FETCH_ASSOC);
                     $response = ['status' => ($result['result_status'] === 'acceptance_successful' ? 'success' : 'error'), 'message' => $result['message']];
                    $stmt->closeCursor();
                } else {
                    $response['message'] = 'Falta el ID del solicitante.';
                }
                break;

            case 'reject_friend_request':
                if (isset($_POST['requester_id'])) {
                    $requester_id = $_POST['requester_id'];
                    $stmt = $conn->prepare("CALL sp_RejectFriendRequest(?, ?)");
                    $stmt->execute([$requester_id, $current_user_id]);
                    $result = $stmt->fetch(PDO::FETCH_ASSOC);
                    $response = ['status' => ($result['result_status'] === 'rejection_successful' ? 'success' : 'error'), 'message' => $result['message']];
                    $stmt->closeCursor();
                } else {
                    $response['message'] = 'Falta el ID del solicitante.';
                }
                break;

            default:
                $response['message'] = 'Acción no reconocida.';
                break;
        }
    } catch (PDOException $e) {
        // Captura errores generales de PDO, incluyendo los de SIGNAL SQLSTATE '45000' si no se manejan antes
        $errorInfo = $e->errorInfo;
        if (isset($errorInfo[1]) && $errorInfo[1] == 1644) { // Error desde SIGNAL
            $response['message'] = $errorInfo[2]; // Mensaje del SIGNAL
        } else {
            $response['message'] = "Error en la base de datos: " . $e->getMessage();
        }
    }
}

header('Content-Type: application/json');
echo json_encode($response);
exit();
?>