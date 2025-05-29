use bd_hijinx;

DELIMITER $$
-- Registrar Usuarios
CREATE PROCEDURE sp_RegisterUser (
    IN p_id_name VARCHAR(15),
    IN p_username VARCHAR(50),
    IN p_email VARCHAR(100),
    IN p_pass VARCHAR(255),
    IN p_birth DATE
)
BEGIN
    INSERT INTO users (id_name, username, email, pass, birth)
    VALUES (p_id_name, p_username, p_email, p_pass, p_birth);
END$$

DELIMITER ;

DELIMITER //
-- Validar que no se repitan Usuarios
CREATE PROCEDURE sp_ValidateUser(
    IN p_id_name VARCHAR(50),
    IN p_email VARCHAR(255)
)
BEGIN
    SELECT id_name, email
    FROM users
    WHERE id_name = p_id_name OR email = p_email;
END //
DELIMITER ;

DELIMITER $$
-- Iniciar Sesión
CREATE PROCEDURE sp_LogUser(
IN p_user VARCHAR(255)
)
BEGIN
    SELECT 
        id_name,
		username,
		email,
		pass,
		birth,
		profile_picture,
		created_at
    FROM users
    WHERE id_name = p_user OR email = p_user
    LIMIT 1;
END$$
DELIMITER ;

DELIMITER //
-- Buscar Usuario
CREATE PROCEDURE sp_SearchUser(
IN p_user VARCHAR(50)
)
BEGIN
	SELECT
		id_name,
        username,
        profile_picture
	FROM users
    WHERE id_name LIKE p_user OR username LIKE p_user;
END//
DELIMITER ; 

DELIMITER $$
-- Modificar Usuario
CREATE PROCEDURE sp_ModifyUser (
    IN p_current_id_name VARCHAR(15),         -- El id_name actual del usuario para identificarlo
    IN p_new_id_name VARCHAR(15),             -- El nuevo id_name (si se cambia)
    IN p_username VARCHAR(50),
    IN p_new_email VARCHAR(100),              -- El nuevo email (si se cambia)
    IN p_new_pass VARCHAR(255),               -- La nueva contraseña (hasheada, si se cambia)
    IN p_birth DATE,
    IN p_profile_picture VARCHAR(255),
    IN p_cover_picture VARCHAR(255),
    IN p_location VARCHAR(100),
    IN p_education VARCHAR(100),
    IN p_bio VARCHAR(100)
)
BEGIN
    DECLARE v_id_exists INT DEFAULT 0;
    DECLARE v_email_exists INT DEFAULT 0;
    DECLARE v_error_msg VARCHAR(255) DEFAULT NULL;
    DECLARE v_current_email VARCHAR(100); 
    -- Obtener el email actual del usuario para comparaciones posteriores
    SELECT email INTO v_current_email FROM users WHERE id_name = p_current_id_name LIMIT 1;

    -- Validar si el nuevo id_name ya está en uso por OTRO usuario
    IF p_new_id_name IS NOT NULL AND p_new_id_name != p_current_id_name THEN
        SELECT COUNT(*) INTO v_id_exists FROM users WHERE id_name = p_new_id_name;
        IF v_id_exists > 0 THEN
            SET v_error_msg = 'El nuevo nombre único (ID) ya está en uso.';
        END IF;
    END IF;

    -- Validar si el nuevo email ya está en uso por OTRO usuario
    IF v_error_msg IS NULL AND p_new_email IS NOT NULL AND p_new_email != v_current_email THEN
        SELECT COUNT(*) INTO v_email_exists FROM users WHERE email = p_new_email;
        IF v_email_exists > 0 THEN
            SET v_error_msg = 'El nuevo correo electrónico ya está en uso.';
        END IF;
    END IF;

    IF v_error_msg IS NOT NULL THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = v_error_msg;
    ELSE
        UPDATE users
        SET
            id_name = COALESCE(p_new_id_name, id_name), -- COALESCE para actualizar solo si se provee un nuevo valor
            username = COALESCE(p_username, username),
            email = COALESCE(p_new_email, email),
            pass = COALESCE(p_new_pass, pass), -- Asegúrate de pasar la contraseña ya hasheada si se cambia
            birth = COALESCE(p_birth, birth),
            profile_picture = COALESCE(p_profile_picture, profile_picture),
            cover_picture = COALESCE(p_cover_picture, cover_picture),
            location = COALESCE(p_location, location),
            education = COALESCE(p_education, education),
            biography = COALESCE(p_bio, biography) 
        WHERE id_name = p_current_id_name;
    END IF;
END$$

DELIMITER ;

