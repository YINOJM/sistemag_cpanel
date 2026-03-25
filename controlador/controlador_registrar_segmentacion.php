<?php
// controlador/controlador_registrar_segmentacion.php
declare(strict_types=1);

date_default_timezone_set('America/Lima');

require_once __DIR__ . '/../modelo/conexion.php';
require_once __DIR__ . '/../modelo/segmentacion_util.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
if ($conn instanceof mysqli) {
    $conn->set_charset('utf8mb4');
}

function yn(string $v): string {
    $v = strtoupper(trim($v));
    return ($v === 'SI' || $v === 'SÍ' || $v === '1') ? 'Si' : 'No';
}

try {

    // Solo debe llegar por POST. Si entra por GET, lo mando al listado.
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: ../vista/segmentacion_listado.php?err=ex');
        exit;
    }

    $embed = !empty($_POST['embed']);

    // =========================
    // CAMPOS BÁSICOS
    // =========================

    // 1) Referencia PAC (normalizando)
    $ref_pac_raw = trim($_POST['ref_pac'] ?? '');
    // dejar solo dígitos
    $ref_pac_raw = preg_replace('/\D/', '', $ref_pac_raw);

    if ($ref_pac_raw === '') {
        header('Location: ../vista/segmentacion.php?err=faltan');
        exit;
    }

    // número sin ceros a la izquierda
    $ref_pac_num = (int)$ref_pac_raw;

    // rango permitido
    if ($ref_pac_num < 1 || $ref_pac_num > 999) {
        header('Location: ../vista/segmentacion.php?err=refinv');
        exit;
    }

    // formato final que se guardará (ej. 001, 010, 120)
    $ref_pac = str_pad((string)$ref_pac_num, 3, '0', STR_PAD_LEFT);

    // 2) Resto de campos
    $cmn                 = trim($_POST['cmn'] ?? '');
    $objeto_contratacion = trim($_POST['objeto_contratacion'] ?? '');
    $tipo_proceso_id     = (int)($_POST['tipo_proceso_id'] ?? 0);
    $descripcion         = trim($_POST['descripcion'] ?? '');


    // Normalizador monetario
    $moneyToFloat = function ($v): float {
        if ($v === null) return 0.0;
        $s = trim((string)$v);
        if ($s === '') return 0.0;

        // eliminar símbolos "S/."
        $s = str_ireplace(['S/', 'S/.', ' '], '', $s);

        // caso 1: "12345,67"
        if (substr_count($s, ',') === 1 && substr_count($s, '.') === 0) {
            $s = str_replace(',', '.', $s);
        } else {
            // caso 2: "12,345.67"
            $s = str_replace(',', '', $s);
        }
        return (float)$s;
    };

    $cuantia_input = $moneyToFloat($_POST['cuantia'] ?? 0);

    // Estos valores NO se toman del form; se recalculan después
// Valores iniciales válidos (luego se recalculan con recalcPorcentajeYCuantiaCategoria)
$porcentaje        = 0.00;
$cuantia_categoria = 'Baja';       // valor válido del ENUM
$riesgo_categoria  = 'Bajo';       // valor válido del ENUM
$resultado_seg     = 'Rutinario';  // valor válido del ENUM


