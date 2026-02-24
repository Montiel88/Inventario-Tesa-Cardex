<?php
/**
 * SISTEMA DE PERMISOS - Inventario TESA
 * Roles: 1 = Admin, 2 = Lector
 */

function verificarSesion() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php');
        exit();
    }
}

function esAdmin() {
    return isset($_SESSION['user_rol']) && $_SESSION['user_rol'] == 1;
}

function esLector() {
    return isset($_SESSION['user_rol']) && $_SESSION['user_rol'] == 2;
}

function requiereAdmin() {
    verificarSesion();
    if (!esAdmin()) {
        header('Location: modules/dashboard.php?error=permisos');
        exit();
    }
}
?>