<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once '../../config/permisos.php';
verificarSesion();
requiereAdmin();

require_once '../../config/database.php';
require_once '../../config/listas.php';
include '../../includes/header.php';

$mensaje = '';
$error = '';

// Obtener persona_id de la URL si viene
$persona_id_seleccionada = isset($_GET['persona_id']) ? intval($_GET['persona_id']) : 0;

// Obtener lista de personas
$personas = $conn->query("SELECT id, nombres FROM personas ORDER BY nombres");

// Obtener equipos en bodega (disponibles)
$equipos_bodega = $conn->query("SELECT id, codigo_barras, tipo_equipo, marca, modelo FROM equipos WHERE estado = 'Disponible' ORDER BY codigo_barras");

// ============================================
// PROCESAR EL FORMULARIO
// ============================================
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $persona_id = intval($_POST['persona_id'] ?? 0);
    $tipo_asignacion = $_POST['tipo_asignacion'] ?? 'nuevo';
    
    if ($persona_id == 0) {
        $error = "❌ Debe seleccionar una persona";
    } else {
        if ($tipo_asignacion == 'nuevo') {
            // PROCESAR EQUIPO NUEVO
            $tipo_equipo = $conn->real_escape_string($_POST['tipo_equipo'] ?? '');
            $marca = $conn->real_escape_string($_POST['marca'] ?? '');
            $modelo = $conn->real_escape_string($_POST['modelo'] ?? '');
            $serie = $conn->real_escape_string($_POST['numero_serie'] ?? '');
            $codigo_barras = $conn->real_escape_string($_POST['codigo_barras'] ?? '');
            $especificaciones = $conn->real_escape_string($_POST['especificaciones'] ?? '');

            if (empty($tipo_equipo)) {
                $error = "❌ El tipo de equipo es obligatorio";
            } else {
                if (empty($codigo_barras)) {
                    $result = $conn->query("SELECT MAX(id) as max_id FROM equipos");
                    $row = $result->fetch_assoc();
                    $next_id = ($row['max_id'] ?? 0) + 1;
                    $codigo_barras = 'PRO-' . str_pad($next_id, 6, '0', STR_PAD_LEFT);
                }
                
                $sql_equipo = "INSERT INTO equipos (codigo_barras, tipo_equipo, marca, modelo, numero_serie, especificaciones, estado) 
                               VALUES ('$codigo_barras', '$tipo_equipo', '$marca', '$modelo', '$serie', '$especificaciones', 'Asignado')";
                
                if ($conn->query($sql_equipo)) {
                    $equipo_id = $conn->insert_id;
                    $success_assignment = true;
                } else {
                    $error = "❌ Error al guardar equipo: " . $conn->error;
                    $success_assignment = false;
                }
            }
        } else {
            // PROCESAR EQUIPO DE BODEGA
            $equipo_id = intval($_POST['equipo_bodega_id'] ?? 0);
            if ($equipo_id == 0) {
                $error = "❌ Debe seleccionar un equipo de la bodega";
                $success_assignment = false;
            } else {
                // Actualizar estado del equipo a Asignado
                if ($conn->query("UPDATE equipos SET estado = 'Asignado' WHERE id = $equipo_id")) {
                    $success_assignment = true;
                } else {
                    $error = "❌ Error al actualizar estado del equipo: " . $conn->error;
                    $success_assignment = false;
                }
            }
        }

        // Si el equipo se creó o se seleccionó correctamente, crear la asignación
        if (isset($success_assignment) && $success_assignment) {
            $sql_asignacion = "INSERT INTO asignaciones (equipo_id, persona_id, fecha_asignacion) 
                              VALUES ($equipo_id, $persona_id, NOW())";
            
            if ($conn->query($sql_asignacion)) {
                $conn->query("INSERT INTO movimientos (equipo_id, persona_id, tipo_movimiento, fecha_movimiento) 
                             VALUES ($equipo_id, $persona_id, 'ASIGNACION', NOW())");
                
                $mensaje = "✅ Equipo asignado correctamente.";
                // Recargar equipos de bodega
                $equipos_bodega = $conn->query("SELECT id, codigo_barras, tipo_equipo, marca, modelo FROM equipos WHERE estado = 'Disponible' ORDER BY codigo_barras");
            } else {
                $error = "❌ Error al asignar: " . $conn->error;
            }
        }
    }
}
?>

<style>
    /* Estilos mínimos necesarios */
    .opcion-btn {
        background: #f8f9fa;
        border: 2px solid #5a2d8c;
        border-radius: 10px;
        padding: 20px;
        text-align: center;
        cursor: pointer;
        transition: all 0.3s;
        margin-bottom: 20px;
    }
    .opcion-btn:hover {
        background: #5a2d8c;
        color: white;
    }
    .opcion-btn.active {
        background: #5a2d8c;
        color: white;
    }
