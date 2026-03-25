<?php
// controlador/logout.php
require_once __DIR__ . "/../modelo/conexion.php";

// Limpiar sesión (ya iniciada por conexion.php -> sesion_config.php)
$_SESSION = [];
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}
session_destroy();

// Evita cache del navegador
header("Cache-Control: no-cache, must-revalidate");
header("Expires: 0");

// Redirigir al login
header("Location: " . (defined('BASE_URL') ? BASE_URL : '/sistem_job/') . "vista/login/login.php");
exit();