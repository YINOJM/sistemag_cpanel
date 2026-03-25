<?php
// controlador/ren_generar_notificacion_grupal_pdf.php
ob_start();
require_once __DIR__ . '/../modelo/conexion.php';
require_once __DIR__ . '/../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

// 1. Obtener Grupo
$grupo = $_GET['grupo'] ?? '';
if (empty($grupo)) die("Debe proporcionar un nombre de grupo o archivo.");

// 2. Obtener registros de la rendición para ese grupo que estén PENDIENTES
if ($grupo === 'LOTE INICIAL / OTROS') {
    $stmt = $conexion->prepare("SELECT * FROM ren_rendiciones WHERE (grupo_importacion IS NULL OR grupo_importacion = '') AND estado_rendicion = 'Pendiente' ORDER BY apellidos_nombres ASC");
} else {
    $stmt = $conexion->prepare("SELECT * FROM ren_rendiciones WHERE grupo_importacion = ? AND estado_rendicion = 'Pendiente' ORDER BY apellidos_nombres ASC");
    $stmt->bind_param("s", $grupo);
}

if (!$stmt) die("Error en la consulta: " . $conexion->error);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows === 0) die("No hay rendiciones PENDIENTES para este grupo.");

// 3. Funciones auxiliares (Copiadas de la versión individual para evitar dependencias externas)
function formatDateNotify($date) {
    if (!$date) return '';
    $meses = [
        '01' => 'ENE', '02' => 'FEB', '03' => 'MAR', '04' => 'ABR', 
        '05' => 'MAY', '06' => 'JUN', '07' => 'JUL', '08' => 'AGO', 
        '09' => 'SET', '10' => 'OCT', '11' => 'NOV', '12' => 'DIC'
    ];
    $d = date_create($date);
    return date_format($d, 'd') . $meses[date_format($d, 'm')] . date_format($d, 'Y');
}

function moneyToText($number) {
    $enteros = floor($number);
    $centavos = round(($number - $enteros) * 100);
    $centavosTexto = str_pad($centavos, 2, '0', STR_PAD_LEFT);
    return mb_strtoupper(convertirNumeroALetras($enteros)) . " CON " . $centavosTexto . "/100 SOLES";
}

function convertirNumeroALetras($n) {
    if ($n == 0) return "cero";
    $unidades = ["", "un", "dos", "tres", "cuatro", "cinco", "seis", "siete", "ocho", "nueve"];
    $decenas = ["", "diez", "veinte", "treinta", "cuarenta", "cincuenta", "sesenta", "setenta", "ochenta", "noventa"];
    $especiales = [
        11 => "once", 12 => "doce", 13 => "trece", 14 => "catorce", 15 => "quince", 
        16 => "dieciséis", 17 => "diecisiete", 18 => "dieciocho", 19 => "diecinueve", 
        21 => "veintiuno", 22 => "veintidós", 23 => "veintitrés", 24 => "veinticuatro", 
        25 => "veinticinco", 26 => "veintiséis", 27 => "veintisiete", 28 => "veintiocho", 29 => "veintinueve"
    ];
    $centenas = ["", "cien", "doscientos", "trescientos", "cuatrocientos", "quinientos", "seiscientos", "setecientos", "ochocientos", "novecientos"];
    if ($n < 0) return "menos " . convertirNumeroALetras(abs($n));
    if ($n < 10) return $unidades[$n];
    if (isset($especiales[$n])) return $especiales[$n];
    if ($n < 100) {
        $u = $n % 10; $d = floor($n / 10);
        return $decenas[$d] . ($u > 0 ? " y " . $unidades[$u] : "");
    }
    if ($n < 1000) {
        $d = $n % 100; $c = floor($n / 100);
        if ($n == 100) return "cien";
        return ($c == 1 ? "ciento" : $centenas[$c]) . ($d > 0 ? " " . convertirNumeroALetras($d) : "");
    }
    if ($n < 1000000) {
        $c = $n % 1000; $m = floor($n / 1000);
        if ($m == 1) $m_text = "mil";
        else if ($m == 21) $m_text = "veintiún mil";
        else $m_text = convertirNumeroALetras($m) . " mil";
        return $m_text . ($c > 0 ? " " . convertirNumeroALetras($c) : "");
    }
    if ($n < 1000000000) {
        $m = floor($n / 1000000); $resto = $n % 1000000;
        $m_text = ($m == 1) ? "un millón" : convertirNumeroALetras($m) . " millones";
        return $m_text . ($resto > 0 ? " " . convertirNumeroALetras($resto) : "");
    }
    return (string)$n;
}

