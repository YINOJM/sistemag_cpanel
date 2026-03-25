<?php

// modelo/conexion.php



// 1. DETECCIÓN DE ENTORNO Y SEGURIDAD

$http_host = $_SERVER['HTTP_HOST'] ?? 'localhost';

$is_local = ($http_host === 'localhost' || $http_host === '127.0.0.1' || strpos($http_host, '192.168.') !== false || str_ends_with($http_host, '.test'));



if ($is_local) {

    error_reporting(E_ALL);

    ini_set('display_errors', 1);

} else {

    // SEGURIDAD EN CPANEL: No mostrar errores al público

    error_reporting(0); 

    ini_set('display_errors', 0);

    ini_set('log_errors', 1);

    ini_set('error_log', dirname(__DIR__) . '/php_error.log');

}



// 2. CONFIGURACIÓN DE URL BASE DINÁMICA

if (!defined('BASE_URL')) {

    if ($is_local) {

        define('BASE_URL', '/sistemag_cpanel/'); // Ajuste manual para Laragon

    } else {

        define('BASE_URL', '/'); // Ajuste manual para cPanel (Raíz)

    }

}



// URL completa para recursos (Imágenes, Assets)

if (!defined('FULL_BASE_URL')) {

    $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? "https" : "http";

    define('FULL_BASE_URL', $protocol . "://" . $http_host . BASE_URL);

}



// 3. CONFIGURACIÓN DE SESIÓN (4 HORAS)

require_once __DIR__ . '/sesion_config.php';



// 4. CONFIGURACIÓN DE BASE DE DATOS

if ($is_local) {

    $db_host = "localhost";

    $db_user = "root";

    $db_pass = "";

    $db_name = "sistemag_bdsisintegrado";

} else {

    // CREDENCIALES ACTUALIZADAS SEGÚN TU NOTA

    $db_host = "localhost"; 

    $db_user = "sistemag_user_sig";      

    $db_pass = "AccesoSistema2026*";     

    $db_name = "sistemag_bdsisintegrado";

}



// 5. CONEXIÓN (Modo compatible y seguro)

try {

    mysqli_report(MYSQLI_REPORT_OFF); 

    $conexion = @new mysqli($db_host, $db_user, $db_pass, $db_name);



    if ($conexion->connect_error) {

        throw new Exception("Error de conexión: (" . $conexion->connect_errno . ") " . $conexion->connect_error);

    }

} catch (Exception $e) {

    if ($is_local) {

        die("Fallo en Local: " . $e->getMessage());

    } else {

        // En producción, mensaje limpio

        echo "<div style='font-family:sans-serif; padding:50px; text-align:center;'>";

        echo "<h2 style='color:#d9534f;'>Sistema en Mantenimiento</h2>";

        echo "<p>No pudimos conectar con la base de datos. Por favor, contacte con soporte técnico.</p>";

        echo "</div>";

        exit;

    }

}



// 6. CONFIGURACIONES FINALES

$conexion->set_charset("utf8mb4");

$conn = $conexion; // Compatibilidad con otros archivos

date_default_timezone_set("America/Lima");



// 7. CONSTANTES TRANSVERSALES

if (!defined('ANIO_FISCAL')) define('ANIO_FISCAL', date('Y')); 

if (!defined('ANIO_CMN'))    define('ANIO_CMN', date('Y')); 



?>

