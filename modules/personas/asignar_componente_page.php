<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_rol'] != 1) {
    header('Location: /inventario_ti/login.php');
    exit();
}

require_once '../../config/database.php';
include '../../includes/header.php';

$persona_id = intval($_GET['persona_id'] ?? 0);
if (!$persona_id) {
    header('Location: listar.php');
    exit();
}

// Obtener datos de la persona
$persona = $conn->query("SELECT * FROM personas WHERE id = $persona_id")->fetch_assoc();

// ============================================
// OBTENER TODOS LOS COMPONENTES (no solo disponibles)
// ============================================
$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';

$sql_componentes = "SELECT c.*, 
                    CASE WHEN EXISTS (
                        SELECT 1 FROM movimientos_componentes mc 
                        WHERE mc.componente_id = c.id 
                        AND mc.tipo_movimiento='ASIGNACION' 
                        AND NOT EXISTS (
                            SELECT 1 FROM movimientos_componentes mc2 
                            WHERE mc2.componente_id = mc.componente_id 
                            AND mc2.tipo_movimiento='DEVOLUCION' 
                            AND mc2.fecha_movimiento > mc.fecha_movimiento
                        )
                    ) THEN 0 ELSE 1 END as disponible
                    FROM componentes c";

if (!empty($search)) {
    $sql_componentes .= " WHERE (c.tipo LIKE '%$search%' OR c.nombre_componente LIKE '%$search%' OR c.marca LIKE '%$search%' OR c.modelo LIKE '%$search%' OR c.numero_serie LIKE '%$search%')";
}

$sql_componentes .= " ORDER BY c.tipo, c.nombre_componente";
$componentes = $conn->query($sql_componentes);
$total_componentes = $componentes->num_rows;
?>

<style>
:root {
    --tesa-purple: #5a2d8c;
    --tesa-purple-light: #f3e9ff;
    --tesa-purple-dark: #3d1e5e;
    --tesa-gold: #f3b229;
}

