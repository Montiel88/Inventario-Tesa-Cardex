<?php
// Archivo: restaurar_ultimo_backup.php
// Restaura el último backup automático

$backup_dir = __DIR__ . '/backups_auto/';

// Obtener el último backup
$backups = glob($backup_dir . "backup_*.sql");
if (empty($backups)) {
    die("❌ No hay backups disponibles");
}

usort($backups, function($a, $b) {
    return filemtime($b) - filemtime($a);
});

$ultimo_backup = $backups[0];
$fecha_backup = date('Y-m-d H:i:s', filemtime($ultimo_backup));

echo "📁 Último backup: " . basename($ultimo_backup) . "\n";
echo "📅 Fecha: $fecha_backup\n";
echo "📊 Tamaño: " . round(filesize($ultimo_backup) / 1024, 2) . " KB\n";
echo "\n⚠️ ¿Restaurar este backup? Esto sobrescribirá TODOS los datos actuales.\n";
echo "Escribe 'RESTAURAR' para confirmar: ";

$handle = fopen("php://stdin", "r");
$confirmacion = trim(fgets($handle));

if ($confirmacion !== 'RESTAURAR') {
    die("\n❌ Restauración cancelada");
}

// Configuración
$host = 'localhost';
$user = 'root';
$pass = '';
$db = 'inventario_ti';
$mysql = 'C:\xampp\mysql\bin\mysql.exe';

if (!file_exists($mysql)) {
    die("❌ mysql.exe no encontrado en: $mysql");
}

// Restaurar
$cmd = "\"$mysql\" --host=$host --user=$user --password=$pass \"$db\" < \"$ultimo_backup\" 2>&1";
exec($cmd, $output, $return_var);

if ($return_var === 0) {
    echo "\n✅ Base de datos restaurada exitosamente desde: " . basename($ultimo_backup) . "\n";
} else {
    echo "\n❌ Error al restaurar:\n";
    echo implode("\n", $output);
}
