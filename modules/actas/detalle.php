<?php
session_start();
require_once '../../config/permisos.php';
verificarSesion();

require_once '../../config/database.php';

function autoInstalarTablaActasDetalle(&$conn) {
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
        'codigo_acta' => "ALTER TABLE `actas` ADD `codigo_acta` VARCHAR(120) NULL UNIQUE",
        'persona_id' => "ALTER TABLE `actas` ADD `persona_id` INT UNSIGNED NULL",
        'usuario_id' => "ALTER TABLE `actas` ADD `usuario_id` INT UNSIGNED NOT NULL",
        'archivo_pdf' => "ALTER TABLE `actas` ADD `archivo_pdf` VARCHAR(500) NULL",
        'archivo_firmado' => "ALTER TABLE `actas` ADD `archivo_firmado` VARCHAR(500) NULL",
        'fecha_firmado' => "ALTER TABLE `actas` ADD `fecha_firmado` DATETIME NULL",
        'firmado_por' => "ALTER TABLE `actas` ADD `firmado_por` INT UNSIGNED NULL",
        'movimiento_id' => "ALTER TABLE `actas` ADD `movimiento_id` INT UNSIGNED NULL",
        'motivo' => "ALTER TABLE `actas` ADD `motivo` TEXT NULL",
        'equipos_ids' => "ALTER TABLE `actas` ADD `equipos_ids` TEXT NULL",
        'created_at' => "ALTER TABLE `actas` ADD `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
        'updated_at' => "ALTER TABLE `actas` ADD `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP"
    ];
    foreach ($addCols as $col => $sql) {
        if (empty($cols[$col])) @$conn->query($sql);
    }

    if (!empty($cols['codigo_acta']) && strpos($cols['codigo_acta'], '120') === false) {
        @$conn->query("ALTER TABLE `actas` MODIFY COLUMN `codigo_acta` VARCHAR(120) NOT NULL");
    }
}
autoInstalarTablaActasDetalle($conn);

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$success = isset($_GET['success']) ? intval($_GET['success']) : 0;

if (!$id) {
    header('Location: index.php');
    exit();
}

$stmt = $conn->prepare("SELECT a.*, p.nombres as persona_nombre, p.cedula as persona_cedula,
                               p.cargo as persona_cargo, p.correo as persona_correo,
                               u.nombre as usuario_nombre, u.email as usuario_correo,
                               uf.nombre as firmador_nombre
                        FROM actas a
                        LEFT JOIN personas p ON a.persona_id = p.id
                        LEFT JOIN usuarios u ON a.usuario_id = u.id
                        LEFT JOIN usuarios uf ON a.firmado_por = uf.id
                        WHERE a.id = ?");
$acta = null;
if ($stmt) {
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res && $res->num_rows > 0) {
        $acta = $res->fetch_assoc();
    }
}
if (!$acta) {
    header('Location: index.php');
    exit();
}

$equipos_ids = array_filter(array_map('intval', explode(',', $acta['equipos_ids'] ?? '')));
$equipos = [];
if (!empty($equipos_ids)) {
    $placeholders = implode(',', array_fill(0, count($equipos_ids), '?'));
    $stmtEq = $conn->prepare("SELECT e.* FROM equipos e WHERE e.id IN ($placeholders)");
    if ($stmtEq) {
        $types = str_repeat('i', count($equipos_ids));
        $stmtEq->bind_param($types, ...$equipos_ids);
        $stmtEq->execute();
        $resEq = $stmtEq->get_result();
        while ($eq = $resEq->fetch_assoc()) $equipos[] = $eq;
    }
}

$mensaje = '';
if ($success == 1) {
    $mensaje = 'Acta generada correctamente. Ya puedes subir el PDF firmado o reimprimir el acta.';
}
if (isset($_GET['upload']) && $_GET['upload'] == 1) {
    $mensaje = '¡PDF firmado subido con éxito!';
}

