<?php
session_start();
require_once 'config/database.php';
require_once 'config/validaciones.php';
require_once 'config/NotificadorEmail.php';

// ====================================================
// 🔒 RECUPERACIÓN DE CONTRASEÑA TEMPORALMENTE DESHABILITADA
// Para volver a habilitarla: elimina o comenta este bloque.
// ====================================================
header('Location: login.php?msg=recuperacion_desactivada');
exit();

if (isset($_SESSION['user_id'])) {
    header('Location: modules/dashboard.php');
    exit();
}

$mensaje = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim((string)($_POST['email'] ?? ''));
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Ingresa un correo válido.';
    } else {
        $stmt = $conn->prepare("SELECT id, nombre, email FROM usuarios WHERE email = ? LIMIT 1");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $res = $stmt->get_result();
        $user = $res ? $res->fetch_assoc() : null;

        if ($user) {
            $token = bin2hex(random_bytes(32));
            $expira = date('Y-m-d H:i:s', strtotime('+1 hour'));

            $up = $conn->prepare("UPDATE usuarios SET reset_token = ?, reset_expira = ? WHERE id = ?");
            $up->bind_param('ssi', $token, $expira, $user['id']);
            $up->execute();

            $protocolo = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
            $link = $protocolo . '://' . $host . '/Inventario-Tesa-Cardex/reset_password.php?token=' . urlencode($token);

            $nombre = $user['nombre'] ?? 'Usuario';
            $html = "<html><head><meta charset='UTF-8'><meta http-equiv='Content-Type' content='text/html; charset=UTF-8'></head><body style='font-family:Arial,sans-serif;line-height:1.6;background:#f6f7fb;padding:20px;'>
                <div style='max-width:640px;margin:0 auto;background:#ffffff;border:1px solid #e5e7eb;border-radius:14px;overflow:hidden;'>
                    <div style='background:linear-gradient(135deg,#5a2d8c,#7c3aed);color:#fff;padding:22px 22px;text-align:center;'>
                        <div style='font-size:18px;font-weight:800;letter-spacing:.3px;'>INSTITUTO TECNOLÓGICO SAN ANTONIO - TESA</div>
                        <div style='opacity:.9;margin-top:6px;'>Recuperación de contraseña</div>
                    </div>
                    <div style='padding:22px 22px;color:#111827;'>
                        <p style='margin:0 0 12px 0;'>Estimado/a " . htmlspecialchars($nombre, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . ",</p>
                        <p style='margin:0 0 14px 0;'>Recibimos una solicitud para restablecer tu contraseña del Sistema de Inventario TESA.</p>
                        <p style='margin:0 0 16px 0;'>Haz clic en el siguiente botón para crear una nueva contraseña. Este enlace expira en 1 hora.</p>
                        <p style='text-align:center;margin:18px 0;'>
                            <a href='" . htmlspecialchars($link, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . "' style='display:inline-block;background:#5a2d8c;color:#fff;text-decoration:none;padding:12px 18px;border-radius:10px;font-weight:700;'>Restablecer contraseña</a>
                        </p>
                        <p style='margin:0 0 10px 0;font-size:12px;color:#6b7280;'>Si no solicitaste esto, ignora este correo.</p>
                        <p style='margin:0;font-size:12px;color:#6b7280;'>Enlace directo: " . htmlspecialchars($link, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . "</p>
                    </div>
                    <div style='padding:14px 22px;background:#f9fafb;color:#6b7280;font-size:12px;text-align:center;border-top:1px solid #e5e7eb;'>
                        Sistema de Gestión de Inventario TESA
                    </div>
                </div>
            </body></html>";

            $notificador = new NotificadorEmail($conn);
            $send = $notificador->enviarEmail($email, 'Recuperación de contraseña - TESA', $html, 'recuperacion');

            if (!empty($send['success'])) {
                $mensaje = "✅ Si el correo existe, se enviará un enlace de recuperación.";
                if (!empty($send['local'])) {
                    $url = !empty($send['local_url']) ? $send['local_url'] : '';
                    $note = !empty($send['note']) ? $send['note'] : '';
                    $mensaje .= "<br>
                        <div class='alert alert-info mt-3 mb-0' style='text-align:left;'>
                            <i class='fas fa-circle-info me-2'></i>
                            {$note}
                            <div class='mt-2'>
                                <a class='btn btn-sm btn-outline-primary' target='_blank' href='{$url}'>
                                    <i class='fas fa-envelope-open-text me-1'></i>Abrir correo de recuperación (local)
                                </a>
                            </div>
                        </div>";
                }
            } else {
                $err = !empty($send['error']) ? $send['error'] : 'No se pudo enviar el correo. Revisa la Configuración de Email en Admin.';
                $error = "❌ {$err}";
            }
        } else {
            $mensaje = "✅ Si el correo existe, se enviará un enlace de recuperación.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Contraseña - TESA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #5a2d8c 0%, #6f42c1 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .card {
            border: none;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.2);
            width: 450px;
            max-width: 90%;
        }
        .card-header {
            background: linear-gradient(135deg, #5a2d8c 0%, #6f42c1 100%);
            color: white;
            text-align: center;
            border-radius: 20px 20px 0 0 !important;
            padding: 25px;
        }
        .card-header h3 {
            margin: 0;
            font-weight: 700;
        }
        .btn-primary {
            background: linear-gradient(135deg, #5a2d8c 0%, #6f42c1 100%);
            border: none;
            border-radius: 30px;
            padding: 12px;
            font-weight: 600;
        }
        .btn-primary:hover {
            background: linear-gradient(135deg, #6f42c1 0%, #5a2d8c 100%);
            transform: translateY(-2px);
        }
        .form-control {
            border-radius: 30px;
            padding: 12px 20px;
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-key me-2"></i>Recuperar Contraseña</h3>
            <p class="mb-0 mt-2">Ingresa tu correo para restablecer tu contraseña</p>
        </div>
        <div class="card-body p-4">
            <?php if ($mensaje): ?>
                <div class="alert alert-success"><?php echo $mensaje; ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="mb-4">
                    <label class="form-label">Correo electrónico</label>
                    <input type="email" name="email" class="form-control" placeholder="ejemplo@tesa.edu.ec" required>
                </div>
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-paper-plane me-2"></i>Enviar enlace de recuperación
                </button>
                <div class="text-center mt-3">
                    <a href="login.php" class="text-decoration-none">← Volver al inicio de sesión</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>


