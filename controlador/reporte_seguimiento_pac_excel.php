<?php
// reporte_seguimiento_pac_excel.php
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

/* ===== Consulta principal ===== */
$sql = "
SELECT 
    s.id as segmentacion_id, 
    s.ref_pac,
    s.objeto_contratacion as t_contratacion, 
    s.descripcion as objeto_contrato,
    s.origen_registro,
    tp.nombre as t_procedimiento,
    s.cuantia as v_estimado,
    s.cmn as cmn,
    sp.mes_programado,
    sp.estado_proceso,
    sp.valor_convocado,
    sp.monto_adjudicado as valor_adjudicado,
    sp.imp_comprometido,
    sp.imp_devengado,
    sp.imp_girado,
    sp.certificado,
    sp.observaciones
FROM segmentacion s
LEFT JOIN tipo_proceso tp ON tp.id = s.tipo_proceso_id
LEFT JOIN seguimiento_pac sp ON sp.segmentacion_id = s.id
WHERE s.anio = $anio AND (sp.oculto = 0 OR sp.oculto IS NULL)
ORDER BY s.id DESC";

$rs = $conn->query($sql);

/* ===== Spreadsheet ===== */
$ss = new Spreadsheet();
$sheet = $ss->getActiveSheet();
$sheet->setTitle('Seguimiento PAC ' . $anio);

// Estilos globales
$ss->getDefaultStyle()->getFont()->setName('Arial')->setSize(10);
$ss->getDefaultStyle()->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

// Colores
$azulCorporativo = '004085';
$grisBorde = 'DEE2E6';
$grisZebra = 'F8F9FA';

// Título
$sheet->mergeCells('A1:L1');
$sheet->setCellValue('A1', 'REPORTE DE SEGUIMIENTO PAC (' . $anio . ') - LEY 32069');
$sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14)->getColor()->setRGB('FFFFFF');
$sheet->getStyle('A1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($azulCorporativo);
$sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
$sheet->getRowDimension(1)->setRowHeight(30);

// Cabeceras
$headers = [
    'N° REF.', 'CMN', 'TIPO CONTRAT.', 'OBJETO DE LA CONTRATACIÓN', 'TIPO PROCED.', 
    'MES PROG.', 'ESTADO', 'ESTIMADO (S/)', 'ADJUDICADO (S/)', 'CERTIF.', 'ORIGEN', 'OBSERVACIONES'
];
$sheet->fromArray($headers, null, 'A3');
$sheet->getStyle('A3:L3')->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
$sheet->getStyle('A3:L3')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('17A2B8'); // Turquesa
$sheet->getStyle('A3:L3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
$sheet->getRowDimension(3)->setRowHeight(25);

// Anchos de columna
$sheet->getColumnDimension('A')->setWidth(10); // REF
$sheet->getColumnDimension('B')->setWidth(10); // CMN
$sheet->getColumnDimension('C')->setWidth(15); // TIPO CONTRAT
$sheet->getColumnDimension('D')->setWidth(60); // DESCRIPCION
$sheet->getColumnDimension('E')->setWidth(20); // TIPO PROC
$sheet->getColumnDimension('F')->setWidth(12); // MES
$sheet->getColumnDimension('G')->setWidth(18); // ESTADO
$sheet->getColumnDimension('H')->setWidth(16); // ESTIMADO
$sheet->getColumnDimension('I')->setWidth(16); // ADJUDICADO
$sheet->getColumnDimension('J')->setWidth(12); // CERTIF
$sheet->getColumnDimension('K')->setWidth(12); // ORIGEN
$sheet->getColumnDimension('L')->setWidth(40); // OBSERVACIONES

$row = 4;
while ($r = $rs->fetch_assoc()) {
    $sheet->setCellValue("A{$row}", $r['ref_pac']);
    $sheet->setCellValue("B{$row}", $r['cmn']);
    $sheet->setCellValue("C{$row}", $r['t_contratacion']);
    $sheet->setCellValue("D{$row}", $r['objeto_contrato']);
    $sheet->setCellValue("E{$row}", $r['t_procedimiento']);
    $sheet->setCellValue("F{$row}", $r['mes_programado']);
    $sheet->setCellValue("G{$row}", $r['estado_proceso']);
    $sheet->setCellValue("H{$row}", (float)$r['v_estimado']);
    $sheet->setCellValue("I{$row}", (float)$r['valor_adjudicado']);
    $sheet->setCellValue("J{$row}", $r['certificado']);
    $sheet->setCellValue("K{$row}", $r['origen_registro'] === 'Segmentación' ? 'SEG.' : 'MANUAL');
    $sheet->setCellValue("L{$row}", $r['observaciones']);

    // Formato Zebra
    if ($row % 2 === 0) {
        $sheet->getStyle("A{$row}:L{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($grisZebra);
    }

    // Alineaciones
    $sheet->getStyle("A{$row}:C{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle("F{$row}:G{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle("J{$row}:K{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle("H{$row}:I{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
    $sheet->getStyle("D{$row}")->getAlignment()->setWrapText(true);
    $sheet->getStyle("L{$row}")->getAlignment()->setWrapText(true);

    $row++;
}

// Formato moneda
$sheet->getStyle("H4:I" . ($row - 1))->getNumberFormat()->setFormatCode('#,##0.00');

// Bordes
$styleArray = [
    'borders' => [
        'allBorders' => [
            'borderStyle' => Border::BORDER_THIN,
            'color' => ['rgb' => $grisBorde],
        ],
    ],
];
$sheet->getStyle('A3:L' . ($row - 1))->applyFromArray($styleArray);

// Autofiltro y Freeze
$sheet->setAutoFilter('A3:L3');
$sheet->freezePane('A4');

/* Salida */
$filename = 'seguimiento_pac_' . $anio . '_' . date('Ymd_His') . '.xlsx';
if (function_exists('ob_get_level')) { while (ob_get_level() > 0) { @ob_end_clean(); } }
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="'.$filename.'"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($ss);
$writer->save('php://output');
exit;
