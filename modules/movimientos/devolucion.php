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

// ============================================
// PROCESAR DEVOLUCIÓN SI SE ENVÍA EL FORMULARIO
// ============================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['equipo_id'])) {
    $equipo_id = intval($_POST['equipo_id']);
    $observacion = $conn->real_escape_string($_POST['observacion'] ?? '');
    $estado_equipo = $conn->real_escape_string($_POST['estado_equipo'] ?? '');
    $condiciones = $conn->real_escape_string($_POST['condiciones'] ?? '');
    
    // Validar que se seleccionó el estado
    if (empty($estado_equipo)) {
        $error = "❌ Debe seleccionar el estado del equipo";
    } else {
        // Verificar que el equipo esté prestado
        $sql_verificar = "SELECT a.*, p.nombres as persona_nombre, e.tipo_equipo, e.codigo_barras
                          FROM asignaciones a
                          JOIN personas p ON a.persona_id = p.id
                          JOIN equipos e ON a.equipo_id = e.id
                          WHERE a.equipo_id = $equipo_id AND a.fecha_devolucion IS NULL";
        $result = $conn->query($sql_verificar);
        
        if ($result && $result->num_rows > 0) {
            $asignacion = $result->fetch_assoc();
            
            // Procesar foto si se subió
            $foto_devolucion = '';
            if(isset($_FILES['foto_equipo']) && $_FILES['foto_equipo']['error'] == 0) {
                $allowed = ['jpg', 'jpeg', 'png', 'gif'];
                $filename = $_FILES['foto_equipo']['name'];
                $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                
                if(in_array($ext, $allowed)) {
                    // Crear carpeta si no existe
                    $carpeta_fotos = '../../uploads/devoluciones/';
                    if (!file_exists($carpeta_fotos)) {
                        mkdir($carpeta_fotos, 0777, true);
                    }
                    
                    $nuevo_nombre = 'devolucion_' . $equipo_id . '_' . date('YmdHis') . '.' . $ext;
                    $destino = $carpeta_fotos . $nuevo_nombre;
                    
                    if(move_uploaded_file($_FILES['foto_equipo']['tmp_name'], $destino)) {
                        $foto_devolucion = 'uploads/devoluciones/' . $nuevo_nombre;
                    }
                }
            }
            
            // Iniciar transacción
            $conn->begin_transaction();
            
            try {
                // 1. Actualizar la asignación con fecha de devolución
                $sql_update = "UPDATE asignaciones SET fecha_devolucion = NOW(), observaciones = '$observacion' 
                              WHERE id = " . $asignacion['id'];
                $conn->query($sql_update);
                
                // 2. Actualizar estado del equipo (si está dañado, queda como "En reparación")
                $nuevo_estado = ($estado_equipo == 'BUENO') ? 'Disponible' : 'En reparación';
                $sql_equipo = "UPDATE equipos SET estado = '$nuevo_estado' WHERE id = $equipo_id";
                $conn->query($sql_equipo);
                
                // 3. Registrar en movimientos con TODOS los datos de trazabilidad
                $sql_movimiento = "INSERT INTO movimientos 
                                  (equipo_id, persona_id, tipo_movimiento, observaciones, estado_equipo, condiciones, foto_devolucion) 
                                  VALUES ($equipo_id, " . $asignacion['persona_id'] . ", 'DEVOLUCION', '$observacion', '$estado_equipo', '$condiciones', '$foto_devolucion')";
                $conn->query($sql_movimiento);
                
                // 4. Generar acta de devolución automáticamente
                $acta_url = "/inventario_ti/api/generar_acta_mpdf.php?tipo=devolucion&persona_id=" . $asignacion['persona_id'];
                
                $conn->commit();
                $mensaje = "✅ Devolución registrada correctamente";
                
                echo "<script>
                    Swal.fire({
                        icon: 'success',
                        title: '¡Devolución exitosa!',
                        html: '<p>El equipo ha sido devuelto correctamente</p><p>Estado: <strong>$estado_equipo</strong></p>',
                        showCancelButton: true,
                        confirmButtonText: '📄 Ver Acta',
                        cancelButtonText: 'Ir al Historial'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            window.open('$acta_url', '_blank');
                            window.location.href = 'historial.php';
                        } else {
                            window.location.href = 'historial.php';
                        }
                    });
                </script>";
                
            } catch (Exception $e) {
                $conn->rollback();
                $error = "❌ Error al registrar devolución: " . $e->getMessage();
            }
        } else {
            $error = "❌ Este equipo no está prestado actualmente";
        }
    }
}

