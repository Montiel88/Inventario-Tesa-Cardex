<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: /inventario_ti/login.php');
    exit();
}

// Verificar roles si es necesario
$es_admin = isset($_SESSION['user_rol']) && (
    $_SESSION['user_rol'] == 1 || $_SESSION['user_rol'] === 'admin'
);

// Solo admin puede acceder a ciertas funciones
if (!$es_admin && strpos($_SERVER['PHP_SELF'], 'eliminar.php') !== false) {
    header('Location: dashboard.php?error=No tienes permisos');
    exit();
}
?>
<?php
require_once '../../config/database.php';
include '../../includes/header.php';

if (!isset($_GET['id'])) {
    header('Location: listar.php');
    exit();
}

$id = intval($_GET['id']);

// Obtener datos de la persona
$stmt = $conn->prepare("SELECT * FROM personas WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    header('Location: listar.php');
    exit();
}

$persona = $result->fetch_assoc();
$stmt->close();

// Obtener equipos asignados a esta persona
$stmt_equipos = $conn->prepare(
    "SELECT e.*, a.fecha_asignacion
     FROM equipos e
     JOIN asignaciones a ON e.id = a.equipo_id
     WHERE a.persona_id = ? AND a.fecha_devolucion IS NULL
     ORDER BY a.fecha_asignacion DESC"
);
$stmt_equipos->bind_param("i", $id);
$stmt_equipos->execute();
$equipos = $stmt_equipos->get_result();

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
?>

<style>
    .persona-detail-card {
        border: 0;
        border-radius: 18px;
        box-shadow: 0 16px 40px rgba(42, 21, 68, 0.12);
        overflow: hidden;
    }

    .persona-detail-hero {
        background: linear-gradient(135deg, #52218a 0%, #6a30ab 55%, #8146c9 100%);
        color: #fff;
        padding: 1.25rem 1.5rem;
    }

    .persona-avatar {
        width: 56px;
        height: 56px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.2);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1.3rem;
        margin-right: 0.75rem;
        border: 1px solid rgba(255, 255, 255, 0.35);
    }

    .persona-info-box {
        border: 1px solid #ece5f7;
        border-radius: 14px;
        background: #fff;
        overflow: hidden;
    }

    .persona-info-row {
        display: flex;
        justify-content: space-between;
        gap: 1rem;
        padding: 0.85rem 1rem;
        border-bottom: 1px solid #f2edf9;
    }

    .persona-info-row:last-child {
        border-bottom: 0;
    }

    .persona-info-label {
        font-weight: 600;
        color: #61437f;
    }

    .persona-info-value {
        color: #2d1842;
        text-align: right;
    }

    .btn-action-menu {
        background: rgba(255, 255, 255, 0.2);
        border: 1px solid rgba(255, 255, 255, 0.45);
        color: #fff !important;
        border-radius: 999px;
        font-weight: 600;
        padding: 0.5rem 1rem;
    }

    .btn-action-menu:hover,
    .btn-action-menu:focus {
        background: rgba(255, 255, 255, 0.32);
        color: #fff !important;
    }

    .equip-list .list-group-item {
        border: 1px solid #ece5f7;
        border-radius: 12px;
        margin-bottom: 0.75rem;
        box-shadow: 0 4px 14px rgba(60, 25, 100, 0.06);
    }

    .chip-code {
        font-size: 0.76rem;
        background: #efe8fa;
        color: #5a2d8c;
        border-radius: 999px;
        padding: 0.2rem 0.55rem;
        font-weight: 600;
    }
