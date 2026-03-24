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
        body {
            background: linear-gradient(145deg, #f5f9ff 0%, #ecf3fa 100%);
            font-family: 'Inter', sans-serif;
        }
        .page-header {
            background: linear-gradient(135deg, #5a2d8c, #3d1e5e);
            padding: 2rem 0;
            margin-bottom: 2rem;
            border-bottom: 3px solid #f3b229;
        }
        .table-modern {
            background: white;
            border-radius: 28px;
            overflow: hidden;
            box-shadow: 0 15px 35px -10px rgba(0,0,0,0.05);
        }
        .table-modern th {
            background: #f8fafc;
            font-weight: 600;
            border-bottom: 2px solid #e2e8f0;
        }
        .badge-estado {
            padding: 0.3rem 0.8rem;
            border-radius: 40px;
            font-weight: 500;
        }
        .btn-view {
            background: #f1f5f9;
            border: none;
            border-radius: 40px;
            padding: 0.3rem 1rem;
        }
        .modal-content {
            border-radius: 28px;
            border: none;
        }
        .modal-header {
            background: linear-gradient(135deg, #5a2d8c, #3d1e5e);
            color: white;
            border-radius: 28px 28px 0 0;
        }
        .empty-state {
            text-align: center;
            padding: 4rem;
        }
    </style>
</head>
<body>
<div class="page-header">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="text-white mb-0"><i class="fas fa-history me-2"></i>Historial de Correos</h1>
                <p class="text-white-50 mt-2">Registro de todas las notificaciones enviadas</p>
            </div>
            <a href="listar.php" class="btn btn-outline-light rounded-pill px-4">← Volver</a>
        </div>
    </div>
</div>

<div class="container pb-5">
    <?php if (!$tabla_existe): ?>
        <div class="alert alert-warning rounded-4 shadow-sm">
            <i class="fas fa-info-circle me-2"></i> La tabla de historial aún no existe. Los registros se guardarán automáticamente cuando envíes correos.
        </div>
    <?php elseif (empty($correos)): ?>
        <div class="empty-state">
            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
            <h5>No hay correos registrados</h5>
            <p>Envía tu primer correo desde la gestión.</p>
            <a href="listar.php" class="btn btn-primary rounded-pill px-4">Ir a gestión</a>
        </div>
    <?php else: ?>
        <div class="table-modern">
            <table class="table align-middle mb-0">
                <thead>
                    <tr><th>Fecha</th><th>Destinatario</th><th>Asunto</th><th>Tipo</th><th>Estado</th><th>Acciones</th> </thead>
                <tbody>
                    <?php foreach ($correos as $c): ?>
                    <tr>
                        <td><?= date('d/m/Y H:i', strtotime($c['created_at'])) ?></td>
                        <td><strong><?= htmlspecialchars($c['nombres'] ?? '—') ?></strong><br><small><?= htmlspecialchars($c['persona_email']) ?></small></td>
                        <td><?= htmlspecialchars($c['asunto']) ?></td>
                        <td>
                            <?php
                            $tipos = ['vencido'=>'Vencido','por_vencer'=>'Por vencer','danado'=>'Dañado','manual'=>'Manual'];
                            $tipo = $tipos[$c['tipo_motivo']] ?? $c['tipo_motivo'];
                            echo "<span class='badge bg-secondary'>{$tipo}</span>";
                            ?>
                        </td>
                        <td>
                            <?php if ($c['email_enviado']): ?>
                                <span class="badge-estado bg-success text-white"><i class="fas fa-check-circle me-1"></i> Enviado</span>
                            <?php else: ?>
                                <span class="badge-estado bg-danger text-white"><i class="fas fa-times-circle me-1"></i> Fallido</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <button class="btn-view" onclick="verDetalle(<?= htmlspecialchars(json_encode($c)) ?>)"><i class="fas fa-eye"></i> Ver</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
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
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    function verDetalle(correo) {
        let html = `
            <div class="mb-3">
                <strong>Destinatario:</strong> ${correo.nombres || '—'} (${correo.persona_email})<br>
                <strong>Asunto:</strong> ${correo.asunto}<br>
                <strong>Fecha:</strong> ${new Date(correo.created_at).toLocaleString()}<br>
                <strong>Estado:</strong> ${correo.email_enviado ? '✅ Enviado' : '❌ Fallido'}<br>
                ${correo.error_email ? `<strong>Error:</strong> ${correo.error_email}<br>` : ''}
            </div>
            <div class="mt-3 p-3 bg-light rounded">
                <strong>Mensaje:</strong><br>
                ${correo.mensaje.replace(/\n/g, '<br>')}
            </div>
        `;
        document.getElementById('modalContent').innerHTML = html;
        new bootstrap.Modal(document.getElementById('detalleModal')).show();
    }
</script>
</body>
</html>