<?php
// cmn_registro_save.php
header('Content-Type: application/json');
require_once "../modelo/conexion.php";

$response = ["success" => false, "message" => ""];

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Método no permitido.");
    }

    // Datos del formulario
    $grado = $_POST['grado'] ?? '';
    $apellidos = $_POST['apellidos'] ?? '';
    $nombres = $_POST['nombres'] ?? '';
    $dni = $_POST['dni'] ?? '';
    $cip = $_POST['cip'] ?? '';
    $correo = $_POST['correo'] ?? '';
    $celular = $_POST['celular'] ?? '';
    $cargo = $_POST['cargo'] ?? '';
    $region = $_POST['region_policial'] ?? '';
    $divpol = $_POST['divpol_divopus'] ?? '';
    $subunidad = $_POST['sub_unidad'] ?? '';
    $es_borrador = (isset($_POST['es_borrador']) && $_POST['es_borrador'] === '1');
    $ip = $_SERVER['REMOTE_ADDR'];

    // Validaciones básicas
    if (empty($dni)) {
        throw new Exception("DNI es obligatorio para guardar borrador.");
    }

    if (!$es_borrador && (empty($cip) || empty($correo) || empty($cargo))) {
        throw new Exception("DNI, CIP, Correo y Cargo son obligatorios.");
    }

    // Manejo del Archivo PDF
    $archivo_nombre_final = null;
    if (isset($_FILES['solicitud_pdf']) && $_FILES['solicitud_pdf']['error'] === UPLOAD_ERR_OK) {
        $file_tmp = $_FILES['solicitud_pdf']['tmp_name'];
        $file_name = $_FILES['solicitud_pdf']['name'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        if ($file_ext !== 'pdf') {
            throw new Exception("Solo se permiten archivos PDF.");
        }

        // Validar tamaño (Máx 5MB)
        if ($_FILES['solicitud_pdf']['size'] > 5242880) { // 5 * 1024 * 1024
            throw new Exception("El archivo PDF es muy pesado. El límite es 5MB.");
        }

        // Renombrar archivo para evitar conflictos y virus
        $nuevo_nombre = "SOLICITUD_" . $cip . "_" . date('Ymd_His') . ".pdf";
        $directorio_destino = "../uploads/cmn_" . ANIO_CMN . "/";

        if (!is_dir($directorio_destino)) {
            mkdir($directorio_destino, 0777, true);
        }

        if (move_uploaded_file($file_tmp, $directorio_destino . $nuevo_nombre)) {
            $archivo_nombre_final = $nuevo_nombre;
        } else {
            throw new Exception("Error al guardar el archivo PDF.");
        }
    } else {
        // Si no hay archivo y NO es borrador, lanzar error
        if (!$es_borrador) {
            throw new Exception("Debe adjuntar la solicitud firmada en PDF.");
        }
    }

    // Verificar si ya existe el DNI para permitir actualización u observar duplicados
    $stmt_check = $conexion->prepare("SELECT id, archivo_pdf, estado FROM cmn_responsables WHERE dni = ?");
    $stmt_check->bind_param("s", $dni);
    $stmt_check->execute();
    $res_check = $stmt_check->get_result();
    $existe = $res_check->fetch_assoc();

    // Si ya existe y tiene PDF finalizado (y NO está observado), bloquear.
    if ($existe && !empty($existe['archivo_pdf']) && $existe['estado'] == 0) {
        $response["success"] = false;
        $response["message"] = "Su registro ya fue completado exitosamente con este DNI. No es necesario realizarlo nuevamente.";
        $response["dni"] = $dni;
        $response["duplicated"] = true;
        echo json_encode($response);
        exit;
    }

    if ($existe) {
        // ACTUALIZAR (UPSERT)
        // Si el registro estaba observado, al subir el nuevo PDF se debe limpiar el estado
        $nuevo_estado = ($es_borrador) ? $existe['estado'] : 0;

        $sql = "UPDATE cmn_responsables SET 
                    grado=?, apellidos=?, nombres=?, cip=?, correo=?, celular=?, cargo=?,
                    region_policial=?, divpol_divopus=?, sub_unidad_especifica=?, 
                    archivo_pdf=COALESCE(?, archivo_pdf), ip_registro=?, estado=?
                WHERE dni=?";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param(
            "ssssssssssssis",
            $grado,
            $apellidos,
            $nombres,
            $cip,
            $correo,
            $celular,
            $cargo,
            $region,
            $divpol,
            $subunidad,
            $archivo_nombre_final,
            $ip,
            $nuevo_estado,
            $dni
        );
    } else {
        // Insertar nuevo
        $sql = "INSERT INTO cmn_responsables (
                    grado, apellidos, nombres, dni, cip, correo, celular, cargo,
                    region_policial, divpol_divopus, sub_unidad_especifica, 
                    archivo_pdf, ip_registro, anio_proceso
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conexion->prepare($sql);
        $anioActualCmn = ANIO_CMN;
        $stmt->bind_param(
            "sssssssssssssi",
            $grado,
            $apellidos,
            $nombres,
            $dni,
            $cip,
            $correo,
            $celular,
            $cargo,
            $region,
            $divpol,
            $subunidad,
            $archivo_nombre_final,
            $ip,
            $anioActualCmn
        );
    }

    if ($stmt->execute()) {
        $response["success"] = true;
        $response["dni"] = $dni;
        if ($es_borrador) {
            $response["message"] = "Borrador guardado. Puede continuar más tarde.";
            $response["is_borrador"] = true;
        } else {
            $response["message"] = "Su registro ha sido completado con éxito.";
            $response["is_borrador"] = false;
        }
    } else {
        throw new Exception("Error al procesar en la base de datos.");
    }

} catch (Exception $e) {
    $response["message"] = $e->getMessage();
}

echo json_encode($response);
exit;
