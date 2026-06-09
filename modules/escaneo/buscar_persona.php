<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: /inventario_ti/login.php');
    exit();
}

// Verificar roles si es necesario
$es_admin = ($_SESSION['user_rol'] == 'admin');

// Solo admin puede acceder a ciertas funciones
if (!$es_admin && strpos($_SERVER['PHP_SELF'], 'eliminar.php') !== false) {
    header('Location: dashboard.php?error=No tienes permisos');
    exit();
}
?><?php
include '../../includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4><i class="fas fa-search me-2"></i>Buscador de Personas</h4>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Buscar por cédula o nombre:</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                                    <input type="text" id="buscador" class="form-control" placeholder="Escriba para buscar..." autofocus>
                                    <button class="btn btn-primary" onclick="buscarPersona()">
                                        <i class="fas fa-arrow-right"></i>
                                    </button>
                                </div>
                                <small class="text-muted">Mínimo 2 caracteres</small>
                            </div>
                            
                            <div class="mt-3">
                                <h5>Escáner QR (cédula)</h5>
                                <div id="reader" style="width: 100%; min-height: 250px;"></div>
                                <p class="mt-2 text-center">
                                    <button class="btn btn-sm btn-warning" onclick="iniciarScanner()">
                                        <i class="fas fa-camera"></i> Iniciar cámara
                                    </button>
                                </p>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div id="resultados" style="display: none;">
                                <h5>Resultados:</h5>
                                <div id="listaResultados" class="list-group"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
let html5QrCode = null;

function iniciarScanner() {
    if (html5QrCode) {
        html5QrCode.stop();
    }
    
    html5QrCode = new Html5Qrcode("reader");
    const config = { fps: 10, qrbox: { width: 250, height: 250 } };
    
    html5QrCode.start(
        { facingMode: "environment" },
        config,
        (decodedText) => {
            document.getElementById('buscador').value = decodedText;
            buscarPersona();
        },
        (error) => {}
    );
}

let timeoutId;
document.getElementById('buscador').addEventListener('input', function() {
    clearTimeout(timeoutId);
    let termino = this.value;
    
    if (termino.length < 2) {
        document.getElementById('resultados').style.display = 'none';
        return;
    }
    
    timeoutId = setTimeout(() => {
        buscarPersona();
    }, 500);
});

function buscarPersona() {
    let termino = document.getElementById('buscador').value;
    
    if (termino.length < 2) {
        Swal.fire('Atención', 'Ingrese al menos 2 caracteres', 'warning');
        return;
    }
    
    fetch(`/inventario_ti/api/buscar_persona.php?q=${termino}`)
        .then(response => response.json())
        .then(data => {
            let divResultados = document.getElementById('resultados');
            let lista = document.getElementById('listaResultados');
            
            if (data.success && data.resultados.length > 0) {
                let html = '';
                data.resultados.forEach(persona => {
                    html += `
                        <div class="list-group-item list-group-item-action" style="cursor: pointer;" onclick="verPersona(${persona.id})">
                            <div class="d-flex justify-content-between">
                                <h6 class="mb-1">${persona.nombres}</h6>
                                <small class="text-muted">${persona.cedula}</small>
                            </div>
                            <p class="mb-1">${persona.cargo}</p>
                            <small class="text-muted">${persona.correo || 'Sin correo'}</small>
                        </div>
                    `;
                });
                lista.innerHTML = html;
                divResultados.style.display = 'block';
            } else {
                lista.innerHTML = '<div class="alert alert-warning">No se encontraron personas</div>';
                divResultados.style.display = 'block';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire('Error', 'No se pudo conectar con el servidor', 'error');
        });
}

function verPersona(id) {
    window.location.href = `http://localhost/inventario_ti/modules/personas/ver.php?id=${id}`;
}

// Iniciar escáner automáticamente
window.onload = function() {
    iniciarScanner();
};
</script>

<?php include '../../includes/footer.php'; ?>