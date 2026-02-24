<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: /inventario_ti/login.php');
    exit();
}
require_once '../../config/database.php';
include '../../includes/header.php';

if (!isset($_GET['id'])) {
    header('Location: listar.php');
    exit();
}

$id = intval($_GET['id']);

// Obtener datos de la persona
$sql_persona = "SELECT * FROM personas WHERE id = $id";
$result_persona = $conn->query($sql_persona);

if ($result_persona->num_rows == 0) {
    header('Location: listar.php');
    exit();
}

$persona = $result_persona->fetch_assoc();

// Obtener equipos asignados a esta persona
$sql_equipos = "SELECT e.*, a.fecha_asignacion, a.observaciones as obs_asignacion
                FROM equipos e
                JOIN asignaciones a ON e.id = a.equipo_id
                WHERE a.persona_id = $id AND a.fecha_devolucion IS NULL
                ORDER BY a.fecha_asignacion DESC";
$equipos_asignados = $conn->query($sql_equipos);

// Obtener historial de movimientos de esta persona
$sql_historial = "SELECT m.*, e.tipo_equipo, e.codigo_barras
                  FROM movimientos m
                  JOIN equipos e ON m.equipo_id = e.id
                  WHERE m.persona_id = $id
                  ORDER BY m.fecha_movimiento DESC
                  LIMIT 20";
$historial = $conn->query($sql_historial);

// ============================================
// IP DEL SERVIDOR CORREGIDA (IPv4)
// ============================================
$ip_servidor = '192.168.101.25'; // TU IP FIJA
$puerto = $_SERVER['SERVER_PORT'];
$puerto_texto = ($puerto == '80' || $puerto == '443') ? '' : ':' . $puerto;
?>

<style>
/* ============================================ */
/* ESTILOS PARA EL MODAL DEL QR */
/* ============================================ */
.qr-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    display: none;
    align-items: center;
    justify-content: center;
    z-index: 10000;
}

.qr-modal-content {
    background: white;
    padding: 30px;
    border-radius: 20px;
    max-width: 400px;
    width: 90%;
    text-align: center;
    box-shadow: 0 20px 40px rgba(0,0,0,0.3);
}

.qr-modal-content h4 {
    color: #5a2d8c;
    margin-bottom: 20px;
}

.qr-modal-content #qrcode-container {
    margin: 20px 0;
    padding: 20px;
    background: #f8f9fc;
    border-radius: 15px;
}

.qr-modal-content .btn-close {
    background: #dc3545;
    color: white;
    border: none;
    padding: 10px 30px;
    border-radius: 30px;
    font-weight: 600;
    margin-top: 15px;
}

.qr-modal-content .btn-close:hover {
    background: #c82333;
}

/* ============================================ */
/* RESPONSIVE PARA MÓVILES - DETALLE DE PERSONA */
/* ============================================ */
@media (max-width: 768px) {
    /* Ajustes generales */
    .container-fluid {
        padding-left: 10px !important;
        padding-right: 10px !important;
    }
    
    /* Cabecera con botones */
    .card-header {
        flex-direction: column !important;
        align-items: flex-start !important;
        gap: 10px !important;
    }
    
    .card-header div {
        display: flex !important;
        flex-wrap: wrap !important;
        gap: 5px !important;
        width: 100% !important;
    }
    
    .card-header .btn {
        flex: 1 1 auto !important;
        font-size: 0.85rem !important;
        padding: 8px 10px !important;
        margin: 0 !important;
    }
    
    /* Tabla de datos personales */
    .table-bordered {
        font-size: 14px !important;
    }
    
    .table-bordered th,
    .table-bordered td {
        padding: 8px !important;
    }
    
    .table-bordered th {
        width: 35% !important;
    }
    
    /* Cuadro de equipos asignados */
    .col-md-6 .card.bg-light {
        margin-top: 15px !important;
    }
    
    .col-md-6 .card-body {
        padding: 15px !important;
    }
    
    .col-md-6 h3 {
        font-size: 2rem !important;
    }
    
    .btn-success {
        font-size: 1rem !important;
        padding: 12px !important;
    }
    
    /* Tabla de equipos actuales */
    .table-responsive {
        border: none !important;
    }
    
    .table {
        min-width: auto !important;
    }
    
    .table thead {
        display: none !important;
    }
    
    .table tbody tr {
        display: block !important;
        margin-bottom: 20px !important;
        border: 1px solid #e0e0e0 !important;
        border-radius: 15px !important;
        padding: 15px !important;
        background: white !important;
        box-shadow: 0 2px 8px rgba(0,0,0,0.05) !important;
    }
    
    .table tbody td {
        display: flex !important;
        justify-content: space-between !important;
        align-items: center !important;
        padding: 10px 8px !important;
        border: none !important;
        border-bottom: 1px dashed #eee !important;
        font-size: 14px !important;
    }
    
    .table tbody td:last-child {
        border-bottom: none !important;
    }
    
    .table tbody td:before {
        content: attr(data-label) !important;
        font-weight: 700 !important;
        color: #5a2d8c !important;
        margin-right: 10px !important;
        min-width: 100px !important;
        font-size: 13px !important;
    }
    
    /* Botones de acciones en la tabla */
    .table tbody td .btn-sm {
        padding: 5px 10px !important;
        font-size: 12px !important;
    }
    
    /* Historial de movimientos */
    .list-group-item {
        padding: 12px !important;
        font-size: 13px !important;
    }
    
    .list-group-item .d-flex {
        flex-direction: column !important;
        align-items: flex-start !important;
        gap: 5px !important;
    }
    
    .list-group-item .badge {
        font-size: 11px !important;
        padding: 3px 6px !important;
    }
    
    .list-group-item small {
        font-size: 11px !important;
    }
}

