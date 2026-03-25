<?php
// controlador/reporte_documento_excel.php
require_once '../vendor/autoload.php';
require_once '../modelo/DocumentoModelo.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;

// Validar Sesión
session_start();
if (empty($_SESSION['id'])) {
    die("Acceso denegado.");
}

$anio = $_GET['anio'] ?? date('Y');
$tipo = $_GET['tipo'] ?? '';
$fecha_inicio = $_GET['fecha_inicio'] ?? null;
$fecha_fin = $_GET['fecha_fin'] ?? null;

// Obtener datos
$modelo = new DocumentoModelo();
$data = $modelo->listarV2($anio, $tipo, $fecha_inicio, $fecha_fin);

// Crear Spreadsheet
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Título del reporte
if ($fecha_inicio && $fecha_fin) {
    $fInicioStr = date('d/m/Y', strtotime($fecha_inicio));
    $fFinStr = date('d/m/Y', strtotime($fecha_fin));
    $titulo = "REPORTE DE DOCUMENTOS ($fInicioStr AL $fFinStr)";
} else {
    $titulo = "REPORTE DE DOCUMENTOS REGISTRADOS - AÑO $anio";
}

if ($tipo) $titulo .= " ($tipo)";

$sheet->setCellValue('A1', $titulo);
$sheet->mergeCells('A1:I1');
$sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14)->setColor(new Color(Color::COLOR_WHITE));
$sheet->getStyle('A1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('00779E');
$sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

// Encabezados
$headers = ['N°', 'Documento', 'Fecha', 'HT / Ref', 'Clasif. (Se Solicita)', 'Destino', 'Asunto', 'Formulado Por', 'Observaciones'];
$col = 'A';
foreach ($headers as $h) {
    $sheet->setCellValue($col . '2', $h);
    $sheet->getStyle($col . '2')->getFont()->setBold(true)->setColor(new Color(Color::COLOR_WHITE));
    $sheet->getStyle($col . '2')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('005070');
    $sheet->getStyle($col . '2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $col++;
}

// Datos
$row = 3;
$i = 1;
foreach ($data as $d) {
    // Formato fecha
    $fecha = !empty($d['created_at']) ? date('d/m/Y', strtotime($d['created_at'])) : '-';
    
    // Limpieza de datos
    $numCompleto = $d['num_completo'];
    $ht = $d['ht'] ?? '-';
   $clasif  = mb_strtoupper($d['se_solicita'] ?? '', 'UTF-8');
$destino = mb_strtoupper($d['nombre_destino'] ?? '', 'UTF-8');
$asunto  = mb_strtoupper($d['asunto'] ?? '', 'UTF-8');
    $user = $d['usuario_formulador'] ?? '';
    $obs = $d['observaciones'] ?? '';

    $sheet->setCellValue('A' . $row, $i);
    $sheet->setCellValue('B' . $row, $numCompleto);
    $sheet->setCellValue('C' . $row, $fecha);
    $sheet->setCellValue('D' . $row, $ht);
    $sheet->setCellValue('E' . $row, $clasif);
    $sheet->setCellValue('F' . $row, $destino);
    $sheet->setCellValue('G' . $row, $asunto);
    $sheet->setCellValue('H' . $row, $user);
    $sheet->setCellValue('I' . $row, $obs);

    // Color alterno filas
    if ($row % 2 == 0) {
        $sheet->getStyle("A$row:I$row")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('F0F8FF');
    }

    $row++;
    $i++;
}

// Bordes para toda la tabla
$styleArray = [
    'borders' => [
        'allBorders' => [
            'borderStyle' => Border::BORDER_THIN,
            'color' => ['argb' => 'CCCCCC'],
        ],
    ],
    'alignment' => [
        'vertical' => Alignment::VERTICAL_CENTER,
    ],
];

$sheet->getStyle('A2:I' . ($row - 1))->applyFromArray($styleArray);

// 1. CONFIGURACIÓN DE ANCHOS FIJOS (Para un reporte compacto)
$sheet->getColumnDimension('A')->setWidth(6);   // N°
$sheet->getColumnDimension('B')->setWidth(23);  // Documento
$sheet->getColumnDimension('C')->setWidth(13);  // Fecha
$sheet->getColumnDimension('D')->setWidth(20);  // HT / Ref
$sheet->getColumnDimension('E')->setWidth(25);  // Clasif.
$sheet->getColumnDimension('F')->setWidth(25);  // Destino
$sheet->getColumnDimension('G')->setWidth(75);  // Asunto (Controlado)
$sheet->getColumnDimension('H')->setWidth(25);  // Formulado Por
$sheet->getColumnDimension('I')->setWidth(30);  // Observaciones

// 2. AJUSTE DE TEXTO Y ALINEACIÓN TOP (Maestro)
$rangoTotal = 'A3:I' . ($row - 1);
$sheet->getStyle($rangoTotal)->getAlignment()->setWrapText(true);
$sheet->getStyle($rangoTotal)->getAlignment()->setVertical(Alignment::VERTICAL_TOP);

// 3. DETALLES DE ALINEACIÓN HORIZONTAL
$sheet->getStyle('A3:A' . ($row - 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
$sheet->getStyle('C3:C' . ($row - 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
$sheet->getStyle('G3:G' . ($row - 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);



// Descarga
$filename = "Reporte_Documentos_$anio" . ($tipo ? "_$tipo" : "") . ".xlsx";

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="' . $filename . '"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
