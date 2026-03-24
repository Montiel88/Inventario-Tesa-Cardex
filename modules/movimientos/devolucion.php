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
                
                // ============================================
                // 2. ACTUALIZAR ESTADO Y CREAR MANTENIMIENTO SI ES NECESARIO
                // ============================================
                if ($estado_equipo == 'BUENO') {
                    $nuevo_estado = 'Disponible';
                } else {
                    $nuevo_estado = 'En mantenimiento';
                    
                    $descripcion_manto = "Equipo ingresado a mantenimiento por devolución en estado: $estado_equipo";
                    if (!empty($condiciones)) {
                        $descripcion_manto .= " - Condiciones: $condiciones";
                    }
                    
                    $sql_mantenimiento = "INSERT INTO mantenimientos 
                        (equipo_id, fecha_ingreso, tipo_mantenimiento, descripcion, observaciones, created_by) 
                        VALUES 
                        ($equipo_id, NOW(), 'correctivo', '$descripcion_manto', 'Generado automáticamente por devolución', {$_SESSION['user_id']})";
                    $conn->query($sql_mantenimiento);
                }
                
                $sql_equipo = "UPDATE equipos SET estado = '$nuevo_estado' WHERE id = $equipo_id";
                $conn->query($sql_equipo);
                
                // 3. Registrar en movimientos
                $sql_movimiento = "INSERT INTO movimientos 
                                  (equipo_id, persona_id, tipo_movimiento, observaciones, estado_equipo, condiciones, foto_devolucion) 
                                  VALUES ($equipo_id, " . $asignacion['persona_id'] . ", 'DEVOLUCION', '$observacion', '$estado_equipo', '$condiciones', '$foto_devolucion')";
                $conn->query($sql_movimiento);
                
                // 4. Generar acta de devolución automáticamente
                $acta_url = "/inventario_ti/api/generar_acta_mpdf.php?tipo=devolucion&persona_id=" . $asignacion['persona_id'];
                
                $conn->commit();
                $mensaje = "✅ Devolución registrada correctamente";
                
                // Registrar notificación
                require_once '../../config/notificaciones_helper.php';
                registrar_notificacion(
                    $_SESSION['user_id'],
                    'success',
                    '🔄 Devolución registrada',
                    "Equipo {$asignacion['tipo_equipo']} ({$asignacion['codigo_barras']}) devuelto por {$asignacion['persona_nombre']}",
                    "/inventario_ti/modules/equipos/detalle.php?id={$equipo_id}"
                );
                
                $mensaje_adicional = ($estado_equipo != 'BUENO') ? ' Se ha creado un registro automático en Mantenimientos.' : '';
                
                echo "<script>
                    Swal.fire({
                        icon: 'success',
                        title: '¡Devolución exitosa!',
                        html: '<p>El equipo ha sido devuelto correctamente</p><p>Estado: <strong>$estado_equipo</strong></p><p><small>$mensaje_adicional</small></p>',
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
                
                // Registrar notificación de error
                require_once '../../config/notificaciones_helper.php';
                registrar_notificacion(
                    $_SESSION['user_id'],
                    'error',
                    '❌ Error en devolución',
                    'No se pudo registrar la devolución: ' . $e->getMessage(),
                    null
                );
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

<!-- ============================================ -->
<!-- ESTILOS ADICIONALES PARA EL FORMULARIO -->
<!-- ============================================ -->
<style>
    .devolucion-card {
        border: none;
        border-radius: 15px;
        box-shadow: 0 5px 20px rgba(0,0,0,0.08);
    }
    .btn-devolver {
        border-radius: 30px;
        padding: 8px 20px;
        font-weight: 600;
        transition: all 0.3s;
    }
    .btn-devolver:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(90,45,140,0.3);
    }
    .estado-badge {
        font-size: 0.9rem;
        padding: 5px 10px;
    }
    /* Estilo para el formulario de devolución */
    #formularioDevolucion {
        background: #f8f9fc;
        border-radius: 15px;
        padding: 20px;
        margin-top: 20px;
        border: 1px solid #e0e0e0;
    }
