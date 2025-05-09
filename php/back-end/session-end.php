<?php
session_start();
session_unset();
session_destroy();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Cerrando sesión...</title>
    <script>
        localStorage.removeItem('settings');
        window.location.href = "../index.php";
    </script>
</head>
<body>
    <p>Cerrando sesión...</p>
</body>
</html>
