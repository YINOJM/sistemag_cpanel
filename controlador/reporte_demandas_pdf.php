<?php
// controlador/reporte_demandas_pdf.php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../modelo/DemandasModelo.php';

ini_set('display_errors', '0');
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE & ~E_WARNING);

use Dompdf\Dompdf;
use Dompdf\Options;

// Validar Sesión
if (empty($_SESSION['id'])) {
    die("Acceso denegado.");
}

// Aumentar memoria y tiempo para reportes grandes
ini_set('memory_limit', '2048M');
set_time_limit(300);

// Obtener datos
$anio = isset($_GET['anio']) ? $_GET['anio'] : null;
$data = DemandasModelo::listar($anio);

// Configuración Dompdf
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true); // Para imágenes si hubiera
$dompdf = new Dompdf($options);

// Convertir imágenes a Base64
$pathEscudo = __DIR__ . '/../public/images/escudo.png';
$pathLogo = __DIR__ . '/../public/images/logo_regpol.png';

$typeEscudo = pathinfo($pathEscudo, PATHINFO_EXTENSION);
$typeLogo = pathinfo($pathLogo, PATHINFO_EXTENSION);

$dataEscudo = file_exists($pathEscudo) ? file_get_contents($pathEscudo) : '';
$dataLogo = file_exists($pathLogo) ? file_get_contents($pathLogo) : '';

$base64Escudo = 'data:image/' . $typeEscudo . ';base64,' . base64_encode($dataEscudo);
$base64Logo = 'data:image/' . $typeLogo . ';base64,' . base64_encode($dataLogo);

// Generar HTML
ob_start();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Demandas Presupuestales</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 11px; }
        
        /* Márgenes: Arriba 110px para Header, Abajo 80px para Footer */
        @page { margin: 110px 50px 80px 50px; }
        
        /* HEADER FIJO */
        header { 
            position: fixed; 
            top: -110px; 
            left: 0px; 
            right: 0px; 
            height: 110px; 
        }

        /* HEADER TABLE */
        .header-table { width: 100%; border: none; margin-bottom: 0px; padding-top: 15px; }
        .header-table td { border: none; vertical-align: middle; padding: 0; }
        .header-left { width: 20%; text-align: left; }
        .header-center { width: 60%; text-align: center; }
        .header-right { width: 20%; text-align: right; }
        
        .header-center h2 { margin: 0; color: #00607a; font-size: 16px; text-transform: uppercase; }
        .header-center p { margin: 5px 0; color: #555; font-size: 12px; }
        
        .logo { height: 75px; width: auto; object-fit: contain; }

        /* TABLA DE DATOS */
        table.data-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        table.data-table th, table.data-table td { border: 1px solid #ddd; padding: 6px; text-align: left; }
        table.data-table th { background-color: #00607a; color: white; text-align: center; font-weight: bold; }
        table.data-table tr:nth-child(even) { background-color: #f6fbfd; }
        
        .center { text-align: center; }
        .right { text-align: right; }
        .small { font-size: 10px; color: #666; }
        
        /* FOOTER FIJO */
        footer {
            position: fixed;
            bottom: -80px;
            left: 0px;
            right: 0px;
            height: 80px;
            text-align: center;
            color: #666;
            font-size: 10px;
            line-height: 1.5;
            padding-top: 10px;
            border-top: 1px solid #eee;
        }
        
        footer p { margin: 3px 0; }
        .footer-title { color: #333; }
    </style>
</head>
<body>

    <header>
        <table class="header-table">
            <tr>
                <td class="header-left">
                    <img src="<?= $base64Escudo ?>" class="logo">
                </td>
<td class="header-center">
    <!-- --- MODIFICACIÓN: Título dinámico --- -->
    <?php $textoAnio = ($anio && $anio !== 'todos') ? " - AÑO FISCAL $anio " : ""; ?>
    <h2>REPORTE DE DEMANDAS PRESUPUESTALES <?= $textoAnio ?></h2>
    <p>AL <?= date('d/m/Y') ?></p>
</td>

                <td class="header-right">
                    <img src="<?= $base64Logo ?>" class="logo">
                </td>
            </tr>
        </table>
    </header>

    <footer>
        <p class="footer-title">OFICINA DE PROGRAMACIÓN - UNIDAD DE ADMINISTRACIÓN - UE009 - VII DIRTEPOL LIMA</p>
        <p>RUC 20383430250 • Av. España 450 - Cercado de Lima</p>
        <p>Fecha de impresión: <?= date('d/m/Y H:i:s') ?></p>
    </footer>

    <!-- Contenido principal (Tabla) -->
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 30px;" class="center">Nº</th>
                <th style="width: 80px;">CUI</th>
                <th style="width: 80px;">Nº Exp.</th>
                <th>Descripción General</th>
                <th style="width: 90px;">Monto Total (S/)</th>
                <th style="width: 90px;">Fecha Registro</th>
                <th style="width: 60px;">Estado</th>
            </tr>
        </thead>
                <tbody>
            <?php 
            $totalAccum = 0;
            $numero = 1; // --- INICIALIZAR EL CONTADOR SUCESIVO (1, 2, 3...) ---
            if (empty($data)): 
            ?>
                <tr><td colspan="7" class="center">No se encontraron demandas registradas.</td></tr>
            <?php else: ?>
                <?php foreach($data as $d): 
                    $totalAccum += (float)$d['total_presupuesto'];
                ?>
                <tr>
                   <td class="center" style="text-align: center; vertical-align: middle;"><?= $numero++ ?></td>
                    <td class="center"><?= htmlspecialchars($d['cui'] ?: '-') ?></td>
                    <td class="center"><?= htmlspecialchars($d['nro_expediente'] ?: '-') ?></td>
                    <td style="text-transform: uppercase;"><?= nl2br(htmlspecialchars($d['descripcion_general'])) ?></td> <!-- --- MAYÚSCULAS --- -->
                    <td class="right"><?= number_format($d['total_presupuesto'], 2, '.', ',') ?></td>
                    <td class="center"><?= date('d/m/Y H:i', strtotime($d['fecha_registro'])) ?></td>
                    <td class="center" style="text-transform: uppercase;"><?= htmlspecialchars($d['estado']) ?></td>
                </tr>
                <?php endforeach; ?>
                <tr>
                    <td colspan="4" class="right" style="font-weight: bold; font-size: 12px;">TOTAL ACUMULADO:</td>
                    <td class="right" style="font-weight: bold; background-color: #e9f7df; font-size: 12px; color: #198754;"><?= number_format($totalAccum, 2, '.', ',') ?></td>
                    <td colspan="2"></td>
                </tr>
            <?php endif; ?>
        </tbody>

    </table>

</body>
</html>
<?php
$html = ob_get_clean();

$dompdf->loadHtml($html);

// Orientación Horizontal para aprovechar el ancho (Demandas tienen texto largo)
$dompdf->setPaper('A4', 'landscape');

ob_start();
$dompdf->render();
$pdfOutput = $dompdf->output();

while (ob_get_level() > 0) {
    ob_end_clean();
}

$txtAnio = ($anio && $anio !== 'todos') ? "_".$anio : "";
$filename = "Reporte_Demandas_Presupuestales" . $txtAnio . ".pdf";

if (!headers_sent()) {
    header('Content-Type: application/pdf');
    header('Content-Disposition: inline; filename="' . $filename . '"');
    header('Cache-Control: private, max-age=0, must-revalidate');
    header('Pragma: public');
}

echo $pdfOutput;
exit;
