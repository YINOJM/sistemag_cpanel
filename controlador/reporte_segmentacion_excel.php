<?php
declare(strict_types=1);
// reporte_segmentacion_excel.php
ob_start(); 
// Limpiar cualquier buffer previo que pudiera inyectar espacios en blanco
if (ob_get_level() > 1) ob_clean(); 
error_reporting(0);
ini_set('display_errors', '0');

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../modelo/conexion.php';

mysqli_report(MYSQLI_REPORT_OFF);
date_default_timezone_set('America/Lima');

/* ===== Normalizar conexión ===== */
if (isset($conn) && $conn instanceof mysqli) {
    // ok
} elseif (isset($conexion) && $conexion instanceof mysqli) {
    $conn = $conexion;
} elseif (isset($mysqli) && $mysqli instanceof mysqli) {
    $conn = $mysqli;
} else {
    http_response_code(500);
    echo 'Error de conexión a BD';
    exit;
}

$conn->set_charset('utf8mb4');

/* ===== Filtros ===== */
$anio         = isset($_GET['anio']) ? (int)$_GET['anio'] : (int)date('Y');
$programado   = isset($_GET['programado']) && $_GET['programado'] !== '' ? (int)$_GET['programado'] : null;
$texto        = trim($_GET['q'] ?? '');
$objeto       = trim($_GET['objeto'] ?? '');
$tipo_proceso = isset($_GET['tipo_proceso']) ? (int)$_GET['tipo_proceso'] : 0;
$resultado    = trim($_GET['resultado'] ?? '');

$where = ["s.anio={$anio}"];
if ($programado !== null) $where[] = "s.programado={$programado}";
if ($texto !== '')        $where[] = "CONCAT(s.descripcion,' ',s.ref_pac) LIKE '%".$conn->real_escape_string($texto)."%'";
if ($objeto !== '')       $where[] = "s.objeto_contratacion='".$conn->real_escape_string($objeto)."'";
if ($tipo_proceso>0)      $where[] = "s.tipo_proceso_id={$tipo_proceso}";
if ($resultado!=='')      $where[] = "s.resultado_segmentacion='".$conn->real_escape_string($resultado)."'";
$where_sql = $where ? ('WHERE '.implode(' AND ', $where)) : '';

/* ===== Procedimientos ===== */
$sqlProc = "
SELECT
  s.id,
  s.ref_pac,
  s.objeto_contratacion,
  s.descripcion,
  s.cuantia,

  -- %PAC dinámico (CORRECTO SIEMPRE)
  ROUND(
      (s.cuantia / NULLIF((SELECT SUM(cuantia) FROM segmentacion WHERE anio = s.anio), 0)) * 100,
      2
  ) AS porcentaje,

  s.cuantia_categoria,
  s.riesgo_categoria,
  s.resultado_segmentacion,
  s.programado,
  s.fecha,
  tp.nombre AS tipo_proceso
FROM segmentacion s
LEFT JOIN tipo_proceso tp ON tp.id = s.tipo_proceso_id
{$where_sql}
ORDER BY s.ref_pac+0 ASC, s.id ASC
";


$rsProc = $conn->query($sqlProc);
$procs  = [];
while ($r = $rsProc->fetch_assoc()) $procs[] = $r;

