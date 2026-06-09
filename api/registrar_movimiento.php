<?php
session_start(); // Asegurar sesión para obtener usuario_id
header('Content-Type: application/json');
require_once '../config/database.php';
require_once '../config/notificaciones_helper.php'; // Incluir helper

// Leer los datos enviados desde JavaScript
$datos = json_decode(file_get_contents('php://input'), true);

if ($datos) {
    $producto_id = intval($datos['producto_id']);
    $persona_id = isset($datos['persona_id']) ? intval($datos['persona_id']) : null;
    $tipo = $conn->real_escape_string($datos['tipo']);
    $cantidad = intval($datos['cantidad']);
    $observacion = $conn->real_escape_string($datos['observacion'] ?? '');
    
    // Validar que el producto exista
    $check_producto = $conn->query("SELECT * FROM productos WHERE id = $producto_id");
    if ($check_producto->num_rows == 0) {
        echo json_encode(['success' => false, 'mensaje' => 'El producto no existe']);
        exit;
    }
    
    // Si es préstamo, validar que haya stock
    if ($tipo == 'SALIDA') {
        $producto = $check_producto->fetch_assoc();
        if ($producto['stock_actual'] <= 0) {
            echo json_encode(['success' => false, 'mensaje' => 'No hay stock disponible']);
            exit;
        }
    }
    
    // Iniciar transacción
    $conn->begin_transaction();
    
    try {
        // 1. Insertar el movimiento en el Cardex
        $sql_movimiento = "INSERT INTO movimientos (producto_id, persona_id, tipo_movimiento, cantidad, observacion) 
                          VALUES ($producto_id, " . ($persona_id ?: 'NULL') . ", '$tipo', $cantidad, '$observacion')";
        $conn->query($sql_movimiento);
        
        // 2. Actualizar el stock del producto
        if ($tipo == 'SALIDA') {
            $sql_stock = "UPDATE productos SET stock_actual = stock_actual - 1 WHERE id = $producto_id";
        } else if ($tipo == 'DEVOLUCION' || $tipo == 'ENTRADA') {
            $sql_stock = "UPDATE productos SET stock_actual = stock_actual + 1 WHERE id = $producto_id";
        }
        
        if (isset($sql_stock)) {
            $conn->query($sql_stock);
        }
        
        // Confirmar todo
        $conn->commit();
        
        // ==== REGISTRAR NOTIFICACIÓN DE ÉXITO ====
        // Obtener nombre del producto
        $producto_nombre = $producto['nombre'] ?? 'producto';
        // Obtener nombre de la persona (si se registró)
        $persona_nombre = '';
        if ($persona_id) {
            $stmt = $conn->prepare("SELECT nombres FROM personas WHERE id = ?");
            $stmt->bind_param('i', $persona_id);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($res->num_rows) {
                $persona_nombre = $res->fetch_assoc()['nombres'];
            }
        }
        
        // Determinar título y mensaje según el tipo
        if ($tipo == 'SALIDA') {
            $titulo = '📦 Préstamo registrado';
            $mensaje = "Se realizó préstamo de {$producto_nombre} a " . ($persona_nombre ?: 'sin persona registrada');
        } else {
            $titulo = '🔄 Movimiento registrado';
            $mensaje = "Se registró {$tipo} de {$producto_nombre}";
        }
        
        // URL opcional: podemos llevar al detalle del producto (si tenemos un archivo detalle)
        $url = "/inventario_ti/modules/productos/detalle.php?id={$producto_id}";
        
        registrar_notificacion(
            $_SESSION['user_id'],
            'success',
            $titulo,
            $mensaje,
            $url
        );
        
        // Registrar log de la operación
        require_once __DIR__ . '/../includes/logs_functions.php';
        registrarLog($conn, $tipo . ' producto', "Producto: {$producto_nombre}, Cantidad: {$cantidad}", $_SESSION['user_id']);
        
        echo json_encode(['success' => true, 'mensaje' => 'Movimiento registrado correctamente']);
        
    } catch (Exception $e) {
        $conn->rollback();
        
        // ==== REGISTRAR NOTIFICACIÓN DE ERROR ====
        registrar_notificacion(
            $_SESSION['user_id'],
            'error',
            '❌ Error en préstamo',
            'No se pudo completar el préstamo: ' . $e->getMessage(),
            null
        );
        
        echo json_encode(['success' => false, 'mensaje' => 'Error: ' . $e->getMessage()]);
    }
    
} else {
    echo json_encode(['success' => false, 'mensaje' => 'Datos no válidos']);
}
?>