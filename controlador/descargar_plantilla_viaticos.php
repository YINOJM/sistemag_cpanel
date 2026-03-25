<?php
// controlador/descargar_plantilla_viaticos.php
ob_start();
require_once __DIR__ . '/../modelo/conexion.php';
require_once __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

if (empty($_SESSION['id'])) {
    header("Location: ../index.php");
    exit();
}

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('RENDICIONES');

// Título Principal
$anio = defined('ANIO_FISCAL') ? ANIO_FISCAL : date('Y');
$sheet->setCellValue('A1', 'RELACION DE ADELANTO DE VIATICOS DE ENE-DIC ' . $anio);
$sheet->mergeCells('A1:S1');
$sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14)->getColor()->setARGB('FFFF0000');
$sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

// Encabezados de Columnas (Fila 2)
$headers = [
    'DNI',
    'CIP',
    'GRADO',
    'APELLIDOS Y NOMBRES',
    'LUGAR DE COMISION',
    'REGION',
    'DIVOPUS',
    'UNIDAD',
    'CUENTA DE AHORROS',
    'FECHA INICIO',
    'FECHA RETORNO',
    'LIQ.',
    'IGV',
    'DIAS',
    '1ER DEPOSITO',
    'SIAF',
    'PASAJES',
    'TOTAL DEPOSITOS'
];

$col = 'A';
foreach ($headers as $header) {
    $sheet->setCellValue($col . '2', $header);
    // Estilo de encabezados
    $sheet->getStyle($col . '2')->applyFromArray([
        'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF003666']],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
    ]);
    $sheet->getColumnDimension($col)->setAutoSize(true);
    $col++;
}

// Fila de ejemplo
$sheet->setCellValue('A3', '41591821');
$sheet->setCellValue('B3', '345865');
$sheet->setCellValue('C3', 'MAY.');
$sheet->setCellValue('D3', 'VIVANCO GOZZING, OMAR');
$sheet->setCellValue('E3', 'TRUJILLO');
$sheet->setCellValue('F3', 'REGION LIMA');
$sheet->setCellValue('G3', 'DIVOPUS CENTRO');
$sheet->setCellValue('H3', 'UHPM POTAO');
$sheet->setCellValue('I3', '4013210247');
$sheet->setCellValue('J3', '03/01/' . substr($anio, -2));
$sheet->setCellValue('K3', '31/01/' . substr($anio, -2));
$sheet->setCellValue('L3', '300');
$sheet->setCellValue('M3', '320.00');
$sheet->setCellValue('N3', '29');
$sheet->setCellValue('O3', '9280.00');
$sheet->setCellValue('P3', '51');
$sheet->setCellValue('Q3', '0.00');
$sheet->setCellValue('R3', '9280.00');

// Limpiar cualquier salida previa
if (ob_get_length()) ob_end_clean();

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="Plantilla_Rendiciones_' . $anio . '.xlsx"');
header('Cache-Control: max-age=0');
header('Pragma: public');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit();
