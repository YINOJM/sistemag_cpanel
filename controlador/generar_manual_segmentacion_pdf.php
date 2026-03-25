<?php
// controlador/generar_manual_segmentacion_pdf.php
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

$header_color = "#0088aa"; // Color turquesa/teal oscuro de segmentación

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
        .matrix-table { width: 100%; border-collapse: collapse; margin: 20px 0; font-size: 13px; }
        .matrix-table th, .matrix-table td { border: 1px solid #ddd; padding: 10px; text-align: center; }
        .matrix-table th { background-color: #f5f5f5; }
        .badge { display: inline-block; padding: 3px 8px; border-radius: 4px; font-weight: bold; color: white; display: block; margin: 0 auto; }
        .rutinario { background: #2e7d32; }
        .critico { background: #fbc02d; color: #000; }
        .operacional { background: #00acc1; }
        .estrategico { background: #e53935; }
        .tip-box { background-color: #f1f8e9; border-radius: 8px; padding: 15px; border: 1px solid #c5e1a5; margin: 20px 0; }
        .tip-title { font-weight: bold; color: #33691e; margin-bottom: 5px; font-size: 14px; }
        .footer { position: fixed; bottom: -10px; width: 100%; text-align: center; font-size: 10px; color: #999; border-top: 1px solid #eee; padding-top: 5px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>MANUAL DE USUARIO - MÓDULO DE SEGMENTACIÓN</h1>
        <p style="margin: 0; font-weight: bold; color: #666;">Optimización de Contrataciones - LEY 32069</p>
    </div>

    <div class="section">
        <p>Este módulo permite clasificar estratégicamente los procesos de compra para identificar aquellos que representan un mayor riesgo o impacto financiero.</p>
    </div>

    <div class="section">
        <h2>1. Conceptos del Análisis</h2>
        <p>El sistema analiza cada procedimiento basándose en dos ejes fundamentales:</p>
        <ul>
            <li><strong>La Cuantía:</strong> Se considera "Alta" si supera el 10% del total anual programado (PAC).</li>
            <li><strong>El Riesgo:</strong> Se considera "Alto" si el proceso ha sido desierto, tiene pocos postores o hay escasez en el mercado.</li>
        </ul>
    </div>

    <div class="section">
        <h2>2. Matriz de Resultados</h2>
        <p>La combinación de Cuantía y Riesgo genera cuatro categorías posibles:</p>
        <table class="matrix-table">
            <thead>
                <tr>
                    <th>Cuantía \ Riesgo</th>
                    <th>Bajo</th>
                    <th>Alto</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong>Baja (≤ 10%)</strong></td>
                    <td><span class="badge rutinario">RUTINARIO</span></td>
                    <td><span class="badge critico">CRÍTICO</span></td>
                </tr>
                <tr>
                    <td><strong>Alta (> 10%)</strong></td>
                    <td><span class="badge operacional">OPERACIONAL</span></td>
                    <td><span class="badge estrategico">ESTRATÉGICO</span></td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="section">
        <h2>3. Registro de Procedimientos</h2>
        <p>Para registrar, use el botón <strong>"+ Nueva segmentación"</strong>:</p>
        <ul>
            <li><strong>Pestaña General:</strong> Datos básicos y monto estimado.</li>
            <li><strong>Pestaña Riesgo:</strong> Deberá responder SI/NO a los criterios de mercado.</li>
            <li><strong>Pestaña Ítems:</strong> Si el proceso tiene paquetes, desglose aquí sus montos.</li>
        </ul>
    </div>

    <div class="section">
        <h2>4. Reporte Oficial (Anexo 01)</h2>
        <p>Cada registro tiene un icono de PDF amarillo en la columna "Anexo". Al pulsarlo, el sistema genera el <strong>ANEXO 01</strong>, que es el formato técnico legal requerido para el sustento de la segmentación.</p>
    </div>

    <div class="tip-box">
        <div class="tip-title">⚙️ Recomendación Técnica:</div>
        <p>Verifique siempre que el <strong>Año</strong> seleccionado arriba a la izquierda coincida con el ejercicio fiscal que desea trabajar, ya que las métricas de % PAC varían año tras año.</p>
    </div>

    <div class="footer">
        SIG - Módulo de Segmentación Histórica &copy; ' . date('Y') . '
    </div>
</body>
</html>
';

$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

// Limpiar cualquier salida previa (incluyendo espacios o warnings)
if (ob_get_length()) ob_clean();

$dompdf->stream("MANUAL_SEGMENTACION_SIG_LEY32069.pdf", array("Attachment" => false));
exit;
