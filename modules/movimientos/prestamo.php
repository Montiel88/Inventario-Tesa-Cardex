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
document.getElementById('registrar_prestamo').addEventListener('click', function() {
    if (!productoSeleccionado || !personaSeleccionada) {
        alert('Debes seleccionar producto y persona');
        return;
    }
    
    let datos = {
        producto_id: productoSeleccionado.id,
        persona_id: personaSeleccionada.id,
        tipo: 'SALIDA',
        cantidad: -1, // Restar del inventario
        observacion: 'Préstamo de equipo'
    };
    
    fetch('../../api/registrar_movimiento.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(datos)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Préstamo registrado con éxito');
            // Limpiar y reiniciar
            productoSeleccionado = null;
            personaSeleccionada = null;
            location.reload();
        } else {
            alert('Error: ' + data.mensaje);
        }
    });
});
</script>

<?php include '../../includes/footer.php'; ?>