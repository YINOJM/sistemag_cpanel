<?php
// reporte_menores_pdf.php
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
$estado = isset($_GET['estado']) ? (int)$_GET['estado'] : 0;
$q = isset($_GET['q']) ? $_GET['q'] : '';

/* ====== Consulta principal ====== */
$where = "WHERE m.anio = $anio";
if ($estado > 0) {
    $where .= " AND m.estado_id = $estado";
}
if (!empty($q)) {
    $s = $conn->real_escape_string($q);
    $where .= " AND (m.descripcion_servicio_bien LIKE '%$s%' OR m.id LIKE '%$s%')";
}

$sql = "
SELECT 
    m.*, 
    e.nombre as estado_nombre
FROM seguimiento_menores_8uit m
LEFT JOIN seguimiento_estados_menores e ON e.id = m.estado_id
$where
ORDER BY m.id DESC";

$rs = $conn->query($sql);

/* ====== Métricas (KPIs) ====== */
$sql_metricas = "SELECT 
                    COUNT(*) as total_procesos,
                    SUM(monto_comprometido) as total_comprometido,
                    SUM(monto_devengado) as total_devengado,
                    SUM(monto_girado) as total_girado
                 FROM seguimiento_menores_8uit m $where";
$res_metricas = $conn->query($sql_metricas);
$metricas = $res_metricas->fetch_assoc();

$total_proc = (float)($metricas['total_procesos'] ?? 0);
$tot_comp = (float)($metricas['total_comprometido'] ?? 0);
$tot_dev = (float)($metricas['total_devengado'] ?? 0);
$tot_gir = (float)($metricas['total_girado'] ?? 0);

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

$tituloReporte = "REPORTE DE CONTRATACIONES MENORES A 8 UIT ($anio)";

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

<table style="width:100%; margin-bottom: 12px; border-collapse: separate; border-spacing: 5px;">
    <tr>
        <td style="background: #eaf6fc; padding: 6px; border-radius: 4px; border-left: 3px solid #00acc1; width:25%;">
            <div style="font-size:6.5px; color:#555; font-weight:bold;">TOTAL CONTRATOS</div>
            <div style="font-size:10px; color:#00838f; font-weight:bold; padding-top:2px;"><?= number_format($total_proc) ?></div>
        </td>
        <td style="background: #e6f0ff; padding: 6px; border-radius: 4px; border-left: 3px solid #1e88e5; width:25%;">
            <div style="font-size:6.5px; color:#555; font-weight:bold;">IMP. COMPROMETIDO</div>
            <div style="font-size:10px; color:#1565c0; font-weight:bold; padding-top:2px;">S/ <?= money((float)$tot_comp) ?></div>
        </td>
        <td style="background: #fff8e1; padding: 6px; border-radius: 4px; border-left: 3px solid #ffb300; width:25%;">
            <div style="font-size:6.5px; color:#555; font-weight:bold;">IMP. DEVENGADO</div>
            <div style="font-size:10px; color:#f57f17; font-weight:bold; padding-top:2px;">S/ <?= money((float)$tot_dev) ?></div>
        </td>
        <td style="background: #e8f5e9; padding: 6px; border-radius: 4px; border-left: 3px solid #43a047; width:25%;">
            <div style="font-size:6.5px; color:#555; font-weight:bold;">IMP. GIRADO</div>
            <div style="font-size:10px; color:#2e7d32; font-weight:bold; padding-top:2px;">S/ <?= money((float)$tot_gir) ?></div>
        </td>
    </tr>
</table>

<table class="data">
    <thead>
        <tr>
            <th style="width:3%">N°</th>
            <th style="width:6%">TIPO DE CONTRATACIÓN</th>
            <th style="width:18%">OBJETO DE LA CONTRATACIÓN</th>
            <th style="width:10%">ÁREA USUARIA</th>
            <th style="width:7%">F. DE REQUERIMIENTO</th>
            <th style="width:7%">FECHA DE OC/OS</th>
            <th style="width:5%">PLAZO (DÍAS)</th>
            <th style="width:7%">F. FINAL EJECUCIÓN</th>
            <th style="width:7%">IMP. COMPROM.</th>
            <th style="width:7%">IMP. DEVENG.</th>
            <th style="width:7%">IMP. GIRADO</th>
            <th style="width:6%">ESTADO</th>
            <th style="width:10%">OBSERVACIONES</th>
        </tr>
    </thead>
    <tbody>
        <?php 
        $count = 1;
        $t_comprometido = 0; $t_devengado = 0; $t_girado = 0;
        
        while ($row = $rs->fetch_assoc()): 
            $t_comprometido += (float)$row['monto_comprometido'];
            $t_devengado += (float)$row['monto_devengado'];
            $t_girado += (float)$row['monto_girado'];
        ?>
            <tr>
                <td class="t-center fw-bold"><?= $count ?></td>
                <td class="t-center"><?= $row['tipo_orden'] == 'OC' ? 'BIENES' : 'SERVICIOS' ?></td>
                <td class="just"><?= htmlspecialchars($row['descripcion_servicio_bien']) ?></td>
                <td class="t-center small"><?= htmlspecialchars((string)$row['unidad_solicitante']) ?></td>
                <td class="t-center"><?= $row['fecha_requerimiento'] ? date('d/m/Y', strtotime($row['fecha_requerimiento'])) : '-' ?></td>
                <td class="t-center"><?= $row['fecha_emision'] ? date('d/m/Y', strtotime($row['fecha_emision'])) : '-' ?></td>
                <td class="t-center"><?= $row['plazo_ejecucion_dias'] ?></td>
                <td class="t-center"><?= $row['fecha_vencimiento'] ? date('d/m/Y', strtotime($row['fecha_vencimiento'])) : '-' ?></td>
                <td class="t-right"><?= money((float)$row['monto_comprometido']) ?></td>
                <td class="t-right"><?= money((float)$row['monto_devengado']) ?></td>
                <td class="t-right"><?= money((float)$row['monto_girado']) ?></td>
                <td class="t-center" style="font-size: 6px;"><?= strtoupper($row['estado_nombre'] ?? 'S/E') ?></td>
                <td class="just" style="font-size: 6px;"><?= htmlspecialchars((string)$row['observaciones']) ?></td>
            </tr>
        <?php $count++; endwhile; ?>
    </tbody>
    <tfoot class="bg-light">
        <tr class="fw-bold">
            <th colspan="8" class="t-right" style="font-size: 7.5px;">TOTAL GENERAL:</th>
            <th class="t-right" style="font-size: 7.5px;"><?= money($t_comprometido) ?></th>
            <th class="t-right" style="font-size: 7.5px;"><?= money($t_devengado) ?></th>
            <th class="t-right" style="font-size: 7.5px;"><?= money($t_girado) ?></th>
            <th colspan="2"></th>
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

$filename = 'reporte_menores_8uit_' . date('Ymd_His') . '.pdf';

if (!headers_sent()) {
    header('Content-Type: application/pdf');
    header('Content-Disposition: inline; filename="' . $filename . '"');
    header('Cache-Control: private, max-age=0, must-revalidate');
    header('Pragma: public');
}

echo $pdfOutput;
exit;
