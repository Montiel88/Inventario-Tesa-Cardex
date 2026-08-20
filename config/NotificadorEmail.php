<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/validaciones.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class NotificadorEmail {
    private $config = null;
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
        $this->cargarConfiguracion();
    }

    private function ensureConfigTable() {
        $sql = "CREATE TABLE IF NOT EXISTS configuraciones_email (
            id INT(11) NOT NULL PRIMARY KEY,
            smtp_host VARCHAR(255) NULL,
            smtp_port INT(11) NULL,
            smtp_username VARCHAR(255) NULL,
            smtp_password TEXT NULL,
            smtp_encryption VARCHAR(10) NULL,
            email_from VARCHAR(255) NULL,
            email_from_nombre VARCHAR(255) NULL,
            notificar_asignacion TINYINT(1) NOT NULL DEFAULT 1,
            notificar_devolucion TINYINT(1) NOT NULL DEFAULT 1,
            notificar_vencimiento TINYINT(1) NOT NULL DEFAULT 1,
            dias_antes_vencimiento INT(11) NOT NULL DEFAULT 3,
            smtp_modo_fallback TINYINT(1) NOT NULL DEFAULT 0,
            activo TINYINT(1) NOT NULL DEFAULT 1,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
        $this->conn->query($sql);

        // Añadir columnas que pudieran faltar en tablas existentes
        $cols = $this->conn->query("SHOW COLUMNS FROM configuraciones_email LIKE 'smtp_modo_fallback'");
        if ($cols && $cols->num_rows === 0) {
            $this->conn->query("ALTER TABLE configuraciones_email ADD COLUMN smtp_modo_fallback TINYINT(1) NOT NULL DEFAULT 0 AFTER dias_antes_vencimiento");
        }
    }

    private function cryptoKey() {
        $key = getenv('TESA_APP_KEY') ?: getenv('APP_KEY') ?: '';
        if (!$key) {
            $key = __DIR__ . 'inventario_ti' . php_uname();
        }
        return hash('sha256', $key, true);
    }

    private function encryptSecret($plain) {
        $plain = (string)$plain;
        if ($plain === '') return '';
        $iv = random_bytes(16);
        $ciphertext = openssl_encrypt($plain, 'AES-256-CBC', $this->cryptoKey(), OPENSSL_RAW_DATA, $iv);
        if ($ciphertext === false) return '';
        return 'enc:' . base64_encode($iv . $ciphertext);
    }

    private function decryptSecret($value) {
        $value = (string)$value;
        if ($value === '') return '';
        if (str_starts_with($value, 'enc:')) {
            $raw = base64_decode(substr($value, 4), true);
            if ($raw === false || strlen($raw) < 17) return '';
            $iv = substr($raw, 0, 16);
            $ciphertext = substr($raw, 16);
            $plain = openssl_decrypt($ciphertext, 'AES-256-CBC', $this->cryptoKey(), OPENSSL_RAW_DATA, $iv);
            return $plain === false ? '' : (string)$plain;
        }
        return $value;
    }
    
    private function cargarConfiguracion() {
        $this->ensureConfigTable();
        $sql = "SELECT * FROM configuraciones_email WHERE activo = 1 LIMIT 1";
        $result = $this->conn->query($sql);
        if ($result && $result->num_rows > 0) {
            $this->config = $result->fetch_assoc();
        }
    }
    
    public function estaActivo() {
        return $this->config !== null;
    }
    
    public function getConfig() {
        return $this->config;
    }
    
    public function guardarConfiguracion($datos) {
        $this->ensureConfigTable();

        $smtp_host = trim((string)($datos['smtp_host'] ?? ''));
        $smtp_port = intval($datos['smtp_port'] ?? 0);
        $smtp_username = trim((string)($datos['smtp_username'] ?? ''));
        $email_from = trim((string)($datos['email_from'] ?? ''));
        $smtp_encryption = strtolower(trim((string)($datos['smtp_encryption'] ?? 'tls')));
        $modo_fallback = intval($datos['smtp_modo_fallback'] ?? 0) ? 1 : 0;

        if ($modo_fallback !== 1) {
            if ($smtp_host === '' || $smtp_port <= 0) {
                $this->config = null;
                return ['ok' => false, 'error' => 'Servidor SMTP (Host) y Puerto son obligatorios (o activa el Modo bandeja local para pruebas).'];
            }

            if (strpos($smtp_host, '@') !== false || filter_var($smtp_host, FILTER_VALIDATE_EMAIL)) {
                $this->config = null;
                return ['ok' => false, 'error' => 'Host SMTP no es un correo electrónico. Ejemplos válidos: mail.tesa.edu.ec, smtp.tesa.edu.ec.'];
            }
            if (preg_match('/\s/', $smtp_host)) {
                $this->config = null;
                return ['ok' => false, 'error' => 'Host SMTP no puede contener espacios.'];
            }
        } else {
            if ($smtp_host === '' || $smtp_port <= 0 || strpos($smtp_host, '@') !== false || !in_array($smtp_encryption, ['tls','ssl'], true)) {
                $smtp_host = 'mail.tesa.edu.ec';
                $smtp_port = 587;
                $smtp_encryption = 'tls';
            }
        }

        if (!in_array($smtp_encryption, ['tls', 'ssl', 'none', 'starttls'], true)) {
            $smtp_encryption = 'tls';
        }

        if ($modo_fallback !== 1 && $smtp_host !== '') {
            $ip = @gethostbyname($smtp_host);
            if ($ip === false || $ip === '' || $ip === $smtp_host) {
                $dns_ok = false;
                if (function_exists('dns_get_record')) {
                    $rr = @dns_get_record($smtp_host, DNS_A + DNS_AAAA + DNS_CNAME);
                    $dns_ok = is_array($rr) && count($rr) > 0;
                }
                if (!$dns_ok) {
                    $this->config = null;
                    return ['ok' => false, 'error' => 'Host SMTP no es resoluble (no existe en DNS). Verifica que sea el host real del instituto, ej. mail.tesa.edu.ec.'];
                }
            }
        }

        if (!filter_var($smtp_username, FILTER_VALIDATE_EMAIL)) {
            $this->config = null;
            return ['ok' => false, 'error' => 'El Usuario SMTP debe ser un correo válido.'];
        }
        if (!validarDominioEmailTESA($smtp_username)) {
            $this->config = null;
            return ['ok' => false, 'error' => 'Usuario SMTP no permitido. ' . tesa_mensaje_dominios_email()];
        }

        if ($email_from !== '' && !filter_var($email_from, FILTER_VALIDATE_EMAIL)) {
            $this->config = null;
            return ['ok' => false, 'error' => 'El Email Remitente debe ser un correo válido.'];
        }
        if ($email_from !== '' && !validarDominioEmailTESA($email_from)) {
            $this->config = null;
            return ['ok' => false, 'error' => 'Email Remitente no permitido. ' . tesa_mensaje_dominios_email()];
        }

        $rawPass = (string)($datos['smtp_password'] ?? '');

        if ($modo_fallback !== 1) {
            if ($rawPass === '' && $this->config && isset($this->config['smtp_password'])) {
                $password = (string)$this->config['smtp_password'];
            } else {
                if ($rawPass === '') {
                    $this->config = null;
                    return ['ok' => false, 'error' => 'La Contraseña SMTP es obligatoria al crear la configuración (o activa el Modo bandeja local para pruebas).'];
                }
                $password = $this->encryptSecret($rawPass);
            }
        } else {
            if ($this->config && isset($this->config['smtp_password']) && $rawPass === '') {
                $password = (string)$this->config['smtp_password'];
            } else {
                $password = $rawPass === '' ? '' : $this->encryptSecret($rawPass);
            }
        }
        
        $email_from_nombre = trim((string)($datos['email_from_nombre'] ?? 'Sistema de Inventario TESA'));
        $notificar_asignacion = intval($datos['notificar_asignacion'] ?? 1) ? 1 : 0;
        $notificar_devolucion = intval($datos['notificar_devolucion'] ?? 1) ? 1 : 0;
        $notificar_vencimiento = intval($datos['notificar_vencimiento'] ?? 1) ? 1 : 0;
        $dias_antes_vencimiento = max(1, min(30, intval($datos['dias_antes_vencimiento'] ?? 3)));

        $sql = "INSERT INTO configuraciones_email 
                (id, smtp_host, smtp_port, smtp_username, smtp_password, smtp_encryption, 
                 email_from, email_from_nombre, notificar_asignacion, notificar_devolucion, 
                 notificar_vencimiento, dias_antes_vencimiento, smtp_modo_fallback, activo, created_at)
                VALUES (1, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, NOW())
                ON DUPLICATE KEY UPDATE
                smtp_host = VALUES(smtp_host), smtp_port = VALUES(smtp_port),
                smtp_username = VALUES(smtp_username), smtp_password = VALUES(smtp_password),
                smtp_encryption = VALUES(smtp_encryption), email_from = VALUES(email_from),
                email_from_nombre = VALUES(email_from_nombre), notificar_asignacion = VALUES(notificar_asignacion),
                notificar_devolucion = VALUES(notificar_devolucion), notificar_vencimiento = VALUES(notificar_vencimiento),
                dias_antes_vencimiento = VALUES(dias_antes_vencimiento), smtp_modo_fallback = VALUES(smtp_modo_fallback),
                activo = VALUES(activo),
                updated_at = NOW()";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('sisssssiiiii',
            $smtp_host,
            $smtp_port,
            $smtp_username,
            $password,
            $smtp_encryption,
            $email_from,
            $email_from_nombre,
            $notificar_asignacion,
            $notificar_devolucion,
            $notificar_vencimiento,
            $dias_antes_vencimiento,
            $modo_fallback
        );
        
        $ok = $stmt->execute();
        $this->cargarConfiguracion();
        if ($ok) {
            return ['ok' => true, 'error' => null];
        }
        return ['ok' => false, 'error' => $stmt->error ?: 'No se pudo guardar la configuración.'];
    }
    
    public function enviarEmail($destino, $asunto, $mensaje, $tipo = 'general') {
        $configOk = (bool)$this->config;
        $fallback_on = false;
        $smtp_host = '';
        $smtp_port = 0;
        $smtp_user = '';
        $smtp_pass = '';
        $smtp_enc  = 'tls';
        $from      = '';
        $from_name = 'Sistema de Inventario TESA';

        if ($configOk) {
            $smtp_host = (string)($this->config['smtp_host'] ?? '');
            $smtp_port = (int)($this->config['smtp_port'] ?? 0);
            $smtp_user = (string)($this->config['smtp_username'] ?? '');
            $smtp_pass = $this->decryptSecret($this->config['smtp_password'] ?? '');
            $smtp_enc  = strtolower((string)($this->config['smtp_encryption'] ?? 'tls'));
            $from      = (string)($this->config['email_from'] ?? $smtp_user);
            $from_name = (string)($this->config['email_from_nombre'] ?? 'Sistema de Inventario TESA');
            $fallback_on = (int)($this->config['smtp_modo_fallback'] ?? 0) === 1;
        }

        if ($fallback_on) {
            return $this->guardarCorreoEnBandejaLocal($destino, $asunto, $mensaje, $smtp_user, $from, $from_name, $tipo);
        }

        $configCompleta = $configOk
            && $smtp_host !== ''
            && $smtp_port > 0
            && $smtp_user !== ''
            && $smtp_pass !== '';

        if (!$configCompleta) {
            $fallback_res = $this->enviarConSmtpEmbebido($destino, $asunto, $mensaje, $tipo, $smtp_user, $from, $from_name, false);
            if (!empty($fallback_res['success'])) {
                return $fallback_res;
            }
            $error = 'Configuración SMTP incompleta.';
            if (!empty($fallback_res['error'])) {
                $error .= ' Y el método de envío embebido falló: ' . $fallback_res['error'];
            }
            return ['success' => false, 'error' => $error];
        }

        if (!validarDominioEmailTESA($smtp_user)) {
            // Forzamos fallback si puso un dominio no permitido en la config (lo ignora y usa el hardcodeado)
            $fallback_res = $this->enviarConSmtpEmbebido($destino, $asunto, $mensaje, $tipo, $smtp_user, $from, $from_name, false);
            if (!empty($fallback_res['success'])) {
                return $fallback_res;
            }
        }
        if ($from !== '' && !validarDominioEmailTESA($from)) {
            $from = $smtp_user;
        }

        if (!validarDominioEmailTESA($smtp_user)) {
            return ['success' => false, 'error' => 'Usuario SMTP no institucional. ' . tesa_mensaje_dominios_email()];
        }
        if ($from !== '' && !validarDominioEmailTESA($from)) {
            return ['success' => false, 'error' => 'Email Remitente no institucional. ' . tesa_mensaje_dominios_email()];
        }

        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = $smtp_host;
            $mail->SMTPAuth = true;
            $mail->Username = $smtp_user;
            $mail->Password = $smtp_pass;
            $mail->Port = $smtp_port;
            if ($smtp_enc === 'ssl') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            } elseif ($smtp_enc === 'tls' || $smtp_enc === 'starttls') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            } else {
                $mail->SMTPSecure = '';
                $mail->SMTPAutoTLS = false;
            }

            $mail->CharSet = 'UTF-8';
            $mail->Encoding = 'base64';
            $mail->Timeout = 20;
            $mail->SMTPOptions = [
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true,
                ],
            ];
            $mail->setFrom($from, $from_name);
            $mail->addAddress($destino);
            $mail->isHTML(true);
            $mail->Subject = $asunto;
            $mail->Body = $mensaje;
            $mail->AltBody = strip_tags($mensaje);

            $mail->send();
            return ['success' => true, 'error' => null];
        } catch (Exception $e) {
            $error_info = $mail->ErrorInfo ?: $e->getMessage();
            $hint = '';
            if (stripos($error_info, 'could not connect') !== false
                || stripos($error_info, 'connection') !== false
                || stripos($error_info, 'connect') !== false) {
                $hint = ' — Falló el SMTP configurado. Probando método de envío embebido del sistema...';
            }
            // ═══════════════════════════════════════════════════════════
            // FALLBACK: usar el SMTP embebido que funciona (el mismo que
            // en modules/correos/enviar.php para notificaciones vencidas)
            // ═══════════════════════════════════════════════════════════
            $fallback_res = $this->enviarConSmtpEmbebido($destino, $asunto, $mensaje, $tipo, $smtp_user, $from, $from_name, $fallback_on);
            if (!empty($fallback_res['success'])) {
                return $fallback_res;
            }
            if (!empty($fallback_res['error'])) {
                $error_info .= ' | [Fallback] ' . $fallback_res['error'];
            }
            return ['success' => false, 'error' => $error_info . $hint, 'fallback' => 'file'];
        }
    }

    private function enviarConSmtpEmbebido($destino, $asunto, $mensaje, $tipo, $smtp_user, $from, $from_name, $fallback_on)
    {
        if ($fallback_on) {
            return $this->guardarCorreoEnBandejaLocal($destino, $asunto, $mensaje, $smtp_user, $from, $from_name, $tipo);
        }

        require_once dirname(__DIR__) . '/vendor/autoload.php';
        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'axelpsoriano03@gmail.com';
            $mail->Password   = 'ecourftjtjrrvfxj';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;

            $mail->CharSet  = 'UTF-8';
            $mail->Encoding = 'base64';
            $mail->Timeout  = 20;
            $mail->SMTPAutoTLS = true;
            $mail->SMTPOptions = [
                'ssl' => [
                    'verify_peer'       => false,
                    'verify_peer_name'  => false,
                    'allow_self_signed' => true,
                ],
            ];

            $mail->setFrom(($from && filter_var($from, FILTER_VALIDATE_EMAIL) ? $from : 'no-reply@tesa.edu.ec'), $from_name ?: 'Sistema de Inventario TESA');
            $mail->addAddress($destino);
            $mail->isHTML(true);
            $mail->Subject = $asunto;
            $mail->Body    = $mensaje;
            $mail->AltBody = strip_tags($mensaje);

            $mail->send();
            return ['success' => true, 'error' => null, 'via' => 'embedded_smtp'];
        } catch (Exception $e) {
            $info = $mail->ErrorInfo ?: $e->getMessage();
            if ($fallback_on === false) {
                $local = $this->guardarCorreoEnBandejaLocal($destino, $asunto, $mensaje, $smtp_user, $from, $from_name, $tipo);
                if (!empty($local['success'])) {
                    $local['note'] = (!empty($local['note']) ? $local['note'] . ' ' : '') . '(SMTP embebido falló: ' . mb_substr($info, 0, 120) . ')';
                    return $local;
                }
            }
            return ['success' => false, 'error' => $info];
        }
    }

    private function guardarCorreoEnBandejaLocal($destino, $asunto, $mensaje, $smtp_user, $from, $from_name, $tipo)
    {
        $dir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'tmp' . DIRECTORY_SEPARATOR . 'mailbox';
        if (!is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }
        $safe = function ($txt) {
            return preg_replace('/[^A-Za-z0-9._-]+/', '_', (string)$txt);
        };
        $ts = date('Ymd_His');
        $file = $dir . DIRECTORY_SEPARATOR . $ts . '_' . $safe($tipo) . '_' . $safe($destino) . '.html';
        $cabeceras = "From: " . $from_name . " <" . $from . ">\r\n"
            . "To: " . $destino . "\r\n"
            . "Reply-To: " . $smtp_user . "\r\n"
            . "Subject: =?UTF-8?B?" . base64_encode($asunto) . "?=\r\n"
            . "Content-Type: text/html; charset=UTF-8\r\n"
            . "X-TESA-Modo: local-fallback\r\n";
        $html = "<!doctype html><html><head><meta charset='UTF-8'><title>" . htmlspecialchars($asunto, ENT_QUOTES, 'UTF-8') . "</title></head>"
            . "<body style='font-family:Arial,sans-serif;background:#f5f6fa;padding:24px;'>"
            . "<div style='max-width:720px;margin:0 auto;background:#fff;border:1px solid #e5e7eb;border-radius:14px;padding:20px;'>"
            . "<div style='margin-bottom:14px;color:#374151;font-size:13px;'>"
            . "<b>CORREO EN MODO LOCAL (NO ENVIADO REALMENTE POR SMTP)</b><br>"
            . "Este archivo se genera para que puedas probar la recuperación sin red SMTP institucional. "
            . "Cuándo estés en TESA / VPN activa, apaga el modo bandeja local y el sistema sí envía correos reales a las bandejas."
            . "</div>"
            . "<div style='white-space:pre-wrap;background:#f9fafb;border:1px dashed #d1d5db;border-radius:10px;padding:12px;color:#111827;font-size:12px;margin-bottom:16px;'>"
            . htmlspecialchars($cabeceras, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
            . "</div>"
            . $mensaje
            . "</div></body></html>";
        $wrote = @file_put_contents($file, $html);
        if ($wrote === false) {
            return ['success' => false, 'error' => 'No se pudo escribir en carpeta local tmp/mailbox. Verifica permisos.'];
        }
        $webPath = '/Inventario-Tesa-Cardex/tmp/mailbox/' . basename($file);
        return [
            'success' => true,
            'error' => null,
            'local' => true,
            'local_file' => $file,
            'local_url' => $webPath,
            'note' => 'Modo local: el correo se guardó en tu disco (no se envió por SMTP). Abre el enlace para verlo.',
        ];
    }
    
    public function notificarAsignacion($persona, $equipo) {
        if (!$this->config || !$this->config['notificar_asignacion']) {
            return false;
        }
        
        if (empty($persona['email'])) {
            return false;
        }
        
        $asunto = "Nuevo equipo asignado - {$equipo['codigo_barras']}";
        $mensaje = "
        <html>
        <head>
            <meta charset='UTF-8'>
            <meta http-equiv='Content-Type' content='text/html; charset=UTF-8'>
            <style>
                body { font-family: Arial, sans-serif; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #5a2d8c; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; background: #f9f9f9; }
                .footer { padding: 10px; text-align: center; font-size: 12px; color: #666; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>INSTITUTO TECNOLÓGICO SAN ANTONIO - TESA</h2>
                </div>
                <div class='content'>
                    <h3>Nuevo Equipo Asignado</h3>
                    <p>Hola <strong>{$persona['nombres']}</strong>,</p>
                    <p>Se te ha asignado un nuevo equipo:</p>
                    <table style='width: 100%; border-collapse: collapse;'>
                        <tr>
                            <td style='padding: 8px; border: 1px solid #ddd;'><strong>Código:</strong></td>
                            <td style='padding: 8px; border: 1px solid #ddd;'>{$equipo['codigo_barras']}</td>
                        </tr>
                        <tr>
                            <td style='padding: 8px; border: 1px solid #ddd;'><strong>Tipo:</strong></td>
                            <td style='padding: 8px; border: 1px solid #ddd;'>{$equipo['tipo_equipo']}</td>
                        </tr>
                        <tr>
                            <td style='padding: 8px; border: 1px solid #ddd;'><strong>Marca:</strong></td>
                            <td style='padding: 8px; border: 1px solid #ddd;'>{$equipo['marca']}</td>
                        </tr>
                        <tr>
                            <td style='padding: 8px; border: 1px solid #ddd;'><strong>Modelo:</strong></td>
                            <td style='padding: 8px; border: 1px solid #ddd;'>{$equipo['modelo']}</td>
                        </tr>
                    </table>
                    <p>Por favor, revisa el equipo y reporta cualquier anomalía.</p>
                </div>
                <div class='footer'>
                    Sistema de Gestión de Inventario TESA
                </div>
            </div>
        </body>
        </html>
        ";
        
        return $this->enviarEmail($persona['email'], $asunto, $mensaje, 'asignacion');
    }
    
    public function notificarDevolucion($persona, $equipo) {
        if (!$this->config || !$this->config['notificar_devolucion']) {
            return false;
        }
        
        if (empty($persona['email'])) {
            return false;
        }
        
        $asunto = "Equipo devuelto - {$equipo['codigo_barras']}";
        $mensaje = "
        <html>
        <head>
            <meta charset='UTF-8'>
            <meta http-equiv='Content-Type' content='text/html; charset=UTF-8'>
            <style>
                body { font-family: Arial, sans-serif; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #28a745; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; background: #f9f9f9; }
                .footer { padding: 10px; text-align: center; font-size: 12px; color: #666; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>INSTITUTO TECNOLÓGICO SAN ANTONIO - TESA</h2>
                </div>
                <div class='content'>
                    <h3>Equipo Devuelto</h3>
                    <p>Hola <strong>{$persona['nombres']}</strong>,</p>
                    <p>Has devuelto el siguiente equipo:</p>
                    <table style='width: 100%; border-collapse: collapse;'>
                        <tr>
                            <td style='padding: 8px; border: 1px solid #ddd;'><strong>Código:</strong></td>
                            <td style='padding: 8px; border: 1px solid #ddd;'>{$equipo['codigo_barras']}</td>
                        </tr>
                        <tr>
                            <td style='padding: 8px; border: 1px solid #ddd;'><strong>Tipo:</strong></td>
                            <td style='padding: 8px; border: 1px solid #ddd;'>{$equipo['tipo_equipo']}</td>
                        </tr>
                    </table>
                    <p>Gracias por cuidar el equipo.</p>
                </div>
                <div class='footer'>
                    Sistema de Gestión de Inventario TESA
                </div>
            </div>
        </body>
        </html>
        ";
        
        return $this->enviarEmail($persona['email'], $asunto, $mensaje, 'devolucion');
    }
    
    public function notificarVencimiento($persona, $equipo, $dias_vencimiento) {
        if (!$this->config || !$this->config['notificar_vencimiento']) {
            return false;
        }
        
        if (empty($persona['email'])) {
            return false;
        }
        
        $asunto = "Recordatorio: Préstamo próximo a vencer - {$equipo['codigo_barras']}";
        $mensaje = "
        <html>
        <head>
            <meta charset='UTF-8'>
            <meta http-equiv='Content-Type' content='text/html; charset=UTF-8'>
            <style>
                body { font-family: Arial, sans-serif; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #ffc107; color: #333; padding: 20px; text-align: center; }
                .content { padding: 20px; background: #f9f9f9; }
                .footer { padding: 10px; text-align: center; font-size: 12px; color: #666; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>INSTITUTO TECNOLÓGICO SAN ANTONIO - TESA</h2>
                </div>
                <div class='content'>
                    <h3>Recordatorio de Préstamo</h3>
                    <p>Hola <strong>{$persona['nombres']}</strong>,</p>
                    <p>Tu préstamo del equipo vence en <strong>{$dias_vencimiento} día(s)</strong>:</p>
                    <table style='width: 100%; border-collapse: collapse;'>
                        <tr>
                            <td style='padding: 8px; border: 1px solid #ddd;'><strong>Código:</strong></td>
                            <td style='padding: 8px; border: 1px solid #ddd;'>{$equipo['codigo_barras']}</td>
                        </tr>
                        <tr>
                            <td style='padding: 8px; border: 1px solid #ddd;'><strong>Tipo:</strong></td>
                            <td style='padding: 8px; border: 1px solid #ddd;'>{$equipo['tipo_equipo']}</td>
                        </tr>
                    </table>
                    <p>Por favor, coordina la devolución o renovación del préstamo.</p>
                </div>
                <div class='footer'>
                    Sistema de Gestión de Inventario TESA
                </div>
            </div>
        </body>
        </html>
        ";
        
        return $this->enviarEmail($persona['email'], $asunto, $mensaje, 'vencimiento');
    }
    
    private function registrarNotificacion($tipo, $titulo, $mensaje, $email_destino, $enviado) {
        $sql = "INSERT INTO notificaciones (tipo, titulo, mensaje, email_destino, enviado, fecha_envio, created_at)
                VALUES (?, ?, ?, ?, ?, " . ($enviado ? 'NOW()' : 'NULL') . ", NOW())";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('ssssi', $tipo, $titulo, $mensaje, $email_destino, $enviado);
        $stmt->execute();
    }
    
    public function verificarPrestamosVencidos() {
        if (!$this->config || !$this->config['notificar_vencimiento']) {
            return [];
        }
        
        $dias = $this->config['dias_antes_vencimiento'];
        
        $sql = "SELECT a.*, e.codigo_barras, e.tipo_equipo, e.marca, e.modelo,
                       p.nombres, p.email
                FROM asignaciones a
                JOIN equipos e ON a.equipo_id = e.id
                JOIN personas p ON a.persona_id = p.id
                WHERE a.fecha_devolucion IS NULL 
                AND DATEDIFF(NOW(), a.fecha_asignacion) >= 30
                AND p.email IS NOT NULL";
        
        $result = $this->conn->query($sql);
        $notificados = [];
        
        while ($row = $result->fetch_assoc()) {
            $dias_transcurridos = floor((time() - strtotime($row['fecha_asignacion'])) / (60 * 60 * 24));
            
            if ($dias_transcurridos >= 30) {
                $this->notificarVencimiento(
                    ['nombres' => $row['nombres'], 'email' => $row['email']],
                    $row,
                    $dias_transcurridos
                );
                $notificados[] = $row['codigo_barras'];
            }
        }
        
        return $notificados;
    }
}

