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
$where_ubicacion = $ubicacion_id > 0 ? "AND e.ubicacion_id = $ubicacion_id" : "";
// Obtener filtro de estado
$estado_filtro = isset($_GET['estado']) ? $_GET['estado'] : 'todos';
$where_estado = '';
if ($estado_filtro != 'todos') {
    $where_estado = "AND e.estado = '$estado_filtro'";
}

// ============================================
// CONSULTA PRINCIPAL (excluye eliminados)
// ============================================
$sql = "SELECT e.*, u.nombre as ubicacion_nombre, u.codigo_ubicacion as ubicacion_codigo 
        FROM equipos e
        LEFT JOIN ubicaciones u ON e.ubicacion_id = u.id
        WHERE e.fecha_eliminacion IS NULL
        $where_ubicacion
        $where_estado
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
            
            <div class="card shadow-sm border-0">
                <div class="card-header d-flex justify-content-between align-items-center" style="background: rgba(139, 92, 246, 0.2) !important; border-bottom: 2px solid var(--c-gold) !important;">
                    <h4 class="mb-0 text-white"><i class="fas fa-laptop me-2 text-warning"></i>Listado de Equipos</h4>
                    
                    <!-- Botones AGREGAR - Solo visible para admin -->
                    <?php if ($es_admin): ?>
                    <div>
                        <a href="agregar.php" class="btn btn-primary">
                            <i class="fas fa-plus-circle me-2"></i>Agregar
                        </a>
                        <a href="registro_rapido.php" class="btn btn-success">
                            <i class="fas fa-bolt me-2"></i>Rápido
                        </a>
                        <!-- 👇 NUEVO BOTÓN DE BAJA MASIVA -->
                        <a href="#" class="btn btn-danger" id="btnBajaMasiva" onclick="procesarBajaMasiva()">
                            <i class="fas fa-trash-alt me-2"></i>Baja Masiva
                        </a>
                        <!-- 👆 FIN NUEVO BOTÓN -->
                        <a href="listar_eliminados.php" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-trash-restore me-1"></i>Ver eliminados
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
                    <!-- PESTAÑAS DE FILTRO POR ESTADO -->
<div class="mb-3">
    <div class="btn-group w-100" role="group">
        <a href="listar.php?estado=todos" class="btn btn-outline-light <?php echo (!isset($_GET['estado']) || $_GET['estado'] == 'todos') ? 'active' : ''; ?>">
            <i class="fas fa-list me-1"></i>Todos
        </a>
        <a href="listar.php?estado=Disponible" class="btn btn-outline-success <?php echo (isset($_GET['estado']) && $_GET['estado'] == 'Disponible') ? 'active' : ''; ?>">
            <i class="fas fa-check-circle me-1"></i>Disponibles
        </a>
        <a href="listar.php?estado=Asignado" class="btn btn-outline-warning <?php echo (isset($_GET['estado']) && $_GET['estado'] == 'Asignado') ? 'active' : ''; ?>">
            <i class="fas fa-user-check me-1"></i>Asignados
        </a>
        <a href="listar.php?estado=En mantenimiento" class="btn btn-outline-info <?php echo (isset($_GET['estado']) && $_GET['estado'] == 'En mantenimiento') ? 'active' : ''; ?>">
            <i class="fas fa-tools me-1"></i>En mantenimiento
        </a>
        <a href="listar.php?estado=Baja" class="btn btn-outline-danger <?php echo (isset($_GET['estado']) && $_GET['estado'] == 'Baja') ? 'active' : ''; ?>">
            <i class="fas fa-trash-alt me-1"></i>Dados de baja
        </a>
    </div>
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
                                        <!-- 👇 NUEVA COLUMNA DE SELECCIÓN -->
                                        <th style="width: 40px;"><input type="checkbox" id="seleccionarTodos"></th>
                                        <!-- 👆 FIN NUEVA COLUMNA -->
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
                                        <!-- 👇 CHECKBOX POR FILA -->
                                        <td data-label="SELECCIONAR" style="width: 40px; text-align: center;">
                                            <input type="checkbox" class="checkbox-equipo" value="<?php echo $row['id']; ?>">
                                        </td>
                                        <!-- 👆 FIN CHECKBOX -->
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
                                            <button
                                                type="button"
                                                class="btn btn-sm btn-primary btn-abrir-acciones"
                                                data-equipo-label="<?php echo htmlspecialchars(($row['tipo_equipo'] ?? 'Equipo') . ' - ' . ($row['codigo_barras'] ?? 'N/A'), ENT_QUOTES, 'UTF-8'); ?>"
                                                data-template-id="acciones-template-<?php echo (int)$row['id']; ?>"
                                                data-bs-toggle="modal"
                                                data-bs-target="#accionesModalGlobal">
                                                <i class="fas fa-cog"></i> Acciones
                                            </button>

                                            <div id="acciones-template-<?php echo (int)$row['id']; ?>" class="d-none">
                                                <div class="list-group">
                                                    <?php if ($tiene_asignacion && $persona_id > 0): ?>
                                                        <a href="/inventario_ti/api/generar_acta_entrega.php?persona_id=<?php echo $persona_id; ?>" target="_blank" class="list-group-item list-group-item-action">
                                                            <i class="fas fa-file-pdf me-2" style="color: #5a2d8c;"></i> Acta Entrega
                                                        </a>
                                                    <?php endif; ?>
                                                    
                                                    <a href="/inventario_ti/api/generar_acta_devolucion.php?persona_id=<?php echo $persona_id ?: $row['id']; ?>" target="_blank" class="list-group-item list-group-item-action">
                                                        <i class="fas fa-file-pdf me-2" style="color: #5a2d8c;"></i> Acta Devolución
                                                    </a>
                                                    
                                                    <a href="/inventario_ti/api/generar_descargo.php?persona_id=<?php echo $persona_id ?: $row['id']; ?>" target="_blank" class="list-group-item list-group-item-action">
                                                        <i class="fas fa-file-signature me-2" style="color: #5a2d8c;"></i> Descargo
                                                    </a>

                                                    <!-- ACTA DE BAJA -->
                                                    <a href="/inventario_ti/api/generar_acta_baja.php?equipo_id=<?php echo $row['id']; ?>" target="_blank" class="list-group-item list-group-item-action">
                                                    <i class="fas fa-trash-alt me-2" style="color: #dc3545;"></i> Acta de Baja
                                                    </a>
                                                    
                                                    <a href="../reportes/trazabilidad_equipo.php?id=<?php echo $row['id']; ?>" class="list-group-item list-group-item-action">
                                                        <i class="fas fa-history me-2" style="color: #5a2d8c;"></i> Trazabilidad
                                                    </a>
                                                    
                                                    <a href="/inventario_ti/api/generar_qr_equipo.php?id=<?php echo $row['id']; ?>" download class="list-group-item list-group-item-action">
                                                        <i class="fas fa-qrcode me-2" style="color: #5a2d8c;"></i> Descargar QR
                                                    </a>
                                                    
                                                    <div class="list-group-item list-group-item-action disabled" style="background: #f8f9fa;">
                                                        <hr class="my-1">
                                                    </div>
                                                    
                                                    <a href="detalle.php?id=<?php echo $row['id']; ?>" class="list-group-item list-group-item-action">
                                                        <i class="fas fa-eye me-2" style="color: #5a2d8c;"></i> Ver Detalle
                                                    </a>
                                                    
                                                    <a href="editar.php?id=<?php echo $row['id']; ?>" class="list-group-item list-group-item-action">
                                                        <i class="fas fa-edit me-2" style="color: #5a2d8c;"></i> Editar
                                                    </a>
                                                    
                                                    <?php if (!$tiene_asignacion): ?>
                                                        <a href="eliminar.php?id=<?php echo $row['id']; ?>" class="list-group-item list-group-item-action text-danger" onclick="return confirm('⚠️ ¿ELIMINAR?\n\nEquipo: <?php echo addslashes($row['tipo_equipo']); ?>\nCódigo: <?php echo addslashes($row['codigo_barras']); ?>\n\nSe marcará como eliminado.\n¿Continuar?')">
                                                            <i class="fas fa-trash-alt me-2 text-danger"></i> Eliminar
                                                        </a>
                                                    <?php else: ?>
                                                        <span class="list-group-item list-group-item-action disabled text-muted">
                                                            <i class="fas fa-ban me-2"></i> No se puede eliminar (prestado)
                                                        </span>
                                                    <?php endif; ?>
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
                            Total de equipos activos: <strong><?php echo $result->num_rows; ?></strong>
                            <?php if (!$es_admin): ?>
                                <span class="ms-3 badge bg-success">Modo lectura</span>
                            <?php endif; ?>
                        </div>
                        
                    <?php else: ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>No hay equipos activos registrados.
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

