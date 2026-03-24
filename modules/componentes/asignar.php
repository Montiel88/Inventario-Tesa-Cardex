<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: /inventario_ti/login.php');
    exit();
}
if ($_SESSION['user_rol'] != 1) {
    header('Location: listar.php?error=No tienes permisos');
    exit();
}

require_once '../../config/database.php';
include '../../includes/header.php';

$componente_id = intval($_GET['id'] ?? 0);
if (!$componente_id) {
    header('Location: listar.php');
    exit();
}

// Verificar que el componente esté disponible (usando misma lógica)
$check = $conn->query("SELECT COUNT(*) as total FROM movimientos_componentes mc 
                       WHERE mc.componente_id = $componente_id 
                         AND mc.tipo_movimiento = 'ASIGNACION'
                         AND NOT EXISTS (
                             SELECT 1 FROM movimientos_componentes mc2 
                             WHERE mc2.componente_id = mc.componente_id 
                               AND mc2.tipo_movimiento = 'DEVOLUCION'
                               AND mc2.fecha_movimiento > mc.fecha_movimiento
                         )");
$row = $check->fetch_assoc();
if ($row['total'] > 0) {
    header('Location: listar.php?error=El componente ya está asignado');
    exit();
}

// Obtener lista de personas
$personas = $conn->query("SELECT id, nombres, cedula FROM personas ORDER BY nombres");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $persona_id = intval($_POST['persona_id']);
    $observaciones = $conn->real_escape_string($_POST['observaciones'] ?? '');

    $sql = "INSERT INTO movimientos_componentes (componente_id, persona_id, tipo_movimiento, observaciones)
            VALUES ($componente_id, $persona_id, 'ASIGNACION', '$observaciones')";
    if ($conn->query($sql)) {
        // Registrar notificación
        require_once '../../config/notificaciones_helper.php';
        
        // Obtener datos del componente y persona para la notificación
        $comp_info = $conn->query("SELECT nombre_componente, tipo FROM componentes WHERE id = $componente_id")->fetch_assoc();
        $per_info = $conn->query("SELECT nombres FROM personas WHERE id = $persona_id")->fetch_assoc();
        
        $componente_nombre = $comp_info['nombre_componente'] ?? 'Componente';
        $persona_nombre = $per_info['nombres'] ?? 'sin nombre';
        
        registrar_notificacion(
            $_SESSION['user_id'],
            'success',
            '🔧 Componente asignado',
            "Componente {$componente_nombre} asignado a {$persona_nombre}",
            "/inventario_ti/modules/componentes/detalle.php?id={$componente_id}"
        );
        
        header('Location: listar.php?mensaje=Componente asignado correctamente');
    } else {
        $error = "Error al asignar: " . $conn->error;
    }
}
?>

<!-- Formulario simple -->
<div class="container py-4">
    <div class="card">
        <div class="card-header">
            <h4>Asignar Componente</h4>
        </div>
        <div class="card-body">
            <?php if (isset($error)) echo "<div class='alert alert-danger'>$error</div>"; ?>
            <form method="POST">
                <div class="mb-3">
                    <label>Persona</label>
                    <select name="persona_id" class="form-control" required>
                        <option value="">-- Seleccione --</option>
                        <?php while($p = $personas->fetch_assoc()): ?>
                        <option value="<?php echo $p['id']; ?>"><?php echo $p['nombres'] . ' - ' . $p['cedula']; ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label>Observaciones</label>
                    <textarea name="observaciones" class="form-control" rows="2"></textarea>
                </div>
                <button type="submit" class="btn btn-primary">Asignar</button>
                <a href="listar.php" class="btn btn-secondary">Cancelar</a>
            </form>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>