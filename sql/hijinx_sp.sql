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
-- --------------- Stored Procedures para Bloqueos/Reportes ---------------

DELIMITER $$
-- Bloquear/Reportar un Usuario
CREATE PROCEDURE sp_BlockOrReportUser(
    IN p_reporting_user_id VARCHAR(15),
    IN p_reported_user_id VARCHAR(15),
    IN p_reason TEXT
)
BEGIN
    IF p_reporting_user_id = p_reported_user_id THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'No puedes bloquearte o reportarte a ti mismo.';
    ELSE
        -- Verificar si ya existe un bloqueo activo para este par
        IF NOT EXISTS (SELECT 1 FROM reports WHERE reporting_user_id = p_reporting_user_id AND reported_user_id = p_reported_user_id AND reporting_post_id IS NULL) THEN
            INSERT INTO reports (reporting_user_id, reported_user_id, reason, reporting_post_id)
            VALUES (p_reporting_user_id, p_reported_user_id, p_reason, NULL);
            SELECT 'user_blocked' AS result_status, 'Usuario bloqueado/reportado exitosamente.' AS message;
        ELSE
            -- Si ya existe, se podría actualizar la razón o simplemente informar
            UPDATE reports
            SET reason = p_reason, created_at = CURRENT_TIMESTAMP
            WHERE reporting_user_id = p_reporting_user_id AND reported_user_id = p_reported_user_id AND reporting_post_id IS NULL;
            SELECT 'block_updated' AS result_status, 'Bloqueo/reporte de usuario actualizado.' AS message;
        END IF;
    END IF;
END$$
DELIMITER ;

DELIMITER $$
-- Bloquear/Reportar una Publicación
CREATE PROCEDURE sp_BlockOrReportPost(
    IN p_reporting_user_id VARCHAR(15),
    IN p_reporting_post_id INT,
    IN p_reason TEXT
)
BEGIN
    DECLARE v_post_author_id VARCHAR(15);

    -- (Opcional) Obtener el autor del post para registrarlo si es necesario
    -- SELECT user_id INTO v_post_author_id FROM posts WHERE id = p_reporting_post_id;
    -- Por ahora, el reported_user_id será NULL si es un reporte de post específico

    IF NOT EXISTS (SELECT 1 FROM reports WHERE reporting_user_id = p_reporting_user_id AND reporting_post_id = p_reporting_post_id) THEN
        INSERT INTO reports (reporting_user_id, reporting_post_id, reason, reported_user_id)
        VALUES (p_reporting_user_id, p_reporting_post_id, p_reason, NULL); -- O v_post_author_id si se obtiene
        SELECT 'post_blocked' AS result_status, 'Publicación bloqueada/reportada exitosamente.' AS message;
    ELSE
        UPDATE reports
        SET reason = p_reason, created_at = CURRENT_TIMESTAMP
        WHERE reporting_user_id = p_reporting_user_id AND reporting_post_id = p_reporting_post_id;
        SELECT 'block_updated' AS result_status, 'Bloqueo/reporte de publicación actualizado.' AS message;
    END IF;
END$$
DELIMITER ;

DELIMITER $$
-- Desbloquear un Usuario
CREATE PROCEDURE sp_UnblockUser(
    IN p_reporting_user_id VARCHAR(15),
    IN p_reported_user_id VARCHAR(15)
)
BEGIN
    DELETE FROM reports
    WHERE reporting_user_id = p_reporting_user_id AND reported_user_id = p_reported_user_id AND reporting_post_id IS NULL;
    IF ROW_COUNT() > 0 THEN
        SELECT 'user_unblocked' AS result_status, 'Usuario desbloqueado.' AS message;
    ELSE
        SELECT 'block_not_found' AS result_status, 'No se encontró un bloqueo activo para este usuario.' AS message;
    END IF;
END$$
DELIMITER ;

DELIMITER $$
-- Desbloquear una Publicación
CREATE PROCEDURE sp_UnblockPost(
    IN p_reporting_user_id VARCHAR(15),
    IN p_reporting_post_id INT
)
BEGIN
    DELETE FROM reports
    WHERE reporting_user_id = p_reporting_user_id AND reporting_post_id = p_reporting_post_id;
    IF ROW_COUNT() > 0 THEN
        SELECT 'post_unblocked' AS result_status, 'Publicación desbloqueada.' AS message;
    ELSE
        SELECT 'block_not_found' AS result_status, 'No se encontró un bloqueo activo para esta publicación.' AS message;
    END IF;
