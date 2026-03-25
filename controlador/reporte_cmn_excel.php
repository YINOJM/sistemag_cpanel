<?php
// reporte_cmn_excel.php
require_once __DIR__ . "/../modelo/conexion.php";
require_once __DIR__ . "/../vendor/autoload.php";

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

// Asegurar inicio de sesión
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificación de seguridad
if (empty($_SESSION['id']) || $_SESSION['rol'] !== 'Super Administrador') {
    die("Acceso denegado.");
}

$anio = isset($_GET['anio']) ? (int)$_GET['anio'] : date('Y');
$search_query = isset($_GET['q']) ? $_GET['q'] : '';

// Crear objeto Spreadsheet
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle("CMN " . $anio);

// 1. TÍTULO PRINCIPAL
$sheet->mergeCells('A1:L1');
$sheet->setCellValue('A1', "CONSOLIDADO DE RESPONSABLES LOGÍSTICOS - SIAF WEB CMN " . $anio);
$sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
$sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

// 2. CABECERAS
$headers = [
    'N°', 'GRADO', 'APELLIDOS', 'NOMBRES', 'DNI', 'CIP', 
    'CORREO ELECTRÓNICO', 'CELULAR', 'REGIÓN POLICIAL', 
    'DIVOPUS', 'SUB-UNIDAD ESPECÍFICA', 'FECHA REGISTRO'
];

$columnIndex = 'A';
foreach ($headers as $header) {
    $sheet->setCellValue($columnIndex . '2', $header);
    $sheet->getStyle($columnIndex . '2')->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');
    $sheet->getStyle($columnIndex . '2')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('003666');
    $sheet->getStyle($columnIndex . '2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $columnIndex++;
}

// 3. CONSULTA DE DATOS CON FILTROS (Actualizado para incluir DIVOPUS y Región)
$where = "WHERE anio_proceso = $anio AND archivo_pdf IS NOT NULL";
if (!empty($search_query)) {
    $q = trim($conexion->real_escape_string($search_query));
    $where .= " AND (apellidos LIKE '%$q%' 
                OR nombres LIKE '%$q%' 
                OR dni LIKE '%$q%' 
                OR cip LIKE '%$q%' 
                OR sub_unidad_especifica LIKE '%$q%'
                OR divpol_divopus LIKE '%$q%'
                OR region_policial LIKE '%$q%')";
}


$sql = "SELECT * FROM cmn_responsables $where ORDER BY region_policial, divpol_divopus, apellidos ASC";
$result = $conexion->query($sql);

$rowNum = 3;
$i = 1;
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $sheet->setCellValue('A' . $rowNum, $i++);
        $sheet->setCellValue('B' . $rowNum, $row['grado']);
        $sheet->setCellValue('C' . $rowNum, $row['apellidos']);
        $sheet->setCellValue('D' . $rowNum, $row['nombres']);
        
        // DNI y CIP como texto para que no se pierdan los ceros
        $sheet->setCellValueExplicit('E' . $rowNum, $row['dni'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
        $sheet->setCellValueExplicit('F' . $rowNum, $row['cip'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
        
        $sheet->setCellValue('G' . $rowNum, $row['correo']);
        $sheet->setCellValue('H' . $rowNum, $row['celular']);
        $sheet->setCellValue('I' . $rowNum, $row['region_policial']);
        $sheet->setCellValue('J' . $rowNum, $row['divpol_divopus']);
        $sheet->setCellValue('K' . $rowNum, $row['sub_unidad_especifica']);
        $sheet->setCellValue('L' . $rowNum, !empty($row['fecha_registro']) ? date('d/m/Y H:i', strtotime($row['fecha_registro'])) : '-');
        $rowNum++;
    }
}

// 4. ESTILOS FINALES
foreach (range('A', 'L') as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}
if ($rowNum > 3) {
    $sheet->getStyle('A2:L' . ($rowNum - 1))->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
}

// 5. DESCARGA (.xlsx real)
$filename = "CONSOLIDADO_CMN_SIAF_" . $anio . "_" . date('Ymd_His') . ".xlsx";

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="' . $filename . '"');
header('Cache-Control: max-age=0');
header('Pragma: public');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
