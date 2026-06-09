<?php
session_start();
require_once '../../config/database.php';
require_once '../../config/permisos.php';
verificarSesion();
requiereAdmin();

include '../../includes/header.php';

$mensaje = '';
$error = '';

// Procesar guardado de configuración
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    foreach ($_POST as $clave => $valor) {
        if ($clave != 'guardar') {
            $valor = $conn->real_escape_string($valor);
            $sql = "UPDATE configuracion SET valor = '$valor' WHERE clave = '$clave'";
            $conn->query($sql);
        }
    }
    $mensaje = "✅ Configuración guardada correctamente";
}

// Obtener configuración actual
$sql = "SELECT * FROM configuracion WHERE modificable = 1 ORDER BY id";
$result = $conn->query($sql);
$config = [];
while($row = $result->fetch_assoc()) {
    $config[$row['clave']] = $row;
}
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4><i class="fas fa-cog me-2"></i>Configuración de Actas y Documentos</h4>
                </div>
                <div class="card-body">
                    
                    <?php if ($mensaje): ?>
                        <div class="alert alert-success alert-dismissible fade show">
                            <i class="fas fa-check-circle me-2"></i><?php echo $mensaje; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST">
                        <div class="row">
                            <!-- NÚMEROS DE FORMULARIO -->
                            <div class="col-md-6">
                                <div class="card mb-3">
                                    <div class="card-header bg-primary text-white">
                                        <h5 class="mb-0">Números de Formulario</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label class="form-label">Acta de Entrega</label>
                                            <input type="text" name="formulario_entrega" class="form-control" 
                                                   value="<?php echo $config['formulario_entrega']['valor']; ?>">
                                            <small class="text-muted"><?php echo $config['formulario_entrega']['descripcion']; ?></small>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label">Acta de Devolución</label>
                                            <input type="text" name="formulario_devolucion" class="form-control" 
                                                   value="<?php echo $config['formulario_devolucion']['valor']; ?>">
                                            <small class="text-muted"><?php echo $config['formulario_devolucion']['descripcion']; ?></small>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label">Descargo de Responsabilidad</label>
                                            <input type="text" name="formulario_descargo" class="form-control" 
                                                   value="<?php echo $config['formulario_descargo']['valor']; ?>">
                                            <small class="text-muted"><?php echo $config['formulario_descargo']['descripcion']; ?></small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- VERSIÓN Y SECUENCIA -->
                            <div class="col-md-6">
                                <div class="card mb-3">
                                    <div class="card-header bg-warning">
                                        <h5 class="mb-0">Versión y Secuencia</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label class="form-label">Versión</label>
                                            <input type="text" name="version" class="form-control" 
                                                   value="<?php echo $config['version']['valor']; ?>">
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label">Secuencia Actual</label>
                                            <div class="input-group">
                                                <input type="number" name="secuencia_actual" class="form-control" 
                                                       value="<?php echo $config['secuencia_actual']['valor']; ?>" min="1">
                                                <button class="btn btn-outline-secondary" type="button" onclick="this.previousElementSibling.value = parseInt(this.previousElementSibling.value) + 1">
                                                    <i class="fas fa-plus"></i>
                                                </button>
                                            </div>
                                            <small class="text-muted">Este número se incrementa automáticamente con cada acta generada</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- ============================================ -->
                            <!-- DATOS DE QUIEN ENTREGA (EDITABLE) -->
                            <!-- ============================================ -->
                            <div class="col-md-6">
                                <div class="card mb-3">
                                    <div class="card-header bg-success text-white">
                                        <h5 class="mb-0"><i class="fas fa-user-check me-2"></i>Datos de quien ENTREGA</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label class="form-label">Nombre de quien entrega *</label>
                                            <input type="text" name="aprobador_nombre" class="form-control" 
                                                   value="<?php echo $config['aprobador_nombre']['valor']; ?>"
                                                   placeholder="Ej: CYNTHIA VÁZQUEZ JARA">
                                            <small class="text-muted">Este nombre aparecerá en el acta como "ENTREGÓ"</small>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label">Cargo / Departamento</label>
                                            <input type="text" name="aprobador_cargo" class="form-control" 
                                                   value="<?php echo $config['aprobador_cargo']['valor']; ?>"
                                                   placeholder="Ej: Tecnologías de la Información">
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label">Email de contacto</label>
                                            <input type="email" name="email_entrega" class="form-control" 
                                                   value="<?php echo isset($config['email_entrega']) ? $config['email_entrega']['valor'] : ''; ?>"
                                                   placeholder="ejemplo@tesa.edu.ec">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- ============================================ -->
                            <!-- CONFIGURACIÓN DE FIRMAS (SWITCH APROBADOR) -->
                            <!-- ============================================ -->
                            <div class="col-md-6">
                                <div class="card mb-3">
                                    <div class="card-header bg-secondary text-white">
                                        <h5 class="mb-0"><i class="fas fa-pen-fancy me-2"></i>Configuración de Firmas</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" name="mostrar_aprobado" id="mostrar_aprobado" value="1"
                                                       <?php echo (isset($config['mostrar_aprobado']['valor']) && $config['mostrar_aprobado']['valor'] == '1') ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="mostrar_aprobado">
                                                    <strong>Mostrar firma "APROBADO POR"</strong>
                                                </label>
                                            </div>
                                            <small class="text-muted d-block mt-1">Si activas esta opción, aparecerá una tercera firma de aprobación en las actas.</small>
                                        </div>
                                        
                                        <!-- Campos de aprobador (se muestran solo si está activado) -->
                                        <div id="camposAprobador" style="<?php echo (isset($config['mostrar_aprobado']['valor']) && $config['mostrar_aprobado']['valor'] == '1') ? '' : 'display: none;'; ?>">
                                            <hr>
                                            <h6 class="text-muted mb-3">Datos del Aprobador</h6>
                                            <div class="mb-3">
                                                <label class="form-label">Nombre del Aprobador</label>
                                                <input type="text" name="aprobador_aprueba_nombre" class="form-control" 
                                                       value="<?php echo isset($config['aprobador_aprueba_nombre']) ? $config['aprobador_aprueba_nombre']['valor'] : ''; ?>"
                                                       placeholder="Ej: Pablo Morales">
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Cargo del Aprobador</label>
                                                <input type="text" name="aprobador_aprueba_cargo" class="form-control" 
                                                       value="<?php echo isset($config['aprobador_aprueba_cargo']) ? $config['aprobador_aprueba_cargo']['valor'] : ''; ?>"
                                                       placeholder="Ej: Director Área T.I">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- DEPARTAMENTO E INSTITUCIÓN -->
                            <div class="col-md-6">
                                <div class="card mb-3">
                                    <div class="card-header bg-info text-white">
                                        <h5 class="mb-0">Datos de la Institución</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label class="form-label">Departamento que Entrega</label>
                                            <input type="text" name="departamento_entrega" class="form-control" 
                                                   value="<?php echo $config['departamento_entrega']['valor']; ?>">
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label">Nombre de la Institución</label>
                                            <input type="text" name="institucion_nombre" class="form-control" 
                                                   value="<?php echo $config['institucion_nombre']['valor']; ?>">
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label">Ciudad</label>
                                            <input type="text" name="ciudad" class="form-control" 
                                                   value="<?php echo $config['ciudad']['valor']; ?>">
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label">URL del Logo</label>
                                            <input type="text" name="logo_url" class="form-control" 
                                                   value="<?php echo $config['logo_url']['valor']; ?>">
                                            <small class="text-muted">Ruta ejemplo: /inventario_ti/assets/img/logo-tesa.png</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="text-center mt-4">
                            <button type="submit" name="guardar" class="btn btn-primary btn-lg px-5">
                                <i class="fas fa-save me-2"></i>Guardar Configuración
                            </button>
                        </div>
                    </form>
                    
                    <div class="alert alert-info mt-4">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Nota:</strong> 
                        <ul class="mb-0 mt-2">
                            <li>Los campos de "Datos de quien ENTREGA" son los que aparecerán en la firma izquierda del acta.</li>
                            <li>Puedes activar/desactivar la firma "APROBADO POR" según lo necesites.</li>
                            <li>Si desactivas el switch, la tercera firma no aparecerá en las actas.</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Script para mostrar/ocultar campos de aprobador -->
<script>
document.getElementById('mostrar_aprobado').addEventListener('change', function() {
    const camposAprobador = document.getElementById('camposAprobador');
    if (this.checked) {
        camposAprobador.style.display = 'block';
    } else {
        camposAprobador.style.display = 'none';
    }
});
</script>

<?php include '../../includes/footer.php'; ?>