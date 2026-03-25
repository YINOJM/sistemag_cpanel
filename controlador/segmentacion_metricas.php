<?php
// controlador/segmentacion_metrica.php
declare(strict_types=1);

require_once __DIR__.'/../modelo/conexion.php';
require_once __DIR__.'/../modelo/segmentacion_util.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

/* Normalizar conexión como en otros archivos */
if (isset($conn) && $conn instanceof mysqli) {
    // ok
} elseif (isset($conexion) && $conexion instanceof mysqli) {
    $conn = $conexion;
} elseif (isset($mysqli) && $mysqli instanceof mysqli) {
    $conn = $mysqli;
} else {
    http_response_code(500);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode([
        'ok'  => false,
        'msg' => 'Error de conexión a base de datos',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
$conn->set_charset('utf8mb4');

// Respuestas JSON y sin caché
header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

try {

    // ======= INPUTS =======
    $anio = isset($_GET['anio']) ? (int)$_GET['anio'] : (int)date('Y');
    if ($anio <= 0) {
        $anio = (int)date('Y');
    }

    $programado   = (isset($_GET['programado']) && $_GET['programado'] !== '')
        ? (int)$_GET['programado']
        : null;

    $texto       = trim($_GET['q']          ?? '');
    $objeto      = trim($_GET['objeto']     ?? '');
    $tipoProceso = isset($_GET['tipo_proceso']) ? (int)$_GET['tipo_proceso'] : 0;
    $resultado   = trim($_GET['resultado']  ?? '');

    // ======= WHERE (para métricas filtradas) =======
    $where = ["s.anio = {$anio}"];

    if ($programado !== null) {
        $where[] = "s.programado = {$programado}";
    }

    if ($texto !== '') {
        $q = $conn->real_escape_string($texto);
        $where[] = "(s.descripcion LIKE '%{$q}%' OR s.ref_pac LIKE '%{$q}%')";

    }

    if ($objeto !== '') {
        $objEsc = $conn->real_escape_string($objeto);
        $where[] = "s.objeto_contratacion = '{$objEsc}'";
    }

    if ($tipoProceso > 0) {
        $where[] = "s.tipo_proceso_id = {$tipoProceso}";
    }

    if ($resultado !== '') {
        $resEsc = $conn->real_escape_string($resultado);
        $where[] = "s.resultado_segmentacion = '{$resEsc}'";
    }

    $whereSql = 'WHERE '.implode(' AND ', $where);

    // ======= (1) Métricas filtradas =======
    $sqlAgg = "
        SELECT
          COUNT(*) AS cantidad,
          ROUND(COALESCE(SUM(cuantia),0), 2) AS total_cuantia
        FROM segmentacion s
        {$whereSql}
    ";
    $resAgg = $conn->query($sqlAgg);
    $agg    = $resAgg->fetch_assoc();
    $resAgg->close();

    $cantidad     = (int)($agg['cantidad'] ?? 0);
    $totalCuantia = (float)($agg['total_cuantia'] ?? 0.0);

    // ======= (2) PAC REAL DEL AÑO (sin filtros) =======
    // Se utiliza siempre el PAC calculado desde segmentacion (no config_pac)
    $totalPacCalc = getTotalPac($conn, $anio);
    $pac10Calc    = round($totalPacCalc * 0.10, 2);

    // ======= RESPUESTA JSON =======
    echo json_encode([
        'ok'   => true,
        'data' => [
            'cantidad'        => $cantidad,
            'total_cuantia'   => $totalCuantia,
            'total_pac_calc'  => $totalPacCalc,
            'pac_10_calc'     => $pac10Calc,
        ]
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {

    http_response_code(500);
    echo json_encode([
        'ok'  => false,
        'msg' => 'Error al calcular métricas',
        'err' => $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE);
}
