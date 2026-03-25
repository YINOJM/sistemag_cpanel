<?php
// controlador/reporte_unidades_excel.php
require '../vendor/autoload.php';
require_once '../modelo/conexion.php';
require_once '../modelo/UnidadesPoliciales.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

// Validar Sesión
session_start();
if (empty($_SESSION['id'])) {
    die("Acceso denegado.");
}

// Obtener datos
$modelo = new UnidadesPoliciales($conexion);
$data = $modelo->obtenerJerarquiaCompleta();

// Crear documento Excel
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle("Unidades Policiales");

// --- ESTILOS ---
$headerStyle = [
    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '006db3']], // Azul corporativo
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
];

$rowStyle = [
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
    'alignment' => ['vertical' => Alignment::VERTICAL_CENTER]
];

// --- ENCABEZADO INSTITUCIONAL ---
// Fila 1: Título principal
$sheet->setCellValue('A1', 'REPORTE GENERAL DE COMISARÍAS BÁSICAS PNP - ' . date('Y'));
$sheet->mergeCells('A1:H1');
$sheet->getStyle('A1')->applyFromArray([
    'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => 'FFFFFF']],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '006db3']],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER]
]);
$sheet->getRowDimension(1)->setRowHeight(30);

// Fila 2: Separador vacío
$sheet->getRowDimension(2)->setRowHeight(10);

// --- ENCABEZADO DE COLUMNAS (Fila 3) ---
$headers = ['N°', 'REGIÓN', 'DIVISIÓN', 'TIPO', 'NOMBRE DE LA UNIDAD', 'DEPARTAMENTO', 'PROVINCIA', 'DISTRITO'];
$sheet->fromArray([$headers], NULL, 'A3');
$sheet->getStyle('A3:H3')->applyFromArray($headerStyle);
$sheet->getRowDimension(3)->setRowHeight(25);

// --- DATOS ---
$row = 4;
$i = 1;

foreach ($data as $d) {
    // Alternar colores de fila
    if ($i % 2 == 0) {
        $sheet->getStyle("A$row:H$row")->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('F7F9FA');
    }

    $sheet->setCellValue('A' . $row, $i);
    $sheet->setCellValue('B' . $row, $d['nombre_region']);
    $sheet->setCellValue('C' . $row, $d['nombre_division']);
    $sheet->setCellValue('D' . $row, $d['tipo_unidad']);
    $sheet->setCellValue('E' . $row, $d['nombre_subunidad']);
    $sheet->setCellValue('F' . $row, $d['departamento']);
    $sheet->setCellValue('G' . $row, $d['provincia']);
    $sheet->setCellValue('H' . $row, $d['distrito']);

    // Estilos de celda
    $sheet->getStyle("A$row:H$row")->applyFromArray($rowStyle);
    
    // Alineación centrada para columnas específicas
    $sheet->getStyle("A$row")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle("D$row")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

    $row++;
    $i++;
}

// --- ANCHO DE COLUMNAS ---
$sheet->getColumnDimension('A')->setWidth(6);
$sheet->getColumnDimension('B')->setWidth(25);
$sheet->getColumnDimension('C')->setWidth(25);
$sheet->getColumnDimension('D')->setWidth(8);
$sheet->getColumnDimension('E')->setWidth(40); // Nombre unidad ancho
$sheet->getColumnDimension('F')->setWidth(20);
$sheet->getColumnDimension('G')->setWidth(20);
$sheet->getColumnDimension('H')->setWidth(20);

// Auto-filtro
$sheet->setAutoFilter("A3:H" . ($row - 1));

// Wrap text para nombres largos
$sheet->getStyle('B4:C' . ($row - 1))->getAlignment()->setWrapText(true);
$sheet->getStyle('E4:E' . ($row - 1))->getAlignment()->setWrapText(true);

// --- SALIDA ---
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="Reporte_Unidades_Policiales_' . date('d-m-Y') . '.xlsx"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
