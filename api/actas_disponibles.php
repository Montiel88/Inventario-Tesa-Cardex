<?php
header('Content-Type: application/json');
echo json_encode([
    ['nombre' => 'Acta de Prueba 1', 'url' => '#', 'icono' => 'fa-file', 'color' => 'primary'],
    ['nombre' => 'Acta de Prueba 2', 'url' => '#', 'icono' => 'fa-file', 'color' => 'success'],
    ['nombre' => 'Acta de Prueba 3', 'url' => '#', 'icono' => 'fa-file', 'color' => 'danger']
]);
?>