<?php
// controlador/descargar_plantilla_personal.php
require_once __DIR__ . "/../modelo/conexion.php";

if (empty($_SESSION['id'])) {
    die("Acceso denegado.");
}

require_once __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;

// 1. Obtener Datos Maestros para Listas
$grados = [];
$resG = $conexion->query("SELECT nombre_grado FROM mae_grados WHERE activo = 1 ORDER BY id_grado ASC, nombre_grado ASC");
while($r = $resG->fetch_assoc()) $grados[] = $r['nombre_grado'];

$subunidades = [];
$resS = $conexion->query("SELECT s.nombre_subunidad FROM sub_unidades_policiales s WHERE s.estado = 1 ORDER BY s.nombre_subunidad ASC");
while($r = $resS->fetch_assoc()) $subunidades[] = $r['nombre_subunidad'];

// 2. Crear Excel
$spreadsheet = new Spreadsheet();

// --- HOJA 1: CARGA DE DATOS ---
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('CARGA_PERSONAL');

// Encabezados
$headers = ['GRADO (Exacto)', 'CIP (Obligatorio)', 'DNI', 'APELLIDOS', 'NOMBRES', 'UNIDAD / SUB-UNIDAD (Exacto)'];
$sheet->fromArray($headers, NULL, 'A1');

// Estilo Encabezado
$headerStyle = [
    'font' => ['bold' => true, 'color' => ['argb' => Color::COLOR_WHITE]],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF00779E']],
    'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
];
$sheet->getStyle('A1:F1')->applyFromArray($headerStyle);

// Anchos de columna
$sheet->getColumnDimension('A')->setWidth(25);
$sheet->getColumnDimension('B')->setWidth(15);
$sheet->getColumnDimension('C')->setWidth(15);
$sheet->getColumnDimension('D')->setWidth(25);
$sheet->getColumnDimension('E')->setWidth(25);
$sheet->getColumnDimension('F')->setWidth(40);

// --- HOJA 2: REFERENCIAS (OCULTA O VISIBLE PARA COPIAR) ---
$refSheet = $spreadsheet->createSheet();
$refSheet->setTitle('CATALOGOS_SISTEMA');
$refSheet->setCellValue('A1', 'LISTA DE GRADOS VÁLIDOS');
$refSheet->setCellValue('B1', 'LISTA DE UNIDADES VÁLIDAS');
$refSheet->getStyle('A1:B1')->getFont()->setBold(true);

// Llenar catálogos
$row = 2;
foreach($grados as $g) {
    $refSheet->setCellValue('A' . $row, $g);
    $row++;
}
$maxRowGrados = $row - 1;

$row = 2;
foreach($subunidades as $s) {
    $refSheet->setCellValue('B' . $row, $s);
    $row++;
}
$maxRowUnidades = $row - 1;

// Ajustar ancho hoja 2
$refSheet->getColumnDimension('A')->setAutoSize(true);
$refSheet->getColumnDimension('B')->setAutoSize(true);

// --- VALIDACION DE DATOS EN HOJA 1 (DROPDOWNS) ---
// Validacion Grados (Columna A, filas 2 a 1000)
$validation = $sheet->getCell('A2')->getDataValidation();
$validation->setType(DataValidation::TYPE_LIST);
$validation->setErrorStyle(DataValidation::STYLE_INFORMATION);
$validation->setAllowBlank(false);
$validation->setShowInputMessage(true);
$validation->setShowErrorMessage(true);
$validation->setShowDropDown(true);
$validation->setErrorTitle('Valor no válido');
$validation->setError('Seleccione un grado de la lista.');
$validation->setFormula1('CATALOGOS_SISTEMA!$A$2:$A$' . $maxRowGrados);
// Clonar validación hasta fila 100
for ($i = 3; $i <= 1000; $i++) {
    $sheet->getCell("A$i")->setDataValidation(clone $validation);
}

// Validacion Unidades (Columna F, filas 2 a 1000)
// Nota: Excel tiene límite de caracteres para listas directas, pero referenciando rango funciona mejor.
$validationU = $sheet->getCell('F2')->getDataValidation();
$validationU->setType(DataValidation::TYPE_LIST);
$validationU->setErrorStyle(DataValidation::STYLE_WARNING); // Warning para permitir pegar si coincide
$validationU->setAllowBlank(true);
$validationU->setShowDropDown(true);
$validationU->setFormula1('CATALOGOS_SISTEMA!$B$2:$B$' . $maxRowUnidades);

for ($i = 3; $i <= 1000; $i++) {
    $sheet->getCell("F$i")->setDataValidation(clone $validationU);
}

// Descargar
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="Plantilla_Carga_Personal_SIG.xlsx"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