// 4. Conversión de Imágenes a Base64
$pathEscudo = __DIR__ . '/../public/images/escudo.png';
$base64Escudo = '';
if (file_exists($pathEscudo)) {
    $dataEscudo = file_get_contents($pathEscudo);
    $base64Escudo = 'data:image/' . pathinfo($pathEscudo, PATHINFO_EXTENSION) . ';base64,' . base64_encode($dataEscudo);
}

$pathRegpol = __DIR__ . '/../public/images/logo_regpol.png';
$base64Regpol = '';
if (file_exists($pathRegpol)) {
    $dataRegpol = file_get_contents($pathRegpol);
    $base64Regpol = 'data:image/' . pathinfo($pathRegpol, PATHINFO_EXTENSION) . ';base64,' . base64_encode($dataRegpol);
}

// 5. Cargar Plantilla desde BD (Fuera del bucle)
$plantilla_raw = $conexion->query("SELECT valor FROM ren_configuracion WHERE clave = 'plantilla_notificacion'")->fetch_assoc()['valor'] ?? '';
$vals = json_decode($plantilla_raw, true) ?: [];

// Función para reemplazar etiquetas
function applyTemplate($text, $data) {
    foreach ($data as $key => $val) {
        $text = str_replace('{{' . $key . '}}', $val, $text);
    }
    return $text;
}

$header_text = nl2br(htmlspecialchars($vals['header'] ?? ''));

