<?php
// controlador/segmentacion_importar_excel.php
// IMPORTANTE: NO DESTRUYE SISTEMA EXISTENTE - Inyección quirúrgica
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Ocultar errores para no corromper JSON en caso de notices
error_reporting(0);
ini_set('display_errors', '0');

require_once __DIR__ . '/../modelo/conexion.php';
require_once __DIR__ . '/../modelo/segmentacion_util.php';
require_once __DIR__ . '/../vendor/autoload.php';

// Limpieza buffer por si acaso
if (ob_get_length()) ob_clean();
header('Content-Type: application/json; charset=utf-8');

if (empty($_SESSION['id'])) {
    echo json_encode(["status" => "error", "message" => "Acceso no autorizado - Sesión caducada."]);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["status" => "error", "message" => "Método no permitido."]);
    exit();
}

if (!isset($_FILES['excel_file']) || $_FILES['excel_file']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(["status" => "error", "message" => "Error al subir el archivo."]);
    exit();
}

$fileTmp = $_FILES['excel_file']['tmp_name'];

$stats = [
    'procesadas' => 0,
    'omitidas' => 0,
    'errores' => 0
];
$log = [];

try {
    // Si la BD soporta transacciones en MySQLi procedemos
    $conn->autocommit(false); 

    $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
    $reader->setReadDataOnly(true);
    $spreadsheet = $reader->load($fileTmp);
    $sheet = $spreadsheet->getActiveSheet();
    
    // Iteramos desde la fila 6
    $rowIterator = $sheet->getRowIterator(6);
    
    // Extraemos tipos_proceso a RAM
    $resTP = $conn->query("SELECT id, LOWER(TRIM(nombre)) as nom FROM tipo_proceso");
    $catalogoTP = [];
    if($resTP) {
        while($rtp = $resTP->fetch_assoc()) {
            $catalogoTP[$rtp['nom']] = (int)$rtp['id'];
        }
    }

    $idUsuario = (int)$_SESSION['id'];
    $fecreg = date('Y-m-d H:i:s');
    
    $recalcAnios = []; // Años que tocaremos y que requerirán recalcTodoAnio

    foreach ($rowIterator as $row) {
        $rowIndex = $row->getRowIndex();
        $cellIterator = $row->getCellIterator();
        $cellIterator->setIterateOnlyExistingCells(false);

        $cells = [];
        foreach ($cellIterator as $cell) {
            $cells[] = $cell->getValue(); 
        }

        // Leer arreglo (Máximo 10 columnas en formato original)
        $refPac = trim((string)($cells[0] ?? ''));
        $cmn    = trim((string)($cells[1] ?? ''));
        $anio   = (int)($cells[2] ?? 0);
        
        if ($refPac === '' && $anio === 0) {
            continue; // fila totalmente vacía o final del doc
        }
        
        if (empty($refPac)) {
             $stats['omitidas']++;
             $log[] = "Fila {$rowIndex}: Omitida (No tiene N° REF PAC)";
             continue;
        }
        if ($anio <= 0) {
             $stats['errores']++;
             $log[] = "Fila {$rowIndex}: Error u Omitida (Año inválido)";
             continue;
        }

        // --- Verificación Anti-Duplicados ---
        // Se valida que el N° PAC no se repita en el MISMO AÑO
        $stmtChk = $conn->prepare("SELECT id FROM segmentacion WHERE ref_pac = ? AND anio = ?");
        $stmtChk->bind_param("si", $refPac, $anio);
        $stmtChk->execute();
        $resChk = $stmtChk->get_result();
        
        if ($resChk->num_rows > 0) {
            $stats['omitidas']++;
            $log[] = "Fila {$rowIndex}: Omitida (N° REF PAC '{$refPac}' ya existe en el año {$anio} en BD)";
            $stmtChk->close();
            continue;
        }
        $stmtChk->close();

        // ------------------------------------
        
        $objeto = trim((string)($cells[3] ?? 'BIENES'));
        $tipoP  = trim((string)($cells[4] ?? ''));
        $desc   = trim((string)($cells[5] ?? ''));
        $cuantia= (float)($cells[6] ?? 0);
        $desiert= (strtolower(trim((string)($cells[7] ?? 'No'))) === 'si' || strtolower(trim((string)($cells[7] ?? 'No'))) === 'sí') ? 'Si' : 'No';
        $mercadP= (strtolower(trim((string)($cells[8] ?? 'No'))) === 'si' || strtolower(trim((string)($cells[8] ?? 'No'))) === 'sí') ? 'Si' : 'No';
        
        // El formato de usuario es Declarado desierto y Mercado Limitado
        $pocosP = 'No'; // Queda en default "No", ya que no viene en plantilla explícitamente, o puedes mapearlo si se requiere.
        $mercad = $mercadP; 
        
        $prograVal = trim((string)($cells[9] ?? '0'));
        $progra = ($prograVal === '1' || strtolower($prograVal) === 'si' || strtolower($prograVal) === 'sí') ? 1 : 0;
        
        // --- Fuzzy Match Tipo Proceso ---
        $tpId = null;
        if (!empty($tipoP)) {
            $keyTP = strtolower($tipoP);
            // Coincidencia
            if (isset($catalogoTP[$keyTP])) {
                $tpId = $catalogoTP[$keyTP];
            } else {
                // Insertar si no existe
                $stmtIns = $conn->prepare("INSERT INTO tipo_proceso (nombre, estado) VALUES (?, 1)");
                $stmtIns->bind_param("s", $tipoP);
                if ($stmtIns->execute()) {
                    $tpId = $stmtIns->insert_id;
                    $catalogoTP[$keyTP] = $tpId; // Update RAM cache
                    $log[] = "Fila {$rowIndex}: INFO (Se creó y usó un nuevo Tipo de Proceso: {$tipoP})";
                }
                $stmtIns->close();
            }
        }
        
        // Preparar inserción 
        $stmtInsSeg = $conn->prepare("INSERT INTO segmentacion 
            (ref_pac, cmn, objeto_contratacion, descripcion, cuantia, tipo_proceso_id, anio, porcentaje, declarado_desierto, pocos_postores, mercado_limitado, resultado_segmentacion, programado, fecha, cuantia_categoria, riesgo_categoria) 
            VALUES (?, ?, ?, ?, ?, ?, ?, 0, ?, ?, ?, NULL, ?, ?, NULL, NULL)");
            
        // s=ref_pac, s=cmn, s=objeto_contratacion, s=descripcion, d=cuantia, i=tipo_proceso_id(nullable), i=anio
        // s=declarado_desierto, s=pocos_postores, s=mercado_limitado, i=programado, s=fecha
        $stmtInsSeg->bind_param(
            "ssssdiisssis",
            $refPac,
            $cmn,
            $objeto,
            $desc,
            $cuantia,
            $tpId,
            $anio,
            $desiert,
            $pocosP,
            $mercad,
            $progra,
            $fecreg
        );

        if ($stmtInsSeg->execute()) {
            $stats['procesadas']++;
            if (!in_array($anio, $recalcAnios)) {
                $recalcAnios[] = $anio;
            }
        } else {
            $stats['errores']++;
            $log[] = "Fila {$rowIndex}: FALLO INSERT (REF PAC: '{$refPac}') - " . $conn->error;
        }
        $stmtInsSeg->close();
    }

    $conn->commit();
    $conn->autocommit(true);
    
    // --- RECALCULAMOS LAS CATEGORIAS Y PORCENTAJES PARA LOS AÑOS INSERTADOS ---
    if ($stats['procesadas'] > 0) {
        foreach ($recalcAnios as $a) {
             if (function_exists('recalcTodoAnio')) {
                 recalcTodoAnio($conn, $a);
                 $log[] = "<b>INFO del Gestor:</b> Se recalcularon % de PAC, Cuantías y Riesgos para el año {$a} de manera correcta.";
             } else {
                 $log[] = "<b>ADVERTENCIA:</b> La lógica recalcTodoAnio() no fue encontrada. Se deberán recalcular manualmente.";
             }
        }
    }

    if($stats['errores'] > 0) {
         echo json_encode(["status" => "warning", "stats" => $stats, "log" => $log]);
    } else {
         echo json_encode(["status" => "success", "stats" => $stats, "log" => $log]);
    }

} catch (Exception $e) {
    if(isset($conn) && $conn instanceof mysqli) {
        $conn->rollback();
        $conn->autocommit(true);
    }
    echo json_encode(["status" => "error", "message" => "Ocurrió un error general procesando el archivo: " . $e->getMessage()]);
}
?>
