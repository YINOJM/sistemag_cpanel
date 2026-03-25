<?php
// controlador/generar_manual_unidades_pdf.php
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

$header_color = "#006db3"; // Azul institucional usado en el módulo

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
        li { margin-bottom: 8px; }
        .info-card { background-color: #f8fafc; border-radius: 12px; padding: 15px; border: 1px solid #e2e8f0; margin: 20px 0; }
        .card-title { font-weight: bold; color: ' . $header_color . '; margin-bottom: 8px; font-size: 14px; text-transform: uppercase; }
        .step { font-weight: bold; color: ' . $header_color . '; }
        .footer { position: fixed; bottom: -10px; width: 100%; text-align: center; font-size: 10px; color: #999; border-top: 1px solid #eee; padding-top: 5px; }
        .hierarchy { margin: 15px 0; padding-left: 20px; border-left: 2px dashed #cbd5e1; }
    </style>
</head>
<body>
    <div class="header">
        <h1>GESTIÓN DE UNIDADES POLICIALES (SIG)</h1>
        <p style="margin: 0; font-weight: bold; color: #666;">Manual de Administración de la Estructura Organizacional</p>
    </div>

    <div class="section">
        <p>Este módulo es el corazón geográfico del sistema. Aquí se define la jerarquía que permite que otros módulos (como Inventario o Gestión Documental) funcionen correctamente por unidad.</p>
    </div>

    <div class="section">
        <h2>1. Estructura de Datos</h2>
        <p>El sistema maneja una jerarquía de 3 niveles descendentes:</p>
        <div class="hierarchy">
            <p>🌍 <strong>Región Policial:</strong> Comando de nivel departamental o macro-regional.</p>
            <p>🏢 <strong>División Policial:</strong> Comando subordinado (Ej: DIVOPUS, DIVPOL).</p>
            <p>🛡️ <strong>Unidad / Sub-Unidad:</strong> La dependencia operativa final (Comisarías, SEINCRIS, Oficinas).</p>
        </div>
    </div>

    <div class="section">
        <h2>2. Pasos para Registrar una Nueva Unidad</h2>
        <div class="info-card">
            <p><span class="step">Paso 1:</span> Seleccione la pestaña <strong>"Unidades / Sub-Unidades"</strong>.</p>
            <p><span class="step">Paso 2:</span> Pulse el botón <strong>"+ Nueva Unidad"</strong>.</p>
            <p><span class="step">Paso 3:</span> Seleccione la Región y División (estos campos son obligatorios).</p>
            <p><span class="step">Paso 4:</span> Ingrese el Nombre Oficial y asigne su Distrito geográfico.</p>
            <p><span class="step">Paso 5:</span> Defina el "Tipo" (Comisaría A, B, C, Jefatura, etc.) para fines estadísticos.</p>
        </div>
    </div>

    <div class="section">
        <h2>3. Herramientas de Control</h2>
        <ul>
            <li><strong>Dashboard en tiempo real:</strong> Las tarjetas superiores muestran cuántas dependencias hay registradas de cada tipo actualmente.</li>
            <li><strong>Vista Jerárquica:</strong> Use esta tabla para auditar que cada unidad esté bien vinculada a su división y región.</li>
            <li><strong>Importación Masiva:</strong> Para actualizaciones de toda la región, use la carga por Excel para ahorrar tiempo.</li>
        </ul>
    </div>

    <div class="info-card" style="border-left: 4px solid #f59e0b; background-color: #fef3c7;">
        <div class="card-title" style="color: #b45309;">💡 Nota Importante:</div>
        <p>Si cambia el nombre de una unidad, este cambio se verá reflejado automáticamente en todos los inventarios y documentos registrados bajo esa dependencia.</p>
    </div>

    <div class="footer">
        SIG - Módulo de Unidades Policiales &copy; ' . date('Y') . '
    </div>
</body>
</html>
';

$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

// Limpiar cualquier salida previa (incluyendo espacios o warnings)
if (ob_get_length()) ob_clean();

$dompdf->stream("MANUAL_UNIDADES_POLICIALES_SIG.pdf", array("Attachment" => false));
exit;
