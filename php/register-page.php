<?php
$msg = ""; // Inicializar mensaje
$tipo = ""; // 'success' o 'error'

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $id_name = $_POST['usuario'];
    $username = $_POST['nombre'];
    $birth = $_POST['fecha'];
    $email = $_POST['email'];
    $pass = $_POST['password'];
    $passConfirm = $_POST['confirmPassword'];

    // Validaciones básicas

    if (preg_match('/\s/', $username)) {
        $msg = "El nombre de usuario no puede contener espacios.";
        $tipo = "error";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $msg = "El formato del email es inválido.";
        $tipo = "error";
    } elseif (strlen($username) < 3 || strlen($username) > 20) {
        $msg = "El nombre de usuario debe tener entre 3 y 20 caracteres.";
        $tipo = "error";
    }

    if (strlen($pass) < 8) {
        $msg = "La contraseña debe tener al menos 8 caracteres.";
        $tipo = "error";
    } elseif ($pass !== $passConfirm) {
        $msg = "Las contraseñas no coinciden.";
        $tipo = "error";
    } else {
        $passh = password_hash($pass, PASSWORD_DEFAULT);

        try {
            require_once 'back-end/connection.php';
            $db = new DBConnection();
            $conn = $db->getConnection();
        
            // Validar usuario existente
            $stmt = $conn->prepare("CALL sp_ValidateUser(?, ?)");
            $stmt->execute([$id_name, $email]);
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
            if (count($result) > 0) {
                $msg = "¡El Usuario o Correo ya está registrado!";
                $tipo = "error";
            } else {
                // Si no existe, registrar
                $stmt = $conn->prepare("CALL sp_RegisterUser(?, ?, ?, ?, ?)");
                $stmt->execute([$id_name, $username, $email, $passh, $birth]);
                $msg = "¡Registro exitoso!";
                $tipo = "success";
                header("Location: home-page.php");
                exit;
            }
        } catch (PDOException $e) {
            $msg = "Error en el proceso: " . $e->getMessage();
            $tipo = "error";
        }        
    }
}
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>HiJinx</title>
    <link href="https://fonts.googleapis.com/css2?family=Russo+One&display=swap" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="../css/style_v2.css">
    <!-- <script type="module" src="app.js"></script> -->
</head>

<body>
    <button class="button" onclick="location='index.php'">Volver</button>
    <div align="center">
        <form method="post" class="formulario" id="registroForm">
            <h2 class="create-account">Unete a la Comunidad.</h2>

            <p class="cuenta-gratis">Crea tu Cuenta.</p>
            <input type="text" id="nombre" name="nombre" placeholder="Nombre Visible"required>
            <input type="text" id="usuario" name="usuario" placeholder="Nombre Único" required>
            <input type="date" id="fecha" name="fecha" placeholder="Fecha de Nacimiento" max="2007-01-01" min="1925-01-01" placeholder="2003-01-01" required>
            <input type="email" id="email" name="email" placeholder="E-mail" required>
            <input type="password" id="password" name="password" placeholder="Password" required>
            <input type="password" id="confirmPassword" name="confirmPassword" placeholder="Confirm Password" required>
            <input type="submit" id="registrarseBtn" value="Registrarse">
        </form>
        <div class="message" align="center">
            <p>¿Ya eres parte de la comunidad? Inicia Sesión.</p>
            <button class="sign-up-btn" onclick="location='login-page.php'">Iniciar Sesión</button>
        </div>
        <?php if (!empty($msg)): ?>
            <div class="msg <?= $tipo ?>"><?= htmlspecialchars($msg) ?></div>
        <?php endif; ?>
    </div>

</body>
</html>