<?php
// controlador/reporte_inventario_excel.php
require '../vendor/autoload.php';
require_once '../modelo/InventarioModelo.php';

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

// Limpiar TODOS los niveles de buffer de salida previos para evitar corrupción
while (ob_get_level()) {
    ob_end_clean();
}

$anio = $_GET['anio'] ?? date('Y');
$idSubunidadGet = isset($_GET['id_subunidad']) && !empty($_GET['id_subunidad']) ? (int) $_GET['id_subunidad'] : null;

$rol = $_SESSION['rol'] ?? '';
$idOficinaSesion = $_SESSION['id_oficina'] ?? null;
$idSubunidadSesion = $_SESSION['id_subunidad'] ?? null;

// Lógica de filtrado idéntica al controlador principal
$idSubunidadFiltro = null;
$idOficinaFiltro = null;

if ($rol === 'Super Administrador' || $rol === 'Administrador') {
    $idSubunidadFiltro = $idSubunidadGet;
} else {
    $idSubunidadFiltro = $idSubunidadSesion;
    $idOficinaFiltro = $idOficinaSesion;
}

// Obtener datos
global $conexion;
$modelo = new InventarioModelo();
$data = $modelo->listar((int)$anio, $idOficinaFiltro, $idSubunidadFiltro);

// Crear documento Excel
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle("Anexo 07 - $anio");

// --- ESTILOS ---
$headerStyle = [
    'font' => ['bold' => true, 'color' => ['rgb' => '000000']],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'D9D9D9']], // Gris claro según norma (o similar)
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']]]
];

$rowStyle = [
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']]],
    'alignment' => ['vertical' => Alignment::VERTICAL_CENTER]
];

// --- TÍTULOS -- (Simular formato anexo)
$sheet->setCellValue('A1', 'ANEXO N° 07');
$sheet->mergeCells('A1:M1');
$sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
$sheet->getStyle('A1')->getFont()->setBold(true)->setSize(11);

$sheet->setCellValue('A2', 'FORMATO DE FICHA DE LEVANTAMIENTO DE INFORMACIÓN');
$sheet->mergeCells('A2:M2');
$sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
$sheet->getStyle('A2')->getFont()->setBold(true)->setSize(12);

