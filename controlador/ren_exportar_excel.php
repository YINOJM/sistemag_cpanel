<?php
// controlador/ren_exportar_excel.php
require_once __DIR__ . "/../modelo/conexion.php";

if (empty($_SESSION['id'])) {
    die("Acceso denegado.");
}

require_once __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Cell\DataType;

// 1. Capturar filtros de la URL (Mismos que usa el listado y el PDF)
$search = $_GET['q'] ?? '';
$estado = $_GET['estado'] ?? '';
$grupo  = $_GET['grupo'] ?? '';
$anio   = isset($_GET['anio']) ? (int)$_GET['anio'] : ANIO_FISCAL;

// 2. Construir el Filtro (Misma lógica que RendicionesControlador - listar)
$where = "WHERE anio_fiscal = $anio";
if (!empty($search)) {
    $s = $conexion->real_escape_string($search);
    $where .= " AND (dni LIKE '%$s%' OR cip LIKE '%$s%' OR apellidos_nombres LIKE '%$s%' OR lugar_comision LIKE '%$s%')";
}
if (!empty($estado)) {
    $e = $conexion->real_escape_string($estado);
    $where .= " AND estado_rendicion = '$e'";
}
if (!empty($grupo)) {
    $g = $conexion->real_escape_string($grupo);
    if ($g === 'LOTE INICIAL / OTROS') {
        $where .= " AND (grupo_importacion IS NULL OR grupo_importacion = '')";
    } else {
        $where .= " AND grupo_importacion LIKE '%$g%'";
    }
}

// 3. Ejecutar la consulta
$query = "SELECT * FROM ren_rendiciones $where ORDER BY fecha_registro DESC";
$res = $conexion->query($query);

// Crear documento Excel
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle("Rendiciones " . $anio);

// --- ESTILOS ---
$headerStyleMain = [
    'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => 'FFFFFF']],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '003666']], 
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
];

$columnHeaderStyle = [
    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0056a3']], 
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
];

$rowStyle = [
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
    'alignment' => ['vertical' => Alignment::VERTICAL_CENTER]
];

// --- ENCABEZADO INSTITUCIONAL ---
$sheet->setCellValue('A1', 'SISTEMA DE GESTIÓN PNP - REPORTE DE RENDICIONES DE CUENTAS');
$sheet->mergeCells('A1:O1');
$sheet->getStyle('A1')->applyFromArray($headerStyleMain);
$sheet->getRowDimension(1)->setRowHeight(35);

$sheet->setCellValue('A2', 'ESTADO: ' . ($estado ?: 'TODOS') . ' | LOTE: ' . ($grupo ?: 'TODOS') . ' | AÑO: ' . $anio);
$sheet->mergeCells('A2:O2');
$sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

// --- ENCABEZADO DE COLUMNAS ---
$headers = ['N°', 'DNI', 'CIP', 'PERSONAL', 'GRADO', 'REGIÓN', 'DIVISIÓN', 'UNIDAD', 'LUGAR', 'INICIO', 'RETORNO', 'TOTAL', 'ESTADO', 'LOTE/GRUPO', 'HT / REF'];
$sheet->fromArray([$headers], NULL, 'A4');
$sheet->getStyle('A4:O4')->applyFromArray($columnHeaderStyle);
$sheet->getRowDimension(4)->setRowHeight(25);

// --- DATOS ---
$rowPos = 5;
$i = 1;
$totalGral = 0;

if ($res && $res->num_rows > 0) {
    while ($d = $res->fetch_assoc()) {
        $totalGral += $d['total_depositado'];
        
        $sheet->setCellValue('A' . $rowPos, $i);
        $sheet->setCellValueExplicit('B' . $rowPos, $d['dni'], DataType::TYPE_STRING);
        $sheet->setCellValueExplicit('C' . $rowPos, $d['cip'], DataType::TYPE_STRING);
        $sheet->setCellValue('D' . $rowPos, mb_strtoupper($d['apellidos_nombres']));
        $sheet->setCellValue('E' . $rowPos, $d['grado']);
        $sheet->setCellValue('F' . $rowPos, $d['region_cache'] ?? '');
        $sheet->setCellValue('G' . $rowPos, $d['division_cache'] ?? '');
        $sheet->setCellValue('H' . $rowPos, $d['unidad']);
        $sheet->setCellValue('I' . $rowPos, $d['lugar_comision']);
        $sheet->setCellValue('J' . $rowPos, ($d['fecha_inicio'] && $d['fecha_inicio'] != '0000-00-00') ? date('d/m/Y', strtotime($d['fecha_inicio'])) : '-');
        $sheet->setCellValue('K' . $rowPos, ($d['fecha_retorno'] && $d['fecha_retorno'] != '0000-00-00') ? date('d/m/Y', strtotime($d['fecha_retorno'])) : '-');
        $sheet->setCellValue('L' . $rowPos, $d['total_depositado']);
        $sheet->setCellValue('M' . $rowPos, $d['estado_rendicion']);
        $sheet->setCellValue('N' . $rowPos, $d['grupo_importacion'] ?? '-');
        $sheet->setCellValue('O' . $rowPos, $d['ht_ref'] ?? '-');

        $sheet->getStyle("A$rowPos:O$rowPos")->applyFromArray($rowStyle);
        $sheet->getStyle('L' . $rowPos)->getNumberFormat()->setFormatCode('#,##0.00');
        
        if ($i % 2 == 0) {
            $sheet->getStyle("A$rowPos:O$rowPos")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F2F2F2');
        }

        $rowPos++;
        $i++;
    }

    // Fila de Total
    $sheet->setCellValue('K' . $rowPos, 'TOTAL GENERAL:');
    $sheet->setCellValue('L' . $rowPos, $totalGral);
    $sheet->getStyle("K$rowPos:L$rowPos")->getFont()->setBold(true);
    $sheet->getStyle('L' . $rowPos)->getNumberFormat()->setFormatCode('"S/. "#,##0.00');
    $sheet->getStyle("K$rowPos:L$rowPos")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('EEEEEE');
    $sheet->getStyle("K$rowPos:L$rowPos")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
}

// Ancho de columnas
$sheet->getColumnDimension('A')->setWidth(5);
$sheet->getColumnDimension('B')->setWidth(12);
$sheet->getColumnDimension('C')->setWidth(12);
$sheet->getColumnDimension('D')->setWidth(40);
$sheet->getColumnDimension('E')->setWidth(15);
$sheet->getColumnDimension('F')->setWidth(25);
$sheet->getColumnDimension('G')->setWidth(25);
$sheet->getColumnDimension('H')->setWidth(30);
$sheet->getColumnDimension('I')->setWidth(20);
$sheet->getColumnDimension('J')->setWidth(15);
$sheet->getColumnDimension('K')->setWidth(15);
$sheet->getColumnDimension('L')->setWidth(15);
$sheet->getColumnDimension('M')->setWidth(15);
$sheet->getColumnDimension('N')->setWidth(25);
$sheet->getColumnDimension('O')->setWidth(20);

// Auto-filtro
$sheet->setAutoFilter("A4:O" . ($rowPos - 1));

// --- SALIDA ---
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="Reporte_Rendiciones_' . date('dmY_His') . '.xlsx"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
