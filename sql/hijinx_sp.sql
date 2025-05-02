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