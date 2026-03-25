<?php
require_once __DIR__ . '/../modelo/conexion.php';

if (empty($_SESSION['id'])) {
    die("Acceso denegado.");
}

require_once __DIR__ . '/../vendor/autoload.php';

error_reporting(0);
ini_set('display_errors', 0);

use Dompdf\Dompdf;
use Dompdf\Options;

$dni = $_GET['dni'] ?? '';

if (empty($dni)) {
    die("DNI no proporcionado.");
}

$stmt = $conexion->prepare("SELECT * FROM cmn_responsables WHERE dni = ? AND archivo_pdf IS NOT NULL LIMIT 1");
$stmt->bind_param("s", $dni);
$stmt->execute();
$res = $stmt->get_result();
$row = $res->fetch_assoc();

if (!$row) {
    die("No se encontro un registro completado o el archivo PDF aun no ha sido subido para este DNI.");
}

$fecha = date('d/m/Y H:i:s', strtotime($row['fecha_registro']));
$codigo_cargo = 'CMN' . substr(ANIO_CMN, -2) . '-' . str_pad($row['id'], 5, '0', STR_PAD_LEFT) . '-' . strtoupper(substr(hash('sha256', $row['dni'] . $row['fecha_registro']), 0, 8));

// Rutas de Imágenes
$pathEscudo = '../public/images/escudo.png';
$pathLogoRegpol = '../public/images/logo_regpol.png';
$pathFirma = '../public/images/firma-pro.png';

function getBase64($path)
{
    if (file_exists($path)) {
        $type = pathinfo($path, PATHINFO_EXTENSION);
        $data = file_get_contents($path);
        return 'data:image/' . $type . ';base64,' . base64_encode($data);
    }
    return '';
}

$base64Escudo = getBase64($pathEscudo);
$base64Logo = getBase64($pathLogoRegpol);
$base64Firma = getBase64($pathFirma);

// URL de Validación (Dirigida al Google Sites que es el enlace estable)
$urlValidacion = "https://sites.google.com/view/sistema-tramite";
$qrApiUrl = "https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=" . urlencode($urlValidacion);
$base64Qr = '';
$qrData = @file_get_contents($qrApiUrl);
if ($qrData) {
    $base64Qr = 'data:image/png;base64,' . base64_encode($qrData);
}

