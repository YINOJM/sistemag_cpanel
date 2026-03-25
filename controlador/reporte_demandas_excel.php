<?php
// controlador/reporte_demandas_excel.php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../modelo/DemandasModelo.php';

ini_set('display_errors', '0');
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE & ~E_WARNING);

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;

if (empty($_SESSION['id'])) {
    die("Acceso denegado.");
}

// Obtener datos
$anio = (isset($_GET['anio']) && $_GET['anio'] !== 'todos') ? $_GET['anio'] : null;
$data = DemandasModelo::listar($anio);

// Crear Spreadsheet
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Título del reporte
$textoAnio = ($anio) ? " - AÑO FISCAL $anio " : "";
$titulo = "REPORTE DE DEMANDAS PRESUPUESTALES" . $textoAnio . " (" . date('d/m/Y') . ")";

$sheet->setCellValue('A1', $titulo);
$sheet->mergeCells('A1:G1');
$sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14)->setColor(new Color(Color::COLOR_WHITE));
$sheet->getStyle('A1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('00607a');
$sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

// --- CAMBIO EN ENCABEZADOS (Nº en lugar de # ID) ---
$headers = ['Nº', 'CUI', 'Nº Expediente', 'Descripción General', 'Monto Total (S/)', 'Fecha Registro', 'Estado'];
$col = 'A';
foreach ($headers as $h) {
    $sheet->setCellValue($col . '2', $h);
    $sheet->getStyle($col . '2')->getFont()->setBold(true)->setColor(new Color(Color::COLOR_WHITE));
    $sheet->getStyle($col . '2')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('004b61');
    $sheet->getStyle($col . '2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $col++;
}

// Datos
$row = 3;
$totalAccum = 0;
$numeroSucesivo = 1; // --- INICIALIZAR EL CONTADOR ---

foreach ($data as $d) {
    // Formato fecha
    $fecha = date('d/m/Y H:i', strtotime($d['fecha_registro']));
    
    // Extracción de datos
    $cui = $d['cui'] ? $d['cui'] : '-';
    $nro_exp = $d['nro_expediente'] ? $d['nro_expediente'] : '-';
    $desc = mb_strtoupper($d['descripcion_general'], 'UTF-8'); // --- FORZAR MAYÚSCULAS ---
    $monto = (float)$d['total_presupuesto'];
    $estado = mb_strtoupper($d['estado'], 'UTF-8'); // --- FORZAR MAYÚSCULAS ---

    $totalAccum += $monto;

    $sheet->setCellValue('A' . $row, $numeroSucesivo++); // --- MOSTRAR Nº SUCESIVO ---
    $sheet->setCellValueExplicit('B' . $row, $cui, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
    $sheet->setCellValueExplicit('C' . $row, $nro_exp, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
    $sheet->setCellValue('D' . $row, $desc);
    $sheet->setCellValue('E' . $row, $monto);
    $sheet->setCellValue('F' . $row, $fecha);
    $sheet->setCellValue('G' . $row, $estado);

    // Formato de moneda para monto
    $sheet->getStyle('E' . $row)->getNumberFormat()->setFormatCode('#,##0.00');

    // Centrar datos (Nº, CUI, EXP, FECHA, ESTADO)
    $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle('B' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle('C' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle('F' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle('G' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

    // Alinear descripción a la izquierda (por ser texto largo)
    $sheet->getStyle('D' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

    // Color alterno filas
    if ($row % 2 == 0) {
        $sheet->getStyle("A$row:G$row")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('f6fbfd');
    }

    $row++;
}

// Fila de Total Acumulado
$sheet->setCellValue('D' . $row, 'TOTAL ACUMULADO:');
$sheet->getStyle('D' . $row)->getFont()->setBold(true)->getColor()->setARGB('198754');
$sheet->getStyle('D' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

$sheet->setCellValue('E' . $row, $totalAccum);
$sheet->getStyle('E' . $row)->getFont()->setBold(true)->getColor()->setARGB('198754');
$sheet->getStyle('E' . $row)->getNumberFormat()->setFormatCode('#,##0.00');

$sheet->getStyle("A$row:G$row")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('e9f7df');

// Bordes para toda la tabla
$styleArray = [
    'borders' => [
        'allBorders' => [
            'borderStyle' => Border::BORDER_THIN,
            'color' => ['argb' => 'CCCCCC'],
        ],
    ],
    'alignment' => [
        'vertical' => Alignment::VERTICAL_CENTER,
    ],
];

$sheet->getStyle('A2:G' . $row)->applyFromArray($styleArray);

// Ajuste de anchos optimizado
$sheet->getColumnDimension('A')->setWidth(8);
$sheet->getColumnDimension('B')->setWidth(18);
$sheet->getColumnDimension('C')->setWidth(20);
$sheet->getColumnDimension('D')->setWidth(90);
$sheet->getColumnDimension('E')->setWidth(22);
$sheet->getColumnDimension('F')->setWidth(22);
$sheet->getColumnDimension('G')->setWidth(18);

$sheet->getStyle('D3:D' . ($row - 1))->getAlignment()->setWrapText(true);

// Descarga
$txtAnio = ($anio) ? "_".$anio : "";
$filename = "Reporte_Demandas_Presupuestales" . $txtAnio . "_SIG.xlsx";

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="' . $filename . '"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
