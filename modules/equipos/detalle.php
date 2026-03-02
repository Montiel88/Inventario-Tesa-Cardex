<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: /inventario_ti/login.php');
    exit();
}
require_once '../../config/database.php';
include '../../includes/header.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: listar.php');
    exit();
}

$id = intval($_GET['id']);
$es_admin = ($_SESSION['user_rol'] == 1);

// Obtener datos del equipo
$sql = "SELECT e.*, 
               a.persona_id, 
               p.nombres as persona_nombre,
               p.cedula as persona_cedula,
               a.fecha_asignacion,
               a.observaciones as obs_asignacion
        FROM equipos e
        LEFT JOIN asignaciones a ON e.id = a.equipo_id AND a.fecha_devolucion IS NULL
        LEFT JOIN personas p ON a.persona_id = p.id
        WHERE e.id = $id";
$result = $conn->query($sql);

if ($result->num_rows == 0) {
    header('Location: listar.php');
    exit();
}

$equipo = $result->fetch_assoc();

// Obtener historial de movimientos del equipo
$sql_historial = "SELECT m.*, p.nombres as persona_nombre
                  FROM movimientos m
                  LEFT JOIN personas p ON m.persona_id = p.id
                  WHERE m.equipo_id = $id
                  ORDER BY m.fecha_movimiento DESC
                  LIMIT 10";
$historial = $conn->query($sql_historial);

// URL para el QR
$url_qr = '/inventario_ti/api/generar_qr_equipo.php?id=' . $id;
?>

<style>
/* ============================================ */
/* ESTILOS PARA EL MODAL DEL QR (como en personas) */
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
    margin: 20px auto;
    padding: 20px;
    background: #f8f9fc;
    border-radius: 15px;
    display: flex;
    justify-content: center;
    align-items: center;
}

.qr-modal-content .btn-close {
    background: #dc3545;
    color: white;
    border: none;
    padding: 10px 30px;
    border-radius: 30px;
    font-weight: 600;
    margin-top: 15px;
    display: inline-block;
    width: auto;
    margin-left: auto;
    margin-right: auto;
}

.qr-modal-content .btn-close:hover {
    background: #c82333;
}

.qr-modal-content .btn {
    margin: 5px;
}

/* ============================================ */
/* RESPONSIVE PARA MÓVILES */
/* ============================================ */
@media (max-width: 768px) {
    .container-fluid {
        padding-left: 10px !important;
        padding-right: 10px !important;
    }
    
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
    
    .col-md-6 .card.bg-light {
        margin-top: 15px !important;
    }
    
    .col-md-6 h3 {
        font-size: 2rem !important;
    }
    
    .btn-success {
        font-size: 1rem !important;
        padding: 12px !important;
    }
    
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
    
    .qr-modal-content {
        width: 95%;
        padding: 20px;
    }
    
    .qr-modal-content #qrcode-container {
        padding: 10px;
    }
}

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
    
    .col-md-6 h3 {
        font-size: 1.8rem !important;
    }
    
    .qr-modal-content {
        padding: 15px;
    }
    
    .qr-modal-content h4 {
        font-size: 1.2rem;
    }
}
</style>

<!-- MODAL PARA MOSTRAR QR (CORREGIDO - CENTRADO COMO EN PERSONAS) -->
<div id="qrModal" class="qr-modal">
    <div class="qr-modal-content">
        <h4><i class="fas fa-qrcode me-2"></i>Código QR del Equipo</h4>
        <div id="qrcode-container"></div>
        <p class="text-muted">Escanea este código para ver los detalles del equipo</p>
        <div class="d-flex gap-2 justify-content-center mt-3">
            <a href="<?php echo $url_qr; ?>" download="qr_equipo_<?php echo $equipo['codigo_barras']; ?>.png" class="btn btn-success">
                <i class="fas fa-download me-2"></i>Descargar
            </a>
            <button class="btn btn-info" onclick="imprimirQR()">
                <i class="fas fa-print me-2"></i>Imprimir
            </button>
        </div>
        <button class="btn-close" onclick="cerrarModalQR()">Cerrar</button>
    </div>
