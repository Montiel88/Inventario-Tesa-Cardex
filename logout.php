<?php
session_start();
require_once 'config/database.php';
require_once 'includes/logs_functions.php';

// Registrar log de cierre de sesión antes de destruir la sesión
if (isset($_SESSION['user_id'])) {
    $usuario_id = $_SESSION['user_id'];
    registrarLog($conn, 'Cierre de sesión', 'Usuario cerró sesión', $usuario_id);
}

session_destroy();
$conn->close();
header('Location: login.php');
exit();
?>