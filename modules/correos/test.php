<?php
session_start();
require_once '../../config/database.php';

// Verificar sesión y rol
if (!isset($_SESSION['usuario_id'])) {
    die("❌ No hay sesión iniciada. <a href='/inventario_ti/login.php'>Ir a login</a>");
}

if ($_SESSION['rol_id'] != 1) {
    die("❌ Solo administradores pueden acceder. Tu rol: " . $_SESSION['rol_id']);
}

echo "✅ SESIÓN VÁLIDA<br>";
echo "Usuario ID: " . $_SESSION['usuario_id'] . "<br>";
echo "Rol: " . $_SESSION['rol_id'] . " (Admin)<br>";
echo "<br>";

// Probar conexión
$conn = getDBConnection();
if ($conn) {
    echo "✅ CONEXIÓN A BD EXITOSA<br>";
    
    // Probar consulta
    $sql = "SELECT COUNT(*) as total FROM personas";
    $result = $conn->query($sql);
    if ($result) {
        $row = $result->fetch_assoc();
        echo "✅ CONSULTA EXITOSA - Total personas: " . $row['total'] . "<br>";
    } else {
        echo "❌ ERROR EN CONSULTA: " . $conn->error . "<br>";
    }
    
    $conn->close();
} else {
    echo "❌ ERROR DE CONEXIÓN<br>";
}

echo "<br><a href='/inventario_ti/modules/correos/listar.php'>← Volver a Gestión de Correos</a>";
?>
