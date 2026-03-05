<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: /inventario_ti/login.php');
    exit();
}
require_once '../../config/database.php';
include '../../includes/header.php';

$mensaje = '';
$error = '';

// ============================================
// PROCESAR DEVOLUCIÓN SI SE ENVÍA EL FORMULARIO
// ============================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['equipo_id'])) {
    $equipo_id = intval($_POST['equipo_id']);
    $observacion = $conn->real_escape_string($_POST['observacion'] ?? '');
    $estado_equipo = $conn->real_escape_string($_POST['estado_equipo'] ?? '');
    $condiciones = $conn->real_escape_string($_POST['condiciones'] ?? '');
    
    // Validar que se seleccionó el estado
    if (empty($estado_equipo)) {
        $error = "❌ Debe seleccionar el estado del equipo";
    } else {
        // Verificar que el equipo esté prestado
        $sql_verificar = "SELECT a.*, p.nombres as persona_nombre, e.tipo_equipo, e.codigo_barras
                          FROM asignaciones a
                          JOIN personas p ON a.persona_id = p.id
                          JOIN equipos e ON a.equipo_id = e.id
                          WHERE a.equipo_id = $equipo_id AND a.fecha_devolucion IS NULL";
        $result = $conn->query($sql_verificar);
        
        if ($result && $result->num_rows > 0) {
            $asignacion = $result->fetch_assoc();
            
            // Procesar foto si se subió
            $foto_devolucion = '';
            if(isset($_FILES['foto_equipo']) && $_FILES['foto_equipo']['error'] == 0) {
                $allowed = ['jpg', 'jpeg', 'png', 'gif'];
                $filename = $_FILES['foto_equipo']['name'];
                $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                
                if(in_array($ext, $allowed)) {
                    // Crear carpeta si no existe
                    $carpeta_fotos = '../../uploads/devoluciones/';
                    if (!file_exists($carpeta_fotos)) {
                        mkdir($carpeta_fotos, 0777, true);
                    }
                    
                    $nuevo_nombre = 'devolucion_' . $equipo_id . '_' . date('YmdHis') . '.' . $ext;
                    $destino = $carpeta_fotos . $nuevo_nombre;
                    
                    if(move_uploaded_file($_FILES['foto_equipo']['tmp_name'], $destino)) {
                        $foto_devolucion = 'uploads/devoluciones/' . $nuevo_nombre;
                    }
                }
            }
            
            // Iniciar transacción
            $conn->begin_transaction();
            
            try {
                // 1. Actualizar la asignación con fecha de devolución
                $sql_update = "UPDATE asignaciones SET fecha_devolucion = NOW(), observaciones = '$observacion' 
                              WHERE id = " . $asignacion['id'];
                $conn->query($sql_update);
                
                // ============================================
                // 2. ACTUALIZAR ESTADO Y CREAR MANTENIMIENTO SI ES NECESARIO
                // ============================================
                if ($estado_equipo == 'BUENO') {
                    // Equipo en buen estado, disponible
                    $nuevo_estado = 'Disponible';
                    // No crear mantenimiento
                } else {
                    // Equipo dañado, pasa a mantenimiento
                    $nuevo_estado = 'En mantenimiento';
                    
                    // Crear descripción para el mantenimiento
                    $descripcion_manto = "Equipo ingresado a mantenimiento por devolución en estado: $estado_equipo";
                    if (!empty($condiciones)) {
                        $descripcion_manto .= " - Condiciones: $condiciones";
                    }
                    
                    // Insertar en mantenimientos
                    $sql_mantenimiento = "INSERT INTO mantenimientos 
                        (equipo_id, fecha_ingreso, tipo_mantenimiento, descripcion, observaciones, created_by) 
                        VALUES 
                        ($equipo_id, NOW(), 'correctivo', '$descripcion_manto', 'Generado automáticamente por devolución', {$_SESSION['user_id']})";
                    $conn->query($sql_mantenimiento);
                }
                
                $sql_equipo = "UPDATE equipos SET estado = '$nuevo_estado' WHERE id = $equipo_id";
                $conn->query($sql_equipo);
                
                // 3. Registrar en movimientos con TODOS los datos de trazabilidad
                $sql_movimiento = "INSERT INTO movimientos 
                                  (equipo_id, persona_id, tipo_movimiento, observaciones, estado_equipo, condiciones, foto_devolucion) 
                                  VALUES ($equipo_id, " . $asignacion['persona_id'] . ", 'DEVOLUCION', '$observacion', '$estado_equipo', '$condiciones', '$foto_devolucion')";
                $conn->query($sql_movimiento);
                
                // 4. Generar acta de devolución automáticamente
                $acta_url = "/inventario_ti/api/generar_acta_mpdf.php?tipo=devolucion&persona_id=" . $asignacion['persona_id'];
                
                $conn->commit();
                $mensaje = "✅ Devolución registrada correctamente";
                
                // Mensaje adicional si se creó mantenimiento
                $mensaje_adicional = ($estado_equipo != 'BUENO') ? ' Se ha creado un registro automático en Mantenimientos.' : '';
                
                echo "<script>
                    Swal.fire({
                        icon: 'success',
                        title: '¡Devolución exitosa!',
                        html: '<p>El equipo ha sido devuelto correctamente</p><p>Estado: <strong>$estado_equipo</strong></p><p><small>$mensaje_adicional</small></p>',
                        showCancelButton: true,
                        confirmButtonText: '📄 Ver Acta',
                        cancelButtonText: 'Ir al Historial'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            window.open('$acta_url', '_blank');
                            window.location.href = 'historial.php';
                        } else {
                            window.location.href = 'historial.php';
                        }
                    });
                </script>";
                
            } catch (Exception $e) {
                $conn->rollback();
                $error = "❌ Error al registrar devolución: " . $e->getMessage();
            }
        } else {
            $error = "❌ Este equipo no está prestado actualmente";
        }
    }
}

// ============================================
// OBTENER LISTA DE EQUIPOS PRESTADOS
// ============================================
$sql_prestados = "SELECT 
                    a.id as asignacion_id, 
                    a.fecha_asignacion, 
                    a.observaciones as obs_asignacion,
                    e.id as equipo_id, 
                    e.codigo_barras, 
                    e.tipo_equipo, 
                    e.marca, 
                    e.modelo,
                    e.numero_serie,
                    p.id as persona_id, 
                    p.nombres, 
                    p.cedula
                  FROM asignaciones a
                  INNER JOIN equipos e ON a.equipo_id = e.id
                  INNER JOIN personas p ON a.persona_id = p.id
                  WHERE a.fecha_devolucion IS NULL
                  ORDER BY a.fecha_asignacion DESC";

$result_prestados = $conn->query($sql_prestados);

// Si viene un equipo específico por GET, seleccionarlo automáticamente
$equipo_seleccionado = isset($_GET['equipo_id']) ? intval($_GET['equipo_id']) : 0;
?>

<!-- El resto del HTML y JavaScript se mantiene IGUAL -->
<!-- ... (todo el código de estilos, HTML y JavaScript que ya tenías) ... -->