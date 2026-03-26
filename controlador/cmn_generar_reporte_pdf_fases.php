<?php
// controlador/cmn_generar_reporte_pdf_fases.php
require_once __DIR__ . '/../modelo/conexion.php';
require_once __DIR__ . '/../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['id']) || !userCan('cmn')) die("Acceso denegado.");

$fase = isset($_GET['fase']) ? (int)$_GET['fase'] : 1;
$anio = isset($_GET['anio']) ? (int)$_GET['anio'] : date('Y');

$tabla_anexo = "cmn_anexos_fase" . $fase;
$fase_tit = "(i) FASE DE IDENTIFICACIÓN";
if ($fase === 2) $fase_tit = "(ii) FASE DE CLASIFICACIÓN Y PRIORIZACIÓN";
if ($fase === 3) $fase_tit = "(iii) FASE DE CONSOLIDACIÓN Y APROBACIÓN";

$sql = "SELECT r.*, a.fecha_subida, a.monto_total, a.estado_revision 
        FROM cmn_responsables r 
        LEFT JOIN $tabla_anexo a ON r.dni = a.dni_responsable 
        WHERE r.anio_proceso = $anio AND r.archivo_pdf IS NOT NULL
        ORDER BY r.region_policial ASC, r.divpol_divopus ASC";
$res = $conexion->query($sql);

$html = '
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte Fase ' . $fase . '</title>
    <style>
        body { font-family: "Helvetica", sans-serif; font-size: 10px; color: #333; }
        .titulo { text-align: center; font-size: 14px; font-weight: bold; text-transform: uppercase; margin-bottom: 5px; }
        .subtitulo { text-align: center; font-size: 11px; margin-bottom: 20px; color: #555; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th { background-color: #0d2a4a; color: white; padding: 6px; border: 1px solid #444; font-size: 9px; }
        td { padding: 5px; border: 1px solid #ccc; font-size: 8.5px; }
        .text-center { text-align: center; }
        .footer { position: fixed; bottom: 0; width: 100%; text-align: right; font-size: 8px; color: #888; }
    </style>
</head>
<body>
    <div class="titulo">REPORTE DE CUMPLIMIENTO CMN ' . $anio . '</div>
    <div class="subtitulo">' . $fase_tit . '</div>
    <table>
        <thead>
            <tr>
                <th style="width: 25px;">#</th>
                <th style="width: 50px;">DNI</th>
                <th>APELLIDOS Y NOMBRES</th>
                <th>REGIÓN / DIVOPUS</th>
                <th>SUB UNIDAD</th>
                <th style="width: 50px;">ESTADO</th>
                <th style="width: 60px;">MONTO S/</th>
                <th style="width: 70px;">FECHA</th>
            </tr>
        </thead>
        <tbody>';

$c = 1;
while ($r = $res->fetch_assoc()) {
    $estTxt = "PENDIENTE";
    if ($r['estado_revision'] !== null) {
        if($r['estado_revision'] == 0) $estTxt = "RECIBIDO";
        if($r['estado_revision'] == 1) $estTxt = "VALIDADO";
        if($r['estado_revision'] == 2) $estTxt = "OBSERVADO";
    }
    $html .= '
            <tr>
                <td class="text-center">' . $c++ . '</td>
                <td class="text-center">' . $r['dni'] . '</td>
                <td>' . htmlspecialchars($r['apellidos'] . ' ' . $r['nombres']) . '</td>
                <td>' . htmlspecialchars($r['region_policial'] . ' / ' . $r['divpol_divopus']) . '</td>
                <td>' . htmlspecialchars($r['sub_unidad_especifica']) . '</td>
                <td class="text-center">' . $estTxt . '</td>
                <td class="text-center">' . number_format($r['monto_total'] ?? 0, 2) . '</td>
                <td class="text-center">' . ($r['fecha_subida'] ? date('d/m/Y', strtotime($r['fecha_subida'])) : '-') . '</td>
            </tr>';
}

$html .= '
        </tbody>
    </table>
</body>
</html>';

$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'landscape');
$dompdf->render();
$dompdf->stream("Reporte_CMN_Fase{$fase}.pdf", ["Attachment" => false]);
exit;