$declarado_desierto = yn($_POST['declarado_desierto'] ?? 'No');
$pocos_postores     = yn($_POST['pocos_postores'] ?? 'No');
$mercado_limitado   = yn($_POST['mercado_limitado'] ?? 'No');


    $programado = (isset($_POST['es_programado']) && $_POST['es_programado'] === '1') ? 1 : 0;
    $anio       = isset($_POST['anio']) ? (int)$_POST['anio'] : (int)date('Y');

    $tiene_items = isset($_POST['tiene_items']) ? 1 : 0;
    $items_desc  = (array)($_POST['items_descripcion'] ?? []);
    $items_monto = array_map($moneyToFloat, (array)($_POST['items_monto'] ?? []));

    // =========================
    // VALIDACIONES BÁSICAS
    // =========================
    if ($ref_pac === '' || $cmn === '' || $objeto_contratacion === '' || $descripcion === '') {
        header('Location: ../vista/segmentacion.php?err=faltan');
        exit;
    }

    if ($tipo_proceso_id <= 0) {
        header('Location: ../vista/segmentacion.php?err=tipo');
        exit;
    }

    // =========================
    // CUANTÍA FINAL (CON ÍTEMS O SIN ÍTEMS)
    // =========================
    $cuantia_final = $cuantia_input;

    if ($tiene_items) {

        $suma_items = 0.0;
        $n = max(count($items_desc), count($items_monto));

        for ($i = 0; $i < $n; $i++) {
            $desc  = trim($items_desc[$i] ?? '');
            $monto = (float)($items_monto[$i] ?? 0);

            if ($desc !== '' && $monto > 0) {
                $suma_items += $monto;
            }
        }

        if ($suma_items <= 0) {
            header('Location: ../vista/segmentacion.php?err=items0');
            exit;
        }

        $cuantia_final = $suma_items;

    } else {
        if ($cuantia_final <= 0) {
            header('Location: ../vista/segmentacion.php?err=cuantia0');
            exit;
        }
    }


        // =========================
    // VALIDAR QUE NO EXISTA OTRO PAC CON EL MISMO NÚMERO (IGNORANDO CEROS)
    // =========================
    $chk = $conn->prepare("
        SELECT id
        FROM segmentacion
        WHERE anio = ?
          AND CAST(ref_pac AS UNSIGNED) = ?
        LIMIT 1
    ");
    $chk->bind_param('ii', $anio, $ref_pac_num);
    $chk->execute();
    $resDup = $chk->get_result();

    if ($resDup->num_rows > 0) {
        $chk->close();

        $qs = 'err=dup&anio=' . $anio;
        if (!empty($embed)) {
            $qs .= '&embed=1';
        }

        header('Location: ../vista/segmentacion.php?' . $qs);
        exit;
    }
    $chk->close();


    // =========================
    // INSERTAR CABECERA
    // (porcentaje y categorías vacías: se recalculan luego)
    // =========================
    $conn->begin_transaction();

    $sql = "
        INSERT INTO segmentacion (
            ref_pac,
            cmn,
            objeto_contratacion,
            tipo_proceso_id,
            descripcion,
            programado,
            cuantia,
            anio,
            porcentaje,
            cuantia_categoria,
            riesgo_categoria,
            resultado_segmentacion,
            declarado_desierto,
            pocos_postores,
            mercado_limitado,
            fecha
        )
        VALUES (?,?,?,?,?,?,
                ?,?,
                ?,?,?,?,
                ?,?,?,
                CURDATE())
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
        "sssisididssssss",
        $ref_pac,
        $cmn,
        $objeto_contratacion,
        $tipo_proceso_id,
        $descripcion,
        $programado,
        $cuantia_final,
        $anio,
        $porcentaje,       // se recalcula luego
        $cuantia_categoria,
        $riesgo_categoria,
        $resultado_seg,
        $declarado_desierto,
        $pocos_postores,
        $mercado_limitado
    );

    $stmt->execute();
    $segmentacion_id = $conn->insert_id;
    $stmt->close();

    // =========================
    // INSERTAR ÍTEMS
    // =========================
    if ($tiene_items) {

        $hasOrden = false;
        $chk = $conn->query("SHOW COLUMNS FROM segmentacion_items LIKE 'orden'");
        if ($chk && $chk->num_rows) {
            $hasOrden = true;
        }
        $chk?->close();

        $n   = max(count($items_desc), count($items_monto));
        $ord = 1;

        if ($hasOrden) {

            $stmtItem = $conn->prepare("
                INSERT INTO segmentacion_items (segmentacion_id, descripcion_item, monto_item, orden)
                VALUES (?,?,?,?)
            ");

            for ($i = 0; $i < $n; $i++) {

                $desc  = trim($items_desc[$i] ?? '');
                $monto = (float)($items_monto[$i] ?? 0);

                if ($desc !== '' && $monto > 0) {
                    $stmtItem->bind_param('isdi', $segmentacion_id, $desc, $monto, $ord);
                    $stmtItem->execute();
                    $ord++;
                }
            }
            $stmtItem->close();

        } else {

            $stmtItem = $conn->prepare("
                INSERT INTO segmentacion_items (segmentacion_id, descripcion_item, monto_item)
                VALUES (?,?,?)
            ");

            for ($i = 0; $i < $n; $i++) {

                $desc  = trim($items_desc[$i] ?? '');
                $monto = (float)($items_monto[$i] ?? 0);

                if ($desc !== '' && $monto > 0) {
                    $stmtItem->bind_param('isd', $segmentacion_id, $desc, $monto);
                    $stmtItem->execute();
                }
            }
            $stmtItem->close();
        }
    }

// =========================
// RECÁLCULOS FINALES (ORDEN CORRECTO)
// =========================

// 1) Asegurar que cuantia = suma ítems si procede
if ($tiene_items && function_exists('recalcCuantiaFromItems')) {
    recalcCuantiaFromItems($conn, $segmentacion_id);
}

// 2) Recalcular este registro
if (function_exists('recalcPorcentajeYCuantiaCategoria')) {
    recalcPorcentajeYCuantiaCategoria($conn, $segmentacion_id, $anio);
}

    // 3) 🔥 Recalcular todo el año COMPLETO
    // (TOTAL PAC, porcentajes, cuantía categoría, riesgo y resultado para cada registro)
    if (function_exists('recalcTodoAnio')) {
        recalcTodoAnio($conn, $anio);
    }
 
    // AUDITORÍA
    require_once __DIR__ . '/../modelo/audit_util.php';
    registrar_evento($conn, 'CREAR SEGMENTACION', "Nuevo ID: $segmentacion_id | PAC: $ref_pac");

    $conn->commit();

    // =========================
    // REDIRECCIÓN
    // =========================
    if ($embed) {
        header('Location: ../vista/segmentacion.php?ok=1&embed=1&anio=' . $anio);
    } else {
        header('Location: ../vista/segmentacion_listado.php?ok=1&anio=' . $anio);
    }
    exit;

} catch (Throwable $e) {

    if (isset($conn)) {
        try {
            $conn->rollback();
        } catch (Throwable $x) {
            // silencioso
        }
    }

    // Por defecto, error genérico
    $errCode = 'ex';

    // Si es un error de MySQLi, revisamos si es DUPLICADO
    if ($e instanceof mysqli_sql_exception) {
        $code = (int)$e->getCode();
        $msg  = $e->getMessage();

        // Opcional: log para depuración
        // error_log('Error SQL segmentacion: '.$msg);

        if ($code === 1062 && strpos($msg, 'uq_segmentacion_anio_ref') !== false) {
            // Duplicado de (anio, ref_pac)
            $errCode = 'dup';
        }
    }

    // Armamos la QS para volver al formulario de segmentación
    $qs = 'err=' . $errCode . '&anio=' . $anio;
    if (!empty($embed)) {
        $qs .= '&embed=1';
    }

    header('Location: ../vista/segmentacion.php?' . $qs);
    exit;
}

