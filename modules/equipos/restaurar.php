<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: /inventario_ti/login.php');
    exit();
}

if ($_SESSION['user_rol'] != 1) {
    header('Location: listar_eliminados.php?error=No tienes permisos');
    exit();
}

require_once '../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['equipo_id'])) {
    header('Location: listar_eliminados.php?error=Solicitud inválida');
    exit();
}

$equipo_id = intval($_POST['equipo_id']);
$observaciones = trim($_POST['observaciones'] ?? '');

// Verificar que el equipo exista y esté eliminado
$check = $conn->query("SELECT id FROM equipos WHERE id = $equipo_id AND fecha_eliminacion IS NOT NULL");
if ($check->num_rows == 0) {
    header('Location: listar_eliminados.php?error=Equipo no encontrado o no eliminado');
    exit();
}

// Restaurar: quitar fecha_eliminacion y eliminado_por, y poner estado Disponible
$sql = "UPDATE equipos SET fecha_eliminacion = NULL, eliminado_por = NULL, estado = 'Disponible' WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $equipo_id);
if ($stmt->execute()) {
    // Opcional: registrar un movimiento de restauración si tienes tabla de movimientos
    // Por ejemplo, insertar en movimientos si quieres
    header('Location: listar_eliminados.php?mensaje=Equipo restaurado correctamente a bodega');
} else {
    header('Location: listar_eliminados.php?error=' . urlencode('Error al restaurar: ' . $conn->error));
}
$stmt->close();
exit();
?>