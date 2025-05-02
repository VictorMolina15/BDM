<?php
session_start();
// Verificamos si el usuario ha iniciado sesión
if (!isset($_SESSION['id_name'])) {
    // Si no está logueado, redirigimos al login
    header("Location: ../index.php");
    exit();
}
?>