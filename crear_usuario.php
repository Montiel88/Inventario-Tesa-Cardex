<?php
require_once 'config/database.php';

$password = 'Admin123!';
$hash = password_hash($password, PASSWORD_DEFAULT);

echo "Hash generado: " . $hash . "<br>";

$sql = "INSERT INTO usuarios (nombre, email, password, rol) VALUES 
        ('Administrador TESA', 'admin@tesa.edu.ec', '$hash', 'admin')";

if ($conn->query($sql)) {
    echo "✅ Usuario creado correctamente";
} else {
    echo "❌ Error: " . $conn->error;
}
?>