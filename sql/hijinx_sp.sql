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