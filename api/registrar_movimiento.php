<?php
header('Content-Type: application/json');
require_once '../config/database.php';

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
    
    // Iniciar transacción (para que todo se guarde o nada)
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
        // Para BAJA no afectamos stock (ya se descontó al prestar)
        
        if (isset($sql_stock)) {
            $conn->query($sql_stock);
        }
        
        // Confirmar todo
        $conn->commit();
        
        echo json_encode(['success' => true, 'mensaje' => 'Movimiento registrado correctamente']);
        
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'mensaje' => 'Error: ' . $e->getMessage()]);
    }
    
} else {
    echo json_encode(['success' => false, 'mensaje' => 'Datos no válidos']);
}
?>