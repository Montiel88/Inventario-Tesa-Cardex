<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: /inventario_ti/login.php');
    exit();
}

// Verificar rol (1 = admin, 2 = lector)
$es_admin = ($_SESSION['user_rol'] == 1);

require_once '../../config/database.php';
include '../../includes/header.php';

if (!isset($_GET['id'])) {
    header('Location: listar.php');
    exit();
}

$id = intval($_GET['id']);

// Obtener datos del equipo
$sql_equipo = "SELECT * FROM equipos WHERE id = $id";
$result_equipo = $conn->query($sql_equipo);
if ($result_equipo->num_rows == 0) {
    header('Location: listar.php');
    exit();
}
$equipo = $result_equipo->fetch_assoc();

// Obtener asignación actual (si está prestado)
$sql_asignacion = "SELECT p.nombres, p.cedula, a.fecha_asignacion
                   FROM asignaciones a
                   JOIN personas p ON a.persona_id = p.id
                   WHERE a.equipo_id = $id AND a.fecha_devolucion IS NULL";
$asignacion_actual = $conn->query($sql_asignacion)->fetch_assoc();

// Obtener historial de movimientos
$sql_historial = "SELECT m.*, p.nombres as persona_nombre
                  FROM movimientos m
                  LEFT JOIN personas p ON m.persona_id = p.id
                  WHERE m.equipo_id = $id
                  ORDER BY m.fecha_movimiento DESC
                  LIMIT 10";
$historial = $conn->query($sql_historial);

