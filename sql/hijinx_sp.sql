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