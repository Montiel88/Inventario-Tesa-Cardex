<?php
session_start();
require_once 'config/database.php';

if (isset($_SESSION['user_id'])) {
    header('Location: modules/dashboard.php');
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $conn->real_escape_string($_POST['email']);
    $password = $_POST['password'];
    
    $stmt = $conn->prepare("SELECT id, nombre, email, password, rol FROM usuarios WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        
        if (password_verify($password, $user['password'])) {
            
            $rol_numero = 2;
            if ($user['rol'] == 'admin' || $user['rol'] == 1 || $user['rol'] == '1') {
                $rol_numero = 1;
            }
            
            $update = $conn->prepare("UPDATE usuarios SET ultimo_acceso = NOW() WHERE id = ?");
            $update->bind_param("i", $user['id']);
            $update->execute();
            
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['nombre'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_rol'] = $rol_numero;
            
            header('Location: modules/dashboard.php');
            exit();
        } else {
            $error = "❌ Contraseña incorrecta";
        }
    } else {
        $error = "❌ Usuario no encontrado";
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Inventario TESA</title>
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --c-bg: #0a0118;
            --c-deep: #05000a;
            --c-violet: #8b5cf6;
            --c-gold: #f3b229;
            --c-gold-glow: rgba(243, 178, 41, 0.4);
            --ease: cubic-bezier(.4, 0, .2, 1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: var(--c-deep);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow-x: hidden;
            color: #fff;
        }

        /* Spectacular LED Background */
        body::before {
            content: '';
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: 
                radial-gradient(circle at 10% 10%, rgba(139, 92, 246, 0.4) 0%, transparent 40%),
                radial-gradient(circle at 90% 10%, rgba(243, 178, 41, 0.4) 0%, transparent 40%),
                radial-gradient(circle at 50% 50%, #1a0533 0%, var(--c-deep) 100%);
            z-index: -1;
            pointer-events: none;
        }

        body::after {
            content: '';
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.65' numOctaves='3' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='0.03'/%3E%3C/svg%3E");
            z-index: -1;
            pointer-events: none;
            opacity: 0.4;
        }

        .container {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 1100px;
            padding: 20px;
        }

        .login-wrapper {
            display: flex;
            background: rgba(255, 255, 255, 0.03);
            backdrop-filter: blur(25px);
            -webkit-backdrop-filter: blur(25px);
            border-radius: 40px;
            overflow: hidden;
            box-shadow: 
                0 40px 100px rgba(0, 0, 0, 0.6),
                0 0 0 1px rgba(255, 255, 255, 0.05) inset;
            border: 1px solid rgba(255, 255, 255, 0.1);
            position: relative;
        }

        /* LED Top Line */
        .login-wrapper::after {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; height: 3px;
            background: linear-gradient(90deg, transparent, var(--c-violet), var(--c-gold), var(--c-violet), transparent);
            background-size: 200% 100%;
            animation: aurora 3s linear infinite;
        }

        @keyframes aurora {
            from { background-position: 0 0; }
            to { background-position: 200% 0; }
        }

        /* Lado izquierdo - Información */
        .info-side {
            flex: 1.2;
            background: linear-gradient(135deg, rgba(90, 45, 140, 0.4) 0%, rgba(20, 5, 45, 0.2) 100%);
            padding: 60px;
            color: white;
            display: flex;
            flex-direction: column;
            justify-content: center;
            position: relative;
            overflow: hidden;
            border-right: 1px solid rgba(255, 255, 255, 0.05);
        }

        .info-side::before {
            content: '';
            position: absolute;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(139, 92, 246, 0.15) 0%, transparent 60%);
            top: -50%;
            left: -50%;
            animation: rotate 40s linear infinite;
        }

        @keyframes rotate {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        .info-content {
            position: relative;
            z-index: 2;
        }

        .info-side h1 {
            font-size: 3.5rem;
            font-weight: 900;
            margin-bottom: 25px;
            line-height: 1.1;
            letter-spacing: -1.5px;
        }

        .info-side h1 span {
            color: var(--c-gold);
            display: block;
            font-size: 2.2rem;
            text-shadow: 0 0 20px rgba(243, 178, 41, 0.4);
            margin-top: 5px;
        }

        .info-side p {
            font-size: 1.15rem;
            margin-bottom: 40px;
            color: rgba(255, 255, 255, 0.7);
            line-height: 1.6;
        }

        .feature-list {
            list-style: none;
            margin-top: 20px;
        }

        .feature-list li {
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 20px;
            font-size: 1.05rem;
            font-weight: 500;
        }

        .feature-list i {
            font-size: 1.4rem;
            color: var(--c-gold);
            background: rgba(255, 255, 255, 0.06);
            width: 55px;
            height: 55px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 18px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            flex-shrink: 0;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
        }

        .feature-list li div p {
            margin: 0;
            font-size: 0.9rem;
            color: rgba(255, 255, 255, 0.5);
        }

        /* Lado derecho - Formulario */
        .form-side {
            flex: 1;
            padding: 70px 60px;
            background: rgba(255, 255, 255, 0.02);
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .brand {
            text-align: center;
            margin-bottom: 45px;
        }

        .brand-logo-ring {
            width: 100px;
            height: 100px;
            background: #fff;
            border-radius: 28px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 25px;
            box-shadow: 
                0 15px 35px rgba(0, 0, 0, 0.4),
                0 0 0 6px rgba(243, 178, 41, 0.15);
            border: 3px solid var(--c-gold);
            transform: rotate(-5deg);
            transition: transform 0.4s var(--ease);
        }

        .login-wrapper:hover .brand-logo-ring {
            transform: rotate(0deg) scale(1.05);
        }

        .brand-logo-ring img {
            width: 75px;
            height: 75px;
            object-fit: contain;
        }

        .brand h2 {
            color: #fff;
            font-weight: 800;
            font-size: 2.2rem;
            margin-bottom: 8px;
            letter-spacing: -0.5px;
        }

        .brand p {
            color: rgba(255, 255, 255, 0.5);
            font-size: 0.95rem;
        }

        .input-group {
            margin-bottom: 30px;
            position: relative;
        }

        .input-group label {
            display: block;
            margin-bottom: 12px;
            color: rgba(255, 255, 255, 0.8);
            font-weight: 700;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            padding-left: 5px;
        }

        .input-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }

        .input-wrapper i.field-icon {
            position: absolute;
            left: 20px;
            color: var(--c-gold);
            font-size: 1.2rem;
            transition: all 0.3s;
            z-index: 1;
        }

        .input-wrapper input {
            width: 100%;
            padding: 18px 20px 18px 60px;
            background: rgba(255, 255, 255, 0.05);
            border: 2px solid rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            font-size: 1.05rem;
            color: #fff;
            transition: all 0.3s var(--ease);
            font-family: 'Poppins', sans-serif;
        }

        .input-wrapper input:focus {
            outline: none;
            border-color: var(--c-violet);
            background: rgba(255, 255, 255, 0.08);
            box-shadow: 0 0 25px rgba(139, 92, 246, 0.25);
            transform: translateY(-2px);
        }

        .input-wrapper input:focus + i.field-icon {
            transform: scale(1.2) rotate(-5deg);
            filter: drop-shadow(0 0 8px rgba(243, 178, 41, 0.6));
        }

        .input-wrapper .toggle-password {
            position: absolute;
            right: 20px;
            cursor: pointer;
            color: rgba(255, 255, 255, 0.3);
            transition: all 0.3s;
            z-index: 1;
            font-size: 1.2rem;
        }

        .input-wrapper .toggle-password:hover {
            color: #fff;
        }

        .btn-login {
            background: linear-gradient(135deg, var(--c-gold) 0%, #e8a520 100%);
            border: none;
            border-radius: 20px;
            padding: 20px;
            color: #1a0533;
            font-weight: 800;
            font-size: 1.1rem;
            width: 100%;
            margin-top: 15px;
            transition: all 0.4s var(--ease);
            box-shadow: 0 15px 35px rgba(243, 178, 41, 0.4);
            text-transform: uppercase;
            letter-spacing: 1.5px;
            cursor: pointer;
        }

        .btn-login:hover {
            transform: translateY(-6px) scale(1.02);
            box-shadow: 0 25px 50px rgba(243, 178, 41, 0.5);
            filter: brightness(1.1);
        }

        .error-message {
            background: rgba(244, 63, 94, 0.15);
            color: #ff4d6d;
            padding: 18px 25px;
            border-radius: 20px;
            margin-bottom: 35px;
            display: flex;
            align-items: center;
            gap: 15px;
            border: 1px solid rgba(244, 63, 94, 0.3);
            font-weight: 600;
            animation: shake 0.5s ease;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            20% { transform: translateX(-8px); }
            40% { transform: translateX(8px); }
            60% { transform: translateX(-8px); }
            80% { transform: translateX(8px); }
        }

        .footer-copyright {
            margin-top: 45px;
            text-align: center;
            color: rgba(255, 255, 255, 0.3);
            font-size: 0.85rem;
        }

        /* Responsive */
        @media (max-width: 992px) {
            .info-side { display: none; }
            .login-wrapper { max-width: 500px; margin: 0 auto; }
            .container { padding: 15px; }
        }

        @media (max-width: 480px) {
            .form-side { padding: 45px 30px; }
            .brand h2 { font-size: 1.8rem; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="login-wrapper">
            <!-- Lado izquierdo - Información -->
            <div class="info-side">
                <div class="info-content">
                    <h1>
                        ¡Bienvenido!
                        <span>TESA Inventario</span>
                    </h1>
                    <p>Sistema de gestión profesional para el control de inventario y préstamos del Tecnológico San Antonio.</p>
                    
                    <ul class="feature-list">
                        <li>
                            <i class="bi bi-people-fill"></i>
                            <div>
                                <strong>Gestión de Personas</strong>
                                <p>Control total de docentes y estudiantes</p>
                            </div>
                        </li>
                        <li>
                            <i class="bi bi-laptop-fill"></i>
                            <div>
                                <strong>Control de Equipos</strong>
                                <p>Inventario detallado de tecnología</p>
                            </div>
                        </li>
                        <li>
                            <i class="bi bi-arrow-left-right"></i>
                            <div>
                                <strong>Préstamos y Devoluciones</strong>
                                <p>Seguimiento en tiempo real</p>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Lado derecho - Formulario -->
            <div class="form-side">
                <div class="brand">
                    <div class="brand-logo-ring">
                        <img src="assets/img/logo-tesa.png" alt="TESA Logo">
                    </div>
                    <h2>Iniciar Sesión</h2>
                    <p>Accede al panel administrativo</p>
                </div>

                <?php if ($error): ?>
                    <div class="error-message">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="input-group">
                        <label for="email">Correo Electrónico</label>
                        <div class="input-wrapper">
                            <i class="bi bi-envelope field-icon"></i>
                            <input type="email" id="email" name="email" placeholder="ejemplo@tesa.edu.ec" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                        </div>
                    </div>

                    <div class="input-group">
                        <label for="password">Contraseña</label>
                        <div class="input-wrapper">
                            <i class="bi bi-lock field-icon"></i>
                            <input type="password" id="password" name="password" placeholder="••••••••" required>
                            <i class="bi bi-eye-slash toggle-password" id="togglePassword"></i>
                        </div>
                    </div>

                    <button type="submit" class="btn-login">
                        <i class="bi bi-box-arrow-in-right me-2"></i>Entrar al Sistema
                    </button>
                </form>

                <div class="footer-copyright">
                    &copy; <?php echo date('Y'); ?> Instituto Tecnológico San Antonio
                </div>
            </div>
        </div>
    </div>

    <script>
        // Toggle password visibility
        const togglePassword = document.querySelector('#togglePassword');
        const password = document.querySelector('#password');

        togglePassword.addEventListener('click', function (e) {
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);
            this.classList.toggle('bi-eye');
            this.classList.toggle('bi-eye-slash');
        });
    </script>
</body>
</html>