/* Para teléfonos muy pequeños */
@media (max-width: 480px) {
    .card-header h4 {
        font-size: 1.2rem !important;
    }
    
    .card-header .btn {
        font-size: 0.75rem !important;
        padding: 6px 8px !important;
    }
    
    .table-bordered {
        font-size: 12px !important;
    }
    
    .table-bordered th,
    .table-bordered td {
        padding: 6px !important;
    }
    
    .table tbody td {
        font-size: 12px !important;
        padding: 8px 5px !important;
    }
    
    .table tbody td:before {
        min-width: 80px !important;
        font-size: 11px !important;
    }
    
    .btn-sm {
        padding: 4px 8px !important;
        font-size: 11px !important;
    }
    
    .col-md-6 h3 {
        font-size: 1.8rem !important;
    }
}
</style>

<!-- MODAL PARA MOSTRAR QR -->
<div id="qrModal" class="qr-modal">
    <div class="qr-modal-content">
        <h4><i class="fas fa-qrcode me-2"></i>Código QR de <?php echo $persona['nombres']; ?></h4>
        <div id="qrcode-container"></div>
        <p class="text-muted">Escanea este código para ver los equipos de la persona</p>
        <div id="qr-url-info" class="alert alert-info mt-2" style="font-size: 12px; word-break: break-all; display: none;"></div>
        <button class="btn-close" onclick="cerrarModalQR()">Cerrar</button>
    </div>
