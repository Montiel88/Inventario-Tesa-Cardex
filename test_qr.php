<?php
// Incluir la librería
require_once 'vendor/phpqrcode/qrlib.php';

// Datos para el QR
$texto = "Hola Mundo desde inventario_ti";
$archivo = "mi_qr.png";

// Generar QR
QRcode::png($texto, $archivo);

echo "✅ QR generado correctamente: <br>";
echo "<img src='$archivo' alt='QR Code'>";
?>