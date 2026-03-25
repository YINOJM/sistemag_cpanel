<?php
// controlador/reporte_inventario_pdf.php
require '../vendor/autoload.php';
require_once '../modelo/InventarioModelo.php';

use Dompdf\Dompdf;
use Dompdf\Options;

// Validar Sesión
session_start();
if (empty($_SESSION['id'])) {
    die("Acceso denegado.");
}

// Aumentar memoria para reportes grandes
ini_set('memory_limit', '1024M');
set_time_limit(300);

$anio = $_GET['anio'] ?? date('Y');

// Parámetros dinámicos para el inventariador (opcionales)
$invGrado = $_GET['inv_grado'] ?? '';
$invNombres = $_GET['inv_nombres'] ?? '';
$invDni = $_GET['inv_dni'] ?? '';
$invEquipo = $_GET['inv_equipo'] ?? '';
$tipoVerif = $_GET['inv_tipo_verificacion'] ?? 'fisica';
$markFisica = ($tipoVerif === 'fisica') ? 'X' : '&nbsp;';
$markDigital = ($tipoVerif === 'digital') ? 'X' : '&nbsp;';

// Parámetros de filtrado
$idSubunidadGet = isset($_GET['id_subunidad']) && !empty($_GET['id_subunidad']) ? (int) $_GET['id_subunidad'] : null;

// Obtener datos
$rol = $_SESSION['rol'] ?? '';
$idOficinaSesion = $_SESSION['id_oficina'] ?? null;
$idSubunidadSesion = $_SESSION['id_subunidad'] ?? null;

$idSubunidadFiltro = null;
$idOficinaFiltro = null;

// Variables para el encabezado del reporte
$nombreRegion = '';
$nombreDivision = '';
$nombreSubunidad = '';
$nombreOficina = $_SESSION['nombre_oficina'] ?? ''; // Mantener la oficina del usuario para el texto de ubicación

if ($rol === 'Super Administrador' || $rol === 'Administrador') {
    $idSubunidadFiltro = $idSubunidadGet;
    
    // Si se filtró una subunidad específica, tratar de obtener sus nombres para el encabezado
    if ($idSubunidadFiltro) {
        require_once '../modelo/conexion.php';
        $sqlUnidad = "SELECT s.nombre_subunidad, d.nombre_division, r.nombre_region 
                      FROM sub_unidades_policiales s
                      JOIN divisiones_policiales d ON s.id_division = d.id_division
                      JOIN regiones_policiales r ON d.id_region = r.id_region
                      WHERE s.id_subunidad = $idSubunidadFiltro";
        $resUnidad = $conexion->query($sqlUnidad);
        if ($resUnidad && $uni = $resUnidad->fetch_assoc()) {
            $nombreSubunidad = $uni['nombre_subunidad'];
            $nombreDivision = $uni['nombre_division'];
            $nombreRegion = $uni['nombre_region'];
        }
    } else {
        $nombreSubunidad = "CONSOLIDADO GENERAL";
        $nombreDivision = "TODAS LAS DIVISIONES";
        $nombreRegion = "TODAS LAS REGIONES";
    }
} else {
    $idSubunidadFiltro = $idSubunidadSesion;
    $idOficinaFiltro = $idOficinaSesion;
    $nombreRegion = $_SESSION['nombre_region'] ?? '';
    $nombreDivision = $_SESSION['nombre_division'] ?? '';
    $nombreSubunidad = $_SESSION['nombre_subunidad'] ?? '';
    if (empty($nombreSubunidad)) $nombreSubunidad = $_SESSION['nombre_oficina'] ?? '';
}

// Datos del usuario logueado para el encabezado
$grado = $_SESSION['grado'] ?? '';
$nombreCompleto = $_SESSION['nombre'] . ' ' . $_SESSION['apellido'];

