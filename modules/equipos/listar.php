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

// Consultar equipos
$sql = "SELECT * FROM equipos ORDER BY id DESC";
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
                    
                    <!-- Botón AGREGAR - Solo visible para admin -->
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
                    
                    <?php if ($result && $result->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Código</th>
                                        <th>Tipo</th>
                                        <th>Marca</th>
                                        <th>Modelo</th>
                                        <th>Serie</th>
                                        <th>Estado</th>
                                        <th class="text-center">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while($row = $result->fetch_assoc()): ?>
                                    <tr>
                                        <td data-label="CÓDIGO"><?php echo htmlspecialchars($row['codigo_barras'] ?? 'N/A'); ?></td>
                                        <td data-label="TIPO"><?php echo htmlspecialchars($row['tipo_equipo'] ?? 'N/A'); ?></td>
                                        <td data-label="MARCA"><?php echo htmlspecialchars($row['marca'] ?? 'N/A'); ?></td>
                                        <td data-label="MODELO"><?php echo htmlspecialchars($row['modelo'] ?? 'N/A'); ?></td>
                                        <td data-label="SERIE"><?php echo htmlspecialchars($row['numero_serie'] ?? 'N/A'); ?></td>
                                        <td data-label="ESTADO">
                                            <span class="badge bg-<?php 
                                                echo $row['estado'] == 'Disponible' ? 'success' : 
                                                    ($row['estado'] == 'Asignado' ? 'warning' : 'secondary'); 
                                            ?>">
                                                <?php echo $row['estado'] ?? 'N/A'; ?>
                                            </span>
                                        </td>
                                        <td data-label="ACCIONES" class="text-center">
                                            <?php if ($es_admin): ?>
                                                <!-- ADMIN: Todos los botones -->
                                                <div class="d-flex gap-1 justify-content-center">
                                                    <!-- Botón QR -->
                                                    <a href="/inventario_ti/api/generar_qr_equipo.php?id=<?php echo $row['id']; ?>" 
                                                       class="btn btn-sm btn-dark" 
                                                       title="Descargar código QR"
                                                       download="qr_<?php echo $row['codigo_barras']; ?>.png">
                                                        <i class="fas fa-qrcode"></i>
                                                    </a>
                                                    
                                                    <!-- Botón VER DETALLE (si existe) -->
                                                    <?php if (file_exists('detalle.php')): ?>
                                                    <a href="detalle.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-info" title="Ver detalles">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <?php endif; ?>
                                                    
                                                    <!-- Botón EDITAR -->
                                                    <a href="editar.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-warning" title="Editar equipo">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    
                                                    <!-- Botón ELIMINAR -->
                                                    <a href="eliminar.php?id=<?php echo $row['id']; ?>" 
                                                       class="btn btn-sm btn-danger" 
                                                       title="Eliminar permanentemente"
                                                       onclick="return confirm('⚠️ ¿ELIMINAR?\n\nEquipo: <?php echo addslashes($row['tipo_equipo']); ?>\nCódigo: <?php echo addslashes($row['codigo_barras']); ?>\n\nSe borrará TODO su historial.\n¿Continuar?')">
                                                        <i class="fas fa-trash-alt"></i>
                                                    </a>
                                                </div>
                                            <?php else: ?>
                                                <!-- LECTOR: Solo puede ver -->
                                                <div class="d-flex gap-1 justify-content-center">
                                                    <span class="badge bg-secondary">Solo lectura</span>
                                                    <!-- Botón QR también para lectores (solo descarga) -->
                                                    <a href="/inventario_ti/api/generar_qr_equipo.php?id=<?php echo $row['id']; ?>" 
                                                       class="btn btn-sm btn-dark" 
                                                       title="Descargar código QR"
                                                       download="qr_<?php echo $row['codigo_barras']; ?>.png">
                                                        <i class="fas fa-qrcode"></i>
                                                    </a>
                                                </div>
                                            <?php endif; ?>
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

<style>
/* ============================================ */
/* ESTILOS PARA BOTONES DE ACCIÓN */
/* ============================================ */

/* Contenedor de botones - SIEMPRE EN FILA */
.d-flex.gap-1 {
    display: flex !important;
    flex-direction: row !important;
    flex-wrap: nowrap !important;
    gap: 5px !important;
    justify-content: center !important;
    align-items: center !important;
}

/* Estilo base para botones de acción */
.d-flex.gap-1 .btn-sm {
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    padding: 6px 10px !important;
    font-size: 0.85rem !important;
    min-width: 38px !important;
    height: 36px !important;
    margin: 0 !important;
    border-radius: 20px !important;
    white-space: nowrap !important;
    flex-shrink: 0 !important;
    transition: all 0.2s ease !important;
}

/* Efecto hover */
.d-flex.gap-1 .btn-sm:hover {
    transform: translateY(-2px) !important;
    box-shadow: 0 4px 8px rgba(90, 45, 140, 0.3) !important;
}

/* Colores específicos para cada botón */
.d-flex.gap-1 .btn-dark {
    background: #343a40 !important;
    color: #f3b229 !important;
    border: 1px solid #f3b229 !important;
}

.d-flex.gap-1 .btn-info {
    background: #17a2b8 !important;
    color: white !important;
    border: none !important;
}

.d-flex.gap-1 .btn-warning {
    background: #ffc107 !important;
    color: #212529 !important;
    border: none !important;
}

.d-flex.gap-1 .btn-danger {
    background: #dc3545 !important;
    color: white !important;
    border: none !important;
}

/* Badge de solo lectura */
.badge.bg-secondary {
    background: #6c757d !important;
    color: white !important;
    padding: 5px 12px !important;
    border-radius: 20px !important;
    font-size: 0.75rem !important;
    height: 32px !important;
    display: inline-flex !important;
    align-items: center !important;
    white-space: nowrap !important;
}

/* ============================================ */
/* RESPONSIVE PARA MÓVILES */
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
    }
    
    /* Ajuste para la columna de acciones */
    .table tbody td:last-child:before {
        content: "ACCIONES" !important;
    }
    
    /* Botones en móvil - FORZAR FILA */
    .table tbody td .d-flex.gap-1 {
        flex-wrap: nowrap !important;
        justify-content: flex-end !important;
        width: 100% !important;
    }
    
    .d-flex.gap-1 .btn-sm {
        padding: 5px 8px !important;
        font-size: 0.8rem !important;
        min-width: 34px !important;
        height: 34px !important;
    }
    
    /* Badge de estado */
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
    
    .d-flex.gap-1 .btn-sm {
        padding: 4px 6px !important;
        font-size: 0.7rem !important;
        min-width: 30px !important;
        height: 30px !important;
    }
    
    .badge.bg-secondary {
        padding: 4px 8px !important;
        font-size: 0.7rem !important;
        height: 28px !important;
    }
}

@media (max-width: 360px) {
    .d-flex.gap-1 {
        gap: 3px !important;
    }
    
    .d-flex.gap-1 .btn-sm {
        padding: 3px 5px !important;
        min-width: 28px !important;
        height: 28px !important;
    }
}
</style>

<!-- Script adicional para mejor experiencia -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Los botones ya están en fila gracias al CSS
    console.log('📋 Página de equipos cargada correctamente');
});
</script>

<?php include '../../includes/footer.php'; ?>