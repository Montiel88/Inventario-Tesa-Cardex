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

// ============================================
// FUNCIÓN PARA REGISTRAR LOGS (AGREGADA)
// ============================================
function registrarLog($accion, $detalle = '') {
    global $conn;
    
    $usuario_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'NULL';
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $detalle = $conn->real_escape_string($detalle);
    
    $sql = "INSERT INTO logs (usuario_id, accion, detalle, ip, fecha) 
            VALUES ($usuario_id, '$accion', '$detalle', '$ip', NOW())";
    
    return $conn->query($sql);
}
?>