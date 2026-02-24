<?php
session_start();

// Verificar autenticación
if (!isset($_SESSION['user_id'])) {
    header('Location: /inventario_ti/login.php');
    exit();
}

// Verificar rol (1 = admin, 2 = lector) - CORREGIDO
$es_admin = ($_SESSION['user_rol'] == 1);

require_once '../../config/database.php';
include '../../includes/header.php';

// Verificar conexión
if (!$conn) {
    die("Error de conexión a la base de datos");
}

// Consultar personas
$sql = "SELECT * FROM personas ORDER BY nombres";
$result = $conn->query($sql);

// Verificar si la consulta fue exitosa
if (!$result) {
    die("Error en la consulta: " . $conn->error);
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
                                        <th>Cédula</th>
                                        <th>Nombre</th>
                                        <th>Email</th>
                                        <th>Teléfono</th>
                                        <th>Cargo</th>
                                        <th class="text-center">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while($row = $result->fetch_assoc()): ?>
                                    <tr>
                                        <td data-label="CÉDULA"><?php echo htmlspecialchars($row['cedula']); ?></td>
                                        <td data-label="NOMBRE"><?php echo htmlspecialchars($row['nombres']); ?></td>
                                        <td data-label="EMAIL"><?php echo htmlspecialchars($row['correo']); ?></td>
                                        <td data-label="TELÉFONO"><?php echo htmlspecialchars($row['telefono']); ?></td>
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
                                                    
                                                    <!-- Botón ELIMINAR -->
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
                        
                        <!-- Total de registros -->
                        <div class="mt-3 text-muted">
                            <i class="fas fa-info-circle me-1"></i>
                            Total de personas: <strong><?php echo $result->num_rows; ?></strong>
                            <?php if (!$es_admin): ?>
                                <span class="ms-3 badge bg-success">Modo lectura</span>
                            <?php endif; ?>
                        </div>
                        
                    <?php else: ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>No hay personas registradas
                            <?php if ($es_admin): ?>
                                <a href="agregar.php" class="alert-link ms-2">Agregar primera persona</a>
                            <?php endif; ?>
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

/* Responsive para móviles */
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
    }
    
    .table tbody td {
        display: flex !important;
        justify-content: space-between !important;
        align-items: center !important;
        padding: 10px 8px !important;
        border: none !important;
        border-bottom: 1px dashed #eee !important;
    }
    
    .table tbody td:last-child {
        border-bottom: none !important;
    }
    
    .table tbody td:before {
        content: attr(data-label) !important;
        font-weight: 700 !important;
        color: #5a2d8c !important;
        margin-right: 15px !important;
        min-width: 80px !important;
    }
}
</style>

<?php include '../../includes/footer.php'; ?>