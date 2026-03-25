<?php
// controlador/seguridad.php
// IMPORTANTE: Este archivo debe incluirse DESPUÉS de modelo/conexion.php

// La sesión ya se inició en modelo/sesion_config.php (vía conexion.php)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verifica sesión activa
if (empty($_SESSION["id"])) {
    $redir = (defined('BASE_URL') ? BASE_URL : '/sistem_job/') . "vista/login/login.php";
    header("Location: $redir");
    exit();
}

// Control de inactividad dinámico (4 horas)
$inactive_limit = defined('INACTIVITY_LIMIT') ? INACTIVITY_LIMIT : 14400;

if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $inactive_limit)) {
    // Sesión expirada por inactividad
    $_SESSION = [];
    session_unset();
    session_destroy();
    
    // Limpiar cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
    }
    
    $timeout_redir = (defined('BASE_URL') ? BASE_URL : '/sistem_job/') . "vista/login/login.php?timeout=1";
    header("Location: $timeout_redir");
    exit();
}

// Actualizar tiempo de última actividad para que los 4h empiecen a contar desde el último clic
$_SESSION['last_activity'] = time();
?>