/* Estilos personalizados con colores institucionales */
.componente-item {
    padding: 12px 15px;
    margin: 5px 0;
    border: 2px solid #e0e0e0;
    border-radius: 10px;
    cursor: pointer;
    transition: all 0.3s;
    background: white;
}
.componente-item:hover {
    border-color: var(--tesa-purple);
    background: var(--tesa-purple-light);
    transform: translateX(5px);
}
.componente-item.selected {
    border-color: var(--tesa-purple);
    background: #e1d5f0;
    border-width: 3px;
}
.componente-item.disponible {
    border-left: 5px solid #28a745;
}
.componente-item.no-disponible {
    border-left: 5px solid #dc3545;
    opacity: 0.7;
    background: #f8f9fa;
    cursor: not-allowed;
}
.componente-tipo {
    font-size: 0.9rem;
    color: var(--tesa-purple);
    font-weight: 600;
    text-transform: uppercase;
}
.componente-nombre {
    font-size: 1.1rem;
    font-weight: 600;
    color: #333;
}
.componente-detalle {
    font-size: 0.9rem;
    color: #666;
}
.componente-serie {
    font-family: monospace;
    background: #f0f0f0;
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 0.8rem;
}
.badge-disponible {
    background: #28a745;
    color: white;
    padding: 3px 10px;
    border-radius: 20px;
    font-size: 0.7rem;
    font-weight: 600;
}
.badge-no-disponible {
    background: #dc3545;
    color: white;
    padding: 3px 10px;
    border-radius: 20px;
    font-size: 0.7rem;
    font-weight: 600;
}
.search-box {
    position: relative;
    margin-bottom: 20px;
}
.search-box input {
    padding-left: 40px;
    border-radius: 30px;
    border: 2px solid var(--tesa-purple);
}
.search-box input:focus {
    box-shadow: 0 0 0 3px rgba(90, 45, 140, 0.2);
    border-color: var(--tesa-purple);
}
.search-box i {
    position: absolute;
    left: 15px;
    top: 12px;
    color: var(--tesa-purple);
    z-index: 10;
}
.componentes-container {
    max-height: 500px;
    overflow-y: auto;
    padding-right: 5px;
    margin-bottom: 20px;
    border: 1px solid #e0e0e0;
    border-radius: 10px;
    padding: 10px;
    background: #f8f9fa;
}
.componentes-container::-webkit-scrollbar {
    width: 8px;
}
.componentes-container::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 10px;
}
.componentes-container::-webkit-scrollbar-thumb {
    background: var(--tesa-purple);
    border-radius: 10px;
}
.card {
    max-width: 900px;
    margin: 0 auto;
    border: none;
    box-shadow: 0 10px 30px rgba(90, 45, 140, 0.1);
}
.card-header {
    background: linear-gradient(135deg, var(--tesa-purple) 0%, #6f42c1 100%);
    border-bottom: 3px solid var(--tesa-gold);
}
.btn-primary {
    background: var(--tesa-purple);
    border: none;
}
.btn-primary:hover {
    background: var(--tesa-purple-dark);
}
.btn-primary:disabled {
    background: #b3a0cc;
}
.text-purple {
    color: var(--tesa-purple);
}
.bg-purple-light {
    background: var(--tesa-purple-light);
}
.total-badge {
    background: var(--tesa-gold);
    color: var(--tesa-purple-dark);
    font-weight: 600;
    padding: 5px 15px;
    border-radius: 30px;
}
</style>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-lg-8 col-md-10 mx-auto">
            <div class="card shadow">
                <div class="card-header text-white py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h4 class="mb-0"><i class="fas fa-microchip me-2"></i>Asignar Componente</h4>
                            <p class="mb-0 mt-1">
                                <i class="fas fa-user me-2"></i>
                                <strong><?php echo $persona['nombres']; ?></strong> 
                                <span class="text-white-50 ms-2">(<?php echo $persona['cedula']; ?>)</span>
                            </p>
                        </div>
                        <span class="total-badge">
                            <i class="fas fa-boxes me-1"></i>
                            Total: <?php echo $total_componentes; ?>
                        </span>
                    </div>
                </div>
                
                <div class="card-body">
                    
                    <div class="alert alert-info d-flex align-items-center py-2" style="border-left: 4px solid var(--tesa-purple);">
                        <i class="fas fa-info-circle fa-lg me-2 text-purple"></i>
                        <span>Selecciona un componente de la lista. Los componentes <span class="badge-disponible">DISPONIBLES</span> pueden asignarse.</span>
                    </div>
                    
                    <form method="POST" action="asignar_componente.php" id="formAsignacion">
                        <input type="hidden" name="persona_id" value="<?php echo $persona_id; ?>">
                        <input type="hidden" name="componente_id" id="selected_componente_id" value="">
                        
                        <!-- Buscador -->
                        <div class="search-box">
                            <i class="fas fa-search"></i>
                            <input type="text" class="form-control" id="buscarComponente" 
                                   placeholder="🔍 Buscar componente por tipo, nombre, marca, modelo o serie...">
                        </div>
                        
                        <!-- Lista de componentes -->
                        <div class="componentes-container" id="componentesLista">
                            <?php 
                            $componentes->data_seek(0);
                            $hay_disponibles = false;
                            while($c = $componentes->fetch_assoc()): 
                                $disponible = $c['disponible'] == 1;
                                if ($disponible) $hay_disponibles = true;
                            ?>
                            <div class="componente-item <?php echo $disponible ? 'disponible' : 'no-disponible'; ?>" 
                                 onclick="<?php echo $disponible ? 'seleccionarComponente(' . $c['id'] . ', this)' : ''; ?>"
                                 data-disponible="<?php echo $disponible ? 'si' : 'no'; ?>">
                                
                                <div class="d-flex justify-content-between align-items-start">
                                    <div style="flex: 1;">
                                        <div class="d-flex align-items-center gap-2 mb-1">
                                            <span class="componente-tipo"><?php echo $c['tipo']; ?></span>
                                            <?php if ($disponible): ?>
                                                <span class="badge-disponible">DISPONIBLE</span>
                                            <?php else: ?>
                                                <span class="badge-no-disponible">ASIGNADO</span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="componente-nombre"><?php echo $c['nombre_componente']; ?></div>
                                        <div class="componente-detalle">
                                            <?php echo $c['marca'] . ' ' . $c['modelo']; ?>
                                        </div>
                                    </div>
                                    <?php if (!empty($c['numero_serie'])): ?>
                                        <span class="componente-serie">Serie: <?php echo $c['numero_serie']; ?></span>
                                    <?php endif; ?>
                                </div>
                                
                                <?php if (!empty($c['especificaciones'])): ?>
                                    <div class="mt-2 small text-muted">
                                        <i class="fas fa-info-circle me-1 text-purple"></i>
                                        <?php echo substr($c['especificaciones'], 0, 100) . (strlen($c['especificaciones']) > 100 ? '...' : ''); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <?php endwhile; ?>
                            
                            <?php if ($total_componentes == 0): ?>
                                <div class="text-center py-4">
                                    <i class="fas fa-microchip fa-3x text-muted mb-2"></i>
                                    <p class="text-muted">No hay componentes registrados en el sistema</p>
                                    <a href="/inventario_ti/modules/componentes/agregar.php" class="btn btn-primary btn-sm">
                                        <i class="fas fa-plus-circle me-1"></i>Agregar Componente
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="mb-3 mt-3">
                            <label class="form-label fw-bold text-purple">
                                <i class="fas fa-pen me-1"></i>Observaciones
                            </label>
                            <textarea name="observaciones" class="form-control" rows="2" 
                                      placeholder="Notas adicionales sobre la asignación (opcional)"></textarea>
                        </div>
                        
                        <div class="text-center mt-4">
                            <button type="submit" class="btn btn-primary btn-lg px-4" id="btnSubmit" disabled>
                                <i class="fas fa-check me-2"></i>Confirmar Asignación
                            </button>
                            <a href="detalle.php?id=<?php echo $persona_id; ?>" class="btn btn-secondary btn-lg px-4">
                                <i class="fas fa-times me-2"></i>Cancelar
                            </a>
                        </div>
                    </form>
                    
                    <?php if (!$hay_disponibles && $total_componentes > 0): ?>
                        <div class="alert alert-warning mt-3 text-center">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            No hay componentes disponibles para asignar. Todos están actualmente asignados.
                        </div>
                    <?php endif; ?>
                    
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let componenteSeleccionado = null;

function seleccionarComponente(id, elemento) {
    // Quitar selección anterior
    if (componenteSeleccionado) {
        componenteSeleccionado.classList.remove('selected');
    }
    
    // Marcar nuevo elemento
    elemento.classList.add('selected');
    componenteSeleccionado = elemento;
    
    // Actualizar campo oculto
    document.getElementById('selected_componente_id').value = id;
    
    // Habilitar botón
    document.getElementById('btnSubmit').disabled = false;
}

// Buscador en tiempo real
document.getElementById('buscarComponente').addEventListener('keyup', function() {
    let texto = this.value.toLowerCase();
    let items = document.querySelectorAll('.componente-item');
    
    items.forEach(item => {
        let textoItem = item.textContent.toLowerCase();
        if (textoItem.includes(texto)) {
            item.style.display = 'block';
        } else {
            item.style.display = 'none';
        }
    });
});

// Validar antes de enviar
document.getElementById('formAsignacion').addEventListener('submit', function(e) {
    if (!document.getElementById('selected_componente_id').value) {
        e.preventDefault();
        alert('Por favor seleccione un componente disponible');
    }
});
</script>

<?php include '../../includes/footer.php'; ?>