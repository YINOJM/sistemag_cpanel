<?php
//cmn_generar_reporte_pdf.php
require_once __DIR__ . '/../modelo/conexion.php';

if (empty($_SESSION['id']) || $_SESSION['rol'] !== 'Super Administrador') {
    die("Acceso denegado.");
}

require_once __DIR__ . '/../vendor/autoload.php';

error_reporting(0);
ini_set('display_errors', 0);

use Dompdf\Dompdf;
use Dompdf\Options;

// 1. Capturar filtros de la URL
$anio = isset($_GET['anio']) ? (int)$_GET['anio'] : ANIO_CMN;
$search_query = isset($_GET['q']) ? $_GET['q'] : '';

// 2. Construir el Filtro Inteligente
$where = "WHERE anio_proceso = $anio AND archivo_pdf IS NOT NULL";
if (!empty($search_query)) {
    $q = trim($conexion->real_escape_string($search_query));
    $where .= " AND (apellidos LIKE '%$q%' 
                OR nombres LIKE '%$q%' 
                OR dni LIKE '%$q%' 
                OR cip LIKE '%$q%' 
                OR sub_unidad_especifica LIKE '%$q%'
                OR divpol_divopus LIKE '%$q%'
                OR region_policial LIKE '%$q%')";
}

// 3. Ejecutar la consulta
$query = "SELECT * FROM cmn_responsables $where ORDER BY region_policial ASC, divpol_divopus ASC, sub_unidad_especifica ASC";
$res = $conexion->query($query);


// Rutas de Imágenes
$pathEscudo = '../public/images/escudo.png';
$base64Escudo = '';
if (file_exists($pathEscudo)) {
    $type = pathinfo($pathEscudo, PATHINFO_EXTENSION);
    $data = file_get_contents($pathEscudo);
    $base64Escudo = 'data:image/' . $type . ';base64,' . base64_encode($data);
}

$pathRegpol = '../public/images/logo_regpol.png';
$base64Regpol = '';
if (file_exists($pathRegpol)) {
    $type = pathinfo($pathRegpol, PATHINFO_EXTENSION);
    $data = file_get_contents($pathRegpol);
    $base64Regpol = 'data:image/' . $type . ';base64,' . base64_encode($data);
}

$html = '
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte General de Responsables CMN</title>
    <style>
       @page { margin: 1cm 1cm 2.5cm 1cm; }
        body { font-family: "Helvetica", "Arial", sans-serif; font-size: 10px; color: #333; }
        .header-box { text-align: center; margin-bottom: 20px; border-bottom: 1px solid #000; padding-bottom: 15px; }
        .titulo { font-size: 16px; font-weight: bold; margin-bottom: 5px; color: #000; text-transform: uppercase; }
        .subtitulo { font-size: 11px; margin-bottom: 10px; color: #444; }
        .table-reporte { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .table-reporte th { background-color: #003666; color: #ffffff; padding: 6px 4px; border: 1px solid #000; font-size: 9px; text-transform: uppercase; }
        .table-reporte td { padding: 5px 4px; border: 1px solid #ccc; font-size: 8.5px; vertical-align: middle; }
        .table-reporte tr:nth-child(even) { background-color: #f9f9f9; }
        .watermark { position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); width: 450px; opacity: 0.05; z-index: -1000; }
        .footer { position: fixed; bottom: 0cm; left: 0cm; right: 0cm; height: 1cm; text-align: right; font-size: 8px; color: #777; }
        .text-center { text-align: center; }
        .fw-bold { font-weight: bold; }
    </style>
</head>
<body>
    <img src="' . $base64Escudo . '" class="watermark">
    <div class="header-box" style="position: relative; min-height: 40px; border-bottom: 2px solid #ccc;">
        <img src="' . $base64Escudo . '" style="position: absolute; left: 10px; top: -10px; width: 45px;">
        <img src="' . $base64Regpol . '" style="position: absolute; right: 10px; top: -10px; width: 45px;">
        <div class="titulo">REPORTE GENERAL DE RESPONSABLES LOGÍSTICOS REGISTRADOS</div>
        <div class="subtitulo" style="margin-bottom: 0;">PROGRAMACIÓN MULTIANUAL DE BIENES, SERVICIOS Y OBRAS ' . $anio . ' - CMN</div>
    </div>
    <table class="table-reporte">
        <thead>
            <tr>
                <th style="width: 30px;">#</th>
                <th style="width: 60px;">DNI / CIP</th>
                <th>APELLIDOS Y NOMBRES</th>
                <th>GRADO / CARGO</th>
                <th>REGIÓN / DIVPOL</th>
                <th>SUB UNIDAD</th>
                <th style="width: 70px;">CELULAR / CORREO</th>
                <th style="width: 50px;">REGISTRO</th>
            </tr>
        </thead>
        <tbody>';

$count = 1;
if ($res->num_rows > 0) {
    while ($row = $res->fetch_assoc()) {
        $html .= '
            <tr>
                <td class="text-center">' . $count++ . '</td>
                <td class="text-center">
                    <span class="fw-bold">' . $row['dni'] . '</span><br>
                    ' . $row['cip'] . '
                </td>
                <td>' . htmlspecialchars($row['apellidos'] . ' ' . $row['nombres']) . '</td>
                <td>
                    ' . htmlspecialchars($row['grado']) . '<br>
                    <span style="font-size: 7px; color: #666;">' . htmlspecialchars($row['cargo']) . '</span>
                </td>
                <td>
                    ' . htmlspecialchars($row['region_policial']) . '<br>
                    <span style="font-size: 7px; color: #666;">' . htmlspecialchars($row['divpol_divopus']) . '</span>
                </td>
                <td>' . htmlspecialchars($row['sub_unidad_especifica']) . '</td>
                <td>
                    ' . htmlspecialchars($row['celular']) . '<br>
                    <span style="font-size: 7px; color: #666;">' . htmlspecialchars($row['correo']) . '</span>
                </td>
                <td class="text-center">' . date('d/m/Y', strtotime($row['fecha_registro'])) . '</td>
            </tr>';
    }
} else {
    $html .= '<tr><td colspan="8" class="text-center">No se encontraron registros completados.</td></tr>';
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

$canvas = $dompdf->getCanvas();
$w = $canvas->get_width();
$h = $canvas->get_height();
$font = $dompdf->getFontMetrics()->get_font("helvetica", "normal");
$color = array(0.4, 0.4, 0.4);

$text1 = "OFICINA DE PROGRAMACIÓN - UNIDAD DE ADMINISTRACIÓN - UE009 - VII DIRTEPOL LIMA";
$text2 = "RUC 20383430250 • Av. España 450 - Cercado de Lima";
$text3 = "Fecha de impresión: " . date('d/m/Y H:i:s') . " | Página {PAGE_NUM} de {PAGE_COUNT}";

$tw1 = $dompdf->getFontMetrics()->getTextWidth($text1, $font, 7);
$tw2 = $dompdf->getFontMetrics()->getTextWidth($text2, $font, 7);
$tw3 = $dompdf->getFontMetrics()->getTextWidth("Fecha de impresión: 00/00/0000 00:00:00 | Página 0", $font, 7);

$canvas->page_text(($w - $tw1) / 2, $h - 35, $text1, $font, 7, $color);
$canvas->page_text(($w - $tw2) / 2, $h - 25, $text2, $font, 7, $color);
$canvas->page_text(($w - $tw3) / 2, $h - 15, $text3, $font, 7, $color);

$dompdf->stream("Reporte_Responsables_Logiticos_" . $anio . ".pdf", array("Attachment" => false));
