<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    die(json_encode(['success' => false, 'message' => 'Sesión no iniciada']));
}

require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $acta_id = intval($_POST['acta_id'] ?? 0);
    $movimiento_id = intval($_POST['movimiento_id'] ?? 0);
    
    if ($acta_id <= 0 && $movimiento_id <= 0) {
        die(json_encode(['success' => false, 'message' => 'ID no válido']));
    }

    if (!isset($_FILES['archivo_firmado']) || $_FILES['archivo_firmado']['error'] != 0) {
        die(json_encode(['success' => false, 'message' => 'No se subió ningún archivo o hubo un error']));
    }

    $allowed = ['pdf'];
    $filename = $_FILES['archivo_firmado']['name'];
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

    if (!in_array($ext, $allowed)) {
        die(json_encode(['success' => false, 'message' => 'Solo se permiten archivos PDF']));
    }

    $carpeta_destino = '../uploads/actas_firmadas/';
    if (!file_exists($carpeta_destino)) {
        mkdir($carpeta_destino, 0777, true);
    }

    $identificador = $acta_id > 0 ? 'acta_' . $acta_id : 'mov_' . $movimiento_id;
    $nuevo_nombre = 'firmado_' . $identificador . '_' . time() . '.pdf';
    $ruta_final = 'uploads/actas_firmadas/' . $nuevo_nombre;

    if (move_uploaded_file($_FILES['archivo_firmado']['tmp_name'], $carpeta_destino . $nuevo_nombre)) {
        if ($acta_id > 0) {
            $conn->query("UPDATE actas SET archivo_firmado = '$ruta_final' WHERE id = $acta_id");
        }
        if ($movimiento_id > 0) {
            $conn->query("UPDATE movimientos SET acta_firmada = '$ruta_final' WHERE id = $movimiento_id");
        }
        
        echo json_encode(['success' => true, 'message' => 'Archivo subido correctamente', 'ruta' => $ruta_final]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al mover el archivo al servidor']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
}
?>