<div class="modal fade" id="accionesModalGlobal" tabindex="-1" aria-labelledby="accionesModalGlobalLabel" aria-hidden="true" data-bs-backdrop="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="accionesModalGlobalLabel">Acciones</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="accionesModalGlobalBody"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<!-- SCRIPT PARA EL BUSCADOR Y FUNCIONALIDADES -->
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

    const modalTitle = document.getElementById('accionesModalGlobalLabel');
    const modalBody = document.getElementById('accionesModalGlobalBody');
    const botonesAcciones = document.querySelectorAll('.btn-abrir-acciones');
    const accionesModal = document.getElementById('accionesModalGlobal');

    if (accionesModal) {
        accionesModal.addEventListener('show.bs.modal', function() {
            document.querySelectorAll('.modal-backdrop').forEach(function(el) { el.remove(); });
        });
    }

    botonesAcciones.forEach(function(btn) {
        btn.addEventListener('click', function() {
            const equipoLabel = btn.getAttribute('data-equipo-label') || 'Equipo';
            const templateId = btn.getAttribute('data-template-id');
            const template = templateId ? document.getElementById(templateId) : null;

            modalTitle.textContent = 'Acciones para ' + equipoLabel;
            modalBody.innerHTML = template ? template.innerHTML : '<div class="alert alert-warning mb-0">No se pudieron cargar las acciones.</div>';
        });
    });

    // 👇 FUNCIÓN PARA SELECCIONAR TODOS LOS CHECKBOXES
    const selectAll = document.getElementById('seleccionarTodos');
    if (selectAll) {
        selectAll.addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('.checkbox-equipo');
            checkboxes.forEach(function(checkbox) {
                checkbox.checked = selectAll.checked;
            });
        });
    }
});

// 👇 FUNCIÓN PARA PROCESAR BAJA MASIVA
function procesarBajaMasiva() {
    const checkboxes = document.querySelectorAll('.checkbox-equipo:checked');
    
    if (checkboxes.length === 0) {
        alert('❌ Debe seleccionar al menos un equipo');
        return;
    }
    
    const ids = [];
    checkboxes.forEach(function(checkbox) {
        ids.push(checkbox.value);
    });
    
    const confirmacion = confirm(`¿Está seguro de dar de baja ${ids.length} equipo(s)?`);
    if (!confirmacion) return;
    
    window.location.href = `baja_masiva.php?ids=${ids.join(',')}`;
}
</script>

<?php include '../../includes/footer.php'; ?>