// Iniciar HTML con Estilos
$html = '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        @page { margin: 0.6cm 1.5cm 0.5cm 2.5cm; }
        body { font-family: "Helvetica", Arial, sans-serif; font-size: 10pt; line-height: 1.4; color: #1a1a1a; }
        .watermark { position: fixed; top: 25%; left: 15%; width: 70%; opacity: 0.05; z-index: -1000; }
        
        .header-table { width: 100%; border-bottom: 1px solid #000; margin-bottom: 5px; }
        .header-logo { width: 50px; }
        .header-text { text-align: center; text-transform: uppercase; font-size: 8pt; font-weight: bold; }
        
        .titulo { text-align: center; font-weight: bold; text-decoration: underline; font-size: 14pt; margin: 10px 0; letter-spacing: 1px; }
        .negrita { font-weight: bold; }
        .justify { text-align: justify; }
        
        .info-table { width: 100%; margin-bottom: 5px; }
        .info-table td { padding: 1px 0; }
        
        .content { margin-top: 5px; }
        ol { padding-left: 20px; margin-top: 5px; margin-bottom: 5px; }
        li { margin-bottom: 8px; text-align: justify; }
        
        /* Firmas Estilo Sello (Alineación perfecta) */
        .signature-table { width: 100%; border-collapse: collapse; margin-top: 35px; }
        .signature-table td { width: 50%; padding: 0 10px; text-align: center; vertical-align: bottom; }
        .label-sig { font-weight: bold; font-size: 10pt; text-decoration: underline; margin-bottom: 5px; display: block; }
        .sig-space { height: 90px; }
        .sig-line { border-top: 1px dotted #000; width: 90%; margin: 0 auto 5px auto; }
        .sig-name { font-weight: bold; font-size: 9.5pt; color: #004a8e; margin-bottom: 2px; }
        .sig-doc { font-size: 8.5pt; color: #333; }
        .page-break { page-break-after: always; }
    </style>
</head>
<body>';

// 6. Generar Notificaciones por cada registro
while ($row = $res->fetch_assoc()) {
    $grado_txt = trim($row['grado'] ?? '');
    $nombres_txt = trim($row['apellidos_nombres'] ?? '');
    // Eliminar "PNP" y "PNP." de ambos
    $grado_txt = trim(str_ireplace(["PNP.", "PNP"], "", $grado_txt));
    $nombres_txt = trim(str_ireplace(["PNP.", "PNP"], "", $nombres_txt));
    // Unir y limpiar puntos dobles o espacios
    $personal = trim($grado_txt . " " . $nombres_txt);
    $personal = str_replace(["..", ". ."], ".", $personal);
    $personal = preg_replace('/\s+/', ' ', $personal);

    $dni_pers = $row['dni'];
    $cip_pers = $row['cip'];
    $anio_fiscal = date('Y', strtotime($row['fecha_inicio']));
    $referencia = "REGISTRO SIAF AÑO FISCAL " . $anio_fiscal . " N.º " . ($row['siaf_expediente'] ?: '----');
    $lugar = $row['lugar_comision'];
    $fechas = formatDateNotify($row['fecha_inicio']) . " AL " . formatDateNotify($row['fecha_retorno']);
    $monto_letras = moneyToText($row['total_depositado']);
    $monto_num = number_format($row['total_depositado'], 2);

    $data_map = [
        'ANIO_FISCAL' => $anio_fiscal,
        'PERSONAL' => $personal,
        'REFERENCIA' => $referencia,
        'LUGAR' => $lugar,
        'FECHAS' => $fechas,
        'MONTO_LETRAS' => $monto_letras,
        'MONTO_NUM' => $monto_num,
        'SIAF' => ($row['siaf_expediente'] ?: '---')
    ];

    $html .= '
    <div class="page-break">
        <img src="' . $base64Escudo . '" class="watermark">
        <table class="header-table">
            <tr>
                <td class="header-logo"><img src="' . $base64Escudo . '" style="width:45px;"></td>
                <td class="header-text">' . $header_text . '</td>
                <td class="header-logo" style="text-align:right;"><img src="' . $base64Regpol . '" style="width:55px;"></td>
            </tr>
        </table>

        <div class="titulo">NOTIFICACIÓN</div>

        <table class="info-table">
            <tr>
                <td style="width: 120px;" class="negrita text-upper">SEÑOR</td>
                <td style="width: 15px;">:</td>
                <td class="negrita">' . $personal . '</td>
            </tr>
            <tr>
                <td class="negrita">REFERENCIA</td>
                <td>:</td>
                <td class="negrita">' . $referencia . '</td>
            </tr>
        </table>

        <div class="justify content">' . applyTemplate($vals['intro'] ?? '', $data_map) . '</div>

        <ol>
            <li>' . applyTemplate($vals['item1'] ?? '', $data_map) . '</li>
            <li><i>' . applyTemplate($vals['item2'] ?? '', $data_map) . '</i></li>
            <li>' . applyTemplate($vals['item3'] ?? '', $data_map) . '</li>
        </ol>

        <div class="justify">' . applyTemplate($vals['outro'] ?? '', $data_map) . '</div>

        <div style="text-align: right; margin-top: 8px; margin-bottom: 5px; padding-right: 50px; font-style: italic;">
            Lima, ' . date('d') . ' de ' . strtr(date('F'), ['January'=>'enero', 'February'=>'febrero', 'March'=>'marzo', 'April'=>'abril', 'May'=>'mayo', 'June'=>'junio', 'July'=>'julio', 'August'=>'agosto', 'September'=>'septiembre', 'October'=>'octubre', 'November'=>'noviembre', 'December'=>'diciembre']) . ' de ' . date('Y') . '
        </div>

        <!-- Sección de Firmas Estilo Sello -->
        <table class="signature-table">
            <!-- Fila de Etiquetas -->
            <tr>
                <td><span class="label-sig">FIRMA DEL ENTERADO</span></td>
                <td><span class="label-sig">NOTIFICANTE</span></td>
            </tr>
            <!-- Fila de Espacio para firma -->
            <tr>
                <td class="sig-space"></td>
                <td class="sig-space"></td>
            </tr>
            <!-- Fila de Línea de firma -->
            <tr>
                <td><div class="sig-line"></div></td>
                <td><div class="sig-line"></div></td>
            </tr>
            <!-- Fila de Datos del Enterado / Sello -->
            <tr>
                <td>
                    <div class="sig-name">' . mb_strtoupper($personal) . '</div>
                    <div class="sig-doc">DNI: ' . $dni_pers . ' | CIP: ' . $cip_pers . '</div>
                    <div style="font-size: 7.5pt; margin-top: 5px; color: #666;">
                        Fecha: ...... / ...... / ' . date('Y') . ' | Hora: ...... : ......
                    </div>
                </td>
                <td style="vertical-align: top;">
                    <div style="font-size: 7.5pt; font-weight: bold; color: #666; margin-top: 5px;"></div>
                </td>
            </tr>
        </table>
    </div>';
}

$html .= '</body></html>';

// 7. Generar el PDF consolidado
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

ob_end_clean();
$dompdf->stream("Notificaciones_Grupo_".$grupo.".pdf", ["Attachment" => false]);
