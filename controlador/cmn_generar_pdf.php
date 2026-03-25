<?php
require '../vendor/autoload.php';
// Desactivar errores para el streaming limpio
error_reporting(0);
ini_set('display_errors', 0);
require_once "../modelo/conexion.php";
use Dompdf\Dompdf;
use Dompdf\Options;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Acceso no permitido.");
}

// Recibir datos
$grado = $_POST['grado'] ?? '';
$dni = $_POST['dni'] ?? '';
$cip = $_POST['cip'] ?? '';
$apellidos = $_POST['apellidos'] ?? '';
$nombres = $_POST['nombres'] ?? '';
$correo = $_POST['correo'] ?? '';
$celular = $_POST['celular'] ?? '';
$cargo = $_POST['cargo'] ?? '';
$subunidad = $_POST['sub_unidad'] ?? '';
$fecha = date('d / m / Y');

// Rutas de Imágenes (Convertir a Base64 para Dompdf)
$pathEscudo = '../public/images/escudo.png';
$pathLogoRegpol = '../public/images/logo_regpol.png';
$pathSiaf = '../public/images/logo-siaf-solid.svg';

$base64Escudo = '';
if (file_exists($pathEscudo)) {
    $type = pathinfo($pathEscudo, PATHINFO_EXTENSION);
    $data = file_get_contents($pathEscudo);
    $base64Escudo = 'data:image/' . $type . ';base64,' . base64_encode($data);
}

$base64Logo = '';
if (file_exists($pathLogoRegpol)) {
    $type = pathinfo($pathLogoRegpol, PATHINFO_EXTENSION);
    $data = file_get_contents($pathLogoRegpol);
    $base64Logo = 'data:image/' . $type . ';base64,' . base64_encode($data);
}

$base64Siaf = '';
if (file_exists($pathSiaf)) {
    $type = pathinfo($pathSiaf, PATHINFO_EXTENSION);
    $mime = 'image/svg+xml';
    $data = file_get_contents($pathSiaf);
    $base64Siaf = 'data:' . $mime . ';base64,' . base64_encode($data);
}

// Configuración de Dompdf
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);
$dompdf = new Dompdf($options);

