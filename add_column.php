<?php
require_once 'config/database.php';
$sql = "ALTER TABLE equipos ADD COLUMN foto VARCHAR(255) DEFAULT NULL AFTER observaciones";
if ($conn->query($sql)) {
    echo "OK: Columna foto agregada correctamente.";
} else {
    echo "ERROR: " . $conn->error;
}
?>