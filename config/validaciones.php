<?php
/**
 * VALIDACIONES DEL SISTEMA
 */

/**
 * Valida cédula ecuatoriana (algoritmo módulo 10)
 * @param string $cedula Cédula de 10 dígitos
 * @return bool True si es válida, False si no
 */
function validarCedulaEcuador($cedula) {
    // Eliminar espacios y guiones
    $cedula = preg_replace('/[^0-9]/', '', $cedula);
    
    // Validar longitud
    if (strlen($cedula) != 10) {
        return false;
    }
    
    // Validar que sean solo números
    if (!ctype_digit($cedula)) {
        return false;
    }
    
    // Validar provincia (primeros 2 dígitos entre 01 y 24)
    $provincia = intval(substr($cedula, 0, 2));
    if ($provincia < 1 || $provincia > 24) {
        return false;
    }
    
    // Algoritmo del módulo 10
    $digitos = str_split($cedula);
    $suma = 0;
    
    // Coeficientes para cada posición (2,1,2,1,2,1,2,1,2)
    $coeficientes = [2, 1, 2, 1, 2, 1, 2, 1, 2];
    
    for ($i = 0; $i < 9; $i++) {
        $digito = $digitos[$i] * $coeficientes[$i];
        if ($digito > 9) {
            $digito -= 9;
        }
        $suma += $digito;
    }
    
    $digito_verificador = intval($digitos[9]);
    $resto = $suma % 10;
    
    if ($resto == 0) {
        $digito_calculado = 0;
    } else {
        $digito_calculado = 10 - $resto;
    }
    
    return $digito_calculado == $digito_verificador;
}

/**
 * Valida nombre (solo letras, espacios, acentos)
 */
function validarNombre($nombre) {
    $nombre = trim($nombre);
    if (strlen($nombre) < 3) {
        return false;
    }
    return preg_match('/^[a-zA-ZáéíóúñÁÉÍÓÚÑ\s\']+$/', $nombre);
}

/**
 * Valida teléfono (7-10 dígitos, opcional)
 */
function validarTelefono($telefono) {
    if (empty($telefono)) {
        return true;
    }
    $telefono = preg_replace('/[^0-9]/', '', $telefono);
    return preg_match('/^\d{7,10}$/', $telefono);
}

/**
 * Valida correo electrónico
 */
function validarEmail($email) {
    if (empty($email)) {
        return true;
    }
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}
?>