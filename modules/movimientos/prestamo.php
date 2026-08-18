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
?>
<?php
require_once '../../config/database.php';
include '../../includes/header.php';
?>

<div class="container mt-4">
    <h2>Registrar Préstamo de Equipo</h2>
    
    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5>Paso 1: Escanear Producto</h5>
                </div>
                <div class="card-body">
                    <div id="lector" style="width: 100%;"></div>
                    <div class="form-group mt-3">
                        <label>Código de Barras:</label>
                        <input type="text" id="codigo_producto" class="form-control" placeholder="O ingresa manualmente">
                        <button id="buscar_producto" class="btn btn-primary mt-2">Buscar Producto</button>
                    </div>
                    
                    <div id="info_producto" class="mt-3" style="display: none;">
                        <h5>Producto Seleccionado:</h5>
                        <p><strong>Nombre:</strong> <span id="producto_nombre"></span></p>
                        <p><strong>Stock Disponible:</strong> <span id="producto_stock"></span></p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5>Paso 2: Identificar Persona</h5>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label>Cédula:</label>
                        <input type="text" id="cedula_persona" class="form-control">
                        <button id="buscar_persona" class="btn btn-primary mt-2">Buscar Persona</button>
                    </div>
                    
                    <div id="info_persona" class="mt-3" style="display: none;">
                        <h5>Persona Seleccionada:</h5>
                        <p><strong>Nombre:</strong> <span id="persona_nombre"></span></p>
                        <p><strong>Departamento:</strong> <span id="persona_departamento"></span></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row mt-4">
        <div class="col-12">
            <button id="registrar_prestamo" class="btn btn-success btn-lg btn-block" disabled>
                Registrar Préstamo
            </button>
        </div>
    </div>
</div>

<!-- Incluir la librería para escanear códigos -->
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>

<script>
let productoSeleccionado = null;
let personaSeleccionada = null;

// Configurar el escáner
const html5QrCode = new Html5Qrcode("lector");
const qrCodeSuccessCallback = (decodedText, decodedResult) => {
    document.getElementById('codigo_producto').value = decodedText;
    buscarProducto(decodedText);
    html5QrCode.stop();
};

const config = { fps: 10, qrbox: { width: 250, height: 250 } };

// Iniciar la cámara para escanear
html5QrCode.start({ facingMode: "environment" }, config, qrCodeSuccessCallback);

// Buscar producto
document.getElementById('buscar_producto').addEventListener('click', function() {
    let codigo = document.getElementById('codigo_producto').value;
    buscarProducto(codigo);
});

function buscarProducto(codigo) {
    fetch(`../../api/buscar_producto.php?codigo=${codigo}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                productoSeleccionado = data.producto;
                document.getElementById('info_producto').style.display = 'block';
                document.getElementById('producto_nombre').textContent = data.producto.nombre;
                document.getElementById('producto_stock').textContent = data.producto.stock_actual;
                
                if (personaSeleccionada) {
                    document.getElementById('registrar_prestamo').disabled = false;
                }
            } else {
                alert('Producto no encontrado');
            }
        });
}

// Buscar persona
document.getElementById('buscar_persona').addEventListener('click', function() {
    let cedula = document.getElementById('cedula_persona').value;
    
    fetch(`../../api/buscar_persona.php?cedula=${cedula}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                personaSeleccionada = data.persona;
                document.getElementById('info_persona').style.display = 'block';
                document.getElementById('persona_nombre').textContent = data.persona.nombre_completo;
                document.getElementById('persona_departamento').textContent = data.persona.departamento;
                
                if (productoSeleccionado) {
                    document.getElementById('registrar_prestamo').disabled = false;
                }
            } else {
                alert('Persona no encontrada. ¿Quieres registrarla?');
                // Aquí podrías redirigir a un formulario para agregar persona
            }
        });
});