END$$
DELIMITER ;

DELIMITER $$
-- Obtener la lista de bloqueos/reportes de un usuario
CREATE PROCEDURE sp_GetUserBlocksAndReports(
    IN p_current_user_id VARCHAR(15)
)
BEGIN
    SELECT
        r.id AS report_id,
        r.reason,
        r.created_at,
        r.reported_user_id,
        u.username AS reported_username,
        u.profile_picture AS reported_user_avatar,
        r.reporting_post_id,
        SUBSTRING(p.content, 1, 100) AS post_content_preview, -- Muestra un preview del contenido del post
        p_author.id_name AS post_author_id,
        p_author.username AS post_author_username
    FROM reports r
    LEFT JOIN users u ON r.reported_user_id = u.id_name
    LEFT JOIN posts p ON r.reporting_post_id = p.id
    LEFT JOIN users p_author ON p.user_id = p_author.id_name -- Para obtener el autor del post
    WHERE r.reporting_user_id = p_current_user_id
    ORDER BY r.created_at DESC;
END$$
DELIMITER ;

-- Para el Feed (sp_GetFeedPosts del paso anterior, ahora con filtros de bloqueo)
DELIMITER $$
CREATE PROCEDURE sp_GetFeedPosts(
    IN p_current_user_id VARCHAR(15),
    IN p_limit INT,
    IN p_offset INT
)
BEGIN
    SELECT p.*, u.username, u.profile_picture
    FROM posts p
    JOIN users u ON p.user_id = u.id_name
    WHERE
        (
            p.user_id = p_current_user_id OR -- Publicaciones propias
            p.user_id IN (SELECT friend_id FROM friends WHERE user_id = p_current_user_id AND stat = 'accepted') -- Publicaciones de amigos
            -- Aquí podrías añadir lógica para posts de comunidades a las que pertenece, etc.
        )
        AND p.user_id NOT IN ( -- Excluir posts de usuarios bloqueados por el usuario actual
            SELECT rep.reported_user_id
            FROM reports rep
            WHERE rep.reporting_user_id = p_current_user_id AND rep.reported_user_id IS NOT NULL
        )
        AND p.id NOT IN ( -- Excluir posts específicos bloqueados por el usuario actual
            SELECT rep.reporting_post_id
            FROM reports rep
            WHERE rep.reporting_user_id = p_current_user_id AND rep.reporting_post_id IS NOT NULL
        )
        AND p.user_id NOT IN ( -- Excluir posts de usuarios que han bloqueado al usuario actual
             SELECT rep.reporting_user_id
             FROM reports rep
             WHERE rep.reported_user_id = p_current_user_id AND rep.reporting_user_id IS NOT NULL
        )
    ORDER BY p.created_at DESC
    LIMIT p_limit OFFSET p_offset;
END$$
DELIMITER ;

DELIMITER $$
CREATE PROCEDURE sp_SearchGlobalUsers (
    IN p_search_query VARCHAR(255),
    IN p_viewer_id VARCHAR(15) -- Usuario que realiza la búsqueda
)
BEGIN
    DECLARE search_param VARCHAR(260);
    SET search_param = CONCAT('%', p_search_query, '%');

    SELECT u.id_name, u.username, u.profile_picture
    FROM users u
    WHERE (u.id_name LIKE search_param OR u.username LIKE search_param)
      AND u.id_name != p_viewer_id -- No mostrarse a sí mismo
      AND u.id_name NOT IN ( -- Excluir usuarios bloqueados por el viewer
          SELECT rep.reported_user_id
          FROM reports rep
          WHERE rep.reporting_user_id = p_viewer_id AND rep.reported_user_id IS NOT NULL
      )
      AND u.id_name NOT IN ( -- Excluir usuarios que han bloqueado al viewer
          SELECT rep.reporting_user_id
          FROM reports rep
          WHERE rep.reported_user_id = p_viewer_id AND rep.reporting_user_id IS NOT NULL
      );
END$$
DELIMITER ;


DELIMITER $$

