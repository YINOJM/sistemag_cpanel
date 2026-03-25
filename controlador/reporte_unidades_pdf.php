<?php
// controlador/reporte_unidades_pdf.php
require '../vendor/autoload.php';
require_once '../modelo/conexion.php';
require_once '../modelo/UnidadesPoliciales.php';

use Dompdf\Dompdf;
use Dompdf\Options;

// Validar Sesión
session_start();
if (empty($_SESSION['id'])) {
    die("Acceso denegado.");
}

// Aumentar memoria para reportes grandes
ini_set('memory_limit', '1024M');
set_time_limit(300);

// Obtener datos
$modelo = new UnidadesPoliciales($conexion);
$data = $modelo->obtenerJerarquiaCompleta();

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
            .badge-A { background-color: #28a745; }
            .badge-B { background-color: #17a2b8; }
            .badge-C { background-color: #ffc107; color: black; }
    
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
                        <h2>REPORTE GENERAl DE COMISARÍAS BÁSICAS PNP</h2>
                        <p>LISTADO DE UNIDADES POLICIALES Y UBICACIÓN</p>
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
                <th width="4%">N°</th>
                <th width="15%">REGIÓN</th>
                <th width="15%">DIVISIÓN</th>
                <th width="5%">TIPO</th>
                <th width="25%">NOMBRE DE LA UNIDAD</th>
                <th width="12%">DEPARTAMENTO</th>
                <th width="12%">PROVINCIA</th>
                <th width="12%">DISTRITO</th>
            </tr>
        </thead>
        <tbody>';

$i = 1;
foreach ($data as $d) {
    // Clase de badge según tipo (A, B, C...)
    $tipo = trim($d['tipo_unidad']);
    $badgeClass = 'badge';
    if ($tipo == 'A') $badgeClass .= ' badge-A';
    elseif ($tipo == 'B') $badgeClass .= ' badge-B';
    elseif ($tipo == 'C') $badgeClass .= ' badge-C';

    $html .= '<tr>
                <td class="center">' . $i . '</td>
                <td>' . htmlspecialchars($d['nombre_region']) . '</td>
                <td>' . htmlspecialchars($d['nombre_division']) . '</td>
                <td class="center"><span class="' . $badgeClass . '">' . $tipo . '</span></td>
                <td>' . htmlspecialchars($d['nombre_subunidad']) . '</td>
                <td>' . htmlspecialchars($d['departamento']) . '</td>
                <td>' . htmlspecialchars($d['provincia']) . '</td>
                <td>' . htmlspecialchars($d['distrito']) . '</td>
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

$dompdf->render();

// Salida
$dompdf->stream("Reporte_Unidades_Policiales_" . date('d-m-Y') . ".pdf", ["Attachment" => true]);