// Registrar préstamo
document.getElementById('registrar_prestamo').addEventListener('click', async function() {
    if (!productoSeleccionado || !personaSeleccionada) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'warning',
                title: 'Faltan datos',
                text: 'Debes seleccionar un producto (equipo) y una persona antes de registrar el préstamo.',
                confirmButtonColor: '#5a2d8c'
            });
        } else {
            alert('Debes seleccionar producto y persona');
        }
        return;
    }

    const btn = document.getElementById('registrar_prestamo');

    // Paso 1: Confirmación amigable
    let confirmado = true;
    if (typeof Swal !== 'undefined') {
        const res = await Swal.fire({
            title: '¿Registrar préstamo?',
            html:
                '<div class="text-start">' +
                '<p><strong>Producto:</strong> ' + (productoSeleccionado.nombre || 'Sin nombre') + '</p>' +
                '<p><strong>Entregado a:</strong> ' + (personaSeleccionada.nombre_completo || personaSeleccionada.nombre || 'Sin nombre') + '</p>' +
                '<p class="small text-muted mb-0">Se registrará como movimiento de SALIDA en el historial.</p>' +
                '</div>',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#5a2d8c',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, registrar préstamo',
            cancelButtonText: 'Cancelar'
        });
        confirmado = !!res.isConfirmed;
        if (!confirmado) return;
    }

    // Paso 2: Loading global + aplicar loading en botón
    if (typeof window.UXLoading !== 'undefined' && window.UXLoading.btnAplicar) {
        window.UXLoading.btnAplicar(btn, 'Registrando préstamo...');
    } else {
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status"></span>Registrando préstamo...';
    }
    if (typeof window.UXLoading !== 'undefined' && window.UXLoading.mostrarGlobal) {
        try { await window.UXLoading.mostrarGlobal('Registrando préstamo...', 'Por favor espera mientras se guarda el movimiento y se actualiza el stock.'); } catch(e) {}
    }

    const datos = {
        producto_id: productoSeleccionado.id,
        persona_id: personaSeleccionada.id,
        tipo: 'SALIDA',
        cantidad: -1,
        observacion: 'Préstamo de equipo'
    };

    try {
        const response = await fetch('../../api/registrar_movimiento.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(datos)
        });
        const texto = await response.text();
        let data;
        try { data = JSON.parse(texto); } catch(e) {
            throw new Error('Respuesta inválida del servidor: ' + texto.substring(0, 200));
        }

        if (typeof window.UXLoading !== 'undefined' && window.UXLoading.cerrarGlobal) {
            try { window.UXLoading.cerrarGlobal(); } catch(e) {}
        }
        if (typeof window.UXLoading !== 'undefined' && window.UXLoading.btnRestaurar) {
            window.UXLoading.btnRestaurar(btn);
        } else {
            btn.disabled = false;
            btn.textContent = 'Registrar Préstamo';
        }

        if (data.success) {
            if (typeof Swal === 'undefined') {
                alert('Préstamo registrado con éxito');
                productoSeleccionado = null;
                personaSeleccionada = null;
                location.reload();
                return;
            }

            const productoNombre = productoSeleccionado.nombre || 'Producto';
            const personaNombre = (personaSeleccionada.nombre_completo || personaSeleccionada.nombre || 'Persona');

            productoSeleccionado = null;
            personaSeleccionada = null;

            const resSwal = await Swal.fire({
                icon: 'success',
                title: '¡Préstamo Registrado con Éxito! ✅',
                html:
                    '<div class="text-start">' +
                    '<div class="mb-2"><strong>Entregado a:</strong></div>' +
                    '<div class="alert alert-light py-2 px-3 mb-3 border">' + personaNombre + '</div>' +
                    '<div class="mb-1"><strong>Producto:</strong></div>' +
                    '<div class="alert alert-warning py-2 px-3 mb-3 border border-warning bg-opacity-20">' + productoNombre + '</div>' +
                    '<p class="small text-muted mb-0"><i class="fas fa-info-circle me-1"></i> El movimiento ya quedó registrado en Historial de Movimientos.</p>' +
                    '</div>',
                showCancelButton: true,
                showDenyButton: true,
                allowOutsideClick: false,
                allowEscapeKey: false,
                reverseButtons: true,
                confirmButtonColor: '#198754',
                denyButtonColor: '#f3b229',
                cancelButtonColor: '#6c757d',
                cancelButtonText: '<i class="fas fa-home me-1"></i> Volver al Inicio',
                denyButtonText: '<i class="fas fa-redo me-1"></i> Registrar Otro Préstamo',
                confirmButtonText: '<i class="fas fa-history me-1"></i> Ver Historial'
            });

            if (resSwal.isConfirmed) {
                window.location.href = 'historial.php';
                return;
            }
            if (resSwal.isDenied) {
                // Limpiar formularios visualmente y recargar para reiniciar
                location.reload();
                return;
            }
            window.location.href = '/inventario_ti/modules/dashboard.php';
            return;

        } else {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'error',
                    title: 'No se pudo registrar',
                    text: data.mensaje || 'Error desconocido al guardar el préstamo.',
                    confirmButtonColor: '#dc3545'
                });
            } else {
                alert('Error: ' + (data.mensaje || 'No se pudo guardar'));
            }
        }
    } catch(err) {
        if (typeof window.UXLoading !== 'undefined' && window.UXLoading.cerrarGlobal) {
            try { window.UXLoading.cerrarGlobal(); } catch(e) {}
        }
        if (typeof window.UXLoading !== 'undefined' && window.UXLoading.btnRestaurar) {
            window.UXLoading.btnRestaurar(btn);
        } else {
            btn.disabled = false;
            btn.textContent = 'Registrar Préstamo';
        }
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'error',
                title: 'Error inesperado',
                text: (err && err.message) ? err.message : 'Revisa tu conexión e intenta nuevamente.',
                confirmButtonColor: '#dc3545'
            });
        } else {
            alert('Error inesperado: ' + (err && err.message ? err.message : ''));
        }
    }
});
</script>

<?php include '../../includes/footer.php'; ?>