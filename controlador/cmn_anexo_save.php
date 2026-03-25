<?php
// controlador/cmn_anexo_save.php
session_start();
date_default_timezone_set('America/Lima');
require_once __DIR__ . '/../modelo/conexion.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // VERIFICAR SI LA FASE ESTÁ ABIERTA
    $res_config = $conexion->query("SELECT valor FROM cmn_config WHERE clave = 'mantenimiento'");
    $config = $res_config->fetch_assoc();
    if ($config && $config['valor'] === '1') {
        $_SESSION['msg_status'] = "error";
        $_SESSION['msg_texto'] = "¡LO SENTIMOS! El plazo para la remisión de documentos ha finalizado. El sistema no acepta nuevos envíos.";
        header("Location: ../vista/cmn_identificacion_subir.php");
        exit();
    }

    $dni = trim($_POST['dni'] ?? '');
    $monto_total = trim($_POST['monto_total'] ?? '0');
    // Limpiar formato de moneda por si pusieron comas o S/
    $monto_total = str_replace(['S/', 's/', ',', ' '], '', $monto_total);
    
    // Estos datos pueden pasarse validando de la BD o directos si vienen en readonly form fields
    $region_policial = trim($_POST['region_policial'] ?? '');
    $divopus = trim($_POST['divopus'] ?? '');
    $sub_unidad = trim($_POST['sub_unidad'] ?? '');
    
    // Fallback: Si no vienen, consultamos la BD por el DNI para mayor seguridad
    $stmt = $conexion->prepare("SELECT grado, cip, nombres, apellidos, region_policial, divpol_divopus, sub_unidad_especifica as sub_unidad FROM cmn_responsables WHERE dni = ?");
    $stmt->bind_param("s", $dni);
    $stmt->execute();
    $res = $stmt->get_result();
    $responsable = $res->fetch_assoc();
    $stmt->close();



    if (!$responsable) {
        $_SESSION['msg_status'] = "error";
        $_SESSION['msg_texto'] = "El DNI ingresado no está registrado en el Padrón de Responsables Logísticos.";
        header("Location: ../vista/cmn_identificacion_subir.php");

        exit();
    }

    $grado = $responsable['grado'] ?? '---';
    $cip = $responsable['cip'] ?? '---';
    $nombres_completos = $responsable['nombres'] . " " . $responsable['apellidos'];


    if (empty($region_policial)) $region_policial = $responsable['region_policial'];
    if (empty($divopus)) $divopus = $responsable['divpol_divopus'];
    // VALIDACIÓN DE DUPLICADOS POR SUBUNIDAD
    // Si ya existe, solo permitimos si está en estado OBSERVADO (2)
    $stmt_check = $conexion->prepare("SELECT id, nombres_completos, fecha_subida, estado_revision FROM cmn_anexos_fase1 WHERE sub_unidad = ? LIMIT 1");
    $stmt_check->bind_param("s", $sub_unidad);
    $stmt_check->execute();
    $res_check = $stmt_check->get_result();
    $existente = $res_check->fetch_assoc();
    $stmt_check->close();

    $anexo_id_upd = null;
    if ($existente) {
        $est_rev = (int)$existente['estado_revision'];
        if ($est_rev !== 2) {
            $fecha = date('d/m/Y', strtotime($existente['fecha_subida']));
            $_SESSION['msg_status'] = "warning";
            $_SESSION['msg_texto'] = "¡ATENCIÓN! El Anexo N° 01 de la subunidad \"".$sub_unidad."\" YA FUE REMITIDO por el responsable logístico ".$existente['nombres_completos']." el día ".$fecha.". No es necesario realizar un nuevo envío.";
            header("Location: ../vista/cmn_identificacion_subir.php");
            exit();
        }
        $anexo_id_upd = $existente['id'];
    }

    // Manejo de Archivo PDF
    if (isset($_FILES['anexo_pdf']) && $_FILES['anexo_pdf']['error'] === UPLOAD_ERR_OK) {
        $file_tmp = $_FILES['anexo_pdf']['tmp_name'];
        $file_name = $_FILES['anexo_pdf']['name'];
        $file_size = $_FILES['anexo_pdf']['size'];
        $file_type = mime_content_type($file_tmp);

        // Validaciones
        if ($file_type !== 'application/pdf') {
            $_SESSION['msg_status'] = "error";
            $_SESSION['msg_texto'] = "El archivo debe ser estrictamente formato PDF.";
            header("Location: ../vista/cmn_identificacion_subir.php");
            exit();
        }

        if ($file_size > 5242880) { // 5MB
            $_SESSION['msg_status'] = "error";
            $_SESSION['msg_texto'] = "El archivo excede el tamaño máximo permitido (5MB).";
            header("Location: ../vista/cmn_identificacion_subir.php");
            exit();
        }

        // Crear nombre seguro y subir
        $extension = pathinfo($file_name, PATHINFO_EXTENSION);
        $fecha_hora = date('Ymd_His');
        $safe_sub_unidad = preg_replace('/[^A-Za-z0-9\-]/', '_', $sub_unidad);
        $new_filename = "ANEXO01_" . $dni . "_" . $safe_sub_unidad . "_" . $fecha_hora . ".pdf";
        
        $upload_dir = __DIR__ . '/../uploads/cmn_fase1/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        $destino = $upload_dir . $new_filename;

        if (move_uploaded_file($file_tmp, $destino)) {
            $ip_cliente = $_SERVER['REMOTE_ADDR'];
            $ruta_db = "uploads/cmn_fase1/" . $new_filename;
            
            if ($anexo_id_upd) {
                // UPDATE: Volvemos a estado RECEPCIONADO (0)
                $sql = "UPDATE cmn_anexos_fase1 SET grado=?, dni_responsable=?, cip=?, nombres_completos=?, region_policial=?, divopus=?, monto_total=?, archivo_pdf=?, ip_cliente=?, estado_revision=0, fecha_subida=NOW() WHERE id=?";
                $stmt = $conexion->prepare($sql);
                $stmt->bind_param("sssssssdssi", $grado, $dni, $cip, $nombres_completos, $region_policial, $divopus, $monto_total, $ruta_db, $ip_cliente, $anexo_id_upd);
                $mensaje_exito = "¡Anexo N° 01 de ".$sub_unidad." ACTUALIZADO Y SUBSANADO correctamente!";
            } else {
                // INSERT: Nuevo registro
                $sql = "INSERT INTO cmn_anexos_fase1 (grado, dni_responsable, cip, nombres_completos, region_policial, divopus, sub_unidad, monto_total, archivo_pdf, ip_cliente, estado_revision) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0)";
                $stmt = $conexion->prepare($sql);
                $stmt->bind_param("sssssssdss", $grado, $dni, $cip, $nombres_completos, $region_policial, $divopus, $sub_unidad, $monto_total, $ruta_db, $ip_cliente);
                $mensaje_exito = "¡Anexo N° 01 de ".$sub_unidad." enviado y recepcionado correctamente!";
            }

            if ($stmt->execute()) {
                $last_id = ($anexo_id_upd) ? $anexo_id_upd : $stmt->insert_id;
                $_SESSION['msg_status'] = "success";
                $_SESSION['msg_texto'] = $mensaje_exito;
                $_SESSION['last_anexo_id'] = $last_id;
            } else {
                $_SESSION['msg_status'] = "error";
                $_SESSION['msg_texto'] = "Error procesando en la Base de Datos: " . $stmt->error;
            }
            $stmt->close();
        } else {
            $_SESSION['msg_status'] = "error";
            $_SESSION['msg_texto'] = "Error al intentar subir el archivo al servidor.";
        }
    } else {
        $_SESSION['msg_status'] = "error";
        $_SESSION['msg_texto'] = "No se adjuntó ningún archivo válido o hubo un error en la carga.";
    }

    header("Location: ../vista/cmn_identificacion_subir.php");

    exit();
} else {
    header("Location: ../vista/cmn_identificacion_subir.php");

    exit();
}
