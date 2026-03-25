<?php
// controlador/cmn_reporte_cumplimiento_pdf.php
ob_start(); // Prevenir cualquier salida accidental que corrompa el PDF

require_once __DIR__ . '/../modelo/conexion.php';

// Verificación de seguridad básica
if (empty($_SESSION['id']) || !userCan('SEGUIMIENTO')) {
    ob_end_clean();
    die("Acceso denegado o sesión expirada.");
}

require_once __DIR__ . '/../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

// 1. Capturar filtros
$filtro_region = isset($_GET['region']) ? $_GET['region'] : '';
$filtro_division = isset($_GET['division']) ? $_GET['division'] : '';
$filtro_estado = isset($_GET['estado']) ? $_GET['estado'] : '';
$anio = isset($_GET['anio']) ? (int)$_GET['anio'] : ANIO_CMN;

// 2. Lógica de coincidencia (Replicada de la vista para consistencia total)
$sql_match_cond = "
    REPLACE(TRIM(UPPER(s.nombre_subunidad)), ' ', '') COLLATE utf8mb4_spanish_ci = REPLACE(TRIM(UPPER(c.sub_unidad_especifica)), ' ', '') COLLATE utf8mb4_spanish_ci
    AND REPLACE(TRIM(UPPER(r.nombre_region)), ' ', '') COLLATE utf8mb4_spanish_ci = REPLACE(TRIM(UPPER(c.region_policial)), ' ', '') COLLATE utf8mb4_spanish_ci
    AND REPLACE(TRIM(UPPER(d.nombre_division)), ' ', '') COLLATE utf8mb4_spanish_ci = REPLACE(TRIM(UPPER(c.divpol_divopus)), ' ', '') COLLATE utf8mb4_spanish_ci
";

$subquery_cumplimiento = "(SELECT COUNT(*) FROM cmn_responsables c 
    WHERE ($sql_match_cond)
    AND c.anio_proceso = $anio 
    AND c.archivo_pdf IS NOT NULL)";

$subquery_responsable_cmn = "(SELECT CONCAT(c.grado, '|', c.apellidos, ' ', c.nombres) 
    FROM cmn_responsables c 
    WHERE ($sql_match_cond)
    AND c.anio_proceso = $anio 
    AND c.archivo_pdf IS NOT NULL 
    ORDER BY c.fecha_registro DESC LIMIT 1)";

// 3. Construir Consulta
$sql_base = "FROM sub_unidades_policiales s
            INNER JOIN divisiones_policiales d ON s.id_division = d.id_division
            INNER JOIN regiones_policiales r ON d.id_region = r.id_region
            WHERE s.estado = 1";

if ($filtro_region) {
    $sql_base .= " AND r.nombre_region = '" . $conexion->real_escape_string($filtro_region) . "'";
}
if ($filtro_division) {
    $sql_base .= " AND d.nombre_division = '" . $conexion->real_escape_string($filtro_division) . "'";
}

$sql_final = "SELECT r.nombre_region, d.nombre_division, s.nombre_subunidad, 
              $subquery_cumplimiento as cumple,
              $subquery_responsable_cmn as responsable
              $sql_base";

if ($filtro_estado == 'cumplio') {
    $sql_final .= " AND $subquery_cumplimiento > 0";
} elseif ($filtro_estado == 'pendiente') {
    $sql_final .= " AND $subquery_cumplimiento = 0";
}

$sql_final .= " ORDER BY r.nombre_region, d.nombre_division, s.nombre_subunidad";
$res = $conexion->query($sql_final);

// 4. Preparar HTML
$pathEscudo = __DIR__ . '/../public/images/escudo.png';
$pathRegpol = __DIR__ . '/../public/images/logo_regpol.png';
$base64Escudo = '';
$base64Regpol = '';

if (file_exists($pathEscudo)) {
    $base64Escudo = 'data:image/png;base64,' . base64_encode(file_get_contents($pathEscudo));
}
if (file_exists($pathRegpol)) {
    $base64Regpol = 'data:image/png;base64,' . base64_encode(file_get_contents($pathRegpol));
}

// Generar etiquetas de imagen solo si existen
$imgEscudoHtml = !empty($base64Escudo) ? '<img src="' . $base64Escudo . '" class="escudo">' : '';
$imgRegpolHtml = !empty($base64Regpol) ? '<img src="' . $base64Regpol . '" class="logo-regpol">' : '';

