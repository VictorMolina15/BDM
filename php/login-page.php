<?php
session_start();
require_once 'back-end/connection.php';

$msg = ""; // Inicializar mensaje
$tipo = ""; // 'success' o 'error'

$db = new DBConnection();
$conn = $db->getConnection();

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $user = $_POST['user'];
    $pass = $_POST['password'];

    $stmt = $conn->prepare("CALL sp_LogUser(?)");
    $stmt->execute([$user]);
    $userdata = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($userdata && password_verify($pass, $userdata['pass'])) {
        $_SESSION['id_name'] = $userdata['id_name'];
        $_SESSION['username'] = $userdata['username'];
        $_SESSION['avatar'] = $userdata['profile_picture'];

        header("Location: home-page.php"); // Redirigir a la página principal

    } else {
        $msg = "¡Error en las credenciales!";
        $tipo = "error";
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
</head>
<body>
<button class="button" onclick="location='index.php'">Volver</button>
<div align="center">
    <br><br><br><br><br><br><br><br><br><br><br>
    <form method="post" class="formulario">
        <p class="cuenta-gratis">Inicia Sesión</p>
        <input type="text" id="user" name="user" placeholder="Usuario o Email" required>
        <input type="password" id="password" name="password" placeholder="Password" required>
        <p><input type="checkbox" id="showPasswordCheckbox" name="showPasswordCheckbox"> <label for="showPasswordCheckbox">Mostrar Contraseña</label></p>
        <input type="submit" id="LoginBtn" value="Login">
    </form>
    
</div>
<?php if (!empty($msg)): ?>
        <div class="msg <?= $tipo ?>"><?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>
<script type="module" src="../js/LoginFireBase.js"></script>
<script>
    document.getElementById("showPasswordCheckbox").addEventListener("change", function() {
        var passwordInput = document.getElementById("password");
        passwordInput.type = this.checked ? "text" : "password";
    });
</script>
</body>
</html>
