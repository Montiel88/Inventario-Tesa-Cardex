<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: /inventario_ti/login.php');
    exit();
}

// Verificar roles si es necesario
$es_admin = ($_SESSION['user_rol'] == 'admin');

// Solo admin puede acceder a ciertas funciones
if (!$es_admin && strpos($_SERVER['PHP_SELF'], 'eliminar.php') !== false) {
    header('Location: dashboard.php?error=No tienes permisos');
    exit();
}
?>
<?php
require_once '../../config/database.php';

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    
    // Verificar si tiene movimientos
    $sql_check = "SELECT COUNT(*) as total FROM movimientos WHERE producto_id = $id";
    $result = $conn->query($sql_check);
    $row = $result->fetch_assoc();
    
    if ($row['total'] > 0) {
        // Tiene movimientos, no se puede eliminar
        header('Location: listar.php?error=No se puede eliminar un producto con movimientos registrados');
    } else {
        // Se puede eliminar
        $sql = "DELETE FROM productos WHERE id = $id";
        if ($conn->query($sql)) {
            header('Location: listar.php');
        } else {
            header('Location: listar.php?error=Error al eliminar');
        }
    }
} else {
    header('Location: listar.php');
}
?>