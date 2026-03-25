<?php
// reporte_seguimiento_pac_pdf.php
declare(strict_types=1);

ini_set('display_errors', '0');
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE & ~E_WARNING);

use Dompdf\Dompdf;
use Dompdf\Options;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../modelo/conexion.php';

if (empty($_SESSION['id'])) {
    die("No autorizado.");
}

$conn = $conexion;
$conn->set_charset('utf8mb4');

/* ====== Filtros ====== */
$anio = isset($_GET['anio']) ? (int)$_GET['anio'] : (int)date('Y');

/* ====== Consulta principal ====== */
$sql = "
SELECT 
    s.id as segmentacion_id, 
    s.ref_pac,
    s.objeto_contratacion as t_contratacion, 
    s.descripcion as objeto_contrato,
    s.origen_registro,
    tp.nombre as t_procedimiento,
    s.cuantia as v_estimado,
    s.cmn as cmn,
    sp.mes_programado,
    sp.estado_proceso,
    sp.valor_convocado,
    sp.monto_adjudicado as valor_adjudicado,
    sp.imp_comprometido,
    sp.imp_devengado,
    sp.imp_girado,
    sp.certificado,
    sp.observaciones
FROM segmentacion s
LEFT JOIN tipo_proceso tp ON tp.id = s.tipo_proceso_id
LEFT JOIN seguimiento_pac sp ON sp.segmentacion_id = s.id
WHERE s.anio = $anio AND (sp.oculto = 0 OR sp.oculto IS NULL)
ORDER BY s.id DESC";

$rs = $conn->query($sql);

/* ====== Utilitarios ====== */
function locateImage(string $baseNoExt): ?string {
    $dir = realpath(__DIR__ . '/../public/images');
    if (!$dir) return null;
    foreach (['.png','.jpg','.jpeg','.webp'] as $ext) {
        $p = $dir . '/' . $baseNoExt . $ext;
        if (is_file($p)) return $p;
    }
    return null;
}
function embedImg(?string $absPath): ?string {
    if (!$absPath || !is_file($absPath)) return null;
    $ext  = strtolower(pathinfo($absPath, PATHINFO_EXTENSION));
    $mime = ($ext==='jpg' ? 'jpeg' : $ext);
    return 'data:image/'.$mime.';base64,'.base64_encode(file_get_contents($absPath));
}
function money(float $n): string {
    return number_format($n, 2, '.', ',');
}

/* Logos */
$logoEscudo = embedImg(locateImage('escudo'));
$logoRegpol = embedImg(locateImage('logo_regpol'));

$tituloReporte = "REPORTE DE SEGUIMIENTO PAC ($anio) - LEY 32069";

