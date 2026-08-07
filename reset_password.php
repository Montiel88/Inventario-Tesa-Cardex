<?php
session_start();
require_once 'config/database.php';

if (isset($_SESSION['user_id'])) {
    header('Location: modules/dashboard.php');
    exit();
}

$mensaje = '';
$error = '';

$token = trim((string)($_GET['token'] ?? ($_POST['token'] ?? '')));
if ($token === '') {
    $error = 'Token no válido.';
}

$user = null;
if ($token !== '') {
    $stmt = $conn->prepare("SELECT id, email, reset_expira FROM usuarios WHERE reset_token = ? AND reset_expira > NOW() LIMIT 1");
    $stmt->bind_param('s', $token);
    $stmt->execute();
    $res = $stmt->get_result();
    $user = $res ? $res->fetch_assoc() : null;
    if (!$user) {
        $error = 'El enlace de recuperación ha expirado o no es válido.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $user) {
    $password = (string)($_POST['password'] ?? '');
    $confirm = (string)($_POST['confirm_password'] ?? '');

    if (strlen($password) < 8) {
        $error = 'La contraseña debe tener al menos 8 caracteres.';
    } elseif ($password !== $confirm) {
        $error = 'Las contraseñas no coinciden.';
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $up = $conn->prepare("UPDATE usuarios SET password = ?, reset_token = NULL, reset_expira = NULL WHERE id = ?");
        $up->bind_param('si', $hash, $user['id']);
        if ($up->execute()) {
            $mensaje = '✅ Contraseña actualizada correctamente. Ahora puedes iniciar sesión.';
            $user = null;
        } else {
            $error = 'Error al actualizar la contraseña.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restablecer Contraseña - TESA</title>
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
            <h3><i class="fas fa-lock me-2"></i>Restablecer Contraseña</h3>
            <p class="mb-0 mt-2">Crea una nueva contraseña para tu cuenta</p>
        </div>
        <div class="card-body p-4">
            <?php if ($mensaje): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($mensaje, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></div>
                <div class="text-center">
                    <a href="login.php" class="btn btn-primary w-100">Ir al inicio de sesión</a>
                </div>
            <?php else: ?>
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></div>
                <?php endif; ?>

                <?php if ($user): ?>
                    <form method="POST">
                        <input type="hidden" name="token" value="<?php echo htmlspecialchars($token, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>">
                        <div class="mb-3">
                            <label class="form-label">Nueva contraseña</label>
                            <input type="password" name="password" class="form-control" required minlength="8" autocomplete="new-password">
                            <small class="text-muted">Mínimo 8 caracteres</small>
                        </div>
                        <div class="mb-4">
                            <label class="form-label">Confirmar contraseña</label>
                            <input type="password" name="confirm_password" class="form-control" required minlength="8" autocomplete="new-password">
                        </div>
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-save me-2"></i>Cambiar contraseña
                        </button>
                        <div class="text-center mt-3">
                            <a href="login.php" class="text-decoration-none">← Volver al inicio de sesión</a>
                        </div>
                    </form>
                <?php else: ?>
                    <div class="text-center">
                        <a href="forgot_password.php" class="btn btn-primary w-100">Solicitar un nuevo enlace</a>
                        <div class="mt-3">
                            <a href="login.php" class="text-decoration-none">← Volver al inicio de sesión</a>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>

