<?php
session_start();
// Solo para debug - mostrar qué se está renderizando
ob_start();

echo "<!DOCTYPE html>
<html>
<head>
    <title>Ver HTML del Header</title>
    <style>
        body { font-family: monospace; background: #1e1e1e; color: #0f0; padding: 20px; }
        pre { background: #2d2d2d; padding: 15px; border-radius: 5px; overflow-x: auto; }
        .highlight { background: #ffff00; color: #000; }
    </style>
</head>
<body>
<h1>🔍 HTML Generado por header.php</h1>
<p>Busca la sección de 'Correos' para ver si los enlaces están correctos</p>
<hr>
<pre>";

// Incluir header pero capturar solo la parte del menú
require_once '../../config/permisos.php';

$es_admin = isset($_SESSION['user_rol']) && $_SESSION['user_rol'] == 1;

// Mostrar solo la parte relevante
echo "&lt;!-- MENÚ CORREOS --&gt;\n";
if ($es_admin) {
    echo "&lt;li class=\"nav-item dropdown\"&gt;\n";
    echo "  &lt;a class=\"nav-link dropdown-toggle tesa-nav-btn\" href=\"#\" ...&gt;📧 Correos&lt;/a&gt;\n";
    echo "  &lt;ul class=\"dropdown-menu dropdown-menu-end\"&gt;\n";
    echo "    &lt;li&gt;&lt;a class=\"dropdown-item\" href=\"/inventario_ti/modules/correos/listar.php\"&gt;📋 Gestión de Correos&lt;/a&gt;&lt;/li&gt;\n";
    echo "    &lt;li&gt;&lt;a class=\"dropdown-item\" href=\"/inventario_ti/modules/correos/composer.php\"&gt;✍️ Componer Correo&lt;/a&gt;&lt;/li&gt;\n";
    echo "    &lt;li&gt;&lt;a class=\"dropdown-item\" href=\"/inventario_ti/modules/correos/historial.php\"&gt;📜 Historial&lt;/a&gt;&lt;/li&gt;\n";
    echo "  &lt;/ul&gt;\n";
    echo "&lt;/li&gt;\n";
} else {
    echo "⚠️ NO ERES ADMIN - El menú no se muestra\n";
    echo "Tu rol: " . ($_SESSION['user_rol'] ?? 'SIN ROL') . "\n";
}

echo "</pre>

<h2>📋 Enlaces Directos de Prueba:</h2>
<ul>
    <li><a href='/inventario_ti/modules/correos/listar.php' style='display:inline-block; padding:10px 20px; background:#5a2d8c; color:white; text-decoration:none; border-radius:5px; margin:5px;'>📋 Gestión de Correos</a></li>
    <li><a href='/inventario_ti/modules/correos/composer.php' style='display:inline-block; padding:10px 20px; background:#28a745; color:white; text-decoration:none; border-radius:5px; margin:5px;'>✍️ Componer Correo</a></li>
    <li><a href='/inventario_ti/modules/correos/historial.php' style='display:inline-block; padding:10px 20px; background:#17a2b8; color:white; text-decoration:none; border-radius:5px; margin:5px;'>📜 Historial</a></li>
</ul>

<h2>ℹ️ Información de Sesión:</h2>
<pre>";
print_r($_SESSION);
echo "</pre>

<h2>🔧 Prueba con JavaScript:</h2>
<button onclick='testClick()' style='padding:10px 20px; background:#f3b229; border:none; border-radius:5px; cursor:pointer;'>Probar Click</button>
<div id='result' style='margin-top:10px; padding:10px; background:#333; color:#0f0;'></div>

<script>
function testClick() {
    document.getElementById('result').innerHTML = '✅ JavaScript funciona! Ahora probando navegación...';
    
    // Simular click en enlace
    setTimeout(() => {
        window.location.href = '/inventario_ti/modules/correos/listar.php';
    }, 1000);
}
</script>

</body>
</html>";

ob_end_flush();
?>
