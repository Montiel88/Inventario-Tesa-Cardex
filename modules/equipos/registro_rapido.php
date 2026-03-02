<?php
session_start();
require_once '../../config/permisos.php';
verificarSesion();
requiereAdmin(); // Solo admin puede usar registro rápido

require_once '../../config/database.php';
include '../../includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="card">
        <div class="card-header">
            <h4><i class="fas fa-bolt me-2"></i>Registro Rápido de Equipos</h4>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <h5>Paso 1: Escanear código</h5>
                    <div id="reader" style="width: 100%; min-height: 300px;"></div>
                    
                    <div class="mt-3">
                        <label>Código escaneado:</label>
                        <input type="text" id="codigo_barras" class="form-control" readonly>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <h5>Paso 2: Completar datos</h5>
                    <form id="formEquipo">
                        <input type="hidden" name="codigo_barras" id="hidden_codigo">
                        
                        <div class="mb-3">
                            <label>Tipo de equipo *</label>
                            <select name="tipo_equipo" id="tipo_equipo" class="form-control" required>
                                <option value="">Seleccione...</option>
                                <option value="Laptop">💻 Laptop</option>
                                <option value="Mouse">🖱️ Mouse</option>
                                <option value="Teclado">⌨️ Teclado</option>
                                <option value="Monitor">🖥️ Monitor</option>
                                <option value="Impresora">🖨️ Impresora</option>
                                <option value="Proyector">📽️ Proyector</option>
                                <option value="Tablet">📱 Tablet</option>
                                <option value="Celular">📞 Celular</option>
                                <option value="Otro">🔧 Otro</option>
                            </select>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label>Marca</label>
                                <input type="text" name="marca" class="form-control">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label>Modelo</label>
                                <input type="text" name="modelo" class="form-control">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label>Número de serie</label>
                            <input type="text" name="numero_serie" class="form-control">
                        </div>
                        
                        <div class="mb-3">
                            <label>Especificaciones</label>
                            <textarea name="especificaciones" class="form-control" rows="3"></textarea>
                        </div>
                        
                        <button type="submit" class="btn btn-primary btn-lg w-100">
                            <i class="fas fa-save me-2"></i>Registrar Equipo
                        </button>
                    </form>
                    
                    <div id="mensaje" class="mt-3"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script>
let html5QrCode = new Html5Qrcode("reader");

html5QrCode.start(
    { facingMode: "environment" },
    { fps: 10, qrbox: { width: 250, height: 250 } },
    (decodedText) => {
        document.getElementById('codigo_barras').value = decodedText;
        document.getElementById('hidden_codigo').value = decodedText;
        html5QrCode.stop();
        
        Swal.fire({
            icon: 'success',
            title: 'Código escaneado',
            text: decodedText,
            timer: 1500
        });
    },
    (error) => {}
);

document.getElementById('formEquipo').addEventListener('submit', function(e) {
    e.preventDefault();
    
    if (!document.getElementById('hidden_codigo').value) {
        Swal.fire('Error', 'Primero escanee un código', 'warning');
        return;
    }
    
    let datos = {
        codigo_barras: document.getElementById('hidden_codigo').value,
        tipo_equipo: document.getElementById('tipo_equipo').value,
        marca: document.querySelector('input[name="marca"]').value,
        modelo: document.querySelector('input[name="modelo"]').value,
        numero_serie: document.querySelector('input[name="numero_serie"]').value,
        especificaciones: document.querySelector('textarea[name="especificaciones"]').value
    };
    
    fetch('/inventario_ti/api/registrar_equipo_rapido.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(datos)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: '¡Registrado!',
                text: 'Equipo guardado correctamente',
                showConfirmButton: true
            }).then(() => {
                location.reload();
            });
        } else {
            Swal.fire('Error', data.mensaje, 'error');
        }
    });
});
</script>

<?php include '../../includes/footer.php'; ?>