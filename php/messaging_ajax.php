<?php
require_once 'back-end/connection.php'; // Adjust path if needed
require_once 'back-end/verified-session.php'; // Adjust path if needed

$db = new DBConnection();
$conn = $db->getConnection();
$response = ['status' => 'error', 'message' => 'Invalid request'];
$current_user_id = $_SESSION['id_name']; // From verified-session.php

if (isset($_POST['action'])) {
    $action = $_POST['action'];

    try {
        switch ($action) {
            case 'get_chats':
                $stmt = $conn->prepare("CALL sp_GetChatsForUser(?)");
                $stmt->execute([$current_user_id]);
                $chats = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $response = ['status' => 'success', 'chats' => $chats];
                break;

            case 'get_messages':
                if (isset($_POST['chat_id'])) {
                    $chat_id = (int)$_POST['chat_id'];
                    $limit = isset($_POST['limit']) ? (int)$_POST['limit'] : 20;
                    $offset = isset($_POST['offset']) ? (int)$_POST['offset'] : 0;

                    // Security check: Ensure current user is part of this chat
                    $verifyStmt = $conn->prepare("SELECT id FROM chat WHERE id = ? AND (user1_id = ? OR user2_id = ?)");
                    $verifyStmt->execute([$chat_id, $current_user_id, $current_user_id]);
                    if ($verifyStmt->fetch()) {
                        $stmt = $conn->prepare("CALL sp_GetMessagesForChat(?, ?, ?)");
                        $stmt->execute([$chat_id, $limit, $offset]);
                        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        // Messages are fetched ASC, reverse for typical chat display (newest at bottom)
                        $response = ['status' => 'success', 'messages' => $messages];
                    } else {
                        $response['message'] = 'Unauthorized chat access.';
                    }
                } else {
                    $response['message'] = 'Chat ID missing.';
                }
                break;

            case 'send_message':
                if (isset($_POST['chat_id'], $_POST['content'])) {
                    $chat_id = (int)$_POST['chat_id'];
                    $content = trim($_POST['content']);

                    if (!empty($content)) {
                        // Security check
                        $verifyStmt = $conn->prepare("SELECT id FROM chat WHERE id = ? AND (user1_id = ? OR user2_id = ?)");
                        $verifyStmt->execute([$chat_id, $current_user_id, $current_user_id]);
                        if ($verifyStmt->fetch()) {
                            $stmt = $conn->prepare("CALL sp_SendMessage(?, ?, ?, @p_message_id)");
                            $stmt->execute([$chat_id, $current_user_id, $content]);
                            $sentMessage = $stmt->fetch(PDO::FETCH_ASSOC); // SP now returns the message
                            $stmt->closeCursor();

                            if ($sentMessage) {
                                $response = ['status' => 'success', 'message_data' => $sentMessage];
                            } else {
                                $response['message'] = 'Failed to retrieve sent message details.';
                            }
                        } else {
                             $response['message'] = 'Unauthorized to send to this chat.';
                        }
                    } else {
                        $response['message'] = 'Message content cannot be empty.';
                    }
                } else {
                    $response['message'] = 'Chat ID or content missing.';
                }
                break;

            case 'create_or_get_chat':
                if (isset($_POST['other_user_id'])) {
                    $other_user_id = $_POST['other_user_id'];
                    if ($other_user_id != $current_user_id) {
                        $stmt = $conn->prepare("CALL sp_CreateOrGetChat(?, ?)");
                        $stmt->execute([$current_user_id, $other_user_id]);
                        $result = $stmt->fetch(PDO::FETCH_ASSOC);
                        $stmt->closeCursor();

                        if ($result && isset($result['chat_id'])) {
                             // Get the other user's details for the chat window header
                            $userStmt = $conn->prepare("SELECT id_name, username, profile_picture FROM users WHERE id_name = ?");
                            $userStmt->execute([$other_user_id]);
                            $other_user_data = $userStmt->fetch(PDO::FETCH_ASSOC);

                            $response = ['status' => 'success', 'chat_id' => $result['chat_id'], 'other_user' => $other_user_data];
                        } else {
                            $response['message'] = 'Could not create or get chat. SP might have failed or returned unexpected result.';
                             // Add $stmt->errorInfo() for debugging if needed
                        }
                    } else {
                        $response['message'] = 'Cannot create chat with yourself.';
                    }
                } else {
                    $response['message'] = 'Other user ID missing.';
                }
                break;

            case 'search_users_for_chat':
                if (isset($_POST['search_term'])) {
                    $search_term = $_POST['search_term'];
                    $stmt = $conn->prepare("CALL sp_SearchUsersForChat(?, ?)");
                    $stmt->execute([$current_user_id, $search_term]);
                    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    $response = ['status' => 'success', 'users' => $users];
                } else {
                    $response['message'] = 'Search term missing.';
                }
                break;

            default:
                $response['message'] = 'Unknown action.';
                break;
        }
    } catch (PDOException $e) {
        $response['message'] = "Database error: " . $e->getMessage();
        // For debugging, you might want to include more detailed error info
        // $response['error_details'] = $e->errorInfo;
    }
}

header('Content-Type: application/json');
echo json_encode($response);
exit();