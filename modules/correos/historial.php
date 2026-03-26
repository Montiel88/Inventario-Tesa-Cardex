<?php
session_start();
require_once '../../config/database.php';

$usuario_id = $_SESSION['usuario_id'] ?? $_SESSION['user_id'] ?? null;
$rol_id = $_SESSION['rol_id'] ?? $_SESSION['user_rol'] ?? null;
if (!$usuario_id || $rol_id != 1) {
    header('Location: /inventario_ti/login.php');
    exit;
}

// Obtener historial de correos (simulado con estructura real, pero podemos usar la tabla si existe)
// Si no existe tabla, mostramos mensaje. Pero aquí asumimos que existe o se va a crear.
$correos = [];
$tabla_existe = $conn->query("SHOW TABLES LIKE 'correos_enviados'")->num_rows > 0;
if ($tabla_existe) {
    $sql = "SELECT c.*, p.nombres, p.correo as persona_email 
            FROM correos_enviados c
            LEFT JOIN personas p ON c.persona_id = p.id
            ORDER BY c.created_at DESC LIMIT 100";
    $result = $conn->query($sql);
    if ($result) $correos = $result->fetch_all(MYSQLI_ASSOC);
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historial de Correos | TESA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --c-bg: #0a0118;
            --c-deep: #05000a;
            --c-mid: #160530;
            --c-violet: #8b5cf6;
            --c-gold: #f3b229;
            --c-danger: #f43f5e;
            --c-success: #10b981;
            --font: 'Outfit','Poppins',sans-serif;
        }

        body {
            background: var(--c-deep);
            background-image: 
                radial-gradient(circle at 10% 10%, rgba(139, 92, 246, 0.15) 0%, transparent 40%),
                radial-gradient(circle at 90% 10%, rgba(243, 178, 41, 0.1) 0%, transparent 40%),
                radial-gradient(circle at 50% 50%, #1a0533 0%, var(--c-deep) 100%);
            font-family: var(--font);
            color: #fff;
            min-height: 100vh;
        }

        .page-header {
            background: linear-gradient(135deg, var(--c-deep) 0%, var(--c-mid) 100%);
            padding: 3rem 0;
            margin-bottom: 2rem;
            border-bottom: 2px solid rgba(243, 178, 41, 0.3);
            position: relative;
            box-shadow: 0 10px 40px rgba(0,0,0,0.5);
        }

        .page-header::after {
            content: '';
            position: absolute; bottom: -2px; left: 0; right: 0; height: 2px;
            background: linear-gradient(90deg, transparent, var(--c-gold), var(--c-violet), transparent);
            background-size: 200% 100%;
            animation: aurora 4s linear infinite;
        }

        @keyframes aurora {
            from { background-position: 0 0; }
            to   { background-position: 200% 0; }
        }

        .table-modern-container {
            background: rgba(20, 5, 45, 0.6);
            backdrop-filter: blur(25px) saturate(2);
            -webkit-backdrop-filter: blur(25px) saturate(2);
            border-radius: 28px;
            padding: 1.5rem;
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 25px 80px rgba(0, 0, 0, 0.6);
        }

        .table-modern {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0 10px;
        }

        .table-modern th {
            background: rgba(139, 92, 246, 0.2) !important;
            color: var(--c-gold) !important;
            font-weight: 800 !important;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            padding: 1.2rem !important;
            border: none !important;
            border-bottom: 2px solid var(--c-gold) !important;
        }

        .table-modern tbody tr {
            background: rgba(255, 255, 255, 0.03) !important;
            transition: all 0.3s ease;
        }

        .table-modern tbody tr:hover {
            background: rgba(255, 255, 255, 0.08) !important;
            transform: scale(1.005);
            box-shadow: 0 10px 30px rgba(0,0,0,0.4);
        }

        .table-modern td {
            padding: 1.2rem !important;
            border: none !important;
            color: #fff !important;
            vertical-align: middle;
        }

        .badge-estado {
            padding: 0.5rem 1rem;
            border-radius: 12px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-size: 0.75rem;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .status-enviado {
            background: linear-gradient(135deg, #10b981, #059669);
            box-shadow: 0 0 15px rgba(16, 185, 129, 0.3);
        }

        /* ROJO BRILLANTE PARA FALLIDO */
        .status-fallido {
            background: linear-gradient(135deg, #f43f5e, #e11d48);
            box-shadow: 0 0 25px rgba(244, 63, 94, 0.7);
            animation: pulse-red 2s infinite;
            border: 1px solid rgba(255,255,255,0.2);
        }

        @keyframes pulse-red {
            0% { box-shadow: 0 0 15px rgba(244, 63, 94, 0.5); }
            50% { box-shadow: 0 0 35px rgba(244, 63, 94, 0.9); }
            100% { box-shadow: 0 0 15px rgba(244, 63, 94, 0.5); }
        }

        .btn-view {
            background: linear-gradient(135deg, var(--c-violet), #6d28d9);
            border: none;
            border-radius: 12px;
            padding: 0.5rem 1.2rem;
            color: #fff;
            font-weight: 700;
            transition: all 0.3s ease;
            box-shadow: 0 5px 15px rgba(139, 92, 246, 0.3);
        }

        .btn-view:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(139, 92, 246, 0.5);
            filter: brightness(1.2);
            color: #fff;
        }

        .modal-content {
            background: rgba(15, 5, 30, 0.98) !important;
            backdrop-filter: blur(25px) saturate(2) !important;
            border: 1px solid rgba(255, 255, 255, 0.15) !important;
            border-radius: 28px !important;
            color: #fff !important;
            box-shadow: 0 30px 100px rgba(0, 0, 0, 0.8) !important;
        }

        .modal-header {
            background: linear-gradient(135deg, var(--c-deep) 0%, var(--c-mid) 100%) !important;
            border-bottom: 2px solid var(--c-gold) !important;
            color: var(--c-gold) !important;
            border-radius: 28px 28px 0 0 !important;
        }

        .detail-item {
            margin-bottom: 1rem;
            padding: 0.8rem;
            background: rgba(255, 255, 255, 0.03);
            border-radius: 12px;
            border: 1px solid rgba(255, 255, 255, 0.05);
        }

        .detail-label {
            color: var(--c-gold);
            font-weight: 800;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 1px;
            margin-bottom: 4px;
            display: block;
        }

        /* ESTADO EN ROJO DENTRO DEL MODAL */
        .detail-status-fallido {
            color: #f43f5e !important;
            font-weight: 800;
            text-shadow: 0 0 10px rgba(244, 63, 94, 0.5);
        }

        .message-box {
            background: rgba(0, 0, 0, 0.3) !important;
            border: 1px solid rgba(255, 255, 255, 0.1) !important;
            border-radius: 16px;
            padding: 1.5rem;
            max-height: 400px;
            overflow-y: auto;
            color: rgba(255, 255, 255, 0.9);
            font-family: 'Courier New', Courier, monospace;
            white-space: pre-wrap;
        }

        .empty-state {
            text-align: center;
            padding: 5rem;
            color: rgba(255, 255, 255, 0.4);
        }
        
        .empty-state i {
            font-size: 4rem;
            color: var(--c-violet);
            margin-bottom: 1.5rem;
            opacity: 0.5;
        }
    </style>
</head>
<body>
<div class="page-header">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <h1 class="text-white mb-0" style="font-weight: 900; letter-spacing: -1.5px; font-size: 2.8rem;">
                    <i class="fas fa-clock-rotate-left me-2 text-warning" style="filter: drop-shadow(0 0 10px var(--c-gold-glow));"></i>Historial de Correos
                </h1>
                <p class="text-white-50 mt-2 mb-0">Auditoría completa de comunicaciones enviadas</p>
            </div>
            <a href="listar.php" class="btn btn-outline-light rounded-pill px-4 py-2" style="font-weight: 700; border-width: 2px;">
                <i class="fas fa-arrow-left me-2"></i> Volver a Gestión
            </a>
        </div>
    </div>
</div>

<div class="container pb-5">
    <?php if (!$tabla_existe): ?>
        <div class="alert alert-warning rounded-4 shadow-sm border-0" style="background: rgba(245, 158, 11, 0.15); color: #f59e0b; backdrop-filter: blur(10px);">
            <i class="fas fa-info-circle me-2"></i> La tabla de historial aún no existe. Los registros se guardarán automáticamente cuando envíes correos.
        </div>
    <?php elseif (empty($correos)): ?>
        <div class="empty-state">
            <i class="fas fa-inbox fa-3x mb-3"></i>
            <h5 class="text-white">No hay correos registrados</h5>
            <p>Envía tu primer correo desde la gestión.</p>
            <a href="listar.php" class="btn btn-view rounded-pill px-4 mt-3">Ir a gestión</a>
        </div>
    <?php else: ?>
        <div class="table-modern-container">
            <div class="table-responsive">
                <table class="table-modern">
                    <thead>
                        <tr><th>Fecha</th><th>Destinatario</th><th>Asunto</th><th>Tipo</th><th>Estado</th><th>Acciones</th> </thead>
                    <tbody>
                        <?php foreach ($correos as $c): ?>
                        <tr>
                            <td>
                                <span style="font-weight: 700; color: var(--c-gold);"><?= date('d/m/Y', strtotime($c['created_at'])) ?></span><br>
                                <small class="text-white-50"><?= date('H:i', strtotime($c['created_at'])) ?></small>
                            </td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="rounded-circle bg-violet d-flex align-items-center justify-content-center" style="width: 35px; height: 35px; background: rgba(139, 92, 246, 0.2); border: 1px solid rgba(139, 92, 246, 0.3);">
                                        <i class="fas fa-user text-violet" style="font-size: 0.8rem; color: var(--c-violet);"></i>
                                    </div>
                                    <div>
                                        <strong class="text-white"><?= htmlspecialchars($c['nombres'] ?? '—') ?></strong><br>
                                        <small class="text-white-50"><?= htmlspecialchars($c['persona_email']) ?></small>
                                    </div>
                                </div>
                            </td>
                            <td><span class="text-white"><?= htmlspecialchars($c['asunto']) ?></span></td>
                            <td>
                                <?php
                                $tipos = ['vencido'=>'Vencido','por_vencer'=>'Por vencer','danado'=>'Dañado','manual'=>'Manual'];
                                $tipo = $tipos[$c['tipo_motivo']] ?? $c['tipo_motivo'];
                                echo "<span class='badge' style='background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.1); color: #fff; border-radius: 8px; font-weight: 700;'>{$tipo}</span>";
                                ?>
                            </td>
                            <td>
                                <?php if ($c['email_enviado']): ?>
                                    <span class="badge-estado status-enviado text-white"><i class="fas fa-check-circle"></i> Enviado</span>
                                <?php else: ?>
                                    <span class="badge-estado status-fallido text-white"><i class="fas fa-times-circle"></i> Fallido</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <button class="btn btn-view" onclick='verDetalle(<?= json_encode($c) ?>)'>
                                    <i class="fas fa-eye me-1"></i> Detalles
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>

<div class="modal fade" id="detalleModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-envelope-open-text me-2"></i>Detalle del correo</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="modalContent"></div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-outline-light rounded-pill px-4" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    function verDetalle(correo) {
        let statusHtml = correo.email_enviado 
            ? `<span class="badge-estado status-enviado"><i class="fas fa-check-circle"></i> ENVIADO CORRECTAMENTE</span>`
            : `<span class="badge-estado status-fallido"><i class="fas fa-times-circle"></i> ERROR: NO SE ENVIÓ</span>`;
        
        let html = `
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="detail-item">
                        <span class="detail-label">Destinatario</span>
                        <div class="text-white">${correo.nombres || '—'}</div>
                        <div class="text-white-50 small">${correo.persona_email}</div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="detail-item">
                        <span class="detail-label">Estado de Envío</span>
                        <div class="mt-1">${statusHtml}</div>
                    </div>
                </div>
                <div class="col-md-8">
                    <div class="detail-item">
                        <span class="detail-label">Asunto</span>
                        <div class="text-white">${correo.asunto}</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="detail-item">
                        <span class="detail-label">Fecha y Hora</span>
                        <div class="text-white">${new Date(correo.created_at).toLocaleString()}</div>
                    </div>
                </div>
                ${correo.error_email ? `
                <div class="col-12">
                    <div class="detail-item" style="border-left: 4px solid var(--c-danger); background: rgba(244, 63, 94, 0.1);">
                        <span class="detail-label" style="color: var(--c-danger);">Detalle del Error</span>
                        <div class="detail-status-fallido">${correo.error_email}</div>
                    </div>
                </div>` : ''}
                <div class="col-12">
                    <div class="detail-item">
                        <span class="detail-label">Contenido del Mensaje</span>
                        <div class="message-box mt-2">${correo.mensaje}</div>
                    </div>
                </div>
            </div>
        `;
        document.getElementById('modalContent').innerHTML = html;
        new bootstrap.Modal(document.getElementById('detalleModal')).show();
    }
</script>
</body>
</html>