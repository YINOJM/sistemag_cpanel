<?php
ob_start();
// controlador/ren_importar_excel.php
require_once __DIR__ . '/../modelo/conexion.php';
require_once __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;

set_time_limit(600);
ini_set('memory_limit', '512M');

header('Content-Type: application/json');
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Manejador global para capturar CUALQUIER error y devolverlo como JSON
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    // Ignorar errores que no son críticos para el flujo JSON o son simples notas/deprecated
    if (!(error_reporting() & $errno)) return false;
    if ($errno === E_DEPRECATED || $errno === E_USER_DEPRECATED || $errno === E_NOTICE) return true; 

    ob_clean();
    echo json_encode([
        'status' => false,
        'msg' => "Error de PHP [$errno]: $errstr en $errfile:$errline"
    ]);
    exit;
});

// Manejador para excepciones no capturadas
set_exception_handler(function($e) {
    ob_clean();
    echo json_encode([
        'status' => false,
        'msg' => "Excepción no capturada: " . $e->getMessage()
    ]);
    exit;
});

if (empty($_SESSION['id'])) {
    ob_clean();
    die(json_encode(['status' => false, 'msg' => 'No autorizado']));
}

if (!isset($_FILES['archivo']) || $_FILES['archivo']['error'] !== UPLOAD_ERR_OK) {
    ob_clean();
    die(json_encode(['status' => false, 'msg' => 'No se recibió el archivo o formato inválido']));
}

