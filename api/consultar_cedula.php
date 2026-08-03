<?php
// api/consultar_cedula.php
header('Content-Type: application/json');

if (!isset($_GET['cedula']) || strlen($_GET['cedula']) != 10) {
    echo json_encode(['ok' => false, 'error' => 'Cédula inválida']);
    exit;
}

$cedula = preg_replace('/[^0-9]/', '', $_GET['cedula']);
$cedula = substr($cedula, 0, 10);
$cedula = str_pad($cedula, 10, '0', STR_PAD_LEFT);
$ruc = $cedula . '001'; // El SRI consulta por RUC (13 dígitos: cédula + 001)

function sri_request($method, $url, $headers, $body = '')
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 12,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_ENCODING => 'gzip, deflate',
        CURLOPT_HTTPHEADER => $headers,
    ]);
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    }
    $resp = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $ctype = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
    curl_close($ch);
    return [$code, $ctype, $resp];
}

function extraer_nombre($data)
{
    if (!is_array($data)) return null;
    $directo = $data['razonSocial'] ?? $data['nombreComercial'] ?? null;
    if (is_string($directo) && trim($directo) !== '') return trim($directo);
    foreach (['contribuyente', 'datosContribuyente', 'identificacion', 'resultado'] as $k) {
        if (isset($data[$k]) && is_array($data[$k])) {
            $n = extraer_nombre($data[$k]);
            if ($n) return $n;
        }
    }
    foreach ($data as $v) {
        if (is_array($v)) {
            $n = extraer_nombre($v);
            if ($n) return $n;
        }
    }
    return null;
}

$headers = [
    'Accept: application/json, */*',
    'X-Requested-With: XMLHttpRequest',
    'Content-Type: application/json',
    'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/124',
    'Referer: https://srienlinea.sri.gob.ec/sri-en-linea/inicio/SriRucWeb/ConsultaRuc',
    'Origin: https://srienlinea.sri.gob.ec',
];

$pruebas = [
    ['GET',  "https://srienlinea.sri.gob.ec/sri-en-linea/SriRucWeb/ConsultaRuc/Consultas/consultaRuc?numeroRuc={$ruc}", ''],
    ['GET',  "https://srienlinea.sri.gob.ec/sri-en-linea/SriRucWeb/ConsultaRuc/Consultas/consultaRuc?nroRuc={$ruc}", ''],
    ['POST', "https://srienlinea.sri.gob.ec/sri-en-linea/SriRucWeb/ConsultaRuc/Consultas/consultaRuc", json_encode(['nroRuc' => $ruc])],
    ['GET',  "https://srienlinea.sri.gob.ec/sri-en-linea/SriRucWeb/ConsultaRuc/Consultas/consultaRuc?contribuyente={$cedula}", ''],
];

$ultimo_code = null;
$ultimo_body = null;

foreach ($pruebas as [$method, $url, $body]) {
    [$code, $ctype, $resp] = sri_request($method, $url, $headers, $body);
    $ultimo_code = $code;
    $ultimo_body = $resp;
    if ($code !== 200 || !$resp) continue;
    $data = json_decode($resp, true);
    if (!is_array($data)) continue;
    $nombre = extraer_nombre($data);
    if ($nombre) {
        echo json_encode(['ok' => true, 'nombre' => $nombre, 'ruc' => $ruc]);
        exit;
    }
}

if ($ultimo_code === 200 && $ultimo_body) {
    echo json_encode(['ok' => false, 'error' => 'No existe un RUC asociado a esta cédula en el SRI. Ingresa el nombre manualmente.']);
    exit;
}

echo json_encode(['ok' => false, 'error' => 'Error de conexión con el SRI']);
?>