</style>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0"><i class="fas fa-plus-circle me-2"></i>Asignar Equipo a Persona</h4>
                </div>
                <div class="card-body">
                    
                    <?php if ($mensaje): ?>
                        <div class="alert alert-success"><?php echo $mensaje; ?></div>
                    <?php endif; ?>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <form method="POST" action="" id="formEquipo">
    
    <div class="row mb-4">
        <div class="col-md-6">
            <label class="form-label">Persona *</label>
            <select name="persona_id" class="form-control" required>
                <option value="">-- Seleccione una persona --</option>
                <?php 
                $personas->data_seek(0);
                while($p = $personas->fetch_assoc()): 
                ?>
                    <option value="<?php echo $p['id']; ?>" <?php echo ($persona_id_seleccionada == $p['id']) ? 'selected' : ''; ?>>
                        <?php echo $p['nombres']; ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>
    </div>
    
    <!-- ======================================== -->
    <!-- OPCIONES DE ASIGNACIÓN -->
    <!-- ======================================== -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="opcion-btn" id="opcionNuevo" onclick="seleccionarOpcion('nuevo')">
                <i class="fas fa-plus-circle fa-3x mb-2"></i>
                <h5>Agregar Equipo Nuevo</h5>
                <p class="mb-0">Crear un nuevo equipo y asignarlo</p>
            </div>
        </div>
        <div class="col-md-6">
            <div class="opcion-btn" id="opcionBodega" onclick="seleccionarOpcion('bodega')">
                <i class="fas fa-warehouse fa-3x mb-2"></i>
                <h5>Asignar desde Bodega</h5>
                <p class="mb-0">Usar un equipo disponible en inventario</p>
            </div>
        </div>
    </div>
    
    <input type="hidden" name="tipo_asignacion" id="tipo_asignacion" value="nuevo">
    
    <!-- ======================================== -->
    <!-- FORMULARIO PARA EQUIPO NUEVO -->
    <!-- ======================================== -->
    <div id="formNuevo">
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Tipo de Equipo *</label>
                <select name="tipo_equipo" id="tipo_equipo" class="form-control" required>
                    <option value="">-- Seleccione --</option>
                    <?php foreach($tipos_equipos as $valor => $etiqueta): ?>
                        <option value="<?php echo $valor; ?>"><?php echo $etiqueta; ?></option>
                    <?php endforeach; ?>
                </select>
                <small class="text-muted">📋 <?php echo count($tipos_equipos); ?> tipos disponibles</small>
            </div>
            
            <div class="col-md-6 mb-3">
                <label class="form-label">Marca</label>
                <input type="text" name="marca" class="form-control" placeholder="Ej: HP, Dell, Logitech">
            </div>
            
            <div class="col-md-4 mb-3">
                <label class="form-label">Modelo</label>
                <input type="text" name="modelo" class="form-control" placeholder="Ej: Pavilion, Latitude">
            </div>
            
            <div class="col-md-4 mb-3">
                <label class="form-label">Código de Barras</label>
                <input type="text" name="codigo_barras" class="form-control" placeholder="Automático">
            </div>
            
            <div class="col-md-4 mb-3">
                <label class="form-label">Número de Serie</label>
                <input type="text" name="numero_serie" class="form-control" placeholder="Serie del fabricante">
            </div>
            
            <div class="col-md-12 mb-3">
                <label class="form-label">Especificaciones</label>
                <textarea name="especificaciones" class="form-control" rows="2" placeholder="RAM, disco, procesador, etc."></textarea>
            </div>
        </div>
    </div>
    
    <!-- ======================================== -->
    <!-- FORMULARIO PARA ASIGNAR DESDE BODEGA -->
    <!-- ======================================== -->
    <div id="formBodega" style="display: none;">
        <div class="row">
            <div class="col-md-12 mb-3">
                <label class="form-label">Seleccionar equipo de bodega *</label>
                <select name="equipo_bodega_id" id="equipo_bodega_id" class="form-control">
                    <option value="">-- Seleccione un equipo disponible --</option>
                    <?php 
                    $equipos_bodega->data_seek(0);
                    if ($equipos_bodega->num_rows > 0): 
                        while($eq = $equipos_bodega->fetch_assoc()): 
                    ?>
                        <option value="<?php echo $eq['id']; ?>">
                            <?php echo $eq['codigo_barras'] . ' - ' . $eq['tipo_equipo'] . ' ' . $eq['marca'] . ' ' . $eq['modelo']; ?>
                        </option>
                    <?php 
                        endwhile; 
                    endif; 
                    ?>
                </select>
            </div>
        </div>
    </div>
    
    <!-- ======================================== -->
    <!-- BOTÓN DE ENVÍO -->
    <!-- ======================================== -->
    <div class="text-center mt-4">
        <button type="submit" name="submit" class="btn btn-success btn-lg px-5">
            <i class="fas fa-save me-2"></i>Asignar Equipo
        </button>
        <a href="/inventario_ti/modules/personas/listar.php" class="btn btn-secondary btn-lg px-5">
            <i class="fas fa-arrow-left me-2"></i>Cancelar
        </a>
    </div>
</form>

                </div>
            </div>
        </div>
    </div>
</div>

<script>
function seleccionarOpcion(opcion) {
    const btnNuevo = document.getElementById('opcionNuevo');
    const btnBodega = document.getElementById('opcionBodega');
    const formNuevo = document.getElementById('formNuevo');
    const formBodega = document.getElementById('formBodega');
    const tipoAsignacion = document.getElementById('tipo_asignacion');
    
    const inputTipoEquipo = document.getElementById('tipo_equipo');
    const inputEquipoBodega = document.getElementById('equipo_bodega_id');

    if (opcion === 'nuevo') {
        btnNuevo.classList.add('active');
        btnBodega.classList.remove('active');
        formNuevo.style.display = 'block';
        formBodega.style.display = 'none';
        tipoAsignacion.value = 'nuevo';
        
        inputTipoEquipo.required = true;
        inputEquipoBodega.required = false;
    } else {
        btnNuevo.classList.remove('active');
        btnBodega.classList.add('active');
        formNuevo.style.display = 'none';
        formBodega.style.display = 'block';
        tipoAsignacion.value = 'bodega';
        
        inputTipoEquipo.required = false;
        inputEquipoBodega.required = true;
    }
}

// Inicializar con la opción "nuevo" activa
document.addEventListener('DOMContentLoaded', function() {
    seleccionarOpcion('nuevo');
});
</script>

<?php include '../../includes/footer.php'; ?>