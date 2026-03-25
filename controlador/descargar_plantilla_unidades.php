<?php
/**
 * Generador de Plantilla Excel para Importación de Unidades Policiales
 * Sistema Integrado de Gestión - UE009 DIRTEPOL LIMA
 */

session_start();

// Verificar permisos
if (empty($_SESSION['nombre']) || ($_SESSION['rol'] !== 'Administrador' && $_SESSION['rol'] !== 'Super Administrador')) {
    header('Location: ../vista/inicio.php');
    exit;
}

require_once __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Font;

// Crear nuevo documento
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Unidades Policiales');

// Definir encabezados
$headers = [
    'A1' => 'DEPARTAMENTO',
    'B1' => 'PROVINCIA',
    'C1' => 'DISTRITO',
    'D1' => 'REGPOL',
    'E1' => 'DIVPOL/DIVOPUS',
    'F1' => 'UNIDAD POLICIAL',
    'G1' => 'TIPO'
];

// Aplicar encabezados
foreach ($headers as $cell => $value) {
    $sheet->setCellValue($cell, $value);
}

// Estilo para encabezados
$headerStyle = [
    'font' => [
        'bold' => true,
        'color' => ['rgb' => 'FFFFFF'],
        'size' => 12
    ],
    'fill' => [
        'fillType' => Fill::FILL_SOLID,
        'startColor' => ['rgb' => '006DB3']
    ],
    'alignment' => [
        'horizontal' => Alignment::HORIZONTAL_CENTER,
        'vertical' => Alignment::VERTICAL_CENTER
    ],
    'borders' => [
        'allBorders' => [
            'borderStyle' => Border::BORDER_THIN,
            'color' => ['rgb' => '000000']
        ]
    ]
];

$sheet->getStyle('A1:G1')->applyFromArray($headerStyle);

// Ajustar ancho de columnas
$sheet->getColumnDimension('A')->setWidth(15); // DEPARTAMENTO
$sheet->getColumnDimension('B')->setWidth(15); // PROVINCIA
$sheet->getColumnDimension('C')->setWidth(20); // DISTRITO
$sheet->getColumnDimension('D')->setWidth(15); // REGPOL
$sheet->getColumnDimension('E')->setWidth(20); // DIVPOL/DIVOPUS
$sheet->getColumnDimension('F')->setWidth(35); // COMISARÍA
$sheet->getColumnDimension('G')->setWidth(12); // TIPO

// Datos de ejemplo
$ejemplos = [
    ['LIMA', 'LIMA', 'PUENTE PIEDRA', 'REGPOL LIMA', 'DIVPOL NORTE 1', 'COMISARÍA PUENTE PIEDRA', 'COMISARÍA'],
    ['LIMA', 'LIMA', 'COMAS', 'REGPOL LIMA', 'DIVPOL NORTE 1', 'COMISARÍA COMAS', 'COMISARÍA'],
    ['LIMA', 'LIMA', 'SAN JUAN DE LURIGANCHO', 'REGPOL LIMA', 'DIVPOL ESTE 1', 'COMISARÍA SAN JUAN', 'COMISARÍA'],
    ['CALLAO', 'CALLAO', 'CALLAO', 'CALLAO', 'DIVOPUS 01', 'CPNP CALLAO', 'A'],
    ['CALLAO', 'CALLAO', 'LA PUNTA', 'CALLAO', 'DIVOPUS 01', 'CPNP LA PUNTA', 'B']
];

// Agregar datos de ejemplo
$row = 2;
foreach ($ejemplos as $ejemplo) {
    $col = 'A';
    foreach ($ejemplo as $valor) {
        $sheet->setCellValue($col . $row, $valor);
        $col++;
    }
    $row++;
}

// Estilo para datos de ejemplo
$dataStyle = [
    'alignment' => [
        'horizontal' => Alignment::HORIZONTAL_LEFT,
        'vertical' => Alignment::VERTICAL_CENTER
    ],
    'borders' => [
        'allBorders' => [
            'borderStyle' => Border::BORDER_THIN,
            'color' => ['rgb' => 'CCCCCC']
        ]
    ]
];

$sheet->getStyle('A2:G' . ($row - 1))->applyFromArray($dataStyle);

// Agregar altura a las filas
$sheet->getRowDimension(1)->setRowHeight(25);
for ($i = 2; $i < $row; $i++) {
    $sheet->getRowDimension($i)->setRowHeight(20);
}

// Agregar nota informativa
$sheet->setCellValue('A' . ($row + 2), 'INSTRUCCIONES:');
$sheet->getStyle('A' . ($row + 2))->getFont()->setBold(true)->setSize(11);

$instrucciones = [
    '• Las filas 2-6 son ejemplos. Puedes eliminarlas y agregar tus datos reales.',
    '• DEPARTAMENTO: Nombre del departamento (ej: LIMA, CALLAO, AREQUIPA)',
    '• PROVINCIA: Nombre de la provincia',
    '• DISTRITO: Nombre del distrito',
    '• REGPOL: Región Policial (ej: REGPOL LIMA, CALLAO)',
    '• DIVPOL/DIVOPUS: División Policial (ej: DIVPOL NORTE 1, DIVOPUS 01)',
    '• UNIDAD POLICIAL: Nombre completo de la unidad (comisaría, jefatura, departamento, etc.)',
    '• TIPO: Tipo de unidad (COMISARÍA, JEFATURA, DEPARTAMENTO, A, B, etc.) - Opcional'
];

$noteRow = $row + 3;
foreach ($instrucciones as $instruccion) {
    $sheet->setCellValue('A' . $noteRow, $instruccion);
    $sheet->getStyle('A' . $noteRow)->getFont()->setSize(9)->getColor()->setRGB('666666');
    $sheet->mergeCells('A' . $noteRow . ':G' . $noteRow);
    $noteRow++;
}

// Configurar para descarga
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="Plantilla_Unidades_Policiales_' . date('Y-m-d') . '.xlsx"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
?>