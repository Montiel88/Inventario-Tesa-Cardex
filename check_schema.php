<?php
require_once 'config/database.php';
$tables = ['asignaciones', 'movimientos'];
foreach ($tables as $table) {
    echo "--- TABLE: $table ---\n";
    $res = $conn->query("DESCRIBE $table");
    while($row = $res->fetch_assoc()) {
        print_r($row);
    }
}
?>