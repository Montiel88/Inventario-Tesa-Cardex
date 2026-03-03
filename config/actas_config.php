<?php
// ============================================
// CONFIGURACIÓN DE ACTAS Y FORMULARIOS
// ============================================
// Este archivo LEE la configuración desde la tabla 'configuracion'
// Los cambios se hacen desde el panel de administración, no editando este archivo

function cargarConfiguracion() {
    global $conn;
    $config = [];
    
    // Verificar que la conexión existe
    if (!isset($conn) || !$conn) {
        // Si no hay conexión, devolver valores por defecto
        return [
            'formulario_entrega' => 'FOR-TH-04',
            'formulario_devolucion' => 'FOR-TH-05',
            'formulario_descargo' => 'FOR-TH-06',
            'version' => '01',
            'aprobador_nombre' => 'CYNTHIA VÁZQUEZ JARA',
            'aprobador_cargo' => 'CANCILLER',
            'departamento_entrega' => 'Tecnologías de la Información',
            'institucion_nombre' => 'TECNOLÓGICO SAN ANTONIO - TESA',
            'ciudad' => 'Quito',
            'logo_url' => '/inventario_ti/assets/img/logo-tesa.png'
        ];
    }
    
    $sql = "SELECT clave, valor FROM configuracion WHERE modificable = 1";
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $config[$row['clave']] = $row['valor'];
        }
    }
    
    // Asegurar que todas las claves necesarias existan
    $defaults = [
        'formulario_entrega' => 'FOR-TH-04',
        'formulario_devolucion' => 'FOR-TH-05',
        'formulario_descargo' => 'FOR-TH-06',
        'version' => '01',
        'aprobador_nombre' => 'CYNTHIA VÁZQUEZ JARA',
        'aprobador_cargo' => 'CANCILLER',
        'departamento_entrega' => 'Tecnologías de la Información',
        'institucion_nombre' => 'TECNOLÓGICO SAN ANTONIO - TESA',
        'ciudad' => 'Quito',
        'logo_url' => '/inventario_ti/assets/img/logo-tesa.png'
    ];
    
    foreach ($defaults as $key => $value) {
        if (!isset($config[$key])) {
            $config[$key] = $value;
        }
    }
    
    return $config;
}

function obtenerSecuencia() {
    global $conn;
    
    if (!isset($conn) || !$conn) {
        return 1;
    }
    
    $sql = "SELECT valor FROM configuracion WHERE clave = 'secuencia_actual'";
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return intval($row['valor'] ?? 1);
    }
    
    return 1;
}

function incrementarSecuencia() {
    global $conn;
    
    if (!isset($conn) || !$conn) {
        return false;
    }
    
    $sql = "UPDATE configuracion SET valor = valor + 1 WHERE clave = 'secuencia_actual'";
    $conn->query($sql);
    return $conn->affected_rows > 0;
}

function generarCodigoActa($tipo) {
    global $conn;
    
    // Cargar configuración
    $config = cargarConfiguracion();
    
    $prefijo = $config['formulario_' . $tipo] ?? 'FOR-TH-00';
    $version = $config['version'] ?? '01';
    $secuencia = obtenerSecuencia();
    $anio = date('Y');
    $mes = date('m');
    
    // Incrementar secuencia para el próximo uso
    incrementarSecuencia();
    
    return $prefijo . '-' . $version . '-' . $anio . $mes . '-' . str_pad($secuencia, 4, '0', STR_PAD_LEFT);
}

// ============================================
// RESPALDO: Variables globales para compatibilidad
// ============================================
// Estas variables mantienen compatibilidad con código antiguo
// Pero ya no se usan directamente, ahora se lee desde BD

$config_actas = cargarConfiguracion();
$secuencia_acta = obtenerSecuencia();
?>