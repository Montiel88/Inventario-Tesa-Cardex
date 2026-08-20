<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: /Inventario-Tesa-Cardex/login.php');
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

// Obtener listas de destino
$personas = $conn->query("SELECT id, nombres, cedula FROM personas ORDER BY nombres");
$salones = $conn->query("SELECT id, codigo_ubicacion, nombre, tipo
                         FROM ubicaciones
                         WHERE tipo IN ('salon', 'laboratorio', 'biblioteca', 'oficina', 'otro')
                         ORDER BY tipo, nombre");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $destino_tipo = $_POST['destino_tipo'] ?? 'persona';
    $persona_id = intval($_POST['persona_id'] ?? 0);
    $salon_id = intval($_POST['salon_id'] ?? 0);
    $observaciones = $conn->real_escape_string($_POST['observaciones'] ?? '');
    $errores = [];

    if (!in_array($destino_tipo, ['persona', 'salon'], true)) {
        $errores[] = 'Debe seleccionar un destino válido.';
    }

    $persona_nombre = '';
    $destino_nombre = '';
    $salon_nombre = '';

    if ($destino_tipo === 'persona') {
        if ($persona_id <= 0) {
            $errores[] = 'Debe seleccionar una persona.';
        } else {
            $per_info = $conn->query("SELECT nombres FROM personas WHERE id = $persona_id LIMIT 1");
            if (!$per_info || $per_info->num_rows === 0) {
                $errores[] = 'La persona seleccionada no existe.';
            } else {
                $persona_nombre = $per_info->fetch_assoc()['nombres'] ?? '';
                $destino_nombre = $persona_nombre;
            }
        }
    } else {
        if ($salon_id <= 0) {
            $errores[] = 'Debe seleccionar un salón.';
        } else {
            $sal_info = $conn->query("SELECT codigo_ubicacion, nombre FROM ubicaciones 
                                      WHERE id = $salon_id
                                        AND tipo IN ('salon', 'laboratorio', 'biblioteca', 'oficina', 'otro')
                                      LIMIT 1");
            if (!$sal_info || $sal_info->num_rows === 0) {
                $errores[] = 'El salón seleccionado no existe o no es válido.';
            } else {
                $sal_row = $sal_info->fetch_assoc();
                $salon_nombre = ($sal_row['codigo_ubicacion'] ?? '') . ' - ' . ($sal_row['nombre'] ?? '');
                $destino_nombre = $salon_nombre;
            }
        }
    }

    if (empty($errores)) {
        $persona_id_sql = $destino_tipo === 'persona' ? $persona_id : 'NULL';
        $obs_final = $observaciones;
        if ($destino_tipo === 'salon') {
            $obs_final = trim($obs_final . ' | Asignado al salón: ' . $destino_nombre);
        }

        $sql = "INSERT INTO movimientos_componentes (componente_id, persona_id, tipo_movimiento, observaciones)
                VALUES ($componente_id, $persona_id_sql, 'ASIGNACION', '$obs_final')";
        if ($conn->query($sql)) {
            // Compatibilidad: guardar ubicación actual del componente si la columna existe
            $check_ubic = $conn->query("SHOW COLUMNS FROM componentes LIKE 'ubicacion_id'");
            if ($check_ubic && $check_ubic->num_rows > 0) {
                $ubicacion_sql = $destino_tipo === 'salon' ? $salon_id : 'NULL';
                $conn->query("UPDATE componentes SET ubicacion_id = $ubicacion_sql WHERE id = $componente_id");
            }

            // Registrar notificación
            require_once '../../config/notificaciones_helper.php';
            
            $comp_info = $conn->query("SELECT nombre_componente, tipo FROM componentes WHERE id = $componente_id")->fetch_assoc();
            $componente_nombre = $comp_info['nombre_componente'] ?? 'Componente';
            $destino_texto = $destino_tipo === 'salon' ? "salón {$destino_nombre}" : $destino_nombre;
            
            registrar_notificacion(
                $_SESSION['user_id'],
                'success',
                '🔧 Componente asignado',
                "Componente {$componente_nombre} asignado a {$destino_texto}",
                "/Inventario-Tesa-Cardex/modules/componentes/detalle.php?id={$componente_id}"
            );
            
            // Registrar log de la operación
            require_once '../../includes/logs_functions.php';
            registrarLog($conn, 'Asignar componente', "Componente: {$componente_nombre}, Destino: {$destino_texto}", $_SESSION['user_id']);
            
            header('Location: listar.php?mensaje=Componente asignado correctamente');
            exit();
        } else {
            $error = "Error al asignar: " . $conn->error;
        }
    } else {
        $error = implode('<br>', $errores);
    }
}
?>

<style>
    .opcion-btn {
        background: rgba(90, 45, 140, 0.75) !important;
        border: 1px solid rgba(243, 178, 41, 0.35) !important;
        border-radius: 10px;
        padding: 20px;
        text-align: center;
        cursor: pointer;
        transition: all 0.3s;
        margin-bottom: 20px;
        color: #ffffff !important;
    }
    .opcion-btn:hover {
        background: rgba(90, 45, 140, 0.95) !important;
        transform: translateY(-2px);
        box-shadow: 0 12px 28px rgba(0, 0, 0, 0.25);
    }
    .opcion-btn.active {
        background: rgba(90, 45, 140, 0.95) !important;
        border-color: var(--c-gold, #f3b229) !important;
        box-shadow: 0 12px 28px rgba(0, 0, 0, 0.25);
        outline: 2px solid #f3b229;
    }
    .is-invalid { border-color: #dc3545 !important; }
</style>

<div class="container-fluid py-4">
    <div class="card">
        <div class="card-header">
            <h4 class="mb-0"><i class="fas fa-microchip me-2"></i>Asignar Componente a Persona o Salón</h4>
        </div>
        <div class="card-body">
            <?php if (isset($error)) echo "<div class='alert alert-danger'>$error</div>"; ?>
            <form method="POST" onsubmit="return false;" id="formComponente">
                <input type="hidden" name="destino_tipo" id="destino_tipo" value="persona">

                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="opcion-btn active" id="opcionPersona" onclick="seleccionarDestino('persona')">
                            <i class="fas fa-user fa-3x mb-2"></i>
                            <h5>Asignar a Persona</h5>
                            <p class="mb-0">Entregar el componente a un custodio</p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="opcion-btn" id="opcionSalon" onclick="seleccionarDestino('salon')">
                            <i class="fas fa-door-open fa-3x mb-2"></i>
                            <h5>Asignar a Salón</h5>
                            <p class="mb-0">Ubicar el componente en un aula, laboratorio o biblioteca</p>
                        </div>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6" id="panelPersona">
                        <label>Persona</label>
                        <select name="persona_id" id="persona_id" class="form-control">
                            <option value="">-- Seleccione --</option>
                            <?php while($p = $personas->fetch_assoc()): ?>
                            <option value="<?php echo $p['id']; ?>"><?php echo $p['nombres'] . ' - ' . $p['cedula']; ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-6" id="panelSalon" style="display:none;">
                        <label>Salón</label>
                        <select name="salon_id" id="salon_id" class="form-control">
                            <option value="">-- Seleccione --</option>
                            <?php while($s = $salones->fetch_assoc()): ?>
                            <option value="<?php echo $s['id']; ?>">
                                <?php echo htmlspecialchars($s['codigo_ubicacion'] . ' - ' . $s['nombre']); ?>
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>

                <div class="mb-3">
                    <label>Observaciones</label>
                    <textarea name="observaciones" class="form-control" rows="2"></textarea>
                </div>
                <button type="button" class="btn btn-primary" id="btnAsignar">Asignar</button>
                <a href="listar.php" class="btn btn-secondary">Cancelar</a>
            </form>
        </div>
    </div>
</div>

<script>
function seleccionarDestino(destino) {
    const btnPersona = document.getElementById('opcionPersona');
    const btnSalon = document.getElementById('opcionSalon');
    const panelPersona = document.getElementById('panelPersona');
    const panelSalon = document.getElementById('panelSalon');
    const tipoDestino = document.getElementById('destino_tipo');
    const inputPersona = document.getElementById('persona_id');
    const inputSalon = document.getElementById('salon_id');

    if (destino === 'salon') {
        btnSalon.classList.add('active');
        btnPersona.classList.remove('active');
        panelSalon.style.display = 'block';
        panelPersona.style.display = 'none';
        tipoDestino.value = 'salon';
        inputSalon.required = true;
        inputPersona.required = false;
        inputPersona.value = '';
    } else {
        btnPersona.classList.add('active');
        btnSalon.classList.remove('active');
        panelPersona.style.display = 'block';
        panelSalon.style.display = 'none';
        tipoDestino.value = 'persona';
        inputPersona.required = true;
        inputSalon.required = false;
        inputSalon.value = '';
    }
}

document.addEventListener('DOMContentLoaded', function() {
    seleccionarDestino('persona');
});

document.getElementById('btnAsignar').addEventListener('click', function() {
    const destino = document.getElementById('destino_tipo').value;
    const personaSel = document.getElementById('persona_id');
    const salonSel = document.getElementById('salon_id');

    if (destino === 'salon' && !salonSel.value) {
        alert('Seleccione un salón.');
        salonSel.focus();
        return;
    }
    if (destino === 'persona' && !personaSel.value) {
        alert('Seleccione una persona.');
        personaSel.focus();
        return;
    }

    document.getElementById('formComponente').submit();
});
</script>

<?php include '../../includes/footer.php'; ?>
