<?php
// ============================================
// INICIO: MANEJO DE PETICIÓN AJAX/XHR PRIMERO
// (SIN NINGÚN OUTPUT ANTES - para que el JSON sea limpio)
// ============================================
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: /inventario_ti/login.php');
    exit();
}
$es_admin = ($_SESSION['user_rol'] == 1);
if (!$es_admin) {
    header('Location: listar.php?error=No tienes permisos');
    exit();
}

require_once '../../config/database.php';
require_once '../../config/listas.php';
require_once '../../config/notificaciones_helper.php';

// Obtener Bodega Principal (antes de cualquier cosa)
$bodega_principal = $conn->query("SELECT id, codigo_ubicacion, nombre FROM ubicaciones WHERE id = 6")->fetch_assoc();
$ubicacion_default_id = $bodega_principal['id'] ?? 6;
$ubicacion_default_label = ($bodega_principal['codigo_ubicacion'] ?? 'BOD-01') . ' - ' . ($bodega_principal['nombre'] ?? 'Bodega Principal');

// ============================================
// DETECTAR PETICIÓN AJAX (X-Requested-With: XMLHttpRequest)
// ============================================
$esAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest');

if ($_SERVER['REQUEST_METHOD'] == 'POST' && $esAjax) {
    header('Content-Type: application/json; charset=utf-8');
    ob_clean();
    
    $codigo_barras = trim($conn->real_escape_string($_POST['codigo_barras'] ?? ''));
    $tipo_equipo = $conn->real_escape_string($_POST['tipo_equipo'] ?? '');
    $marca = $conn->real_escape_string($_POST['marca'] ?? '');
    $modelo = $conn->real_escape_string($_POST['modelo'] ?? '');
    $numero_serie = $conn->real_escape_string($_POST['numero_serie'] ?? '');
    $especificaciones = $conn->real_escape_string($_POST['especificaciones'] ?? '');
    $observaciones = $conn->real_escape_string($_POST['observaciones'] ?? '');
    $ubicacion_id = $ubicacion_default_id;
    $estado = 'Disponible';
    $errores = [];

    // Procesar foto (solo se guarda en disco, no en BD)
    $foto_ruta = '';
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['foto']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if (in_array($ext, $allowed)) {
            $carpeta_fotos = '../../uploads/equipos/';
            if (!file_exists($carpeta_fotos)) {
                @mkdir($carpeta_fotos, 0777, true);
            }
            
            $nuevo_nombre = 'equipo_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
            $destino = $carpeta_fotos . $nuevo_nombre;
            
            if (@move_uploaded_file($_FILES['foto']['tmp_name'], $destino)) {
                $foto_ruta = 'uploads/equipos/' . $nuevo_nombre;
            }
        }
    }

    if (empty($tipo_equipo)) {
        $errores[] = "El tipo de equipo es obligatorio. Por favor seleccione una opción.";
    }

    if (!empty($codigo_barras)) {
        $check = $conn->query("SELECT id FROM equipos WHERE codigo_barras = '$codigo_barras'");
        if ($check && $check->num_rows > 0) {
            $errores[] = "El código de barras '$codigo_barras' ya existe. Usa otro o déjalo vacío.";
        }
    } else {
        $result = $conn->query("SELECT MAX(id) as max_id FROM equipos");
        $row = $result ? $result->fetch_assoc() : ['max_id' => 0];
        $next_id = ($row['max_id'] ?? 0) + 1;
        $codigo_barras = 'PRO-' . str_pad($next_id, 6, '0', STR_PAD_LEFT);
    }

    if (empty($errores)) {
        $ubicacion_id_int = intval($ubicacion_id);
        $sql = "INSERT INTO equipos (codigo_barras, tipo_equipo, marca, modelo, numero_serie, especificaciones, observaciones, ubicacion_id, estado, activo) 
                VALUES ('$codigo_barras', '$tipo_equipo', '$marca', '$modelo', '$numero_serie', '$especificaciones', '$observaciones', $ubicacion_id_int, '$estado', 1)";

        if ($conn->query($sql)) {
            $equipo_id = $conn->insert_id;
            
            registrar_notificacion(
                $_SESSION['user_id'],
                'success',
                '🖥️ Equipo agregado',
                "Se agregó {$tipo_equipo} con código {$codigo_barras}",
                "/inventario_ti/modules/equipos/detalle.php?id=" . $equipo_id
            );
            
            require_once '../../includes/logs_functions.php';
            registrarLog($conn, 'Crear equipo', "Código: {$codigo_barras}, Tipo: {$tipo_equipo}", $_SESSION['user_id']);
            
            echo json_encode([
                'success' => true,
                'mensaje' => "EQUIPO REGISTRADO EXITOSAMENTE",
                'equipo_id' => $equipo_id,
                'codigo_barras' => $codigo_barras,
                'tipo_equipo' => $tipo_equipo,
                'acta_url' => "/inventario_ti/api/generar_acta_ingreso.php?equipo_id=$equipo_id",
                'listado_url' => "/inventario_ti/modules/equipos/listar.php",
                'inicio_url' => "/inventario_ti/modules/dashboard.php",
                'otro_url' => "/inventario_ti/modules/equipos/agregar.php"
            ], JSON_UNESCAPED_UNICODE);
            exit;
        } else {
            echo json_encode([
                'success' => false,
                'errores' => ["Error al guardar en BD: " . $conn->error]
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
    } else {
        echo json_encode([
            'success' => false,
            'errores' => $errores
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

// ============================================
// A PARTIR DE AQUÍ SE RENDERIZA LA VISTA HTML
// ============================================
include '../../includes/header.php';

// Asegurar que SweetAlert2 esté disponible
echo '<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>';
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0"><i class="fas fa-plus-circle me-2"></i>Agregar Nuevo Equipo</h4>
                </div>
                <div class="card-body">
                    
                    <div id="alertasContainer"></div>

                    <form id="formAgregarEquipo" method="POST" enctype="multipart/form-data" onsubmit="return false;">
                        <input type="hidden" name="ubicacion_id" value="<?php echo $ubicacion_default_id; ?>">
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Código de Barras</label>
                                <input type="text" name="codigo_barras" id="codigo_barras" class="form-control" 
                                       placeholder="Ingrese código del equipo o deje vacío">
                                <small class="text-muted">
                                    ✅ Si el equipo tiene su propio código, ingrésalo aquí.<br>
                                    ✅ Si no tiene o no se puede leer, déjalo vacío y el sistema generará uno interno (ej: PRO-000123).
                                </small>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Tipo de Equipo *</label>
                                <select name="tipo_equipo" id="tipo_equipo" class="form-control" required>
                                    <option value="">-- Seleccione --</option>
                                    <?php foreach($tipos_equipos as $valor => $etiqueta): ?>
                                        <option value="<?php echo htmlspecialchars($valor); ?>"><?php echo htmlspecialchars($etiqueta); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="text-muted">📋 <?php echo count($tipos_equipos); ?> tipos disponibles</small>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Marca</label>
                                <input type="text" name="marca" id="marca" class="form-control" placeholder="Ej: HP, Dell, Logitech">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Modelo</label>
                                <input type="text" name="modelo" id="modelo" class="form-control" placeholder="Ej: Pavilion, Latitude">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Número de Serie</label>
                                <input type="text" name="numero_serie" id="numero_serie" class="form-control" placeholder="Serie del fabricante">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Ubicación (Ingreso Inicial)</label>
                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($ubicacion_default_label); ?>" readonly style="background-color: #f8f9fa; font-weight: 500;">
                                <small class="text-muted"><i class="fas fa-warehouse me-1"></i> Todo equipo nuevo ingresa automáticamente a Bodega Principal</small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Foto del Equipo (Opcional)</label>
                                <input type="file" name="foto" id="foto" class="form-control" accept="image/*">
                                <small class="text-muted">La foto se guardará en disco pero no en la BD (por ahora).</small>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Especificaciones</label>
                            <textarea name="especificaciones" id="especificaciones" class="form-control" rows="3" placeholder="RAM, procesador, disco duro, etc."></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Observaciones</label>
                            <textarea name="observaciones" id="observaciones" class="form-control" rows="2" placeholder="Notas adicionales"></textarea>
                        </div>
                        
                        <div class="text-center mt-3">
                            <button type="button" id="btnGuardarEquipo" class="btn btn-primary btn-lg px-5">
                                <i class="fas fa-save me-2"></i>Guardar Equipo
                            </button>
                            <a href="listar.php" class="btn btn-secondary btn-lg px-5">
                                <i class="fas fa-arrow-left me-2"></i>Cancelar
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// ============================================
// HELPER: Escape HTML para SweetAlert
// ============================================
function escapeHtml(str) {
    if (str === null || str === undefined) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

// ============================================
// MOSTRAR / LIMPIAR ALERTAS
// ============================================
function mostrarErrores(errores) {
    let html = '';
    errores.forEach(function(err) {
        html += '<div class="alert alert-danger alert-dismissible fade show" role="alert">' +
                '<i class="fas fa-exclamation-triangle me-2"></i> ❌ ' + escapeHtml(err) +
                '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>' +
                '</div>';
    });
    document.getElementById('alertasContainer').innerHTML = html;
    document.getElementById('alertasContainer').scrollIntoView({ behavior: 'smooth', block: 'start' });
}

function limpiarAlertas() {
    document.getElementById('alertasContainer').innerHTML = '';
}

// ============================================
// VALIDACIÓN LOCAL
// ============================================
function validarFormulario() {
    let errores = [];
    const tipoSel = document.getElementById('tipo_equipo');
    const tipo = tipoSel.value.trim();
    
    if (!tipo) {
        errores.push('El tipo de equipo es obligatorio. Por favor seleccione una opción de la lista.');
        tipoSel.focus();
        tipoSel.classList.add('is-invalid');
    } else {
        tipoSel.classList.remove('is-invalid');
    }
    
    return errores;
}

// ============================================
// CLICK EN BOTÓN GUARDAR (FLUJO COMPLETO)
// ============================================
document.getElementById('btnGuardarEquipo').addEventListener('click', function() {
    limpiarAlertas();
    
    const erroresLocales = validarFormulario();
    if (erroresLocales.length > 0) {
        mostrarErrores(erroresLocales);
        return;
    }
    
    // Resumen para confirmación
    const tipoEquipoTexto = document.getElementById('tipo_equipo').options[document.getElementById('tipo_equipo').selectedIndex].text;
    const marca = document.getElementById('marca').value.trim() || '(sin especificar)';
    const modelo = document.getElementById('modelo').value.trim() || '(sin especificar)';
    const serie = document.getElementById('numero_serie').value.trim() || '(sin especificar)';
    const codigo = document.getElementById('codigo_barras').value.trim() || '(se generará automáticamente)';
    
    let resumenHtml = '<div style="text-align: left; font-size: 0.9rem;">' +
        '<p style="margin:4px 0;"><strong>📌 Tipo:</strong> ' + escapeHtml(tipoEquipoTexto) + '</p>' +
        '<p style="margin:4px 0;"><strong>🏷️ Código:</strong> ' + escapeHtml(codigo) + '</p>' +
        '<p style="margin:4px 0;"><strong>🏢 Marca:</strong> ' + escapeHtml(marca) + '</p>' +
        '<p style="margin:4px 0;"><strong>🔧 Modelo:</strong> ' + escapeHtml(modelo) + '</p>' +
        '<p style="margin:4px 0;"><strong>🔢 Serie:</strong> ' + escapeHtml(serie) + '</p>' +
        '<p style="margin:4px 0;"><strong>📍 Ubicación:</strong> BOD-01 - Bodega Principal</p>' +
        '</div>';
    
    Swal.fire({
        title: '¿Confirmar registro de equipo?',
        html: resumenHtml,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: '<i class="fas fa-check me-1"></i> Sí, guardar',
        cancelButtonText: '<i class="fas fa-times me-1"></i> Cancelar',
        confirmButtonColor: '#28a745',
        cancelButtonColor: '#6c757d'
    }).then((result) => {
        if (!result.isConfirmed) return;
        
        const btn = document.getElementById('btnGuardarEquipo');
        btn.disabled = true;
        const originalHtml = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Guardando...';
        
        Swal.fire({
            title: 'Guardando equipo...',
            text: 'Por favor espere mientras se registra la información',
            allowOutsideClick: false,
            allowEscapeKey: false,
            didOpen: () => Swal.showLoading()
        });
        
        const formData = new FormData(document.getElementById('formAgregarEquipo'));
        
        const xhr = new XMLHttpRequest();
        xhr.open('POST', window.location.pathname || window.location.href, true);
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        
        xhr.onload = function() {
            btn.disabled = false;
            btn.innerHTML = originalHtml;
            Swal.close();
            
            const raw = xhr.responseText.trim();
            console.log('Respuesta servidor:', raw);
            
            try {
                const respuesta = JSON.parse(raw);
                
                if (respuesta.success) {
                    // ============================================
                    // ✅ MODAL DE ÉXITO - 3 OPCIONES (ESTÁNDAR)
                    // ============================================
                    Swal.fire({
                        icon: 'success',
                        title: '✅ EQUIPO REGISTRADO EXITOSAMENTE',
                        html: '<div class="text-start">' +
                              '<p><strong>Código:</strong> ' + escapeHtml(respuesta.codigo_barras) + '</p>' +
                              '<p><strong>Tipo:</strong> ' + escapeHtml(respuesta.tipo_equipo) + '</p>' +
                              '<p><strong>Ubicación:</strong> BOD-01 - Bodega Principal</p>' +
                              '</div><hr><p class="mb-0">¿Qué desea hacer ahora?</p>',
                        showDenyButton: true,
                        showCancelButton: true,
                        confirmButtonText: '<i class="fas fa-list me-1"></i> Ver Listado',
                        denyButtonText: '<i class="fas fa-plus-circle me-1"></i> Registrar Otro Equipo',
                        cancelButtonText: '<i class="fas fa-home me-1"></i> Volver al Inicio',
                        confirmButtonColor: '#28a745',
                        denyButtonColor: '#007bff',
                        cancelButtonColor: '#6c757d',
                        allowOutsideClick: false,
                        allowEscapeKey: false
                    }).then((swalResult) => {
                        if (swalResult.isConfirmed) {
                            // 1. VER LISTADO
                            window.location.href = respuesta.listado_url;
                        } else if (swalResult.isDenied) {
                            // 2. REGISTRAR OTRO EQUIPO (limpiar formulario + acta opcional)
                            Swal.fire({
                                title: '¿Generar acta de ingreso antes?',
                                text: 'Puede generar el PDF para imprimir el comprobante',
                                icon: 'question',
                                showCancelButton: true,
                                confirmButtonText: '<i class="fas fa-file-pdf me-1"></i> Sí, generar acta',
                                cancelButtonText: '<i class="fas fa-forward me-1"></i> No, continuar',
                                confirmButtonColor: '#dc3545'
                            }).then((actaRes) => {
                                if (actaRes.isConfirmed) {
                                    window.open(respuesta.acta_url, '_blank');
                                }
                                document.getElementById('formAgregarEquipo').reset();
                                document.getElementById('tipo_equipo').focus();
                                Swal.fire({
                                    icon: 'info',
                                    title: 'Formulario reiniciado',
                                    text: 'Listo para registrar otro equipo en Bodega Principal',
                                    timer: 1800,
                                    showConfirmButton: false
                                });
                            });
                        } else {
                            // 3. VOLVER AL INICIO (Dashboard)
                            window.location.href = respuesta.inicio_url;
                        }
                    });
                } else {
                    // ❌ Errores del backend
                    if (respuesta.errores && respuesta.errores.length > 0) {
                        mostrarErrores(respuesta.errores);
                        Swal.fire({
                            icon: 'error',
                            title: 'No se pudo guardar el equipo',
                            html: respuesta.errores.map(function(e) { return '• ' + escapeHtml(e); }).join('<br>'),
                            confirmButtonText: 'Entendido'
                        });
                    } else {
                        mostrarErrores(['Ocurrió un error desconocido al guardar.']);
                    }
                }
            } catch (e) {
                console.error('JSON Parse Error:', e, '| Raw:', raw);
                mostrarErrores([
                    'Respuesta inesperada del servidor. Detalle técnico en consola.',
                    'Primeros 200 caracteres recibidos: ' + raw.substring(0, 200)
                ]);
            }
        };
        
        xhr.onerror = function() {
            btn.disabled = false;
            btn.innerHTML = originalHtml;
            Swal.close();
            mostrarErrores(['Error de conexión con el servidor. Revise su conexión e inténtelo de nuevo.']);
        };
        
        xhr.send(formData);
    });
});

// ============================================
// BLOQUEAR ENVÍO POR ENTER / SUBMIT TRADICIONAL
// ============================================
document.getElementById('formAgregarEquipo').addEventListener('submit', function(e) {
    e.preventDefault();
    return false;
});
</script>

<?php include '../../includes/footer.php'; ?>
