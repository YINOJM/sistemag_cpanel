<?php
ob_start();
ini_set('display_errors', '0');
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE & ~E_WARNING);

// controlador/reporte_personal_excel.php
require_once __DIR__ . "/../modelo/conexion.php";

if (empty($_SESSION['id'])) {
    die("Acceso denegado.");
}

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../modelo/PersonalModelo.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

// Obtener datos
$modelo = new PersonalModelo();
$data = $modelo->listar();

// Crear documento Excel
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle("Padrón Personal");

// --- ESTILOS ---
$headerStyleMain = [
    'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => 'FFFFFF']],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '006db3']], // Azul corporativo
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
];

$headerStyleSub = [
    'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => 'FFFFFF']],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '005a92']], // Azul un poco más oscuro
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
];

$columnHeaderStyle = [
    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '006db3']], 
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
];

$rowStyle = [
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
    'alignment' => ['vertical' => Alignment::VERTICAL_CENTER]
];

// --- ENCABEZADO INSTITUCIONAL ---
// Fila 1: Institución
$sheet->setCellValue('A1', 'UNIDAD DE ADMINISTRACIÓN - UE 009 REGIÓN POLICIAL LIMA');
$sheet->mergeCells('A1:J1');
$sheet->getStyle('A1')->applyFromArray($headerStyleMain);
$sheet->getRowDimension(1)->setRowHeight(30);

// Fila 2: Título del Reporte
$sheet->setCellValue('A2', 'PADRÓN GENERAL DE PERSONAL POLICIAL - ' . date('Y'));
$sheet->mergeCells('A2:J2');
$sheet->getStyle('A2')->applyFromArray($headerStyleSub);
$sheet->getRowDimension(2)->setRowHeight(25);

// Fila 3: Separador vacío
$sheet->getRowDimension(3)->setRowHeight(10);

// --- ENCABEZADO DE COLUMNAS (Fila 4) ---
$headers = ['N°', 'GRADO', 'C.I.P.', 'DNI', 'APELLIDOS Y NOMBRES', 'UNIDAD / SUB-UNIDAD', 'CARGO', 'FUNCION', 'SIT. ESPECIAL', 'ESTADO'];
$sheet->fromArray([$headers], NULL, 'A4');
$sheet->getStyle('A4:J4')->applyFromArray($columnHeaderStyle);
$sheet->getRowDimension(4)->setRowHeight(25);

// --- DATOS ---
$row = 5;
$i = 1;

foreach ($data as $d) {
    // Alternar colores de fila
    if ($i % 2 == 0) {
        $sheet->getStyle("A$row:J$row")->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('F7F9FA');
    }

    $sheet->setCellValue('A' . $row, $i);
    $sheet->setCellValue('B' . $row, $d['nombre_grado'] ?? '');
    $sheet->setCellValueExplicit('C' . $row, $d['cip'] ?? '', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING); // Forzar texto
    $sheet->setCellValueExplicit('D' . $row, $d['dni'] ?? '', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING); // Forzar texto
    $sheet->setCellValue('E' . $row, ($d['apellidos'] ?? '') . ' ' . ($d['nombres'] ?? ''));
    $sheet->setCellValue('F' . $row, $d['nombre_subunidad'] ?? '');
    $sheet->setCellValue('G' . $row, $d['cargo'] ?? '');
    $sheet->setCellValue('H' . $row, $d['funcion_horario'] ?? '');
    $sheet->setCellValue('I' . $row, $d['situacion_especial'] ?? '');
    $sheet->setCellValue('J' . $row, $d['estado'] ?? 'Activo');

    // Estilos de celda
    $sheet->getStyle("A$row:J$row")->applyFromArray($rowStyle);
    
    // Alineación centrada para columnas específicas
    $sheet->getStyle("A$row")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle("C$row")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle("D$row")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle("J$row")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

    $row++;
    $i++;
}

// --- ANCHO DE COLUMNAS ---
$sheet->getColumnDimension('A')->setWidth(6);
$sheet->getColumnDimension('B')->setWidth(15);
$sheet->getColumnDimension('C')->setWidth(12);
$sheet->getColumnDimension('D')->setWidth(12);
$sheet->getColumnDimension('E')->setWidth(40); // Nombres
$sheet->getColumnDimension('F')->setWidth(35); // Unidad
$sheet->getColumnDimension('G')->setWidth(25); // Cargo
$sheet->getColumnDimension('H')->setWidth(25); // Funcion
$sheet->getColumnDimension('I')->setWidth(20); // Sit. Esp
$sheet->getColumnDimension('J')->setWidth(12); // Estado

// Auto-filtro
$sheet->setAutoFilter("A4:J" . ($row - 1));

// Wrap text para textos largos
$sheet->getStyle('E5:H' . ($row - 1))->getAlignment()->setWrapText(true);

// --- SALIDA ---
$filename = 'Reporte_Personal_Policial_' . date('d-m-Y') . '.xlsx';

while (ob_get_level() > 0) {
    ob_end_clean();
}

if (!headers_sent()) {
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="' . $filename . '"');
    header('Cache-Control: max-age=0');
}

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