try {
    $archivo = $_FILES['archivo']['tmp_name'];
    $spreadsheet = IOFactory::load($archivo);
    $hoja = $spreadsheet->getActiveSheet();
    $datos = $hoja->toArray();

    if (count($datos) < 3)
        throw new Exception('El archivo no contiene suficientes datos para procesar');

    $exitosos = 0;
    $errores = [];
    $uid = $_SESSION['id'];
    $anio_fiscal = isset($_POST['anio']) ? (int)$_POST['anio'] : ANIO_FISCAL;

    $conexion->begin_transaction();

    $nombre_archivo = $_FILES['archivo']['name'];
    $nombre_lote_input = trim($_POST['nombre_lote'] ?? '');

    // --- LÓGICA DE NOMBRE AUTOMÁTICO NIVEL PRO ---
    if (empty($nombre_lote_input)) {
        $fecha_hoy = date('dmY'); // Formato DíaMesAño (ej: 15032024)
        $prefijo = "LOTE_" . $fecha_hoy . "v"; // LOTE_15032024v
        
        // Buscar el último correlativo para hoy
        $stmt_corr = $conexion->prepare("SELECT grupo_importacion FROM ren_rendiciones WHERE grupo_importacion LIKE ? ORDER BY id DESC LIMIT 1");
        $like_corr = $prefijo . "%";
        $stmt_corr->bind_param("s", $like_corr);
        $stmt_corr->execute();
        $res_corr = $stmt_corr->get_result();
        
        $correlativo = 1;
        if ($row_corr = $res_corr->fetch_assoc()) {
            // Extraer número después de la 'v'
            $ultimo_nombre = $row_corr['grupo_importacion'];
            $partes = explode('v', $ultimo_nombre);
            if (isset($partes[1]) && is_numeric($partes[1])) {
                $correlativo = intval($partes[1]) + 1;
            }
        }
        $nombre_final_lote = $prefijo . str_pad($correlativo, 2, '0', STR_PAD_LEFT);
    } else {
        $nombre_final_lote = mb_strtoupper($nombre_lote_input);
    }

    foreach ($datos as $index => $fila) {
        // Ignorar encabezados (Fila 0: Titulo, Fila 1: Columnas)
        if ($index < 2)
            continue;

        $dni = trim($fila[0] ?? '');
        if (empty($dni) || !is_numeric($dni))
            continue;

        try {
            $cip = trim($fila[1] ?? '');
            $grado = trim($fila[2] ?? '');
            $nombres = trim($fila[3] ?? '');
            $lugar = trim($fila[4] ?? '');
            $unidad_excel = trim($fila[7] ?? '');
            $cuenta = trim($fila[8] ?? '');

            // --- RESOLUCIÓN DE UNIDAD ---
            $id_subunidad = null;
            $region_cache = '';
            $division_cache = '';

            // 1. Intentar buscar en el padrón de personal por DNI
            $stmtPers = $conexion->prepare("
                SELECT p.id_subunidad, r.nombre_region, d.nombre_division, s.nombre_subunidad 
                FROM mae_personal p
                JOIN sub_unidades_policiales s ON p.id_subunidad = s.id_subunidad
                JOIN divisiones_policiales d ON s.id_division = d.id_division
                JOIN regiones_policiales r ON d.id_region = r.id_region
                WHERE p.dni = ? LIMIT 1
            ");
            if ($stmtPers) {
                $stmtPers->bind_param("s", $dni);
                $stmtPers->execute();
                $resPers = $stmtPers->get_result();
                if ($rowPers = $resPers->fetch_assoc()) {
                    $id_subunidad = $rowPers['id_subunidad'];
                    $region_cache = $rowPers['nombre_region'];
                    $division_cache = $rowPers['nombre_division'];
                }
                $stmtPers->close();
            } else {
                // Handle error if prepare fails, though it's rare for a static query
                // For now, we'll just proceed, but a more robust solution might log this.
            }

            // 2. Si no está en personal, tratar de buscar la unidad por nombre
            if (!$id_subunidad && !empty($unidad_excel)) {
                $stmtUnit = $conexion->prepare("
                    SELECT s.id_subunidad, d.nombre_division, r.nombre_region 
                    FROM sub_unidades_policiales s
                    JOIN divisiones_policiales d ON s.id_division = d.id_division
                    JOIN regiones_policiales r ON d.id_region = r.id_region
                    WHERE s.nombre_subunidad LIKE ? LIMIT 1
                ");
                if ($stmtUnit) {
                    $likeUnidad = "%$unidad_excel%";
                    $stmtUnit->bind_param("s", $likeUnidad);
                    $stmtUnit->execute();
                    $resUnit = $stmtUnit->get_result();
                    if ($rowUnit = $resUnit->fetch_assoc()) {
                        $id_subunidad = $rowUnit['id_subunidad'];
                        $region_cache = $rowUnit['nombre_region'];
                        $division_cache = $rowUnit['nombre_division'];
                    }
                    $stmtUnit->close();
                }
            }

            // --- PROCESAMIENTO DE FECHAS ROBUSTO ---
            $fecha_ini = procesarFechaInteligente($fila[9]); // FECHA INICIO
            $fecha_ret = procesarFechaInteligente($fila[10]); // FECHA RETORNO

            // --- MONTOS (Mejorado para detectar miles con coma) ---
            $liq = trim($fila[11] ?? '');
            $igv = cleanAmount($fila[12] ?? 0);
            $dias = intval($fila[13] ?? 0);
            $deposito1 = cleanAmount($fila[14] ?? 0);
            $siaf = trim($fila[15] ?? '');
            $pasajes = cleanAmount($fila[16] ?? 0);
            $total = cleanAmount($fila[17] ?? 0);

            $stmt = $conexion->prepare("INSERT INTO ren_rendiciones (
                dni, cip, grado, apellidos_nombres, lugar_comision, 
                id_subunidad, region_cache, division_cache, unidad, 
                cuenta_ahorros, fecha_inicio, fecha_retorno, nro_liquidacion, 
                igv, dias, primer_deposito, siaf_expediente, pasajes, total_depositado,
                usuario_registro, grupo_importacion, anio_fiscal
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

            if (!$stmt) {
                throw new Exception("Error preparando INSERT: " . $conexion->error);
            }

            $types = "sssssisssssssdidsddisi"; 
            $stmt->bind_param(
                $types,
                $dni, $cip, $grado, $nombres, $lugar,
                $id_subunidad, $region_cache, $division_cache, $unidad_excel,
                $cuenta, $fecha_ini, $fecha_ret, $liq,
                $igv, $dias, $deposito1, $siaf, $pasajes, $total,
                $uid, $nombre_final_lote, $anio_fiscal
            );

            if ($stmt->execute()) {
                $exitosos++;
            } else {
                throw new Exception("Error en fila $index (DNI $dni): " . $stmt->error);
            }
            $stmt->close();

        } catch (Exception $e) {
            $errores[] = $e->getMessage();
        }
    }

    $conexion->commit();

    ob_clean();
    echo json_encode([
        'status' => true,
        'msg' => "Importación finalizada.",
        'exitosos' => $exitosos,
        'err_count' => count($errores),
        'errores' => $errores
    ]);

} catch (Throwable $e) {
    if (isset($conexion) && $conexion->connect_errno == 0) {
        $conexion->rollback();
    }
    ob_clean();
    echo json_encode(['status' => false, 'msg' => "Fallo crítico: " . $e->getMessage()]);
}

/**
 * Función ultra-robusta para parsear fechas de Excel (serial o texto español)
 */
function procesarFechaInteligente($valor)
{
    if (empty($valor))
        return null;

    // 1. Si es número (Serial de Excel)
    if (is_numeric($valor)) {
        return Date::excelToDateTimeObject($valor)->format('Y-m-d');
    }

    // 2. Si es texto (Ej: "03-ene-26")
    $val = strtolower(trim($valor));
    $meses = [
        'ene' => '01',
        'feb' => '02',
        'mar' => '03',
        'abr' => '04',
        'may' => '05',
        'jun' => '06',
        'jul' => '07',
        'ago' => '08',
        'set' => '09',
        'sep' => '09',
        'oct' => '10',
        'nov' => '11',
        'dic' => '12'
    ];

    foreach ($meses as $nom => $num) {
        if (strpos($val, $nom) !== false) {
            // Reemplazar nombre por número. Ej: "03-ene-26" -> "03-01-26"
            $val = str_replace($nom, $num, $val);
            break;
        }
    }

    // Limpiar separadores. A veces usan "." o " "
    $val = str_replace(['.', ' ', '/'], '-', $val);

    // Intentar crear el objeto DateTime
    try {
        // Formato d-m-y (dos dígitos año)
        $d = DateTime::createFromFormat('d-m-y', $val);
        if (!$d)
            $d = DateTime::createFromFormat('d-m-Y', $val);
        if (!$d)
            $d = new DateTime($val);

        if ($d) {
            // Ajuste siglo 20 -> 21
            if ($d->format('Y') < 2000)
                $d->modify('+100 years');
            return $d->format('Y-m-d');
        }
    } catch (Exception $e) {
    }

    return null;
}

/**
 * Limpia montos de Excel para que sean numéricos puros (S/ 3,348.00 -> 3348.00)
 */
function cleanAmount($valor)
{
    if (empty($valor)) return 0;
    // Eliminar todo lo que no sea número o punto decimal (.) o coma (,)
    $val = preg_replace('/[^0-9.,]/', '', $valor);
    
    // Si tiene coma y punto (milésimas con coma), quitar la coma
    // Ej: 3,348.00 -> 3348.00
    if (strpos($val, ',') !== false && strpos($val, '.') !== false) {
        $val = str_replace(',', '', $val);
    } 
    // Si solo tiene coma y parece decimal (ej: 3348,00), cambiar coma por punto
    elseif (strpos($val, ',') !== false && strpos($val, '.') === false) {
        // Solo si la coma está cerca del final (máximo 2-3 decimales)
        if (strlen($val) - strrpos($val, ',') <= 3) {
            $val = str_replace(',', '.', $val);
        } else {
            $val = str_replace(',', '', $val);
        }
    }
    
    return floatval($val);
}
