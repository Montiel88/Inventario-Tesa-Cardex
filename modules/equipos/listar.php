<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: /inventario_ti/login.php');
    exit();
}

// Verificar roles si es necesario
$es_admin = ($_SESSION['user_rol'] == 'admin');
?>
<?php
require_once '../../config/database.php';
include '../../includes/header.php';

// Verificar conexión
if (!$conn) {
    die("Error de conexión a la base de datos");
}

// Consultar equipos
$sql = "SELECT * FROM equipos ORDER BY id DESC";
$result = $conn->query($sql);
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0"><i class="fas fa-laptop me-2"></i>Listado de Equipos</h4>
                </div>
                <div class="card-body">
                    
                    <!-- ============================================ -->
                    <!-- MENSAJES DE ÉXITO O ERROR (AGREGADO) -->
                    <!-- ============================================ -->
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
                    
                    <a href="agregar.php" class="btn btn-primary mb-3">
                        <i class="fas fa-plus-circle me-2"></i>Agregar Nuevo Equipo
                    </a>
                    
                    <?php if ($result && $result->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Código</th>
                                        <th>Tipo</th>
                                        <th>Marca</th>
                                        <th>Modelo</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while($row = $result->fetch_assoc()): ?>
                                    <tr>
                                        <td data-label="CÓDIGO"><?php echo htmlspecialchars($row['codigo_barras'] ?? 'N/A'); ?></td>
                                        <td data-label="TIPO"><?php echo htmlspecialchars($row['tipo_equipo'] ?? 'N/A'); ?></td>
                                        <td data-label="MARCA"><?php echo htmlspecialchars($row['marca'] ?? 'N/A'); ?></td>
                                        <td data-label="MODELO"><?php echo htmlspecialchars($row['modelo'] ?? 'N/A'); ?></td>
                                        <td data-label="ESTADO">
                                            <span class="badge bg-<?php 
                                                echo $row['estado'] == 'Disponible' ? 'success' : 
                                                    ($row['estado'] == 'Asignado' ? 'warning' : 'secondary'); 
                                            ?>">
                                                <?php echo $row['estado'] ?? 'N/A'; ?>
                                            </span>
                                        </td>
                                        <td data-label="ACCIONES">
                                            <div class="d-flex gap-1 justify-content-center">
                                                <a href="editar.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-warning" title="Editar equipo">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <?php if ($es_admin): ?>
                                                <a href="eliminar.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-danger" title="Eliminar equipo" onclick="return confirm('¿Estás seguro de eliminar este equipo?')">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>No hay equipos registrados. 
                            <a href="agregar.php" class="alert-link">Agrega el primero</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Estilos para los botones */
.d-flex.gap-1 {
    gap: 5px !important;
}

.btn-sm {
    padding: 4px 8px;
    font-size: 0.8rem;
}

/* ============================================ */
/* RESPONSIVE PARA MÓVILES - VERSIÓN TARJETAS */
/* ============================================ */
@media (max-width: 768px) {
    /* Ocultar cabeceras de la tabla */
    .table thead {
        display: none !important;
    }
    
    /* Cada fila se convierte en tarjeta */
    .table tbody tr {
        display: block !important;
        margin-bottom: 20px !important;
        border: 1px solid #e0e0e0 !important;
        border-radius: 15px !important;
        padding: 15px !important;
        background: white !important;
        box-shadow: 0 2px 8px rgba(0,0,0,0.05) !important;
    }
    
    /* Cada celda se muestra como flex */
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
    
    /* Última celda sin borde */
    .table tbody td:last-child {
        border-bottom: none !important;
    }
    
    /* Mostrar etiqueta antes del valor */
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
    
    /* Ajuste para la columna de acciones */
    .table tbody td:last-child:before {
        content: "ACCIÓN" !important;
    }
    
    .table tbody td .d-flex {
        justify-content: flex-end !important;
    }
    
    /* Botones más pequeños */
    .btn-sm {
        padding: 5px 8px !important;
        font-size: 11px !important;
    }
    
    /* Ajuste para el badge de estado */
    .badge {
        font-size: 12px !important;
        padding: 5px 8px !important;
    }
}

/* Para teléfonos muy pequeños */
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
}
</style>

<?php include '../../includes/footer.php'; ?>