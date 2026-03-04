<?php
require_once 'config/database.php';

$email = 'admin@tesa.edu.ec';
$password = 'Admin123!';

echo "<h2>🔍 Prueba de login directa</h2>";

// Buscar usuario por email
$sql = "SELECT * FROM usuarios WHERE email = '$email'";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    echo "✅ Usuario encontrado:<br>";
    echo "ID: " . $user['id'] . "<br>";
    echo "Nombre: " . $user['nombre'] . "<br>";
    echo "Email: " . $user['email'] . "<br>";
    echo "Rol: " . $user['rol'] . "<br>";
    echo "Hash en BD: " . $user['password'] . "<br><br>";
    
    if (password_verify($password, $user['password'])) {
        echo "✅ <strong>CONTRASEÑA CORRECTA</strong><br>";
        echo "El login DEBERÍA funcionar.";
    } else {
        echo "❌ <strong>CONTRASEÑA INCORRECTA</strong><br>";
        echo "El hash no coincide con la contraseña proporcionada.";
    }
} else {
    echo "❌ Usuario no encontrado con email: $email";
}
?>