</style>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card persona-detail-card">
                <div class="persona-detail-hero d-flex flex-wrap justify-content-between align-items-center">
                    <div class="d-flex align-items-center">
                        <div class="persona-avatar">
                            <i class="fas fa-user"></i>
                        </div>
                        <div>
                            <h4 class="mb-1">Detalle de Persona</h4>
                            <p class="mb-0 opacity-75"><?php echo e($persona['nombres']); ?></p>
                        </div>
                    </div>
                    <span class="badge bg-light text-dark mt-2 mt-md-0">
                        <i class="fas fa-id-card me-1"></i> C.I. <?php echo e($persona['cedula']); ?>
                    </span>
                    <div class="dropdown mt-2 mt-md-0">
                        <button class="btn btn-action-menu dropdown-toggle" type="button" id="opcionesPersona" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-ellipsis-v me-1"></i>Opciones
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="opcionesPersona">
                            <li>
                                <a class="dropdown-item" href="historial.php?id=<?php echo (int)$persona['id']; ?>">
                                    <i class="fas fa-history me-2"></i>Ver historial
                                </a>
                            </li>
                            <?php if ($es_admin): ?>
                                <li>
                                    <a class="dropdown-item" href="editar.php?id=<?php echo (int)$persona['id']; ?>">
                                        <i class="fas fa-pen-to-square me-2"></i>Editar persona
                                    </a>
                                </li>
                            <?php else: ?>
                                <li><span class="dropdown-item-text text-muted"><i class="fas fa-lock me-2"></i>Editar (solo admin)</span></li>
                            <?php endif; ?>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item" href="listar.php">
                                    <i class="fas fa-arrow-left me-2"></i>Volver al listado
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row g-4">
                        <div class="col-lg-5">
                            <div class="persona-info-box">
                                <div class="persona-info-row">
                                    <span class="persona-info-label">Cédula</span>
                                    <span class="persona-info-value"><?php echo e($persona['cedula']); ?></span>
                                </div>
                                <div class="persona-info-row">
                                    <span class="persona-info-label">Nombres</span>
                                    <span class="persona-info-value"><?php echo e($persona['nombres']); ?></span>
                                </div>
                                <div class="persona-info-row">
                                    <span class="persona-info-label">Correo</span>
                                    <span class="persona-info-value"><?php echo e($persona['correo'] ?: 'No registrado'); ?></span>
                                </div>
                                <div class="persona-info-row">
                                    <span class="persona-info-label">Cargo</span>
                                    <span class="persona-info-value"><?php echo e($persona['cargo']); ?></span>
                                </div>
                                <div class="persona-info-row">
                                    <span class="persona-info-label">Teléfono</span>
                                    <span class="persona-info-value"><?php echo e($persona['telefono'] ?: 'No registrado'); ?></span>
                                </div>
                            </div>
                            <p class="text-muted mt-3 mb-0">
                                <i class="fas fa-info-circle me-1"></i>Acciones disponibles en el menú <strong>Opciones</strong>.
                            </p>
                        </div>
                        
                        <div class="col-lg-7">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="mb-0">
                                    <i class="fas fa-laptop me-2 text-primary"></i>Equipos asignados
                                </h5>
                                <span class="badge bg-primary bg-opacity-75">
                                    <?php echo (int)$equipos->num_rows; ?> activo(s)
                                </span>
                            </div>

                            <?php if ($equipos && $equipos->num_rows > 0): ?>
                                <div class="list-group equip-list">
                                    <?php while($eq = $equipos->fetch_assoc()): ?>
                                        <div class="list-group-item">
                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                <strong><?php echo e($eq['tipo_equipo']); ?></strong>
                                                <span class="chip-code"><?php echo e($eq['codigo_barras']); ?></span>
                                            </div>
                                            <p class="mb-1 text-muted"><?php echo e($eq['marca'] . ' ' . $eq['modelo']); ?></p>
                                            <small class="text-secondary">
                                                <i class="fas fa-calendar-alt me-1"></i>
                                                Desde: <?php echo e(date('d/m/Y', strtotime($eq['fecha_asignacion']))); ?>
                                            </small>
                                        </div>
                                    <?php endwhile; ?>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-light border text-muted mb-0">
                                    <i class="fas fa-info-circle me-2"></i>
                                    No tiene equipos asignados actualmente.
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
