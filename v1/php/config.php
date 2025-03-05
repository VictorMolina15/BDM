<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "your_database_name";
$port = 33065;

// Crear conexión
$conn = new mysqli($servername, $username, $password, $dbname, $port);

// Verificar conexión
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}
echo "Conexión exitosa";
?>