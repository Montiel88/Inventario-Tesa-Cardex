<?php
session_start();
require_once '../../config/database.php';
require_once '../../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Verificar sesión y permisos
$usuario_id = $_SESSION['usuario_id'] ?? $_SESSION['user_id'] ?? null;
$rol_id = $_SESSION['rol_id'] ?? $_SESSION['user_rol'] ?? null;
if (!$usuario_id || $rol_id != 1) {
    header('Location: /inventario_ti/login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: listar.php');
    exit;
}

// Recoger datos
$tipo_motivo = $_POST['tipo_motivo'] ?? 'manual';
$persona_id = $_POST['persona_id'] ?? $_POST['persona_id_manual'] ?? null;
$asignacion_id = !empty($_POST['asignacion_id']) ? intval($_POST['asignacion_id']) : null;
$componente_id = !empty($_POST['componente_id']) ? intval($_POST['componente_id']) : null;
$asunto = trim($_POST['asunto'] ?? '');
$mensaje = trim($_POST['mensaje'] ?? '');

// Validaciones básicas
if (!$persona_id || empty($asunto) || empty($mensaje)) {
    $_SESSION['error'] = 'Todos los campos obligatorios deben estar llenos.';
    header('Location: composer.php');
    exit;
}

// Obtener datos de la persona
$stmt = $conn->prepare("SELECT id, nombres, correo as email, cedula FROM personas WHERE id = ?");
$stmt->bind_param('i', $persona_id);
$stmt->execute();
$persona = $stmt->get_result()->fetch_assoc();

if (!$persona || empty($persona['email'])) {
    $_SESSION['error'] = 'La persona no tiene un email registrado.';
    header('Location: composer.php?tipo=' . urlencode($tipo_motivo) . '&persona_id=' . urlencode($persona_id));
    exit;
}

// ============================================
// CONFIGURACIÓN SMTP (¡AJUSTA AQUÍ!)
// ============================================
$mail = new PHPMailer(true);
$email_enviado = 0;
$error_email = null;

try {
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';          // Cambia por tu servidor SMTP
    $mail->SMTPAuth   = true;
      $mail->Username   = 'axelpsoriano03@gmail.com';     // Tu correo
    $mail->Password   = 'ecou rftj tjrr vfxj';        // Contraseña de aplicación
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;
    // $mail->SMTPDebug = 2; // Descomenta para depurar

    $mail->setFrom('no-reply@tesa.edu.ec', 'Sistema de Inventario TESA');
    $mail->addAddress($persona['email'], $persona['nombres']);
    $mail->isHTML(true);
    $mail->Subject = $asunto;
    $mail->Body    = convertirAHTML($mensaje, $persona, $conn, $asignacion_id, $componente_id);
    $mail->AltBody = $mensaje;

    // Procesar archivos adjuntos
    if (isset($_FILES['adjuntos']) && !empty($_FILES['adjuntos']['name'][0])) {
        $files = $_FILES['adjuntos'];
        $allowed = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf', 
                    'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                    'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    'text/plain'];
        $maxSize = 5 * 1024 * 1024; // 5 MB
        for ($i = 0; $i < count($files['name']); $i++) {
            if ($files['error'][$i] == 0) {
                $tmp_name = $files['tmp_name'][$i];
                $name = $files['name'][$i];
                $type = $files['type'][$i];
                $size = $files['size'][$i];
                if (in_array($type, $allowed) && $size <= $maxSize) {
                    $mail->addAttachment($tmp_name, $name);
                }
            }
        }
    }

    $mail->send();
    $email_enviado = 1;
} catch (Exception $e) {
    $email_enviado = 0;
    $error_email = $mail->ErrorInfo;
}

