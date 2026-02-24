<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: /inventario_ti/login.php');
    exit();
}

// Verificar roles si es necesario
$es_admin = ($_SESSION['user_rol'] == 'admin');

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

// Obtener datos actuales
$sql = "SELECT * FROM productos WHERE id = $id";
$result = $conn->query($sql);

if ($result->num_rows == 0) {
    header('Location: listar.php');
    exit();
}

$producto = $result->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $codigo = $conn->real_escape_string($_POST['codigo']);
    $nombre = $conn->real_escape_string($_POST['nombre']);
    $tipo = $conn->real_escape_string($_POST['tipo']);
    $descripcion = $conn->real_escape_string($_POST['descripcion']);
    
    $sql = "UPDATE productos SET 
            codigo_barras = '$codigo',
            nombre = '$nombre',
            tipo_equipo = '$tipo',
            descripcion = '$descripcion'
            WHERE id = $id";
    
    if ($conn->query($sql)) {
        header('Location: listar.php');
        exit();
    } else {
        $error = "Error al actualizar: " . $conn->error;
    }
}
?>

<h1>Editar Producto</h1>

<?php if (isset($error)): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
<?php endif; ?>

<form method="POST">
    <div class="mb-3">
        <label>Código de Barras</label>
        <input type="text" name="codigo" class="form-control" value="<?php echo $producto['codigo_barras']; ?>" required>
    </div>
    <div class="mb-3">
        <label>Nombre del Producto</label>
        <input type="text" name="nombre" class="form-control" value="<?php echo $producto['nombre']; ?>" required>
    </div>
    <div class="mb-3">
        <label>Tipo de Equipo</label>
        <select name="tipo" class="form-control" required>
            <option value="Mouse" <?php echo $producto['tipo_equipo'] == 'Mouse' ? 'selected' : ''; ?>>Mouse</option>
            <option value="Teclado" <?php echo $producto['tipo_equipo'] == 'Teclado' ? 'selected' : ''; ?>>Teclado</option>
            <option value="Monitor" <?php echo $producto['tipo_equipo'] == 'Monitor' ? 'selected' : ''; ?>>Monitor</option>
            <option value="Laptop" <?php echo $producto['tipo_equipo'] == 'Laptop' ? 'selected' : ''; ?>>Laptop</option>
            <option value="Impresora" <?php echo $producto['tipo_equipo'] == 'Impresora' ? 'selected' : ''; ?>>Impresora</option>
            <option value="Otro" <?php echo $producto['tipo_equipo'] == 'Otro' ? 'selected' : ''; ?>>Otro</option>
        </select>
    </div>
    <div class="mb-3">
        <label>Descripción</label>
        <textarea name="descripcion" class="form-control" rows="3"><?php echo $producto['descripcion']; ?></textarea>
    </div>
    <button type="submit" class="btn btn-primary">Actualizar</button>
    <a href="listar.php" class="btn btn-secondary">Cancelar</a>
</form>

<?php include '../../includes/footer.php'; ?>