<?php
session_start();

// Verificar autenticación
if (!isset($_SESSION['user_id'])) {
    header('Location: /inventario_ti/login.php');
    exit();
}
    
// Verificar rol (1 = admin, 2 = lector)
$es_admin = ($_SESSION['user_rol'] == 1);

require_once '../../config/database.php';
include '../../includes/header.php';

// Verificar conexión
if (!$conn) {
    die("Error de conexión a la base de datos");
}

// Obtener filtro de ubicación si existe
$ubicacion_id = isset($_GET['ubicacion_id']) ? intval($_GET['ubicacion_id']) : 0;
$where = $ubicacion_id > 0 ? "WHERE e.ubicacion_id = $ubicacion_id" : "";

// Consultar equipos con JOIN para obtener nombre de ubicación
$sql = "SELECT e.*, u.nombre as ubicacion_nombre, u.codigo_ubicacion as ubicacion_codigo 
        FROM equipos e
        LEFT JOIN ubicaciones u ON e.ubicacion_id = u.id
        $where
        ORDER BY e.id DESC";
$result = $conn->query($sql);
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            
            <!-- AVISO PARA LECTORES -->
            <?php if (!$es_admin): ?>
            <div class="alert alert-info d-flex align-items-center mb-4" style="border-left: 4px solid #28a745;">
                <i class="fas fa-info-circle fa-2x me-3 text-success"></i>
                <div>
                    <strong>Modo solo lectura activo</strong>
                    <p class="mb-0">Puedes ver todos los equipos, pero no puedes agregar, editar o eliminar registros.</p>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0"><i class="fas fa-laptop me-2"></i>Listado de Equipos</h4>
                    
                    <!-- Botones AGREGAR - Solo visible para admin -->
                    <?php if ($es_admin): ?>
                    <div>
                        <a href="agregar.php" class="btn btn-primary">
                            <i class="fas fa-plus-circle me-2"></i>Agregar
                        </a>
                        <a href="registro_rapido.php" class="btn btn-success">
                            <i class="fas fa-bolt me-2"></i>Rápido
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
                
                <div class="card-body">
                    
                    <!-- BUSCADOR -->
                    <div class="mb-3">
                        <input type="text" 
                               id="buscadorEquipos" 
                               class="form-control" 
                               placeholder="Buscar por código, tipo, marca, modelo, ubicación..."
                               autocomplete="off"
                               style="max-width: 300px; border-radius: 30px; padding: 10px 15px; border: 1px solid #5a2d8c;">
                    </div>
                    
                    <!-- Mensajes de éxito/error -->
                    <?php if (isset($_GET['mensaje'])): ?>
                        <div class="alert alert-success alert-dismissible fade show">
                            <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($_GET['mensaje']); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($_GET['error'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show">
                            <i class="fas fa-exclamation-triangle me-2"></i><?php echo htmlspecialchars($_GET['error']); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Mostrar filtro activo -->
                    <?php if ($ubicacion_id > 0): ?>
                        <div class="alert alert-info">
                            <i class="fas fa-filter me-2"></i>Mostrando equipos de la ubicación seleccionada. 
                            <a href="listar.php" class="alert-link">Ver todos</a>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($result && $result->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover" id="tablaEquipos">
                                <thead>
                                    <tr>
                                        <th>Código</th>
                                        <th>Tipo</th>
                                        <th>Marca</th>
                                        <th>Modelo</th>
                                        <th>Ubicación</th>
                                        <th>Estado</th>
                                        <th class="text-center">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while($row = $result->fetch_assoc()): 
                                        // Verificar si el equipo está asignado actualmente
                                        $check_asignado = $conn->query("SELECT id, persona_id FROM asignaciones WHERE equipo_id = {$row['id']} AND fecha_devolucion IS NULL");
                                        $tiene_asignacion = $check_asignado->num_rows > 0;
                                        $persona_id = $tiene_asignacion ? $check_asignado->fetch_assoc()['persona_id'] : 0;
                                    ?>
                                    <tr>
                                        <td data-label="CÓDIGO"><?php echo htmlspecialchars($row['codigo_barras'] ?? 'N/A'); ?></td>
                                        <td data-label="TIPO"><?php echo htmlspecialchars($row['tipo_equipo'] ?? 'N/A'); ?></td>
                                        <td data-label="MARCA"><?php echo htmlspecialchars($row['marca'] ?? 'N/A'); ?></td>
                                        <td data-label="MODELO"><?php echo htmlspecialchars($row['modelo'] ?? 'N/A'); ?></td>
                                        <td data-label="UBICACIÓN">
                                            <?php 
                                            if (!empty($row['ubicacion_nombre'])) {
                                                echo htmlspecialchars($row['ubicacion_codigo'] . ' - ' . $row['ubicacion_nombre']);
                                            } else {
                                                echo '<span class="text-muted">Sin ubicación</span>';
                                            }
                                            ?>
                                        </td>
                                        <td data-label="ESTADO">
                                            <span class="badge bg-<?php 
                                                echo $row['estado'] == 'Disponible' ? 'success' : 
                                                    ($row['estado'] == 'Asignado' ? 'warning' : 'secondary'); 
                                            ?>">
                                                <?php echo $row['estado'] ?? 'N/A'; ?>
                                            </span>
                                        </td>
                                        <td data-label="ACCIONES" class="text-center">
                                            <div class="dropdown-container" style="position: relative; display: inline-block;">
                                                <button class="btn-accion" 
                                                        style="background: #5a2d8c; color: white; border: 1px solid #f3b229; border-radius: 30px; padding: 6px 15px; cursor: pointer; font-size: 0.85rem;"
                                                        onclick="abrirDropdown(this)">
                                                    <i class="fas fa-cog me-1"></i> Acciones
                                                </button>
                                                <div class="dropdown-menu-custom" style="display: none; position: absolute; top: 100%; left: 0; background: white; border: 2px solid #f3b229; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.2); padding: 8px; min-width: 220px; z-index: 10000;">
                                                    <ul style="list-style: none; margin: 0; padding: 0;">
                                                        
                                                        <!-- ACTA ENTREGA (solo si está asignado) -->
                                                        <?php if ($tiene_asignacion && $persona_id > 0): ?>
                                                        <li>
                                                            <a href="/inventario_ti/api/generar_acta_entrega.php?persona_id=<?php echo $persona_id; ?>" target="_blank"
                                                               style="display: flex; align-items: center; padding: 10px 15px; color: #333; text-decoration: none; border-radius: 10px; font-size: 0.9rem;"
                                                               onmouseover="this.style.background='#f3e9ff'" 
                                                               onmouseout="this.style.background='transparent'">
                                                                <i class="fas fa-file-pdf" style="width: 20px; margin-right: 10px; color: #5a2d8c;"></i> Acta Entrega
                                                            </a>
                                                        </li>
                                                        <?php endif; ?>
                                                        
                                                        <!-- ACTA DEVOLUCIÓN -->
                                                        <li>
                                                            <a href="/inventario_ti/api/generar_acta_devolucion.php?persona_id=<?php echo $persona_id ?: $row['id']; ?>" target="_blank"
                                                               style="display: flex; align-items: center; padding: 10px 15px; color: #333; text-decoration: none; border-radius: 10px; font-size: 0.9rem;"
                                                               onmouseover="this.style.background='#f3e9ff'" 
                                                               onmouseout="this.style.background='transparent'">
                                                                <i class="fas fa-file-pdf" style="width: 20px; margin-right: 10px; color: #5a2d8c;"></i> Acta Devolución
                                                            </a>
                                                        </li>
                                                        
                                                        <!-- DESCARGO -->
                                                        <li>
                                                            <a href="/inventario_ti/api/generar_descargo.php?persona_id=<?php echo $persona_id ?: $row['id']; ?>" target="_blank"
                                                               style="display: flex; align-items: center; padding: 10px 15px; color: #333; text-decoration: none; border-radius: 10px; font-size: 0.9rem;"
                                                               onmouseover="this.style.background='#f3e9ff'" 
                                                               onmouseout="this.style.background='transparent'">
                                                                <i class="fas fa-file-signature" style="width: 20px; margin-right: 10px; color: #5a2d8c;"></i> Descargo
                                                            </a>
                                                        </li>
                                                        
                                                        <!-- TRAZABILIDAD -->
                                                        <li>
                                                            <a href="../reportes/trazabilidad_equipo.php?id=<?php echo $row['id']; ?>"
                                                               style="display: flex; align-items: center; padding: 10px 15px; color: #333; text-decoration: none; border-radius: 10px; font-size: 0.9rem;"
                                                               onmouseover="this.style.background='#f3e9ff'" 
                                                               onmouseout="this.style.background='transparent'">
                                                                <i class="fas fa-history" style="width: 20px; margin-right: 10px; color: #5a2d8c;"></i> Trazabilidad
                                                            </a>
                                                        </li>
                                                        
                                                        <!-- QR -->
                                                        <li>
                                                            <a href="/inventario_ti/api/generar_qr_equipo.php?id=<?php echo $row['id']; ?>" download
                                                               style="display: flex; align-items: center; padding: 10px 15px; color: #333; text-decoration: none; border-radius: 10px; font-size: 0.9rem;"
                                                               onmouseover="this.style.background='#f3e9ff'" 
                                                               onmouseout="this.style.background='transparent'">
                                                                <i class="fas fa-qrcode" style="width: 20px; margin-right: 10px; color: #5a2d8c;"></i> Descargar QR
                                                            </a>
                                                        </li>
                                                        
                                                        <li style="height: 1px; background: #e0e0e0; margin: 8px 0;"></li>
                                                        
                                                        <!-- VER DETALLE -->
                                                        <li>
                                                            <a href="detalle.php?id=<?php echo $row['id']; ?>"
                                                               style="display: flex; align-items: center; padding: 10px 15px; color: #333; text-decoration: none; border-radius: 10px; font-size: 0.9rem;"
                                                               onmouseover="this.style.background='#f3e9ff'" 
                                                               onmouseout="this.style.background='transparent'">
                                                                <i class="fas fa-eye" style="width: 20px; margin-right: 10px; color: #5a2d8c;"></i> Ver Detalle
                                                            </a>
                                                        </li>
                                                        
                                                        <!-- EDITAR -->
                                                        <li>
                                                            <a href="editar.php?id=<?php echo $row['id']; ?>"
                                                               style="display: flex; align-items: center; padding: 10px 15px; color: #333; text-decoration: none; border-radius: 10px; font-size: 0.9rem;"
                                                               onmouseover="this.style.background='#f3e9ff'" 
                                                               onmouseout="this.style.background='transparent'">
                                                                <i class="fas fa-edit" style="width: 20px; margin-right: 10px; color: #5a2d8c;"></i> Editar
                                                            </a>
                                                        </li>
                                                        
                                                        <!-- ELIMINAR -->
                                                        <li>
                                                            <a href="eliminar.php?id=<?php echo $row['id']; ?>" 
                                                               onclick="return confirm('⚠️ ¿ELIMINAR?\n\nEquipo: <?php echo addslashes($row['tipo_equipo']); ?>\nCódigo: <?php echo addslashes($row['codigo_barras']); ?>\n\nSe borrará TODO su historial.\n¿Continuar?')"
                                                               style="display: flex; align-items: center; padding: 10px 15px; color: #dc3545; text-decoration: none; border-radius: 10px; font-size: 0.9rem;"
                                                               onmouseover="this.style.background='#ffeeee'" 
                                                               onmouseout="this.style.background='transparent'">
                                                                <i class="fas fa-trash-alt" style="width: 20px; margin-right: 10px; color: #dc3545;"></i> Eliminar
                                                            </a>
                                                        </li>
                                                    </ul>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Total de registros -->
                        <div class="mt-3 text-muted">
                            <i class="fas fa-info-circle me-1"></i>
                            Total de equipos: <strong><?php echo $result->num_rows; ?></strong>
                            <?php if (!$es_admin): ?>
                                <span class="ms-3 badge bg-success">Modo lectura</span>
                            <?php endif; ?>
                        </div>
                        
                    <?php else: ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>No hay equipos registrados.
                            <?php if ($es_admin): ?>
                                <a href="agregar.php" class="alert-link ms-2">Agrega el primero</a>
                            <?php else: ?>
                                <span class="d-block mt-2">Contacta al administrador para agregar equipos.</span>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- SCRIPT PARA EL BUSCADOR -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const buscador = document.getElementById('buscadorEquipos');
    const tabla = document.getElementById('tablaEquipos');
    
    if (!buscador || !tabla) return;
    
    const filas = tabla.getElementsByTagName('tbody')[0].getElementsByTagName('tr');

    buscador.addEventListener('keyup', function() {
        const texto = buscador.value.toLowerCase().trim();

        for (let fila of filas) {
            let coincide = false;
            const celdas = fila.getElementsByTagName('td');
            
            for (let i = 0; i < celdas.length - 1; i++) {
                const contenido = celdas[i].textContent.toLowerCase();
                if (contenido.includes(texto)) {
                    coincide = true;
                    break;
                }
            }
            
            fila.style.display = coincide ? '' : 'none';
        }
    });
});
</script>

<!-- SCRIPT PARA EL DROPDOWN -->
<script>
// Variable para controlar el dropdown abierto
var dropdownAbierto = null;

function abrirDropdown(boton) {
    var dropdown = boton.nextElementSibling;
    
    // Si hay un dropdown abierto y es diferente, cerrarlo
    if (dropdownAbierto && dropdownAbierto !== dropdown) {
        dropdownAbierto.style.display = 'none';
    }
    
    // Si el dropdown actual está visible, ocultarlo
    if (dropdown.style.display === 'block') {
        dropdown.style.display = 'none';
        dropdownAbierto = null;
    } else {
        // Ocultar todos primero
        var todos = document.querySelectorAll('.dropdown-menu-custom');
        for (var i = 0; i < todos.length; i++) {
            todos[i].style.display = 'none';
        }
        
        // Mostrar este
        dropdown.style.display = 'block';
        dropdownAbierto = dropdown;
    }
    
    // Evitar que el clic se propague
    event.stopPropagation();
}

// Cerrar dropdown al hacer clic fuera
document.addEventListener('click', function() {
    if (dropdownAbierto) {
        dropdownAbierto.style.display = 'none';
        dropdownAbierto = null;
    }
});

// Evitar que el dropdown se cierre al hacer clic dentro
document.querySelectorAll('.dropdown-menu-custom').forEach(function(dropdown) {
    dropdown.addEventListener('click', function(event) {
        event.stopPropagation();
    });
});
</script>

<?php include '../../includes/footer.php'; ?>