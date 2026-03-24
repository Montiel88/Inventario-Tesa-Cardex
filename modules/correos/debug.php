<?php
// Test rápido - sin session start para evitar headers
echo "<!DOCTYPE html><html><head><title>Debug</title></head><body>";
echo "<h1>🔍 Debug Sistema Correos</h1><hr>";

// Test 1: Archivos existen
echo "<h3>1. Archivos:</h3>";
$archivos = [
    'listar.php' => 'C:\\xampp\\htdocs\\inventario_ti\\modules\\correos\\listar.php',
    'composer.php' => 'C:\\xampp\\htdocs\\inventario_ti\\modules\\correos\\composer.php',
    'historial.php' => 'C:\\xampp\\htdocs\\inventario_ti\\modules\\correos\\historial.php'
];

foreach ($archivos as $nombre => $path) {
    if (file_exists($path)) {
        $size = filesize($path);
        echo "✅ <strong>$nombre</strong> existe ($size bytes)<br>";
    } else {
        echo "❌ <strong>$nombre</strong> NO existe<br>";
    }
}

// Test 2: Enlaces
echo "<h3 class='mt-4'>2. Prueba Enlaces Directos:</h3>";
echo "<div style='display: flex; gap: 10px; flex-wrap: wrap;'>";
echo "<a href='/inventario_ti/modules/correos/listar.php' style='padding: 10px 20px; background: #5a2d8c; color: white; text-decoration: none; border-radius: 5px;'>📋 Listar</a>";
echo "<a href='/inventario_ti/modules/correos/composer.php' style='padding: 10px 20px; background: #28a745; color: white; text-decoration: none; border-radius: 5px;'>✍️ Composer</a>";
echo "<a href='/inventario_ti/modules/correos/historial.php' style='padding: 10px 20px; background: #17a2b8; color: white; text-decoration: none; border-radius: 5px;'>📜 Historial</a>";
echo "</div>";

// Test 3: JavaScript
echo "<h3 class='mt-4'>3. Test JavaScript:</h3>";
echo "<button id='testBtn' onclick='alert(\"✅ JavaScript funciona!\")' style='padding: 10px 20px; background: #f3b229; border: none; border-radius: 5px; cursor: pointer;'>Click para test JS</button>";
echo "<div id='jsOutput' style='margin-top: 10px; padding: 10px; background: #f0f0f0; border-radius: 5px;'></div>";

echo "<script>
document.getElementById('jsOutput').innerHTML = '✅ DOM cargado correctamente<br>' +
    '✅ Bootstrap: ' + (typeof bootstrap !== 'undefined' ? 'Cargado' : 'NO cargado') + '<br>' +
    '✅ jQuery: ' + (typeof jQuery !== 'undefined' ? 'Cargado' : 'NO cargado');
</script>";

echo "</body></html>";
?>
