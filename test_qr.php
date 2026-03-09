<?php
// Incluir la librería
require_once 'vendor/autoload.php';

use chillerlan\QRCode\QRCode;

// Datos para el QR
$texto = "Hola Mundo desde inventario_ti";
$archivo = "mi_qr.png";

// Generar QR
$qrCode = new QRCode();
$qrCode->render($texto, $archivo);

echo "✅ QR generado correctamente: <br>";
echo "<img src='$archivo' alt='QR Code'>";
?>