// Recuperar DNI si no está en sesión (para sesiones activas antes del cambio en login)
if (empty($_SESSION['dni'])) {
    // Solo requerimos la conexión si es necesario, ya que puede haber sido requerida antes para la subunidad
    if (!isset($conexion)) {
        require_once '../modelo/conexion.php';
    }
    $idUser = $_SESSION['id'];
    $resDni = $conexion->query("SELECT dni FROM usuario WHERE id_usuario = '$idUser'");
    if ($resDni && $rowDni = $resDni->fetch_assoc()) {
        $_SESSION['dni'] = $rowDni['dni'];
    }
}
$cipUsuario = $_SESSION['dni'] ?? '';

// Determinar qué mostrar en el bloque "USUARIO" (Izquierda) y "INVENTARIADOR" (Derecha)
// El Usuario suele ser el responsable del bien en la unidad, y el Inventariador es quien genera el reporte.

$gradoUsuario = '';
$textoUsuario = '';
$cipUsuario = '';

// 1. Iniciar con datos del usuario en sesión por defecto (Fallback)
$gradoUsuario = mb_strtoupper($grado, 'UTF-8');
$textoUsuario = mb_strtoupper($nombreCompleto, 'UTF-8');
$cipUsuarioLogueado = $_SESSION['dni'] ?? ''; // Evitar colisión de nombres de variable
$cipUsuario = $cipUsuarioLogueado;

// Datos del bloque "PERSONAL INVENTARIADOR" (Derecha)
$invGradoLabel = mb_strtoupper($invGrado, 'UTF-8');
$invNombresLabel = mb_strtoupper($invNombres, 'UTF-8');
$invDniLabel = mb_strtoupper($invDni, 'UTF-8');

// 2. Si hay un filtro de subunidad y NO es la subunidad del usuario logueado (ej: admin mirando otra unidad),
// buscamos al responsable de esa unidad específica.
if ($idSubunidadFiltro && (int)$idSubunidadFiltro !== (int)$idSubunidadSesion) {
    if (!isset($conexion)) { require_once '../modelo/conexion.php'; }
    $resUserUnit = $conexion->query("SELECT nombre, apellido, grado, dni FROM usuario WHERE id_subunidad = $idSubunidadFiltro AND estado = 'Activo' ORDER BY rol DESC LIMIT 1");
    if ($resUserUnit && $uUnit = $resUserUnit->fetch_assoc()) {
        $gradoUsuario = mb_strtoupper($uUnit['grado'], 'UTF-8');
        $textoUsuario = mb_strtoupper($uUnit['nombre'] . ' ' . $uUnit['apellido'], 'UTF-8');
        $cipUsuario = $uUnit['dni'];
    }
}

// 3. Ya no sobrescribimos el bloque USUARIO con los datos del modal, 
// pues esos datos ahora van al bloque PERSONAL INVENTARIADOR (Derecha)
/*
if (!empty($invNombres)) {
    $gradoUsuario = !empty($invGrado) ? mb_strtoupper($invGrado, 'UTF-8') : '';
    $textoUsuario = mb_strtoupper($invNombres, 'UTF-8');
    $cipUsuario = $invDni;
}
*/

// Construir Ubicación Visual
// Si hay un filtro de unidad, usarlo. Si no, usar la oficina del usuario.
if ($nombreSubunidad && $nombreSubunidad !== 'CONSOLIDADO GENERAL') {
    $textoUbicacion = $nombreSubunidad;
} else {
    $textoUbicacion = $nombreOficina ? $nombreOficina : "SIN OFICINA ASIGNADA";
}


// Obtener datos filtrados
global $conexion;
$modelo = new InventarioModelo();
$data = $modelo->listar((int)$anio, $idOficinaFiltro, $idSubunidadFiltro);

// Configurar Dompdf
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);

$dompdf = new Dompdf($options);

// Convertir imágenes a Base64 (Mantenemos logos institucionales como pidió el usuario)
$pathEscudo = '../public/images/escudo.png';
$pathLogo = '../public/images/logo_regpol.png';

// Verificar existencia de imagenes antes de leer
$base64Escudo = '';
$base64Logo = '';

