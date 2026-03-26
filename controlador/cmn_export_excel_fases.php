<?php
// controlador/cmn_export_excel_fases.php
require_once __DIR__ . "/../modelo/conexion.php";
require_once __DIR__ . "/../vendor/autoload.php";

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['id']) || !userCan('cmn')) die("Acceso denegado.");

$fase = isset($_GET['fase']) ? (int)$_GET['fase'] : 1;
$anio = isset($_GET['anio']) ? (int)$_GET['anio'] : date('Y');

$tabla_anexo = "cmn_anexos_fase1";
$fase_nombre = "IDENTIFICACIÓN";
if ($fase === 2) { $tabla_anexo = "cmn_anexos_fase2"; $fase_nombre = "CLASIFICACIÓN"; }
if ($fase === 3) { $tabla_anexo = "cmn_anexos_fase3"; $fase_nombre = "CONSOLIDACIÓN"; }

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle("CMN FASE " . $fase);

// Título
$sheet->mergeCells('A1:K1');
$sheet->setCellValue('A1', "REPORTE CMN $anio - FASE: $fase_nombre");
$sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
$sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

// Cabeceras
$headers = ['N°', 'DNI', 'APELLIDOS Y NOMBRES', 'GRADO', 'CIP', 'REGIÓN POLICIAL', 'DIVOPUS / FRENTE', 'SUB-UNIDAD', 'ESTADO', 'MONTO', 'FECHA ENVÍO'];
$col = 'A';
foreach ($headers as $h) {
    $sheet->setCellValue($col . '2', $h);
    $sheet->getStyle($col . '2')->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');
    $sheet->getStyle($col . '2')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('0d2a4a');
    $col++;
}

$sql = "SELECT 
            r.dni, CONCAT(r.apellidos, ' ', r.nombres) as nombres, r.grado, r.cip, 
            r.region_policial, r.divpol_divopus, r.sub_unidad_especifica,
            a.estado_revision, a.monto_total, a.fecha_subida
        FROM cmn_responsables r 
        LEFT JOIN $tabla_anexo a ON r.dni = a.dni_responsable 
        WHERE r.anio_proceso = $anio AND r.archivo_pdf IS NOT NULL
        ORDER BY r.sub_unidad_especifica ASC";

$res = $conexion->query($sql);
$rowPos = 3;
$cnt = 1;

while ($r = $res->fetch_assoc()) {
    $sheet->setCellValue('A' . $rowPos, $cnt++);
    $sheet->setCellValueExplicit('B' . $rowPos, $r['dni'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
    $sheet->setCellValue('C' . $rowPos, $r['nombres']);
    $sheet->setCellValue('D' . $rowPos, $r['grado']);
    $sheet->setCellValue('E' . $rowPos, $r['cip']);
    $sheet->setCellValue('F' . $rowPos, $r['region_policial']);
    $sheet->setCellValue('G' . $rowPos, $r['divpol_divopus']);
    $sheet->setCellValue('H' . $rowPos, $r['sub_unidad_especifica']);
    
    $estado = "PENDIENTE";
    if ($r['estado_revision'] !== null) {
        if($r['estado_revision'] == 0) $estado = "RECIBIDO";
        elseif($r['estado_revision'] == 1) $estado = "VALIDADO";
        elseif($r['estado_revision'] == 2) $estado = "OBSERVADO";
    }
    $sheet->setCellValue('I' . $rowPos, $estado);
    $sheet->setCellValue('J' . $rowPos, $r['monto_total'] ?? 0);
    $sheet->setCellValue('K' . $rowPos, $r['fecha_subida'] ? date('d/m/Y H:i', strtotime($r['fecha_subida'])) : '-');
    $rowPos++;
}

foreach (range('A', 'K') as $c) $sheet->getColumnDimension($c)->setAutoSize(true);

$filename = "Reporte_CMN_Fase{$fase}_" . date('Ymd_His') . ".xlsx";
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="' . $filename . '"');
$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
