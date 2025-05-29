<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Verificamos si el usuario ha iniciado sesión
if (!isset($_SESSION['id_name'])) {
    // Si no está logueado, redirigimos al login
    header("Location: /BDM/php/index.php");
    exit();
}
?>