// Obtener nombres para ubicación si hay filtro
$locText = "CONSOLIDADO GENERAL";
if ($idSubunidadFiltro) {
    require_once '../modelo/conexion.php';
    $resU = $conexion->query("SELECT s.nombre_subunidad, d.nombre_division, r.nombre_region 
                              FROM sub_unidades_policiales s
                              JOIN divisiones_policiales d ON s.id_division = d.id_division
                              JOIN regiones_policiales r ON d.id_region = r.id_region
                              WHERE s.id_subunidad = $idSubunidadFiltro");
    if ($resU && $u = $resU->fetch_assoc()) {
        $locText = $u['nombre_region'] . " / " . $u['nombre_division'] . " / " . $u['nombre_subunidad'];
    }
} else if ($idOficinaFiltro) {
    $locText = $_SESSION['nombre_region'] . " / " . $_SESSION['nombre_division'] . " / " . $_SESSION['nombre_subunidad'];
}

$sheet->setCellValue('A3', 'UBICACIÓN: ' . mb_strtoupper($locText, 'UTF-8'));
$sheet->mergeCells('A3:M3');
$sheet->getStyle('A3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
$sheet->getStyle('A3')->getFont()->setItalic(true)->setSize(9);

// --- ENCABEZADO DE COLUMNAS (Fila 4) ---
// Columnas: N°, CÓDIGO, DENOMINACIÓN, MARCA, MODELO, TIPO, COLOR, SERIE, DIMENSIONES, OTROS, SITUACIÓN, ESTADO, OBSERVACIÓN
$headers = [
    'N°', 
    'CÓDIGO', 
    'DENOMINACIÓN', 
    'MARCA', 
    'MODELO', 
    'TIPO', 
    'COLOR', 
    'SERIE', 
    'DIMENSIONES', 
    'OTROS', 
    'SITUACIÓN', 
    'ESTADO', 
    'OBSERVACIÓN'
];

$colStart = 'A';
$colEnd = 'M'; // A-M son 13 columnas

$sheet->fromArray([$headers], NULL, 'A4');
$sheet->getStyle("A4:{$colEnd}4")->applyFromArray($headerStyle);
$sheet->getRowDimension(4)->setRowHeight(30);

// --- DATOS ---
$row = 5;
$i = 1;

// Mapeo simple de estados para Excel (aunque mostraremos el texto completo o corto según preferencia, usaremos corto para seguir formato visual)
// Bueno (B), Regular (R), Malo (M), Chatarra (Ch), RAEE
$mapEstados = [
    'BUENO' => 'B', 'NUEVO' => 'B',
    'REGULAR' => 'R',
    'MALO' => 'M',
    'CHATARRA' => 'Ch',
    'RAEE' => 'RAEE'
];

foreach ($data as $d) {
    // Normalizar Situacion
    $sit = strtoupper($d['situacion'] ?? 'USO');
    $sitCode = ($sit === 'USO' || $sit === 'U') ? 'U' : 'D';

    // Normalizar Estado code
    $estadoFull = strtoupper($d['estado_bien']);
    $estadoCode = $mapEstados[$estadoFull] ?? $estadoFull;
    
    // Mapeo de campos
    $tipo = $d['tipo_bien'] ?? '';
    $color = $d['color'] ?? '';
    $dimensiones = $d['dimensiones'] ?? '';
    $otros = $d['otras_caracteristicas'] ?? '';

    $sheet->setCellValue('A' . $row, $i);
    $sheet->setCellValueExplicit('B' . $row, $d['codigo_inventario'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING); // Forzar texto para ceros a izquierda
    $sheet->setCellValue('C' . $row, $d['descripcion']);
    $sheet->setCellValue('D' . $row, $d['marca']);
    $sheet->setCellValue('E' . $row, $d['modelo']);
    $sheet->setCellValue('F' . $row, $tipo);
    $sheet->setCellValue('G' . $row, $color);
    $sheet->setCellValue('H' . $row, $d['serie']);
    $sheet->setCellValue('I' . $row, $dimensiones);
    $sheet->setCellValue('J' . $row, $otros);
    $sheet->setCellValue('K' . $row, $sitCode);
    $sheet->setCellValue('L' . $row, $estadoCode);
    $sheet->setCellValue('M' . $row, $d['observaciones']);

    // Aplicar estilos a la fila
    $sheet->getStyle("A$row:{$colEnd}$row")->applyFromArray($rowStyle);

    // Alineaciones
    $sheet->getStyle("A$row")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle("B$row")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle("F$row:M$row")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER); // Centrar atributos cortos
    
    // Alineación Izquierda para descripción y detalles largos
    $sheet->getStyle("C$row")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
    $sheet->getStyle("J$row")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

    $row++;
    $i++;
}

// --- ANCHO DE COLUMNAS ---
$sheet->getColumnDimension('A')->setWidth(5);
$sheet->getColumnDimension('B')->setWidth(15);
$sheet->getColumnDimension('C')->setWidth(45); // Descripción
$sheet->getColumnDimension('D')->setWidth(15);
$sheet->getColumnDimension('E')->setWidth(15);
$sheet->getColumnDimension('F')->setWidth(15); // Tipo
$sheet->getColumnDimension('G')->setWidth(10); // Color
$sheet->getColumnDimension('H')->setWidth(15); // Serie
$sheet->getColumnDimension('I')->setWidth(15); // Dimensiones
$sheet->getColumnDimension('J')->setWidth(25); // Otros
$sheet->getColumnDimension('K')->setWidth(8); // Sit
$sheet->getColumnDimension('L')->setWidth(8); // Est
$sheet->getColumnDimension('M')->setWidth(25); // Obs

// Wrap text
$sheet->getStyle('C5:C' . ($row - 1))->getAlignment()->setWrapText(true);
$sheet->getStyle('J5:J' . ($row - 1))->getAlignment()->setWrapText(true);
$sheet->getStyle('M5:M' . ($row - 1))->getAlignment()->setWrapText(true);

// --- SALIDA ---
$filename = "Anexo07_Inventario_" . $anio . ".xlsx";

// Headers para forzar descarga y compatibilidad
header('Content-Description: File Transfer');
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Transfer-Encoding: binary');
header('Expires: 0');
header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
header('Pragma: public');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
?>
