<?php
// controlador/cmn_anexo_fase3_save.php
session_start();
date_default_timezone_set('America/Lima');
require_once __DIR__ . '/../modelo/conexion.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dni = trim($_POST['dni'] ?? '');
    $monto_total = trim($_POST['monto_total'] ?? '0');
    $monto_total = str_replace(['S/', 's/', ',', ' '], '', $monto_total);
    
    $region_policial = trim($_POST['region_policial'] ?? '');
    $divopus = trim($_POST['divopus'] ?? '');
    $sub_unidad = trim($_POST['sub_unidad'] ?? '');
    
    // Consultar la BD por el DNI
    $stmt = $conexion->prepare("SELECT nombres, apellidos, region_policial, divpol_divopus, sub_unidad_especifica as sub_unidad FROM cmn_responsables WHERE dni = ?");
    $stmt->bind_param("s", $dni);
    $stmt->execute();
    $res = $stmt->get_result();
    $responsable = $res->fetch_assoc();
    $stmt->close();

    if (!$responsable) {
        $_SESSION['msg_status'] = "error";
        $_SESSION['msg_texto'] = "DNI no válido.";
        header("Location: ../vista/cmn_consolidacion_subir.php");
        exit();
    }

    $nombres_completos = $responsable['nombres'] . " " . $responsable['apellidos'];
    if (empty($region_policial)) $region_policial = $responsable['region_policial'];
    if (empty($divopus)) $divopus = $responsable['divopus'] ?? $responsable['divpol_divopus'];
    if (empty($sub_unidad)) $sub_unidad = $responsable['sub_unidad'];

    // =========================================================================
    // REGLA DE UNICIDAD PRO: Evitar duplicados por Sub Unidad en Fase 3
    // =========================================================================
    $duplicado = $conexion->prepare("SELECT id FROM cmn_anexos_fase3 WHERE TRIM(sub_unidad) = ? AND estado_revision != 2 LIMIT 1");
    $sub_trimmed = trim($sub_unidad);
    $duplicado->bind_param("s", $sub_trimmed);
    $duplicado->execute();
    if ($duplicado->get_result()->num_rows > 0) {
        $duplicado->close();
        $_SESSION['msg_status'] = "error";
        $_SESSION['msg_texto'] = "AVISO: Su Sub Unidad (". $sub_trimmed .") ya cuenta con una Consolidación Final remitida. Si requiere enviarla nuevamente, contacte con la Oficina de Programación.";
        header("Location: ../vista/cmn_consolidacion_subir.php");
        exit();
    }
    $duplicado->close();

    // Manejo de Archivo PDF
    if (isset($_FILES['anexo_pdf']) && $_FILES['anexo_pdf']['error'] === UPLOAD_ERR_OK) {
        $file_tmp = $_FILES['anexo_pdf']['tmp_name'];
        $file_name = $_FILES['anexo_pdf']['name'];
        $file_size = $_FILES['anexo_pdf']['size'];
        $file_type = mime_content_type($file_tmp);

        if ($file_type !== 'application/pdf') {
            $_SESSION['msg_status'] = "error";
            $_SESSION['msg_texto'] = "El archivo debe ser PDF.";
            header("Location: ../vista/cmn_consolidacion_subir.php");
            exit();
        }

        if ($file_size > 5242880) {
            $_SESSION['msg_status'] = "error";
            $_SESSION['msg_texto'] = "Archivo muy pesado.";
            header("Location: ../vista/cmn_consolidacion_subir.php");
            exit();
        }

        // Crear nombre seguro
        $fecha_hora = date('Ymd_His');
        $safe_sub_unidad = preg_replace('/[^A-Za-z0-9\-]/', '_', $sub_unidad);
        $new_filename = "ANEXO03_FINAL_" . $dni . "_" . $safe_sub_unidad . "_" . $fecha_hora . ".pdf";
        
        $upload_dir = __DIR__ . '/../uploads/cmn_fase3/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

        if (move_uploaded_file($file_tmp, $upload_dir . $new_filename)) {
            $ip_cliente = $_SERVER['REMOTE_ADDR'];
            $ruta_db = "uploads/cmn_fase3/" . $new_filename;
            
            $sql = "INSERT INTO cmn_anexos_fase3 (dni_responsable, nombres_completos, region_policial, divopus, sub_unidad, monto_total, archivo_pdf, ip_cliente) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conexion->prepare($sql);
            $stmt->bind_param("sssssdss", $dni, $nombres_completos, $region_policial, $divopus, $sub_unidad, $monto_total, $ruta_db, $ip_cliente);
            
            if ($stmt->execute()) {
                $_SESSION['msg_status'] = "success";
                $_SESSION['msg_texto'] = "¡Anexo N° 03 (FINAL) de ".$sub_unidad." recibido correctamente!";
            } else {
                $_SESSION['msg_status'] = "error";
                $_SESSION['msg_texto'] = "Error en DB.";
            }
            $stmt->close();
        } else {
            $_SESSION['msg_status'] = "error";
            $_SESSION['msg_texto'] = "Error subiendo archivo.";
        }
    } else {
        $_SESSION['msg_status'] = "error";
        $_SESSION['msg_texto'] = "Falta archivo.";
    }

    header("Location: ../vista/cmn_consolidacion_subir.php");
    exit();
}
