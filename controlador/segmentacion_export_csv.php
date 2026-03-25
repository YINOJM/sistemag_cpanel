<?php
// controlador/segmentacion_export_csv.php
date_default_timezone_set('America/Lima');
require_once '../modelo/conexion.php';

$anio          = isset($_GET['anio']) ? (int)$_GET['anio'] : (int)date('Y');
$programado    = isset($_GET['programado']) && $_GET['programado'] !== '' ? (int)$_GET['programado'] : null;
$texto         = trim($_GET['q'] ?? '');
$objeto        = trim($_GET['objeto'] ?? '');
$tipo_proceso  = isset($_GET['tipo_proceso']) ? (int)$_GET['tipo_proceso'] : 0;
$resultado     = trim($_GET['resultado'] ?? '');

$where = ["s.anio={$anio}"];
if ($programado !== null)       $where[] = "s.programado={$programado}";
if ($texto !== '')              $where[] = "CONCAT(s.descripcion,' ',s.ref_pac) LIKE '%".$conn->real_escape_string($texto)."%'";
if ($objeto !== '')             $where[] = "s.objeto_contratacion='".$conn->real_escape_string($objeto)."'";
if ($tipo_proceso > 0)          $where[] = "s.tipo_proceso_id={$tipo_proceso}";
if ($resultado !== '')          $where[] = "s.resultado_segmentacion='".$conn->real_escape_string($resultado)."'";
$where_sql = $where ? ('WHERE '.implode(' AND ',$where)) : '';

$sql = "
SELECT  s.ref_pac, s.objeto_contratacion, tp.nombre AS tipo_proceso,
        s.descripcion, s.cuantia, s.porcentaje, s.cuantia_categoria,
        s.riesgo_categoria, s.resultado_segmentacion,
        s.declarado_desierto, s.pocos_postores, s.mercado_limitado,
        s.programado, s.anio, s.fecha,
        ROUND(SUM(i.monto_item),2) AS suma_items
FROM segmentacion s
LEFT JOIN segmentacion_items i ON i.segmentacion_id=s.id
LEFT JOIN tipo_proceso tp ON tp.id=s.tipo_proceso_id
{$where_sql}
GROUP BY s.id
ORDER BY s.ref_pac+0 ASC
";

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="segmentacion_'.$anio.'.csv"');
$out = fopen('php://output', 'w');

fputcsv($out, [
  'N° PAC','Objeto','Tipo Proceso','Descripción',
  'Cuantía','%','Cuantía (Alta/Baja)','Riesgo',
  'Resultado','Desierto 2 años','Pocos postores','Mercado limitado',
  'Programado','Año','Fecha','Suma Ítems'
]);

$res = $conn->query($sql);
while($r = $res->fetch_assoc()){
  fputcsv($out, [
    $r['ref_pac'], $r['objeto_contratacion'], $r['tipo_proceso'],
    $r['descripcion'], number_format((float)$r['cuantia'],2,'.',''),
    (int)$r['porcentaje'], $r['cuantia_categoria'], $r['riesgo_categoria'],
    $r['resultado_segmentacion'], $r['declarado_desierto'],
    $r['pocos_postores'], $r['mercado_limitado'],
    $r['programado'] ? 'Sí' : 'No', $r['anio'], $r['fecha'],
    is_null($r['suma_items']) ? '' : number_format((float)$r['suma_items'],2,'.','')
  ]);
}

fclose($out);
exit;
