<?php
// Archivo: backup_auto.php - Versión que funciona sin mysqldump
// Ejecutar automáticamente o manualmente para respaldar la BD

$backup_dir = __DIR__ . '/backups_auto/';
if (!file_exists($backup_dir)) {
    mkdir($backup_dir, 0777, true);
}

$fecha = date('Y-m-d_H-i-s');
$filename = "backup_{$fecha}.sql";
$filepath = $backup_dir . $filename;

echo "📁 Creando backup en: $filepath\n";

// Exportar usando PHP
$success = exportarConPHP($filepath);

if ($success && file_exists($filepath) && filesize($filepath) > 0) {
    echo "✅ Backup creado: $filename\n";
    echo "📊 Tamaño: " . round(filesize($filepath) / 1024, 2) . " KB\n";
    
    // Limpiar backups antiguos (más de 30 días)
    $files = glob($backup_dir . "backup_*.sql");
    $now = time();
    $deleted = 0;
    foreach ($files as $file) {
        if (is_file($file) && ($now - filemtime($file)) > 30 * 24 * 60 * 60) {
            unlink($file);
            $deleted++;
        }
    }
    if ($deleted > 0) {
        echo "🗑️ Se eliminaron $deleted backup(s) antiguos (>30 días)\n";
    }
} else {
    echo "❌ Error al crear backup\n";
}

function exportarConPHP($filepath) {
    global $conn;
    
    // Conectar a la base de datos si no está conectada
    if (!isset($conn) || !$conn) {
        $host = 'localhost';
        $user = 'root';
        $pass = '';
        $db = 'inventario_ti';
        $conn = new mysqli($host, $user, $pass, $db);
        if ($conn->connect_error) {
            echo "❌ Error de conexión: " . $conn->connect_error . "\n";
            return false;
        }
        $conn->set_charset("utf8");
    }
    
    // Obtener todas las tablas
    $tables = [];
    $result = $conn->query("SHOW TABLES");
    if (!$result) {
        echo "❌ Error al obtener tablas: " . $conn->error . "\n";
        return false;
    }
    while ($row = $result->fetch_row()) {
        $tables[] = $row[0];
    }
    
    if (empty($tables)) {
        echo "❌ No se encontraron tablas en la base de datos\n";
        return false;
    }
    
    echo "📋 Exportando " . count($tables) . " tablas...\n";
    
    $sql = "-- Backup generado el " . date('Y-m-d H:i:s') . "\n";
    $sql .= "-- Base de datos: inventario_ti\n";
    $sql .= "SET FOREIGN_KEY_CHECKS=0;\n\n";
    
    $total_rows = 0;
    
    foreach ($tables as $table) {
        echo "   Exportando tabla: $table... ";
        
        // Estructura de la tabla
        $createRes = $conn->query("SHOW CREATE TABLE `$table`");
        if (!$createRes) {
            echo "ERROR (estructura)\n";
            continue;
        }
        $create = $createRes->fetch_row();
        $sql .= "DROP TABLE IF EXISTS `$table`;\n";
        $sql .= $create[1] . ";\n\n";
        
        // Datos de la tabla
        $res = $conn->query("SELECT * FROM `$table`");
        if (!$res) {
            echo "ERROR (datos)\n";
            continue;
        }
        
        $rows = $res->fetch_all(MYSQLI_ASSOC);
        if (count($rows) > 0) {
            $columns = array_keys($rows[0]);
            $sql .= "INSERT INTO `$table` (`" . implode("`, `", $columns) . "`) VALUES\n";
            $values = [];
            foreach ($rows as $row) {
                $escaped = array_map(function($v) use ($conn) {
                    if ($v === null) return 'NULL';
                    return "'" . $conn->real_escape_string($v) . "'";
                }, $row);
                $values[] = "(" . implode(", ", $escaped) . ")";
            }
            $sql .= implode(",\n", $values) . ";\n\n";
            $total_rows += count($rows);
            echo count($rows) . " filas\n";
        } else {
            echo "0 filas\n";
        }
    }
    
    $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";
    
    // Guardar archivo
    if (file_put_contents($filepath, $sql) === false) {
        echo "❌ Error al escribir archivo\n";
        return false;
    }
    
    echo "✅ Exportación completada: $total_rows filas exportadas\n";
    return true;
}
