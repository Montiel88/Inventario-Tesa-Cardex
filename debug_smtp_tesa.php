<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('memory_limit', '256M');
set_time_limit(120);

header('Content-Type: text/html; charset=UTF-8');

echo "<!doctype html><html><head><meta charset='utf-8'><title>Depuración SMTP - Inventario TESA</title>
<style>
  body{font-family:Arial,sans-serif;background:#f5f6fa;padding:20px;line-height:1.55;}
  .card{background:#fff;border:1px solid #e5e7eb;border-radius:14px;padding:18px 22px;margin-bottom:16px;box-shadow:0 2px 8px rgba(0,0,0,0.04);}
  h1{color:#5a2d8c;margin-top:0;}
  h2{color:#5a2d8c;border-bottom:2px solid #7c3aed;padding-bottom:6px;}
  .ok{color:#15803d;font-weight:700;}
  .bad{color:#b91c1c;font-weight:700;}
  .warn{color:#a16207;font-weight:700;}
  code{background:#f9fafb;border:1px solid #e5e7eb;padding:2px 6px;border-radius:6px;font-size:12px;}
  pre{background:#0f172a;color:#e2e8f0;padding:12px;border-radius:10px;overflow:auto;max-height:420px;}
  table{border-collapse:collapse;width:100%;font-size:13px;}
  th,td{border:1px solid #e5e7eb;padding:6px 10px;text-align:left;}
  th{background:#f9fafb;}
</style></head><body>";

echo "<div class='card'><h1>🧪 Depurador SMTP - Inventario TESA</h1>
<p>Este script valida todo el flujo de envío para que recuperación de contraseña funcione igual que los correos de vencidos.</p>
<p><b>Para usar la prueba 1 y 2:</b> escribe un correo de destino en la URL, ejemplo:
<code>?to=tucorreo@gmail.com</code> o <code>?to=cmontiel@estud.tesa.edu.ec</code></p>
</div>";

// ============================
// PASO 1: Conexión BD y listar usuarios
// ============================
echo "<div class='card'><h2>1. Conexión a la BD y tabla <code>usuarios</code></h2>";
try {
    require_once __DIR__ . '/config/database.php';
    echo "<p class='ok'>✅ Conectado a la BD</p>";

    $res = $conn->query("SELECT id, nombre, email, rol, created_at FROM usuarios ORDER BY id");
    if (!$res) {
        echo "<p class='bad'>❌ Error al consultar usuarios: " . $conn->error . "</p>";
    } elseif ($res->num_rows === 0) {
        echo "<p class='warn'>⚠️ No hay NINGÚN usuario en la tabla <code>usuarios</code>. Por lo tanto la recuperación de contraseña <b>nunca envía nada</b> (consulta esta tabla). Crea uno primero en Admin → Usuarios.</p>";
    } else {
        echo "<p class='ok'>✅ Hay {$res->num_rows} usuario(s) en la tabla <code>usuarios</code> (recuperación de contraseña SOLO usa estos correos):</p>";
        echo "<table><thead><tr><th>ID</th><th>Nombre</th><th>Email</th><th>Rol</th><th>Fecha Alta</th></tr></thead><tbody>";
        while ($u = $res->fetch_assoc()) {
            $rol = (int)$u['rol'] === 1 ? '<span style="color:#b91c1c;">ADMIN</span>' : '<span style="color:#1d4ed8;">LECTOR</span>';
            echo "<tr><td>{$u['id']}</td><td>" . htmlspecialchars($u['nombre']) . "</td><td>" . htmlspecialchars($u['email']) . "</td><td>$rol</td><td>" . htmlspecialchars($u['created_at']) . "</td></tr>";
        }
        echo "</tbody></table>";
    }
    $res2 = $conn->query("SELECT COUNT(*) as c FROM personas WHERE correo IS NOT NULL AND correo <> ''");
    $n = $res2 ? (int)$res2->fetch_assoc()['c'] : 0;
    echo "<p class='warn'>ℹ️ Hay $n personas con correo en la tabla <code>personas</code> (estas son las destinatarias de NOTIFICACIONES / Vencidos, PERO NO son las que pueden iniciar sesión ni recuperar contraseña).</p>";
} catch (Throwable $e) {
    echo "<p class='bad'>❌ Excepción BD: " . htmlspecialchars($e->getMessage()) . "</p>";
}
echo "</div>";

// ============================
// PASO 2: Configuracion guardada
// ============================
echo "<div class='card'><h2>2. Tabla <code>configuraciones_email</code> (BD)</h2>";
try {
    require_once __DIR__ . '/config/NotificadorEmail.php';
    $not = new NotificadorEmail($conn);
    $cfg = $not->getConfig();

    if (!$cfg) {
        echo "<p class='warn'>⚠️ No hay ninguna configuración guardada. Se usará 100% el método SMTP embebido de Gmail (el mismo que envía vencidos).</p>";
    } else {
        echo "<p class='ok'>✅ Configuración encontrada en BD. Estado switch bandeja local (smtp_modo_fallback): <b>" . ((int)($cfg['smtp_modo_fallback'] ?? 0) === 1 ? 'ACTIVADO ⚠️ hará que TODO caiga a carpeta local, no envía correos reales' : 'DESACTIVADO ✅') . "</b></p>";
        echo "<ul>";
        echo "<li>Host: " . htmlspecialchars($cfg['smtp_host'] ?? '') . ":" . (int)($cfg['smtp_port'] ?? 0) . " / enc: " . htmlspecialchars($cfg['smtp_encryption'] ?? '') . "</li>";
        echo "<li>SMTP User: " . htmlspecialchars($cfg['smtp_username'] ?? '') . "</li>";
        echo "<li>From: " . htmlspecialchars($cfg['email_from_nombre'] ?? '') . " &lt;" . htmlspecialchars($cfg['email_from'] ?? '') . "&gt;</li>";
        $pass = $not === null ? '' : (method_exists($not, 'decryptSecret') ? '' : '');
        echo "<li>SMTP Password guardada: " . (empty($cfg['smtp_password']) ? 'VACÍA' : 'CIFRADA PRESENTE (' . strlen($cfg['smtp_password']) . ' chars)') . "</li>";
        echo "</ul>";
    }
} catch (Throwable $e) {
    echo "<p class='bad'>❌ Error al leer NotificadorEmail: " . htmlspecialchars($e->getMessage()) . "</p>";
}
echo "</div>";

// ============================
// PASO 3: Destinatario
// ============================
$to = trim((string)($_GET['to'] ?? ''));
if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
    echo "<div class='card'><h2>3. Destinatario</h2>
    <p class='warn'>⚠️ No pusiste destinatario de prueba. Agrega al final de la URL <code>?to=TU_CORREO</code> para probar el envío real.</p>
    </div>";
    echo "</body></html>";
    exit;
}

echo "<div class='card'><h2>3. Destinatario de prueba</h2>
<p>Se enviará a: <code>" . htmlspecialchars($to) . "</code></p></div>";

// ============================
// PRUEBA 1: SMTP Embebido (igual que modules/correos/enviar.php)
// ============================
echo "<div class='card'><h2>4. Prueba 1 — SMTP embebido DIRECTO (igual que Correos → Enviar vencidos)</h2>";
try {
    require_once __DIR__ . '/vendor/autoload.php';
    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
    $mail->isSMTP();
    $mail->SMTPDebug = 2;
    $mail->Debugoutput = function ($str, $level) {
        echo $str . "\n";
    };
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'axelpsoriano03@gmail.com';
    $mail->Password   = 'ecourftjtjrrvfxj';  // SIN ESPACIOS (16 chars)
    $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;
    $mail->CharSet  = 'UTF-8';
    $mail->Encoding = 'base64';
    $mail->Timeout  = 25;
    $mail->SMTPAutoTLS = true;
    $mail->SMTPOptions = [
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true,
        ],
    ];
    $mail->setFrom('no-reply@tesa.edu.ec', 'Sistema de Inventario TESA (Prueba SMTP directo)');
    $mail->addAddress($to);
    $mail->isHTML(true);
    $mail->Subject = 'Prueba 1 SMTP directo - Inventario TESA';
    $mail->Body    = '<html><body><h2 style="color:#5a2d8c;">✅ Prueba 1 OK</h2>
<p>Si recibes este correo, el SMTP embebido de Gmail (el de vencidos) funciona de forma aislada.</p>
<p>Hora: ' . date('Y-m-d H:i:s') . '</p></body></html>';
    $mail->AltBody = 'Prueba 1: SMTP directo OK. Hora ' . date('Y-m-d H:i:s');
    echo "<pre>";
    $ok1 = $mail->send();
    echo "</pre>";
    echo "<p class='ok'>✅ Prueba 1 enviada con éxito (revisa bandeja de entrada / SPAM de <code>" . htmlspecialchars($to) . "</code>)</p>";
} catch (\Throwable $e) {
    echo "<p class='bad'>❌ Prueba 1 FALLÓ: " . htmlspecialchars($e->getMessage()) . "</p>";
    $debug_info = isset($mail) && isset($mail->ErrorInfo) ? $mail->ErrorInfo : '';
    if ($debug_info) echo "<p><b>PHPMailer ErrorInfo:</b> " . htmlspecialchars($debug_info) . "</p>";
}
echo "</div>";

// ============================
// PRUEBA 2: NotificadorEmail::enviarEmail (el que usa forgot_password)
// ============================
echo "<div class='card'><h2>5. Prueba 2 — NotificadorEmail::enviarEmail (el que usa Olvidaste tu contraseña)</h2>";
try {
    echo "<pre>";
    // Para depurar, agregar SMTPDebug a envio: no tiene esa opcion, asi que probamos y mostramos resultado
    echo "[NotificadorEmail] Enviando...\n";
    $res = $not->enviarEmail(
        $to,
        'Prueba 2 NotificadorEmail - Inventario TESA',
        '<html><body><h2 style="color:#5a2d8c;">✅ Prueba 2 OK</h2>
<p>Si recibes este correo, NotificadorEmail funciona (así que recuperación de contraseña también debería funcionar).</p>
<p>Hora: ' . date('Y-m-d H:i:s') . '</p></body></html>',
        'prueba_debug'
    );
    echo "Resultado crudo: " . json_encode($res, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";
    echo "</pre>";
    if (!empty($res['success'])) {
        if (!empty($res['local'])) {
            echo "<p class='warn'>⚠️ Éxito, pero <b>EN MODO LOCAL</b> (no se envió por SMTP real). El correo está guardado en: <code>" . htmlspecialchars($res['local_file'] ?? '') . "</code><br>Abre: <a target='_blank' href='" . htmlspecialchars($res['local_url'] ?? '') . "'>" . htmlspecialchars($res['local_url'] ?? '') . "</a><br>Nota: " . htmlspecialchars($res['note'] ?? '') . "</p>";
        } else {
            $via = !empty($res['via']) ? " (vía {$res['via']})" : '';
            echo "<p class='ok'>✅ Prueba 2 enviada con éxito$via (revisa bandeja de entrada / SPAM de <code>" . htmlspecialchars($to) . "</code>)</p>";
        }
    } else {
        echo "<p class='bad'>❌ Prueba 2 FALLÓ. Error NotificadorEmail: " . htmlspecialchars($res['error'] ?? 'desconocido') . "</p>";
    }
} catch (\Throwable $e) {
    echo "<p class='bad'>❌ Prueba 2 Excepción: " . htmlspecialchars($e->getMessage()) . "</p>";
}
echo "</div>";

echo "<div class='card'>
<h2>6. Conclusiones</h2>
<ul>
  <li><b>Si la Prueba 1 falla:</b> Tu red / firewall está bloqueando la salida a smtp.gmail.com:587, o la App Password de Gmail está vencida/incorrecta. Desde una red corporativa TESA podría bloquearse; en casa normalmente funciona.</li>
  <li><b>Si la Prueba 1 pasa y la Prueba 2 cae a MODO LOCAL:</b> Tu configuración guardada en la BD tiene <code>smtp_modo_fallback=1</code> (switch forzado a bandeja local). Desactívalo en Opciones avanzadas de Config. Email y vuelve a probar.</li>
  <li><b>Si las pruebas pasan pero Forgot no envía nada:</b> El correo con el que pruebas NO está en la tabla <code>usuarios</code> (lo tienes en <code>personas</code>, que es para destinatarios de notificaciones, no para login del sistema).</li>
</ul>
</div>";

echo "</body></html>";
