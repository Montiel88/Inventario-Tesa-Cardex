<?php
// Test directo sin includes
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html><html><head><title>Test Directo</title></head><body>";
echo "<h1>🧪 Test Directo</h1><hr>";

// Test 1: Verificar ruta
echo "<h3>1. Ruta Actual:</h3>";
echo "Script: " . __FILE__ . "<br>";
echo "Directorio: " . dirname(__FILE__) . "<br><br>";

// Test 2: Intentar incluir config
echo "<h3>2. Probando incluir config:</h3>";
$config_path = dirname(dirname(__FILE__)) . '\\config\\database.php';
echo "Ruta config: $config_path<br>";
if (file_exists($config_path)) {
    echo "✅ database.php existe<br>";
    try {
        require_once $config_path;
        echo "✅ database.php incluido<br>";
        
        $conn = getDBConnection();
        if ($conn) {
            echo "✅ Conexión exitosa<br>";
            $conn->close();
        } else {
            echo "❌ Error de conexión<br>";
        }
    } catch (Exception $e) {
        echo "❌ Error: " . $e->getMessage() . "<br>";
    }
} else {
    echo "❌ database.php NO existe en esa ruta<br>";
}

// Test 3: Redireccionar
echo "<h3>3. Redirección:</h3>";
echo "<meta http-equiv='refresh' content='2;url=/inventario_ti/modules/correos/listar.php'>";
echo "Redireccionando a listar.php en 2 segundos...<br>";
echo "<a href='/inventario_ti/modules/correos/listar.php'>Click aquí si no redirecciona</a>";

echo "</body></html>";
?>
