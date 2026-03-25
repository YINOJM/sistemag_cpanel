<?php
ob_start();
// controlador/RendicionesControlador.php
require_once __DIR__ . "/../modelo/conexion.php";

// Auto-creación de tabla si no existe
// 1. Registrar el módulo si no existe para que aparezca en el menú y permita permisos
$conexion->query("INSERT IGNORE INTO modulos (nombre, slug, enlace, icono, categoria, orden, activo) 
VALUES ('Rendiciones', 'rendiciones', 'vista/ren_listado.php', 'fa-file-invoice-dollar', 'OPERATIVOS', 15, 1)");

$sql_check = "CREATE TABLE IF NOT EXISTS ren_rendiciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    dni VARCHAR(15),
    cip VARCHAR(15),
    grado VARCHAR(50),
    apellidos_nombres VARCHAR(255),
    lugar_comision VARCHAR(100),
    id_subunidad INT NULL,
    region_cache VARCHAR(150) NULL,
    division_cache VARCHAR(150) NULL,
    unidad VARCHAR(150),
    cuenta_ahorros VARCHAR(50) NULL,
    fecha_inicio DATE NULL,
    fecha_retorno DATE NULL,
    nro_liquidacion VARCHAR(20) NULL,
    igv DECIMAL(10,2) DEFAULT 0,
    dias INT DEFAULT 0,
    primer_deposito DECIMAL(10,2) DEFAULT 0,
    siaf_expediente VARCHAR(20) NULL,
    pasajes DECIMAL(10,2) DEFAULT 0,
    total_depositado DECIMAL(10,2) DEFAULT 0,
    estado_rendicion ENUM('Pendiente', 'Rendido', 'Observado') DEFAULT 'Pendiente',
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    usuario_registro INT NULL,
    archivo_sustento VARCHAR(255) NULL,
    grupo_importacion VARCHAR(150) NULL,
    ht_ref VARCHAR(50) NULL,
    anio_fiscal INT DEFAULT 2026
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
$conexion->query($sql_check);

// Verificador de columnas faltantes - Versión Robusta
$res_cols = $conexion->query("DESCRIBE ren_rendiciones");
$existing_cols = [];
while ($c = $res_cols->fetch_assoc())
    $existing_cols[] = strtolower($c['Field']);

$cols_to_ensure = [
    'id_subunidad' => 'INT NULL AFTER lugar_comision',
    'region_cache' => 'VARCHAR(150) NULL AFTER id_subunidad',
    'division_cache' => 'VARCHAR(150) NULL AFTER region_cache',
    'cuenta_ahorros' => 'VARCHAR(50) NULL AFTER unidad',
    'nro_liquidacion' => 'VARCHAR(20) NULL AFTER fecha_retorno',
    'igv' => 'DECIMAL(10,2) DEFAULT 0 AFTER nro_liquidacion',
    'dias' => 'INT DEFAULT 0 AFTER igv',
    'pasajes' => 'DECIMAL(10,2) DEFAULT 0 AFTER siaf_expediente',
    'total_depositado' => 'DECIMAL(10,2) DEFAULT 0 AFTER pasajes',
    'usuario_registro' => 'INT NULL AFTER fecha_registro',
    'grupo_importacion' => 'VARCHAR(150) NULL AFTER archivo_sustento',
    'ht_ref' => 'VARCHAR(50) NULL AFTER grupo_importacion',
    'anio_fiscal' => 'INT DEFAULT 2026 AFTER ht_ref'
];

foreach ($cols_to_ensure as $col_name => $col_definition) {
    if (!in_array(strtolower($col_name), $existing_cols)) {
        $conexion->query("ALTER TABLE ren_rendiciones ADD $col_name $col_definition");
    }
}

// Tabla de Configuración de Plantillas
$conexion->query("CREATE TABLE IF NOT EXISTS ren_configuracion (
    id INT AUTO_INCREMENT PRIMARY KEY,
    clave VARCHAR(50) UNIQUE,
    valor TEXT,
    descripcion VARCHAR(255)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

// Insertar plantilla por defecto si no existe
$check_plantilla = $conexion->query("SELECT id FROM ren_configuracion WHERE clave = 'plantilla_notificacion'");
if ($check_plantilla->num_rows == 0) {
    $default_data = [
        "header" => "POLICÍA NACIONAL DEL PERÚ\nREGIÓN POLICIAL LIMA\nUNIDAD DE ADMINISTRACIÓN - UE 009",
        "intro" => "Mediante el presente, se le hace de conocimiento que esta Unidad de Administración (Unidad Ejecutora N°009-VII DIRTEPOL LIMA) - Área de Contabilidad, de la Región Policial Lima, ha dispuesto ejecutar el control concurrente interno de las RENDICIONES DE CUENTAS de los pagos efectuados en el presente Año Fiscal {{ANIO_FISCAL}}, por el concepto de viáticos por Comisión del servicio; ante ello, del resultado de verificación en el Sistema Integrado de Administración Financiera (SIAF) del Registro SIAF de la referencia, se evidencia lo siguiente:",
        "item1" => "El administrado no cumplió con la presentación de la respectiva Rendición de Cuentas correspondiente de la comisión del servicio realizada en la ciudad de {{LUGAR}} del {{FECHAS}}, por el DEPOSITO en la especifica de gasto 23.21.22 “VIATICOS Y ASIGNACIONES POR COMISIÓN DEL SERVICIO” el importe total de: {{MONTO_LETRAS}} (S/. {{MONTO_NUM}})",
        "item2" => "En tal sentido, conforme a la Directiva N° 08-2023-COMGEN-PNP/SECEJE-DIRADM, RCG N.º 182-2023-CG PNP/EMG del 16MAY2023, directiva que regula el referido concepto, establece que la presentación oportuna de las Rendiciones de Cuentas por el importe total es de exclusiva responsabilidad del comisionado; efectuándose dentro de los DIEZ (10) días hábiles de culminada la comisión, debiendo presentar la documentación correctamente sellada, firmada y foliada a la Unidad Ejecutora que otorgo los viáticos.",
        "item3" => "En esa línea, el expediente que generó el registro - SIAF Nº {{SIAF}} deberá ser PRESENTADO EN EL PLAZO DE 72 HORAS A PARTIR DE LA NOTIFICACIÓN, vencido el plazo se procederá conforme a lo previsto en la misma directiva: numerales 6.1.1.3.12 y 6.1.1.3.13, con los cuales se dispone que las Unidades Ejecutoras procedan a informar a Inspectoría General PNP, para las acciones administrativas pertinentes.",
        "outro" => "Con el propósito de fortalecer el control administrativo del Área de Contabilidad, la Unidad Ejecutora 009-VII-DIRTEPOL-LIMA-REGPOL-LIMA, CUMPLE CON NOTIFICARLE, con la finalidad de que regularice la respectiva Rendición de Cuentas, cumpliendo de este modo con el requisito exigido por el TUO de la Ley N.º 27444 – Ley del Procedimiento Administrativo General, relacionado a la notificación de actos administrativos; así, como el debido procedimiento para adoptar acciones administrativas disciplinarias y de conformidad a la Ley Nº 30714 Ley que regula el Régimen Disciplinario de la PNP, Código: L 36 Infracción: “ No cumplir de manera oportuna o reglamentaria con la rendición de cuentas de dinero o la remisión de documentos que justifiquen la entrega de especies, bienes o enseres recibidos para el servicio policial, siempre que no constituya infracción grave” Sanción: de 6 a 10 días de sanción simple."
    ];
    $default_txt = $conexion->real_escape_string(json_encode($default_data));
    $conexion->query("INSERT INTO ren_configuracion (clave, valor, descripcion) VALUES ('plantilla_notificacion', '$default_txt', 'Textos base para la notificación PDF')");
}

$op = $_GET['op'] ?? '';
$uid = $_SESSION['id_usuario'] ?? 1; // Default 1 para evitar errores si no hay sesión

switch ($op) {
    case 'get_grupos':
        $anio = isset($_GET['anio']) ? (int)$_GET['anio'] : ANIO_FISCAL;
        $query = "SELECT DISTINCT IF(grupo_importacion IS NULL OR grupo_importacion = '', 'LOTE INICIAL / OTROS', grupo_importacion) as nombre 
                  FROM ren_rendiciones WHERE anio_fiscal = $anio ORDER BY nombre ASC";
        $res = $conexion->query($query);
        $grupos = [];
        while ($row = $res->fetch_assoc()) {
            $grupos[] = $row['nombre'];
        }
        ob_clean();
        echo json_encode($grupos);
        break;

    case 'get_regiones':
        $res = $conexion->query("SELECT id_region as id, nombre_region as nombre FROM regiones_policiales WHERE estado = 1 ORDER BY nombre_region ASC");
        $data = [];
        while ($row = $res->fetch_assoc()) $data[] = $row;
        echo json_encode($data);
        break;

    case 'get_divisiones':
        $region = $_GET['region'] ?? '';
        $stmt = $conexion->prepare("SELECT d.id_division as id, d.nombre_division as nombre FROM divisiones_policiales d JOIN regiones_policiales r ON d.id_region = r.id_region WHERE r.nombre_region = ? AND d.estado = 1 ORDER BY d.nombre_division ASC");
        $stmt->bind_param("s", $region);
        $stmt->execute();
        $res = $stmt->get_result();
        $data = [];
        while ($row = $res->fetch_assoc()) $data[] = $row;
        echo json_encode($data);
        break;

    case 'get_unidades':
        $division = $_GET['division'] ?? '';
        $stmt = $conexion->prepare("SELECT s.id_subunidad as id, s.nombre_subunidad as nombre FROM sub_unidades_policiales s JOIN divisiones_policiales d ON s.id_division = d.id_division WHERE d.nombre_division = ? AND s.estado = 1 ORDER BY s.nombre_subunidad ASC");
        $stmt->bind_param("s", $division);
        $stmt->execute();
        $res = $stmt->get_result();
        $data = [];
        while ($row = $res->fetch_assoc()) $data[] = $row;
        echo json_encode($data);
        break;

    case 'listar':
        $search = $_GET['search']['value'] ?? '';
        $estado = $_GET['estado'] ?? '';
        $grupo  = $_GET['grupo'] ?? '';
        $anio   = isset($_GET['anio']) ? (int)$_GET['anio'] : ANIO_FISCAL;

        $where = "WHERE anio_fiscal = $anio";
        if (!empty($search)) {
            $s = $conexion->real_escape_string($search);
            $where .= " AND (dni LIKE '%$s%' OR cip LIKE '%$s%' OR apellidos_nombres LIKE '%$s%' OR lugar_comision LIKE '%$s%')";
        }
        if (!empty($estado)) {
            $e = $conexion->real_escape_string($estado);
            $where .= " AND estado_rendicion = '$e'";
        }
        if (!empty($grupo)) {
            $g = $conexion->real_escape_string($grupo);
            if ($g === 'LOTE INICIAL / OTROS') {
                $where .= " AND (grupo_importacion IS NULL OR grupo_importacion = '')";
            } else {
                $where .= " AND grupo_importacion LIKE '%$g%'";
            }
        }

        // Paginación DataTables
        $start = $_GET['start'] ?? 0;
        $length = $_GET['length'] ?? 10;

        $sql_total = "SELECT COUNT(*) as total FROM ren_rendiciones $where";
        $res_total = $conexion->query($sql_total);
        $total = $res_total->fetch_assoc()['total'];

        $sql = "SELECT * FROM ren_rendiciones $where ORDER BY fecha_registro DESC LIMIT $start, $length";
        $result = $conexion->query($sql);

        $data = [];
        $i = $start + 1;
        while ($row = $result->fetch_assoc()) {
            $data[] = [
                "nro" => $i++,
                "dni" => $row['dni'],
                "cip" => $row['cip'],
                "grado" => $row['grado'],
                "personal" => $row['apellidos_nombres'],
                "lugar" => $row['lugar_comision'],
                "unidad" => $row['unidad'],
                "region" => $row['region_cache'] ?? '',
                "division" => $row['division_cache'] ?? '',
                "fecha_inicio" => $row['fecha_inicio'],
                "fecha_retorno" => $row['fecha_retorno'],
                "total" => number_format($row['total_depositado'], 2),
                "estado" => $row['estado_rendicion'],
                "grupo" => $row['grupo_importacion'] ?? '',
                "ht_ref" => $row['ht_ref'] ?? '',
                "id" => $row['id']
            ];
        }

        ob_clean();
        header('Content-Type: application/json');
        echo json_encode([
            "draw" => intval($_GET['draw'] ?? 1),
            "recordsTotal" => $total,
            "recordsFiltered" => $total,
            "data" => $data
        ]);
        break;

    case 'cambiar_estado':
        $id = $_POST['id'] ?? 0;
        $nuevo_estado = $_POST['estado'] ?? '';

        $stmt = $conexion->prepare("UPDATE ren_rendiciones SET estado_rendicion = ? WHERE id = ?");
        $stmt->bind_param("si", $nuevo_estado, $id);
        if ($stmt->execute()) {
            echo json_encode(["status" => "success", "message" => "Estado actualizado"]);
        } else {
            echo json_encode(["status" => "error", "message" => $conexion->error]);
        }
        break;

    case 'stats':
        $anio = (isset($_GET['anio']) ? (int)$_GET['anio'] : ANIO_FISCAL);
        $res = $conexion->query("SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN estado_rendicion = 'Pendiente' THEN 1 ELSE 0 END) as pendientes,
            SUM(CASE WHEN estado_rendicion = 'Rendido' THEN 1 ELSE 0 END) as rendidos,
            SUM(CASE WHEN estado_rendicion = 'Observado' THEN 1 ELSE 0 END) as observados
            FROM ren_rendiciones WHERE anio_fiscal = $anio");
        $resp = $res->fetch_assoc();
        ob_clean();
        header('Content-Type: application/json');
        echo json_encode($resp);
        break;

    case 'limpiar_todo':
        $res = $conexion->query("DELETE FROM ren_rendiciones");
        if ($res) {
            $conexion->query("ALTER TABLE ren_rendiciones AUTO_INCREMENT = 1");
            echo json_encode(["status" => "success"]);
        } else {
            echo json_encode(["status" => "error"]);
        }
        break;

    case 'buscar_personal':
        $dni = $_GET['dni'] ?? '';
        $stmt = $conexion->prepare("
            SELECT p.id_personal, p.cip, p.dni, CONCAT(p.apellidos, ', ', p.nombres) as nombres, g.nombre_grado, s.id_subunidad, r.nombre_region, d.nombre_division, s.nombre_subunidad
            FROM mae_personal p
            JOIN mae_grados g ON p.id_grado = g.id_grado
            JOIN sub_unidades_policiales s ON p.id_subunidad = s.id_subunidad
            JOIN divisiones_policiales d ON s.id_division = d.id_division
            JOIN regiones_policiales r ON d.id_region = r.id_region
            WHERE p.dni = ? LIMIT 1
        ");

        if (!$stmt) {
            ob_clean();
            header('Content-Type: application/json');
            echo json_encode(["status" => false, "message" => $conexion->error]);
            break;
        }

        $stmt->bind_param("s", $dni);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($row = $res->fetch_assoc()) {
            $resp = [
                "status" => true,
                "nombres" => $row['nombres'],
                "cip" => $row['cip'],
                "grado" => $row['nombre_grado'], // Corregido: antes decía 'grado'
                "id_subunidad" => $row['id_subunidad'],
                "region" => $row['nombre_region'],
                "division" => $row['nombre_division'],
                "unidad" => $row['nombre_subunidad']
            ];
        } else {
            $resp = ["status" => false];
        }
        ob_clean();
        header('Content-Type: application/json');
        echo json_encode($resp);
        break;

    case 'registrar_manual':
        $dni = $_POST['dni'] ?? '';
        $cip = $_POST['cip'] ?? '';
        $grado = $_POST['grado'] ?? '';
        $nombres = $_POST['nombres'] ?? '';
        $lugar = $_POST['lugar'] ?? '';
        $fecha_ini = (!empty($_POST['fecha_ini'])) ? $_POST['fecha_ini'] : null;
        $fecha_ret = (!empty($_POST['fecha_ret'])) ? $_POST['fecha_ret'] : null;
        $siaf = $_POST['siaf'] ?? '';
        $total = floatval($_POST['total'] ?? 0);
        $liq = $_POST['liq'] ?? '';
        $ht_ref = $_POST['ht_ref'] ?? '';
        $uid = $_SESSION['id'] ?? 0;
        $anio_fiscal = isset($_POST['anio']) ? (int)$_POST['anio'] : ANIO_FISCAL;

        $region_cache = $_POST['region_cache'] ?? '';
        $division_cache = $_POST['division_cache'] ?? '';
        $id_subunidad = (isset($_POST['id_subunidad']) && $_POST['id_subunidad'] !== '') ? (int) $_POST['id_subunidad'] : null;
        $unidad_nombre = $_POST['unidad_nombre'] ?? '';

        $stmt = $conexion->prepare("INSERT INTO ren_rendiciones (
            dni, cip, grado, apellidos_nombres, lugar_comision, 
            id_subunidad, region_cache, division_cache, unidad, 
            fecha_inicio, fecha_retorno, siaf_expediente, total_depositado, 
            nro_liquidacion, usuario_registro, anio_fiscal, ht_ref
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

        if (!$stmt) {
            ob_clean();
            header('Content-Type: application/json');
            echo json_encode(["status" => "error", "message" => "Error preparando consulta: " . $conexion->error]);
            break;
        }

        $stmt->bind_param(
            "sssssissssssdsiis",
            $dni,
            $cip,
            $grado,
            $nombres,
            $lugar,
            $id_subunidad,
            $region_cache,
            $division_cache,
            $unidad_nombre,
            $fecha_ini,
            $fecha_ret,
            $siaf,
            $total,
            $liq,
            $uid,
            $anio_fiscal,
            $ht_ref
        );

        if ($stmt->execute()) {
            ob_clean();
            header('Content-Type: application/json');
            echo json_encode(["status" => "success"]);
        } else {
            $error = $stmt->error ?: $conexion->error;
            ob_clean();
            header('Content-Type: application/json');
            echo json_encode(["status" => "error", "message" => "Error DB: " . $error]);
        }
        break;

    case 'listar_grados':
        $res = $conexion->query("SELECT id_grado as id, nombre_grado as nombre FROM mae_grados ORDER BY id_grado ASC");
        $grados = [];
        while ($row = $res->fetch_assoc())
            $grados[] = $row;
        echo json_encode($grados);
        break;

    case 'eliminar_lote':
        $grupo = $_POST['grupo'] ?? '';
        if (empty($grupo)) {
            echo json_encode(["status" => "error", "message" => "Nombre de lote no recibido"]);
            break;
        }

        if ($grupo === 'LOTE INICIAL / OTROS') {
            $stmt = $conexion->prepare("DELETE FROM ren_rendiciones WHERE grupo_importacion IS NULL OR grupo_importacion = ''");
        } else {
            $stmt = $conexion->prepare("DELETE FROM ren_rendiciones WHERE grupo_importacion = ?");
            $stmt->bind_param("s", $grupo);
        }

        ob_clean();
        header('Content-Type: application/json');
        if ($stmt->execute()) {
            echo json_encode(["status" => "success"]);
        } else {
            echo json_encode(["status" => "error", "message" => $conexion->error]);
        }
        break;

    case 'get_config':
        $clave = $_GET['clave'] ?? '';
        $res = $conexion->query("SELECT valor FROM ren_configuracion WHERE clave = '$clave'");
        echo json_encode($res->fetch_assoc());
        break;

    case 'save_config':
        $clave = $_POST['clave'] ?? '';
        $valor = $_POST['valor'] ?? '';
        $stmt = $conexion->prepare("UPDATE ren_configuracion SET valor = ? WHERE clave = ?");
        $stmt->bind_param("ss", $valor, $clave);
        if ($stmt->execute()) {
            echo json_encode(["status" => "success"]);
        } else {
            echo json_encode(["status" => "error", "message" => $conexion->error]);
        }
        break;

    case 'eliminar':
        $id = $_POST['id'] ?? 0;
        $stmt = $conexion->prepare("DELETE FROM ren_rendiciones WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $resp = ["status" => "success"];
        } else {
            $resp = ["status" => "error"];
        }
        ob_clean();
        header('Content-Type: application/json');
        echo json_encode($resp);
        break;

    case 'mostrar':
        $id = $_POST['id'] ?? 0;
        $stmt = $conexion->prepare("SELECT * FROM ren_rendiciones WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $res = $stmt->get_result();
        ob_clean();
        header('Content-Type: application/json');
        echo json_encode($res->fetch_assoc());
        break;

    case 'editar':
        $id = $_POST['id'] ?? 0;
        $id_subunidad = (isset($_POST['id_subunidad']) && $_POST['id_subunidad'] !== '') ? (int) $_POST['id_subunidad'] : null;
        $total = floatval($_POST['total_depositado'] ?? 0);

        // Obtenemos los nombres de región y división para el cache
        $region_cache = '';
        $division_cache = '';
        $unidad_nombre = '';

        // Nuevos campos adicionales
        $dni = $_POST['dni'] ?? '';
        $cip = $_POST['cip'] ?? '';
        $grado = $_POST['grado'] ?? '';
        $nombres = $_POST['apellidos_nombres'] ?? '';
        $lugar = $_POST['lugar_comision'] ?? '';

        $cuenta = $_POST['cuenta_ahorros'] ?? '';
        $fecha_ini = $_POST['fecha_inicio'] ?? null;
        $fecha_ret = $_POST['fecha_retorno'] ?? null;
        $liq = $_POST['nro_liquidacion'] ?? '';
        $igv = floatval($_POST['igv'] ?? 0);
        $dias = intval($_POST['dias'] ?? 0);
        $siaf = $_POST['siaf_expediente'] ?? '';
        $ht_ref = $_POST['ht_ref'] ?? '';
        $deposito1 = floatval($_POST['primer_deposito'] ?? 0);
        $pasajes = floatval($_POST['pasajes'] ?? 0);

        if ($id_subunidad) {
            $stmtUni = $conexion->prepare("
                SELECT s.nombre_subunidad, d.nombre_division, r.nombre_region 
                FROM sub_unidades_policiales s
                JOIN divisiones_policiales d ON s.id_division = d.id_division
                JOIN regiones_policiales r ON d.id_region = r.id_region
                WHERE s.id_subunidad = ?
            ");
            $stmtUni->bind_param("i", $id_subunidad);
            $stmtUni->execute();
            $resUni = $stmtUni->get_result();
            if ($rowUni = $resUni->fetch_assoc()) {
                $region_cache = $rowUni['nombre_region'];
                $division_cache = $rowUni['nombre_division'];
                $unidad_nombre = $rowUni['nombre_subunidad'];
            }
        }
        $stmt = $conexion->prepare("UPDATE ren_rendiciones SET 
            dni = ?, cip = ?, grado = ?, apellidos_nombres = ?, lugar_comision = ?,
            id_subunidad = ?, region_cache = ?, division_cache = ?, unidad = ?, 
            cuenta_ahorros = ?, fecha_inicio = ?, fecha_retorno = ?, nro_liquidacion = ?,
            igv = ?, dias = ?, siaf_expediente = ?, primer_deposito = ?, pasajes = ?,
            total_depositado = ?, ht_ref = ?
            WHERE id = ?");

        $stmt->bind_param(
            "sssssisssssssdiddddsi",
            $dni,
            $cip,
            $grado,
            $nombres,
            $lugar,
            $id_subunidad,
            $region_cache,
            $division_cache,
            $unidad_nombre,
            $cuenta,
            $fecha_ini,
            $fecha_ret,
            $liq,
            $igv,
            $dias,
            $siaf,
            $deposito1,
            $pasajes,
            $total,
            $ht_ref,
            $id
        );

        ob_clean();
        header('Content-Type: application/json');
        if ($stmt->execute()) {
            echo json_encode(["status" => true, "msg" => "Registro actualizado correctamente"]);
        } else {
            echo json_encode(["status" => false, "msg" => $conexion->error]);
        }
        break;
}
ob_end_flush();