$html = '
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: "Helvetica", "Arial", sans-serif; font-size: 14px; text-align: justify; margin: 0; padding: 0; }
        .header-section { margin-bottom: 15px; border-bottom: 2px solid #003666; padding-bottom: 5px; }
        .titulo { text-align: center; font-size: 18px; font-weight: bold; margin-bottom: 10px; text-decoration: underline; color: #000; }
        .dato { margin-bottom: 8px; font-size: 13px; }
        
        .firma-wrapper { margin-top: 40px; text-align: center; }
        .firma-digital {
            display: inline-block;
            border: 1px solid #003666;
            padding: 10px 20px;
            background-color: #f8fbff;
            border-radius: 6px;
            position: relative;
            width: 280px;
            text-align: center;
        }
        .img-firma {
            width: 120px;
            height: auto;
            position: absolute;
            top: 5px;
            left: 50%;
            margin-left: -60px;
            z-index: 1000;
        }
        .linea-firma {
            margin-top: 40px;
            border-top: 1px solid #777;
            padding-top: 5px;
        }
        .cargo-txt { font-size: 11px; color: #444; line-height: 1.2; }
        
        .footer { 
            position: fixed; 
            bottom: -35px; 
            left: 0px; 
            width: 100%; 
            text-align: center; 
            font-size: 8px; 
            color: #666;
            border-top: 0.5px solid #eee; 
            padding-top: 5px;
        }
        .table-datos { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .table-datos td { padding: 4px 8px; border: 1px solid #ccc; font-size: 12px; }
        .table-datos td.lbl { font-weight: bold; width: 35%; background-color: rgba(245, 245, 245, 0.4); }
        
        .watermark { position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); width: 400px; opacity: 0.08; z-index: -1000; }
        .codigo-box { margin-top: 10px; padding: 8px; border: 1px dashed #666; font-size: 10px; text-align: center; background-color: #fcfcfc;}
        .qr-header { position: absolute; top: 0; right: 0; }
    </style>
</head>
<body>
    <img src="' . $base64Escudo . '" class="watermark">
    
    <table style="width: 100%; border: none; margin-bottom: 10px;">
        <tr>
            <td style="width: 15%; text-align: left;"><img src="' . $base64Logo . '" style="width: 55px;"></td>
            <td style="width: 70%; text-align: center; vertical-align: middle;">
                <div style="font-weight: bold; font-size: 12px; line-height: 1.3;">
                    POLICÍA NACIONAL DEL PERÚ<br>
                    REGIÓN POLICIAL LIMA<br>
                    UNIDAD DE ADMINISTRACIÓN - UE009 - VII DIRTEPOL LIMA
                </div>
            </td>
            <td style="width: 15%; text-align: right;">
                <div style="text-align: center;">
                    <img src="' . $base64Qr . '" style="width: 65px; height: 65px; border: 1px solid #eee; padding: 2px;">
                    <div style="font-size: 6px; color: #666; margin-top: 2px;">VALIDAR AQUÍ</div>
                </div>
            </td>
        </tr>
    </table>

    <div class="header-section">
        <div style="font-size: 11px; font-weight: bold;">Fecha y Hora de Recepción: <span style="font-weight: normal;">' . $fecha . '</span></div>
    </div>

    <div class="titulo">CONSTANCIA DE CUMPLIMIENTO (CARGO)</div>
    <div style="text-align: center; font-weight:bold; margin-bottom:12px; font-size: 10px;">DIRECTIVA N&deg; 0007-2025-EF/54.01 - PMBSO</div>

    <div class="dato">A través del presente documento, se <strong>CERTIFICA Y DEJA CONSTANCIA</strong> que el personal policial y/o civil detallado a continuación ha cumplido satisfactoriamente con el registro de sus datos en el formulario correspondiente y la carga de la documentación requerida en formato digital (PDF), garantizando la integridad de la información declarada para el Proceso de Programación Multianual de Bienes, Servicios y Obras (PMBSO) correspondiente al periodo ' . ANIO_CMN . '.</div>

    <table class="table-datos">
        <tr><td class="lbl">REGIÓN POLICIAL:</td><td>' . htmlspecialchars($row['region_policial']) . '</td></tr>
        <tr><td class="lbl">DIVPOL / DIVOPUS:</td><td>' . htmlspecialchars($row['divpol_divopus']) . '</td></tr>
        <tr><td class="lbl">SUB UNIDAD:</td><td>' . htmlspecialchars($row['sub_unidad_especifica']) . '</td></tr>
        <tr><td class="lbl">GRADO Y NOMBRES:</td><td>' . htmlspecialchars($row['grado']) . ' ' . htmlspecialchars($row['apellidos']) . ' ' . htmlspecialchars($row['nombres']) . '</td></tr>
        <tr><td class="lbl">CIP / DNI:</td><td>' . htmlspecialchars($row['cip']) . ' / ' . htmlspecialchars($row['dni']) . '</td></tr>
        <tr><td class="lbl">FECHA REGISTRO:</td><td>' . $fecha . '</td></tr>
        <tr><td class="lbl">CÓDIGO EMISIÓN:</td><td style="font-family: monospace; font-size: 14px;"><strong>' . $codigo_cargo . '</strong></td></tr>
        <tr><td class="lbl">ESTADO:</td><td><strong style="color:green;">RECIBIDO CONFORME</strong></td></tr>
    </table>

    <div class="codigo-box">
        <strong>TRAZABILIDAD E INTEGRIDAD</strong><br>
        ID Criptográfico: ' . hash('sha256', $codigo_cargo) . '<br>
        Registro desde IP: ' . htmlspecialchars($row['ip_registro']) . '
    </div>

    <div class="firma-wrapper">
        <div class="firma-digital">
            <img src="' . $base64Firma . '" class="img-firma">
            <div class="linea-firma">
                <strong style="font-size: 11px; color: #003666;">FIRMADO DIGITALMENTE POR:</strong><br>
                <strong style="text-transform: uppercase;">OMAR YINO JARA MENDOZA</strong><br>
                <div class="cargo-txt">
                    Administrador del Sistema<br>
                    Oficina de Programación - UE009 - VII DIRTEPOL LIMA
                </div>
            </div>
            <div style="font-size: 7px; color: #999; margin-top: 3px;">Cód. Emisión: ' . $codigo_cargo . '</div>
        </div>
    </div>

    <div class="footer">
        © ' . date('Y') . ' | OFICINA DE PROGRAMACIÓN - UE009 - VII DIRTEPOL LIMA | AV. ESPAÑA 450 - LIMA
    </div>
</body>
</html>
';

$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$dompdf->stream("CARGO_CMN" . ANIO_CMN . "_" . $row['dni'] . ".pdf", ["Attachment" => true]);
?>
