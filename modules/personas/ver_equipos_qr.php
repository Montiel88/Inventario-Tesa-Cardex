<?php
session_start();
// NOTA: No requerimos login para esta página porque es pública para el QR
// Si quieres que sea privada, descomenta las líneas de abajo
// if (!isset($_SESSION['user_id'])) {
//     header('Location: /inventario_ti/login.php');
//     exit();
// }

require_once '../../config/database.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes">
    <title>Equipos de Persona - TESA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #f8f9fc 0%, #ffffff 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
            padding: 20px;
        }
        .header-qr {
            background: #5a2d8c;
            color: white;
            padding: 30px 20px;
            border-radius: 15px;
            margin-bottom: 30px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(90, 45, 140, 0.3);
        }
        .header-qr h1 {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 10px;
        }
        .header-qr p {
            font-size: 1.1rem;
            opacity: 0.9;
        }
        .persona-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
            border-left: 5px solid #f3b229;
        }
        .persona-card h2 {
            color: #5a2d8c;
            font-size: 1.8rem;
            margin-bottom: 15px;
        }
        .equipo-item {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 15px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.03);
            border: 1px solid #e0e0e0;
            transition: transform 0.2s;
        }
        .equipo-item:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 20px rgba(90, 45, 140, 0.1);
            border-color: #5a2d8c;
        }
        .equipo-tipo {
            font-size: 1.3rem;
            font-weight: 700;
            color: #5a2d8c;
            margin-bottom: 10px;
        }
        .equipo-detalle {
            color: #666;
            margin-bottom: 5px;
        }
        .equipo-detalle strong {
            color: #333;
            width: 100px;
            display: inline-block;
        }
        .badge-qr {
            background: #f3b229;
            color: #333;
            padding: 5px 15px;
            border-radius: 20px;
            font-weight: 600;
        }
        .footer-qr {
            text-align: center;
            margin-top: 40px;
            padding: 20px;
            color: #666;
            border-top: 1px solid #e0e0e0;
        }
        @media (max-width: 768px) {
            .header-qr h1 {
                font-size: 1.5rem;
            }
            .persona-card h2 {
                font-size: 1.4rem;
            }
            .equipo-tipo {
                font-size: 1.1rem;
            }
            .equipo-detalle strong {
                width: 80px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        
        <?php
        if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
            echo '<div class="alert alert-danger">ID de persona no válido</div>';
            echo '</div></body></html>';
            exit();
        }

        $persona_id = intval($_GET['id']);

        // Obtener datos de la persona
        $sql_persona = "SELECT id, cedula, nombres, cargo FROM personas WHERE id = $persona_id";
        $result_persona = $conn->query($sql_persona);

        if ($result_persona->num_rows == 0) {
            echo '<div class="alert alert-danger">Persona no encontrada</div>';
            echo '</div></body></html>';
            exit();
        }

        $persona = $result_persona->fetch_assoc();

        // Obtener equipos asignados
        $sql_equipos = "SELECT e.*, a.fecha_asignacion 
                        FROM equipos e 
                        JOIN asignaciones a ON e.id = a.equipo_id 
                        WHERE a.persona_id = $persona_id AND a.fecha_devolucion IS NULL
                        ORDER BY a.fecha_asignacion DESC";
        $equipos = $conn->query($sql_equipos);
        ?>

        <!-- Header -->
        <div class="header-qr">
            <h1><i class="fas fa-qrcode me-3"></i>INVENTARIO TESA</h1>
            <p>Código QR de equipos asignados</p>
        </div>

        <!-- Datos de la persona -->
        <div class="persona-card">
            <h2><i class="fas fa-user me-2"></i><?php echo htmlspecialchars($persona['nombres']); ?></h2>
            <div class="row">
                <div class="col-md-6">
                    <p><strong>Cédula:</strong> <?php echo $persona['cedula']; ?></p>
                </div>
                <div class="col-md-6">
                    <p><strong>Cargo:</strong> <?php echo $persona['cargo']; ?></p>
                </div>
            </div>
        </div>

        <!-- Listado de equipos -->
        <h3 class="mb-4"><i class="fas fa-laptop me-2"></i>Equipos asignados (<?php echo $equipos->num_rows; ?>)</h3>

        <?php if ($equipos->num_rows > 0): ?>
            <div class="row">
                <?php while($eq = $equipos->fetch_assoc()): ?>
                    <div class="col-12">
                        <div class="equipo-item">
                            <div class="equipo-tipo">
                                <i class="fas fa-qrcode me-2" style="color: #f3b229;"></i>
                                <?php echo $eq['tipo_equipo']; ?>
                                <span class="badge-qr float-end"><?php echo $eq['codigo_barras']; ?></span>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <p class="equipo-detalle"><strong>Marca:</strong> <?php echo $eq['marca'] ?: 'N/A'; ?></p>
                                    <p class="equipo-detalle"><strong>Modelo:</strong> <?php echo $eq['modelo'] ?: 'N/A'; ?></p>
                                </div>
                                <div class="col-md-6">
                                    <p class="equipo-detalle"><strong>Serie:</strong> <?php echo $eq['numero_serie'] ?: 'N/A'; ?></p>
                                    <p class="equipo-detalle"><strong>Asignado:</strong> <?php echo date('d/m/Y', strtotime($eq['fecha_asignacion'])); ?></p>
                                </div>
                            </div>
                            <?php if (!empty($eq['especificaciones'])): ?>
                                <div class="mt-2">
                                    <small class="text-muted"><?php echo $eq['especificaciones']; ?></small>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="alert alert-info text-center py-5">
                <i class="fas fa-info-circle fa-3x mb-3"></i>
                <h4>No tiene equipos asignados</h4>
                <p>Esta persona no tiene equipos en su poder actualmente.</p>
            </div>
        <?php endif; ?>

        <div class="footer-qr">
            <p><i class="fas fa-warehouse me-2"></i>Tecnológico San Antonio - TESA · Sistema de Inventario</p>
            <small>Escanea este código para ver los equipos asignados</small>
        </div>
    </div>
</body>
</html>