$html = '
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        @page { margin: 1.5cm 1cm 2cm 1cm; }
        body { font-family: "Helvetica", sans-serif; font-size: 10px; color: #333; }
        .header { text-align: center; border-bottom: 2px solid #003666; padding-bottom: 10px; margin-bottom: 20px; position: relative; min-height: 60px; }
        .escudo { position: absolute; left: 0; top: -10px; width: 45px; }
        .logo-regpol { position: absolute; right: 0; top: -10px; width: 45px; }
        .titulo { font-size: 15px; font-weight: bold; color: #003666; margin: 0 60px; padding-top: 5px; }
        .subtitulo { font-size: 10px; color: #666; margin: 5px 60px 0 60px; }
        
        .filters-info { background: #f1f5f9; padding: 10px; border-radius: 8px; margin-bottom: 20px; font-size: 9px; border-left: 4px solid #003666; }
        
        .table { width: 100%; border-collapse: collapse; }
        .table th { background-color: #003666; color: white; padding: 8px; border: 1px solid #002a50; text-transform: uppercase; font-size: 8px; }
        .table td { padding: 7px; border: 1px solid #e2e8f0; font-size: 9px; }
        .table tr:nth-child(even) { background-color: #f8fafc; }
        
        .badge { padding: 3px 8px; border-radius: 10px; font-weight: bold; font-size: 8px; }
        .bg-success { background-color: #dcfce7; color: #166534; }
        .bg-danger { background-color: #fee2e2; color: #991b1b; }
        
        .footer { position: fixed; bottom: -1cm; left: 0; right: 0; height: 1cm; text-align: center; font-size: 8px; color: #94a3b8; border-top: 1px solid #e2e8f0; padding-top: 5px; }
    </style>
</head>
<body>
    <div class="header">
        ' . $imgEscudoHtml . '
        ' . $imgRegpolHtml . '
        <div class="titulo">REPORTE DE SEGUIMIENTO DE CUMPLIMIENTO</div>
        <div class="subtitulo">PROGRAMACIÓN MULTIANUAL DE BIENES, SERVICIOS Y OBRAS ' . $anio . ' - CMN</div>
    </div>

    <div class="filters-info">
        <strong>Filtros aplicados:</strong> 
        Región: ' . ($filtro_region ?: 'Todas') . ' | 
        División: ' . ($filtro_division ?: 'Todas') . ' | 
        Estado: ' . ($filtro_estado == 'cumplio' ? 'Solo Cumplieron' : ($filtro_estado == 'pendiente' ? 'Solo Pendientes' : 'Todos')) . '
    </div>

    <table class="table">
        <thead>
            <tr>
                <th style="width: 30px;">#</th>
                <th>REGIÓN POLICIAL</th>
                <th>DIVPOL / DIVOPUS</th>
                <th>SUB UNIDAD POLICIAL</th>
                <th style="width: 80px; text-align: center;">ESTADO</th>
                <th>RESPONSABLE REGISTRADO</th>
            </tr>
        </thead>
        <tbody>';

$count = 1;
while ($row = $res->fetch_assoc()) {
    $status_class = ($row['cumple'] > 0) ? 'bg-success' : 'bg-danger';
    $status_text = ($row['cumple'] > 0) ? 'CUMPLIÓ' : 'PENDIENTE';
    $responsable = $row['responsable'] ? str_replace('|', ' - ', $row['responsable']) : '-';
    
    $html .= '
        <tr>
            <td style="text-align: center;">' . $count++ . '</td>
            <td>' . htmlspecialchars($row['nombre_region']) . '</td>
            <td>' . htmlspecialchars($row['nombre_division']) . '</td>
            <td>' . htmlspecialchars($row['nombre_subunidad']) . '</td>
            <td style="text-align: center;">
                <span class="badge ' . $status_class . '">' . $status_text . '</span>
            </td>
            <td>' . htmlspecialchars($responsable) . '</td>
        </tr>';
}

if ($count === 1) {
    $html .= '<tr><td colspan="6" style="text-align: center; padding: 20px;">No se encontraron unidades con los filtros seleccionados.</td></tr>';
}

$html .= '
        </tbody>
    </table>

    <div class="footer"></div>
</body>
</html>';

$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'landscape');
$dompdf->render();

// Agregar numeración de páginas vía Canvas (Post-render)
$canvas = $dompdf->getCanvas();
$w = $canvas->get_width();
$h = $canvas->get_height();
$font = $dompdf->getFontMetrics()->get_font("helvetica", "normal");
$color = array(0.58, 0.64, 0.72); // #94a3b8 aprox

$text = "UNIDAD DE ADMINISTRACIÓN - OFICINA DE PROGRAMACIÓN | Generado el " . date('d/m/Y H:i:s') . " | Página {PAGE_NUM} de {PAGE_COUNT}";
$size = 8;
$width = $dompdf->getFontMetrics()->getTextWidth($text, $font, $size);

// Centrar el texto en el footer
$canvas->page_text(($w - $width) / 2, $h - 28, $text, $font, $size, $color);

ob_end_clean(); // Limpiar búfer antes de enviar el PDF
$dompdf->stream("Seguimiento_Cumplimiento_CMN" . $anio . ".pdf", ["Attachment" => false]);
