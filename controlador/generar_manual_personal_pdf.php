<?php
// controlador/generar_manual_personal_pdf.php
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

$header_color = "#006db3"; // Azul institucional

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
        .badge-warning { background-color: #fef9c3; color: #854d0e; padding: 2px 6px; border-radius: 4px; font-size: 11px; border: 1px solid #fde047; }
    </style>
</head>
<body>
    <div class="header">
        <h1>GESTIÓN DE RECURSOS HUMANOS (SIG)</h1>
        <p style="margin: 0; font-weight: bold; color: #666;">Manual de Usuario y Procedimientos del Padrón General</p>
    </div>

    <div class="section">
        <p>Este módulo centraliza la administración del Capital Humano Policial. Permite el alta, baja, edición y pase de revista mensual de todo el personal, vinculando cada efectivo a su unidad funcional exacta.</p>
    </div>

    <div class="section">
        <h2>1. Estructura del Registro</h2>
        <p>La ficha de cada efectivo está diseñada para capturar la información operativa esencial en 3 bloques:</p>
        <ul>
            <li><strong>Datos Personales:</strong> Grado, CIP, DNI y Nombres Completos (Validados para evitar duplicidad).</li>
            <li><strong>Asignación de Unidad:</strong> Vinculación jerárquica (Región > División > Sub-Unidad) conectada al módulo de Unidades.</li>
            <li><strong>Información Laboral:</strong> Cargo, Situación Especial y Modalidad de Horario.</li>
        </ul>
    </div>

    <div class="section">
        <h2>2. Funcionalidades Clave</h2>
        
        <h3>A. Registro Flexible e Inteligente</h3>
        <p>El sistema cuenta con campos predictivos para "Cargo", "Situación" y "Función".</p>
        <div class="hierarchy">
            <p>💡 <strong>Auto-aprendizaje:</strong> Puede seleccionar una opción estándar de la lista desplegable o <u>escribir una nueva</u> manualmente. Si escribe una nueva, el sistema la "aprende" y la sugerirá en futuros registros.</p>
            <p>💡 <strong>Alertas Visuales:</strong> Si deja un cargo sin definir, el sistema mostrará una etiqueta <span class="badge-warning">POR DEFINIR</span> en el listado para recordarle completarlo luego.</p>
        </div>

        <h3>B. Carga Masiva (Importación)</h3>
        <p>Para no registrar uno por uno, use la opción <strong>"Importación"</strong>:</p>
        <div class="info-card">
            <p><span class="step">Paso 1:</span> Descargue la Plantilla Excel oficial.</p>
            <p><span class="step">Paso 2:</span> Llene los datos copiando de sus fuentes. (El sistema reconoce las Unidades incluso si omite tildes o mayúsculas).</p>
            <p><span class="step">Paso 3:</span> Suba el archivo y el sistema procesará todo el lote en segundos.</p>
        </div>
    </div>

    <div class="section">
        <h2>3. Gestión Diaria</h2>
        <ul>
            <li><strong>Edición de Datos:</strong> Al editar un efectivo, el sistema recupera automáticamente toda su jerarquía de unidad (Región y División) basándose en su comisaría actual.</li>
            <li><strong>Control de Revista:</strong> Utilice los botones de acción rápida para marcar la presencia menusal.</li>
            <li><strong>Búsqueda Global:</strong> Use la barra superior para ubicar rápidamente a cualquier efectivo por CIP o Apellido.</li>
        </ul>
    </div>

    <div class="info-card" style="border-left: 4px solid #0ea5e9; background-color: #e0f2fe;">
        <div class="card-title" style="color: #0369a1;">ℹ️ Integridad de Datos:</div>
        <p>La asignación de unidades está "conectada en vivo" a la Base de Datos Matriz. Esto asegura que no se registren efectivos en unidades fantasma o inexistentes.</p>
    </div>

    <div class="footer">
        SIG - Recursos Humanos &copy; ' . date('Y') . '
    </div>
</body>
</html>
';

$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

// Limpiar cualquier salida previa (incluyendo espacios o warnings)
if (ob_get_length()) ob_clean();

$dompdf->stream("MANUAL_RECURSOS_HUMANOS_SIG.pdf", array("Attachment" => false));
exit;
