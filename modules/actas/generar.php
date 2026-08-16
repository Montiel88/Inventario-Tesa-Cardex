<?php
session_start();
require_once '../../config/permisos.php';
verificarSesion();

require_once '../../config/database.php';
require_once '../../config/actas_config.php';
include '../../includes/header.php';

 = '';
 = '';

// Procesar el formulario al enviar
if (['REQUEST_METHOD'] == 'POST') {
     = ['tipo_acta'] ?? '';
     = intval(['persona_id'] ?? 0);
     = ['equipos_ids'] ?? ''; // String separado por comas
     = ['motivo'] ?? '';
     = ['user_id'];

    if (empty()) {
         = "Debe seleccionar un tipo de acta.";
    } elseif (empty()) {
         = "Debe seleccionar al menos un equipo.";
    } else {
        // Generar código de acta usando tu función existente
         = generarCodigoActa();
        
        // Insertar en la tabla actas
         = "INSERT INTO actas (codigo_acta, tipo_acta, persona_id, usuario_id, equipos_ids, fecha_generacion) 
                VALUES ('', '', , , '', NOW())";
        
        if (->query()) {
             = ->insert_id;
            // Redirigir a detalle
            header("Location: detalle.php?id=&success=Acta generada correctamente");
            exit();
        } else {
             = "Error al guardar el acta: " . ->error;
        }
    }
}

// Obtener lista de personas
 = ->query("SELECT id, nombres FROM personas ORDER BY nombres");
?>

<div class="container-fluid py-4">
    <div class="card">
        <div class="card-header">
            <h4><i class="fas fa-file-pdf me-2"></i>Generar Nueva Acta</h4>
        </div>
        <div class="card-body">
            <?php if (): ?>
                <div class="alert alert-danger"><?php echo ; ?></div>
            <?php endif; ?>

            <form method="POST" id="formGenerarActa">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Tipo de Acta <span class="text-danger">*</span></label>
                        <select name="tipo_acta" class="form-control" id="tipoActa" required>
                            <option value="">-- Seleccione --</option>
                            <option value="ingreso">Ingreso de Inventario (Masivo o Individual)</option>
                            <option value="entrega">Acta de Entrega (Onboarding)</option>
                            <option value="devolucion">Acta de Devolución (Offboarding)</option>
                            <option value="traspaso">Acta de Traspaso</option>
                            <option value="baja">Acta de Baja</option>
                        </select>
                    </div>

                    <div class="col-md-6 mb-3" id="divPersona" style="display: none;">
                        <label class="form-label">Persona (Custodio) <span class="text-danger">*</span></label>
                        <select name="persona_id" class="form-control">
                            <option value="0">-- Sin persona (para Ingresos o Bajas) --</option>
                            <?php while( = ->fetch_assoc()): ?>
                                <option value="<?php echo ['id']; ?>"><?php echo ['nombres']; ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Seleccionar Equipo(s) <span class="text-danger">*</span></label>
                    <div class="input-group mb-2">
                        <input type="text" id="buscadorEquipos" class="form-control" placeholder="Buscar equipo por código o nombre...">
                        <button class="btn btn-secondary" type="button" id="btnAgregarEquipo">Agregar</button>
                    </div>
                    <div id="listaEquiposSeleccionados" class="list-group mb-2">
                        <!-- Aquí se agregarán los equipos seleccionados -->
                    </div>
                    <input type="hidden" name="equipos_ids" id="equipos_ids" value="">
                    <small class="text-muted">Busca y agrega equipos. Para ingreso masivo, repite el proceso con cada equipo.</small>
                </div>

                <div class="mb-3" id="divMotivo" style="display: none;">
                    <label class="form-label">Motivo de la Baja <span class="text-danger">*</span></label>
                    <textarea name="motivo" class="form-control" rows="2"></textarea>
                </div>

                <div class="text-center mt-4">
                    <button type="submit" class="btn btn-primary btn-lg px-5">
                        <i class="fas fa-file-pdf me-2"></i>Generar Acta
                    </button>
                    <a href="index.php" class="btn btn-secondary btn-lg px-5">
                        <i class="fas fa-arrow-left me-2"></i>Cancelar
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('tipoActa').addEventListener('change', function() {
    const tipo = this.value;
    document.getElementById('divPersona').style.display = (tipo === 'entrega' || tipo === 'devolucion' || tipo === 'traspaso') ? 'block' : 'none';
    document.getElementById('divMotivo').style.display = (tipo === 'baja') ? 'block' : 'none';
});

let equiposSeleccionados = [];

document.getElementById('btnAgregarEquipo').addEventListener('click', function() {
    const query = document.getElementById('buscadorEquipos').value;
    if (query.length < 2) return;

    // Búsqueda simulada o llamada AJAX
    fetch('/inventario_ti/api/buscar_producto.php?codigo=' + encodeURIComponent(query))
        .then(res => res.json())
        .then(data => {
            if (data.success && data.equipo) {
                const eq = data.equipo;
                if (!equiposSeleccionados.find(e => e.id == eq.id)) {
                    equiposSeleccionados.push(eq);
                    renderizarEquipos();
                } else {
                    alert('Este equipo ya fue agregado.');
                }
            } else {
                alert('Equipo no encontrado. Asegúrate de que esté registrado.');
            }
        });
});

function renderizarEquipos() {
    const container = document.getElementById('listaEquiposSeleccionados');
    const hiddenInput = document.getElementById('equipos_ids');
    container.innerHTML = '';
    let ids = [];
    equiposSeleccionados.forEach((eq, index) => {
        ids.push(eq.id);
        container.innerHTML += 
            <div class="list-group-item d-flex justify-content-between align-items-center">
                 -  ( )
                <button class="btn btn-sm btn-danger" onclick="eliminarEquipo()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        ;
    });
    hiddenInput.value = ids.join(',');
}

function eliminarEquipo(index) {
    equiposSeleccionados.splice(index, 1);
    renderizarEquipos();
}

// Permitir agregar con Enter
document.getElementById('buscadorEquipos').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        document.getElementById('btnAgregarEquipo').click();
    }
});
</script>
<?php include '../../includes/footer.php'; ?>
