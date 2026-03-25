<?php
// controlador/cmn_cargo_fase1.php
// Genera la constancia de cargo del Anexo N°01 - Fase de Identificación (por ID de anexo)
require_once __DIR__ . '/../modelo/conexion.php';

if (empty($_SESSION['id'])) {
    die("Acceso denegado.");
}

require_once __DIR__ . '/../vendor/autoload.php';

error_reporting(0);
ini_set('display_errors', 0);

use Dompdf\Dompdf;
use Dompdf\Options;

$id = (int)($_GET['id'] ?? 0);

if (!$id) {
    die("ID de anexo no proporcionado.");
}

// Obtener datos del anexo fase 1
$stmt = $conexion->prepare(
    "SELECT a.*, r.region_policial, r.divpol_divopus
     FROM cmn_anexos_fase1 a
     LEFT JOIN cmn_responsables r ON r.dni = a.dni_responsable
     WHERE a.id = ?
     LIMIT 1"
);
$stmt->bind_param("i", $id);
$stmt->execute();
$res  = $stmt->get_result();
$row  = $res->fetch_assoc();

if (!$row) {
    die("Registro de Anexo no encontrado (ID: $id).");
}

$fecha         = date('d/m/Y H:i:s', strtotime($row['fecha_subida']));
$anio_cmn      = defined('ANIO_CMN') ? ANIO_CMN : 2026;
$codigo_cargo  = 'CMN-FA1-' . $anio_cmn . '-' . str_pad($row['id'], 5, '0', STR_PAD_LEFT)
               . '-' . strtoupper(substr(hash('sha256', $row['dni_responsable'] . $row['fecha_subida']), 0, 8));

// Imágenes base64
function getBase64Img($path) {
    if (file_exists($path)) {
        $type = pathinfo($path, PATHINFO_EXTENSION);
        return 'data:image/' . $type . ';base64,' . base64_encode(file_get_contents($path));
    }
    return '';
}

$base64Escudo = getBase64Img('../public/images/escudo.png');
$base64Logo   = getBase64Img('../public/images/logo_regpol.png');
$base64Firma  = getBase64Img('../public/images/firma-pro.png');

// QR
$urlValidacion = "https://sites.google.com/view/sistema-tramite";
$qrData        = @file_get_contents("https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=" . urlencode($urlValidacion));
$base64Qr      = $qrData ? 'data:image/png;base64,' . base64_encode($qrData) : '';

