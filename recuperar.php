<?php
session_start();
require_once 'config/database.php';

if (isset($_SESSION['user_id'])) {
    header('Location: modules/dashboard.php');
    exit();
}

$mensaje = '';
$error = '';
$paso = 1;

// Verificar si viene con token
if (isset($_GET['token']) && !empty($_GET['token'])) {
    $token = $conn->real_escape_string($_GET['token']);
    $paso = 2;
    
    $sql = "SELECT id, email, reset_expira FROM usuarios WHERE reset_token = '$token' AND reset_expira > NOW()";
    $result = $conn->query($sql);
    
    if ($result->num_rows == 0) {
        $error = "El enlace de recuperación ha expirado o no es válido. Solicita uno nuevo.";
        $paso = 1;
    } else {
        $usuario = $result->fetch_assoc();
        $user_id = $usuario['id'];
        $user_email = $usuario['email'];
    }
}

// Procesar solicitud
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['solicitar'])) {
    $email = $conn->real_escape_string($_POST['email']);
    
    $sql = "SELECT id, email FROM usuarios WHERE email = '$email'";
    $result = $conn->query($sql);
    
    if ($result->num_rows == 0) {
        $error = "No existe una cuenta con ese correo electrónico.";
    } else {
        $usuario = $result->fetch_assoc();
        $user_id = $usuario['id'];
        
        $token = bin2hex(random_bytes(32));
        $expira = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        $sql_update = "UPDATE usuarios SET reset_token = '$token', reset_expira = '$expira' WHERE id = $user_id";
        $conn->query($sql_update);
        
        $protocolo = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'];
        $link = $protocolo . '://' . $host . '/inventario_ti/recuperar.php?token=' . $token;
        
        $mensaje = "
            <div class='alert alert-success'>
                <strong>✅ Enlace de recuperación generado:</strong><br>
                <a href='$link' target='_blank'>$link</a><br><br>
                <small class='text-muted'>Este enlace expirará en 1 hora.</small>
            </div>
        ";
    }
}

// Procesar cambio de contraseña
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['cambiar']) && isset($_POST['token'])) {
    $token = $conn->real_escape_string($_POST['token']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    $sql = "SELECT id FROM usuarios WHERE reset_token = '$token' AND reset_expira > NOW()";
    $result = $conn->query($sql);
    
    if ($result->num_rows == 0) {
        $error = "El enlace ha expirado o no es válido.";
    } elseif (strlen($password) < 6) {
        $error = "La contraseña debe tener al menos 6 caracteres.";
    } elseif ($password !== $confirm_password) {
        $error = "Las contraseñas no coinciden.";
    } else {
        $usuario = $result->fetch_assoc();
        $user_id = $usuario['id'];
        
        $hash = password_hash($password, PASSWORD_DEFAULT);
        
        $sql_update = "UPDATE usuarios SET password = '$hash', reset_token = NULL, reset_expira = NULL WHERE id = $user_id";
        
        if ($conn->query($sql_update)) {
            $mensaje = "<div class='alert alert-success'>✅ Contraseña actualizada correctamente. <a href='login.php'>Iniciar sesión</a></div>";
            $paso = 3;
        } else {
            $error = "Error al actualizar la contraseña.";
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
                <?php echo $mensaje; ?>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if ($paso == 1): ?>
                <form method="POST">
                    <div class="mb-4">
                        <label class="form-label">Correo electrónico</label>
                        <input type="email" name="email" class="form-control" placeholder="ejemplo@tesa.edu.ec" required>
                    </div>
                    <button type="submit" name="solicitar" class="btn btn-primary w-100">
                        <i class="fas fa-paper-plane me-2"></i>Enviar enlace de recuperación
                    </button>
                    <div class="text-center mt-3">
                        <a href="login.php" class="text-decoration-none">← Volver al inicio de sesión</a>
                    </div>
                </form>
                
            <?php elseif ($paso == 2 && isset($_GET['token'])): ?>
                <form method="POST">
                    <input type="hidden" name="token" value="<?php echo htmlspecialchars($_GET['token']); ?>">
                    <div class="mb-3">
                        <label class="form-label">Nueva contraseña</label>
                        <input type="password" name="password" class="form-control" required minlength="6">
                        <small class="text-muted">Mínimo 6 caracteres</small>
                    </div>
                    <div class="mb-4">
                        <label class="form-label">Confirmar contraseña</label>
                        <input type="password" name="confirm_password" class="form-control" required>
                    </div>
                    <button type="submit" name="cambiar" class="btn btn-primary w-100">
                        <i class="fas fa-save me-2"></i>Cambiar contraseña
                    </button>
                    <div class="text-center mt-3">
                        <a href="login.php" class="text-decoration-none">← Volver al inicio de sesión</a>
                    </div>
                </form>
                
            <?php elseif ($paso == 3): ?>
                <div class="text-center">
                    <a href="login.php" class="btn btn-primary w-100">Ir al inicio de sesión</a>
                </div>
            <?php endif; ?>
            
        </div>
    </div>
</body>
</html>