// ============================================
// OBTENER LISTA DE EQUIPOS PRESTADOS
// ============================================
$sql_prestados = "SELECT 
                    a.id as asignacion_id, 
                    a.fecha_asignacion, 
                    a.observaciones as obs_asignacion,
                    e.id as equipo_id, 
                    e.codigo_barras, 
                    e.tipo_equipo, 
                    e.marca, 
                    e.modelo,
                    e.numero_serie,
                    p.id as persona_id, 
                    p.nombres, 
                    p.cedula
                  FROM asignaciones a
                  INNER JOIN equipos e ON a.equipo_id = e.id
                  INNER JOIN personas p ON a.persona_id = p.id
                  WHERE a.fecha_devolucion IS NULL
                  ORDER BY a.fecha_asignacion DESC";

$result_prestados = $conn->query($sql_prestados);

// Si viene un equipo específico por GET, seleccionarlo automáticamente
$equipo_seleccionado = isset($_GET['equipo_id']) ? intval($_GET['equipo_id']) : 0;
?>

<style>
/* Estilos adicionales */
.equipo-item {
    cursor: pointer;
    transition: all 0.3s ease;
    border-left: 4px solid transparent;
}

.equipo-item:hover {
    transform: translateX(5px);
    background-color: #f8f9fa !important;
}

.equipo-item.seleccionado {
    border-left-color: #f3b229;
    background-color: #f3e9ff !important;
    box-shadow: 0 2px 10px rgba(90, 45, 140, 0.1);
}

.estado-badge {
    font-size: 0.9rem;
    padding: 5px 10px;
    border-radius: 20px;
}

