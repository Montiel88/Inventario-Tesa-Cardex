<?php
require_once 'config/database.php';
$sql = "ALTER TABLE actas ADD COLUMN archivo_firmado VARCHAR(255) DEFAULT NULL AFTER archivo_pdf";
if ($conn->query($sql)) {
    echo "OK: Columna archivo_firmado agregada correctamente a la tabla actas.";
} else {
    echo "ERROR: " . $conn->error;
}
?>