-- ================CHATS==========================
DELIMITER $$

-- Obtiene todos los chats del Usuario y elementos para mostrarlos en el preview
CREATE PROCEDURE sp_GetChatsForUser(
    IN p_user_id VARCHAR(15)
)
BEGIN
    SELECT
        c.id AS chat_id,
        IF(c.user1_id = p_user_id, c.user2_id, c.user1_id) AS other_user_id,
        u.username AS other_username,
        u.profile_picture AS other_user_avatar,
        (SELECT content FROM message msg WHERE msg.chat_id = c.id ORDER BY msg.created_at DESC LIMIT 1) AS last_message_content,
        (SELECT created_at FROM message msg WHERE msg.chat_id = c.id ORDER BY msg.created_at DESC LIMIT 1) AS last_message_time
    FROM chat c
    JOIN users u ON u.id_name = IF(c.user1_id = p_user_id, c.user2_id, c.user1_id)
    WHERE c.user1_id = p_user_id OR c.user2_id = p_user_id
    ORDER BY last_message_time DESC;
END$$

-- Obtiene mensajes en Chats especificos con paginación
CREATE PROCEDURE sp_GetMessagesForChat(
    IN p_chat_id INT,
    IN p_limit INT,
    IN p_offset INT
)
BEGIN
    SELECT
        m.id AS message_id,
        m.author_id,
        u.username AS author_username,
        u.profile_picture AS author_avatar,
        m.content,
        m.created_at
    FROM message m
    JOIN users u ON m.author_id = u.id_name
    WHERE m.chat_id = p_chat_id
    ORDER BY m.created_at ASC
    LIMIT p_limit OFFSET p_offset;
END$$

-- Send a new message
CREATE PROCEDURE sp_SendMessage(
    IN p_chat_id INT,
    IN p_author_id VARCHAR(15),
    IN p_content TEXT,
    OUT p_message_id INT
)
BEGIN
    INSERT INTO message (chat_id, author_id, content, created_at)
    VALUES (p_chat_id, p_author_id, p_content, NOW());
    SET p_message_id = LAST_INSERT_ID();

    -- Selecciona el último mensaje enviado para mostrarlo inmediatamente
    SELECT
        p_message_id AS message_id,
        p_author_id AS author_id,
        (SELECT username FROM users WHERE id_name = p_author_id) AS author_username,
        (SELECT profile_picture FROM users WHERE id_name = p_author_id) AS author_avatar,
        p_content AS content,
        NOW() AS created_at;
END$$

-- Crear Chat si no existe, o obtener una existente
CREATE PROCEDURE sp_CreateOrGetChat(
    IN p_user1_id VARCHAR(15),
    IN p_user2_id VARCHAR(15)
)
BEGIN
    DECLARE v_chat_id INT;

    IF p_user1_id = p_user2_id THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Cannot create a chat with oneself.';
    END IF;

    IF p_user1_id > p_user2_id THEN
        SET @temp_id = p_user1_id;
        SET p_user1_id = p_user2_id;
        SET p_user2_id = @temp_id;
    END IF;

    SELECT id INTO v_chat_id
    FROM chat
    WHERE user1_id = p_user1_id AND user2_id = p_user2_id
    LIMIT 1;

    IF v_chat_id IS NULL THEN
        INSERT INTO chat (user1_id, user2_id) VALUES (p_user1_id, p_user2_id);
        SET v_chat_id = LAST_INSERT_ID();
    END IF;
    SELECT v_chat_id AS chat_id;
END$$

-- Buscar Usuarios para Chatear (adaptandose a sp_SearchUser)
CREATE PROCEDURE sp_SearchUsersForChat(
    IN p_current_user_id VARCHAR(15),
    IN p_search_term VARCHAR(50)
)
BEGIN
    SELECT
        id_name,
        username,
        profile_picture
    FROM users
    WHERE (id_name LIKE CONCAT('%', p_search_term, '%') OR username LIKE CONCAT('%', p_search_term, '%'))
      AND id_name != p_current_user_id;
END$$

DELIMITER ;

-- --------------- Stored Procedures para Búsqueda Global ---------------

DELIMITER $$
-- Stored Procedure para buscar usuarios globalmente (para la barra de navegación)
CREATE PROCEDURE sp_SearchGlobalUsers (
    IN p_search_query VARCHAR(255)
)
BEGIN
    DECLARE search_param VARCHAR(260);
    SET search_param = CONCAT('%', p_search_query, '%');

    SELECT id_name, username, profile_picture
    FROM users
    WHERE id_name LIKE search_param OR username LIKE search_param;
