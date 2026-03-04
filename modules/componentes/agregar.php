<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: /inventario_ti/login.php');
    exit();
}

// Solo admin puede agregar componentes
if ($_SESSION['user_rol'] != 1) {
    header('Location: listar.php?error=No tienes permisos');
    exit();
}

require_once '../../config/database.php';
include '../../includes/header.php';

// Si viene de un equipo específico, obtenemos su ID
$equipo_id = isset($_GET['equipo_id']) ? intval($_GET['equipo_id']) : 0;
$equipo = null;
if ($equipo_id > 0) {
    $sql_eq = "SELECT tipo_equipo, codigo_barras FROM equipos WHERE id = $equipo_id";
    $res_eq = $conn->query($sql_eq);
    if ($res_eq && $res_eq->num_rows > 0) {
        $equipo = $res_eq->fetch_assoc();
    } else {
        $equipo_id = 0; // Si no existe, ignoramos
    }
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Recoger y limpiar datos
    $nombre = trim($_POST['nombre_componente'] ?? '');
    $tipo = $_POST['tipo'] ?? '';
    $marca = trim($_POST['marca'] ?? '');
    $modelo = trim($_POST['modelo'] ?? '');
    $serie = trim($_POST['numero_serie'] ?? '');
    $especificaciones = trim($_POST['especificaciones'] ?? '');
    $estado = $_POST['estado'] ?? 'Bueno';
    $fecha_instalacion = !empty($_POST['fecha_instalacion']) ? $_POST['fecha_instalacion'] : null;
    $observaciones = trim($_POST['observaciones'] ?? '');
    $equipo_id_post = isset($_POST['equipo_id']) ? intval($_POST['equipo_id']) : 0;

    // Validaciones básicas
    if (empty($nombre)) {
        $error = "El nombre del componente es obligatorio.";
    } elseif (empty($tipo)) {
        $error = "El tipo de componente es obligatorio.";
    } else {
        // Si equipo_id_post es 0, lo convertimos a NULL para permitir componentes sin equipo
        if ($equipo_id_post == 0) {
            $equipo_id_post = null;
        }

        // Insertar usando prepared statement
        $sql = "INSERT INTO componentes 
                (equipo_id, nombre_componente, tipo, marca, modelo, numero_serie, especificaciones, estado, fecha_instalacion, observaciones) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        // Nota: bind_param con "i" para integer, "s" para string, pero null debe pasarse como null y tipo "i" no acepta null directamente.
        // Para manejar null, usamos variables y pasamos null si corresponde.
        $stmt->bind_param(
            "isssssssss",
            $equipo_id_post,
            $nombre,
            $tipo,
            $marca,
            $modelo,
            $serie,
            $especificaciones,
            $estado,
            $fecha_instalacion,
            $observaciones
        );

        if ($stmt->execute()) {
            // Redirigir según origen
            if ($equipo_id_post > 0) {
                header("Location: /inventario_ti/modules/equipos/detalle.php?id=$equipo_id_post&mensaje=Componente agregado correctamente");
            } else {
                header("Location: listar.php?mensaje=Componente agregado correctamente");
            }
            exit();
        } else {
            $error = "Error al guardar el componente: " . $conn->error;
        }
        $stmt->close();
    }
}
?>

