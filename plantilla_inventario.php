<?php
// plantilla_inventario.php
// Script para generar y descargar plantilla Excel de inventario

// Verificar que PhpSpreadsheet esté instalado
if (!file_exists(__DIR__ . '/vendor/autoload.php')) {
    die('PhpSpreadsheet no está instalado. Ejecute: composer require phpoffice/phpspreadsheet');
}

require_once __DIR__ . '/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

// Crear nuevo Spreadsheet
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Configurar título
$sheet->setCellValue('A1', 'ANEXO N° 1');
$sheet->mergeCells('A1:M1');
$sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
$sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

// Configurar subtítulo
$sheet->setCellValue('A2', 'INVENTARIO FISICO DE BIENES - OFICINA DE PROGRAMACIÓN - REGPOL LIMA - 2026');
$sheet->mergeCells('A2:M2');
$sheet->getStyle('A2')->getFont()->setBold(true)->setSize(12);
$sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
$sheet->getStyle('A2')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFFF00');

// Fila 3 vacía
$sheet->getRowDimension(3)->setRowHeight(5);

// Encabezados de columnas (Fila 4)
$headers = [
    'A4' => 'ITEM',
    'B4' => 'CÓDIGO INVENTARIO',
    'C4' => 'DESCRIPCIÓN DEL BIEN',
    'D4' => 'MARCA',
    'E4' => 'SERIE',
    'F4' => 'MODELO',
    'G4' => 'DISCO SÓLIDO',
    'H4' => 'DISCO DURO',
    'I4' => 'ESTADO DEL BIEN',
    'J4' => 'COLOR',
    'K4' => 'CANTIDAD',
    'L4' => 'OBSERVACIONES',
    'M4' => 'USUARIO'
];

foreach ($headers as $cell => $value) {
    $sheet->setCellValue($cell, $value);
}

// Estilo de encabezados
$headerStyle = [
    'font' => [
        'bold' => true,
        'color' => ['argb' => 'FFFFFFFF'],
        'size' => 10
    ],
    'fill' => [
        'fillType' => Fill::FILL_SOLID,
        'startColor' => ['argb' => 'FF4472C4']
    ],
    'alignment' => [
        'horizontal' => Alignment::HORIZONTAL_CENTER,
        'vertical' => Alignment::VERTICAL_CENTER
    ],
    'borders' => [
        'allBorders' => [
            'borderStyle' => Border::BORDER_THIN,
            'color' => ['argb' => 'FF000000']
        ]
    ]
];

$sheet->getStyle('A4:M4')->applyFromArray($headerStyle);
$sheet->getRowDimension(4)->setRowHeight(30);

// Datos de ejemplo (Fila 5 en adelante)
$ejemplos = [
    [1, '000001', 'COMPUTADORA ALL IN ONE (TODO EN UNO)', 'LENOVO', 'MP2163V9', 'FVEY00NKLD', '250 GB', '1 TB', 'NUEVO', 'NEGRO', 1, 'INCLUYE TECLADO Y MOUSE DE LA MARCA DELL', 'S1 OMAR JARA'],
    [2, '000002', 'IMPRESORA MULTIFUNCION', 'BROTHER', 'U64209-K8N091945', 'MFC-L5900DW', '', '', 'REGULAR', 'NEGRO', 1, '', 'ING. IVAN'],
    [3, '000003', 'MESA DE MELAMINE', 'S/N', '', '', '', '', 'REGULAR', 'BEIGE CLARO', 1, '', ''],
    [4, '000004', 'SILLON GIRATORIO DE METAL', 'S/N', '', '', '', '', 'REGULAR', 'NEGRO', 1, '', ''],
];

$row = 5;
foreach ($ejemplos as $ejemplo) {
    $col = 'A';
    foreach ($ejemplo as $value) {
        $sheet->setCellValue($col . $row, $value);
        $col++;
    }
    $row++;
}

// Estilo de datos de ejemplo
$dataStyle = [
    'borders' => [
        'allBorders' => [
            'borderStyle' => Border::BORDER_THIN,
            'color' => ['argb' => 'FFD3D3D3']
        ]
    ],
    'alignment' => [
        'vertical' => Alignment::VERTICAL_CENTER
    ]
];

$sheet->getStyle('A5:M8')->applyFromArray($dataStyle);

// Ajustar anchos de columna
$sheet->getColumnDimension('A')->setWidth(8);
$sheet->getColumnDimension('B')->setWidth(18);
$sheet->getColumnDimension('C')->setWidth(35);
$sheet->getColumnDimension('D')->setWidth(15);
$sheet->getColumnDimension('E')->setWidth(18);
$sheet->getColumnDimension('F')->setWidth(18);
$sheet->getColumnDimension('G')->setWidth(15);
$sheet->getColumnDimension('H')->setWidth(15);
$sheet->getColumnDimension('I')->setWidth(18);
$sheet->getColumnDimension('J')->setWidth(15);
$sheet->getColumnDimension('K')->setWidth(12);
$sheet->getColumnDimension('L')->setWidth(40);
$sheet->getColumnDimension('M')->setWidth(25);

// Agregar nota informativa
$sheet->setCellValue('A10', 'IMPORTANTE:');
$sheet->getStyle('A10')->getFont()->setBold(true)->getColor()->setARGB('FFFF0000');
$sheet->setCellValue('A11', '1. Los datos deben empezar en la FILA 5 (después de los encabezados)');
$sheet->setCellValue('A12', '2. Campos obligatorios: CÓDIGO INVENTARIO, DESCRIPCIÓN, ESTADO DEL BIEN');
$sheet->setCellValue('A13', '3. Estado del bien: NUEVO, REGULAR o MALO');
$sheet->setCellValue('A14', '4. Para mobiliario, dejar vacíos los campos DISCO SÓLIDO y DISCO DURO');
$sheet->setCellValue('A15', '5. Puede eliminar las filas de ejemplo (5-8) y agregar sus propios datos');

$sheet->getStyle('A11:A15')->getFont()->setItalic(true)->setSize(9);

// Configurar propiedades del documento
$spreadsheet->getProperties()
    ->setCreator('Sistema de Inventario')
    ->setTitle('Plantilla de Inventario')
    ->setSubject('Plantilla para importación masiva')
    ->setDescription('Plantilla Excel para importar bienes al sistema de inventario');

// Generar archivo y forzar descarga
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="Plantilla_Inventario_2026.xlsx"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