// Registrar en tabla correos_enviados (si existe)
$check_table = $conn->query("SHOW TABLES LIKE 'correos_enviados'");
if ($check_table && $check_table->num_rows > 0) {
    $stmt = $conn->prepare("INSERT INTO correos_enviados 
        (persona_id, usuario_id, asignacion_id, componente_id, tipo_motivo, asunto, mensaje, email_destino, email_enviado, error_email, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
    $stmt->bind_param('iiiissssis', 
        $persona_id, $usuario_id, $asignacion_id, $componente_id, $tipo_motivo, $asunto, $mensaje, $persona['email'], $email_enviado, $error_email
    );
    $stmt->execute();
    $correo_id = $stmt->insert_id;
    
    // Registrar notificación
    require_once '../../config/notificaciones_helper.php';
    registrar_notificacion(
        $usuario_id,
        $email_enviado ? 'success' : 'error',
        $email_enviado ? '✉️ Correo enviado' : '❌ Error al enviar',
        $email_enviado ? "Correo enviado a {$persona['nombres']}: {$asunto}" : "Error al enviar correo a {$persona['nombres']}: {$error_email}",
        $email_enviado ? "/inventario_ti/modules/correos/historial.php?id={$correo_id}" : null
    );
    
    // Registrar log de la operación
    require_once '../../includes/logs_functions.php';
    registrarLog($conn, 'Enviar correo', "Destinatario: {$persona['email']}, Asunto: {$asunto}", $usuario_id);
}

$conn->close();

// Retornar JSON para manejo con AJAX
header('Content-Type: application/json');
if ($email_enviado) {
    echo json_encode([
        'success' => true,
        'message' => 'Correo enviado exitosamente',
        'detalle' => [
            'destinatario' => $persona['email'],
            'asunto' => $asunto
        ]
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Error al enviar el correo',
        'error' => $error_email,
        'detalle' => [
            'destinatario' => $persona['email']
        ]
    ]);
}
exit;

// ============================================
// FUNCIÓN PARA CONVERTIR A HTML (igual que antes)
// ============================================
function convertirAHTML($mensaje, $persona, $conn, $asignacion_id, $componente_id) {
    // Reemplazar placeholders
    $mensaje = str_replace('[NOMBRE]', $persona['nombres'], $mensaje);
    $mensaje = str_replace('[CEDULA]', $persona['cedula'] ?? '', $mensaje);
    
    if ($asignacion_id) {
        $stmt = $conn->prepare("SELECT e.*, a.fecha_asignacion, a.fecha_estimada_devolucion, DATEDIFF(NOW(), a.fecha_asignacion) as dias FROM asignaciones a JOIN equipos e ON a.equipo_id = e.id WHERE a.id = ?");
        $stmt->bind_param('i', $asignacion_id);
        $stmt->execute();
        $equipo = $stmt->get_result()->fetch_assoc();
        if ($equipo) {
            $mensaje = str_replace('[EQUIPO]', $equipo['tipo_equipo'] . ' ' . $equipo['marca'] . ' ' . $equipo['modelo'], $mensaje);
            $mensaje = str_replace('[CÓDIGO]', $equipo['codigo_barras'], $mensaje);
            $mensaje = str_replace('[DÍAS]', $equipo['dias'] ?? 'N/A', $mensaje);
            if (!empty($equipo['fecha_estimada_devolucion'])) {
                $mensaje = str_replace('[FECHA]', date('d/m/Y', strtotime($equipo['fecha_estimada_devolucion'])), $mensaje);
            }
        }
    }
    
    if ($componente_id) {
        $stmt = $conn->prepare("SELECT c.*, e.codigo_barras, e.tipo_equipo, e.marca, e.modelo FROM componentes c JOIN equipos e ON c.equipo_id = e.id WHERE c.id = ?");
        $stmt->bind_param('i', $componente_id);
        $stmt->execute();
        $comp = $stmt->get_result()->fetch_assoc();
        if ($comp) {
            $mensaje = str_replace('[COMPONENTE]', $comp['tipo'] . ': ' . $comp['nombre_componente'], $mensaje);
            $mensaje = str_replace('[EQUIPO]', $comp['tipo_equipo'] . ' ' . $comp['marca'] . ' ' . $comp['modelo'], $mensaje);
            $mensaje = str_replace('[CÓDIGO]', $comp['codigo_barras'], $mensaje);
            if (!empty($comp['descripcion_problema'])) {
                $mensaje = str_replace('[DESCRIPCIÓN]', $comp['descripcion_problema'], $mensaje);
            }
            if (!empty($comp['fecha_reporte_problema'])) {
                $mensaje = str_replace('[FECHA]', date('d/m/Y', strtotime($comp['fecha_reporte_problema'])), $mensaje);
            }
        }
    }
    
    $parrafos = explode("\n", trim($mensaje));
    $html = "<html><head><style>body{font-family:Arial; line-height:1.6;}.container{max-width:600px;margin:0 auto;padding:20px;}.header{background:linear-gradient(135deg,#5a2d8c,#7b4ba8);color:white;padding:20px;text-align:center;border-radius:8px 8px 0 0;}.content{padding:20px;background:#f9f9f9;border:1px solid #e0e0e0;border-top:none;border-radius:0 0 8px 8px;}.footer{padding:15px;text-align:center;font-size:12px;color:#666;background:#f0f0f0;border-radius:0 0 8px 8px;}</style></head><body><div class='container'><div class='header'><h2>INSTITUTO TECNOLÓGICO SAN ANTONIO - TESA</h2><p>Departamento de Tecnología</p></div><div class='content'>";
    foreach ($parrafos as $linea) {
        if (trim($linea) !== '') {
            $html .= "<p>" . nl2br(htmlspecialchars($linea)) . "</p>";
        }
    }
    $html .= "</div><div class='footer'><p>Este es un mensaje automático del Sistema de Gestión de Inventario TESA</p><p>Por favor no responda este correo directamente</p></div></div></body></html>";
    return $html;
}
?>