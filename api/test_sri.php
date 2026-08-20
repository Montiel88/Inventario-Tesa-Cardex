<?php
/**
 * debug_sri.php  — coloca en /Inventario-Tesa-Cardex/api/debug_sri.php
 * Prueba todos los endpoints conocidos del SRI y muestra la respuesta real.
 * BORRA este archivo después de usarlo.
 */
header('Content-Type: text/html; charset=utf-8');

$cedula = '1714825807';   // cambia por una cédula real válida
$ruc    = $cedula . '001';

$hJson = [
    'Accept: application/json, */*',
    'X-Requested-With: XMLHttpRequest',
    'Content-Type: application/json',
    'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/124',
    'Referer: https://srienlinea.sri.gob.ec/sri-en-linea/inicio/SriRucWeb/ConsultaRuc',
    'Origin: https://srienlinea.sri.gob.ec',
];

$pruebas = [
    ['GET',  "https://srienlinea.sri.gob.ec/sri-en-linea/SriRucWeb/ConsultaRuc/Consultas/consultaRuc?numeroRuc={$ruc}",        $hJson, ''],
    ['GET',  "https://srienlinea.sri.gob.ec/sri-en-linea/SriRucWeb/ConsultaRuc/Consultas/consultaRuc?nroRuc={$ruc}",           $hJson, ''],
    ['GET',  "https://srienlinea.sri.gob.ec/sri-en-linea/SriRucWeb/ConsultaRuc/Consultas/consultaRuc/{$ruc}",                  $hJson, ''],
    ['GET',  "https://srienlinea.sri.gob.ec/sri-en-linea/SriRucWeb/ConsultaRuc/Consultas/obtenerRuc?ruc={$ruc}",               $hJson, ''],
    ['POST', "https://srienlinea.sri.gob.ec/sri-en-linea/SriRucWeb/ConsultaRuc/Consultas/consultaRuc",                        $hJson, json_encode(['nroRuc'=>$ruc])],
    ['GET',  "https://srienlinea.sri.gob.ec/movil-servicios/api/v1.0/deudas/porNumeroRuc/{$ruc}",                              $hJson, ''],
    ['GET',  "https://srienlinea.sri.gob.ec/sri-en-linea/SriRucWeb/ConsultaRuc/Consultas/consultaRuc?contribuyente={$cedula}", $hJson, ''],
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Debug SRI</title>
<style>
body { font-family: monospace; background: #0a0014; color: #e2e8f0; padding: 20px; font-size: 13px; }
h2   { color: #f3b229; }
h3   { color: #a78bfa; margin: 20px 0 6px; }
.ok  { color: #10b981; font-weight: bold; }
.err { color: #f43f5e; font-weight: bold; }
.warn{ color: #f59e0b; font-weight: bold; }
pre  { background: #1a0a2e; border: 1px solid #5a2d8c; border-radius: 8px; padding: 12px; overflow-x: auto; max-height: 300px; color: #c4b5fd; margin: 6px 0; white-space: pre-wrap; word-break: break-all; }
.box { background: #12002a; border: 1px solid #3b0764; border-radius: 10px; padding: 14px; margin: 10px 0; }
</style>
</head>
<body>
<h2>🔬 Debug endpoints SRI — cédula: <?= $cedula ?></h2>
<p style="color:#9ca3af">Probando <?= count($pruebas) ?> endpoints...</p>

<?php foreach ($pruebas as $i => [$method, $url, $headers, $body]):
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_ENCODING       => 'gzip, deflate',
        CURLOPT_HTTPHEADER     => $headers,
        CURLOPT_HEADER         => true,    // incluir headers de respuesta
    ]);
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    }
    $raw      = curl_exec($ch);
    $code     = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $hSize    = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $cType    = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
    $time     = round(curl_getinfo($ch, CURLINFO_TOTAL_TIME) * 1000);
    curl_close($ch);

    $respBody = substr($raw, $hSize);
    $isJson   = str_contains($cType ?? '', 'json') || ($respBody && $respBody[0] === '{') || ($respBody && $respBody[0] === '[');
    $hasNombre = str_contains($respBody ?? '', 'razonSocial') ||
                 str_contains($respBody ?? '', 'nombreComercial') ||
                 str_contains($respBody ?? '', '"nombre"');

    $statusClass = $code === 200 ? ($hasNombre ? 'ok' : 'warn') : 'err';
    $statusLabel = $code === 200 ? ($hasNombre ? '✓ 200 + NOMBRE ENCONTRADO!' : '⚠ 200 pero sin nombre') : "✗ HTTP $code";
?>
<div class="box">
    <h3>#<?= $i+1 ?> — [<?= $method ?>] <?= htmlspecialchars(parse_url($url, PHP_URL_PATH)) ?>
        <?php if ($method === 'GET'): ?>?<?= htmlspecialchars(parse_url($url, PHP_URL_QUERY)) ?><?php endif; ?>
    </h3>
    <div><b>URL:</b> <span style="color:#7dd3fc"><?= htmlspecialchars($url) ?></span></div>
    <?php if ($body): ?><div><b>Body:</b> <?= htmlspecialchars($body) ?></div><?php endif; ?>
    <div><b>Status:</b> <span class="<?= $statusClass ?>"><?= $statusLabel ?></span> &nbsp;|&nbsp;
         <b>Content-Type:</b> <?= htmlspecialchars($cType ?? 'N/A') ?> &nbsp;|&nbsp;
         <b>Tiempo:</b> <?= $time ?>ms</div>
    <b>Respuesta (primeros 600 chars):</b>
    <pre><?= htmlspecialchars(substr($respBody ?: '', 0, 600)) ?></pre>
    <?php if ($isJson && $respBody): ?>
        <?php $parsed = json_decode($respBody, true); ?>
        <?php if ($parsed): ?>
        <b>JSON parseado (keys):</b>
        <pre><?= htmlspecialchars(json_encode(array_keys($parsed), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) ?></pre>
        <?php endif; ?>
    <?php endif; ?>
</div>
<?php endforeach; ?>

<p style="color:#6b7280; margin-top:30px">⚠️ Elimina este archivo (debug_sri.php) cuando termines.</p>
</body>
</html>