END$$
DELIMITER ;

DELIMITER $$
-- Stored Procedure para buscar comunidades globalmente (para la barra de navegación)
CREATE PROCEDURE sp_SearchGlobalCommunities (
    IN p_search_query VARCHAR(255)
)
BEGIN
    DECLARE search_param VARCHAR(260);
    SET search_param = CONCAT('%', p_search_query, '%');

    SELECT id, name_comm, community_picture  -- Asegúrate que community_picture existe y es el campo correcto
    FROM communities
    WHERE name_comm LIKE search_param;
END$$
DELIMITER ;
-- --------------- Stored Procedures para el Sistema de Amistades ---------------

DELIMITER $$
-- Enviar una solicitud de amistad
CREATE PROCEDURE sp_SendFriendRequest(
    IN p_sender_id VARCHAR(15),
    IN p_receiver_id VARCHAR(15)
)
BEGIN
    DECLARE existing_status ENUM('pending', 'accepted', 'rejected');
    DECLARE inverse_status ENUM('pending', 'accepted', 'rejected');
    DECLARE error_message VARCHAR(255);

    IF p_sender_id = p_receiver_id THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'No puedes enviarte una solicitud de amistad a ti mismo.';
    END IF;

    -- Verificar si ya existe una solicitud o amistad (A -> B)
    SELECT stat INTO existing_status FROM friends WHERE user_id = p_sender_id AND friend_id = p_receiver_id LIMIT 1;

    -- Verificar si existe una solicitud inversa (B -> A)
    SELECT stat INTO inverse_status FROM friends WHERE user_id = p_receiver_id AND friend_id = p_sender_id LIMIT 1;

    IF existing_status IS NOT NULL THEN
        IF existing_status = 'pending' THEN
            SET error_message = 'Ya has enviado una solicitud de amistad a este usuario.';
        ELSEIF existing_status = 'accepted' THEN
            SET error_message = 'Ya eres amigo de este usuario.';
        ELSE -- 'rejected', permitir reenviar creando un nuevo registro o actualizando. Aquí simplemente lo bloqueamos si ya existe.
            SET error_message = 'Tu solicitud anterior fue rechazada o cancelada.';
             -- Para permitir re-solicitar si fue rechazada, podrías borrar el registro 'rejected'
             -- O simplemente permitir la inserción si no es 'pending' o 'accepted'
             -- Por simplicidad, si existe cualquier registro A->B, no hacemos nada nuevo.
        END IF;
        IF error_message IS NOT NULL AND existing_status != 'rejected' THEN -- Solo error si no es rejected (para permitir reenviar si quisiera)
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = error_message;
        END IF;
    END IF;

    IF inverse_status IS NOT NULL THEN
        IF inverse_status = 'pending' THEN
            -- El otro usuario ya envió una solicitud, aceptarla automáticamente.
            CALL sp_AcceptFriendRequest(p_receiver_id, p_sender_id);
            SELECT 'mutual_acceptance' AS result_status, 'Amistad aceptada mutuamente.' AS message;
        ELSEIF inverse_status = 'accepted' THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Ya eres amigo de este usuario (solicitud inversa aceptada).';
        ELSE -- 'rejected' por el otro usuario
             -- Continuar para permitir que el usuario actual envíe una nueva solicitud
            INSERT INTO friends (user_id, friend_id, stat) VALUES (p_sender_id, p_receiver_id, 'pending')
                ON DUPLICATE KEY UPDATE stat = 'pending'; -- Si A->B fue 'rejected', la actualiza a 'pending'
            SELECT 'request_sent' AS result_status, 'Solicitud de amistad enviada.' AS message;
        END IF;
    ELSE
        -- No existe solicitud A->B (o fue 'rejected' y queremos sobreescribir) ni B->A
        INSERT INTO friends (user_id, friend_id, stat) VALUES (p_sender_id, p_receiver_id, 'pending')
            ON DUPLICATE KEY UPDATE stat = 'pending'; -- Esto maneja el caso de reenviar una solicitud que fue 'rejected'
        SELECT 'request_sent' AS result_status, 'Solicitud de amistad enviada.' AS message;
    END IF;
END$$
DELIMITER ;


