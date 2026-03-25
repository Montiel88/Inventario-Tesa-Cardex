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
    <!-- Google Fonts - Poppins y Montserrat -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&family=Montserrat:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Animate.css para animaciones -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
   <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow-x: hidden;
        }

        /* Fondo animado con partículas */
        body::before {
            content: '';
            position: fixed;
            width: 100%;
            height: 100%;
            background: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" opacity="0.1"><circle cx="10" cy="10" r="2" fill="white"/><circle cx="90" cy="20" r="3" fill="white"/><circle cx="30" cy="80" r="2" fill="white"/><circle cx="70" cy="60" r="4" fill="white"/><circle cx="50" cy="30" r="2" fill="white"/><circle cx="20" cy="50" r="3" fill="white"/></svg>');
            background-size: 200px 200px;
            animation: moveBackground 20s linear infinite;
            z-index: 0;
            pointer-events: none;
        }

        @keyframes moveBackground {
            0% { transform: translateY(0) translateX(0); }
            100% { transform: translateY(-100px) translateX(100px); }
        }

        .container {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 1200px;
            padding: 15px;
        }

        .login-wrapper {
            display: flex;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 30px;
            overflow: hidden;
            box-shadow: 0 30px 60px rgba(0, 0, 0, 0.3);
            animation: slideInUp 1s ease;
        }

        /* Lado izquierdo - Información */
        .info-side {
            flex: 1;
            background: linear-gradient(135deg, #5a2d8c 0%, #8e44ad 100%);
            padding: 50px;
            color: white;
            display: flex;
            flex-direction: column;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }

        .info-side::before {
            content: '';
            position: absolute;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.2) 0%, transparent 50%);
            top: -50%;
            left: -50%;
            animation: rotate 30s linear infinite;
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
            font-size: 3rem;
            font-weight: 800;
            margin-bottom: 20px;
            line-height: 1.2;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.2);
        }

        .info-side h1 span {
            color: #f3b229;
            display: block;
            font-size: 2rem;
        }

        .info-side p {
            font-size: 1.1rem;
            margin-bottom: 30px;
            opacity: 0.9;
            line-height: 1.6;
        }

        .feature-list {
            list-style: none;
            margin-top: 30px;
        }

        .feature-list li {
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 15px;
            font-size: 1rem;
            animation: fadeInRight 0.5s ease;
            animation-fill-mode: both;
        }

        .feature-list li:nth-child(1) { animation-delay: 0.2s; }
        .feature-list li:nth-child(2) { animation-delay: 0.4s; }
        .feature-list li:nth-child(3) { animation-delay: 0.6s; }

        .feature-list i {
            font-size: 1.5rem;
            color: #f3b229;
            background: rgba(255,255,255,0.2);
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 15px;
            flex-shrink: 0;
        }

        .feature-list li div {
            flex: 1;
        }

        .feature-list li div p {
            margin: 0;
            font-size: 0.9rem;
            opacity: 0.8;
        }

        /* Lado derecho - Formulario */
        .form-side {
            flex: 1;
            padding: 60px 50px;
            background: white;
        }

        .brand {
            text-align: center;
            margin-bottom: 40px;
            animation: bounceIn 1s ease;
        }

        .brand img {
            width: 100px;
            height: auto;
            margin-bottom: 20px;
            filter: drop-shadow(0 10px 20px rgba(90,45,140,0.2));
            transition: transform 0.3s;
        }

        .brand img:hover {
            transform: scale(1.1) rotate(5deg);
        }

        .brand h2 {
            color: #5a2d8c;
            font-weight: 700;
            font-size: 2rem;
            margin-bottom: 5px;
        }

        .brand p {
            color: #666;
            font-size: 0.9rem;
        }

        .input-group {
            margin-bottom: 30px;
            position: relative;
        }

        .input-group label {
            display: block;
            margin-bottom: 8px;
            color: #555;
            font-weight: 500;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .input-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }

        .input-wrapper i {
            position: absolute;
            left: 15px;
            color: #5a2d8c;
            font-size: 1.2rem;
            transition: all 0.3s;
            z-index: 1;
        }

        .input-wrapper input {
            width: 100%;
            padding: 18px 18px 18px 50px;
            border: 2px solid #e0e0e0;
            border-radius: 15px;
            font-size: 1rem;
            transition: all 0.3s;
            font-family: 'Poppins', sans-serif;
            background: white;
            -webkit-appearance: none; /* Evita estilos por defecto en iOS */
            appearance: none;
        }

        .input-wrapper input:focus {
            outline: none;
            border-color: #5a2d8c;
            box-shadow: 0 10px 20px rgba(90,45,140,0.1);
            transform: translateY(-2px);
        }

        .input-wrapper input:focus + i {
            color: #f3b229;
            transform: scale(1.1);
        }

        .input-wrapper .toggle-password {
            position: absolute;
            right: 15px;
            cursor: pointer;
            color: #999;
            transition: all 0.3s;
            z-index: 1;
            font-size: 1.2rem;
        }

        .input-wrapper .toggle-password:hover {
            color: #5a2d8c;
        }

        .error-message {
            background: #fee;
            color: #e74c3c;
            padding: 15px 20px;
            border-radius: 15px;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: shake 0.5s ease;
            border-left: 4px solid #e74c3c;
            font-size: 0.95rem;
        }

        .error-message i {
            font-size: 1.2rem;
            flex-shrink: 0;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-10px); }
            75% { transform: translateX(10px); }
        }

        .btn-login {
            width: 100%;
            padding: 18px;
            background: linear-gradient(135deg, #5a2d8c 0%, #8e44ad 100%);
            color: white;
            border: none;
            border-radius: 15px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
            margin-bottom: 20px;
            text-transform: uppercase;
            letter-spacing: 1px;
            -webkit-appearance: none;
            appearance: none;
        }

        .btn-login::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            transition: left 0.5s;
        }

        .btn-login:hover::before {
            left: 100%;
        }

        .btn-login:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 30px rgba(90,45,140,0.3);
        }

        .btn-login i {
            margin-right: 10px;
            transition: transform 0.3s;
        }

        .btn-login:hover i {
            transform: translateX(5px);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        .additional-links {
            text-align: center;
            margin-top: 20px;
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 10px;
        }

        .additional-links a {
            color: #999;
            text-decoration: none;
            font-size: 0.85rem;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 8px 12px;
            border-radius: 30px;
            background: rgba(0,0,0,0.02);
        }

        .additional-links a:hover {
            color: #5a2d8c;
            background: rgba(90,45,140,0.05);
            transform: translateY(-2px);
        }

        .additional-links .separator {
            color: #ddd;
            display: none;
        }

        /* ============================================ */
        /* MEDIA QUERIES - VERSIÓN RESPONSIVE MEJORADA */
        /* ============================================ */

        /* Tablets y pantallas medianas */
        @media (max-width: 992px) {
            .info-side {
                padding: 40px;
            }

            .info-side h1 {
                font-size: 2.5rem;
            }

            .info-side h1 span {
                font-size: 1.8rem;
            }

            .form-side {
                padding: 40px;
            }
        }

        /* Móviles grandes y tablets pequeñas */
        @media (max-width: 768px) {
            .login-wrapper {
                flex-direction: column;
                border-radius: 25px;
            }
            
            .info-side {
                padding: 35px 25px;
                border-radius: 25px 25px 0 0;
            }
            
            .info-side h1 {
                font-size: 2rem;
                text-align: center;
            }
            
            .info-side h1 span {
                font-size: 1.5rem;
            }

            .info-side p {
                text-align: center;
                font-size: 1rem;
            }
            
            .form-side {
                padding: 35px 25px;
            }

            .brand h2 {
                font-size: 1.8rem;
            }

            .feature-list li {
                gap: 12px;
            }

            .feature-list i {
                width: 45px;
                height: 45px;
                font-size: 1.3rem;
            }
        }

        /* Móviles pequeños */
        @media (max-width: 480px) {
            .container {
                padding: 10px;
            }

            .login-wrapper {
                border-radius: 20px;
            }

            .info-side {
                padding: 25px 20px;
            }

            .info-side h1 {
                font-size: 1.8rem;
                margin-bottom: 15px;
            }

            .info-side h1 span {
                font-size: 1.3rem;
            }

            .info-side p {
                font-size: 0.9rem;
                margin-bottom: 20px;
            }

            .feature-list {
                margin-top: 20px;
            }

            .feature-list li {
                margin-bottom: 15px;
                gap: 10px;
            }

            .feature-list i {
                width: 40px;
                height: 40px;
                font-size: 1.1rem;
            }

            .feature-list li div strong {
                font-size: 0.95rem;
            }

            .feature-list li div p {
                font-size: 0.8rem;
            }

            .form-side {
                padding: 25px 20px;
            }

            .brand {
                margin-bottom: 25px;
            }

            .brand img {
                width: 70px;
                margin-bottom: 10px;
            }

            .brand h2 {
                font-size: 1.5rem;
            }

            .brand p {
                font-size: 0.8rem;
            }

            .input-group {
                margin-bottom: 20px;
            }

            .input-group label {
                font-size: 0.8rem;
                margin-bottom: 5px;
            }

            .input-wrapper input {
                padding: 14px 14px 14px 45px;
                font-size: 0.9rem;
                border-radius: 12px;
            }

            .input-wrapper i {
                left: 12px;
                font-size: 1rem;
            }

            .input-wrapper .toggle-password {
                right: 12px;
                font-size: 1rem;
            }

            .error-message {
                padding: 12px 15px;
                font-size: 0.85rem;
                margin-bottom: 20px;
            }

            .btn-login {
                padding: 15px;
                font-size: 1rem;
                border-radius: 12px;
            }

            .additional-links {
                gap: 5px;
            }

            .additional-links a {
                font-size: 0.75rem;
                padding: 6px 10px;
            }
        }

        /* Móviles muy pequeños (menos de 360px) */
        @media (max-width: 360px) {
            .info-side h1 {
                font-size: 1.5rem;
            }

            .info-side h1 span {
                font-size: 1.1rem;
            }

            .feature-list li {
                flex-direction: column;
                text-align: center;
                gap: 5px;
            }

            .feature-list i {
                margin-bottom: 5px;
            }

            .brand h2 {
                font-size: 1.3rem;
            }

            .additional-links {
                flex-direction: column;
                gap: 0;
            }

            .additional-links a {
                justify-content: center;
            }
        }

        /* Orientación horizontal en móviles */
        @media (max-height: 600px) and (orientation: landscape) {
            body {
                align-items: flex-start;
                padding: 20px 0;
            }

            .login-wrapper {
                flex-direction: row;
            }

            .info-side {
                padding: 20px;
            }

            .info-side h1 {
                font-size: 1.5rem;
            }

            .info-side h1 span {
                font-size: 1rem;
            }

            .feature-list {
                margin-top: 10px;
            }

            .feature-list li {
                margin-bottom: 8px;
            }

            .form-side {
                padding: 20px;
            }

            .brand {
                margin-bottom: 15px;
            }

            .brand img {
                width: 50px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="login-wrapper animate__animated animate__fadeInUp">
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
                    <img src="assets/img/logo-tesa.png" alt="TESA" onerror="this.style.display='none'">
                    <h2>Iniciar Sesión</h2>
                    <p>Ingresa tus credenciales para acceder</p>
                </div>
                
                <?php if ($error): ?>
                    <div class="error-message">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" id="loginForm">
                    <div class="input-group">
                        <label for="email">Email institucional</label>
                        <div class="input-wrapper">
                            <i class="bi bi-envelope-fill"></i>
                            <input type="email" 
                                   id="email" 
                                   name="email" 
                                   placeholder="ejemplo@tesa.edu.ec" 
                                   required 
                                   value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                        </div>
                    </div>
                    
                <div class="input-group">
    <label for="password">Contraseña</label>
    <div class="input-wrapper">
        <input type="password" id="password" name="password" placeholder="••••••••" required>
        <i class="bi bi-eye-slash toggle-password" id="togglePassword"></i>
    </div>
</div>
                    
                    <button type="submit" class="btn-login">
                        <i class="bi bi-box-arrow-in-right"></i>
                        Ingresar al Sistema
                    </button>
                    
                  <div class="additional-links">
    <a href="recuperar.php"><i class="bi bi-key"></i> ¿Olvidaste tu contraseña?</a>
    <span class="separator">|</span>
    <a href="#"><i class="bi bi-info-circle"></i> Ayuda</a>
</div>
                </form>
            </div>
        </div>
    </div>

    <!-- Script para mostrar/ocultar contraseña -->
    <script>
        const togglePassword = document.getElementById('togglePassword');
        const password = document.getElementById('password');
        
        togglePassword.addEventListener('click', function() {
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);
            this.classList.toggle('bi-eye');
            this.classList.toggle('bi-eye-slash');
        });
        
        // Animación de carga
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelector('.login-wrapper').classList.add('animate__animated', 'animate__fadeInUp');
        });
    </script>
</body>
</html>