$html = '<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        body        { font-family: "Helvetica", "Arial", sans-serif; font-size: 13px; text-align: justify; margin: 0; padding: 0; }
        .titulo     { text-align: center; font-size: 17px; font-weight: bold; margin: 12px 0 6px; text-decoration: underline; color: #000; }
        .subtitulo  { text-align: center; font-weight: bold; font-size: 10px; margin-bottom: 14px; }
        .dato       { margin-bottom: 8px; font-size: 12px; }
        .table-datos{ width: 100%; border-collapse: collapse; margin-top: 10px; }
        .table-datos td { padding: 5px 8px; border: 1px solid #ccc; font-size: 12px; }
        .table-datos td.lbl { font-weight: bold; width: 35%; background-color: #f5f5f5; }
        .watermark  { position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); width: 380px; opacity: 0.07; z-index: -1; }
        .codigo-box { margin-top: 12px; padding: 8px; border: 1px dashed #666; font-size: 9px; text-align: center; background: #fcfcfc; }
        .firma-wrapper  { margin-top: 35px; text-align: center; }
        .firma-digital  { display: inline-block; border: 1px solid #003666; padding: 10px 20px; background: #f8fbff; border-radius: 6px; width: 270px; text-align: center; position: relative; }
        .img-firma      { width: 110px; height: auto; position: absolute; top: 5px; left: 50%; margin-left: -55px; }
        .linea-firma    { margin-top: 40px; border-top: 1px solid #777; padding-top: 5px; }
        .cargo-txt      { font-size: 10px; color: #444; line-height: 1.3; }
        .footer         { position: fixed; bottom: -35px; left: 0; width: 100%; text-align: center; font-size: 8px; color: #666; border-top: 0.5px solid #eee; padding-top: 5px; }
        .badge-recepcionado { display: inline-block; background: #dcfce7; color: #15803d; padding: 2px 10px; border-radius: 4px; font-size: 11px; font-weight: bold; border: 1px solid #bbf7d0; }
    </style>
</head>
<body>
    <img src="' . $base64Escudo . '" class="watermark">
    <table style="width:100%;border:none;margin-bottom:10px;">
        <tr>
            <td style="width:15%;text-align:left;"><img src="' . $base64Logo . '" style="width:52px;"></td>
            <td style="width:70%;text-align:center;vertical-align:middle;">
                <div style="font-weight:bold;font-size:11px;line-height:1.4;">
                    POLICÍA NACIONAL DEL PERÚ<br>
                    REGIÓN POLICIAL LIMA<br>
                    UNIDAD DE ADMINISTRACIÓN - UE009 - VII DIRTEPOL LIMA
                </div>
            </td>
            <td style="width:15%;text-align:right;">
                <div style="text-align:center;">
                    <img src="' . $base64Qr . '" style="width:60px;height:60px;border:1px solid #eee;padding:2px;">
                    <div style="font-size:6px;color:#666;margin-top:2px;">VALIDAR AQUÍ</div>
                </div>
            </td>
        </tr>
    </table>
    <div style="font-size:10px;font-weight:bold;border-bottom:2px solid #003666;padding-bottom:4px;margin-bottom:10px;">
        Fecha y Hora de Recepción: <span style="font-weight:normal;">' . $fecha . '</span>
    </div>

    <div class="titulo">CONSTANCIA DE RECEPCIÓN - ANEXO N° 01</div>
    <div class="subtitulo">FASE DE IDENTIFICACIÓN - CMN ' . $anio_cmn . '<br>Directiva N° 0007-2025-EF/54.01 - PMBSO</div>

    <div class="dato">Se <strong>CERTIFICA Y DEJA CONSTANCIA</strong> que el personal policial identificado a continuación ha cumplido con la remisión del <strong>Anexo N° 01 del Cuadro Multianual de Necesidades - Fase de Identificación</strong>, debidamente visado por el responsable del Área Usuaria, correspondiente al periodo de programación ' . $anio_cmn . '.</div>

    <table class="table-datos">
        <tr><td class="lbl">REGIÓN POLICIAL:</td><td>' . htmlspecialchars($row['region_policial'] ?? $row['region_policial']) . '</td></tr>
        <tr><td class="lbl">DIVOPUS / FRENTE:</td><td>' . htmlspecialchars($row['divopus']) . '</td></tr>
        <tr><td class="lbl">SUB UNIDAD:</td><td>' . htmlspecialchars($row['sub_unidad']) . '</td></tr>
        <tr><td class="lbl">GRADO Y APELLIDOS/NOMBRES:</td><td>' . htmlspecialchars($row['grado']) . ' ' . htmlspecialchars($row['nombres_completos']) . '</td></tr>
        <tr><td class="lbl">CIP / DNI:</td><td>' . htmlspecialchars($row['cip']) . ' / ' . htmlspecialchars($row['dni_responsable']) . '</td></tr>
        <tr><td class="lbl">FECHA DE REMISIÓN:</td><td>' . $fecha . '</td></tr>
        <tr><td class="lbl">ESTADO:</td><td><span class="badge-recepcionado">RECIBIDO CONFORME</span></td></tr>
        <tr><td class="lbl">CÓDIGO DE CARGO:</td><td style="font-family:monospace;font-size:13px;"><strong>' . $codigo_cargo . '</strong></td></tr>
    </table>

    <div class="codigo-box">
        <strong>TRAZABILIDAD E INTEGRIDAD DEL DOCUMENTO</strong><br>
        Hash SHA-256: ' . hash('sha256', $codigo_cargo) . '<br>
        IP de Remisión: ' . htmlspecialchars($row['ip_cliente'] ?? '---') . '
    </div>

    <div class="firma-wrapper">
        <div class="firma-digital">
            <img src="' . $base64Firma . '" class="img-firma">
            <div class="linea-firma">
                <strong style="font-size:10px;color:#003666;">FIRMADO DIGITALMENTE POR:</strong><br>
                <strong style="text-transform:uppercase;">OMAR YINO JARA MENDOZA</strong><br>
                <div class="cargo-txt">Administrador del Sistema<br>Oficina de Programación - UE009 - VII DIRTEPOL LIMA</div>
            </div>
            <div style="font-size:7px;color:#999;margin-top:3px;">Cód.: ' . $codigo_cargo . '</div>
        </div>
    </div>

    <div class="footer">
        © ' . date('Y') . ' | OFICINA DE PROGRAMACIÓN - UE009 - VII DIRTEPOL LIMA | AV. ESPAÑA 450 - LIMA
    </div>
</body>
</html>';

$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$dompdf->stream("CARGO_FASE1_" . $anio_cmn . "_" . $row['dni_responsable'] . ".pdf", ["Attachment" => false]);
