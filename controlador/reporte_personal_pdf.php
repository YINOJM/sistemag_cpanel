<?php
ob_start();
ini_set('display_errors', '0');
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE & ~E_WARNING);

// controlador/reporte_personal_pdf.php
require_once __DIR__ . "/../modelo/conexion.php";

if (empty($_SESSION['id'])) {
    die("Acceso denegado.");
}

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../modelo/PersonalModelo.php';

use Dompdf\Dompdf;
use Dompdf\Options;

// Aumentar memoria para reportes grandes
ini_set('memory_limit', '1024M');
set_time_limit(300);

// Obtener datos
$modelo = new PersonalModelo();
$data = $modelo->listar();

// Configurar Dompdf
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);

$dompdf = new Dompdf($options);

// Convertir imágenes a Base64
$pathEscudo = '../public/images/escudo.png';
$pathLogoRegpol = '../public/images/logo_regpol.png';

$typeEscudo = pathinfo($pathEscudo, PATHINFO_EXTENSION);
$typeLogoRegpol = pathinfo($pathLogoRegpol, PATHINFO_EXTENSION);

$dataEscudo = file_get_contents($pathEscudo);
$dataLogoRegpol = file_get_contents($pathLogoRegpol);

$base64Escudo = 'data:image/' . $typeEscudo . ';base64,' . base64_encode($dataEscudo);
$base64LogoRegpol = 'data:image/' . $typeLogoRegpol . ';base64,' . base64_encode($dataLogoRegpol);

// Generar HTML
$html = '
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <style>
            body { font-family: Arial, sans-serif; font-size: 10px; }
            
            @page { margin: 100px 40px 60px 40px; }
            
            header { 
                position: fixed; 
                top: -80px; 
                left: 0px; 
                right: 0px; 
                height: 80px; 
            }

            .header-table { width: 100%; border: none; margin-bottom: 0px; }
            .header-table td { border: none; vertical-align: middle; padding: 0; }
            .header-left { width: 20%; text-align: left; }
            .header-center { width: 60%; text-align: center; }
            .header-right { width: 20%; text-align: right; }
            
            .header-center h2 { margin: 0; color: #006db3; font-size: 16px; text-transform: uppercase; }
            .header-center p { margin: 5px 0; color: #555; font-size: 11px; }
            
            .logo { height: 60px; width: auto; }

            table { width: 100%; border-collapse: collapse; margin-top: 10px; }
            th, td { border: 1px solid #ddd; padding: 5px; text-align: left; }
            
            th { background-color: #006db3; color: white; text-align: center; font-weight: bold; }
            
            .center { text-align: center; }
            .badge { display: inline-block; padding: 2px 5px; border-radius: 3px; font-size: 9px; color: white; background-color: #6c757d; }
            .badge-success { background-color: #28a745; }
            .badge-danger { background-color: #dc3545; }
            
            tr:nth-child(even) { background-color: #f9f9f9; }
            
            footer {
                position: fixed;
                bottom: -40px;
                left: 0px;
                right: 0px;
                height: 40px;
                text-align: center;
                color: #666;
                font-size: 9px;
                border-top: 1px solid #ddd;
                padding-top: 5px;
            }
        </style>
    </head>
    <body>
        <header>
            <table class="header-table">
                <tr>
                    <td class="header-left">
                        <img src="' . $base64Escudo . '" class="logo">
                    </td>
                    <td class="header-center">
                        <h2>UNIDAD DE ADMINISTRACIÓN - UE 009 REGIÓN POLICIAL LIMA</h2>
                        <p style="font-size: 14px; font-weight: bold; color: #333;">PADRÓN GENERAL DE PERSONAL POLICIAL</p>
                    </td>
                    <td class="header-right">
                        <img src="' . $base64LogoRegpol . '" class="logo">
                    </td>
                </tr>
            </table>
        </header>
        
        <footer>
            <p>Sistema Integrado de Gestión - Generado el: ' . date('d/m/Y H:i:s') . '</p>
        </footer>

    <table>
        <thead>
            <tr>
                <th width="3%">N°</th>
                <th width="8%">GRADO</th>
                <th width="20%">APELLIDOS Y NOMBRES</th>
                <th width="8%">CIP</th>
                <th width="8%">DNI</th>
                <th width="20%">UNIDAD / SUB-UNIDAD</th>
                <th width="15%">CARGO</th>
                <th width="10%">SITUACIÓN ESP.</th>
                <th width="8%">ESTADO</th>
            </tr>
        </thead>
        <tbody>';

$i = 1;
foreach ($data as $d) {
    // Definir estado
    $estado = 'Activo'; 
    $badgeClass = 'badge badge-success';
    if(isset($d['estado']) && $d['estado'] != 'Activo') {
        $estado = $d['estado'];
        $badgeClass = 'badge badge-danger';
    }

    $html .= '<tr>
                <td class="center">' . $i . '</td>
                <td>' . htmlspecialchars($d['nombre_grado'] ?? '-') . '</td>
                <td>' . htmlspecialchars(($d['apellidos'] ?? '') . ' ' . ($d['nombres'] ?? '')) . '</td>
                <td class="center">' . htmlspecialchars($d['cip'] ?? '-') . '</td>
                <td class="center">' . htmlspecialchars($d['dni'] ?? '-') . '</td>
                <td>' . htmlspecialchars($d['nombre_subunidad'] ?? 'NO ASIGNADO') . '</td>
                <td>' . htmlspecialchars($d['cargo'] ?? '-') . '</td>
                <td>' . htmlspecialchars($d['situacion_especial'] ?? '-') . '</td>
                <td class="center"><span class="' . $badgeClass . '">' . $estado . '</span></td>
              </tr>';
    $i++;
}

$html .= '
        </tbody>
    </table>
</body>
</html>';

$dompdf->loadHtml($html);

// Orientación Horizontal para que quepan las columnas
$dompdf->setPaper('A4', 'landscape');

ob_start();
$dompdf->render();
$pdfOutput = $dompdf->output();

while (ob_get_level() > 0) {
    ob_end_clean();
}

$filename = "Reporte_Personal_Policial_" . date('d-m-Y') . ".pdf";

if (!headers_sent()) {
    header('Content-Type: application/pdf');
    header('Content-Disposition: inline; filename="' . $filename . '"');
    header('Cache-Control: private, max-age=0, must-revalidate');
    header('Pragma: public');
}

echo $pdfOutput;
exit;