/* ===== Ítems (tolerante a nombres de columnas) ===== */
$itemsMap = [];
if ($procs) {
  $ids = array_map(function($r){ return (int)$r['id']; }, $procs);
  $idList = implode(',', $ids);

  $rsItems = $conn->query("
    SELECT * FROM segmentacion_items
    WHERE segmentacion_id IN ({$idList})
    ORDER BY segmentacion_id ASC, id ASC
  ");
  while ($it = $rsItems->fetch_assoc()) {
    $sid = (int)$it['segmentacion_id'];

    $desc = '';
    if (isset($it['descripcion']) && $it['descripcion']!=='')               $desc = (string)$it['descripcion'];
    elseif (isset($it['descripcion_item']) && $it['descripcion_item']!=='') $desc = (string)$it['descripcion_item'];
    elseif (isset($it['detalle']) && $it['detalle']!=='')                   $desc = (string)$it['detalle'];
    elseif (isset($it['nombre']) && $it['nombre']!=='')                     $desc = (string)$it['nombre'];

    $monto = 0.0;
    if (isset($it['monto_item'])) $monto = (float)$it['monto_item'];
    elseif (isset($it['monto']))  $monto = (float)$it['monto'];
    elseif (isset($it['importe']))$monto = (float)$it['importe'];

    $itemsMap[$sid][] = ['descripcion'=>$desc, 'monto'=>$monto];
  }
}

/* ===== Spreadsheet ===== */
$ss = new Spreadsheet();
$sheet = $ss->getActiveSheet();
$sheet->setTitle('Segmentación (con ítems)');
$sheet->setShowGridlines(false);

/* ✔ Centro vertical por defecto en todo el libro */
$ss->getDefaultStyle()->getAlignment()
   ->setVertical(Alignment::VERTICAL_CENTER);

/* Colores */
$azulHeader = '0B7FC1';
$grisBorde  = 'E6EAF0';
$amarillo   = 'C8CF02';
$zebraItems = 'EEF3F8';
$chipGreen  = '1FAA70';
$chipCyan   = '00ACC1';
$chipOrange = 'FF9800';
$chipRed    = 'E53935';

/* Título */
$sheet->mergeCells('A1:J1');
$sheet->setCellValue('A1', 'SEGMENTACIÓN DE BIENES Y SERVICIOS – UE009-VII DIRTEPOL LIMA ('.$anio.') ');
$sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
$sheet->getStyle('A1')->getAlignment()
  ->setHorizontal(Alignment::HORIZONTAL_CENTER)
  ->setVertical(Alignment::VERTICAL_CENTER)
  ->setWrapText(true);
$sheet->getRowDimension(1)->setRowHeight(28);

/* Cabecera */
$headers = ['N°','N° PAC','TIPO DE PROCESO','DESCRIPCIÓN','CUANTÍA (S/)', '% PAC','CUANTÍA','RIESGO','RESULTADO','PROC.PROGRAMADO'];
$sheet->fromArray($headers, null, 'A3');
$sheet->getStyle('A3:J3')->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
$sheet->getStyle('A3:J3')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($azulHeader);
$sheet->getStyle('A3:J3')->getAlignment()
  ->setHorizontal(Alignment::HORIZONTAL_CENTER)
  ->setVertical(Alignment::VERTICAL_CENTER);
$sheet->getRowDimension(3)->setRowHeight(22);

/* Bordes helper */
$applyBorders = function(string $range) use($sheet, $grisBorde){
  $sheet->getStyle($range)->getBorders()->getAllBorders()
    ->setBorderStyle(Border::BORDER_THIN)
    ->getColor()->setRGB($grisBorde);
};

/* Anchos */
$sheet->getColumnDimension('A')->setWidth(6);
$sheet->getColumnDimension('B')->setWidth(18);
$sheet->getColumnDimension('C')->setWidth(18);
$sheet->getColumnDimension('D')->setWidth(95);
$sheet->getColumnDimension('E')->setWidth(16);
$sheet->getColumnDimension('F')->setWidth(8);
$sheet->getColumnDimension('G')->setWidth(12);
$sheet->getColumnDimension('H')->setWidth(11);
$sheet->getColumnDimension('I')->setWidth(14);
$sheet->getColumnDimension('J')->setWidth(15);

/* “Chip” de resultado */
$resultStyle = function($txt) use($chipGreen,$chipCyan,$chipOrange,$chipRed){
  $txtN = mb_strtolower(trim((string)$txt), 'UTF-8');
  $fill = null; $fontRGB = 'FFFFFF';
  if ($txtN === 'rutinario') { $fill = $chipGreen; }
  elseif ($txtN === 'operacional') { $fill = $chipCyan; $fontRGB = '111111'; }
  elseif ($txtN === 'crítico' || $txtN === 'critico') { $fill = $chipOrange; $fontRGB = '111111'; }
  elseif ($txtN === 'estratégico' || $txtN === 'estrategico') { $fill = $chipRed; }
  return ['fill' => $fill, 'font' => $fontRGB];
};

/* Datos */
$row = 4; $n=0;
$sumProgCant=0; $sumProgTotal=0.0;
$sumNoProgCant=0; $sumNoProgTotal=0.0;

foreach ($procs as $p) {
  $n++;
  $id      = (int)$p['id'];
  $cuantia = (float)$p['cuantia'];
  $progTxt = ((int)$p['programado']===1) ? 'Sí' : 'No';

  if ((int)$p['programado']===1) { $sumProgCant++; $sumProgTotal += $cuantia; }
  else { $sumNoProgCant++; $sumNoProgTotal += $cuantia; }

  /* Fila principal (negrita) */
  $sheet->setCellValue("A{$row}", $n);
  $sheet->setCellValue("B{$row}", (string)$p['ref_pac']);
  $sheet->setCellValue("C{$row}", $p['tipo_proceso']);
  $sheet->setCellValue("D{$row}", $p['descripcion']);
  $sheet->setCellValue("E{$row}", $cuantia);

  // 👉 Usar porcentaje como float, sin cortar decimales
  $porc = (float)($p['porcentaje'] ?? 0);
  $sheet->setCellValue("F{$row}", $porc);

  $sheet->setCellValue("G{$row}", $p['cuantia_categoria']);
  $sheet->setCellValue("H{$row}", $p['riesgo_categoria']);
  $sheet->setCellValue("I{$row}", $p['resultado_segmentacion']);
  $sheet->setCellValue("J{$row}", $progTxt);

  $sheet->getStyle("A{$row}:J{$row}")->getFont()->setBold(true);
  $sheet->getStyle("D{$row}")->getAlignment()->setWrapText(true);

  /* ✔ Centro vertical explícito en la fila del procedimiento */
  $sheet->getStyle("A{$row}:J{$row}")
        ->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

  $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
  $sheet->getStyle("B{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
  $sheet->getStyle("E{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
  $sheet->getStyle("F{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
  $sheet->getStyle("J{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

  $st = $resultStyle($p['resultado_segmentacion']);
  if (!empty($st['fill'])) {
    $sheet->getStyle("I{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($st['fill']);
    $sheet->getStyle("I{$row}")->getFont()->getColor()->setRGB($st['font']);
    $sheet->getStyle("I{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
  }

  $row++;

  /* Ítems (si hay) */
  $sumItems = 0.0;
  if (!empty($itemsMap[$id])) {
    $k=0;
    foreach ($itemsMap[$id] as $it) {
      $k++;
      $desc  = (string)$it['descripcion'];
      $monto = (float)$it['monto'];

      $sheet->setCellValue("D{$row}", "Ítem {$k}. ".$desc);
      $sheet->setCellValue("E{$row}", $monto);

      $sheet->getStyle("D{$row}:E{$row}")->getFont()->setItalic(true);
      $sheet->getStyle("D{$row}")->getAlignment()->setWrapText(true);
      $sheet->getStyle("E{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
      $sheet->getStyle("A{$row}:J{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($zebraItems);

      $sumItems += $monto;
      $row++;
    }

    /* Suma ítems (fila amarilla, centrada verticalmente) */
    $sheet->setCellValue("D{$row}", 'Suma ítems');
    $sheet->setCellValue("E{$row}", $sumItems);

    $sheet->getStyle("D{$row}:E{$row}")->getFont()->setBold(true);
    $sheet->getStyle("E{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

    $sheet->getStyle("A{$row}:J{$row}")
          ->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($amarillo);

    /* ✔ Centro vertical explícito en la fila amarilla */
    $sheet->getStyle("A{$row}:J{$row}")
          ->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

    /* Altura para que el centrado se note */
    $sheet->getRowDimension($row)->setRowHeight(22);

    $row++;
  }
}

/* Formatos, bordes, filtros */
$sheet->getStyle("E4:E".($row-1))->getNumberFormat()->setFormatCode('#,##0.00');
$sheet->getStyle("F4:F".($row-1))->getNumberFormat()->setFormatCode('0.00'); // 👉 2 decimales en % PAC

// Centrar columnas G (CUANTÍA) y H (RIESGO)
$sheet->getStyle("G4:H".($row-1))
      ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

$applyBorders("A3:J".($row-1));
$sheet->setAutoFilter("A3:J".($row-1));
$sheet->freezePane('A4');

/* ===== RESUMEN debajo (compacto a la IZQUIERDA) ===== */
$row += 2;
$sumTop = $row;

/* Título del recuadro */
$sheet->mergeCells("B{$row}:E{$row}");
$sheet->setCellValue("B{$row}", 'RESUMEN');
$sheet->getStyle("B{$row}:E{$row}")->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
$sheet->getStyle("B{$row}:E{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($azulHeader);
$sheet->getStyle("B{$row}:E{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
$row++;

/* Cabeceras */
$sheet->setCellValue("C{$row}", 'Procedimientos');
$sheet->setCellValue("D{$row}", 'Total (S/)');
$sheet->getStyle("C{$row}:D{$row}")->getFont()->setBold(true);
$sheet->getStyle("C{$row}:D{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
$applyBorders("B{$row}:E{$row}");
$row++;

/* Filas */
$sheet->setCellValue("B{$row}", 'Programados');
$sheet->setCellValue("C{$row}", $sumProgCant);
$sheet->setCellValue("D{$row}", $sumProgTotal);
$row++;

$sheet->setCellValue("B{$row}", 'No programados');
$sheet->setCellValue("C{$row}", $sumNoProgCant);
$sheet->setCellValue("D{$row}", $sumNoProgTotal);
$row++;

$sheet->setCellValue("B{$row}", 'Total general');
$sheet->setCellValue("C{$row}", $sumProgCant + $sumNoProgCant);
$sheet->setCellValue("D{$row}", $sumProgTotal + $sumNoProgTotal);
$sheet->getStyle("B{$row}:E{$row}")->getFont()->setBold(true);
$sheet->getStyle("B{$row}:E{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('DDE8FB');

/* Formatos y bordes del resumen */
$sheet->getStyle("D".($row-2).":D{$row}")->getNumberFormat()->setFormatCode('#,##0.00');
$sheet->getStyle("C".($row-3).":C{$row}")->getNumberFormat()->setFormatCode('0');
$sheet->getStyle("C".($row-3).":D{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
$applyBorders("B{$sumTop}:E{$row}");

/* Impresión */
$ps = $sheet->getPageSetup();
$ps->setOrientation(PageSetup::ORIENTATION_LANDSCAPE);
$ps->setPaperSize(PageSetup::PAPERSIZE_A4);
$ps->setFitToWidth(1);
$ps->setFitToHeight(0);
$sheet->getPageMargins()->setTop(0.4)->setRight(0.25)->setLeft(0.25)->setBottom(0.4);
$sheet->getHeaderFooter()->setOddFooter('&LUE009-VII DIRTEPOL LIMA&R&Página &P de &N');

// 1. Limpiar CUALQUIER salida accidental (espacios, warnings) antes del stream
if (ob_get_length()) ob_clean(); 

// 2. Cabeceras robustas para descarga de Excel
$filename = 'reporte_segmentacion_' . date('Ymd_His') . '.xlsx';

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $filename . '"; filename*=UTF-8\'\'' . rawurlencode($filename));
header('Cache-Control: max-age=0');
header('Cache-Control: max-age=1');
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Fecha en el pasado
header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
header('Cache-Control: cache, must-revalidate'); // HTTP/1.1
header('Pragma: public'); // HTTP/1.0

$writer = new Xlsx($ss);
$writer->setPreCalculateFormulas(false);
$writer->save('php://output');
exit;