</div>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4><i class="fas fa-user me-2"></i>Detalle de Persona</h4>
                    <div>
                        <!-- Botón VER QR (abre modal) -->
                        <button onclick="generarQR(<?php echo $id; ?>)" class="btn btn-info">
                            <i class="fas fa-qrcode me-2"></i>Ver QR
                        </button>
                        
                        <!-- Botón DESCARGAR QR (descarga imagen) -->
                        <a href="/inventario_ti/api/generar_qr_persona.php?id=<?php echo $id; ?>" class="btn btn-warning" download="qr_persona_<?php echo $id; ?>.png">
                            <i class="fas fa-download me-2"></i>Descargar QR
                        </a>
                        
                        <!-- Botones de acción -->
                        <a href="editar.php?id=<?php echo $id; ?>" class="btn btn-primary">
                            <i class="fas fa-edit me-2"></i>Editar
                        </a>
                        <a href="listar.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Volver
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    
                    <!-- Datos de la persona -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <table class="table table-bordered">
                                <tr>
                                    <th width="30%">Cédula</th>
                                    <td><?php echo $persona['cedula']; ?></td>
                                </tr>
                                <tr>
                                    <th>Nombres</th>
                                    <td><?php echo $persona['nombres']; ?></td>
                                </tr>
                                <tr>
                                    <th>Cargo</th>
                                    <td><?php echo $persona['cargo']; ?></td>
                                </tr>
                                <tr>
                                    <th>Correo</th>
                                    <td><?php echo $persona['correo'] ?: 'No registrado'; ?></td>
                                </tr>
                                <tr>
                                    <th>Teléfono</th>
                                    <td><?php echo $persona['telefono'] ?: 'No registrado'; ?></td>
                                </tr>
                            </table>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="card bg-light">
                                <div class="card-body text-center">
                                    <h3><?php echo $equipos_asignados->num_rows; ?></h3>
                                    <p>Equipos asignados actualmente</p>
                                    <!-- BOTÓN DE ASIGNAR NUEVO EQUIPO -->
                                    <a href="../asignaciones/cargar_equipos.php?persona_id=<?php echo $id; ?>" class="btn btn-success btn-lg w-100">
                                        <i class="fas fa-plus-circle me-2"></i>Asignar nuevo equipo
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Equipos actuales -->
                    <div class="card mt-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-laptop me-2"></i>Equipos Asignados Actualmente</h5>
                        </div>
                        <div class="card-body">
                            <?php if ($equipos_asignados->num_rows > 0): ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Código</th>
                                                <th>Tipo</th>
                                                <th>Marca/Modelo</th>
                                                <th>Serie</th>
                                                <th>Fecha Asignación</th>
                                                <th>Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while($eq = $equipos_asignados->fetch_assoc()): ?>
                                            <tr>
                                                <td data-label="CÓDIGO"><?php echo $eq['codigo_barras']; ?></td>
                                                <td data-label="TIPO"><?php echo $eq['tipo_equipo']; ?></td>
                                                <td data-label="MARCA/MODELO"><?php echo $eq['marca'] . ' ' . $eq['modelo']; ?></td>
                                                <td data-label="SERIE"><?php echo $eq['numero_serie'] ?: 'N/A'; ?></td>
                                                <td data-label="FECHA"><?php echo date('d/m/Y', strtotime($eq['fecha_asignacion'])); ?></td>
                                                <td data-label="ACCIONES">
                                                    <div class="d-flex gap-1">
                                                        <a href="../movimientos/devolucion.php?equipo_id=<?php echo $eq['id']; ?>" class="btn btn-sm btn-warning" title="Registrar devolución">
                                                            <i class="fas fa-undo-alt"></i>
                                                        </a>
                                                        <a href="../equipos/detalle.php?id=<?php echo $eq['id']; ?>" class="btn btn-sm btn-info" title="Ver equipo">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <p class="text-muted text-center py-4">
                                    <i class="fas fa-info-circle fa-2x mb-3"></i><br>
                                    Esta persona no tiene equipos asignados actualmente.
                                    <br>
                                    <a href="../asignaciones/cargar_equipos.php?persona_id=<?php echo $id; ?>" class="btn btn-success mt-3">
                                        <i class="fas fa-plus-circle me-2"></i>Asignar primer equipo
                                    </a>
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Historial de movimientos -->
                    <div class="card mt-4">
                        <div class="card-header bg-secondary text-white">
                            <h5 class="mb-0"><i class="fas fa-history me-2"></i>Historial de Movimientos</h5>
                        </div>
                        <div class="card-body">
                            <?php if ($historial->num_rows > 0): ?>
                                <div class="list-group">
                                    <?php while($h = $historial->fetch_assoc()): 
                                        $clase = $h['tipo_movimiento'] == 'ASIGNACION' ? 'success' : 
                                                ($h['tipo_movimiento'] == 'DEVOLUCION' ? 'warning' : 'info');
                                    ?>
                                        <div class="list-group-item list-group-item-<?php echo $clase; ?>">
                                            <div class="d-flex justify-content-between">
                                                <div>
                                                    <strong><?php echo $h['tipo_equipo']; ?></strong>
                                                    <span class="badge bg-<?php echo $clase; ?> ms-2"><?php echo $h['tipo_movimiento']; ?></span>
                                                    <small class="text-muted ms-2"><?php echo $h['codigo_barras']; ?></small>
                                                </div>
                                                <small><?php echo date('d/m/Y H:i', strtotime($h['fecha_movimiento'])); ?></small>
                                            </div>
                                            <?php if ($h['observaciones']): ?>
                                                <p class="mb-0 mt-2"><small><?php echo $h['observaciones']; ?></small></p>
                                            <?php endif; ?>
                                        </div>
                                    <?php endwhile; ?>
                                </div>
                            <?php else: ?>
                                <p class="text-muted">No hay historial de movimientos</p>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Librería QR -->
<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>

<script>
// ============================================
// FUNCIONES PARA EL MODAL QR - CORREGIDO CON TU IP
// ============================================
function generarQR(id) {
    // Mostrar modal
    document.getElementById('qrModal').style.display = 'flex';
    
    // Limpiar contenedor anterior
    document.getElementById('qrcode-container').innerHTML = '';
    
    // ============================================
    // URL CORREGIDA CON TU IP 192.168.101.25
    // ============================================
    var ipServidor = '192.168.101.25'; // TU IP FIJA
    var puerto = '<?php echo $puerto_texto; ?>';
    
    // Construir URL con IP en lugar de localhost
    const url = 'http://' + ipServidor + puerto + '/inventario_ti/modules/personas/ver_equipos_qr.php?id=' + id;
    
    // Mostrar la URL en consola para debug
    console.log('URL del QR CORREGIDA:', url);
    console.log('IP utilizada:', ipServidor);
    
    // Mostrar la URL en el modal (opcional)
    document.getElementById('qr-url-info').style.display = 'block';
    document.getElementById('qr-url-info').innerHTML = '<small>URL: ' + url + '</small>';
    
    // Generar QR
    new QRCode(document.getElementById("qrcode-container"), {
        text: url,
        width: 250,
        height: 250,
        colorDark: "#5a2d8c",
        colorLight: "#ffffff",
        correctLevel: QRCode.CorrectLevel.H
    });
}

function cerrarModalQR() {
    document.getElementById('qrModal').style.display = 'none';
}

// Cerrar modal si se hace clic fuera
window.onclick = function(event) {
    const modal = document.getElementById('qrModal');
    if (event.target == modal) {
        modal.style.display = 'none';
    }
}
</script>

<?php include '../../includes/footer.php'; ?>