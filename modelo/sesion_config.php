<?php
// modelo/sesion_config.php

/**
 * Configuración de Sesión para Producción (cPanel) y Local
 * Objetivo: Mantener la sesión activa por 4 horas y evitar cierres inesperados.
 */

$duracion_sesion = 14400; // 4 horas en segundos

// 1. Verificar si ya se enviaron cabeceras para evitar errores fatales
if (headers_sent($file, $line)) {
    if (ini_get('display_errors')) {
        error_log("Sesión: No se pudo configurar en sesion_config.php porque la salida ya empezó en $file:$line");
    }
} else {
    // 2. Configurar tiempos y parámetros solo si la sesión NO ha empezado
    if (session_status() === PHP_SESSION_NONE) {
        
        $http_host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $is_remote = !in_array($http_host, ['localhost', '127.0.0.1', '::1']) && strpos($http_host, '192.168.') === false;

        if ($is_remote) {
            $ruta_sesiones = dirname(__DIR__) . '/sesiones_temp';
            if (!is_dir($ruta_sesiones)) {
                @mkdir($ruta_sesiones, 0777, true);
            }
            if (is_dir($ruta_sesiones) && is_writable($ruta_sesiones)) {
                ini_set('session.save_path', $ruta_sesiones);
            }
        }

        ini_set('session.gc_maxlifetime', (string)$duracion_sesion);
        ini_set('session.cookie_lifetime', (string)$duracion_sesion);
        ini_set('session.gc_probability', '1');
        ini_set('session.gc_divisor', '100');

        $seguro = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on');
        session_set_cookie_params([
            'lifetime' => $duracion_sesion,
            'path' => '/',
            'secure' => $seguro,
            'httponly' => true,
            'samesite' => 'Lax'
        ]);

        session_start();
        
        if (!isset($_SESSION['last_activity'])) {
            $_SESSION['last_activity'] = time();
        }
    }
}

if (!defined('INACTIVITY_LIMIT')) {
    define('INACTIVITY_LIMIT', $duracion_sesion);
}

/**
 * Función Global de Verificación de Permisos
 */
if (!function_exists('userCan')) {
    function userCan($modulo, $accion = 'VER') {
        if (session_status() !== PHP_SESSION_ACTIVE) return false;
        
        // CORRECCIÓN: Caso insensible a mayúsculas para SUPER ADMINISTRADOR
        if (isset($_SESSION['rol']) && strtoupper(trim($_SESSION['rol'])) === 'SUPER ADMINISTRADOR') return true;
        
        if (!isset($_SESSION['permisos']) || !is_array($_SESSION['permisos'])) return false;
        
        $mod = strtoupper(trim($modulo));
        $acc = strtoupper(trim($accion));
        
        return isset($_SESSION['permisos'][$mod][$acc]) && $_SESSION['permisos'][$mod][$acc] === true;
    }
}