// Obtener componentes del equipo
$sql_componentes = "SELECT * FROM componentes WHERE equipo_id = $id ORDER BY tipo, nombre_componente";
$componentes = $conn->query($sql_componentes);
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <!-- AVISO PARA LECTORES -->
            <?php if (!$es_admin): ?>
            <div class="alert alert-info d-flex align-items-center mb-4" style="border-left: 4px solid #28a745;">
                <i class="fas fa-info-circle fa-2x me-3 text-success"></i>
                <div>
                    <strong>Modo solo lectura activo</strong>
                    <p class="mb-0">Puedes ver la información pero no puedes modificar nada.</p>
                </div>
            </div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4><i class="fas fa-laptop me-2"></i>Detalle del Equipo</h4>
                    <div class="d-flex gap-2">
                        <!-- Grupo Actas para EQUIPOS -->
                        <div class="btn-group" role="group">
                            <button type="button" class="btn btn-sm btn-outline-success dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-file-pdf me-1"></i>Actas
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end" style="max-height: 400px; overflow-y: auto;">
                                <li><h6 class="dropdown-header">📦 ACTAS DE EQUIPO</h6></li>
                                <li><a class="dropdown-item" href="/inventario_ti/api/generar_acta_ingreso.php?equipo_id=<?php echo $id; ?>" target="_blank">
                                    <i class="fas fa-box-open me-2 text-primary"></i>Acta de Ingreso
                                </a></li>
                                <li><a class="dropdown-item" href="/inventario_ti/api/generar_acta_baja.php?equipo_id=<?php echo $id; ?>" target="_blank">
                                    <i class="fas fa-trash-alt me-2 text-danger"></i>Acta de Baja
                                </a></li>
                                
                                <?php
                                // Verificar si el equipo está asignado a alguna persona
                                $check_asignacion = $conn->query("SELECT persona_id FROM asignaciones WHERE equipo_id = $id AND fecha_devolucion IS NULL");
                                if ($check_asignacion && $check_asignacion->num_rows > 0):
                                    $persona_id = $check_asignacion->fetch_assoc()['persona_id'];
                                ?>
                                <li><hr class="dropdown-divider"></li>
                                <li><h6 class="dropdown-header">👤 ACTAS DE PERSONA ASIGNADA</h6></li>
                                <li><a class="dropdown-item" href="/inventario_ti/api/generar_acta_entrega.php?persona_id=<?php echo $persona_id; ?>" target="_blank">
                                    <i class="fas fa-hand-holding me-2 text-success"></i>Acta Entrega
                                </a></li>
                                <li><a class="dropdown-item" href="/inventario_ti/api/generar_acta_devolucion.php?persona_id=<?php echo $persona_id; ?>" target="_blank">
                                    <i class="fas fa-undo-alt me-2 text-warning"></i>Acta Devolución
                                </a></li>
                                <li><a class="dropdown-item" href="/inventario_ti/api/generar_descargo.php?persona_id=<?php echo $persona_id; ?>" target="_blank">
                                    <i class="fas fa-file-signature me-2 text-info"></i>Descargo
                                </a></li>
                                <?php endif; ?>
                            </ul>
                        </div>
                        
                        <a href="#" onclick="generarQR(<?php echo $id; ?>)" class="btn btn-info btn-sm">
                            <i class="fas fa-qrcode me-1"></i>Ver QR
                        </a>
                        <a href="historial.php?id=<?php echo $id; ?>" class="btn btn-secondary btn-sm">
                            <i class="fas fa-history me-1"></i>Historial
                        </a>
                        <a href="listar.php" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left me-1"></i>Volver
                        </a>
                        <?php if ($es_admin): ?>
                            <a href="editar.php?id=<?php echo $id; ?>" class="btn btn-primary btn-sm">
                                <i class="fas fa-edit me-1"></i>Editar
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="card-body">
                    <!-- Datos del equipo en tabla -->
                    <table class="table table-bordered">
                        <tr><th>Código</th><td><?php echo $equipo['codigo_barras']; ?></td></tr>
                        <tr><th>Tipo</th><td><?php echo $equipo['tipo_equipo']; ?></td></tr>
                        <tr><th>Marca</th><td><?php echo $equipo['marca'] ?: 'N/A'; ?></td></tr>
                        <tr><th>Modelo</th><td><?php echo $equipo['modelo'] ?: 'N/A'; ?></td></tr>
                        <tr><th>Nº Serie</th><td><?php echo $equipo['numero_serie'] ?: 'N/A'; ?></td></tr>
                        <tr><th>Estado</th><td><?php echo $equipo['estado']; ?></td></tr>
                        <tr><th>Ubicación</th><td><?php echo $equipo['ubicacion_id'] ?: 'Sin ubicación'; ?></td></tr>
                    </table>

                    <!-- Información de préstamo actual -->
                    <?php if ($asignacion_actual): ?>
                        <div class="alert alert-warning mt-4">
                            <h5><i class="fas fa-hand-holding me-2"></i>Equipo prestado actualmente</h5>
                            <p>
                                <strong>Persona:</strong> <?php echo $asignacion_actual['nombres']; ?><br>
                                <?php if ($es_admin): ?>
                                    <strong>Cédula:</strong> <?php echo $asignacion_actual['cedula']; ?><br>
                                <?php endif; ?>
                                <strong>Desde:</strong> <?php echo date('d/m/Y', strtotime($asignacion_actual['fecha_asignacion'])); ?>
                            </p>
                            <?php if ($es_admin): ?>
                                <a href="../movimientos/devolucion.php?equipo_id=<?php echo $id; ?>" class="btn btn-warning">
                                    <i class="fas fa-undo-alt me-2"></i>Registrar Devolución
                                </a>
                            <?php else: ?>
                                <button class="btn btn-secondary" disabled>
                                    <i class="fas fa-ban me-2"></i>Devolución (solo admin)
                                </button>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-success mt-4">
                            <i class="fas fa-check-circle me-2"></i>El equipo está disponible en bodega.
                            <?php if ($es_admin): ?>
                                <a href="../asignaciones/cargar_equipos.php?equipo_id=<?php echo $id; ?>" class="btn btn-success btn-sm ms-3">
                                    <i class="fas fa-plus-circle me-1"></i>Asignar
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <!-- SECCIÓN DE COMPONENTES -->
                    <div class="card mt-4">
                        <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><i class="fas fa-microchip me-2"></i>Componentes del Equipo</h5>
                            <?php if ($es_admin): ?>
                                <a href="../componentes/agregar.php?equipo_id=<?php echo $id; ?>" class="btn btn-sm btn-light">
                                    <i class="fas fa-plus-circle me-1"></i>Agregar Componente
                                </a>
                            <?php endif; ?>
                        </div>
                        <div class="card-body">
                            <?php if ($componentes && $componentes->num_rows > 0): ?>
                                <div class="table-responsive">
                                    <table class="table table-sm table-hover">
                                        <thead>
                                            <tr>
                                                <th>Tipo</th>
                                                <th>Nombre</th>
                                                <th>Marca/Modelo</th>
                                                <th>Serie</th>
                                                <th>Estado</th>
                                                <th>Instalación</th>
                                                <?php if ($es_admin): ?><th>Acciones</th><?php endif; ?>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while($comp = $componentes->fetch_assoc()): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($comp['tipo']); ?></td>
                                                <td><?php echo htmlspecialchars($comp['nombre_componente']); ?></td>
                                                <td><?php echo htmlspecialchars($comp['marca'] . ' ' . $comp['modelo']); ?></td>
                                                <td><?php echo htmlspecialchars($comp['numero_serie'] ?? 'N/A'); ?></td>
                                                <td>
                                                    <?php
                                                    $badge = match($comp['estado']) {
                                                        'Bueno' => 'success',
                                                        'Regular' => 'warning',
                                                        'Malo', 'Por reemplazar' => 'danger',
                                                        default => 'secondary'
                                                    };
                                                    echo "<span class='badge bg-$badge'>" . htmlspecialchars($comp['estado']) . "</span>";
                                                    ?>
                                                </td>
                                                <td><?php echo $comp['fecha_instalacion'] ? date('d/m/Y', strtotime($comp['fecha_instalacion'])) : '-'; ?></td>
                                                <?php if ($es_admin): ?>
                                                <td>
                                                    <a href="../componentes/editar.php?id=<?php echo $comp['id']; ?>" class="btn btn-sm btn-warning" title="Editar">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="../componentes/eliminar.php?id=<?php echo $comp['id']; ?>" class="btn btn-sm btn-danger" title="Eliminar" onclick="return confirm('¿Eliminar componente?')">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                </td>
                                                <?php endif; ?>
                                            </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <p class="text-muted">Este equipo no tiene componentes registrados.</p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Historial reciente -->
                    <?php if ($historial && $historial->num_rows > 0): ?>
                        <div class="card mt-4">
                            <div class="card-header bg-secondary text-white">
                                <h5 class="mb-0"><i class="fas fa-history me-2"></i>Últimos movimientos</h5>
                            </div>
                            <div class="card-body">
                                <ul class="list-group">
                                    <?php while($h = $historial->fetch_assoc()): ?>
                                        <li class="list-group-item">
                                            <?php echo date('d/m/Y H:i', strtotime($h['fecha_movimiento'])); ?> - 
                                            <strong><?php echo $h['tipo_movimiento']; ?></strong> 
                                            <?php if ($h['persona_nombre']): ?>
                                                a <?php echo $h['persona_nombre']; ?>
                                            <?php endif; ?>
                                        </li>
                                    <?php endwhile; ?>
                                </ul>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para QR -->