if (file_exists($pathEscudo)) {
    $typeEscudo = pathinfo($pathEscudo, PATHINFO_EXTENSION);
    $dataEscudo = file_get_contents($pathEscudo);
    $base64Escudo = 'data:image/' . $typeEscudo . ';base64,' . base64_encode($dataEscudo);
}

if (file_exists($pathLogo)) {
    $typeLogo = pathinfo($pathLogo, PATHINFO_EXTENSION);
    $dataLogo = file_get_contents($pathLogo);
    $base64Logo = 'data:image/' . $typeLogo . ';base64,' . base64_encode($dataLogo);
}

// Generar HTML
$html = '
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Anexo 07 - Inventario Patrimonial</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 10px; margin-top: 185px; }
        
        /* Configuración de página */
        @page { margin: 15px 30px 20px 30px; }
        
        /* Encabezado Fijo */
        header {
            position: fixed;
            top: 0px;
            left: 0px;
            right: 0px;
            height: 175px;
            text-align: center;
        }

        /* Títulos */
        .main-title { 
            font-weight: bold; 
            margin-bottom: 2px; 
            font-size: 12px;
        }
        .sub-title { 
            font-weight: bold; 
            margin-bottom: 5px; 
            font-size: 11px;
            text-transform: uppercase;
        }

        /* Tabla Principal */
        .table-data { width: 100%; border-collapse: collapse; margin-bottom: 10px; table-layout: fixed; }
        .table-data th, .table-data td { 
            border: 1px solid #000; 
            padding: 3px; 
            text-align: center; 
            vertical-align: middle;
            font-size: 8px;
            word-wrap: break-word;
        }
        .table-data th { 
            background-color: #BDD7EE; 
            text-transform: uppercase;
            font-weight: bold;
            color: #000;
        }
        
        /* Firmas */
        .signatures { width: 100%; margin-top: 150px; border: none; }
        .signatures td { 
            width: 50%; 
            text-align: center; 
            vertical-align: top;
        }
        .dotted-line { 
            border-bottom: 1px dotted black; 
            height: 35px; 
            margin-bottom: 5px;
            width: 70%;
            margin: 0 auto;
        }
        .firma-role { font-weight: bold; font-size: 9px; text-transform: uppercase; margin-top: 5px;}
        
        /* Logos Headers */
        .logo-img { height: 45px; width: auto; position: absolute; top: 0; }
        .logo-left { left: 25px; }
        .logo-right { right: 25px; }

        /* ESTILO OFICIAL GRID COMPACTO */
        .info-table { width: 100%; border-collapse: collapse; font-size: 8px; border: 0.5px solid #000; margin-top: 5px; }
        .info-table td { border: 0.5px solid #000; padding: 3px 6px; vertical-align: middle; text-align: left; }
        .header-bg { background-color: #E9EFF7; font-weight: bold; width: 15%; } /* Azul pálido etiquetas */
        .title-block { 
            font-weight: bold; 
            font-size: 9px; 
            background-color: #1F4E79; /* Azul oscuro institucional */
            color: white;
            padding: 4px !important; 
            text-align: left; 
        }
    </style>
</head>
<body>

    <header>
        <!-- Logos -->
        ' . ($base64Escudo ? '<img src="' . $base64Escudo . '" class="logo-img logo-left">' : '') . '
        ' . ($base64Logo ? '<img src="' . $base64Logo . '" class="logo-img logo-right">' : '') . '

        <div class="main-title">ANEXO N° 07</div>
        <div class="main-title">FORMATO DE FICHA DE LEVANTAMIENTO DE INFORMACIÓN</div>
        <div class="sub-title">INVENTARIO PATRIMONIAL ' . $anio . '</div>

        <div style="text-align: left; font-weight: bold; font-size: 11px; margin-top: 8px; margin-bottom: 2px;">
            UNIDAD DE ADMINISTRACIÓN - UE 009 REGIÓN POLICIAL LIMA
        </div>

        <!-- Quitamos el borde externo de la tabla para que el espacio central quede limpio -->
        <table class="info-table" style="border: none;">
            <tr>
                <td colspan="2" class="title-block" style="width: 49%;">USUARIO:</td>
                <!-- ESPACIADOR -->
                <td style="width: 2%; border: none; background: white;"></td>
                <td colspan="2" class="title-block" style="width: 49%;">PERSONAL INVENTARIADOR:</td>
            </tr>
            
            <!-- FILA 1 -->
            <tr>
                <!-- USUARIO: Datos Personales Compactos -->
                <td colspan="2">
                    <span style="background-color: #E9EFF7; font-weight: bold; padding: 2px;">GRADO:</span> ' . $gradoUsuario . ' &nbsp;|&nbsp; 
                    <span style="background-color: #E9EFF7; font-weight: bold; padding: 2px;">APELLIDOS Y NOMBRES:</span> ' . $textoUsuario . ' &nbsp;|&nbsp; 
                    <span style="background-color: #E9EFF7; font-weight: bold; padding: 2px;">DNI:</span> ' . $cipUsuario . '
                </td>
                
                <!-- ESPACIADOR -->
                <td style="border: none; background: white;"></td>

                <!-- INVENTARIADOR: Grado -->
                <td class="header-bg" style="width: 15%;">GRADO:</td>
                <td>' . $invGradoLabel . '</td>
            </tr>

            <!-- FILA 2 -->
            <tr>
                <!-- USUARIO: Región -->
                <td class="header-bg" style="width: 12%;">REGION POLICIAL:</td>
                <td>' . mb_strtoupper($nombreRegion, "UTF-8") . '</td>
                
                <!-- ESPACIADOR -->
                <td style="border: none; background: white;"></td>

                <!-- INVENTARIADOR: Nombres -->
                <td class="header-bg">NOMBRES Y APELLIDOS:</td>
                <td>' . $invNombresLabel . '</td>
            </tr>

            <!-- FILA 3 -->
            <tr>
                <!-- USUARIO: División -->
                <td class="header-bg">DIVISION POLICIAL:</td>
                <td>' . mb_strtoupper($nombreDivision, "UTF-8") . '</td>
                
                <!-- ESPACIADOR -->
                <td style="border: none; background: white;"></td>

                <!-- INVENTARIADOR: CIP/DNI -->
                <td class="header-bg">CIP:</td>
                <td>' . $invDniLabel . '</td>
            </tr>

            <!-- FILA 4 -->
            <tr>
                <!-- USUARIO: Sub Unidad -->
                <td class="header-bg">SUB UNIDAD:</td>
                <td>' . mb_strtoupper($nombreSubunidad, "UTF-8") . '</td>
                
                <!-- ESPACIADOR -->
                <td style="border: none; background: white;"></td>

                <!-- INVENTARIADOR: Equipo -->
                <td class="header-bg">EQUIPO DE TRABAJO:</td>
                <td>' . mb_strtoupper($invEquipo, "UTF-8") . '</td>
            </tr>

            <!-- FILA 5: TIPO DE VERIFICACION (Izquierda) y Bloque Vacío (Derecha) -->
            <tr>
                <!-- BLOQUE IZQUIERDO: Verificación -->
                <!-- Celda Título con fondo gris y borde -->
                <td class="header-bg" style="border: 0.5px solid #000;">TIPO DE VERIFICACIÓN:</td>
                <!-- Celda Valor centrada y con borde -->
                <td style="border: 0.5px solid #000; text-align: center;">
                    <span style="margin-right: 15px;">Física ( <b>' . $markFisica . '</b> )</span>
                    <span>Digital ( <b>' . $markDigital . '</b> )</span>
                </td>
                
                <!-- ESPACIADOR -->
                <td style="border: none; background: white;"></td>

                <!-- BLOQUE DERECHO: Celdas vacías para mantener simetría -->
                <td class="header-bg" style="border: 0.5px solid #000;"></td>
                <td style="border: 0.5px solid #000;"></td>
            </tr>
        </table>
    </header>

    <table class="table-data">
        <thead>
            <tr>
                <th width="3%">N°</th>
                <th width="8%">CÓDIGO<br>PATRIMONIAL</th>
                <th width="20%">DENOMINACIÓN</th>
                <th width="8%">MARCA</th>
                <th width="8%">MODELO</th>
                <th width="8%">TIPO</th>
                <th width="6%">COLOR</th>
                <th width="8%">SERIE</th>
                <th width="8%">DIMENSIONES</th>
                <th width="10%">OTROS</th>
                <th width="4%">SIT.<sup>(1)</sup></th>
                <th width="4%">EST.<sup>(2)</sup></th>
                <th width="9%">OBSERVACIÓN</th>
            </tr>
        </thead>
        <tbody>';

$i = 1;

// Normalizador corto para Estado
$mapEstados = [
    'BUENO' => 'B',
    'NUEVO' => 'B',
    'REGULAR' => 'R',
    'MALO' => 'M',
    'CHATARRA' => 'Ch',
    'RAEE' => 'RAEE'
];

foreach ($data as $d) {
    // Normalizar Situacion
    $sit = strtoupper($d['situacion'] ?? 'USO');
    $sitCode = ($sit === 'USO' || $sit === 'U') ? 'U' : 'D';

    // Normalizar Estado code
    $estadoFull = strtoupper($d['estado_bien']);
    $estadoCode = $mapEstados[$estadoFull] ?? substr($estadoFull, 0, 1);

    // Mapeo de campos nuevos
    $tipo = $d['tipo_bien'] ?? '';
    $color = $d['color'] ?? '';
    $dimensiones = $d['dimensiones'] ?? '';
    $otros = $d['otras_caracteristicas'] ?? '';

    $html .= '<tr>
                <td>' . $i . '</td>
                <td>' . htmlspecialchars($d['codigo_inventario']) . '</td>
                <td class="text-left">' . htmlspecialchars(substr($d['descripcion'], 0, 80)) . '</td>
                <td>' . htmlspecialchars($d['marca']) . '</td>
                <td>' . htmlspecialchars($d['modelo']) . '</td>
                <td>' . htmlspecialchars($tipo) . '</td>
                <td>' . htmlspecialchars($color) . '</td>
                <td>' . htmlspecialchars($d['serie']) . '</td>
                <td>' . htmlspecialchars($dimensiones) . '</td>
                <td style="font-size: 8px;">' . htmlspecialchars($otros) . '</td>
                <td>' . $sitCode . '</td>
                <td>' . $estadoCode . '</td>
                <td style="font-size: 8px;">' . htmlspecialchars($d['observaciones']) . '</td>
              </tr>';
    $i++;
}

if (count($data) == 0) {
    $html .= '<tr><td colspan="13">NO HAY REGISTROS PARA ESTE AÑO</td></tr>';
}

$html .= '
        </tbody>
    </table>

    <div class="legend">
        <strong>(1)</strong> Uso (U), Desuso (D)<br>
        <strong>(2)</strong> El estado es consignado en base a la siguiente escala: Bueno (B), Regular (R), Malo (M), Chatarra (Ch) y RAEE.
    </div>

    <table class="signatures">
        <tr>
            <!-- Firma 1 (Izquierda) -->
            <td style="width: 50%;">
                <div class="dotted-line"></div>
                <div class="firma-role">USUARIO:</div>
            </td>
            
            <!-- Firma 2 (Derecha - Personal Inventariador) -->
            <td style="width: 50%;">
                <div class="dotted-line"></div>
                <div class="firma-role">PERSONAL INVENTARIADOR:</div>
            </td>
        </tr>
    </table>

</body>
</html>';

$dompdf->loadHtml($html);

// Orientación Horizontal
$dompdf->setPaper('A4', 'landscape');

$dompdf->render();

// Salida
$dompdf->stream("Anexo07_Inventario_$anio.pdf", ["Attachment" => false]); // Attachment false para previsualizar en navegador si es posible
?>