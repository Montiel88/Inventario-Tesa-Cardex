<?php
require_once 'config/database.php';
$sql = "ALTER TABLE movimientos ADD COLUMN acta_firmada VARCHAR(255) DEFAULT NULL AFTER acta_generada";
if ($conn->query($sql)) {
    echo "OK: Columna acta_firmada agregada correctamente a la tabla movimientos.";
} else {
    echo "ERROR: " . $conn->error;
}
?>