/* Responsive */
@media (max-width: 768px) {
    .list-group-item .d-flex {
        flex-direction: column !important;
        align-items: flex-start !important;
        gap: 5px !important;
    }
}
</style>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0"><i class="fas fa-undo-alt me-2"></i>Registrar Devolución de Equipo</h4>
                    <div>
                        <a href="historial.php" class="btn btn-sm btn-secondary me-2">
                            <i class="fas fa-history me-2"></i>Historial
                        </a>
                
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    
                    <!-- MENSAJES -->
                    <?php if ($mensaje): ?>
                        <div class="alert alert-success alert-dismissible fade show">
                            <i class="fas fa-check-circle me-2"></i><?php echo $mensaje; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show">
                            <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <div class="row">
                        <!-- Columna izquierda: Lista de equipos prestados -->
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header bg-warning text-dark">
                                    <h5 class="mb-0"><i class="fas fa-hand-holding me-2"></i>Equipos Prestados Actualmente</h5>
                                </div>
                                <div class="card-body" style="max-height: 600px; overflow-y: auto;">
                                    <?php if ($result_prestados && $result_prestados->num_rows > 0): ?>
                                        <div class="list-group">
                                            <?php while($row = $result_prestados->fetch_assoc()): 
                                                $seleccionado = ($row['equipo_id'] == $equipo_seleccionado) ? 'seleccionado' : '';
                                            ?>
                                                <div class="list-group-item list-group-item-action equipo-item <?php echo $seleccionado; ?>" 
                                                     onclick="seleccionarEquipo(<?php echo $row['equipo_id']; ?>, '<?php echo $row['tipo_equipo']; ?>', '<?php echo $row['marca']; ?>', '<?php echo $row['modelo']; ?>', '<?php echo $row['nombres']; ?>', '<?php echo $row['codigo_barras']; ?>', '<?php echo $row['numero_serie']; ?>')">
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <div>
                                                            <strong><i class="fas fa-laptop me-2"></i><?php echo $row['tipo_equipo']; ?></strong>
                                                            <span class="badge bg-secondary ms-2"><?php echo $row['codigo_barras']; ?></span>
                                                            <br>
                                                            <small class="text-muted">
                                                                <?php echo $row['marca'] . ' ' . $row['modelo']; ?>
                                                                <?php if($row['numero_serie']): ?>
                                                                    <br>Serie: <?php echo $row['numero_serie']; ?>
                                                                <?php endif; ?>
                                                            </small>
                                                        </div>
                                                        <div class="text-end">
                                                            <small class="badge bg-info">
                                                                <i class="fas fa-user me-1"></i><?php echo $row['nombres']; ?>
                                                            </small>
                                                            <br>
                                                            <small class="text-muted">
                                                                <?php echo date('d/m/Y', strtotime($row['fecha_asignacion'])); ?>
                                                            </small>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endwhile; ?>
                                        </div>
                                    <?php else: ?>
                                        <div class="text-center text-muted py-5">
                                            <i class="fas fa-check-circle fa-4x mb-3"></i>
                                            <h5>No hay equipos prestados</h5>
                                            <p>Todos los equipos están disponibles en bodega</p>
                                            <a href="../asignaciones/cargar_equipos.php" class="btn btn-primary mt-3">
                                                <i class="fas fa-plus-circle me-2"></i>Registrar Préstamo
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Columna derecha: Formulario de devolución MEJORADO -->
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header bg-success text-white">
                                    <h5 class="mb-0"><i class="fas fa-undo-alt me-2"></i>Registrar Devolución</h5>
                                </div>
                                <div class="card-body">
                                    <form method="POST" id="formDevolucion" enctype="multipart/form-data" onsubmit="return validarFormulario()">
                                        <input type="hidden" name="equipo_id" id="equipo_id" value="<?php echo $equipo_seleccionado; ?>">
                                        
                                        <div class="mb-3">
                                            <label class="form-label">Equipo a devolver</label>
                                            <div class="input-group">
                                                <span class="input-group-text"><i class="fas fa-laptop"></i></span>
                                                <input type="text" id="equipo_display" class="form-control" readonly 
                                                       placeholder="Seleccione un equipo de la lista"
                                                       value="<?php 
                                                           if($equipo_seleccionado > 0) {
                                                               $result_prestados->data_seek(0);
                                                               while($row = $result_prestados->fetch_assoc()) {
                                                                   if($row['equipo_id'] == $equipo_seleccionado) {
                                                                       echo $row['tipo_equipo'] . ' - ' . $row['marca'] . ' ' . $row['modelo'];
                                                                       break;
                                                                   }
                                                               }
                                                           }
                                                       ?>">
                                            </div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label">Persona que devuelve</label>
                                            <div class="input-group">
                                                <span class="input-group-text"><i class="fas fa-user"></i></span>
                                                <input type="text" id="persona_display" class="form-control" readonly 
                                                       placeholder="Seleccione un equipo primero"
                                                       value="<?php 
                                                           if($equipo_seleccionado > 0) {
                                                               $result_prestados->data_seek(0);
                                                               while($row = $result_prestados->fetch_assoc()) {
                                                                   if($row['equipo_id'] == $equipo_seleccionado) {
                                                                       echo $row['nombres'];
                                                                       break;
                                                                   }
                                                               }
                                                           }
                                                       ?>">
                                            </div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label">Código de barras</label>
                                            <div class="input-group">
                                                <span class="input-group-text"><i class="fas fa-qrcode"></i></span>
                                                <input type="text" id="codigo_display" class="form-control" readonly 
                                                       placeholder="Código del equipo"
                                                       value="<?php 
                                                           if($equipo_seleccionado > 0) {
                                                               $result_prestados->data_seek(0);
                                                               while($row = $result_prestados->fetch_assoc()) {
                                                                   if($row['equipo_id'] == $equipo_seleccionado) {
                                                                       echo $row['codigo_barras'];
                                                                       break;
                                                                   }
                                                               }
                                                           }
                                                       ?>">
                                            </div>
                                        </div>
                                        
                                        <!-- NUEVO: Estado del equipo -->
                                        <div class="mb-3">
                                            <label class="form-label">Estado del equipo <span class="text-danger">*</span></label>
                                            <select name="estado_equipo" id="estado_equipo" class="form-select" required>
                                                <option value="">-- Seleccione estado --</option>
                                                <option value="BUENO">✅ Bueno (funciona correctamente)</option>
                                                <option value="REGULAR">🟡 Regular (funciona con detalles)</option>
                                                <option value="MALO">❌ Malo (no funciona)</option>
                                                <option value="DAÑADO">🔴 Dañado (requiere reparación)</option>
                                                <option value="INCOMPLETO">⚠️ Incompleto (faltan piezas)</option>
                                            </select>
                                        </div>
                                        
                                        <!-- NUEVO: Condiciones detalladas -->
                                        <div class="mb-3">
                                            <label class="form-label">Condiciones / Observaciones</label>
                                            <textarea name="condiciones" class="form-control" rows="2" 
                                                      placeholder="Detalle el estado físico, daños, piezas faltantes, etc."></textarea>
                                        </div>
                                        
                                        <!-- NUEVO: Subir foto -->
                                        <div class="mb-3">
                                            <label class="form-label">Foto del equipo (opcional)</label>
                                            <input type="file" name="foto_equipo" class="form-control" accept="image/*">
                                            <small class="text-muted">Tome una foto del equipo al devolverlo para mayor control</small>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label">Observaciones adicionales</label>
                                            <textarea name="observacion" class="form-control" rows="2" 
                                                      placeholder="Notas adicionales sobre la devolución"></textarea>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label">¿Desea escanear el código?</label>
                                            <button type="button" class="btn btn-primary w-100" onclick="abrirScanner()">
                                                <i class="fas fa-camera me-2"></i>Abrir Escáner
                                            </button>
                                        </div>
                                        
                                        <button type="submit" class="btn btn-success btn-lg w-100" id="btnRegistrar" <?php echo $equipo_seleccionado ? '' : 'disabled'; ?>>
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
                                            <p class="text-center text-muted mt-2">
                                                <small>Apunta al código QR o código de barras del equipo</small>
                                            </p>
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
// ============================================
// VARIABLES GLOBALES
// ============================================
let html5QrCode = null;
let equiposPrestados = [];

