<?php
require_once 'config/database.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>Estructura de Equipos</title>
    <style>
        body { font-family: Arial; margin: 20px; }
        table { border-collapse: collapse; width: 100%; }
        th { background: #5a2d8c; color: white; padding: 10px; }
        td { padding: 8px; border: 1px solid #ddd; }
        tr:nth-child(even) { background: #f9f9f9; }
    </style>
</head>
<body>
    <h1>Estructura de la tabla 'equipos'</h1>";

// Consultar estructura
$sql = "DESCRIBE equipos";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    echo "<table>";
    echo "<tr><th>Campo</th><th>Tipo</th><th>Nulo</th><th>Clave</th><th>Predeterminado</th></tr>";
    
    while($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td><strong>" . $row['Field'] . "</strong></td>";
        echo "<td>" . $row['Type'] . "</td>";
        echo "<td>" . $row['Null'] . "</td>";
        echo "<td>" . $row['Key'] . "</td>";
        echo "<td>" . ($row['Default'] ?? 'NULL') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>Error al consultar la tabla</p>";
}

// Verificar si la columna 'ubicacion_id' existe
$check = $conn->query("SHOW COLUMNS FROM equipos LIKE 'ubicacion_id'");
if ($check->num_rows > 0) {
    echo "<p style='color:green;'>✅ La columna 'ubicacion_id' EXISTE en la tabla</p>";
} else {
    echo "<p style='color:red;'>❌ La columna 'ubicacion_id' NO EXISTE en la tabla</p>";
}

echo "</body></html>";
?>