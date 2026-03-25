<?php
// controlador/DescargarPlantillaControlador.php
require_once __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

session_start();
if (empty($_SESSION['id'])) {
    http_response_code(401);
    exit('No autorizado');
}

// Crear nuevo documento Excel
$spreadsheet = new Spreadsheet();

// Configurar propiedades del documento
$spreadsheet->getProperties()
    ->setCreator("Sistema de Gestión Documental")
    ->setTitle("Plantilla de Importación de Documentos")
    ->setSubject("Plantilla Excel")
    ->setDescription("Plantilla para importar documentos al sistema");

// Cargar tipos de documento desde configuración
$tiposDocumento = require_once __DIR__ . '/../config/tipos_documento.php';

// Preparar hojas para el Excel
$hojasExcel = [];
foreach ($tiposDocumento as $codigo => $nombre) {
    // Convertir código a nombre de hoja válido (sin caracteres especiales)
    $nombreHoja = str_replace(['Ó', 'Í', 'Á', 'É', 'Ú'], ['O', 'I', 'A', 'E', 'U'], strtoupper($nombre));
    $nombreHoja = str_replace(' ', '_', $nombreHoja);
    $nombreHoja = substr($nombreHoja, 0, 31); // Excel limita a 31 caracteres
    $hojasExcel[$nombreHoja] = $nombre;
}

$primeraHoja = true;
foreach ($hojasExcel as $nombreHoja => $nombreTipo) {
    if ($primeraHoja) {
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle($nombreHoja);
        $primeraHoja = false;
    } else {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle($nombreHoja);
    }

    // Encabezados
    $encabezados = ['NUMERO', 'HT', 'FECHA', 'SE_SOLICITA', 'DESTINO', 'DESCRIPCION', 'FORMULADO_POR', 'OBSERVACIONES'];
    $columnas = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'];

    // Escribir encabezados
    foreach ($encabezados as $index => $encabezado) {
        $celda = $columnas[$index] . '1';
        $sheet->setCellValue($celda, $encabezado);

        // Estilo de encabezado
        $sheet->getStyle($celda)->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size' => 12
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '00779E']
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
        ]);
    }

    // Agregar ejemplos en la fila 2
    $ejemplos = [
        '1',
        '20250015236',
        '06/01/2025',
        'ESTUDIO MERCADO',
        'PRESUPUESTO',
        'Solicita CCP para Caja Chica',
        'S1 PNP JARA MENDOZA OMAR',
        'Observaciones opcionales'
    ];

    foreach ($ejemplos as $index => $ejemplo) {
        $celda = $columnas[$index] . '2';
        $sheet->setCellValue($celda, $ejemplo);

        // Estilo de ejemplo
        $sheet->getStyle($celda)->applyFromArray([
            'font' => [
                'italic' => true,
                'color' => ['rgb' => '666666']
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'F0F0F0']
            ],
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
        ]);
    }

    // Ajustar ancho de columnas
    $sheet->getColumnDimension('A')->setWidth(12);  // NUMERO
    $sheet->getColumnDimension('B')->setWidth(18);  // HT
    $sheet->getColumnDimension('C')->setWidth(15);  // FECHA
    $sheet->getColumnDimension('D')->setWidth(25);  // SE_SOLICITA
    $sheet->getColumnDimension('E')->setWidth(25);  // DESTINO
    $sheet->getColumnDimension('F')->setWidth(50);  // DESCRIPCION
    $sheet->getColumnDimension('G')->setWidth(30);  // FORMULADO_POR
    $sheet->getColumnDimension('H')->setWidth(25);  // OBSERVACIONES

    // Altura de las filas
    $sheet->getRowDimension('1')->setRowHeight(25);
    $sheet->getRowDimension('2')->setRowHeight(20);
}

// Establecer la primera hoja como activa
$spreadsheet->setActiveSheetIndex(0);

// Configurar headers para descarga
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="Plantilla_Importacion_Documentos_2025.xlsx"');
header('Cache-Control: max-age=0');

// Escribir el archivo
$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