// HTML del Formulario Estilizado con Azules Claros
$html = '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        @page { margin: 1cm; }
        body { font-family: "Arial", sans-serif; font-size: 9.5pt; color: #444; margin: 0; padding: 0; }
        /* Suavizamos el renderizado al máximo */
        
        .container { position: relative; width: 100%; height: 100%; }
        
        /* Watermark Background */
        .watermark-bg { position: absolute; top: 22%; left: 0%; width: 100%; z-index: -1000; opacity: 0.12; text-align: center; }
        .watermark-bg img { width: 440px; }

        /* Header */
        .header-table { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        .header-logo-left { width: 100px; vertical-align: middle; }
        .header-title { text-align: center; font-weight: normal; font-size: 11pt; line-height: 1.2; color: #1F618D; padding: 0 10px; }
        .header-logo-right { width: 110px; text-align: right; vertical-align: middle; }
        
        /* Date Box */
        .date-box-table { border: 1px solid #3498DB; border-collapse: collapse; float: right; margin-bottom: 10px; }
        .date-box-table td { border: 1px solid #3498DB; padding: 3px 8px; text-align: center; font-size: 8.5pt; }
        .bg-blue-soft { background-color: #EBF5FB; color: #3498DB; font-weight: normal; }

        /* Tables & Layout */
        table.main-table { width: 100%; border-collapse: collapse; margin-bottom: 8px; border: 1px solid #3498DB; }
        table.main-table td { border: 1px solid #AED6F1; padding: 6px 8px; vertical-align: middle; }
        
        /* Etiquetas y Valores - Balance de Nitidez */
        .label-cell { background-color: rgba(244, 249, 253, 0.6); font-weight: bold !important; font-size: 8pt; color: #1F618D; text-transform: uppercase; width: 15%; }
        .value-cell { background-color: transparent; font-weight: normal !important; font-size: 9.5pt; color: #000000; }

        .section-header { background-color: #D4E6F1; font-weight: bold !important; padding: 6px; border: 1px solid #3498DB; font-size: 8.5pt; text-align: center; color: #1B4F72; text-transform: uppercase; }
        
        /* Checks Table */
        .checks-grid { width: 100%; border-collapse: collapse; margin-top: 5px; }
        .checks-grid td { border: 1px solid #AED6F1; padding: 5px; font-size: 8.5pt; color: #1B4F72; }
        .check-box-ui { width: 16px; height: 16px; border: 1.5px solid #2E86C1; background: #FFF; display: inline-block; text-align: center; line-height: 16px; font-weight: normal; margin-right: 8px; color: #666; border-radius: 2px; }

        /* Signature Section - JERARQUIA NIVELADA */
        .signature-area { margin-top: 40px; width: 100%; position: relative; }
        .sig-block { width: 45%; text-align: center; display: inline-block; vertical-align: top; }
        .sig-line { border-top: 1.2pt dotted #2E86C1; width: 85%; margin: 0 auto 8px auto; } /* Volvemos a PUNTEADA mas fina */
        .sig-name { font-weight: bold !important; font-size: 10pt; text-transform: uppercase; color: #1B4F72; line-height: 1.2; }
        .sig-label { font-weight: bold !important; font-size: 8.5pt; color: #2E86C1; margin-top: 2px; }
        .sig-sub { font-size: 7.5pt; color: #5D6D7E; }

        .spacer-sello-jefe { height: 140px; } 
        .spacer-sello-log  { height: 65px; }  

        /* Footer - Mas suave y elegante */
        .footer { position: fixed; bottom: 0; left: 0; width: 100%; text-align: center; border-top: 1px solid #AED6F1; padding-top: 8px; font-size: 8pt; color: #7F8C8D; font-weight: normal; }
    </style>
</head>
<body>
    <div class="footer">
        OFICINA DE PROGRAMACIÓN - UNIDAD DE ADMINISTRACIÓN - UE009 - VII DIRTEPOL LIMA RUC 20383430250 •<br>
        Av. España 450 - Cercado de Lima
    </div>
    <div class="container">
        <!-- Marca de Agua Escudo PNP (Más visible y centrada) -->
        <div class="watermark-bg">
            ' . ($base64Escudo ? '<img src="' . $base64Escudo . '">' : '') . '
        </div>

        <!-- HEADER ESTILIZADO -->
        <table class="header-table">
            <tr>
                <td class="header-logo-left" style="padding-bottom: 12px;">
                    ' . ($base64Logo ? '<img src="' . $base64Logo . '" style="width: 85px;">' : '') . '
                </td>
                <td class="header-title">
                    FORMATO PARA SOLICITAR USUARIO Y CONTRASEÑA<br>
                    DE ACCESO A SISTEMA INTEGRADO DE ADMINISTRACION<br>
                    FINANCIERA - SIAF WEB - ' . ANIO_CMN . '
                </td>
                <td class="header-logo-right">
                    ' . ($base64Siaf ? '<img src="' . $base64Siaf . '" style="width: 125px;">' : '') . '
                </td>
            </tr>
        </table>

        <!-- FECHA EN CAJA AZUL -->
        <div style="width: 100%; height: 35px;">
            <table class="date-box-table">
                <tr>
                    <td class="bg-blue-soft">FECHA:</td>
                    <td>' . date('d') . '</td>
                    <td>' . date('m') . '</td>
                    <td>' . date('Y') . '</td>
                </tr>
            </table>
        </div>

        <!-- SECCIÓN 1: REFERENCIA -->
        <table class="main-table">
            <tr>
                <td class="label-cell">DOCUMENTO REFERENCIA</td>
                <td class="value-cell" style="font-size: 8.5pt;">LA DIRECTIVA N° 0007-2025-EF/54.01 "DIRECTIVA PARA LA PROGRAMACIÓN MULTIANUAL DE BIENES, SERVICIOS Y OBRAS" (PMBSO).</td>
            </tr>
        </table>

        <!-- SECCIÓN 2: DATOS PERSONALES -->
        <table class="main-table">
            <tr>
                <td class="label-cell" style="width: 10%;">GRADO:</td>
                <td class="value-cell" style="width: 20%;">' . htmlspecialchars($grado) . '</td>
                <td class="label-cell" style="width: 10%;">CIP:</td>
                <td class="value-cell" style="width: 25%;">' . htmlspecialchars($cip) . '</td>
                <td class="label-cell" style="width: 10%;">DNI:</td>
                <td class="value-cell" style="width: 25%;">' . htmlspecialchars($dni) . '</td>
            </tr>
            <tr>
                <td class="label-cell">APELLIDOS Y NOMBRES:</td>
                <td class="value-cell" colspan="5" style="font-size: 11pt; letter-spacing: 0.5px;">' . htmlspecialchars($apellidos) . ', ' . htmlspecialchars($nombres) . '</td>
            </tr>
        </table>

        <!-- SECCIÓN 3: UBICACIÓN Y CARGO -->
        <table class="main-table">
            <tr>
                <td class="label-cell" style="width: 10%;">ÁREA / UNIDAD</td>
                <td class="value-cell" style="width: 30%;">' . htmlspecialchars($subunidad) . '</td>
                <td class="label-cell" style="width: 10%;">SECCIÓN</td>
                <td class="value-cell" style="width: 25%;">LOGÍSTICA</td>
                <td class="label-cell" style="width: 10%;">CARGO</td>
                <td class="value-cell" style="width: 15%;">' . htmlspecialchars($cargo) . '</td>
            </tr>
        </table>

        <!-- SECCIÓN 4: CONTACTO -->
        <table class="main-table">
            <tr>
                <td class="label-cell" style="width: 10%;">CELULAR</td>
                <td class="value-cell" style="width: 25%;">' . htmlspecialchars($celular) . '</td>
                <td class="label-cell" style="width: 10%;">EMAIL:</td>
                <td class="value-cell" style="width: 55%; font-family: monospace;">' . htmlspecialchars($correo) . '</td>
            </tr>
        </table>

        <!-- SECCIÓN 5: MOTIVOS -->
        <table class="main-table">
            <tr>
                <td class="label-cell">MOTIVOS DEL USO:</td>
                <td class="value-cell">PROGRAMACION MULTIANUAL / DIRECTIVA N° 0007-2025-EF/54.01</td>
            </tr>
        </table>

        <!-- TABLA DE COMPROMISOS ESTILIZADA -->
        <table style="width: 100%; border-collapse: collapse; border: 1.5px solid #A9CCE3; margin-top: 5px;">
            <tr style="background-color: #F4F9FD;">
                <td style="padding: 8px; border: 1px solid #D4E6F1; text-align: center; width: 33.3%;">
                    <div class="check-box-ui"></div> COMPROMISO ANUAL
                </td>
                <td style="padding: 8px; border: 1px solid #D4E6F1; text-align: center; width: 33.3%;">
                    <div class="check-box-ui"></div> COMPROMISO MENSUAL
                </td>
                <td style="padding: 8px; border: 1px solid #D4E6F1; width: 33.3%;"></td>
            </tr>
        </table>

        <!-- GRID DE MODULOS Y ROLES -->
        <div style="width: 100%; margin-top: 15px;">
            <div style="width: 48%; float: left;">
                <div class="section-header">MÓDULO</div>
                <table class="checks-grid">
                    <tr><td style="width: 30px; text-align: center;"><div class="check-box-ui">X</div></td><td>PMBSO</td></tr>
                    <tr><td style="height: 20px;"></td><td></td></tr>
                    <tr><td style="height: 20px;"></td><td></td></tr>
                    <tr><td style="height: 20px;"></td><td></td></tr>
                </table>
            </div>
            <div style="width: 49%; float: right;">
                <div class="section-header">DESCRIPCIÓN DEL ROL</div>
                <table class="checks-grid">
                    <tr><td style="width: 30px; text-align: center;"><div class="check-box-ui">X</div></td><td>PROGRAMACIÓN CMN</td></tr>
                    <tr><td style="width: 30px; text-align: center;"><div class="check-box-ui">X</div></td><td>CMM BIENES SERVICIOS Y OBRAS</td></tr>
                    <tr><td style="width: 30px; text-align: center;"><div class="check-box-ui">X</div></td><td>REPORTES</td></tr>
                    <tr><td style="width: 30px; text-align: center;"><div class="check-box-ui">X</div></td><td>ANEXOS DIRECTIVA</td></tr>
                </table>
            </div>
            <div style="clear: both;"></div>
        </div>

        <!-- SECCIÓN DE FIRMAS - JERARQUÍA POR NIVELES -->
        <div class="signature-area">
            <!-- Izquierda: Jefe de Unidad (MÁS ABAJO) -->
            <div class="sig-block" style="float: left;">
                <div class="spacer-sello-jefe"></div>
                <div class="sig-line"></div>
                <div class="sig-name">JEFE DE UNIDAD</div>
                <div class="sig-sub">(Firma y Sello)</div>
            </div>
            
            <!-- Derecha: Responsable Logístico (MÁS ARRIBA) -->
            <div class="sig-block" style="float: right;">
                <div class="spacer-sello-log"></div>
                <div class="sig-line"></div>
                <div class="sig-name">' . htmlspecialchars($grado) . ' PNP ' . htmlspecialchars($apellidos) . ' ' . htmlspecialchars($nombres) . '</div>
                <div class="sig-label">RESPONSABLE LOGÍSTICO</div>
                <div style="font-size: 8pt; color: #1B4F72; font-weight: normal; margin-bottom: 2px;">' . htmlspecialchars($subunidad) . '</div>
                <div class="sig-sub">DNI: ' . htmlspecialchars($dni) . ' | CIP: ' . htmlspecialchars($cip) . '</div>
            </div>
            <div style="clear: both;"></div>
        </div>
    </div>
</body>
</html>';

// Desactivar errores para evitar basura en el stream
error_reporting(0);
ini_set('display_errors', 0);

// Generar PDF
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

$filename = "SOLICITUD_SIAF" . ANIO_CMN . "_" . $cip . ".pdf";
$pdfOutput = $dompdf->output();

// Limpiar cualquier buffer previo
if (ob_get_length())
    ob_end_clean();

// Cabeceras para forzar DESCARGA (Download)
header('Content-Type: application/octet-stream'); // Fuerza descarga en casi todos los navegadores
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . strlen($pdfOutput));
header('Content-Transfer-Encoding: binary');
header('Cache-Control: private, no-transform, no-store, must-revalidate');
header('Expires: 0');
header('Pragma: no-cache');

echo $pdfOutput;
exit;