/* ====== HTML/CSS ====== */
ob_start(); ?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<style>
    @page { margin: 12mm 10mm; }
    body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 8px; color: #111; line-height: 1.2; }
    
    .header-table { width: 100%; border-bottom: 2px solid #0056b3; margin-bottom: 15px; }
    .logo-box { width: 70px; }
    .logo-img { width: 55px; height: 55px; object-fit: contain; }
    .title-box { text-align: center; }
    .title { font-size: 13px; font-weight: bold; margin: 0; color: #000; }
    .subtitle { font-size: 8.5px; color: #444; margin-top: 2px; font-weight: bold; }

    table.data { width: 100%; border-collapse: collapse; table-layout: fixed; }
    table.data thead th {
        background: #004085; color: #fff; font-weight: bold;
        padding: 5px 2px; border: 1px solid #dee2e6;
        text-align: center; vertical-align: middle;
        font-size: 7.5px;
    }
    table.data td {
        padding: 4px 2px; border: 1px solid #dee2e6;
        vertical-align: middle;
        overflow-wrap: break-word;
        font-size: 7px;
    }
    .t-center { text-align: center; }
    .t-right { text-align: right; }
    .just { text-align: justify; }
    .bg-light { background-color: #f8f9fa; }
    .fw-bold { font-weight: bold; }
    
    .footer { 
        position: fixed; 
        bottom: -6mm; 
        left: 0; 
        right: 0; 
        color: #444; 
        font-size: 7.5px; 
        text-align: center;
        border-top: 1px solid #ccc;
        padding-top: 4px;
    }
    .pageno:before { content: counter(page); }
</style>
</head>
<body>

<div class="footer">
    <div>OFICINA DE PROGRAMACIÓN - UNIDAD DE ADMINISTRACIÓN - UE009 - VII DIRTEPOL LIMA</div>
    <div>RUC 20383430250 • Av. España 450 - Cercado de Lima</div>
    <div style="margin-top:2px;">Fecha de impresión: <?= date('d/m/Y H:i:s') ?> | Página <span class="pageno"></span></div>
</div>

<table class="header-table">
    <tr>
        <td class="logo-box">
            <?php if ($logoEscudo): ?><img src="<?= $logoEscudo ?>" class="logo-img"><?php endif; ?>
        </td>
        <td class="title-box">
            <div class="title"><?= $tituloReporte ?></div>
            <div class="subtitle">UNIDAD DE ADMINISTRACIÓN - UE009 - VII DIRTEPOL LIMA</div>
        </td>
        <td class="logo-box" style="text-align:right">
            <?php if ($logoRegpol): ?><img src="<?= $logoRegpol ?>" class="logo-img"><?php endif; ?>
        </td>
    </tr>
</table>

<table class="data">
    <thead>
        <tr>
            <th style="width:3.5%">N°</th>
            <th style="width:23.5%">OBJETO DE LA CONTRATACIÓN (DESCRIPCIÓN)</th>
            <th style="width:5.5%">CMN</th>
            <th style="width:8.5%">TIPO PROCESO</th>
            <th style="width:6%">MES PROG.</th>
            <th style="width:8.5%">ESTADO SITUACIONAL</th>
            <th style="width:8.5%">VAL. ESTIMADO (S/)</th>
            <th style="width:8.5%">VAL. ADJUDICADO (S/)</th>
            <th style="width:8.5%">IMP. COMPROM. (S/)</th>
            <th style="width:8.5%">IMP. DEVENG. (S/)</th>
            <th style="width:8.5%">IMP. GIRADO (S/)</th>
            <th style="width:7.5%">N° CERTIF.</th>
        </tr>
    </thead>
    <tbody>
        <?php 
        $count = 1;
        $t_estimado = 0; $t_adjudicado = 0; $t_comprometido = 0; $t_devengado = 0; $t_girado = 0;
        
        while ($row = $rs->fetch_assoc()): 
            $t_estimado += (float)$row['v_estimado'];
            $t_adjudicado += (float)$row['valor_adjudicado'];
            $t_comprometido += (float)$row['imp_comprometido'];
            $t_devengado += (float)$row['imp_devengado'];
            $t_girado += (float)$row['imp_girado'];
        ?>
            <tr>
                <td class="t-center fw-bold"><?= $row['ref_pac'] ?: $count ?></td>
                <td class="just"><?= htmlspecialchars($row['objeto_contrato']) ?></td>
                <td class="t-center"><?= htmlspecialchars((string)$row['cmn']) ?></td>
                <td class="t-center"><?= htmlspecialchars((string)$row['t_procedimiento']) ?></td>
                <td class="t-center"><?= htmlspecialchars((string)$row['mes_programado']) ?></td>
                <td class="t-center" style="font-size: 6.5px;"><?= htmlspecialchars((string)$row['estado_proceso']) ?></td>
                <td class="t-right"><?= money((float)$row['v_estimado']) ?></td>
                <td class="t-right"><?= money((float)$row['valor_adjudicado']) ?></td>
                <td class="t-right"><?= money((float)$row['imp_comprometido']) ?></td>
                <td class="t-right"><?= money((float)$row['imp_devengado']) ?></td>
                <td class="t-right"><?= money((float)$row['imp_girado']) ?></td>
                <td class="t-center"><?= htmlspecialchars((string)$row['certificado']) ?></td>
            </tr>
        <?php $count++; endwhile; ?>
    </tbody>
    <tfoot class="bg-light">
        <tr class="fw-bold">
            <th colspan="6" class="t-right" style="font-size: 8px;">TOTALES GENERALES:</th>
            <th class="t-right" style="font-size: 8px;"><?= money($t_estimado) ?></th>
            <th class="t-right" style="font-size: 8px;"><?= money($t_adjudicado) ?></th>
            <th class="t-right" style="font-size: 8px;"><?= money($t_comprometido) ?></th>
            <th class="t-right" style="font-size: 8px;"><?= money($t_devengado) ?></th>
            <th class="t-right" style="font-size: 8px;"><?= money($t_girado) ?></th>
            <th border="0"></th>
        </tr>
    </tfoot>
</table>

</body>
</html>
<?php
$html = ob_get_clean();

$options = new Options();
$options->set('isRemoteEnabled', true);
$options->set('isHtml5ParserEnabled', true);

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html, 'UTF-8');
$dompdf->setPaper('A4', 'landscape');

ob_start();
$dompdf->render();
$pdfOutput = $dompdf->output();

while (ob_get_level() > 0) {
    ob_end_clean();
}

$filename = 'reporte_seguimiento_pac_' . date('Ymd_His') . '.pdf';

if (!headers_sent()) {
    header('Content-Type: application/pdf');
    header('Content-Disposition: inline; filename="' . $filename . '"');
    header('Cache-Control: private, max-age=0, must-revalidate');
    header('Pragma: public');
}

echo $pdfOutput;
exit;