// Cargar equipos prestados
<?php 
$result_prestados->data_seek(0);
while($row = $result_prestados->fetch_assoc()): 
?>
equiposPrestados.push({
    id: <?php echo $row['equipo_id']; ?>,
    codigo: '<?php echo $row['codigo_barras']; ?>',
    tipo: '<?php echo $row['tipo_equipo']; ?>',
    marca: '<?php echo $row['marca']; ?>',
    modelo: '<?php echo $row['modelo']; ?>',
    serie: '<?php echo $row['numero_serie']; ?>',
    persona: '<?php echo addslashes($row['nombres']); ?>'
});
<?php endwhile; ?>

// ============================================
// FUNCIÓN PARA SELECCIONAR EQUIPO
// ============================================
function seleccionarEquipo(id, tipo, marca, modelo, persona, codigo, serie) {
    document.getElementById('equipo_id').value = id;
    document.getElementById('equipo_display').value = tipo + ' - ' + marca + ' ' + modelo;
    document.getElementById('persona_display').value = persona;
    document.getElementById('codigo_display').value = codigo;
    document.getElementById('btnRegistrar').disabled = false;
    
    document.querySelectorAll('.equipo-item').forEach(item => {
        item.classList.remove('seleccionado');
    });
    event.currentTarget.classList.add('seleccionado');
    
    Swal.fire({
        icon: 'success',
        title: 'Equipo seleccionado',
        text: `${tipo} - ${persona}`,
        timer: 1500,
        showConfirmButton: false,
        toast: true,
        position: 'top-end'
    });
}

// ============================================
// FUNCIÓN PARA ABRIR ESCÁNER
// ============================================
function abrirScanner() {
    const modal = new bootstrap.Modal(document.getElementById('scannerModal'));
    modal.show();
    
    document.getElementById('scannerModal').addEventListener('shown.bs.modal', function () {
        if (html5QrCode) html5QrCode.stop();
        
        html5QrCode = new Html5Qrcode("reader");
        html5QrCode.start(
            { facingMode: "environment" },
            { fps: 10, qrbox: { width: 250, height: 250 } },
            (decodedText) => {
                const equipo = equiposPrestados.find(e => e.codigo === decodedText);
                if (equipo) {
                    seleccionarEquipo(equipo.id, equipo.tipo, equipo.marca, equipo.modelo, equipo.persona, equipo.codigo, equipo.serie);
                    bootstrap.Modal.getInstance(document.getElementById('scannerModal')).hide();
                    Swal.fire('Equipo encontrado', `${equipo.tipo} - ${equipo.persona}`, 'success');
                } else {
                    Swal.fire('Equipo no encontrado', 'El código no corresponde a un equipo prestado', 'warning');
                }
            }
        );
    });
}

// ============================================
// VALIDAR FORMULARIO
// ============================================
function validarFormulario() {
    const equipoId = document.getElementById('equipo_id').value;
    const estado = document.getElementById('estado_equipo').value;
    
    if (!equipoId || equipoId == 0) {
        Swal.fire('Error', 'Seleccione un equipo', 'warning');
        return false;
    }
    
    if (!estado) {
        Swal.fire('Error', 'Seleccione el estado del equipo', 'warning');
        return false;
    }
    
    return confirm('¿Registrar esta devolución? Se generará el acta automáticamente.');
}

// ============================================
// LIMPIEZA
// ============================================
document.getElementById('scannerModal').addEventListener('hidden.bs.modal', function () {
    if (html5QrCode) {
        html5QrCode.stop().then(() => html5QrCode = null);
    }
});

document.addEventListener('DOMContentLoaded', function() {
    <?php if ($equipo_seleccionado > 0): ?>
    document.getElementById('btnRegistrar').disabled = false;
    <?php endif; ?>
});
</script>

<?php include '../../includes/footer.php'; ?>