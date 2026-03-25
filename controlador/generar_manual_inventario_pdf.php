<?php
// controlador/generar_manual_inventario_pdf.php
require_once __DIR__ . '/../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

// 1. CARGAR CONEXIÓN Y ENTORNO (Centralizado para Sesiones en sesiones_temp)
require_once __DIR__ . "/../modelo/conexion.php";

if (empty($_SESSION['id'])) {
    die("Acceso denegado.");
}

$rol = $_SESSION['rol'] ?? 'Usuario';
$esAdmin = ($rol === 'Super Administrador' || $rol === 'Administrador');

$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);

$dompdf = new Dompdf($options);

$header_color = "#00779e";

if ($esAdmin) {
    // --- CONTENIDO PARA ADMINISTRADORES ---
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
            .admin-box { background-color: #fff9c4; border-radius: 8px; padding: 15px; border: 1px solid #fbc02d; margin: 20px 0; }
            .admin-title { font-weight: bold; color: #f57f17; margin-bottom: 5px; font-size: 14px; }
            .footer { position: fixed; bottom: -10px; width: 100%; text-align: center; font-size: 10px; color: #999; border-top: 1px solid #eee; padding-top: 5px; }
        </style>
    </head>
    <body>
        <div class="header">
            <h1>MANUAL DE GESTIÓN AVANZADA - INVENTARIO</h1>
            <p style="margin: 0; font-weight: bold; color: #666;">Manual Especializado para Administradores / Super Admins</p>
        </div>

        <div class="section">
            <p>Como administrador, usted tiene herramientas globales para supervisar el patrimonio de múltiples unidades. Esta guía detalla las funciones de control total.</p>
        </div>

        <div class="section">
            <h2>1. Filtros de Jerarquía Policial (Consolidación)</h2>
            <p>A diferencia de un usuario común, usted puede visualizar la data de cualquier unidad usando los buscadores superiores:</p>
            <ul>
                <li><strong>Macro Región / Región:</strong> Inicie la búsqueda aquí para filtrar todo el departamento o zona.</li>
                <li><strong>División y Sub-Unidad:</strong> Una vez seleccionada la región, estos campos se activarán para permitirle llegar hasta una comisaría u oficina específica.</li>
                <li><strong>Botón Limpiar:</strong> Restablece todos los filtros para volver a ver el "Consolidado General" del sistema.</li>
            </ul>
        </div>

        <div class="section">
            <h2>2. Gestión de Reportes Masivos (Anexo 07)</h2>
            <p>Usted puede generar reportes oficiales filtrados por unidad o consolidados:</p>
            <ul>
                <li><strong>Reporte Consolidado:</strong> Si no selecciona ninguna unidad, el PDF incluirá TODOS los bienes del sistema del año seleccionado.</li>
                <li><strong>Reporte por Unidad:</strong> Si filtra una Sub-Unidad específica, el Anexo 07 se generará únicamente con la información de dicha unidad, incluyendo los nombres de su jerarquía correspondiente.</li>
            </ul>
        </div>

        <div class="section">
            <h2>3. Importación Masiva de Datos (Excel)</h2>
            <p>Para cargar grandes volúmenes de información patrimonial:</p>
            <ol>
                <li>Haga clic en <strong>"Importar Excel"</strong>.</li>
                <li>Descargue la <strong>"Plantilla de Ejemplo"</strong>. Es CRÍTICO mantener el orden de las columnas.</li>
                <li>El sistema validará automáticamente los Códigos y Series para evitar duplicados.</li>
            </ol>
        </div>

        <div class="admin-box">
            <div class="admin-title">🔒 Seguridad y Auditoría:</div>
            <p>Todas las acciones de edición y eliminación realizadas por los administradores quedan registradas en la Bitácora del sistema. Asegúrese de que toda modificación manual esté debidamente sustentada en documentos físicos.</p>
        </div>

        <div class="footer">
            SIG - Manual de Administración de Inventario &copy; ' . date('Y') . '
        </div>
    </body>
    </html>
    ';
} else {
    // --- CONTENIDO PARA USUARIOS COMUNES (MANTENER EL SIMPLIFICADO) ---
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
            .tip-box { background-color: #e3f2fd; border-radius: 8px; padding: 15px; border: 1px solid #bbdefb; margin: 20px 0; }
            .tip-title { font-weight: bold; color: #0d47a1; margin-bottom: 5px; font-size: 14px; }
            .field-list { background-color: #f8f9fa; padding: 10px; border-radius: 5px; border: 1px solid #eee; }
            .footer { position: fixed; bottom: -10px; width: 100%; text-align: center; font-size: 10px; color: #999; border-top: 1px solid #eee; padding-top: 5px; }
        </style>
    </head>
    <body>
        <div class="header">
            <h1>GUÍA RÁPIDA DE USUARIO - INVENTARIO</h1>
            <p style="margin: 0; font-weight: bold; color: #666;">Sistema Integrado de Gestión (SIG)</p>
        </div>

        <div class="section">
            <p>Esta guía está dirigida al personal policial encargado de registrar y controlar los bienes de su oficina o unidad. Siga estos pasos para mantener su inventario al día.</p>
        </div>

        <div class="section">
            <h2>Paso 1: Acceso</h2>
            <p>Use su DNI como usuario. En el menú de la izquierda, haga clic en el icono de la caja 📦 <strong>"Inventario"</strong>.</p>
        </div>

        <div class="section">
            <h2>Paso 2: Registrar un Bien (+ Nuevo Item)</h2>
            <p>Haga clic en el botón azul <strong style="color: #00779e;">+ Nuevo Item</strong>:</p>
            <div class="field-list">
                <ul>
                    <li><strong>Código:</strong> El número patrimonial de la etiqueta.</li>
                    <li><strong>Denominación:</strong> ¿Qué es el bien? (Ej: Computadora).</li>
                    <li><strong>Marca/Modelo/Serie:</strong> Datos vitales para equipos informáticos.</li>
                    <li><strong>Ubicación:</strong> ¿En qué oficina exacta está?</li>
                    <li><strong>Responsable:</strong> Grado y Apellidos de quien usa el bien.</li>
                </ul>
            </div>
        </div>

        <div class="section">
            <h2>Paso 3: Reportes (Anexo 07)</h2>
            <p>Haga clic en el botón amarillo <strong>Reportes</strong> y elija <strong>Reporte PDF</strong> para descargar su hoja de inventario lista para firmar.</p>
        </div>

        <div class="tip-box">
            <div class="tip-title">⚠️ IMPORTANTE:</div>
            <p>Si se equivoca, use el botón de <strong>Editar</strong> (Lápiz azul) en su lista para corregir la información.</p>
        </div>

        <div class="footer">
            Guía de Usuario SIG - Inventario &copy; ' . date('Y') . '
        </div>
    </body>
    </html>
    ';
}

$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

// Limpiar cualquier salida previa (incluyendo espacios o warnings)
if (ob_get_length()) ob_clean();

$dompdf->stream("MANUAL_USUARIO_INVENTARIO_SIG.pdf", array("Attachment" => false));
exit;