<!-- El resto del formulario igual, pero con el campo oculto equipo_id si corresponde -->
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-md-8 mx-auto">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0"><i class="fas fa-microchip me-2"></i>Agregar Nuevo Componente</h4>
                    <?php if ($equipo): ?>
                        <p class="mb-0 text-muted">Para el equipo: <strong><?php echo $equipo['tipo_equipo'] . ' - ' . $equipo['codigo_barras']; ?></strong></p>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <?php echo $error; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <form method="POST">
                        <?php if ($equipo_id > 0): ?>
                            <input type="hidden" name="equipo_id" value="<?php echo $equipo_id; ?>">
                        <?php endif; ?>
                        <!-- resto del formulario igual -->
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Tipo de Componente <span class="text-danger">*</span></label>
                                <select name="tipo" class="form-select" required>
                                    <option value="">-- Seleccione --</option>
                                    <?php
                                    $tipos = [
                                        'Fuente de poder' => '🔌 Fuente de poder',
                                        'Memoria RAM' => '🧠 Memoria RAM',
                                        'Disco Duro' => '💾 Disco Duro',
                                        'SSD' => '⚡ SSD',
                                        'Procesador' => '⚙️ Procesador',
                                        'Tarjeta Madre' => '🖥️ Tarjeta Madre',
                                        'Ventilador' => '🌀 Ventilador',
                                        'Batería' => '🔋 Batería',
                                        'Cable' => '🔌 Cable',
                                        'Adaptador' => '🔌 Adaptador',
                                        'Otro' => '🔧 Otro'
                                    ];
                                    $selected_tipo = $_POST['tipo'] ?? '';
                                    foreach ($tipos as $valor => $etiqueta) {
                                        $selected = ($selected_tipo == $valor) ? 'selected' : '';
                                        echo "<option value=\"$valor\" $selected>$etiqueta</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nombre del Componente <span class="text-danger">*</span></label>
                                <input type="text" name="nombre_componente" class="form-control" 
                                       value="<?php echo htmlspecialchars($_POST['nombre_componente'] ?? ''); ?>" 
                                       required placeholder="Ej: Fuente ATX 600W">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Marca</label>
                                <input type="text" name="marca" class="form-control" 
                                       value="<?php echo htmlspecialchars($_POST['marca'] ?? ''); ?>" 
                                       placeholder="Ej: Corsair">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Modelo</label>
                                <input type="text" name="modelo" class="form-control" 
                                       value="<?php echo htmlspecialchars($_POST['modelo'] ?? ''); ?>" 
                                       placeholder="Ej: VS600">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Número de Serie</label>
                                <input type="text" name="numero_serie" class="form-control" 
                                       value="<?php echo htmlspecialchars($_POST['numero_serie'] ?? ''); ?>" 
                                       placeholder="Serie del fabricante">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Especificaciones</label>
                            <textarea name="especificaciones" class="form-control" rows="2" 
                                      placeholder="Ej: 600W, 80 Plus Bronze, etc."><?php echo htmlspecialchars($_POST['especificaciones'] ?? ''); ?></textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Estado <span class="text-danger">*</span></label>
                                <select name="estado" class="form-select" required>
                                    <?php
                                    $estados = ['Bueno', 'Regular', 'Malo', 'Por reemplazar'];
                                    $selected_estado = $_POST['estado'] ?? 'Bueno';
                                    foreach ($estados as $est) {
                                        $selected = ($selected_estado == $est) ? 'selected' : '';
                                        $icono = match($est) {
                                            'Bueno' => '✅',
                                            'Regular' => '⚠️',
                                            'Malo' => '❌',
                                            'Por reemplazar' => '🔄',
                                            default => ''
                                        };
                                        echo "<option value=\"$est\" $selected>$icono $est</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Fecha de Instalación</label>
                                <input type="date" name="fecha_instalacion" class="form-control" 
                                       value="<?php echo htmlspecialchars($_POST['fecha_instalacion'] ?? ''); ?>">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Observaciones</label>
                            <textarea name="observaciones" class="form-control" rows="2" 
                                      placeholder="Notas adicionales sobre el componente"><?php echo htmlspecialchars($_POST['observaciones'] ?? ''); ?></textarea>
                        </div>

                        <div class="d-flex justify-content-between mt-4">
                            <a href="<?php echo $equipo_id > 0 ? '/inventario_ti/modules/equipos/detalle.php?id=' . $equipo_id : 'listar.php'; ?>" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-2"></i>Cancelar
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Guardar Componente
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>