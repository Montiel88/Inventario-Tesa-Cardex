<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: /inventario_ti/login.php');
    exit();
}
require_once '../../config/database.php';
include '../../includes/header.php';

$mensaje = '';
$error = '';

// Procesar devolución si se envía el formulario
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $equipo_id = intval($_POST['equipo_id']);
    $observacion = $conn->real_escape_string($_POST['observacion']);
    
    // Verificar que el equipo esté prestado
    $sql_verificar = "SELECT a.*, p.nombres as persona_nombre, e.tipo_equipo 
                      FROM asignaciones a
                      JOIN personas p ON a.persona_id = p.id
                      JOIN equipos e ON a.equipo_id = e.id
                      WHERE a.equipo_id = $equipo_id AND a.fecha_devolucion IS NULL";
    $result = $conn->query($sql_verificar);
    
    if ($result->num_rows > 0) {
        $asignacion = $result->fetch_assoc();
        
        // Iniciar transacción
        $conn->begin_transaction();
        
        try {
            // 1. Actualizar la asignación con fecha de devolución
            $sql_update = "UPDATE asignaciones SET fecha_devolucion = NOW(), observaciones = '$observacion' 
                          WHERE id = " . $asignacion['id'];
            $conn->query($sql_update);
            
            // 2. Actualizar estado del equipo
            $sql_equipo = "UPDATE equipos SET estado = 'Disponible' WHERE id = $equipo_id";
            $conn->query($sql_equipo);
            
            // 3. Registrar en movimientos
            $sql_movimiento = "INSERT INTO movimientos (equipo_id, persona_id, tipo_movimiento, observaciones) 
                              VALUES ($equipo_id, " . $asignacion['persona_id'] . ", 'DEVOLUCION', '$observacion')";
            $conn->query($sql_movimiento);
            
            $conn->commit();
            $mensaje = "✅ Devolución registrada correctamente";
        } catch (Exception $e) {
            $conn->rollback();
            $error = "❌ Error al registrar devolución: " . $e->getMessage();
        }
    } else {
        $error = "❌ Este equipo no está prestado actualmente";
    }
}

// Obtener lista de equipos prestados
$sql_prestados = "SELECT a.id as asignacion_id, a.fecha_asignacion, a.observaciones,
                         e.id as equipo_id, e.codigo_barras, e.tipo_equipo, e.marca, e.modelo,
                         p.id as persona_id, p.nombres, p.cedula
                  FROM asignaciones a
                  JOIN equipos e ON a.equipo_id = e.id
                  JOIN personas p ON a.persona_id = p.id
                  WHERE a.fecha_devolucion IS NULL
                  ORDER BY a.fecha_asignacion DESC";
$result_prestados = $conn->query($sql_prestados);
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0"><i class="fas fa-undo-alt me-2"></i>Registrar Devolución de Equipo</h4>
                </div>
                <div class="card-body">
                    
                    <?php if ($mensaje): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle me-2"></i><?php echo $mensaje; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <div class="row">
                        <!-- Columna izquierda: Lista de equipos prestados -->
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header bg-warning">
                                    <h5 class="mb-0"><i class="fas fa-hand-holding me-2"></i>Equipos Prestados Actualmente</h5>
                                </div>
                                <div class="card-body" style="max-height: 500px; overflow-y: auto;">
                                    <?php if ($result_prestados && $result_prestados->num_rows > 0): ?>
                                        <div class="list-group">
                                            <?php while($row = $result_prestados->fetch_assoc()): ?>
                                                <div class="list-group-item list-group-item-action" style="cursor: pointer;" onclick="seleccionarEquipo(<?php echo $row['equipo_id']; ?>, '<?php echo $row['tipo_equipo']; ?>', '<?php echo $row['nombres']; ?>')">
                                                    <div class="d-flex justify-content-between">
                                                        <div>
                                                            <strong><?php echo $row['tipo_equipo']; ?></strong>
                                                            <small class="text-muted d-block"><?php echo $row['marca'] . ' ' . $row['modelo']; ?></small>
                                                        </div>
                                                        <small class="text-warning"><?php echo $row['codigo_barras']; ?></small>
                                                    </div>
                                                    <div class="mt-2">
                                                        <small>
                                                            <i class="fas fa-user me-1"></i><?php echo $row['nombres']; ?><br>
                                                            <i class="fas fa-calendar me-1"></i><?php echo date('d/m/Y', strtotime($row['fecha_asignacion'])); ?>
                                                        </small>
                                                    </div>
                                                </div>
                                            <?php endwhile; ?>
                                        </div>
                                    <?php else: ?>
                                        <p class="text-center text-muted py-4">
                                            <i class="fas fa-check-circle fa-3x mb-3"></i><br>
                                            No hay equipos prestados actualmente
                                        </p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Columna derecha: Formulario de devolución -->
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header bg-success text-white">
                                    <h5 class="mb-0"><i class="fas fa-undo-alt me-2"></i>Registrar Devolución</h5>
                                </div>
                                <div class="card-body">
                                    <form method="POST" id="formDevolucion">
                                        <div class="mb-3">
                                            <label class="form-label">Equipo a devolver</label>
                                            <div class="input-group">
                                                <span class="input-group-text"><i class="fas fa-laptop"></i></span>
                                                <input type="text" id="equipo_display" class="form-control" readonly placeholder="Seleccione un equipo de la lista">
                                                <input type="hidden" name="equipo_id" id="equipo_id">
                                            </div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label">Persona que devuelve</label>
                                            <div class="input-group">
                                                <span class="input-group-text"><i class="fas fa-user"></i></span>
                                                <input type="text" id="persona_display" class="form-control" readonly placeholder="Seleccione un equipo primero">
                                            </div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label">Observaciones (opcional)</label>
                                            <textarea name="observacion" class="form-control" rows="3" placeholder="Estado del equipo, novedades, etc."></textarea>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label">¿Desea escanear el código?</label>
                                            <button type="button" class="btn btn-primary w-100" onclick="abrirScanner()">
                                                <i class="fas fa-camera me-2"></i>Abrir Escáner
                                            </button>
                                        </div>
                                        
                                        <button type="submit" class="btn btn-success btn-lg w-100" id="btnRegistrar" disabled>
                                            <i class="fas fa-check-circle me-2"></i>Registrar Devolución
                                        </button>
                                    </form>
                                </div>
                            </div>
                            
                            <!-- Escáner QR Modal -->
                            <div class="modal fade" id="scannerModal" tabindex="-1">
                                <div class="modal-dialog modal-lg">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title"><i class="fas fa-camera me-2"></i>Escanear Código del Equipo</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div id="reader" style="width: 100%; min-height: 400px;"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
