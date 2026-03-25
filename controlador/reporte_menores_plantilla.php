<?php
// reporte_menores_plantilla.php
declare(strict_types=1);

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

require_once __DIR__ . '/../vendor/autoload.php';

session_start();
if (empty($_SESSION['id']) || $_SESSION['rol'] !== 'Super Administrador') {
    die("No autorizado.");
}

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Plantilla Importación');

// Cabeceras exactas como en el sistema
$headers = [
    'Tipo de Contratacion', 
    'Objeto de la Contratacion', 
    'Área Usuaria', 
    'F. de Requerimiento', 
    'Fecha de OC/OS', 
    'Plazo Ejecucion (Dias)', 
    'F. Final de Ejecucion', 
    'Imp. Comprometido (S/)', 
    'Imp. Devengado (S/)', 
    'Imp. Girado (S/)', 
    'Estado',
    'Observaciones'
];

$sheet->fromArray($headers, null, 'A1');

// Estilos cabecera
$headerStyle = [
    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '006699']],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
];
$sheet->getStyle('A1:L1')->applyFromArray($headerStyle);
$sheet->getRowDimension(1)->setRowHeight(35);

// Ejemplo de data
$example = [
    'SERVICIOS', 
    'CONTRATACIÓN DE UN (01) CONTADOR PÚBLICO', 
    'ÁREA DE CONTABILIDAD', 
    '31/01/2026', 
    '03/02/2026', 
    '0', 
    '30/07/2026', 
    '36000.00', 
    '0.00', 
    '0.00', 
    'EN EJECUCION',
    'Registro de ejemplo'
];
$sheet->fromArray($example, null, 'A2');

// Anchos de columna
$sheet->getColumnDimension('A')->setWidth(20);
$sheet->getColumnDimension('B')->setWidth(50);
$sheet->getColumnDimension('C')->setWidth(30);
$sheet->getColumnDimension('D')->setWidth(20);
$sheet->getColumnDimension('E')->setWidth(20);
$sheet->getColumnDimension('F')->setWidth(20);
$sheet->getColumnDimension('G')->setWidth(20);
$sheet->getColumnDimension('H')->setWidth(20);
$sheet->getColumnDimension('I')->setWidth(20);
$sheet->getColumnDimension('J')->setWidth(20);
$sheet->getColumnDimension('K')->setWidth(20);
$sheet->getColumnDimension('L')->setWidth(40);

// Salida
$filename = 'plantilla_importacion_menores.xlsx';
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="'.$filename.'"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
