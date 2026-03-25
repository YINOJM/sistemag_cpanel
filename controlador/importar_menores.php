<?php
// importar_menores.php
declare(strict_types=1);

use PhpOffice\PhpSpreadsheet\IOFactory;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../modelo/conexion.php';

// Verificación de seguridad (Super Admin, Admin o permiso específico)
if (empty($_SESSION['id']) || (strcasecmp($_SESSION['rol'], 'Super Administrador') !== 0 && strcasecmp($_SESSION['rol'], 'Administrador') !== 0 && !isset($_SESSION['permisos_rutas']['seguimiento']))) {
    echo json_encode(['status' => 'error', 'message' => 'No autorizado.']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_FILES['archivo_excel'])) {
    echo json_encode(['success' => false, 'message' => 'No se recibió el archivo.']);
    exit;
}

$file = $_FILES['archivo_excel'];
$extension = pathinfo($file['name'], PATHINFO_EXTENSION);

if (!in_array(strtolower($extension), ['xlsx', 'xls', 'csv'])) {
    echo json_encode(['success' => false, 'message' => 'Formato de archivo no válido. Use Excel (.xlsx, .xls).']);
    exit;
}

try {
    $spreadsheet = IOFactory::load($file['tmp_name']);
    $sheet = $spreadsheet->getActiveSheet();
    // nullValue=null, calculateFormulas=true, formatData=false (raw values), returnRawData=true
    $data = $sheet->toArray(null, true, false, true);

    $registrosInsertados = 0;
    $anioActual = (int)date('Y');
    $usuarioId = $_SESSION['id'];

    // Mapeo de estados basado en texto (opcional pero útil)
    $mapeoEstados = [
        'PENDIENTE' => 1,
        'EN EJECUCION' => 2,
        'ENTREGO CONFORME' => 3,
        'NO ENTREGO CONFORME' => 4,
        'NO ENTREGO' => 5,
        'ANULADO' => 6,
        'CULMINADO' => 7
    ];

    foreach ($data as $index => $row) {
        if ($index == 1) continue; 

        if (empty($row['B'])) continue; 

        // 1. Mapeo de Tipo (OC/OS)
        $tipo_raw = strtoupper(trim((string)($row['A'] ?? '')));
        $tipo_orden = 'OC'; // Default Bienes
        if (strpos($tipo_raw, 'SERV') !== false || $tipo_raw === 'OS') {
            $tipo_orden = 'OS';
        }

        $objeto = trim((string)$row['B']);
        $area = trim((string)$row['C']);
        
        $procesarFecha = function($valor) {
            if (empty($valor)) return null;
            
            // Si es número de Excel
            if (is_numeric($valor)) {
                $dateTime = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float)$valor);
                return $dateTime->format('Y-m-d');
            }
            
            $valor = trim((string)$valor);
            // Caso DD/MM/YYYY o DD-MM-YYYY
            if (preg_match('/^(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})$/', $valor, $matches)) {
                // matches[1]=D, matches[2]=M, matches[3]=Y
                return sprintf('%04d-%02d-%02d', $matches[3], $matches[2], $matches[1]);
            }
            
            // Fallback a strtotime (usando el formato as-is)
            $valor = str_replace('/', '-', $valor); // Normaliza para que actúe compatible
            $timestamp = strtotime($valor);
            return $timestamp ? date('Y-m-d', $timestamp) : null;
        };

        $f_req = $procesarFecha($row['D']);
        $f_emision = $procesarFecha($row['E']);
        $plazo = (int)($row['F'] ?? 0);
        $f_venc = $procesarFecha($row['G']);
        
        if (!$f_venc && $f_emision && $plazo > 0) {
            $f_venc = date('Y-m-d', strtotime($f_emision . " + $plazo days"));
        } elseif ($plazo === 0 && $f_emision && $f_venc) {
            $d_emision = new DateTime($f_emision);
            $d_venc = new DateTime($f_venc);
            if ($d_venc >= $d_emision) {
                $plazo = $d_emision->diff($d_venc)->days;
            }
        }

        // 3. Limpieza de Importes
        $cleanAmount = function($val) {
            if (is_numeric($val)) return (float)$val;
            $val = str_replace(['S/', ',', ' '], '', (string)$val);
            return is_numeric($val) ? (float)$val : 0.00;
        };

        $m_comprometido = $cleanAmount($row['H'] ?? 0);
        $m_devengado = $cleanAmount($row['I'] ?? 0);
        $m_girado = $cleanAmount($row['J'] ?? 0);
        
        $estado_txt = strtoupper(trim((string)($row['K'] ?? '')));
        $obs = trim((string)($row['L'] ?? ''));

        // 4. Detectar estado desde columna 'Estado' o 'Observaciones' como fallback
        $estadoId = 1; // PENDIENTE por defecto
        foreach ($mapeoEstados as $nombre => $id) {
            if (strpos($estado_txt, $nombre) !== false || (!empty($obs) && strpos(strtoupper($obs), $nombre) !== false)) {
                $estadoId = $id;
                break;
            }
        }

        // Insertar
        $sql = "INSERT INTO seguimiento_menores_8uit (
                    anio, tipo_orden, unidad_solicitante, fecha_requerimiento, 
                    descripcion_servicio_bien, fecha_emision, plazo_ejecucion_dias, 
                    fecha_vencimiento, estado_id, monto_comprometido, 
                    monto_devengado, monto_girado, observaciones, usuario_registro_id
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $conexion->prepare($sql);
        if (!$stmt) throw new Exception("Error SQL: " . $conexion->error);

        $stmt->bind_param("isssssisidddsi", 
            $anioActual, $tipo_orden, $area, $f_req, 
            $objeto, $f_emision, $plazo, $f_venc, $estadoId,
            $m_comprometido, $m_devengado, $m_girado, $obs, $usuarioId
        );

        if ($stmt->execute()) {
            $registrosInsertados++;
        }
        $stmt->close();
    }

    echo json_encode(['success' => true, 'message' => "Se importaron exitosamente $registrosInsertados registros."]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error al procesar el archivo: ' . $e->getMessage()]);
}
