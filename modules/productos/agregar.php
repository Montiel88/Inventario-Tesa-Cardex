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

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $codigo = $conn->real_escape_string($_POST['codigo']);
    $nombre = $conn->real_escape_string($_POST['nombre']);
    $tipo = $conn->real_escape_string($_POST['tipo']);
    $descripcion = $conn->real_escape_string($_POST['descripcion']);
    $stock = intval($_POST['stock']);
    
    $sql = "INSERT INTO productos (codigo_barras, nombre, tipo_equipo, descripcion, stock_actual) 
            VALUES ('$codigo', '$nombre', '$tipo', '$descripcion', $stock)";
    
    if ($conn->query($sql)) {
        header('Location: listar.php');
        exit();
    } else {
        $error = "Error al guardar: " . $conn->error;
    }
}
?>

<h1>Agregar Producto</h1>

<?php if (isset($error)): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
<?php endif; ?>

<form method="POST">
    <div class="mb-3">
        <label>Código de Barras</label>
        <input type="text" name="codigo" class="form-control" required>
    </div>
    <div class="mb-3">
        <label>Nombre del Producto</label>
        <input type="text" name="nombre" class="form-control" required>
    </div>
    <div class="mb-3">
        <label>Tipo de Equipo</label>
        <select name="tipo" class="form-control" required>
            <option value="Mouse">Mouse</option>
            <option value="Teclado">Teclado</option>
            <option value="Monitor">Monitor</option>
            <option value="Laptop">Laptop</option>
            <option value="Impresora">Impresora</option>
            <option value="Otro">Otro</option>
        </select>
    </div>
    <div class="mb-3">
        <label>Descripción</label>
        <textarea name="descripcion" class="form-control" rows="3"></textarea>
    </div>
    <div class="mb-3">
        <label>Stock Inicial</label>
        <input type="number" name="stock" class="form-control" value="1" required>
    </div>
    <button type="submit" class="btn btn-primary">Guardar</button>
    <a href="listar.php" class="btn btn-secondary">Cancelar</a>
</form>

<?php include '../../includes/footer.php'; ?>