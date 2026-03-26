<?php
$host = 'localhost';
$user = 'root';      // Usuario por defecto de XAMPP
$password = '';      // Contraseña vacía en XAMPP
$database = 'inventario_ti';

$conn = new mysqli($host, $user, $password, $database);

if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

$conn->set_charset("utf8");
?>