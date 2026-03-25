<?php
// controlador/ren_generar_reporte_listado_pdf.php
require_once __DIR__ . '/../modelo/conexion.php';
require_once __DIR__ . '/../vendor/autoload.php';

error_reporting(0);
ini_set('display_errors', 0);

use Dompdf\Dompdf;
use Dompdf\Options;

// 1. Capturar filtros de la URL (Mismos que usa DataTables)
$search = $_GET['q'] ?? '';
$estado = $_GET['estado'] ?? '';
$grupo  = $_GET['grupo'] ?? '';
$anio   = isset($_GET['anio']) ? (int)$_GET['anio'] : ANIO_FISCAL;

// 2. Construir el Filtro (Misma lógica que RendicionesControlador - listar)
$where = "WHERE anio_fiscal = $anio";
if (!empty($search)) {
    $s = $conexion->real_escape_string($search);
    $where .= " AND (dni LIKE '%$s%' OR cip LIKE '%$s%' OR apellidos_nombres LIKE '%$s%' OR lugar_comision LIKE '%$s%')";
}
if (!empty($estado)) {
    $e = $conexion->real_escape_string($estado);
    $where .= " AND estado_rendicion = '$e'";
}
if (!empty($grupo)) {
    $g = $conexion->real_escape_string($grupo);
    if ($g === 'LOTE INICIAL / OTROS') {
        $where .= " AND (grupo_importacion IS NULL OR grupo_importacion = '')";
    } else {
        $where .= " AND grupo_importacion LIKE '%$g%'";
    }
}

// 3. Ejecutar la consulta
$query = "SELECT * FROM ren_rendiciones $where ORDER BY fecha_registro DESC";
$res = $conexion->query($query);

// Rutas de Imágenes
$pathEscudo = __DIR__ . '/../public/images/escudo.png';
$base64Escudo = '';
if (file_exists($pathEscudo)) {
    $dataEscudo = file_get_contents($pathEscudo);
    $base64Escudo = 'data:image/' . pathinfo($pathEscudo, PATHINFO_EXTENSION) . ';base64,' . base64_encode($dataEscudo);
}

$pathRegpol = __DIR__ . '/../public/images/logo_regpol.png';
$base64Regpol = '';
if (file_exists($pathRegpol)) {
    $dataRegpol = file_get_contents($pathRegpol);
    $base64Regpol = 'data:image/' . pathinfo($pathRegpol, PATHINFO_EXTENSION) . ';base64,' . base64_encode($dataRegpol);
}

// 4. Funciones auxiliares
function formatReportDate($date) {
    if (!$date || $date == '0000-00-00') return '-';
    return date('d/m/Y', strtotime($date));
}

