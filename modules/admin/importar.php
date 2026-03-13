<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_rol'] != 1) {
    header('Location: /inventario_ti/login.php');
    exit();
}

require_once '../../config/database.php';
include '../../includes/header.php';

$mensaje = '';
$error = '';
$resultados = [];

// Procesar importación
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['archivo'])) {
    $archivo = $_FILES['archivo'];
    $tipo_importacion = $_POST['tipo_importacion'] ?? 'equipos';
    
    if ($archivo['error'] !== UPLOAD_ERR_OK) {
        $error = 'Error al subir el archivo';
    } else {
        $ext = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
        
        if (!in_array($ext, ['csv', 'xlsx', 'xls'])) {
            $error = 'Solo se permiten archivos CSV o Excel';
        } else {
            require_once '../../vendor/autoload.php';
            
            if ($ext == 'csv') {
                $resultados = procesarCSV($conn, $archivo['tmp_name'], $tipo_importacion);
            } else {
                $resultados = procesarExcel($conn, $archivo['tmp_name'], $tipo_importacion, $ext);
            }
            
            if (empty($resultados['errores'])) {
                $mensaje = "✅ Importación completada: {$resultados['exitos']} registros importados correctamente";
            } else {
                $mensaje = "⚠️ Importación parcial: {$resultados['exitos']} exitosos, " . count($resultados['errores']) . " con errores";
            }
        }
    }
}

function procesarCSV($conn, $archivo, $tipo) {
    $exitos = 0;
    $errores = [];
    
    if (($handle = fopen($archivo, 'r')) !== false) {
        $headers = fgetcsv($handle);
        
        while (($data = fgetcsv($handle)) !== false) {
            $row = array_combine($headers, $data);
            
            try {
                if ($tipo == 'equipos') {
                    $result = importarEquipo($conn, $row);
                } elseif ($tipo == 'personas') {
                    $result = importarPersona($conn, $row);
                }
                
                if ($result['success']) {
                    $exitos++;
                } else {
                    $errores[] = "Fila " . ($exitos + count($errores) + 1) . ": " . $result['error'];
                }
            } catch (Exception $e) {
                $errores[] = "Fila " . ($exitos + count($errores) + 1) . ": " . $e->getMessage();
            }
        }
        fclose($handle);
    }
    
    return ['exitos' => $exitos, 'errores' => $errores];
}

function procesarExcel($conn, $archivo, $tipo, $ext) {
    $exitos = 0;
    $errores = [];
    
    try {
        if ($ext == 'xlsx') {
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($archivo);
        } else {
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($archivo);
        }
        
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray();
        
        $headers = array_map('trim', array_shift($rows));
        
        foreach ($rows as $index => $row) {
            if (empty(array_filter($row))) continue;
            
            $row = array_combine($headers, $row);
            
            try {
                if ($tipo == 'equipos') {
                    $result = importarEquipo($conn, $row);
                } elseif ($tipo == 'personas') {
                    $result = importarPersona($conn, $row);
                }
                
                if ($result['success']) {
                    $exitos++;
                } else {
                    $errores[] = "Fila " . ($index + 2) . ": " . $result['error'];
                }
            } catch (Exception $e) {
                $errores[] = "Fila " . ($index + 2) . ": " . $e->getMessage();
            }
        }
    } catch (Exception $e) {
        $errores[] = "Error al leer Excel: " . $e->getMessage();
    }
    
    return ['exitos' => $exitos, 'errores' => $errores];
}

