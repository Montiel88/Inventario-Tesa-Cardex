<?php
// duckdns_update.php - ¡EN LA RAÍZ!

$domain = "iptesa";
$token = "6b45e6ba-c295-4a35-842c-bfb0a87919cd";
$log_file = __DIR__ . "/duckdns.log"; // Guarda en la raíz

// Obtener IP actual
$current_ip = file_get_contents("http://checkip.amazonaws.com/");
$current_ip = trim($current_ip);

// Actualizar DuckDNS
$update_url = "https://www.duckdns.org/update?domains={$domain}&token={$token}&ip={$current_ip}";
$response = file_get_contents($update_url);

// Guardar log
$log = date('Y-m-d H:i:s') . " - IP: {$current_ip} - Respuesta: {$response}\n";
file_put_contents($log_file, $log, FILE_APPEND);

// Mostrar resultado
echo "IP actualizada correctamente: {$current_ip}";
?>