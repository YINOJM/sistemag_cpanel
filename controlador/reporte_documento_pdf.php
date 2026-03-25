<?php
// controlador/reporte_documento_pdf.php
require_once '../vendor/autoload.php';
require_once '../modelo/DocumentoModelo.php';

use Dompdf\Dompdf;
use Dompdf\Options;

// Validar Sesión
session_start();
if (empty($_SESSION['id'])) {
    die("Acceso denegado.");
}

// Aumentar memoria y tiempo para reportes grandes
ini_set('memory_limit', '2048M');
set_time_limit(300);

$anio = $_GET['anio'] ?? date('Y');
$tipo = $_GET['tipo'] ?? '';
$fecha_inicio = $_GET['fecha_inicio'] ?? null;
$fecha_fin = $_GET['fecha_fin'] ?? null;

// Obtener datos
$modelo = new DocumentoModelo();
$data = $modelo->listarV2($anio, $tipo, $fecha_inicio, $fecha_fin);

// Configuración Dompdf
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true); // Para imágenes si hubiera
$dompdf = new Dompdf($options);

// Convertir imágenes a Base64 (Igual que inventario)
$pathEscudo = '../public/images/escudo.png';
$pathLogo = '../public/images/logo_regpol.png';

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
    <title>Reporte de Documentos</title>
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
        
        .header-center h2 { margin: 0; color: #00779e; font-size: 16px; text-transform: uppercase; }
        .header-center p { margin: 5px 0; color: #555; font-size: 12px; }
        
        .logo { height: 75px; width: auto; object-fit: contain; }

        /* TABLA DE DATOS */
        table.data-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        table.data-table th, table.data-table td { border: 1px solid #ddd; padding: 6px; text-align: left; }
        table.data-table th { background-color: #00779E; color: white; text-align: center; font-weight: bold; }
        table.data-table tr:nth-child(even) { background-color: #f2f2f2; }
        
        .center { text-align: center; }
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
                    <h2>REPORTE DE DOCUMENTOS REGISTRADOS</h2>
                    <?php if($fecha_inicio && $fecha_fin): ?>
                        <p>DEL <?= date('d/m/Y', strtotime($fecha_inicio)) ?> AL <?= date('d/m/Y', strtotime($fecha_fin)) ?> <?= $tipo ? " - TIPO: " . strtoupper($tipo) : "" ?></p>
                    <?php else: ?>
                        <p>AÑO: <?= $anio ?> <?= $tipo ? " - TIPO: " . strtoupper($tipo) : "" ?></p>
                    <?php endif; ?>
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
        <p>Fecha de impresión: <?= date('d/m/Y H:i:s') ?> • Generado por: <?= $_SESSION['nombre'] ?? 'Usuario' ?></p>
    </footer>

    <!-- Contenido principal (Tabla) -->
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 30px;">#</th>
                <th style="width: 100px;">N° Documento</th>
                <th style="width: 70px;">Fecha</th>
                <th style="width: 80px;">HT / Ref</th>
                <th>Destino</th>
                <th>Asunto</th>
                <th style="width: 100px;">Formulado Por</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($data)): ?>
                <tr><td colspan="7" class="center">No se encontraron registros.</td></tr>
            <?php else: ?>
                <?php $i=1; foreach($data as $d): ?>
                <tr>
                    <td class="center"><?= $i++ ?></td>
                    <td style="font-weight:bold;"><?= htmlspecialchars($d['num_completo']) ?></td>
                    <td class="center"><?= !empty($d['created_at']) ? date('d/m/Y', strtotime($d['created_at'])) : '-' ?></td>
                    <td class="center"><?= htmlspecialchars($d['ht'] ?? '-') ?></td>
                    <td><div style="font-size:10px;"><?= htmlspecialchars($d['nombre_destino'] ?? '') ?></div></td>
                    <td><?= nl2br(htmlspecialchars($d['asunto'] ?? '')) ?></td>
                    <td class="small"><?= htmlspecialchars($d['usuario_formulador'] ?? '') ?></td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

</body>
</html>
<?php
$html = ob_get_clean();

$dompdf->loadHtml($html);

// Orientación Horizontal para que quepa todo
$dompdf->setPaper('A4', 'landscape');

$dompdf->render();

// Enviar al navegador
$filename = "Reporte_Documentos_$anio" . ($tipo ? "_$tipo" : "") . ".pdf";
$dompdf->stream($filename, ["Attachment" => true]);
exit;