$html = '
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Rendiciones de Viáticos</title>
    <style>
        @page { margin: 1cm 1cm 2.5cm 1cm; }
        body { font-family: "Helvetica", "Arial", sans-serif; font-size: 9px; color: #333; }
        .header-box { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #003666; padding-bottom: 10px; position: relative; }
        .logo-l { position: absolute; left: 0; top: 0; width: 45px; }
        .logo-r { position: absolute; right: 0; top: 0; width: 50px; }
        .titulo { font-size: 14px; font-weight: bold; color: #000; text-transform: uppercase; margin-top: 5px; }
        .subtitulo { font-size: 10px; color: #555; margin-top: 3px; }
        
        .filters-info { font-style: italic; color: #666; margin-bottom: 10px; font-size: 8px; }
        
        .table-reporte { width: 100%; border-collapse: collapse; }
        .table-reporte th { background-color: #003666; color: #ffffff; padding: 5px; border: 1px solid #000; text-transform: uppercase; font-size: 8px; }
        .table-reporte td { padding: 4px; border: 1px solid #ccc; font-size: 8px; vertical-align: middle; }
        .table-reporte tr:nth-child(even) { background-color: #f2f2f2; }
        
        .badge-pend { color: #d68910; font-weight: bold; }
        .badge-rend { color: #1e8449; font-weight: bold; }
        .badge-obs { color: #c0392b; font-weight: bold; }
        
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .fw-bold { font-weight: bold; }
    </style>
</head>
<body>
    <div class="header-box">
        <img src="' . $base64Escudo . '" class="logo-l">
        <img src="' . $base64Regpol . '" class="logo-r">
        <div class="titulo">REPORTE DE RENDICIONES DE CUENTAS POR COMISIÓN DE SERVICIOS - AÑO ' . $anio . '</div>
        <div class="subtitulo">REGIÓN POLICIAL LIMA - UNIDAD DE ADMINISTRACIÓN (UE 009)</div>
    </div>

    <div class="filters-info">
        Filtros aplicados: ' . ($search ? "Búsqueda: $search | " : "") . ($estado ? "Estado: $estado | " : "") . ($grupo ? "Lote: $grupo" : "Todos los Lotes") . '
        <br>Generado el: ' . date('d/m/Y H:i:s') . '
    </div>

    <table class="table-reporte">
        <thead>
            <tr>
                <th style="width: 20px;">N°</th>
                <th style="width: 45px;">DNI</th>
                <th style="width: 45px;">CIP</th>
                <th style="width: 120px;">PERSONAL POLICIAL</th>
                <th style="width: 55px;">LUGAR</th>
                <th style="width: 80px;">REGIÓN</th>
                <th>DIVOPUS / SUBUNIDAD</th>
                <th style="width: 65px;">COMISIÓN</th>
                <th style="width: 45px;">TOTAL S/.</th>
                <th style="width: 50px;">HT/REF</th>
                <th style="width: 50px;">ESTADO</th>
            </tr>
        </thead>
        <tbody>';

$count = 1;
$total_suma = 0;
if ($res && $res->num_rows > 0) {
    while ($row = $res->fetch_assoc()) {
        $total_suma += $row['total_depositado'];
        $personal = trim(($row['grado'] ? $row['grado'] . " " : "") . $row['apellidos_nombres']);
        // Limpieza de PNP ya aplicada en base de datos si se corrigió, pero aplicamos por seguridad
        $personal = trim(str_ireplace(["PNP.", "PNP"], "", $personal));
        $personal = str_replace(["..", ". ."], ".", $personal);

        $clase_estado = "";
        if ($row['estado_rendicion'] == 'Pendiente') $clase_estado = 'badge-pend';
        elseif ($row['estado_rendicion'] == 'Rendido') $clase_estado = 'badge-rend';
        else $clase_estado = 'badge-obs';

        $html .= '
            <tr>
                <td class="text-center">' . $count++ . '</td>
                <td class="text-center">' . ($row['dni'] ?: '-') . '</td>
                <td class="text-center">' . ($row['cip'] ?: '-') . '</td>
                <td>' . mb_strtoupper($personal) . '</td>
                <td>' . htmlspecialchars($row['lugar_comision']) . '</td>
                <td><small>' . ($row['region_cache'] ?: '-') . '</small></td>
                <td>' . htmlspecialchars($row['unidad']) . '<br><small style="color: #666;">' . ($row['division_cache'] ?: '-') . '</small></td>
                <td class="text-center">' . formatReportDate($row['fecha_inicio']) . '<br>' . formatReportDate($row['fecha_retorno']) . '</td>
                <td class="text-right fw-bold">' . number_format($row['total_depositado'], 2) . '</td>
                <td class="text-center">' . ($row['ht_ref'] ?: '-') . '</td>
                <td class="text-center ' . $clase_estado . '">' . mb_strtoupper($row['estado_rendicion']) . '</td>
            </tr>';
    }
    $html .= '
            <tr>
                <td colspan="8" class="text-right fw-bold" style="background: #eee;">TOTAL GENERAL:</td>
                <td class="text-right fw-bold" style="background: #eee;">S/. ' . number_format($total_suma, 2) . '</td>
                <td colspan="2" style="background: #eee;"></td>
            </tr>';
} else {
    $html .= '<tr><td colspan="11" class="text-center">No se encontraron registros con los filtros seleccionados.</td></tr>';
}

$html .= '
        </tbody>
    </table>
</body>
</html>';

$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'landscape');
$dompdf->render();

// Pie de página fijo en cada hoja usando canvas
$canvas = $dompdf->getCanvas();
$w = $canvas->get_width();
$h = $canvas->get_height();
$font = $dompdf->getFontMetrics()->get_font("helvetica", "normal");
$color = array(0.4, 0.4, 0.4);

$text1 = "ÁREA DE CONTABILIDAD - UNIDAD DE ADMINISTRACIÓN - UE009 - VII DIRTEPOL LIMA";
$text2 = "RUC 20383430250 • Av. España 450 - Cercado de Lima";
$text3 = "Fecha de impresión: " . date('d/m/Y H:i:s') . " | Página {PAGE_NUM} de {PAGE_COUNT}";

$tw1 = $dompdf->getFontMetrics()->getTextWidth($text1, $font, 7);
$tw2 = $dompdf->getFontMetrics()->getTextWidth($text2, $font, 7);
$tw3 = $dompdf->getFontMetrics()->getTextWidth("Fecha de impresión: 00/00/0000 00:00:00 | Página 0 de 0", $font, 7);

$canvas->page_text(($w - $tw1) / 2, $h - 35, $text1, $font, 7, $color);
$canvas->page_text(($w - $tw2) / 2, $h - 25, $text2, $font, 7, $color);
$canvas->page_text(($w - $tw3) / 2, $h - 15, $text3, $font, 7, $color);

$dompdf->stream("Reporte_Rendiciones_" . date('dmY_His') . ".pdf", array("Attachment" => false));