-- Create a Post (with optional media)
CREATE PROCEDURE sp_CreatePost(
    IN p_user_id VARCHAR(15),
    IN p_content TEXT,
    IN p_media_path VARCHAR(255),
    IN p_media_type ENUM('image', 'video', 'none') -- 'none' if no media
)
BEGIN
    DECLARE v_post_id INT;

    INSERT INTO posts (user_id, content, likes, created_at)
    VALUES (p_user_id, p_content, 0, NOW());

    SET v_post_id = LAST_INSERT_ID();

    IF p_media_path IS NOT NULL AND p_media_type != 'none' THEN
        INSERT INTO multimedia (post_id, media, media_type)
        VALUES (v_post_id, p_media_path, p_media_type);
    END IF;

    -- Return the created post details for immediate display (optional)
    SELECT
        p.id AS post_id,
        p.user_id,
        u.username AS author_username,
        u.profile_picture AS author_avatar,
        p.content,
        p.likes,
        p.created_at,
        m.media AS media_path,
        m.media_type
    FROM posts p
    JOIN users u ON p.user_id = u.id_name
    LEFT JOIN multimedia m ON p.id = m.post_id
    WHERE p.id = v_post_id;
END$$

-- View for simplified post fetching (recommended)
CREATE OR REPLACE VIEW view_post_feed_details AS
SELECT
    p.id AS post_id,
    p.user_id AS author_id,
    u.username AS author_username,
    u.profile_picture AS author_avatar,
    p.content,
    p.likes,
    p.created_at,
    m.media AS media_path,
    m.media_type
FROM posts p
JOIN users u ON p.user_id = u.id_name
LEFT JOIN multimedia m ON p.id = m.post_id
$$

