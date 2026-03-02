<?php
echo "<!DOCTYPE html>
<html>
<head>
    <title>Estructura del Proyecto</title>
    <style>
        body { font-family: 'Courier New', monospace; background: #1e1e1e; color: #d4d4d4; padding: 20px; }
        .folder { color: #569cd6; font-weight: bold; }
        .file { color: #ce9178; }
        .exists { color: #6a9955; }
        .not-exists { color: #f48771; }
        .tree { margin-left: 20px; }
        .highlight { background: #2d2d2d; padding: 2px 5px; border-radius: 3px; }
    </style>
</head>
<body>
    <h1>📁 Estructura del Proyecto Inventario TESA</h1>
    <p>Ruta base: <strong>" . __DIR__ . "</strong></p>";

// Función para escanear directorios
function escanearDirectorio($dir, $nivel = 0, $maxNivel = 3) {
    if (!is_dir($dir)) return;
    if ($nivel > $maxNivel) return;
    
    $archivos = scandir($dir);
    $espacio = str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', $nivel);
    
    foreach ($archivos as $archivo) {
        if ($archivo == '.' || $archivo == '..' || $archivo == 'ver_estructura.php') continue;
        
        $ruta = $dir . '/' . $archivo;
        $esDirectorio = is_dir($ruta);
        
        echo $espacio . ($esDirectorio ? '📁 ' : '📄 ');
        
        if ($esDirectorio) {
            echo "<span class='folder'>$archivo/</span><br>";
            escanearDirectorio($ruta, $nivel + 1, $maxNivel);
        } else {
            $tamano = filesize($ruta);
            $tamanoFormateado = $tamano < 1024 ? $tamano . ' B' : round($tamano / 1024, 2) . ' KB';
            echo "<span class='file'>$archivo</span> <span style='color:#808080;'>($tamanoFormateado)</span><br>";
        }
    }
}

echo "<h2>📂 Estructura completa (3 niveles):</h2>";
echo "<div class='tree'>";
escanearDirectorio(__DIR__, 0, 3);
echo "</div>";

// Verificar específicamente la carpeta assets/img
echo "<h2>🔍 Verificación de la imagen del logo:</h2>";
$rutas_posibles = [
    __DIR__ . '/assets/img/logo-tesa.png',
    __DIR__ . '/includes/assets/img/logo-tesa.png',
    __DIR__ . '/config/assets/img/logo-tesa.png',
    __DIR__ . '/modules/assets/img/logo-tesa.png',
    __DIR__ . '/public/assets/img/logo-tesa.png',
    __DIR__ . '/img/logo-tesa.png',
    __DIR__ . '/images/logo-tesa.png'
];

echo "<table border='1' style='border-collapse: collapse; width: 100%; background: #252526;'>";
echo "<tr style='background: #333;'><th>Ruta</th><th>Estado</th><th>Tamaño</th></tr>";

foreach ($rutas_posibles as $ruta) {
    $existe = file_exists($ruta);
    $color = $existe ? '#6a9955' : '#f48771';
    $estado = $existe ? '✅ EXISTE' : '❌ NO EXISTE';
    $tamano = $existe ? round(filesize($ruta) / 1024, 2) . ' KB' : '-';
    
    echo "<tr style='background: #2d2d2d;'>";
    echo "<td style='color: #ce9178;'>" . str_replace(__DIR__, '', $ruta) . "</td>";
    echo "<td style='color: $color;'>$estado</td>";
    echo "<td>$tamano</td>";
    echo "</tr>";
}
echo "</table>";

// Verificar el contenido de la carpeta assets si existe
echo "<h2>📋 Contenido de la carpeta assets (si existe):</h2>";
$assets_path = __DIR__ . '/assets';
if (is_dir($assets_path)) {
    echo "<ul>";
    $assets_content = scandir($assets_path);
    foreach ($assets_content as $item) {
        if ($item != '.' && $item != '..') {
            $ruta_completa = $assets_path . '/' . $item;
            if (is_dir($ruta_completa)) {
                echo "<li>📁 <strong>$item/</strong></li>";
                // Mostrar contenido de subcarpetas
                if ($item == 'img') {
                    $img_path = $ruta_completa;
                    $img_content = scandir($img_path);
                    foreach ($img_content as $img) {
                        if ($img != '.' && $img != '..') {
                            $img_ruta = $img_path . '/' . $img;
                            $img_tamano = round(filesize($img_ruta) / 1024, 2) . ' KB';
                            echo "<li style='margin-left: 20px;'>📄 $img <span style='color:#808080;'>($img_tamano)</span></li>";
                        }
                    }
                }
            } else {
                echo "<li>📄 $item</li>";
            }
        }
    }
    echo "</ul>";
} else {
    echo "<p class='not-exists'>❌ La carpeta 'assets' no existe en la raíz</p>";
}

// Verificar la carpeta config/assets
echo "<h2>📋 Contenido de la carpeta config/assets (si existe):</h2>";
$config_assets_path = __DIR__ . '/config/assets';
if (is_dir($config_assets_path)) {
    echo "<ul>";
    $config_assets_content = scandir($config_assets_path);
    foreach ($config_assets_content as $item) {
        if ($item != '.' && $item != '..') {
            $ruta_completa = $config_assets_path . '/' . $item;
            if (is_dir($ruta_completa)) {
                echo "<li>📁 <strong>$item/</strong></li>";
                if ($item == 'img') {
                    $img_path = $ruta_completa;
                    $img_content = scandir($img_path);
                    foreach ($img_content as $img) {
                        if ($img != '.' && $img != '..') {
                            $img_ruta = $img_path . '/' . $img;
                            $img_tamano = round(filesize($img_ruta) / 1024, 2) . ' KB';
                            echo "<li style='margin-left: 20px;'>📄 $img <span style='color:#808080;'>($img_tamano)</span></li>";
                        }
                    }
                }
            } else {
                echo "<li>📄 $item</li>";
            }
        }
    }
    echo "</ul>";
} else {
    echo "<p class='not-exists'>❌ La carpeta 'config/assets' no existe</p>";
}

// Probar la URL de la imagen
echo "<h2>🌐 Prueba de URL de la imagen:</h2>";
$urls_posibles = [
    '/inventario_ti/assets/img/logo-tesa.png',
    '/inventario_ti/config/assets/img/logo-tesa.png',
    '/inventario_ti/includes/assets/img/logo-tesa.png',
    '/inventario_ti/modules/assets/img/logo-tesa.png',
    '/inventario_ti/img/logo-tesa.png'
];

foreach ($urls_posibles as $url) {
    echo "<div>";
    echo "<span style='color:#ce9178;'>$url</span> → ";
    echo "<a href='$url' target='_blank' style='color:#569cd6;'>Ver imagen</a>";
    echo "</div>";
}

// Mostrar el código actual del header para la imagen
echo "<h2>🔧 Código actual en header.php para el logo:</h2>";
$header_file = __DIR__ . '/includes/header.php';
if (file_exists($header_file)) {
    $header_content = file_get_contents($header_file);
    // Extraer la línea del logo
    preg_match('/<img[^>]*logo-tesa\.png[^>]*>/', $header_content, $matches);
    if (!empty($matches)) {
        echo "<div class='highlight'>" . htmlspecialchars($matches[0]) . "</div>";
    } else {
        echo "<p class='not-exists'>❌ No se encontró la línea del logo en header.php</p>";
    }
}

// Recomendación final
echo "<h2>💡 Recomendación:</h2>";
echo "<div style='background: #007acc; color: white; padding: 15px; border-radius: 5px;'>";
echo "<strong>Basado en tu prueba anterior, la imagen está en: <span style='background: #005a9e; padding: 3px 8px; border-radius: 3px;'>config/assets/img/logo-tesa.png</span></strong><br><br>";
echo "En tu header.php, usa esta ruta:<br>";
echo "<code style='background: #1e1e1e; color: #ce9178; padding: 5px; display: inline-block; margin-top: 5px;'>&lt;img src='/inventario_ti/config/assets/img/logo-tesa.png' alt='TESA'&gt;</code><br><br>";
echo "O si prefieres mantener la estructura estándar, crea la carpeta <strong>assets/img/</strong> en la raíz y copia la imagen allí.";
echo "</div>";

echo "</body>
</html>";
?>