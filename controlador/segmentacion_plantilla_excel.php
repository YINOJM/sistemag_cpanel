<?php
// controlador/segmentacion_plantilla_excel.php
// IMPORTANTE: NO DESTRUYE SISTEMA EXISTENTE - Inyección quirúrgica
require_once __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Datos Importacion');

// Fila 1 a 3: Instrucciones
$sheet->mergeCells('A1:J1');
$sheet->mergeCells('A2:J2');
$sheet->mergeCells('A3:J3');

$sheet->setCellValue('A1', 'PLANTILLA PARA IMPORTACIÓN DE SEGMENTACIÓN LEY 32069');
$sheet->setCellValue('A2', 'ADVERTENCIA: No modifique los encabezados originales (Fila 5). Inicie la carga de datos estrictamente a partir de la Fila 6.');
$sheet->setCellValue('A3', 'IMPORTANTE: Valores como "SÍ/NO" deben ser exactos para evitar errores en la importación. Todos los datos numéricos de Cuantía deben estar sin comas (,).');

$sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
$sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
$sheet->getStyle('A1:A3')->getFont()->getColor()->setARGB(Color::COLOR_DARKRED);

// Cabeceras en Fila 5
$cabeceras = [
    'A5' => 'N° REF PAC',
    'B5' => 'CMN',
    'C5' => 'AÑO',
    'D5' => 'OBJETO CONTRATACIÓN',
    'E5' => 'TIPO PROCESO',
    'F5' => 'DESCRIPCIÓN',
    'G5' => 'CUANTÍA (S/.)',
    'H5' => 'DECLARADO DESIERTO?',
    'I5' => 'MERCADO LIMITADO?',
    'J5' => 'PROGRAMADO (1=Sí/0=No)'
];

foreach ($cabeceras as $cel => $val) {
    $sheet->setCellValue($cel, $val);
}

// Estilos Cabecera (Turquesa / Verde Azulado)
$styleArray = [
    'font' => [
        'bold' => true,
        'color' => ['argb' => Color::COLOR_WHITE],
    ],
    'fill' => [
        'fillType' => Fill::FILL_SOLID,
        'startColor' => ['argb' => 'FF007F9E'], // Turquesa corporativo
    ],
    'borders' => [
        'allBorders' => [
            'borderStyle' => Border::BORDER_THIN,
            'color' => ['argb' => Color::COLOR_BLACK],
        ],
    ],
    'alignment' => [
        'horizontal' => Alignment::HORIZONTAL_CENTER,
        'vertical' => Alignment::VERTICAL_CENTER,
    ],
];

$sheet->getStyle('A5:J5')->applyFromArray($styleArray);
$sheet->getRowDimension(5)->setRowHeight(25);

// Anchos de columna
$sheet->getColumnDimension('A')->setWidth(15);
$sheet->getColumnDimension('B')->setWidth(15);
$sheet->getColumnDimension('C')->setWidth(10);
$sheet->getColumnDimension('D')->setWidth(25);
$sheet->getColumnDimension('E')->setWidth(30);
$sheet->getColumnDimension('F')->setWidth(40);
$sheet->getColumnDimension('G')->setWidth(18);
$sheet->getColumnDimension('H')->setWidth(20);
$sheet->getColumnDimension('I')->setWidth(20);
$sheet->getColumnDimension('J')->setWidth(25);

// Ajustar formato para datos 
$sheet->getStyle('G6:G1000')->getNumberFormat()->setFormatCode('#,##0.00'); // Cuantía
$sheet->getStyle('C6:C1000')->getNumberFormat()->setFormatCode('0'); // Año

// Headers HTTP para descarga
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="Plantilla_Segmentacion_' . date('Ymd_His') . '.xlsx"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
