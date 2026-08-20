<?php
session_start();
require_once '../../config/permisos.php';
verificarSesion();
if (!esAdmin()) {
    header('Location: index.php');
    exit();
}

require_once '../../config/database.php';
require_once '../../config/actas_config.php';

function autoInstalarTablaActasGenerar(&$conn) {
    @$conn->query("CREATE TABLE IF NOT EXISTS `actas` (
        `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        `codigo_acta` VARCHAR(120) NOT NULL UNIQUE,
        `tipo_acta` ENUM('ingreso','entrega','devolucion','traspaso','baja') NOT NULL,
        `persona_id` INT UNSIGNED NULL,
        `usuario_id` INT UNSIGNED NOT NULL,
        `equipos_ids` TEXT NULL,
        `motivo` TEXT NULL,
        `fecha_generacion` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `archivo_pdf` VARCHAR(500) NULL,
        `archivo_firmado` VARCHAR(500) NULL,
        `fecha_firmado` DATETIME NULL,
        `firmado_por` INT UNSIGNED NULL,
        `movimiento_id` INT UNSIGNED NULL,
        `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX `idx_tipo` (`tipo_acta`),
        INDEX `idx_persona` (`persona_id`),
        INDEX `idx_usuario` (`usuario_id`),
        INDEX `idx_fecha` (`fecha_generacion`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $cols = [];
    $rc = @$conn->query("SHOW COLUMNS FROM `actas`");
    if ($rc) while ($r = $rc->fetch_assoc()) $cols[$r['Field']] = $r['Type'];

    if (empty($cols['tipo_acta']) || (strpos($cols['tipo_acta'], 'ingreso') === false) || (strpos($cols['tipo_acta'], 'traspaso') === false) || (strpos($cols['tipo_acta'], 'baja') === false)) {
        @$conn->query("ALTER TABLE `actas` MODIFY COLUMN `tipo_acta` ENUM('ingreso','entrega','devolucion','traspaso','baja') NOT NULL");
    }

    $addCols = [
        'archivo_pdf' => "ALTER TABLE `actas` ADD `archivo_pdf` VARCHAR(500) NULL",
        'archivo_firmado' => "ALTER TABLE `actas` ADD `archivo_firmado` VARCHAR(500) NULL",
        'fecha_firmado' => "ALTER TABLE `actas` ADD `fecha_firmado` DATETIME NULL",
        'firmado_por' => "ALTER TABLE `actas` ADD `firmado_por` INT UNSIGNED NULL",
        'movimiento_id' => "ALTER TABLE `actas` ADD `movimiento_id` INT UNSIGNED NULL",
        'motivo' => "ALTER TABLE `actas` ADD `motivo` TEXT NULL",
        'equipos_ids' => "ALTER TABLE `actas` ADD `equipos_ids` TEXT NULL",
        'created_at' => "ALTER TABLE `actas` ADD `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
        'updated_at' => "ALTER TABLE `actas` ADD `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP",
        'codigo_acta' => "ALTER TABLE `actas` ADD `codigo_acta` VARCHAR(120) NULL UNIQUE",
        'persona_id' => "ALTER TABLE `actas` ADD `persona_id` INT UNSIGNED NULL",
        'usuario_id' => "ALTER TABLE `actas` ADD `usuario_id` INT UNSIGNED NOT NULL"
    ];
    foreach ($addCols as $col => $sql) {
        if (empty($cols[$col])) @$conn->query($sql);
    }

    if (!empty($cols['codigo_acta']) && strpos($cols['codigo_acta'], '120') === false) {
        @$conn->query("ALTER TABLE `actas` MODIFY COLUMN `codigo_acta` VARCHAR(120) NOT NULL");
    }
}
autoInstalarTablaActasGenerar($conn);

$mensaje_error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $tipo_acta = $_POST['tipo_acta'] ?? '';
    $persona_id = intval($_POST['persona_id'] ?? 0);
    $equipos_ids = trim($_POST['equipos_ids'] ?? '');
    $motivo = trim($_POST['motivo'] ?? '');
    $usuario_id = intval($_SESSION['user_id']);

    if (empty($tipo_acta)) {
        $mensaje_error = "Debe seleccionar un tipo de acta.";
    } elseif (empty($equipos_ids)) {
        $mensaje_error = "Debe seleccionar al menos un equipo.";
    } else {
        $requiere_persona = in_array($tipo_acta, ['entrega', 'devolucion', 'traspaso']);
        if ($requiere_persona && $persona_id <= 0) {
            $mensaje_error = "Para este tipo de acta debe seleccionar una persona (custodio).";
        } elseif ($tipo_acta == 'baja' && empty($motivo)) {
            $mensaje_error = "Para actas de baja debe indicar el motivo.";
        } else {
            $codigo_acta = generarCodigoActa($tipo_acta);
            $persona_bind = ($requiere_persona && $persona_id > 0) ? $persona_id : null;
            $motivo_bind = ($tipo_acta == 'baja') ? $motivo : null;

            $stmt = $conn->prepare("INSERT INTO actas (codigo_acta, tipo_acta, persona_id, usuario_id, equipos_ids, motivo, fecha_generacion) VALUES (?, ?, ?, ?, ?, ?, NOW())");
            if ($stmt) {
                $tipos = 'sssiss';
                $stmt->bind_param($tipos, $codigo_acta, $tipo_acta, $persona_bind, $usuario_id, $equipos_ids, $motivo_bind);
                if ($stmt->execute()) {
                    $id_nuevo = $conn->insert_id;
                    header("Location: /Inventario-Tesa-Cardex/api/generar_acta_unificada.php?acta_id=" . $id_nuevo . "&guardar=1");
                    exit();
                } else {
                    $mensaje_error = "Error al guardar: " . $conn->error;
                }
            } else {
                $mensaje_error = "Error preparando la consulta: " . $conn->error;
            }
        }
    }
}

$personas = @$conn->query("SELECT id, nombres, cedula, cargo FROM personas WHERE (activo IS NULL OR activo = 1) AND (fecha_eliminacion IS NULL) ORDER BY nombres");
$user_name = $_SESSION['user_name'] ?? ($_SESSION['user_nombre'] ?? 'Usuario actual');

include '../../includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h4><i class="fas fa-file-circle-plus me-2"></i>Generar Nueva Acta</h4>
            <a href="index.php" class="btn btn-secondary btn-sm">
                <i class="fas fa-arrow-left me-1"></i> Volver al Listado
            </a>
        </div>
        <div class="card-body">
            <?php if (!empty($mensaje_error)): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($mensaje_error); ?></div>
            <?php endif; ?>

            <form method="POST" id="formGenerarActa">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Tipo de Acta <span class="text-danger">*</span></label>
                        <select name="tipo_acta" class="form-control" id="tipoActa" required>
                            <option value="">-- Seleccione el tipo de acta --</option>
                            <option value="ingreso">Ingreso de Inventario (Masivo o Individual)</option>
                            <option value="entrega">Acta de Entrega (Onboarding / Asignación)</option>
                            <option value="devolucion">Acta de Devolución (Offboarding)</option>
                            <option value="traspaso">Acta de Traspaso de Custodio</option>
                            <option value="baja">Acta de Baja / Descargo</option>
                        </select>
                    </div>

                    <div class="col-md-6 mb-3" id="divPersona" style="display: none;">
                        <label class="form-label">Persona (Custodio) <span class="text-danger">*</span></label>
                        <select name="persona_id" id="personaId" class="form-control">
                            <option value="0">-- Seleccione una persona --</option>
                            <?php if ($personas && $personas->num_rows > 0): while($pr = $personas->fetch_assoc()): ?>
                                <option value="<?php echo $pr['id']; ?>">
                                    <?php
                                        $detalle = [];
                                        if (!empty($pr['cedula'])) $detalle[] = $pr['cedula'];
                                        if (!empty($pr['cargo'])) $detalle[] = $pr['cargo'];
                                        $suffix = !empty($detalle) ? '  (' . implode(' · ', $detalle) . ')' : '';
                                        echo htmlspecialchars($pr['nombres'] . $suffix);
                                    ?>
                                </option>
                            <?php endwhile; endif; ?>
                        </select>
                    </div>
                </div>

                <div class="alert alert-info py-2 mb-3">
                    <i class="fas fa-circle-info me-2"></i>
                    <strong>Responsable:</strong> Esta acta quedará registrada a tu nombre
                    <strong><?php echo htmlspecialchars($user_name); ?></strong>.
                </div>

                <div class="mb-4">
                    <label class="form-label"><i class="fas fa-barcode me-1"></i> Seleccionar Equipo(s) <span class="text-danger">*</span></label>
                    <div class="input-group mb-2">
                        <input type="text" id="buscadorEquipos" class="form-control"
                               placeholder="Escribe el código de barras del equipo y presiona Enter o clic en Agregar...">
                        <button class="btn btn-secondary" type="button" id="btnAgregarEquipo">
                            <i class="fas fa-plus me-1"></i>Agregar
                        </button>
                    </div>
                    <small class="text-muted d-block mb-2">
                        <i class="fas fa-lightbulb me-1"></i>
                        Usa el código de barras (ej: <strong>EQU-00001</strong>) para buscar cada equipo. Puedes agregar varios para un acta masiva.
                    </small>

                    <div id="listaEquiposSeleccionados" class="mb-2"></div>
                    <div class="alert alert-warning py-2" id="emptyEquiposMsg">
                        <i class="fas fa-triangle-exclamation me-1"></i>
                        No has agregado ningún equipo aún.
                    </div>

                    <input type="hidden" name="equipos_ids" id="equipos_ids" value="">
                </div>

                <div class="mb-4" id="divMotivo" style="display: none;">
                    <label class="form-label"><i class="fas fa-file-lines me-1"></i> Motivo de la Baja <span class="text-danger">*</span></label>
                    <textarea name="motivo" id="motivoBaja" class="form-control" rows="3"
                              placeholder="Describe el motivo del descargo (daño, pérdida, obsolescencia, etc.)"></textarea>
                </div>

                <div class="text-center mt-4 d-flex gap-2 justify-content-center flex-wrap">
                    <button type="submit" class="btn btn-primary btn-lg px-5">
                        <i class="fas fa-file-pdf me-2"></i>Guardar y Generar Acta
                    </button>
                    <a href="index.php" class="btn btn-secondary btn-lg px-5">
                        <i class="fas fa-arrow-left me-2"></i>Cancelar
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('tipoActa').addEventListener('change', function() {
    const tipo = this.value;
    const requierePersona = (tipo === 'entrega' || tipo === 'devolucion' || tipo === 'traspaso');
    document.getElementById('divPersona').style.display = requierePersona ? 'block' : 'none';
    document.getElementById('divMotivo').style.display = (tipo === 'baja') ? 'block' : 'none';
    if (!requierePersona) document.getElementById('personaId').value = '0';
});

let equiposSeleccionados = [];

function renderizarEquipos() {
    const container = document.getElementById('listaEquiposSeleccionados');
    const hiddenInput = document.getElementById('equipos_ids');
    const emptyMsg = document.getElementById('emptyEquiposMsg');

    container.innerHTML = '';
    let ids = [];

    if (equiposSeleccionados.length === 0) {
        emptyMsg.style.display = 'block';
    } else {
        emptyMsg.style.display = 'none';
    }

    equiposSeleccionados.forEach((eq, index) => {
        ids.push(eq.id);
        const badge = eq.estado_actual === 'DISPONIBLE'
            ? '<span class="badge bg-success ms-2">Disponible</span>'
            : '<span class="badge bg-warning ms-2">' + (eq.estado_actual || 'Asignado') + '</span>';

        const tipoVal = eq.tipo_equipo || eq.tipo || '';
        const serieVal = eq.numero_serie || eq.serie || '';

        const div = document.createElement('div');
        div.className = 'list-group-item d-flex justify-content-between align-items-center mb-1';
        div.innerHTML = `
            <div>
                <strong>[${eq.codigo_barras || 'CÓDIGO'}]</strong>
                ${tipoVal ? tipoVal + ' · ' : ''}
                ${eq.marca ? eq.marca + ' ' : ''}
                ${eq.modelo || 'Equipo'}
                ${serieVal ? ' <small class="text-muted">(SN: ' + serieVal + ')</small>' : ''}
                ${badge}
                ${eq.persona_nombre ? '<div class="small mt-1 text-warning"><i class="fas fa-user me-1"></i>Asignado a: ' + eq.persona_nombre + '</div>' : ''}
            </div>
            <button class="btn btn-sm btn-danger" type="button" onclick="eliminarEquipo(${index})">
                <i class="fas fa-times"></i>
            </button>
        `;
        container.appendChild(div);
    });
    hiddenInput.value = ids.join(',');
}

function eliminarEquipo(index) {
    equiposSeleccionados.splice(index, 1);
    renderizarEquipos();
}

function agregarEquipo() {
    const input = document.getElementById('buscadorEquipos');
    const query = input.value.trim();
    if (query.length < 2) {
        alert('Escribe al menos 2 caracteres del código de barras.');
        return;
    }
    const btn = document.getElementById('btnAgregarEquipo');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Buscando...';

    fetch('/Inventario-Tesa-Cardex/api/buscar_producto.php?codigo=' + encodeURIComponent(query))
        .then(res => res.json())
        .then(data => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-plus me-1"></i>Agregar';
            if (data.success && data.equipo) {
                const eq = data.equipo;
                if (!equiposSeleccionados.find(e => e.id == eq.id)) {
                    equiposSeleccionados.push(eq);
                    renderizarEquipos();
                    input.value = '';
                    input.focus();
                } else {
                    alert('Este equipo ya fue agregado a la lista.');
                }
            } else {
                alert('Equipo no encontrado: ' + (data.mensaje || 'Asegúrate de que el código esté registrado.'));
            }
        })
        .catch(err => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-plus me-1"></i>Agregar';
            alert('Error de conexión al buscar el equipo.');
        });
}

document.getElementById('btnAgregarEquipo').addEventListener('click', agregarEquipo);
document.getElementById('buscadorEquipos').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        agregarEquipo();
    }
});

document.getElementById('formGenerarActa').addEventListener('submit', function(e) {
    const ids = document.getElementById('equipos_ids').value;
    if (!ids) {
        e.preventDefault();
        alert('Debes agregar al menos un equipo al acta.');
        return;
    }
    const tipo = document.getElementById('tipoActa').value;
    if ((tipo === 'entrega' || tipo === 'devolucion' || tipo === 'traspaso') &&
        parseInt(document.getElementById('personaId').value) <= 0) {
        e.preventDefault();
        alert('Para este tipo de acta debes seleccionar un custodio.');
        return;
    }
    if (tipo === 'baja' && document.getElementById('motivoBaja').value.trim() === '') {
        e.preventDefault();
        alert('Indica el motivo de la baja.');
        return;
    }
});

renderizarEquipos();
</script>
<?php include '../../includes/footer.php'; ?>

