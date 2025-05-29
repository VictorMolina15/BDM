<?php
require_once 'connection.php'; 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$response = ['status' => 'error', 'message' => 'Solicitud inválida', 'users' => [], 'communities' => []];

if (isset($_POST['action']) && $_POST['action'] === 'global_search' && isset($_POST['search_term'])) {
    $db = new DBConnection();
    $conn = $db->getConnection();
    $search_term_raw = trim($_POST['search_term']); // Término de búsqueda sin procesar

    if (empty($search_term_raw)) {
        $response['message'] = 'El término de búsqueda no puede estar vacío.';
        header('Content-Type: application/json');
        echo json_encode($response);
        exit();
    }

    try {
        // Buscar Usuarios usando Stored Procedure
        $stmt_users = $conn->prepare("CALL sp_SearchGlobalUsers(:search_query)");
        $stmt_users->bindParam(':search_query', $search_term_raw, PDO::PARAM_STR);
        $stmt_users->execute();
        $users = $stmt_users->fetchAll(PDO::FETCH_ASSOC);
        $stmt_users->closeCursor(); // Buena práctica cerrar el cursor

        // Buscar Comunidades usando Stored Procedure
        $stmt_communities = $conn->prepare("CALL sp_SearchGlobalCommunities(:search_query)");
        $stmt_communities->bindParam(':search_query', $search_term_raw, PDO::PARAM_STR);
        $stmt_communities->execute();
        $communities = $stmt_communities->fetchAll(PDO::FETCH_ASSOC);
        $stmt_communities->closeCursor(); // Buena práctica cerrar el cursor

        $response['status'] = 'success';
        $response['users'] = $users ?: []; // Asegurar que siempre sea un array
        $response['communities'] = $communities ?: []; // Asegurar que siempre sea un array
        $response['message'] = 'Búsqueda exitosa.';

    } catch (PDOException $e) {
        $response['message'] = "Error de base de datos: " . $e->getMessage();
        // Considera loggear el error real en un archivo de logs en producción
        // error_log("Search Handler DB Error: " . $e->getMessage());
    }
} else {
    if (!isset($_POST['search_term'])) {
        $response['message'] = 'Falta el término de búsqueda.';
    }
}

header('Content-Type: application/json');
echo json_encode($response);
exit();

?>