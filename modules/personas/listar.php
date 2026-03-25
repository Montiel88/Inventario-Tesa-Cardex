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

// Consultar personas NO eliminadas
$sql = "SELECT * FROM personas WHERE fecha_eliminacion IS NULL ORDER BY nombres";
$result = $conn->query($sql);

// Verificar si la consulta fue exitosa
if (!$result) {
    die("Error en la consulta: " . $conn->error);
}

// Contar total de personas eliminadas (opcional para mostrar enlace)
$total_eliminadas = 0;
$result_elim = $conn->query("SELECT COUNT(*) as total FROM personas WHERE fecha_eliminacion IS NOT NULL");
if ($result_elim) {
    $total_eliminadas = $result_elim->fetch_assoc()['total'];
}
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
                    <p class="mb-0">Puedes ver todas las personas, pero no puedes agregar, editar o eliminar registros.</p>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0"><i class="fas fa-users me-2"></i>Listado de Personas</h4>
                    
                    <!-- Botón AGREGAR - Solo visible para admin -->
                    <?php if ($es_admin): ?>
                    <a href="agregar.php" class="btn btn-primary">
                        <i class="fas fa-user-plus me-2"></i>Agregar Nueva Persona
                    </a>
                    <?php endif; ?>
                </div>
                
                <div class="card-body">
                    
                    <!-- ============================================ -->
                    <!-- BUSCADOR CON LUPA DESPLEGABLE (HOVER) -->
                    <!-- ============================================ -->
                    <div class="buscador-container">
                        <div class="search-wrapper">
                            <i class="fas fa-search search-icon"></i>
                            <input type="text" class="search-input" id="buscadorPersonas" placeholder="Buscar por cédula, nombre, email, cargo...">
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

                    <!-- Enlace para ver eliminados (solo admin) -->
                    <?php if ($es_admin && $total_eliminadas > 0): ?>
                        <div class="mb-3">
                            <a href="listar_eliminados.php" class="text-danger"><i class="fas fa-trash-alt me-1"></i>Ver personas eliminadas (<?php echo $total_eliminadas; ?>)</a>
                        </div>
                    <?php endif; ?>

                    <?php if ($result && $result->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover" id="tablaPersonas">
                                <thead>
                                    <tr>
                                        <?php if ($es_admin): ?>
                                            <th>Cédula</th>
                                        <?php endif; ?>
                                        <th>Nombre</th>
                                        <th>Email</th>
                                        <?php if ($es_admin): ?>
                                            <th>Teléfono</th>
                                        <?php endif; ?>
                                        <th>Cargo</th>
                                        <th class="text-center">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while($row = $result->fetch_assoc()): ?>
                                    <tr>
                                        <?php if ($es_admin): ?>
                                            <td data-label="CÉDULA"><?php echo htmlspecialchars($row['cedula']); ?></td>
                                        <?php endif; ?>
                                        <td data-label="NOMBRE"><?php echo htmlspecialchars($row['nombres']); ?></td>
                                        <td data-label="EMAIL"><?php echo htmlspecialchars($row['correo']); ?></td>
                                        <?php if ($es_admin): ?>
                                            <td data-label="TELÉFONO"><?php echo htmlspecialchars($row['telefono']); ?></td>
                                        <?php endif; ?>
                                        <td data-label="CARGO"><?php echo htmlspecialchars($row['cargo']); ?></td>
                                        <td data-label="ACCIONES" class="text-center">
                                            <?php if ($es_admin): ?>
                                                <!-- ADMIN: Ve todos los botones -->
                                                <div class="d-flex gap-1 justify-content-center">
                                                    <!-- Botón VER DETALLE -->
                                                    <a href="detalle.php?id=<?php echo $row['id']; ?>" 
                                                       class="btn btn-sm btn-info" 
                                                       title="Ver detalles">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    
                                                    <!-- Botón EDITAR -->
                                                    <a href="editar.php?id=<?php echo $row['id']; ?>" 
                                                       class="btn btn-sm btn-warning" 
                                                       title="Editar persona">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    
                                                    <!-- Botón ELIMINAR (marca como eliminado) -->
                                                    <a href="eliminar.php?id=<?php echo $row['id']; ?>" 
                                                       class="btn btn-sm btn-danger" 
                                                       title="Eliminar"
                                                       onclick="return confirm('¿Estás seguro de eliminar a <?php echo addslashes($row['nombres']); ?>?')">
                                                        <i class="fas fa-trash-alt"></i>
                                                    </a>
                                                </div>
                                            <?php else: ?>
                                                <!-- LECTOR: Solo ve botón VER -->
                                                <div class="d-flex gap-1 justify-content-center">
                                                    <a href="detalle.php?id=<?php echo $row['id']; ?>" 
                                                       class="btn btn-sm btn-info" 
                                                       title="Ver detalles">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <span class="badge bg-secondary">Solo lectura</span>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Total de registros activos -->
                        <div class="mt-3 text-muted">
                            <i class="fas fa-info-circle me-1"></i>
                            Total de personas activas: <strong><?php echo $result->num_rows; ?></strong>
                            <?php if (!$es_admin): ?>
                                <span class="ms-3 badge bg-success">Modo lectura</span>
                            <?php endif; ?>
                        </div>
                        
                    <?php else: ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>No hay personas activas registradas.
                            <?php if ($es_admin && $total_eliminadas > 0): ?>
                                <a href="listar_eliminados.php" class="alert-link ms-2">Ver personas eliminadas</a>
                            <?php endif; ?>
                            <?php if ($es_admin && $total_eliminadas == 0): ?>
                                <a href="agregar.php" class="alert-link ms-2">Agregar primera persona</a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ============================================ -->
<!-- SCRIPT PARA EL BUSCADOR (LUPA) -->
<!-- ============================================ -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const buscador = document.getElementById('buscadorPersonas');
    const tabla = document.getElementById('tablaPersonas');
    
    if (!buscador || !tabla) return;
    
    const filas = tabla.getElementsByTagName('tbody')[0].getElementsByTagName('tr');

    buscador.addEventListener('keyup', function() {
        const texto = buscador.value.toLowerCase().trim();

        for (let fila of filas) {
            let coincide = false;
            const celdas = fila.getElementsByTagName('td');
            
            // Recorrer todas las celdas de la fila excepto la última (acciones)
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

<!-- ESTILOS (se mantienen igual) -->
<style>
/* ============================================ */
/* ESTILOS PARA LOS BOTONES */
/* ============================================ */
.d-flex.gap-1 {
    gap: 5px !important;
}

.btn-sm {
    padding: 4px 8px;
    font-size: 0.8rem;
}

/* ============================================ */
/* BUSCADOR CON EFECTO HOVER (LUPA QUE SE EXPANDE) */
/* ============================================ */
.buscador-container {
    display: flex;
    justify-content: flex-end;
    margin-bottom: 20px;
}

.search-wrapper {
    position: relative;
    display: flex;
    align-items: center;
}

.search-icon {
    position: absolute;
    right: 10px;
    color: #5a2d8c;
    font-size: 1.2rem;
    cursor: pointer;
    z-index: 2;
    transition: all 0.3s ease;
    background: white;
    padding: 8px;
    border-radius: 50%;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}

.search-input {
    width: 0;
    padding: 8px 0;
    border: 2px solid #5a2d8c;
    border-radius: 30px;
    font-size: 1rem;
    outline: none;
    opacity: 0;
    transition: width 0.4s ease, opacity 0.3s ease, padding 0.3s ease;
    background: white;
    color: #333;
}

/* Al hacer hover sobre el contenedor, el input se expande */
.search-wrapper:hover .search-input {
    width: 250px;
    padding: 8px 15px;
    opacity: 1;
}

.search-wrapper:hover .search-icon {
    background: #5a2d8c;
    color: white;
    transform: scale(1.1);
    right: 10px; /* se mantiene a la derecha */
}

/* Responsive para móviles */
@media (max-width: 768px) {
    .buscador-container {
        justify-content: center;
    }
    .search-wrapper:hover .search-input {
        width: 200px;
    }
}

/* ============================================ */
/* RESPONSIVE PARA MÓVILES - VERSIÓN TARJETAS MEJORADA */
/* ============================================ */
@media (max-width: 768px) {
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
        white-space: normal !important;
        word-break: break-word !important;
    }
    
    .table tbody td:last-child {
        border-bottom: none !important;
    }
    
    .table tbody td:before {
        content: attr(data-label) !important;
        font-weight: 700 !important;
        color: #5a2d8c !important;
        margin-right: 15px !important;
        min-width: 90px !important;
        font-size: 13px !important;
        text-transform: uppercase !important;
        letter-spacing: 0.5px !important;
    }
    
    .table tbody td:last-child:before {
        content: "ACCIONES" !important;
    }
    
    .btn-sm {
        padding: 5px 8px !important;
        font-size: 11px !important;
        margin: 2px !important;
    }
    
    .badge {
        font-size: 11px !important;
        padding: 4px 8px !important;
    }
}

@media (max-width: 480px) {
    .table tbody td {
        font-size: 13px !important;
        padding: 8px 5px !important;
    }
    
    .table tbody td:before {
        min-width: 70px !important;
        font-size: 11px !important;
    }
    
    .btn-sm {
        padding: 4px 6px !important;
        font-size: 10px !important;
    }
    
    .table tbody td .d-flex {
        gap: 3px !important;
    }
}

@media (min-width: 769px) and (max-width: 1024px) {
    .table {
        font-size: 14px;
    }
    .btn-sm {
        padding: 3px 6px;
        font-size: 0.7rem;
    }
}
</style>

<?php include '../../includes/footer.php'; ?>
