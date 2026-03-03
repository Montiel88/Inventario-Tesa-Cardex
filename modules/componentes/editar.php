<?php
session_start();
require_once '../../config/database.php';
include '../../includes/header.php';

// Solo admin puede editar
if ($_SESSION['user_rol'] != 1) {
    header('Location: listar.php?error=No tienes permisos para editar');
    exit();
}

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: listar.php');
    exit();
}

// Obtener datos del componente
$sql = "SELECT c.*, e.tipo_equipo, e.codigo_barras 
        FROM componentes c
        JOIN equipos e ON c.equipo_id = e.id
        WHERE c.id = $id";
$result = $conn->query($sql);
if (!$result || $result->num_rows == 0) {
    header('Location: listar.php');
    exit();
}
$componente = $result->fetch_assoc();
$equipo_id = $componente['equipo_id'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre = $conn->real_escape_string($_POST['nombre_componente']);
    $tipo = $conn->real_escape_string($_POST['tipo']);
    $marca = $conn->real_escape_string($_POST['marca'] ?? '');
    $modelo = $conn->real_escape_string($_POST['modelo'] ?? '');
    $serie = $conn->real_escape_string($_POST['numero_serie'] ?? '');
    $especificaciones = $conn->real_escape_string($_POST['especificaciones'] ?? '');
    $estado = $conn->real_escape_string($_POST['estado']);
    $fecha_instalacion = !empty($_POST['fecha_instalacion']) ? "'" . $conn->real_escape_string($_POST['fecha_instalacion']) . "'" : "NULL";
    $observaciones = $conn->real_escape_string($_POST['observaciones'] ?? '');

    $sql_update = "UPDATE componentes SET 
                    nombre_componente = '$nombre',
                    tipo = '$tipo',
                    marca = '$marca',
                    modelo = '$modelo',
                    numero_serie = '$serie',
                    especificaciones = '$especificaciones',
                    estado = '$estado',
                    fecha_instalacion = $fecha_instalacion,
                    observaciones = '$observaciones'
                  WHERE id = $id";

    if ($conn->query($sql_update)) {
        header("Location: /inventario_ti/modules/equipos/detalle.php?id=$equipo_id&mensaje=Componente actualizado");
    } else {
        $error = "Error al actualizar: " . $conn->error;
    }
}
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-md-8 mx-auto">
            <div class="card">
                <div class="card-header">
                    <h4><i class="fas fa-edit me-2"></i>Editar Componente</h4>
                    <p class="mb-0">Equipo: <?php echo $componente['tipo_equipo'] . ' - ' . $componente['codigo_barras']; ?></p>
                </div>
                <div class="card-body">
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label>Nombre del Componente *</label>
                                <input type="text" name="nombre_componente" class="form-control" 
                                       value="<?php echo htmlspecialchars($componente['nombre_componente']); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label>Tipo *</label>
                                <select name="tipo" class="form-control" required>
                                    <option value="">Seleccione</option>
                                    <option value="Fuente de poder" <?php echo $componente['tipo'] == 'Fuente de poder' ? 'selected' : ''; ?>>🔌 Fuente de poder</option>
                                    <option value="Memoria RAM" <?php echo $componente['tipo'] == 'Memoria RAM' ? 'selected' : ''; ?>>🧠 Memoria RAM</option>
                                    <option value="Disco Duro" <?php echo $componente['tipo'] == 'Disco Duro' ? 'selected' : ''; ?>>💾 Disco Duro</option>
                                    <option value="SSD" <?php echo $componente['tipo'] == 'SSD' ? 'selected' : ''; ?>>⚡ SSD</option>
                                    <option value="Procesador" <?php echo $componente['tipo'] == 'Procesador' ? 'selected' : ''; ?>>⚙️ Procesador</option>
                                    <option value="Tarjeta Madre" <?php echo $componente['tipo'] == 'Tarjeta Madre' ? 'selected' : ''; ?>>🖥️ Tarjeta Madre</option>
                                    <option value="Ventilador" <?php echo $componente['tipo'] == 'Ventilador' ? 'selected' : ''; ?>>🌀 Ventilador</option>
                                    <option value="Batería" <?php echo $componente['tipo'] == 'Batería' ? 'selected' : ''; ?>>🔋 Batería</option>
                                    <option value="Otro" <?php echo $componente['tipo'] == 'Otro' ? 'selected' : ''; ?>>🔧 Otro</option>
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label>Marca</label>
                                <input type="text" name="marca" class="form-control" 
                                       value="<?php echo htmlspecialchars($componente['marca'] ?? ''); ?>">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label>Modelo</label>
                                <input type="text" name="modelo" class="form-control" 
                                       value="<?php echo htmlspecialchars($componente['modelo'] ?? ''); ?>">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label>Número de Serie</label>
                                <input type="text" name="numero_serie" class="form-control" 
                                       value="<?php echo htmlspecialchars($componente['numero_serie'] ?? ''); ?>">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label>Especificaciones</label>
                            <textarea name="especificaciones" class="form-control" rows="2"><?php echo htmlspecialchars($componente['especificaciones'] ?? ''); ?></textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label>Estado *</label>
                                <select name="estado" class="form-control" required>
                                    <option value="Bueno" <?php echo $componente['estado'] == 'Bueno' ? 'selected' : ''; ?>>✅ Bueno</option>
                                    <option value="Regular" <?php echo $componente['estado'] == 'Regular' ? 'selected' : ''; ?>>⚠️ Regular</option>
                                    <option value="Malo" <?php echo $componente['estado'] == 'Malo' ? 'selected' : ''; ?>>❌ Malo</option>
                                    <option value="Por reemplazar" <?php echo $componente['estado'] == 'Por reemplazar' ? 'selected' : ''; ?>>🔄 Por reemplazar</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label>Fecha de Instalación</label>
                                <input type="date" name="fecha_instalacion" class="form-control" 
                                       value="<?php echo $componente['fecha_instalacion'] ?? ''; ?>">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label>Observaciones</label>
                            <textarea name="observaciones" class="form-control" rows="2"><?php echo htmlspecialchars($componente['observaciones'] ?? ''); ?></textarea>
                        </div>

                        <div class="text-center">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Actualizar Componente
                            </button>
                            <a href="/inventario_ti/modules/equipos/detalle.php?id=<?php echo $equipo_id; ?>" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-2"></i>Cancelar
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>