</style>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card devolucion-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0"><i class="fas fa-undo-alt me-2"></i>Registrar Devolución de Equipo</h4>
                    <a href="historial.php" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-history me-1"></i> Historial
                    </a>
                </div>
                <div class="card-body">
                    
                    <!-- Mensajes de éxito/error (se muestran con SweetAlert, pero también aquí por si acaso) -->
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    <?php if ($mensaje): ?>
                        <div class="alert alert-success"><?php echo $mensaje; ?></div>
                    <?php endif; ?>

                    <!-- Tabla de equipos prestados -->
                    <?php if ($result_prestados && $result_prestados->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Código</th>
                                        <th>Equipo</th>
                                        <th>Persona</th>
                                        <th>Fecha préstamo</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while($row = $result_prestados->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($row['codigo_barras']); ?></td>
                                        <td>
                                            <?php echo htmlspecialchars($row['tipo_equipo'] . ' ' . $row['marca'] . ' ' . $row['modelo']); ?>
                                            <br><small><?php echo htmlspecialchars($row['numero_serie'] ?? 'N/A'); ?></small>
                                        </td>
                                        <td>
                                            <?php echo htmlspecialchars($row['nombres']); ?>
                                            <br><small><?php echo htmlspecialchars($row['cedula']); ?></small>
                                        </td>
                                        <td><?php echo date('d/m/Y H:i', strtotime($row['fecha_asignacion'])); ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-warning btn-devolver" 
                                                    data-equipo-id="<?php echo $row['equipo_id']; ?>"
                                                    data-equipo-nombre="<?php echo htmlspecialchars($row['tipo_equipo'] . ' ' . $row['marca'] . ' ' . $row['modelo']); ?>"
                                                    data-persona-nombre="<?php echo htmlspecialchars($row['nombres']); ?>">
                                                <i class="fas fa-undo-alt"></i> Devolver
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info text-center py-4">
                            <i class="fas fa-info-circle fa-3x mb-3"></i>
                            <h5>No hay equipos prestados actualmente</h5>
                            <p>Todos los equipos están disponibles o no hay préstamos registrados.</p>
                            <a href="prestamo.php" class="btn btn-primary mt-2">
                                <i class="fas fa-hand-holding me-2"></i>Registrar nuevo préstamo
                            </a>
                        </div>
                    <?php endif; ?>

                    <!-- Formulario de devolución (oculto inicialmente, se muestra al hacer clic en un botón) -->
                    <div id="formularioDevolucion" style="display: none;">
                        <h5 class="mb-3"><i class="fas fa-undo-alt me-2"></i>Detalles de la devolución</h5>
                        <form method="POST" enctype="multipart/form-data" id="devolucionForm">
                            <input type="hidden" name="equipo_id" id="equipo_id" value="">
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Estado del equipo <span class="text-danger">*</span></label>
                                    <select name="estado_equipo" id="estado_equipo" class="form-control" required>
                                        <option value="">-- Seleccione --</option>
                                        <option value="BUENO">✅ Bueno</option>
                                        <option value="REGULAR">⚠️ Regular</option>
                                        <option value="MALO">❌ Malo</option>
                                        <option value="DAÑADO">🔧 Dañado (requiere mantenimiento)</option>
                                    </select>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Condiciones adicionales</label>
                                    <select name="condiciones" class="form-control">
                                        <option value="">-- Normal --</option>
                                        <option value="CON_ACCESORIOS">Con accesorios completos</option>
                                        <option value="SIN_ACCESORIOS">Sin accesorios</option>
                                        <option value="CON_FALLAS">Con fallas</option>
                                    </select>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Foto del equipo (opcional)</label>
                                    <input type="file" name="foto_equipo" class="form-control" accept="image/*">
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Observaciones</label>
                                <textarea name="observacion" class="form-control" rows="2" placeholder="Detalles adicionales..."></textarea>
                            </div>
                            <div class="d-flex justify-content-end gap-2">
                                <button type="button" class="btn btn-secondary" id="cancelarDevolucion">Cancelar</button>
                                <button type="submit" class="btn btn-primary">Registrar devolución</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Script para manejar el formulario de devolución -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const botones = document.querySelectorAll('.btn-devolver');
    const formulario = document.getElementById('formularioDevolucion');
    const equipoIdInput = document.getElementById('equipo_id');
    const cancelarBtn = document.getElementById('cancelarDevolucion');
    
    botones.forEach(btn => {
        btn.addEventListener('click', function() {
            const equipoId = this.getAttribute('data-equipo-id');
            const equipoNombre = this.getAttribute('data-equipo-nombre');
            const personaNombre = this.getAttribute('data-persona-nombre');
            
            equipoIdInput.value = equipoId;
            
            // Mostrar el formulario y hacer scroll hacia él
            formulario.style.display = 'block';
            formulario.scrollIntoView({ behavior: 'smooth', block: 'start' });
            
            // Opcional: mostrar información del equipo seleccionado
            // Podrías agregar un texto informativo
        });
    });
    
    cancelarBtn.addEventListener('click', function() {
        formulario.style.display = 'none';
        equipoIdInput.value = '';
        document.getElementById('devolucionForm').reset();
    });
});
</script>

<?php include '../../includes/footer.php'; ?>