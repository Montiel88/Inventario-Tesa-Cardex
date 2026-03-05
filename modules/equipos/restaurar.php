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
$destino = $_POST['destino'] ?? 'bodega';
$persona_id = intval($_POST['persona_id'] ?? 0);
$ubicacion_id = intval($_POST['ubicacion_id'] ?? 0);
$observaciones = trim($_POST['observaciones'] ?? '');

if (!in_array($destino, ['bodega', 'persona', 'ubicacion'], true)) {
    header('Location: listar_eliminados.php?error=Destino inválido');
    exit();
}

// Verificar que el equipo exista y esté eliminado
$check = $conn->query("SELECT id FROM equipos WHERE id = $equipo_id AND fecha_eliminacion IS NOT NULL");
if ($check->num_rows == 0) {
    header('Location: listar_eliminados.php?error=Equipo no encontrado o no eliminado');
    exit();
}

// Compatibilidad con instalaciones que no tienen eliminado_por
$check_eliminado_por = $conn->query("SHOW COLUMNS FROM equipos LIKE 'eliminado_por'");
$tiene_eliminado_por = $check_eliminado_por && $check_eliminado_por->num_rows > 0;

$conn->begin_transaction();
try {
    if ($tiene_eliminado_por) {
        $sql_restore = "UPDATE equipos SET fecha_eliminacion = NULL, eliminado_por = NULL WHERE id = ?";
    } else {
        $sql_restore = "UPDATE equipos SET fecha_eliminacion = NULL WHERE id = ?";
    }

    $stmt_restore = $conn->prepare($sql_restore);
    $stmt_restore->bind_param("i", $equipo_id);
    if (!$stmt_restore->execute()) {
        throw new Exception("No se pudo restaurar el equipo");
    }
    $stmt_restore->close();

    if ($destino === 'bodega') {
        $stmt_equipo = $conn->prepare("UPDATE equipos SET estado = 'Disponible', ubicacion_id = NULL WHERE id = ?");
        $stmt_equipo->bind_param("i", $equipo_id);
        if (!$stmt_equipo->execute()) {
            throw new Exception("No se pudo actualizar el estado del equipo");
        }
        $stmt_equipo->close();
        $mensaje = 'Equipo restaurado correctamente en bodega.';
    }

    if ($destino === 'ubicacion') {
        if ($ubicacion_id <= 0) {
            throw new Exception("Debes seleccionar una ubicación");
        }

        $stmt_ubic = $conn->prepare("UPDATE equipos SET estado = 'Disponible', ubicacion_id = ? WHERE id = ?");
        $stmt_ubic->bind_param("ii", $ubicacion_id, $equipo_id);
        if (!$stmt_ubic->execute()) {
            throw new Exception("No se pudo asignar la ubicación");
        }
        $stmt_ubic->close();
        $mensaje = 'Equipo restaurado y asignado a la ubicación seleccionada.';
    }

    if ($destino === 'persona') {
        if ($persona_id <= 0) {
            throw new Exception("Debes seleccionar una persona");
        }

        // Cerrar cualquier asignación activa previa por consistencia
        $stmt_cerrar = $conn->prepare("UPDATE asignaciones SET fecha_devolucion = NOW() WHERE equipo_id = ? AND fecha_devolucion IS NULL");
        $stmt_cerrar->bind_param("i", $equipo_id);
        if (!$stmt_cerrar->execute()) {
            throw new Exception("No se pudo actualizar historial de asignaciones");
        }
        $stmt_cerrar->close();

        // Insert dinámico para compatibilidad de columnas (estado/observaciones)
        $check_estado = $conn->query("SHOW COLUMNS FROM asignaciones LIKE 'estado'");
        $check_obs = $conn->query("SHOW COLUMNS FROM asignaciones LIKE 'observaciones'");
        $tiene_estado = $check_estado && $check_estado->num_rows > 0;
        $tiene_obs = $check_obs && $check_obs->num_rows > 0;

        if ($tiene_estado && $tiene_obs) {
            $stmt_asig = $conn->prepare("INSERT INTO asignaciones (equipo_id, persona_id, fecha_asignacion, estado, observaciones) VALUES (?, ?, NOW(), 'Activo', ?)");
            $stmt_asig->bind_param("iis", $equipo_id, $persona_id, $observaciones);
        } elseif ($tiene_estado) {
            $stmt_asig = $conn->prepare("INSERT INTO asignaciones (equipo_id, persona_id, fecha_asignacion, estado) VALUES (?, ?, NOW(), 'Activo')");
            $stmt_asig->bind_param("ii", $equipo_id, $persona_id);
        } elseif ($tiene_obs) {
            $stmt_asig = $conn->prepare("INSERT INTO asignaciones (equipo_id, persona_id, fecha_asignacion, observaciones) VALUES (?, ?, NOW(), ?)");
            $stmt_asig->bind_param("iis", $equipo_id, $persona_id, $observaciones);
        } else {
            $stmt_asig = $conn->prepare("INSERT INTO asignaciones (equipo_id, persona_id, fecha_asignacion) VALUES (?, ?, NOW())");
            $stmt_asig->bind_param("ii", $equipo_id, $persona_id);
        }

        if (!$stmt_asig->execute()) {
            throw new Exception("No se pudo crear la asignación de restauración");
        }
        $stmt_asig->close();

        $stmt_estado = $conn->prepare("UPDATE equipos SET estado = 'Asignado' WHERE id = ?");
        $stmt_estado->bind_param("i", $equipo_id);
        if (!$stmt_estado->execute()) {
            throw new Exception("No se pudo actualizar el estado del equipo");
        }
        $stmt_estado->close();

        $mensaje = 'Equipo restaurado y asignado a la persona seleccionada.';
    }

    $conn->commit();
    header('Location: listar_eliminados.php?mensaje=' . urlencode($mensaje));
} catch (Exception $e) {
    $conn->rollback();
    header('Location: listar_eliminados.php?error=' . urlencode('Error al restaurar: ' . $e->getMessage()));
}
exit();
?>