let html5QrCode = null;

// Función para seleccionar equipo de la lista
function seleccionarEquipo(equipoId, equipoNombre, personaNombre) {
    document.getElementById('equipo_id').value = equipoId;
    document.getElementById('equipo_display').value = equipoNombre;
    document.getElementById('persona_display').value = personaNombre;
    document.getElementById('btnRegistrar').disabled = false;
    
    // Mostrar confirmación
    Swal.fire({
        icon: 'success',
        title: 'Equipo seleccionado',
        text: `Devolución de: ${equipoNombre}`,
        timer: 1500,
        showConfirmButton: false
    });
}

// Función para abrir el escáner
function abrirScanner() {
    const modal = new bootstrap.Modal(document.getElementById('scannerModal'));
    modal.show();
    
    document.getElementById('scannerModal').addEventListener('shown.bs.modal', function () {
        if (html5QrCode) {
            html5QrCode.stop();
        }
        
        html5QrCode = new Html5Qrcode("reader");
        const config = { fps: 10, qrbox: { width: 250, height: 250 } };
        
        html5QrCode.start(
            { facingMode: "environment" },
            config,
            (decodedText) => {
                // Buscar el equipo por código escaneado
                buscarEquipoPorCodigo(decodedText);
                bootstrap.Modal.getInstance(document.getElementById('scannerModal')).hide();
            },
            (error) => {}
        );
    });
}

// Función para buscar equipo por código
function buscarEquipoPorCodigo(codigo) {
    fetch(`/inventario_ti/api/buscar_producto.php?codigo=${codigo}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.equipo) {
                // Verificar si el equipo está prestado
                if (data.equipo.estado == 'Asignado' || data.equipo.persona_id) {
                    seleccionarEquipo(
                        data.equipo.id,
                        data.equipo.tipo_equipo,
                        data.equipo.persona_nombre || 'Desconocido'
                    );
                } else {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Equipo no prestado',
                        text: 'Este equipo no está actualmente prestado',
                        confirmButtonColor: '#5a2d8c'
                    });
                }
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Equipo no encontrado',
                    text: 'No existe un equipo con ese código',
                    confirmButtonColor: '#5a2d8c'
                });
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire('Error', 'Error al conectar con el servidor', 'error');
        });
}

// Detener escáner al cerrar modal
document.getElementById('scannerModal').addEventListener('hidden.bs.modal', function () {
    if (html5QrCode) {
        html5QrCode.stop().then(() => {
            html5QrCode = null;
        });
    }
});

// Validar formulario antes de enviar
document.getElementById('formDevolucion').addEventListener('submit', function(e) {
    if (!document.getElementById('equipo_id').value) {
        e.preventDefault();
        Swal.fire({
            icon: 'warning',
            title: 'Seleccione un equipo',
            text: 'Debe seleccionar un equipo de la lista o escanearlo',
            confirmButtonColor: '#5a2d8c'
        });
    }
});
</script>

<?php include '../../includes/footer.php'; ?>