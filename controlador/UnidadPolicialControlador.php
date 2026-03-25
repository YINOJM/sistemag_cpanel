<?php
// Controlador/UnidadPolicialControlador.php

// Incluir autoload de Composer para PhpSpreadsheet
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../modelo/unidadPolicialModelo.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

// Manejo de peticiones AJAX
if (isset($_GET['op'])) {
    $controlador = new UnidadPolicialControlador();
    $op = $_GET['op'];

    switch ($op) {
        case 'importar':
            $controlador->importar();
            break;
        case 'buscar':
            $controlador->buscar();
            break;
        case 'arbol':
            $controlador->arbol();
            break;
        case 'descargarPrototipo':
            $controlador->descargarPrototipo();
            break;
    }
}

class UnidadPolicialControlador {

    public function importar() {
        header('Content-Type: application/json');
        
        if (!isset($_FILES['archivo_excel']) || $_FILES['archivo_excel']['error'] !== 0) {
            echo json_encode(['status' => false, 'msg' => 'Error al subir el archivo.']);
            return;
        }

        $archivo = $_FILES['archivo_excel']['tmp_name'];

        try {
            $spreadsheet = IOFactory::load($archivo);
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();

            // Truncar tabla antes de importar
            ModeloUnidadesPoliciales::mdlTruncate();

            $count = 0;
            // Asumimos que la fila 0 son cabeceras (segun imagen: region | unidades_policiales | sub_unidades)
            // Indices: 0 => Region, 1 => Unidad, 2 => Sub-unidad, 3 => Tipo CPNP
            foreach ($rows as $index => $row) {
                if ($index == 0) continue; // Saltar cabecera

                $region = trim($row[0] ?? '');
                $unidad = trim($row[1] ?? '');
                $sub_unidad = trim($row[2] ?? '');
                $tipo_cpnp = trim($row[3] ?? '');

                if (!empty($region) || !empty($unidad)) {
                    ModeloUnidadesPoliciales::mdlInsertar($region, $unidad, $sub_unidad, $tipo_cpnp);
                    $count++;
                }
            }

            echo json_encode(['status' => true, 'msg' => "Importación exitosa. $count registros importados."]);

        } catch (Exception $e) {
            echo json_encode(['status' => false, 'msg' => 'Error al procesar el archivo: ' . $e->getMessage()]);
        }
    }

    public function buscar() {
        header('Content-Type: application/json');
        $termino = $_POST['termino'] ?? '';
        
        if (empty($termino)) {
            echo json_encode([]);
            return;
        }

        $resultados = ModeloUnidadesPoliciales::mdlBuscar($termino);
        echo json_encode($resultados);
    }

    public function arbol() {
        header('Content-Type: application/json');
        $data = ModeloUnidadesPoliciales::mdlObtenerTodo();
        
        // Estructurar en árbol: Region -> Unidad -> SubUnidad
        $arbol = [];
        foreach ($data as $row) {
            $reg = $row['region'];
            $uni = $row['unidad_superior'];
            $sub = $row['sub_unidad'];

            if (!isset($arbol[$reg])) {
                $arbol[$reg] = [];
            }
            if (!isset($arbol[$reg][$uni])) {
                $arbol[$reg][$uni] = [];
            }
            if (!empty($sub)) {
                $arbol[$reg][$uni][] = $sub;
            }
        }

        echo json_encode($arbol);
    }

    public function descargarPrototipo() {
        // Crear nuevo spreadsheet
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // Cabeceras
        $sheet->setCellValue('A1', 'region');
        $sheet->setCellValue('B1', 'unidades_policiales');
        $sheet->setCellValue('C1', 'sub_unidades');
        $sheet->setCellValue('D1', 'tipo_cpnp');

        // Estilo cabecera (Cyan background + Bold)
        $styleArray = [
            'font' => [
                'bold' => true,
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => [
                    'argb' => 'FF00FFFF', // Cyan
                ],
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
            ],
        ];
        $sheet->getStyle('A1:D1')->applyFromArray($styleArray);
        
        // Ajustar ancho columnas
        foreach(range('A','D') as $columnID) {
            $sheet->getColumnDimension($columnID)->setAutoSize(true);
        }

        // Descarga
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="Formato_Unidades_Policiales.xlsx"');
        header('Cache-Control: max-age=0');

        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save('php://output');
        exit;
    }
}
