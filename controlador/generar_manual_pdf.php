<?php
// controlador/generar_manual_pdf.php
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

$html = '
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 40px; }
        h1 { color: #00779e; text-align: center; border-bottom: 2px solid #00779e; padding-bottom: 10px; }
        h2 { color: #005a7a; margin-top: 30px; border-left: 5px solid #00779e; padding-left: 10px; }
        h3 { color: #333; margin-top: 20px; }
        .section { margin-bottom: 30px; }
        .badge { display: inline-block; padding: 2px 8px; border-radius: 4px; font-size: 12px; font-weight: bold; }
        .bg-info { background-color: #17a2b8; color: white; }
        .bg-warning { background-color: #ffc107; color: #212529; }
        .bg-success { background-color: #28a745; color: white; }
        .bg-danger { background-color: #dc3545; color: white; }
        .bg-secondary { background-color: #6c757d; color: white; }
        .alert { padding: 10px; border-radius: 5px; margin: 10px 0; border: 1px solid #ddd; }
        .alert-info { background-color: #d1ecf1; border-color: #bee5eb; color: #0c5460; }
        ul { margin-bottom: 10px; }
        li { margin-bottom: 5px; }
        .footer { position: fixed; bottom: 0; width: 100%; text-align: center; font-size: 10px; color: #777; border-top: 1px solid #ddd; padding-top: 5px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>MANUAL DE GESTIÓN DE USUARIOS Y PERMISOS - SIG</h1>
    </div>

    <div class="section">
        <p>Este manual detalla las funciones implementadas para facilitar el registro, la seguridad y el control de accesos en el <strong>Sistema Integrado de Gestión (SIG)</strong>.</p>
    </div>

    <div class="section">
        <h2>1. Administración de Usuarios (Panel de Control)</h2>
        <h3>A. Registro de Personal</h3>
        <p>Como Super Administrador o Usuario con permisos de Configuración, usted debe registrar al personal manualmente desde el botón <strong>"Registrar"</strong>. Esto garantiza que solo personas autorizadas tengan acceso al sistema.</p>

        <h3>B. Activación y Desactivación</h3>
        <p>Utilice el botón de estado en la lista para habilitar o deshabilitar usuarios. Un usuario <strong>Inactivo</strong> no podrá iniciar sesión por ningún motivo.</p>

        <h3>C. Recuperación de Contraseña (Reseteo)</h3>
        <p>Si un efectivo olvida sus credenciales, usted puede asignar una nueva clave desde el modal de <strong>Editar</strong> (campo "Nueva Contraseña").</p>
    </div>

    <div class="section">
        <h2>2. Gestión de Permisos Detallada</h2>
        <h3>Plantillas Rápidas:</h3>
        <ul>
            <li><strong>Lectura:</strong> Solo ver.</li>
            <li><strong>Editor:</strong> Ver y crear.</li>
            <li><strong>Gestor:</strong> Ver, crear, editar y exportar.</li>
            <li><strong>Admin:</strong> Control total.</li>
        </ul>

        <h3>Gestión Masiva:</h3>
        <p>Seleccione varios usuarios para aplicar una plantilla a todos a la vez o use <strong>"Copiar Permisos"</strong> para clonar la configuración exacta de un usuario modelo a otros. Esto ahorra tiempo al configurar grupos de una misma oficina.</p>
    </div>

    <div class="section">
        <h2>3. Seguridad Proactiva y Reglas</h2>
        <ul>
            <li><strong>Usuario = DNI:</strong> Siempre se usa el DNI para ingresar.</li>
            <li><strong>Encriptación:</strong> Las claves están cifradas con MD5.</li>
            <li><strong>Bloqueo Automático:</strong> Usuarios inactivos no tienen acceso al sistema.</li>
        </ul>
    </div>

    <div class="section" style="background-color: #f8f9fa; padding: 15px; border-radius: 8px; border: 1px dashed #00779e;">
        <h2 style="margin-top: 0;">4. Ejemplos Prácticos de Gestión</h2>
        
        <div class="alert alert-info" style="border-left: 5px solid #00779e;">
            <strong>Ejemplo 1: Registrar a un nuevo integrante</strong><br>
            Usted crea la cuenta de Lucas con su DNI y datos base. Lucas aparecerá como <span class="badge bg-success">Activo</span> por defecto (o Inactivo si desea que espere). Lucas ya podrá ingresar usando su DNI y la clave que usted le asigne.
        </div>

        <div class="alert alert-info" style="border-left: 5px solid #00779e;">
            <strong>Ejemplo 2: ¿Un usuario olvidó su clave?</strong><br>
            1. Busque al usuario en la lista y haga clic en <strong>Editar</strong> (Lápiz amarillo).<br>
            2. En el campo "Nueva Contraseña", escriba por ejemplo: <code>Pnp2024*</code>.<br>
            3. Guarde los cambios y dígale al usuario que su contraseña ahora es esa.
        </div>

        <div class="alert alert-info" style="border-left: 5px solid #00779e;">
            <strong>Ejemplo 3: Copiar permisos de LUCAS a otros</strong><br>
            Supongamos que LUCAS ya tiene permisos perfectos. Usted registra a JOEL y LAGO y quiere que tengan lo mismo:<br>
            1. Seleccione a los tres usuarios en la tabla (JOEL, LAGO y LUCAS).<br>
            2. Haga clic en <strong>"Copiar Permisos"</strong>.<br>
            3. En el buscador que aparece, elija a <strong>LUCAS</strong> como origen.<br>
            4. El sistema clonará todo lo de Lucas hacia Joel y Lago en un segundo.
        </div>
    </div>

    <div class="footer">
        Sistema Integrado de Gestión - SIG &copy; ' . date('Y') . '
    </div>
</body>
</html>
';

$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

// Limpiar cualquier salida previa (incluyendo espacios o warnings)
if (ob_get_length()) ob_clean();

$dompdf->stream("MANUAL_GESTION_USUARIOS_SIG.pdf", array("Attachment" => false));
exit;