-- Modify sp_GetFeedPosts to use the view and include media
DROP PROCEDURE IF EXISTS sp_GetFeedPosts$$
CREATE PROCEDURE sp_GetFeedPosts(
    IN p_current_user_id VARCHAR(15),
    IN p_profile_user_id VARCHAR(15), -- NULL for general feed, specific user_id for profile feed
    IN p_limit INT,
    IN p_offset INT
)
BEGIN
    SELECT v.*
    FROM view_post_feed_details v
    WHERE
        (
            -- For a specific user's profile page (show all their posts)
            (p_profile_user_id IS NOT NULL AND v.author_id = p_profile_user_id)
            OR
            -- For the general homepage feed (own posts + friends' posts)
            (p_profile_user_id IS NULL AND
                (
                    v.author_id = p_current_user_id OR
                    v.author_id IN (SELECT friend_id FROM friends WHERE user_id = p_current_user_id AND stat = 'accepted')
                )
            )
        )
        -- Blocking Logic (copied from your existing sp_GetFeedPosts)
        AND v.author_id NOT IN (
            SELECT rep.reported_user_id
            FROM reports rep
            WHERE rep.reporting_user_id = p_current_user_id AND rep.reported_user_id IS NOT NULL
        )
        AND v.post_id NOT IN (
            SELECT rep.reporting_post_id
            FROM reports rep
            WHERE rep.reporting_user_id = p_current_user_id AND rep.reporting_post_id IS NOT NULL
        )
        AND v.author_id NOT IN (
             SELECT rep.reporting_user_id
             FROM reports rep
             WHERE rep.reported_user_id = p_current_user_id AND rep.reporting_user_id IS NOT NULL
        )
    ORDER BY v.created_at DESC
    LIMIT p_limit OFFSET p_offset;
END$$

-- Add a Comment
CREATE PROCEDURE sp_AddComment(
    IN p_post_id INT,
    IN p_user_id VARCHAR(15),
    IN p_content TEXT,
    OUT p_comment_id INT
)
BEGIN
    INSERT INTO comments (post_id, user_id, content, likes, created_at)
    VALUES (p_post_id, p_user_id, p_content, 0, NOW());
    SET p_comment_id = LAST_INSERT_ID();

    SELECT c.id, c.post_id, c.user_id, u.username as commenter_username, u.profile_picture as commenter_avatar, c.content, c.likes, c.created_at
    FROM comments c
    JOIN users u ON c.user_id = u.id_name
    WHERE c.id = p_comment_id;
END$$

-- Get Comments for a Post
CREATE PROCEDURE sp_GetCommentsForPost(
    IN p_post_id INT,
    IN p_limit INT,
    IN p_offset INT
)
BEGIN
    SELECT c.id, c.post_id, c.user_id, u.username as commenter_username, u.profile_picture as commenter_avatar, c.content, c.likes, c.created_at
    FROM comments c
    JOIN users u ON c.user_id = u.id_name
    WHERE c.post_id = p_post_id
    ORDER BY c.created_at ASC -- Or DESC if you prefer newest first
    LIMIT p_limit OFFSET p_offset;
END$$

DELIMITER $$

-- Alternar el "Me Gusta" de un usuario en un post
CREATE PROCEDURE sp_TogglePostLike(
    IN p_post_id INT,
    IN p_user_id_liking VARCHAR(15)
)
BEGIN
    DECLARE v_liked_already BOOLEAN;

    -- Verificar si el usuario ya le dio "Me Gusta"
    SELECT EXISTS(SELECT 1 FROM post_likes WHERE post_id = p_post_id AND user_id = p_user_id_liking) INTO v_liked_already;

    IF v_liked_already THEN
        -- Ya le dio "Me Gusta", entonces quitarlo (Unlike)
        DELETE FROM post_likes WHERE post_id = p_post_id AND user_id = p_user_id_liking;
    ELSE
        -- No le ha dado "Me Gusta", entonces agregarlo (Like)
        INSERT INTO post_likes (post_id, user_id) VALUES (p_post_id, p_user_id_liking);
    END IF;

    -- Devolver el nuevo estado y conteo de "Me Gusta"
    SELECT
        (SELECT COUNT(*) FROM post_likes WHERE post_id = p_post_id) AS new_like_count,
        NOT v_liked_already AS liked_status; -- Si antes estaba likeado (v_liked_already=TRUE), ahora el status es FALSE (no likeado)

END$$

DELIMITER ;

DELIMITER $$

CREATE TRIGGER trg_after_post_like_insert
AFTER INSERT ON post_likes
FOR EACH ROW
BEGIN
    UPDATE posts SET likes = likes + 1 WHERE id = NEW.post_id;
END$$

CREATE TRIGGER trg_after_post_like_delete
AFTER DELETE ON post_likes
FOR EACH ROW
BEGIN
    UPDATE posts SET likes = CASE WHEN likes > 0 THEN likes - 1 ELSE 0 END WHERE id = OLD.post_id;
END$$

DELIMITER ;

DELIMITER $$

CREATE FUNCTION func_HasUserLikedPost(
    p_user_id_checking VARCHAR(15),
    p_post_id_to_check INT
)
RETURNS BOOLEAN
DETERMINISTIC
READS SQL DATA
BEGIN
    DECLARE v_has_liked BOOLEAN DEFAULT FALSE;
    SELECT EXISTS(SELECT 1 FROM post_likes WHERE post_id = p_post_id_to_check AND user_id = p_user_id_checking) INTO v_has_liked;
    RETURN v_has_liked;
END$$

DELIMITER ;

DELIMITER $$

-- Crear Comunidad
CREATE PROCEDURE sp_CreateCommunity (
    IN p_creator_id VARCHAR(15),
    IN p_name VARCHAR(100),
    IN p_descrip TEXT,
    IN p_community_pic VARCHAR(255),
    IN p_cover_pic VARCHAR(255),
    OUT p_community_id INT
)
BEGIN
    DECLARE v_existing_community INT DEFAULT 0;

    -- Validar que el nombre de la comunidad no esté vacío
    IF p_name IS NULL OR TRIM(p_name) = '' THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'El nombre de la comunidad no puede estar vacío.';
    END IF;

    -- Validar unicidad del nombre de la comunidad
    SELECT COUNT(*) INTO v_existing_community FROM communities WHERE name_comm = p_name;
    IF v_existing_community > 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Ya existe una comunidad con este nombre.';
    END IF;

    INSERT INTO communities (name_comm, descrip, community_picture, cover_picture, creator_id, created_at)
    VALUES ( TRIM(p_name), TRIM(p_descrip), p_community_pic, p_cover_pic, p_creator_id, NOW());

    SET p_community_id = LAST_INSERT_ID();

    IF p_community_id > 0 THEN
        -- Agregar al creador como administrador
        INSERT INTO community_members (community_id, user_id, role_type, joined_at)
        VALUES (p_community_id, p_creator_id, 'admin', NOW());
        
        SELECT 'success' AS `status`, 'Comunidad creada exitosamente.' AS message, p_community_id AS community_id;
    ELSE
        SELECT 'error' AS `status`, 'No se pudo crear la comunidad.' AS message, NULL AS community_id;
    END IF;
END$$


-- Modificar sp_CreatePost para incluir community_id
DROP PROCEDURE IF EXISTS sp_CreatePost$$
CREATE PROCEDURE sp_CreatePost(
    IN p_user_id VARCHAR(15),
    IN p_content TEXT,
    IN p_media_path VARCHAR(255),
    IN p_media_type ENUM('image', 'video', 'none'),
    IN p_community_id INT -- Nuevo parámetro
)
BEGIN
    DECLARE v_post_id INT;

    -- Validación: si se provee p_community_id, verificar que el usuario es miembro
    IF p_community_id IS NOT NULL THEN
        IF NOT EXISTS (SELECT 1 FROM community_members WHERE community_id = p_community_id AND user_id = p_user_id) THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'No eres miembro de la comunidad especificada para publicar en ella.';
        END IF;
    END IF;

    INSERT INTO posts (user_id, content, likes, created_at, community_id) -- Añadido community_id
    VALUES (p_user_id, p_content, 0, NOW(), p_community_id); -- Añadido p_community_id

    SET v_post_id = LAST_INSERT_ID();

    IF p_media_path IS NOT NULL AND p_media_type != 'none' THEN
        INSERT INTO multimedia (post_id, media, media_type)
        VALUES (v_post_id, p_media_path, p_media_type);
    END IF;

    SELECT
        p.id AS post_id,
        p.user_id,
        u.username AS author_username,
        u.profile_picture AS author_avatar,
        p.content,
        p.likes,
        p.created_at,
        m.media AS media_path,
        m.media_type,
        p.community_id -- Devolver community_id
    FROM posts p
    JOIN users u ON p.user_id = u.id_name
    LEFT JOIN multimedia m ON p.id = m.post_id
    WHERE p.id = v_post_id;
END$$

-- Asegúrate que la VISTA view_post_feed_details incluya community_id
DROP VIEW IF EXISTS view_post_feed_details$$
CREATE VIEW view_post_feed_details AS
SELECT
    p.id AS post_id,
    p.user_id AS author_id,
    u.username AS author_username,
    u.profile_picture AS author_avatar,
    p.content,
    p.likes,
    p.created_at,
    m.media AS media_path,
    m.media_type,
    p.community_id -- Asegúrate que esta línea esté presente
FROM posts p
JOIN users u ON p.user_id = u.id_name
LEFT JOIN multimedia m ON p.id = m.post_id$$

DELIMITER ;

DELIMITER $$

CREATE PROCEDURE sp_GetCommunityDetails(
    IN p_community_id_to_view INT,
    IN p_current_user_id VARCHAR(15) -- El ID del usuario que está viendo la página de la comunidad
)
BEGIN
    SELECT
        c.id,
        c.name_comm,
        c.descrip,
        c.community_picture,
        c.cover_picture,
        c.creator_id,
        u_creator.username AS creator_username, -- Nombre del creador
        u_creator.profile_picture AS creator_avatar, -- Avatar del creador (opcional)
        c.created_at,
        (SELECT COUNT(*) FROM community_members cm WHERE cm.community_id = c.id) AS member_count,
        (SELECT cm_user.role_type FROM community_members cm_user WHERE cm_user.community_id = c.id AND cm_user.user_id = p_current_user_id LIMIT 1) AS current_user_role,
        EXISTS(SELECT 1 FROM community_members cm_user_exists WHERE cm_user_exists.community_id = c.id AND cm_user_exists.user_id = p_current_user_id) AS is_member
    FROM communities c
    LEFT JOIN users u_creator ON c.creator_id = u_creator.id_name -- Unir con users para obtener datos del creador
    WHERE c.id = p_community_id_to_view
    LIMIT 1; -- Asegura que solo devuelva una fila si por alguna razón hubiera IDs duplicados (no debería si es PK)
END$$

DELIMITER ;

DELIMITER $$

CREATE PROCEDURE sp_GetCommunityFeedPosts(
    IN p_current_user_id VARCHAR(15), -- Usuario que realiza la visualización (para filtros de bloqueo)
    IN p_community_id_to_view INT,  -- El ID de la comunidad cuyos posts se quieren ver
    IN p_limit INT,
    IN p_offset INT
)
BEGIN
    SELECT v.*
    FROM view_post_feed_details v -- Utiliza la vista que ya incluye detalles del post y del autor
    WHERE
        v.community_id = p_community_id_to_view -- Condición principal: posts de ESTA comunidad
        -- Lógica de Bloqueo (copiada de tu sp_GetFeedPosts existente)
        AND v.author_id NOT IN ( -- Excluir posts de usuarios bloqueados por el usuario actual
            SELECT rep.reported_user_id
            FROM reports rep
            WHERE rep.reporting_user_id = p_current_user_id AND rep.reported_user_id IS NOT NULL
        )
        AND v.post_id NOT IN ( -- Excluir posts específicos bloqueados por el usuario actual
            SELECT rep.reporting_post_id
            FROM reports rep
            WHERE rep.reporting_user_id = p_current_user_id AND rep.reporting_post_id IS NOT NULL
        )
        AND v.author_id NOT IN ( -- Excluir posts de usuarios que han bloqueado al usuario actual
             SELECT rep.reporting_user_id
             FROM reports rep
             WHERE rep.reported_user_id = p_current_user_id AND rep.reporting_user_id IS NOT NULL
        )
    ORDER BY v.created_at DESC
    LIMIT p_limit OFFSET p_offset;
END$$

DELIMITER ;

DELIMITER $$

DROP PROCEDURE IF EXISTS sp_JoinCommunity$$
CREATE PROCEDURE sp_JoinCommunity(
    IN p_user_id VARCHAR(15),
    IN p_community_id INT
)
BEGIN
    IF NOT EXISTS (SELECT 1 FROM communities WHERE id = p_community_id) THEN
        SELECT 'error' AS status, 'La comunidad no existe.' AS message;
    ELSEIF EXISTS (SELECT 1 FROM community_members WHERE community_id = p_community_id AND user_id = p_user_id) THEN
        SELECT 'success' AS status, 'Ya eres miembro de esta comunidad.' AS message; -- Considerado éxito si ya es miembro
    ELSE
        INSERT INTO community_members (community_id, user_id, role_type, joined_at)
        VALUES (p_community_id, p_user_id, 'member', NOW());
        SELECT 'success' AS status, 'Te has unido a la comunidad exitosamente.' AS message;
    END IF;
END$$

DROP PROCEDURE IF EXISTS sp_LeaveCommunity$$
CREATE PROCEDURE sp_LeaveCommunity(
    IN p_user_id VARCHAR(15),
    IN p_community_id INT
)
BEGIN
    DECLARE member_role ENUM('member', 'admin');
    DECLARE admin_count INT;

    IF NOT EXISTS (SELECT 1 FROM community_members WHERE community_id = p_community_id AND user_id = p_user_id) THEN
        SELECT 'success' AS status, 'No eres miembro de esta comunidad (o ya la has abandonado).' AS message; -- Éxito si no es miembro
    ELSE
        SELECT role_type INTO member_role FROM community_members WHERE community_id = p_community_id AND user_id = p_user_id;

        IF member_role = 'admin' THEN
            SELECT COUNT(*) INTO admin_count FROM community_members WHERE community_id = p_community_id AND role_type = 'admin';
            IF admin_count <= 1 THEN
                -- En lugar de SIGNAL, devolvemos un error que PHP pueda manejar
                SELECT 'error' AS status, 'No puedes abandonar la comunidad. Eres el único administrador.' AS message;
            ELSE
                DELETE FROM community_members WHERE community_id = p_community_id AND user_id = p_user_id;
                SELECT 'success' AS status, 'Has abandonado la comunidad exitosamente.' AS message;
            END IF;
        ELSE
            DELETE FROM community_members WHERE community_id = p_community_id AND user_id = p_user_id;
            SELECT 'success' AS status, 'Has abandonado la comunidad exitosamente.' AS message;
        END IF;
    END IF;
END$$

DELIMITER ;