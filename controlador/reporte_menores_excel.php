<?php
// reporte_menores_excel.php
declare(strict_types=1);

ini_set('display_errors', '0');
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE & ~E_WARNING);

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../modelo/conexion.php';

if (empty($_SESSION['id'])) {
    die("No autorizado.");
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
date_default_timezone_set('America/Lima');

$conn = $conexion;
$conn->set_charset('utf8mb4');

/* ===== Filtros ===== */
$anio = isset($_GET['anio']) ? (int)$_GET['anio'] : (int)date('Y');
$estado = isset($_GET['estado']) ? (int)$_GET['estado'] : 0;
$q = isset($_GET['q']) ? $_GET['q'] : '';

/* ===== Consulta principal ===== */
$where = "WHERE m.anio = $anio";
if ($estado > 0) {
    $where .= " AND m.estado_id = $estado";
}
if (!empty($q)) {
    $s = $conn->real_escape_string($q);
    $where .= " AND (m.descripcion_servicio_bien LIKE '%$s%' OR m.id LIKE '%$s%')";
}

$sql = "
SELECT 
    m.*, 
    e.nombre as estado_nombre
FROM seguimiento_menores_8uit m
LEFT JOIN seguimiento_estados_menores e ON e.id = m.estado_id
$where
ORDER BY m.id DESC";

$rs = $conn->query($sql);

/* ===== Métricas (KPIs) ===== */
$sql_metricas = "SELECT 
                    COUNT(*) as total_procesos,
                    SUM(monto_comprometido) as total_comprometido,
                    SUM(monto_devengado) as total_devengado,
                    SUM(monto_girado) as total_girado
                 FROM seguimiento_menores_8uit m $where";
$res_metricas = $conn->query($sql_metricas);
$metricas = $res_metricas->fetch_assoc();

$total_proc = (float)($metricas['total_procesos'] ?? 0);
$tot_comp = (float)($metricas['total_comprometido'] ?? 0);
$tot_dev = (float)($metricas['total_devengado'] ?? 0);
$tot_gir = (float)($metricas['total_girado'] ?? 0);

/* ===== Spreadsheet ===== */
$ss = new Spreadsheet();
$sheet = $ss->getActiveSheet();
$sheet->setTitle('Menores 8 UIT ' . $anio);

// Estilos globales
$ss->getDefaultStyle()->getFont()->setName('Arial')->setSize(10);
$ss->getDefaultStyle()->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

// Colores
$azulCorporativo = '004085';
$grisBorde = 'DEE2E6';
$grisZebra = 'F8F9FA';

// Título
$sheet->mergeCells('A1:M1');
$sheet->setCellValue('A1', 'REPORTE DE CONTRATACIONES MENORES A 8 UIT (' . $anio . ')');
$sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14)->getColor()->setRGB('FFFFFF');
$sheet->getStyle('A1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($azulCorporativo);
$sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
$sheet->getRowDimension(1)->setRowHeight(30);

// Panel Dashboard (Fila 3 y 4)
$sheet->mergeCells('B3:C3'); $sheet->mergeCells('B4:C4');
$sheet->setCellValue('B3', 'TOTAL CONTRATOS');
$sheet->setCellValue('B4', $total_proc);
$sheet->getStyle('B3:C4')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('EAF6FC');
$sheet->getStyle('B3')->getFont()->setBold(true)->setSize(9)->getColor()->setRGB('555555');
$sheet->getStyle('B4')->getFont()->setBold(true)->setSize(14)->getColor()->setRGB('00838F');
$sheet->getStyle('B3:B4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

$sheet->mergeCells('E3:F3'); $sheet->mergeCells('E4:F4');
$sheet->setCellValue('E3', 'IMP. COMPROMETIDO (S/)');
$sheet->setCellValue('E4', (float)$tot_comp);
$sheet->getStyle('E3:F4')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('E6F0FF');
$sheet->getStyle('E3')->getFont()->setBold(true)->setSize(9)->getColor()->setRGB('555555');
$sheet->getStyle('E4')->getFont()->setBold(true)->setSize(14)->getColor()->setRGB('1565C0');
$sheet->getStyle('E3:E4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

$sheet->mergeCells('I3:J3'); $sheet->mergeCells('I4:J4');
$sheet->setCellValue('I3', 'IMP. DEVENGADO (S/)');
$sheet->setCellValue('I4', (float)$tot_dev);
$sheet->getStyle('I3:J4')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FFF8E1');
$sheet->getStyle('I3')->getFont()->setBold(true)->setSize(9)->getColor()->setRGB('555555');
$sheet->getStyle('I4')->getFont()->setBold(true)->setSize(14)->getColor()->setRGB('F57F17');
$sheet->getStyle('I3:I4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

$sheet->mergeCells('L3:M3'); $sheet->mergeCells('L4:M4');
$sheet->setCellValue('L3', 'IMP. GIRADO (S/)');
$sheet->setCellValue('L4', (float)$tot_gir);
$sheet->getStyle('L3:M4')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('E8F5E9');
$sheet->getStyle('L3')->getFont()->setBold(true)->setSize(9)->getColor()->setRGB('555555');
$sheet->getStyle('L4')->getFont()->setBold(true)->setSize(14)->getColor()->setRGB('2E7D32');
$sheet->getStyle('L3:L4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

$sheet->getStyle('E4:M4')->getNumberFormat()->setFormatCode('#,##0.00');

$sheet->getRowDimension(3)->setRowHeight(15);
$sheet->getRowDimension(4)->setRowHeight(25);

// Cabeceras (Ahora en Dila 7)
$headers = [
    'N°', 'TIPO DE CONTRATACIÓN', 'OBJETO DE LA CONTRATACIÓN', 'ÁREA USUARIA', 'F. DE REQUERIMIENTO', 
    'FECHA DE OC/OS', 'PLAZO EJECUCIÓN (DIAS)', 'F. FINAL DE EJECUCION', 'IMP. COMPROMETIDO (S/)', 
    'IMP. DEVENGADO (S/)', 'IMP. GIRADO (S/)', 'ESTADO', 'OBSERVACIONES'
];
$sheet->fromArray($headers, null, 'A7');
$sheet->getStyle('A7:M7')->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
$sheet->getStyle('A7:M7')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('006699');
$sheet->getStyle('A7:M7')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
$sheet->getRowDimension(7)->setRowHeight(25);

// Anchos de columna
$sheet->getColumnDimension('A')->setWidth(6);
$sheet->getColumnDimension('A')->setWidth(6);
$sheet->getColumnDimension('B')->setWidth(15);
$sheet->getColumnDimension('C')->setWidth(50);
$sheet->getColumnDimension('D')->setWidth(20);
$sheet->getColumnDimension('E')->setWidth(12);
$sheet->getColumnDimension('F')->setWidth(15);
$sheet->getColumnDimension('G')->setWidth(10);
$sheet->getColumnDimension('H')->setWidth(12);
$sheet->getColumnDimension('I')->setWidth(15);
$sheet->getColumnDimension('J')->setWidth(15);
$sheet->getColumnDimension('K')->setWidth(15);
$sheet->getColumnDimension('L')->setWidth(15);
$sheet->getColumnDimension('M')->setWidth(30);

$row = 8;
$cont = 1;
while ($r = $rs->fetch_assoc()) {
    $sheet->setCellValue("A{$row}", $cont++);
    $sheet->setCellValue("B{$row}", $r['tipo_orden'] == 'OC' ? 'BIENES' : 'SERVICIOS');
    $sheet->setCellValue("C{$row}", $r['descripcion_servicio_bien']);
    $sheet->setCellValue("D{$row}", $r['unidad_solicitante']);
    $sheet->setCellValue("E{$row}", $r['fecha_requerimiento'] ? date('d/m/Y', strtotime($r['fecha_requerimiento'])) : '-');
    $sheet->setCellValue("F{$row}", $r['fecha_emision'] ? date('d/m/Y', strtotime($r['fecha_emision'])) : '-');
    $sheet->setCellValue("G{$row}", $r['plazo_ejecucion_dias']);
    $sheet->setCellValue("H{$row}", $r['fecha_vencimiento'] ? date('d/m/Y', strtotime($r['fecha_vencimiento'])) : '-');
    $sheet->setCellValue("I{$row}", (float)$r['monto_comprometido']);
    $sheet->setCellValue("J{$row}", (float)$r['monto_devengado']);
    $sheet->setCellValue("K{$row}", (float)$r['monto_girado']);
    $sheet->setCellValue("L{$row}", strtoupper($r['estado_nombre'] ?? 'S/E'));
    $sheet->setCellValue("M{$row}", $r['observaciones']);

    // Formato Zebra
    if ($row % 2 === 0) {
        $sheet->getStyle("A{$row}:M{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($grisZebra);
    }

    $sheet->getStyle("A{$row}:B{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle("E{$row}:H{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle("L{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle("I{$row}:K{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
    $sheet->getStyle("C{$row}")->getAlignment()->setWrapText(true);
    $sheet->getStyle("M{$row}")->getAlignment()->setWrapText(true);

    $row++;
}

// Totales
$sheet->setCellValue("H{$row}", 'TOTAL GENERAL:');
$sheet->setCellValue("I{$row}", "=SUM(I8:I" . ($row - 1) . ")");
$sheet->setCellValue("J{$row}", "=SUM(J8:J" . ($row - 1) . ")");
$sheet->setCellValue("K{$row}", "=SUM(K8:K" . ($row - 1) . ")");
$sheet->getStyle("H{$row}:K{$row}")->getFont()->setBold(true);
$sheet->getStyle("H{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

// Formato moneda
$sheet->getStyle("I8:K" . $row)->getNumberFormat()->setFormatCode('#,##0.00');

// Bordes
$styleArray = [
    'borders' => [
        'allBorders' => [
            'borderStyle' => Border::BORDER_THIN,
            'color' => ['rgb' => $grisBorde],
        ],
    ],
];
$sheet->getStyle('A7:M' . ($row - 1))->applyFromArray($styleArray);

$sheet->setAutoFilter('A7:M7');
$sheet->freezePane('A8');

/* Salida */
$filename = 'reporte_menores_8uit_' . date('Ymd_His') . '.xlsx';
if (function_exists('ob_get_level')) { while (ob_get_level() > 0) { @ob_end_clean(); } }
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="'.$filename.'"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($ss);
$writer->save('php://output');
exit;