$tipos_label = [
    'ingreso' => ['label' => 'Acta de Ingreso', 'badge' => 'bg-success', 'icon' => 'fa-box-open'],
    'entrega' => ['label' => 'Acta de Entrega', 'badge' => 'bg-primary', 'icon' => 'fa-hand-holding'],
    'devolucion' => ['label' => 'Acta de Devolución', 'badge' => 'bg-info', 'icon' => 'fa-rotate-left'],
    'traspaso' => ['label' => 'Acta de Traspaso', 'badge' => 'bg-warning', 'icon' => 'fa-right-left'],
    'baja' => ['label' => 'Acta de Baja', 'badge' => 'bg-danger', 'icon' => 'fa-trash-can']
];
$tinfo = isset($tipos_label[$acta['tipo_acta']]) ? $tipos_label[$acta['tipo_acta']] : ['label' => $acta['tipo_acta'], 'badge' => 'bg-secondary', 'icon' => 'fa-file'];

include '../../includes/header.php';
?>

<div class="container-fluid py-4">
    <?php if (!empty($mensaje)): ?>
        <div class="alert alert-success mb-3">
            <i class="fas fa-circle-check me-2"></i><?php echo htmlspecialchars($mensaje); ?>
        </div>
    <?php endif; ?>

    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h4>
                <i class="fas <?php echo $tinfo['icon']; ?> me-2"></i>
                Detalle del Acta
                <span class="badge <?php echo $tinfo['badge']; ?> ms-2"><?php echo $tinfo['label']; ?></span>
            </h4>
            <a href="index.php" class="btn btn-secondary btn-sm">
                <i class="fas fa-arrow-left me-1"></i> Volver al Listado
            </a>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <div class="alert alert-primary mb-0 h-100">
                        <h6 class="text-uppercase small mb-2 opacity-75"><i class="fas fa-barcode me-1"></i>Código del Acta</h6>
                        <h5 class="mb-0"><?php echo htmlspecialchars($acta['codigo_acta']); ?></h5>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="alert alert-info mb-0 h-100">
                        <h6 class="text-uppercase small mb-2 opacity-75"><i class="fas fa-user-shield me-1"></i>Responsable (Generada por)</h6>
                        <h5 class="mb-0"><?php echo htmlspecialchars($acta['usuario_nombre'] ?: 'Usuario #' . $acta['usuario_id']); ?></h5>
                        <?php if (!empty($acta['usuario_correo'])): ?>
                            <small class="opacity-75"><?php echo htmlspecialchars($acta['usuario_correo']); ?></small>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="alert alert-light mb-0 h-100">
                        <h6 class="text-uppercase small mb-2 opacity-75"><i class="fas fa-calendar me-1"></i>Fecha de Generación</h6>
                        <h5 class="mb-0"><?php echo date('d/m/Y H:i', strtotime($acta['fecha_generacion'])); ?></h5>
                    </div>
                </div>

                <?php if (!empty($acta['persona_id'])): ?>
                <div class="col-md-6">
                    <div class="alert alert-warning mb-0">
                        <h6 class="text-uppercase small mb-2 opacity-75"><i class="fas fa-user me-1"></i>Custodio / Persona Involucrada</h6>
                        <h5 class="mb-1"><?php echo htmlspecialchars($acta['persona_nombre'] ?: '-'); ?></h5>
                        <?php if (!empty($acta['persona_cedula'])): ?>
                            <small class="opacity-75 me-3">C.I.: <?php echo htmlspecialchars($acta['persona_cedula']); ?></small>
                        <?php endif; ?>
                        <?php if (!empty($acta['persona_cargo'])): ?>
                            <small class="opacity-75 me-3">Cargo: <?php echo htmlspecialchars($acta['persona_cargo']); ?></small>
                        <?php endif; ?>
                        <?php if (!empty($acta['persona_correo'])): ?>
                            <small class="opacity-75">Correo: <?php echo htmlspecialchars($acta['persona_correo']); ?></small>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>

                <?php if (!empty($acta['motivo'])): ?>
                <div class="col-md-6">
                    <div class="alert alert-danger mb-0">
                        <h6 class="text-uppercase small mb-2 opacity-75"><i class="fas fa-file-lines me-1"></i>Motivo</h6>
                        <p class="mb-0"><?php echo nl2br(htmlspecialchars($acta['motivo'])); ?></p>
                    </div>
                </div>
                <?php endif; ?>

                <?php if (!empty($acta['archivo_firmado'])): ?>
                <div class="col-md-12">
                    <div class="alert alert-success mb-0">
                        <h6 class="text-uppercase small mb-2 opacity-75"><i class="fas fa-signature me-1"></i>Acta Firmada</h6>
                        <div class="d-flex flex-wrap align-items-center gap-3">
                            <i class="fas fa-check-circle fa-2x"></i>
                            <div>
                                <strong class="d-block">Subida el: <?php echo !empty($acta['fecha_firmado']) ? date('d/m/Y H:i', strtotime($acta['fecha_firmado'])) : '-'; ?></strong>
                                <?php if (!empty($acta['firmador_nombre'])): ?>
                                    <small>Por: <?php echo htmlspecialchars($acta['firmador_nombre']); ?></small>
                                <?php endif; ?>
                            </div>
                            <a href="/Inventario-Tesa-Cardex/<?php echo htmlspecialchars($acta['archivo_firmado']); ?>" target="_blank" class="btn btn-success ms-auto">
                                <i class="fas fa-file-signature me-1"></i> Abrir PDF Firmado
                            </a>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5><i class="fas fa-list-check me-2"></i>Equipos incluidos en el acta (<?php echo count($equipos); ?>)</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Código</th>
                            <th>Tipo</th>
                            <th>Marca / Modelo</th>
                            <th>Serie</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($equipos) > 0): foreach ($equipos as $i => $eq): ?>
                        <tr>
                            <td><?php echo $i + 1; ?></td>
                            <td>
                                <a href="/Inventario-Tesa-Cardex/modules/equipos/detalle.php?id=<?php echo $eq['id']; ?>" class="text-white fw-bold">
                                    <?php echo htmlspecialchars($eq['codigo_barras'] ?? '-'); ?>
                                </a>
                            </td>
                            <td><?php echo htmlspecialchars($eq['tipo_equipo'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars(($eq['marca'] ?? '') . ' ' . ($eq['modelo'] ?? '')); ?></td>
                            <td><?php echo htmlspecialchars($eq['numero_serie'] ?? '-'); ?></td>
                            <td>
                                <?php
                                    $e = $eq['estado'] ?? '';
                                    $map = [
                                        'disponible' => ['Disponible', 'bg-success'],
                                        'asignado' => ['Asignado', 'bg-primary'],
                                        'prestado' => ['Prestado', 'bg-warning'],
                                        'en mantenimiento' => ['Mantenimiento', 'bg-info'],
                                        'baja' => ['De baja', 'bg-danger']
                                    ];
                                    $ekey = strtolower(trim($e));
                                    $info = isset($map[$ekey]) ? $map[$ekey] : [$e ?: 'Desconocido', 'bg-secondary'];
                                ?>
                                <span class="badge <?php echo $info[1]; ?>"><?php echo $info[0]; ?></span>
                            </td>
                        </tr>
                        <?php endforeach; else: ?>
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">
                                <i class="fas fa-box-open me-2"></i>
                                No se encontraron detalles de equipos (IDs: <?php echo htmlspecialchars($acta['equipos_ids']); ?>)
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header">
                    <h5><i class="fas fa-file-pdf me-2 text-info"></i>PDF Original (Generado automáticamente)</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($acta['archivo_pdf'])): ?>
                        <p class="mb-3">El PDF fue generado correctamente el día del acta. Puedes abrirlo o reimprimirlo cuando necesites.</p>
                        <a href="/Inventario-Tesa-Cardex/<?php echo htmlspecialchars($acta['archivo_pdf']); ?>" target="_blank" class="btn btn-outline-primary w-100 mb-2">
                            <i class="fas fa-eye me-1"></i> Ver PDF Original Almacenado
                        </a>
                    <?php else: ?>
                        <p class="mb-3 text-info">
                            <i class="fas fa-circle-info me-1"></i>
                            Esta acta fue registrada pero aún no se ha guardado una copia del PDF en el servidor. Usa los botones de abajo para generarlo.
                        </p>
                        <a href="/Inventario-Tesa-Cardex/api/generar_acta_unificada.php?acta_id=<?php echo $acta['id']; ?>&guardar=1"
                           class="btn btn-outline-success w-100 mb-2">
                            <i class="fas fa-file-arrow-down me-1"></i> Generar y Guardar PDF en el Sistema
                        </a>
                    <?php endif; ?>
                    <button class="btn btn-primary w-100 mb-2" onclick="reimprimirActa(<?php echo $acta['id']; ?>)">
                        <i class="fas fa-print me-1"></i> Reimprimir / Ver PDF (Ventana Nueva)
                    </button>
                    <small class="text-muted d-block text-center">
                        Usa esta opción para volver a generar el PDF si necesitas una copia.
                    </small>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header">
                    <h5><i class="fas fa-signature me-2 text-success"></i>Subir Acta Firmada</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($acta['archivo_firmado'])): ?>
                        <p class="mb-3">Sube la versión escaneada y firmada del acta (formato PDF únicamente).</p>
                        <form action="/Inventario-Tesa-Cardex/api/subir_acta_firmada.php" method="POST" enctype="multipart/form-data" id="formSubirFirmado" class="needs-ajax-upload" data-acta-id="<?php echo $acta['id']; ?>">
                            <input type="hidden" name="acta_id" value="<?php echo $acta['id']; ?>">
                            <div class="mb-3">
                                <label class="form-label small">Archivo PDF firmado</label>
                                <input type="file" name="archivo_firmado" id="archivoFirmado" class="form-control" accept=".pdf" required>
                                <small class="text-muted">Tamaño máximo recomendado: 15 MB. Solo se aceptan PDFs válidos.</small>
                            </div>
                            <button type="submit" id="btnSubir" class="btn btn-warning w-100">
                                <i class="fas fa-upload me-1"></i> Subir PDF Firmado
                            </button>
                        </form>
                    <?php else: ?>
                        <div class="text-center py-3">
                            <i class="fas fa-check-circle text-success fa-3x mb-3"></i>
                            <h6 class="mb-2">¡Acta firmada ya fue subida!</h6>
                            <p class="small text-muted mb-3">Puedes volver a subir una versión actualizada (reemplazará la ruta anterior en el sistema).</p>
                            <a href="/Inventario-Tesa-Cardex/<?php echo htmlspecialchars($acta['archivo_firmado']); ?>" target="_blank" class="btn btn-success w-100 mb-2">
                                <i class="fas fa-file-pdf me-1"></i> Ver PDF Firmado
                            </a>
                            <form action="/Inventario-Tesa-Cardex/api/subir_acta_firmada.php" method="POST" enctype="multipart/form-data" class="mt-2 needs-ajax-upload" data-acta-id="<?php echo $acta['id']; ?>">
                                <input type="hidden" name="acta_id" value="<?php echo $acta['id']; ?>">
                                <label class="form-label small text-start d-block mb-1">Reemplazar PDF firmado</label>
                                <input type="file" name="archivo_firmado" class="form-control form-control-sm mb-2" accept=".pdf" required>
                                <button type="submit" class="btn btn-outline-warning btn-sm w-100 btn-subir-reemplazo">
                                    <i class="fas fa-rotate me-1"></i> Subir Nueva Versión
                                </button>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    function cargarSweetAlert(callback) {
        if (window.Swal) {
            callback();
            return;
        }
        if (!document.getElementById('swal2-script-inyectado')) {
            const s = document.createElement('script');
            s.id = 'swal2-script-inyectado';
            s.src = 'https://cdn.jsdelivr.net/npm/sweetalert2@11';
            s.onload = callback;
            s.onerror = function() {
                alert('No se pudo cargar SweetAlert2. Revisa la conexión a internet.');
            };
            document.head.appendChild(s);
        } else {
            let intentos = 0;
            const t = setInterval(function() {
                intentos++;
                if (window.Swal) { clearInterval(t); callback(); }
                if (intentos > 20) clearInterval(t);
            }, 100);
        }
    }

    function manejarSubida(form, ev) {
        ev.preventDefault();
        const fileInput = form.querySelector('input[type="file"][name="archivo_firmado"]');
        const submitBtn = form.querySelector('button[type="submit"]');
        const file = fileInput && fileInput.files ? fileInput.files[0] : null;
        if (!file) return;

        if (file.type !== 'application/pdf' && !file.name.toLowerCase().endsWith('.pdf')) {
            alert('Solo se permiten archivos PDF.');
            return;
        }
        if (file.size > 15 * 1024 * 1024) {
            alert('El archivo es demasiado grande (máx 15 MB).');
            return;
        }

        cargarSweetAlert(function() {
            const originalHtml = submitBtn ? submitBtn.innerHTML : '';
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Subiendo...';
            }

            const formData = new FormData(form);

            Swal.fire({
                title: 'Subiendo acta firmada...',
                text: 'Por favor espera mientras se sube el PDF. Esto puede tardar unos segundos según el tamaño del archivo.',
                didOpen: () => { Swal.showLoading(); },
                allowOutsideClick: false,
                allowEscapeKey: false,
                showConfirmButton: false
            });

            fetch(form.action || '/Inventario-Tesa-Cardex/api/subir_acta_firmada.php', {
                method: 'POST',
                body: formData
            })
            .then(async function(response) {
                const text = await response.text();
                let data;
                try {
                    data = JSON.parse(text);
                } catch (e) {
                    const visible = text.length > 300 ? (text.substring(0, 300) + '\n...(truncado)') : text;
                    throw new Error('El servidor no devolvió una respuesta válida. Respuesta cruda: ' + visible);
                }
                if (!response.ok && data && !data.message) {
                    data.message = 'Código HTTP ' + response.status;
                }
                return data;
            })
            .then(function(data) {
                Swal.close();
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalHtml;
                }
                if (data && data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Acta firmada subida con éxito!',
                        text: data.message || 'El PDF fue registrado correctamente en el sistema.',
                        confirmButtonColor: '#5a2d8c',
                        confirmButtonText: 'Aceptar'
                    }).then(function() {
                        const urlParams = new URLSearchParams(window.location.search);
                        urlParams.set('upload', '1');
                        window.location.search = urlParams.toString();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'No se pudo subir el archivo',
                        text: data && data.message ? data.message : 'Error desconocido en el servidor.',
                        confirmButtonColor: '#b91c1c',
                        confirmButtonText: 'Entendido'
                    });
                }
            })
            .catch(function(error) {
                Swal.close();
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalHtml;
                }
                Swal.fire({
                    icon: 'error',
                    title: 'Error de conexión',
                    text: 'No se pudo comunicar con el servidor: ' + (error.message || ''),
                    confirmButtonColor: '#b91c1c',
                    confirmButtonText: 'Entendido'
                });
            });
        });
    }

    document.addEventListener('DOMContentLoaded', function() {
        const formularios = document.querySelectorAll('form.needs-ajax-upload');
        formularios.forEach(function(form) {
            form.addEventListener('submit', function(e) { manejarSubida(form, e); });
        });
    });
})();

function reimprimirActa(actaId) {
    const win = window.open('/Inventario-Tesa-Cardex/api/generar_acta_unificada.php?acta_id=' + actaId, '_blank');
    if (!win) {
        alert('El navegador bloqueó la ventana emergente. Permite ventanas emergentes para este sitio e inténtalo de nuevo.');
    }
}
<?php include '../../includes/footer.php'; ?>

