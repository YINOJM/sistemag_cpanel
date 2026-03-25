<?php
// controlador/ImportarControlador.php
// VERSIÓN MEJORADA - Sistema de Gestión Documental
// Configuración de recursos para archivos grandes
ini_set('memory_limit', '1024M');
ini_set('max_execution_time', 600);
ob_clean(); // Limpiar buffer

require_once '../modelo/conexion.php';

$response = ['status' => false, 'log' => '', 'stats' => []];
$log_buffer = "";
$stats = [
    'total_filas' => 0,
    'procesadas' => 0,
    'omitidas' => 0,
    'errores' => []
];

function logger($msg)
{
    global $log_buffer;
    $log_buffer .= "[" . date('H:i:s') . "] " . $msg . "\n";
}

function addError($fila, $razon)
{
    global $stats;
    $stats['errores'][] = ['fila' => $fila, 'razon' => $razon];
}

// Función para limpiar BOM (Byte Order Mark)
function removeBOM($text)
{
    $bom = pack('H*', 'EFBBBF');
    return preg_replace("/^$bom/", '', $text);
}

// Función para detectar delimitador automáticamente
function detectDelimiter($filePath)
{
    $handle = fopen($filePath, 'r');
    if (!$handle)
        return ',';

    $firstLine = fgets($handle);
    fclose($handle);

    // Limpiar BOM si existe
    $firstLine = removeBOM($firstLine);

    // Contar ocurrencias de delimitadores comunes
    $commas = substr_count($firstLine, ',');
    $semicolons = substr_count($firstLine, ';');
    $tabs = substr_count($firstLine, "\t");

    // Retornar el delimitador más común
    if ($semicolons > $commas && $semicolons > $tabs) {
        return ';';
    } elseif ($tabs > $commas && $tabs > $semicolons) {
        return "\t";
    }
    return ',';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tipo_doc = $_POST['tipo_documento'];

    if (isset($_FILES['archivo_csv']) && $_FILES['archivo_csv']['error'] == 0) {
        $file_tmp = $_FILES['archivo_csv']['tmp_name'];

        logger("═══════════════════════════════════════");
        logger("INICIO DE IMPORTACIÓN");
        logger("Tipo de documento: $tipo_doc");
        logger("Archivo: " . $_FILES['archivo_csv']['name']);
        logger("Tamaño: " . number_format($_FILES['archivo_csv']['size'] / 1024, 2) . " KB");

        // Detectar delimitador automáticamente
        $delimiter = detectDelimiter($file_tmp);
        logger("Delimitador detectado: " . ($delimiter == ',' ? 'COMA' : ($delimiter == ';' ? 'PUNTO Y COMA' : 'TABULADOR')));

        $handle = fopen($file_tmp, "r");
        if ($handle !== FALSE) {
            $row = 0;
            $success = 0;
            $errors = 0;
            $skipped = 0;

            // Preparar statements
            $stmtDest = $conexion->prepare("SELECT id_destino FROM mae_destinos WHERE nombre_destino = ?");
            $stmtNewDest = $conexion->prepare("INSERT INTO mae_destinos (nombre_destino, activo) VALUES (?, 1)");

            $sqlInsert = "INSERT INTO documentos (anio, cod_tipo, num_correlativo, num_completo, ht, created_at, se_solicita, id_destino, asunto, usuario_formulador) 
                          VALUES (2025, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmtInsert = $conexion->prepare($sqlInsert);

            logger("Procesando filas...");

            while (($data = fgetcsv($handle, 0, $delimiter)) !== FALSE) {
                $row++;
                $stats['total_filas'] = $row;

                // Limpiar BOM en la primera celda de cada fila
                if (isset($data[0])) {
                    $data[0] = removeBOM($data[0]);
                }

                // Convertir encoding si es necesario
                $data = array_map(function ($field) {
                    if (!mb_check_encoding($field, 'UTF-8')) {
                        return mb_convert_encoding($field, 'UTF-8', 'ISO-8859-1');
                    }
                    return $field;
                }, $data);

                // Validar que tenga al menos un número de documento
                // Permitir números como 0, 336, 341, 344, 352, 353, 358, 362, 364, etc.
                $num_correlativo = isset($data[0]) && trim($data[0]) !== '' ? (int) trim($data[0]) : null;

                if ($num_correlativo === null) {
                    $skipped++;
                    $stats['omitidas']++;
                    if ($row == 1) {
                        logger("Fila $row: Encabezado detectado, omitiendo");
                    } else {
                        logger("Fila $row: OMITIDA - Sin número de documento. Columna A: '" . ($data[0] ?? 'VACÍO') . "'");
                        addError($row, "Sin número de documento");
                    }
                    continue;
                }

                // Log de inicio de procesamiento
                logger("Fila $row: Procesando $tipo_doc N° $num_correlativo");

                // Mapeo dinámico según tipo de documento
                if ($tipo_doc == 'MEMORANDUM') {
                    // Estructura: Num | HT | Fecha | Destino | Asunto | Formulado
                    $ht = trim($data[1] ?? '');
                    $fecha_raw = trim($data[2] ?? '');
                    $se_solicita = "";
                    $nombre_destino = strtoupper(trim($data[3] ?? ''));
                    $asunto = trim($data[4] ?? '');
                    $formulado_por = !empty($data[5]) ? trim($data[5]) : 'MIGRACION';
                } else {
                    // Estructura Estándar: Num | HT | Fecha | Se Solicita | Destino | Asunto | Formulado
                    $ht = trim($data[1] ?? '');
                    $fecha_raw = trim($data[2] ?? '');
                    $se_solicita = trim($data[3] ?? '');
                    $nombre_destino = strtoupper(trim($data[4] ?? ''));
                    $asunto = trim($data[5] ?? '');
                    $formulado_por = !empty($data[6]) ? trim($data[6]) : 'MIGRACION';
                }

                // Formatear fecha - NO INVENTAR FECHAS
                $fecha_mysql = null; // Por defecto NULL si no hay fecha
                if (!empty($fecha_raw)) {
                    $dateObj = DateTime::createFromFormat('d/m/Y H:i:s', $fecha_raw);
                    if ($dateObj === false) {
                        $dateObj = DateTime::createFromFormat('d/m/Y', $fecha_raw);
                    }
                    if ($dateObj) {
                        $fecha_mysql = $dateObj->format('Y-m-d H:i:s');
                    } else {
                        // Si la fecha es inválida, dejar como NULL y registrar advertencia
                        logger("Fila $row: Fecha inválida '$fecha_raw', se guardará como NULL");
                    }
                }
                // Si fecha_mysql es null, quedará como NULL en la BD

                // Resolver Destino - Si está vacío o no se puede resolver, usar "POR ASIGNAR"
                $id_destino = 0;
                if (empty($nombre_destino)) {
                    $nombre_destino = "POR ASIGNAR";
                }

                $stmtDest->bind_param("s", $nombre_destino);
                $stmtDest->execute();
                $resDest = $stmtDest->get_result();

                if ($rowDest = $resDest->fetch_assoc()) {
                    $id_destino = $rowDest['id_destino'];
                } else {
                    // Crear destino
                    $stmtNewDest->bind_param("s", $nombre_destino);
                    try {
                        if ($stmtNewDest->execute()) {
                            $id_destino = $stmtNewDest->insert_id;
                            logger("Nuevo destino creado: $nombre_destino (ID: $id_destino)");
                        }
                    } catch (mysqli_sql_exception $e) {
                        if ($e->getCode() == 1062) {
                            // Duplicado, intentar buscar de nuevo
                            $stmtDest->execute();
                            $resDest = $stmtDest->get_result();
                            if ($rowDest = $resDest->fetch_assoc()) {
                                $id_destino = $rowDest['id_destino'];
                            }
                        } else {
                            logger("Fila $row: Error creando destino '$nombre_destino': " . $e->getMessage());
                        }
                    }
                }

                // Si aún no se pudo resolver, buscar o crear "POR ASIGNAR"
                if ($id_destino == 0) {
                    $nombre_destino = "POR ASIGNAR";
                    $stmtDest->bind_param("s", $nombre_destino);
                    $stmtDest->execute();
                    $resDest = $stmtDest->get_result();

                    if ($rowDest = $resDest->fetch_assoc()) {
                        $id_destino = $rowDest['id_destino'];
                    } else {
                        // Crear "POR ASIGNAR" si no existe
                        $stmtNewDest->bind_param("s", $nombre_destino);
                        if ($stmtNewDest->execute()) {
                            $id_destino = $stmtNewDest->insert_id;
                            logger("Destino 'POR ASIGNAR' creado (ID: $id_destino)");
                        }
                    }

                    // Si aún así no se pudo, usar ID 1 como fallback
                    if ($id_destino == 0) {
                        $id_destino = 1;
                        logger("Fila $row: Usando destino ID 1 como fallback");
                    }
                }

                // Armar num_completo
                $num_completo = "$tipo_doc N° $num_correlativo";

                // Insertar documento - Manejar NULL en fecha
                if ($fecha_mysql === null) {
                    // Si no hay fecha, insertar NULL
                    $sqlInsertRow = "INSERT INTO documentos (anio, cod_tipo, num_correlativo, num_completo, ht, created_at, se_solicita, id_destino, asunto, usuario_formulador) 
                                     VALUES (2025, ?, ?, ?, ?, NULL, ?, ?, ?, ?)";
                    $stmtInsertRow = $conexion->prepare($sqlInsertRow);
                    $stmtInsertRow->bind_param(
                        "sisssiss",
                        $tipo_doc,
                        $num_correlativo,
                        $num_completo,
                        $ht,
                        $se_solicita,
                        $id_destino,
                        $asunto,
                        $formulado_por
                    );
                } else {
                    // Si hay fecha, insertarla normalmente
                    $stmtInsertRow = $stmtInsert;
                    $stmtInsertRow->bind_param(
                        "sissssiss",
                        $tipo_doc,
                        $num_correlativo,
                        $num_completo,
                        $ht,
                        $fecha_mysql,
                        $se_solicita,
                        $id_destino,
                        $asunto,
                        $formulado_por
                    );
                }

                try {
                    if ($stmtInsertRow->execute()) {
                        $success++;
                        $stats['procesadas']++;
                        logger("Fila $row: ✅ IMPORTADO - $num_completo");
                    } else {
                        if ($conexion->errno == 1062) {
                            addError($row, "Documento duplicado: $num_completo");
                            logger("Fila $row: ❌ DUPLICADO - $num_completo ya existe en BD");
                        } else {
                            addError($row, "Error al insertar: " . $stmtInsertRow->error);
                            logger("Fila $row: ❌ ERROR - " . $stmtInsertRow->error);
                        }
                        $errors++;
                        $stats['omitidas']++;
                    }
                } catch (Exception $e) {
                    addError($row, "Excepción: " . $e->getMessage());
                    logger("Fila $row: ❌ EXCEPCIÓN - " . $e->getMessage());
                    $errors++;
                    $stats['omitidas']++;
                }

                // Log de progreso cada 50 filas
                if ($row % 50 == 0) {
                    logger("Progreso: $row filas leídas, $success procesadas, $errors errores");
                }
            }

            fclose($handle);

            logger("═══════════════════════════════════════");
            logger("FIN DE IMPORTACIÓN");
            logger("Total de filas leídas: $row");
            logger("Filas procesadas exitosamente: $success");
            logger("Filas omitidas: $skipped");
            logger("Errores encontrados: $errors");

            if (count($stats['errores']) > 0) {
                logger("\nDETALLE DE ERRORES:");
                $errorCount = min(count($stats['errores']), 20); // Mostrar máximo 20 errores
                for ($i = 0; $i < $errorCount; $i++) {
                    $err = $stats['errores'][$i];
                    logger("  Fila {$err['fila']}: {$err['razon']}");
                }
                if (count($stats['errores']) > 20) {
                    logger("  ... y " . (count($stats['errores']) - 20) . " errores más");
                }
            }

            $response['status'] = true;
            $response['stats'] = $stats;
        } else {
            logger("ERROR: No se pudo abrir el archivo");
        }
    } else {
        logger("ERROR: Archivo no recibido o error de carga");
        if (isset($_FILES['archivo_csv']['error'])) {
            logger("Código de error: " . $_FILES['archivo_csv']['error']);
        }
    }
}

$response['log'] = $log_buffer;
echo json_encode($response);
exit();
?>