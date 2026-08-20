<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: text/html; charset=UTF-8');
echo "<!doctype html><html><head><meta charset='utf-8'><title>Instalar Tabla documentos_adjuntos</title>
<style>body{font-family:Arial;padding:20px;} .ok{color:#15803d;font-weight:700;} .bad{color:#b91c1c;font-weight:700;} .warn{color:#a16207;font-weight:700;} pre{background:#0f172a;color:#e2e8f0;padding:10px;border-radius:8px;}</style>
</head><body><h1>📁 Verificación / Instalación tabla documentos_adjuntos</h1>";

require_once __DIR__ . '/config/database.php';

$tabla = 'documentos_adjuntos';

// Verificar si existe la tabla
$res = $conn->query("SHOW TABLES LIKE '$tabla'");
$existe = $res && $res->num_rows > 0;

if (!$existe) {
    echo "<p class='warn'>⚠️ No existe la tabla $tabla. Creando...</p>";

    $sql = "CREATE TABLE `$tabla` (
        `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
        `equipo_id` INT UNSIGNED NULL,
        `persona_id` INT UNSIGNED NULL,
        `tipo_documento` VARCHAR(50) NOT NULL DEFAULT 'otro',
        `nombre_original` VARCHAR(255) NOT NULL,
        `nombre_archivo` VARCHAR(255) NOT NULL,
        `ruta` VARCHAR(500) NOT NULL,
        `tamano` INT UNSIGNED NOT NULL DEFAULT 0,
        `mime_type` VARCHAR(120) NULL,
        `descripcion` VARCHAR(500) NULL,
        `usuario_id` INT UNSIGNED NULL,
        `created_at` DATETIME NOT NULL,
        PRIMARY KEY (`id`),
        INDEX `idx_doc_equipo` (`equipo_id`),
        INDEX `idx_doc_persona` (`persona_id`),
        INDEX `idx_doc_tipo` (`tipo_documento`),
        INDEX `idx_doc_fecha` (`created_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

    if ($conn->query($sql)) {
        echo "<p class='ok'>✅ Tabla $tabla creada correctamente.</p>";
    } else {
        echo "<p class='bad'>❌ Error al crear: " . htmlspecialchars($conn->error) . "</p>";
    }
} else {
    echo "<p class='ok'>✅ La tabla $tabla ya existe.</p>";
}

// Verificar columnas
$cols = [];
$resCols = $conn->query("SHOW COLUMNS FROM `$tabla`");
if ($resCols) {
    while ($r = $resCols->fetch_assoc()) {
        $cols[$r['Field']] = $r;
    }
}

$necesarias = [
    'id','equipo_id','persona_id','tipo_documento','nombre_original','nombre_archivo',
    'ruta','tamano','mime_type','descripcion','usuario_id','created_at'
];

$faltantes = [];
foreach ($necesarias as $c) {
    if (!isset($cols[$c])) $faltantes[] = $c;
}

if (count($faltantes) > 0) {
    echo "<p class='warn'>⚠️ Faltan columnas: " . implode(', ', $faltantes) . ". Agregando...</p>";
    foreach ($faltantes as $c) {
        switch ($c) {
            case 'id':
                $sql = "ALTER TABLE `$tabla` ADD `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST"; break;
            case 'equipo_id':
                $sql = "ALTER TABLE `$tabla` ADD `equipo_id` INT UNSIGNED NULL, ADD INDEX `idx_doc_equipo` (`equipo_id`)"; break;
            case 'persona_id':
                $sql = "ALTER TABLE `$tabla` ADD `persona_id` INT UNSIGNED NULL, ADD INDEX `idx_doc_persona` (`persona_id`)"; break;
            case 'tipo_documento':
                $sql = "ALTER TABLE `$tabla` ADD `tipo_documento` VARCHAR(50) NOT NULL DEFAULT 'otro', ADD INDEX `idx_doc_tipo` (`tipo_documento`)"; break;
            case 'nombre_original':
                $sql = "ALTER TABLE `$tabla` ADD `nombre_original` VARCHAR(255) NOT NULL AFTER `tipo_documento`"; break;
            case 'nombre_archivo':
                $sql = "ALTER TABLE `$tabla` ADD `nombre_archivo` VARCHAR(255) NOT NULL AFTER `nombre_original`"; break;
            case 'ruta':
                $sql = "ALTER TABLE `$tabla` ADD `ruta` VARCHAR(500) NOT NULL AFTER `nombre_archivo`"; break;
            case 'tamano':
                $sql = "ALTER TABLE `$tabla` ADD `tamano` INT UNSIGNED NOT NULL DEFAULT 0 AFTER `ruta`"; break;
            case 'mime_type':
                $sql = "ALTER TABLE `$tabla` ADD `mime_type` VARCHAR(120) NULL AFTER `tamano`"; break;
            case 'descripcion':
                $sql = "ALTER TABLE `$tabla` ADD `descripcion` VARCHAR(500) NULL AFTER `mime_type`"; break;
            case 'usuario_id':
                $sql = "ALTER TABLE `$tabla` ADD `usuario_id` INT UNSIGNED NULL AFTER `descripcion`"; break;
            case 'created_at':
                $sql = "ALTER TABLE `$tabla` ADD `created_at` DATETIME NOT NULL, ADD INDEX `idx_doc_fecha` (`created_at`)"; break;
        }
        if (!empty($sql)) {
            if ($conn->query($sql)) {
                echo "<p class='ok'>✅ Columna $c agregada.</p>";
            } else {
                echo "<p class='bad'>❌ No se pudo agregar $c: " . htmlspecialchars($conn->error) . "</p>";
            }
        }
    }
} else {
    echo "<p class='ok'>✅ Todas las columnas necesarias están presentes.</p>";
}

// Crear carpeta uploads/documentos
$carpeta = __DIR__ . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'documentos';
if (!is_dir($carpeta)) {
    if (@mkdir($carpeta, 0755, true)) {
        echo "<p class='ok'>✅ Carpeta uploads/documentos creada.</p>";
    } else {
        echo "<p class='warn'>⚠️ No se pudo crear carpeta uploads/documentos. Creala manualmente y dale permisos 755/777.</p>";
    }
} else {
    echo "<p class='ok'>✅ Carpeta uploads/documentos ya existe.</p>";
}

$ht = $carpeta . DIRECTORY_SEPARATOR . '.htaccess';
if (!file_exists($ht)) {
    @file_put_contents($ht, "Options -Indexes\n<Files ~ \"\.(php|phtml|php5|phar)\">\nRequire all denied\n</Files>\n");
    echo "<p class='ok'>✅ Archivo .htaccess de seguridad creado en uploads/documentos (bloquea ejecución de PHP).</p>";
}

// Probar permisos de escritura
$prueba = $carpeta . DIRECTORY_SEPARATOR . 'write_test_' . time() . '.txt';
if (@file_put_contents($prueba, 'ok') !== false) {
    unlink($prueba);
    echo "<p class='ok'>✅ La carpeta uploads/documentos es escribible.</p>";
} else {
    echo "<p class='bad'>❌ La carpeta uploads/documentos NO es escribible. Ajusta permisos o propietario.</p>";
}

echo "<hr><p><a href='/Inventario-Tesa-Cardex/'>← Volver al inicio</a> | <a href='/Inventario-Tesa-Cardex/modules/equipos/listar.php'>Ir a Equipos</a></p></body></html>";