DELIMITER $$
-- Aceptar una solicitud de amistad
CREATE PROCEDURE sp_AcceptFriendRequest(
    IN p_requester_id VARCHAR(15), -- Quien envió la solicitud originalmente
    IN p_accepter_id VARCHAR(15)   -- Quien está aceptando la solicitud (el usuario actual)
)
BEGIN
    DECLARE row_affected INT;
    -- Actualizar la solicitud original (requester -> accepter) a 'accepted'
    UPDATE friends
    SET stat = 'accepted'
    WHERE user_id = p_requester_id AND friend_id = p_accepter_id AND stat = 'pending';

    SET row_affected = ROW_COUNT();

    IF row_affected > 0 THEN
        -- Crear la relación recíproca (accepter -> requester) como 'accepted'
        INSERT INTO friends (user_id, friend_id, stat)
        VALUES (p_accepter_id, p_requester_id, 'accepted')
        ON DUPLICATE KEY UPDATE stat = 'accepted'; -- En caso de que ya exista (ej. ambos enviaron y uno aceptó)
        SELECT 'acceptance_successful' AS result_status, 'Solicitud de amistad aceptada.' AS message;
    ELSE
        SELECT 'request_not_found_or_not_pending' AS result_status, 'No se encontró la solicitud pendiente o ya fue gestionada.' AS message;
    END IF;
END$$
DELIMITER ;


DELIMITER $$
-- Rechazar una solicitud de amistad
CREATE PROCEDURE sp_RejectFriendRequest(
    IN p_requester_id VARCHAR(15), -- Quien envió la solicitud originalmente
    IN p_rejecter_id VARCHAR(15)   -- Quien está rechazando (el usuario actual)
)
BEGIN
    -- Cambiar el estado a 'rejected'. Considera si quieres borrar el registro en su lugar.
    UPDATE friends
    SET stat = 'rejected'
    WHERE user_id = p_requester_id AND friend_id = p_rejecter_id AND stat = 'pending';

    IF ROW_COUNT() > 0 THEN
        SELECT 'rejection_successful' AS result_status, 'Solicitud de amistad rechazada.' AS message;
    ELSE
        SELECT 'request_not_found_or_not_pending' AS result_status, 'No se encontró la solicitud pendiente o ya fue gestionada.' AS message;
    END IF;
END$$
DELIMITER ;


DELIMITER $$
-- Obtener solicitudes de amistad pendientes para un usuario
CREATE PROCEDURE sp_GetPendingFriendRequests(
    IN p_current_user_id VARCHAR(15)
)
BEGIN
    SELECT
        f.user_id AS requester_id,         -- El ID del usuario que envió la solicitud
        u.username AS requester_username,
        u.profile_picture AS requester_avatar
    FROM friends f
    JOIN users u ON f.user_id = u.id_name
    WHERE f.friend_id = p_current_user_id AND f.stat = 'pending'
    ORDER BY f.created_at DESC;
                              
END$$
DELIMITER ;

DELIMITER $$
-- Obtener el estado de amistad entre dos usuarios
CREATE PROCEDURE sp_GetFriendshipStatus(
    IN p_viewer_id VARCHAR(15),  -- El usuario que está viendo el perfil
    IN p_profile_id VARCHAR(15) -- El usuario del perfil que se está viendo
)
BEGIN
    DECLARE status_viewer_to_profile ENUM('pending', 'accepted', 'rejected') DEFAULT NULL;
    DECLARE status_profile_to_viewer ENUM('pending', 'accepted', 'rejected') DEFAULT NULL;

    IF p_viewer_id = p_profile_id THEN
        SELECT 'own_profile' AS friendship_status;
    ELSE
        SELECT stat INTO status_viewer_to_profile FROM friends WHERE user_id = p_viewer_id AND friend_id = p_profile_id LIMIT 1;
        SELECT stat INTO status_profile_to_viewer FROM friends WHERE user_id = p_profile_id AND friend_id = p_viewer_id LIMIT 1;

        IF status_viewer_to_profile = 'accepted' -- AND status_profile_to_viewer = 'accepted' (implícito si la lógica de aceptar es correcta)
        THEN
            SELECT 'friends' AS friendship_status;
        ELSEIF status_viewer_to_profile = 'pending' THEN
            SELECT 'request_sent' AS friendship_status; -- viewer envió a profile
        ELSEIF status_profile_to_viewer = 'pending' THEN
            SELECT 'request_received' AS friendship_status; -- viewer recibió de profile
        ELSE
            SELECT 'not_friends' AS friendship_status;
        END IF;
    END IF;
END$$
DELIMITER ;

-- (Opcional) Stored Procedure para obtener la lista de amigos aceptados (para el feed)
DELIMITER $$
CREATE PROCEDURE sp_GetAcceptedFriendIds(
    IN p_user_id VARCHAR(15)
)
BEGIN
    SELECT friend_id
    FROM friends
    WHERE user_id = p_user_id AND stat = 'accepted';
END$$
DELIMITER ;