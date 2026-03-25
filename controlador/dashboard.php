<?php
//controlador/dashboard.php

// 1. CARGAR CONEXIÓN Y ENTORNO (Centralizado)
require_once __DIR__ . "/../modelo/conexion.php";

if (!isset($_SESSION["id"])) {
    header("Location: " . (defined('BASE_URL') ? BASE_URL : '/sistem_job/') . "vista/login/login.php");
    exit();
}

// session_start(); removido porque lo maneja conexion.php -> sesion_config.php

require_once "../modelo/dashboard_model.php";

$dashboard = new DashboardModel();
$anios = $dashboard->getAniosDisponibles();

// Aseguramos que el año actual siempre esté disponible en la lista
$anio_actual = date("Y");
if (!in_array($anio_actual, $anios)) {
    array_unshift($anios, $anio_actual);
}

// Por defecto mostramos el año actual, a menos que se seleccione otro
$anio = isset($_GET['anio']) && is_numeric($_GET['anio']) ? $_GET['anio'] : $anio_actual;

// 1. Sincronizar permisos (IMPORTANTE)
// require_once "../modelo/conexion.php"; // Ya incluido arriba
require_once "autocargar_permisos.php";
if (isset($conn) && $conn instanceof mysqli) {
    recargarPermisosUsuario($_SESSION['id'], $conn);
} elseif (isset($conexion) && $conexion instanceof mysqli) {
    recargarPermisosUsuario($_SESSION['id'], $conexion);
}

// 2. Verificar Permiso de Dashboard
$tieneAcceso = userCan('DASHBOARD');

$metrics = null;

if ($tieneAcceso) {
    /* ============================
       MÉTRICAS GENERALES
    ============================ */
    $metrics = [
        "total_procedimientos" => $dashboard->getTotalProcedimientos($anio),
        "total_cuantias" => $dashboard->getTotalPAC($anio),
        "total_criticos" => $dashboard->getTotalCriticos($anio),
        "total_estrategicos" => $dashboard->getTotalEstrategicos($anio),
        "total_rutinarios" => $dashboard->getTotalRutinarios($anio),
        "total_operacionales" => $dashboard->getTotalOperacionales($anio),

        // Dashboard extendido
        "por_mes" => $dashboard->getProcedimientosPorMes($anio),
        "por_resultado" => $dashboard->getResultadoSegmentacion($anio),
        "por_riesgo" => $dashboard->getRiesgoCategoria($anio),
        "por_cuantia" => $dashboard->getCuantiaCategoria($anio),
        "por_tipo" => $dashboard->getTipoProceso($anio),
        "top_5" => $dashboard->getTop5Costosos($anio)
    ];

    /* ============================
       FESTEJOS DE CUMPLEAÑOS
    ============================ */
    $cumpleanieros = [];
    $tieneAccesoRRHH = userCan('PERSONAL');
    
    if ($tieneAccesoRRHH) {
        require_once "../modelo/PersonalModelo.php";
        $personalModel = new PersonalModelo();
        // Buscamos cumpleaños en los próximos 10 días (Solicitud de reducción de rango)
        $cumpleanieros = $personalModel->obtenerCumpleanieros(10);
    }
}

include "../vista/dashboard.php";