</div>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4><i class="fas fa-laptop me-2"></i>Detalle del Equipo</h4>
                    <div>
                        <!-- Botón VER QR (abre modal) -->
                        <button onclick="generarQR(<?php echo $id; ?>)" class="btn btn-info">
                            <i class="fas fa-qrcode me-2"></i>Ver QR
                        </button>
                        
                        <!-- Botón EDITAR -->
                        <a href="editar.php?id=<?php echo $id; ?>" class="btn btn-primary">
                            <i class="fas fa-edit me-2"></i>Editar
                        </a>
                        
                        <!-- Botón VOLVER -->
                        <a href="listar.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Volver
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    
                    <!-- Datos del equipo -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <table class="table table-bordered">
                                <tr>
                                    <th width="30%">Código</th>
                                    <td><?php echo $equipo['codigo_barras']; ?></td>
                                </tr>
                                <tr>
                                    <th>Tipo</th>
                                    <td><?php echo $equipo['tipo_equipo']; ?></td>
                                </tr>
                                <tr>
                                    <th>Marca</th>
                                    <td><?php echo $equipo['marca'] ?: 'No registrado'; ?></td>
                                </tr>
                                <tr>
                                    <th>Modelo</th>
                                    <td><?php echo $equipo['modelo'] ?: 'No registrado'; ?></td>
                                </tr>
                                <tr>
                                    <th>Número de Serie</th>
                                    <td><?php echo $equipo['numero_serie'] ?: 'No registrado'; ?></td>
                                </tr>
                                <tr>
                                    <th>Estado</th>
                                    <td>
                                        <span class="badge bg-<?php echo $equipo['persona_id'] ? 'warning' : 'success'; ?>">
                                            <?php echo $equipo['persona_id'] ? 'PRESTADO' : 'DISPONIBLE'; ?>
                                        </span>
                                    </td>
                                </tr>
                            </table>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="card bg-light">
                                <div class="card-body text-center">
                                    <?php if ($equipo['persona_id']): ?>
                                        <h3><i class="fas fa-user me-2"></i><?php echo $equipo['persona_nombre']; ?></h3>
                                        <p>Equipo prestado actualmente</p>
                                        <p class="text-muted">Cédula: <?php echo $equipo['persona_cedula']; ?></p>
                                        <p class="text-muted">Desde: <?php echo date('d/m/Y', strtotime($equipo['fecha_asignacion'])); ?></p>
                                        
                                        <!-- BOTÓN DE DEVOLUCIÓN -->
                                        <a href="../movimientos/devolucion.php?equipo_id=<?php echo $id; ?>" class="btn btn-warning btn-lg w-100">
                                            <i class="fas fa-undo-alt me-2"></i>Registrar Devolución
                                        </a>
                                    <?php else: ?>
                                        <h3><i class="fas fa-check-circle text-success"></i> DISPONIBLE</h3>
                                        <p>Equipo disponible en bodega</p>
                                        
                                        <!-- BOTÓN DE PRÉSTAMO -->
                                        <a href="../movimientos/prestamo.php?equipo_id=<?php echo $id; ?>" class="btn btn-primary btn-lg w-100">
                                            <i class="fas fa-hand-holding me-2"></i>Registrar Préstamo
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Especificaciones y Observaciones -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card mt-2">
                                <div class="card-header bg-info text-white">
                                    <h5 class="mb-0"><i class="fas fa-microchip me-2"></i>Especificaciones</h5>
                                </div>
                                <div class="card-body">
                                    <?php echo nl2br($equipo['especificaciones'] ?: 'No hay especificaciones registradas'); ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="card mt-2">
                                <div class="card-header bg-secondary text-white">
                                    <h5 class="mb-0"><i class="fas fa-sticky-note me-2"></i>Observaciones</h5>
                                </div>
                                <div class="card-body">
                                    <?php echo nl2br($equipo['observaciones'] ?: 'No hay observaciones'); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Historial de movimientos -->
                    <div class="card mt-4">
                        <div class="card-header bg-secondary text-white">
                            <h5 class="mb-0"><i class="fas fa-history me-2"></i>Historial de Movimientos</h5>
                        </div>
                        <div class="card-body">
                            <?php if ($historial && $historial->num_rows > 0): ?>
                                <div class="list-group">
                                    <?php while($h = $historial->fetch_assoc()): 
                                        $clase = $h['tipo_movimiento'] == 'ASIGNACION' ? 'warning' : 
                                                ($h['tipo_movimiento'] == 'DEVOLUCION' ? 'success' : 'info');
                                    ?>
                                        <div class="list-group-item list-group-item-<?php echo $clase; ?>">
                                            <div class="d-flex justify-content-between">
                                                <div>
                                                    <strong><?php echo $h['tipo_movimiento']; ?></strong>
                                                    <span class="badge bg-<?php echo $clase; ?> ms-2"><?php echo $h['tipo_movimiento']; ?></span>
                                                    <?php if ($h['persona_nombre']): ?>
                                                        <small class="ms-2">
                                                            <i class="fas fa-user me-1"></i><?php echo $h['persona_nombre']; ?>
                                                        </small>
                                                    <?php endif; ?>
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
                                <p class="text-muted text-center py-3">No hay historial de movimientos</p>
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
// FUNCIONES PARA EL MODAL QR (CORREGIDO - CENTRADO)
// ============================================
function generarQR(id) {
    // Mostrar modal
    document.getElementById('qrModal').style.display = 'flex';
    
    // Limpiar contenedor anterior
    const container = document.getElementById('qrcode-container');
    container.innerHTML = '';
    
    // Asegurar que el contenedor esté centrado
    container.style.display = 'flex';
    container.style.justifyContent = 'center';
    container.style.alignItems = 'center';
    container.style.margin = '20px auto';
    container.style.padding = '20px';
    container.style.background = '#f8f9fc';
    container.style.borderRadius = '15px';
    
    // Construir URL del equipo
    var url = window.location.origin + '/inventario_ti/modules/equipos/detalle.php?id=' + id;
    
    console.log('URL del QR:', url);
    
    // Generar QR
    new QRCode(container, {
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

// ============================================
// FUNCIÓN PARA IMPRIMIR QR
// ============================================
function imprimirQR() {
    let qrUrl = '<?php echo $url_qr; ?>';
    let codigo = '<?php echo $equipo['codigo_barras']; ?>';
    let tipo = '<?php echo $equipo['tipo_equipo']; ?>';
    let marca = '<?php echo $equipo['marca']; ?>';
    let modelo = '<?php echo $equipo['modelo']; ?>';
    
    let ventana = window.open('', '_blank');
    ventana.document.write(`
        <html>
        <head>
            <title>QR Equipo - ${codigo}</title>
            <style>
                body {
                    font-family: 'Poppins', Arial, sans-serif;
                    text-align: center;
                    padding: 20px;
                    background: #f8f9fc;
                }
                .qr-container {
                    margin: 30px auto;
                    padding: 30px;
                    border: 2px solid #5a2d8c;
                    border-radius: 20px;
                    max-width: 450px;
                    background: white;
                    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
                }
                img {
                    max-width: 300px;
                    height: auto;
                    margin: 20px auto;
                    border: 3px solid #f3b229;
                    border-radius: 15px;
                    padding: 10px;
                }
                h2 {
                    color: #5a2d8c;
                }
                .badge {
                    background: #f3b229;
                    color: #5a2d8c;
                    padding: 5px 15px;
                    border-radius: 30px;
                    display: inline-block;
                    margin: 10px 0;
                }
            </style>
        </head>
        <body>
            <div class="qr-container">
                <div class="badge">TESA INVENTARIO</div>
                <h2>${tipo}</h2>
                <h3>${marca} ${modelo}</h3>
                <p>Código: <strong>${codigo}</strong></p>
                <img src="${qrUrl}" alt="QR del equipo">
                <p>Tecnológico San Antonio TESA</p>
            </div>
            <script>
                window.onload = function() { 
                    setTimeout(() => { window.print(); }, 500);
                }
            <\/script>
        </body>
        </html>
    `);
    ventana.document.close();
}

// ============================================
// INICIALIZACIÓN
// ============================================
document.addEventListener('DOMContentLoaded', function() {
    console.log('✅ Página de detalle de equipo cargada correctamente');
});
</script>

<?php include '../../includes/footer.php'; ?>