function importarEquipo($conn, $data) {
    $tipo = $conn->real_escape_string($data['tipo'] ?? $data['tipo_equipo'] ?? '');
    $marca = $conn->real_escape_string($data['marca'] ?? '');
    $modelo = $conn->real_escape_string($data['modelo'] ?? '');
    $serie = $conn->real_escape_string($data['serie'] ?? $data['numero_serie'] ?? '');
    $estado = $conn->real_escape_string($data['estado'] ?? 'Disponible');
    
    if (empty($tipo)) {
        return ['success' => false, 'error' => 'Tipo de equipo requerido'];
    }
    
    // Generar código único
    $codigo = 'PRO-' . str_pad(mt_rand(1, 999999), 6, '0', STR_PAD_LEFT);
    
    $sql = "INSERT INTO equipos (codigo_barras, tipo_equipo, marca, modelo, numero_serie, estado, fecha_ingreso, created_at)
            VALUES ('$codigo', '$tipo', '$marca', '$modelo', '$serie', '$estado', NOW(), NOW())";
    
    if ($conn->query($sql)) {
        return ['success' => true, 'id' => $conn->insert_id];
    }
    
    return ['success' => false, 'error' => $conn->error];
}

function importarPersona($conn, $data) {
    $nombres = $conn->real_escape_string($data['nombres'] ?? $data['nombre'] ?? '');
    $apellidos = $conn->real_escape_string($data['apellidos'] ?? $data['apellido'] ?? '');
    $cedula = $conn->real_escape_string($data['cedula'] ?? '');
    $cargo = $conn->real_escape_string($data['cargo'] ?? '');
    $departamento = $conn->real_escape_string($data['departamento'] ?? '');
    $email = $conn->real_escape_string($data['email'] ?? '');
    $telefono = $conn->real_escape_string($data['telefono'] ?? '');
    
    if (empty($nombres) || empty($cedula)) {
        return ['success' => false, 'error' => 'Nombres y cédula requeridos'];
    }
    
    $sql = "INSERT INTO personas (nombres, apellido, cedula, cargo, departamento, email, telefono, created_at)
            VALUES ('$nombres', '$apellidos', '$cedula', '$cargo', '$departamento', '$email', '$telefono', NOW())";
    
    if ($conn->query($sql)) {
        return ['success' => true, 'id' => $conn->insert_id];
    }
    
    return ['success' => false, 'error' => $conn->error];
}
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h4 class="mb-0"><i class="fas fa-file-import me-2"></i>Importación Masiva de Datos</h4>
                </div>
                <div class="card-body">
                    <?php if ($mensaje): ?>
                        <div class="alert alert-<?php echo strpos($mensaje, '✅') !== false ? 'success' : 'warning'; ?> alert-dismissible fade show" role="alert">
                            <?php echo $mensaje; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?php echo $error; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" enctype="multipart/form-data">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Tipo de Importación</label>
                                    <select name="tipo_importacion" class="form-select" required>
                                        <option value="equipos">Equipos</option>
                                        <option value="personas">Personas</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Archivo (CSV o Excel)</label>
                                    <input type="file" name="archivo" class="form-control" accept=".csv,.xlsx,.xls" required>
                                </div>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-upload me-1"></i> Importar Datos
                        </button>
                    </form>
                    
                    <hr class="my-4">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header bg-info text-white">
                                    <h5 class="mb-0">Plantilla de Equipos</h5>
                                </div>
                                <div class="card-body">
                                    <p>Columnas requeridas:</p>
                                    <code>tipo, marca, modelo, serie, estado</code>
                                    <hr>
                                    <a href="/inventario_ti/templates/plantilla_equipos.csv" download class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-download me-1"></i> Descargar Plantilla CSV
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header bg-info text-white">
                                    <h5 class="mb-0">Plantilla de Personas</h5>
                                </div>
                                <div class="card-body">
                                    <p>Columnas requeridas:</p>
                                    <code>nombres, apellidos, cedula, cargo, departamento, email, telefono</code>
                                    <hr>
                                    <a href="/inventario_ti/templates/plantilla_personas.csv" download class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-download me-1"></i> Descargar Plantilla CSV
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <?php if (!empty($resultados['errores'])): ?>
                    <div class="alert alert-danger mt-4">
                        <h5>Errores en la importación:</h5>
                        <ul class="mb-0">
                            <?php foreach ($resultados['errores'] as $err): ?>
                                <li><?php echo htmlspecialchars($err); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
