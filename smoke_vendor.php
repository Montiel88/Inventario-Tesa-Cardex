<?php

require __DIR__ . '/vendor/autoload.php';

$checks = [
    'mpdf' => class_exists('Mpdf\\Mpdf'),
    'dompdf' => class_exists('Dompdf\\Dompdf'),
    'tcpdf' => class_exists('TCPDF'),
    'qrcode' => class_exists('chillerlan\\QRCode\\QRCode'),
    'phpmailer' => class_exists('PHPMailer\\PHPMailer\\PHPMailer'),
    'openai' => interface_exists('OpenAI\\Contracts\\ClientContract'),
];

echo "autoload_ok\n";
foreach ($checks as $name => $ok) {
    echo $name . ':' . ($ok ? 'ok' : 'fail') . "\n";
}

