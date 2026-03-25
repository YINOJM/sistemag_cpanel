<?php
// controlador/generar_manual_documento_pdf.php
require_once __DIR__ . '/../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

// 1. CARGAR CONEXIÓN Y ENTORNO (Centralizado para Sesiones en sesiones_temp)
require_once __DIR__ . "/../modelo/conexion.php";

if (empty($_SESSION['id'])) {
    die("Acceso denegado.");
}

$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);

$dompdf = new Dompdf($options);

$header_color = "#00779e";

$html = '
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.5; color: #333; margin: 30px; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 3px solid ' . $header_color . '; padding-bottom: 10px; }
        h1 { color: ' . $header_color . '; font-size: 22px; margin-bottom: 5px; }
        h2 { color: ' . $header_color . '; border-left: 5px solid ' . $header_color . '; padding-left: 10px; margin-top: 25px; font-size: 18px; }
        h3 { color: #444; margin-top: 15px; font-size: 15px; }
        p { margin-bottom: 10px; font-size: 13px; }
        ul { margin-bottom: 15px; font-size: 13px; }
        li { margin-bottom: 5px; }
        .tip-box { background-color: #f1f8e9; border-radius: 8px; padding: 15px; border: 1px solid #c5e1a5; margin: 20px 0; }
        .tip-title { font-weight: bold; color: #33691e; margin-bottom: 5px; font-size: 14px; }
        .field-list { background-color: #f8f9fa; padding: 10px; border-radius: 5px; border: 1px solid #eee; }
        .footer { position: fixed; bottom: -10px; width: 100%; text-align: center; font-size: 10px; color: #999; border-top: 1px solid #eee; padding-top: 5px; }
        .step { font-weight: bold; color: ' . $header_color . '; }
    </style>
</head>
<body>
    <div class="header">
        <h1>GUÍA DE USUARIO - GESTIÓN DOCUMENTAL</h1>
        <p style="margin: 0; font-weight: bold; color: #666;">SisDocumentos - SIG</p>
    </div>

    <div class="section">
        <p>Esta guía le enseñará a utilizar el sistema para registrar, buscar y organizar la documentación emitida (Oficios, Informes, etc.) en su unidad policial.</p>
    </div>

    <div class="section">
        <h2>Paso 1: Entrar a Gestión Documental</h2>
        <p>Haga clic en el menú lateral en la opción <strong>"Gestión Documental"</strong>. Verá la bandeja principal con todos los documentos del año actual.</p>
    </div>

    <div class="section">
        <h2>Paso 2: Registrar un Documento Nuevo</h2>
        <p>Pulse el botón verde <strong style="color: #10b981;">+ Nuevo Documento</strong>. Complete los datos:</p>
        
        <div class="field-list">
            <ul>
                <li><strong>Tipo:</strong> Elija si es Oficio, Informe, Memorandum, etc.</li>
                <li><strong>Número:</strong> El sistema le dará el número que sigue automáticamente.</li>
                <li><strong>HT / Ref:</strong> Ingrese el número de Hoja de Trámite si lo tiene.</li>
                <li><strong>Asunto:</strong> Explique brevemente de qué trata el documento.</li>
                <li><strong>Destino:</strong> A qué oficina o unidad se envía.</li>
                <li><strong>Formulado por:</strong> Quién hizo el documento.</li>
            </ul>
        </div>
    </div>

    <div class="section">
        <h2>Paso 3: Búsqueda y Organización</h2>
        <p>¿Busca un documento antiguo o por asunto?</p>
        <ul>
            <li><strong>Pestañas Superiores:</strong> Haga clic en "OFICIOS" o "INFORMES" para filtrar rápidamente.</li>
            <li><strong>Cuadro de Búsqueda:</strong> Escriba parte del asunto o el nombre de quien lo formuló y el sistema lo encontrará en tiempo real.</li>
            <li><strong>Filtro de Año:</strong> Arriba a la derecha puede cambiar el año para ver documentos de gestiones pasadas.</li>
        </ul>
    </div>

    <div class="section">
        <h2>Paso 4: Reportes</h2>
        <p>Si necesita imprimir el cuaderno de registro, use el botón <strong>Reportes</strong> y elija <strong>Reporte PDF</strong>. Se generará una lista formal con toda la información.</p>
    </div>

    <div class="tip-box">
        <div class="tip-title">💡 Recomendaciones de Oro:</div>
        <p>1. Mantenga los asuntos cortos pero claros para facilitar las búsquedas.<br>
           2. Si se equivoca en un dato, use el botón de <strong>Editar</strong> (Lápiz azul) a la derecha de cada fila.<br>
           3. Use el botón de la hojita al lado del número de documento para copiar el número rápidamente.</p>
    </div>

    <div class="footer">
        Manual de Gestión Documental SIG &copy; ' . date('Y') . '
    </div>
</body>
</html>
';

$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

// Limpiar cualquier salida previa (incluyendo espacios o warnings)
if (ob_get_length()) ob_clean();

$dompdf->stream("MANUAL_GESTION_DOCUMENTAL_SIG.pdf", array("Attachment" => false));
exit;