<div id="qrModal" class="qr-modal">
    <div class="qr-modal-content">
        <h4><i class="fas fa-qrcode me-2"></i>Código QR del Equipo</h4>
        <div id="qrcode-container" style="display: flex; justify-content: center; align-items: center; min-height: 250px;"></div>
        <p class="text-muted">Escanea para ver detalles del equipo</p>
        <button class="btn-close" onclick="cerrarModalQR()">Cerrar</button>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
<script>
const baseUrl = 'http://192.168.100.154/inventario_ti';

function generarQR(id) {
    document.getElementById('qrModal').style.display = 'flex';
    document.getElementById('qrcode-container').innerHTML = '';
    const url = baseUrl + '/modules/equipos/detalle.php?id=' + id;
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

window.onclick = function(event) {
    const modal = document.getElementById('qrModal');
    if (event.target == modal) {
        modal.style.display = 'none';
    }
}
</script>

<style>
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
#qrcode-container {
    display: flex;
    justify-content: center;
    align-items: center;
    margin: 20px 0;
    padding: 20px;
    background: #f8f9fc;
    border-radius: 15px;
    min-height: 300px;
}
#qrcode-container canvas {
    margin: 0 auto;
    max-width: 100%;
    height: auto;
}
.qr-modal-content .btn-close {
    background: #dc3545;
    color: white;
    border: none;
    padding: 10px 30px;
    border-radius: 30px;
    font-weight: 600;
    margin-top: 15px;
    cursor: pointer;
    display: block;
    margin-left: auto;
    margin-right: auto;
    width: fit-content;
    text-align: center;
}
.qr-modal-content .btn-close:hover {
    background: #c82333;
}
</style>

<?php include '../../includes/footer.php'; ?>