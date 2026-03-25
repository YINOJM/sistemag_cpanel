<?php
require_once "../modelo/conexion.php";

header('Content-Type: application/json');

$dni = $_GET['dni'] ?? '';

if (strlen($dni) !== 8) {
    echo json_encode(['exists' => false]);
    exit;
}

try {
    // 1. Buscar al responsable logístico por DNI en el padrón
    $stmt = $conexion->prepare("SELECT * FROM cmn_responsables WHERE dni = ? AND archivo_pdf IS NOT NULL ORDER BY id DESC LIMIT 1");
    $stmt->bind_param("s", $dni);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();

    if ($data) {
        $sub_unidad      = trim($data['sub_unidad_especifica']);
        $region_policial = trim($data['region_policial']);
        $divpol_divopus  = trim($data['divpol_divopus']);
        $has_annex       = false;
        $annex_details   = null;

        // =====================================================================
        // REGLA DE UNICIDAD: El Anexo N°1 es ÚNICO por SUB-UNIDAD.
        // Jerarquía: REGIÓN > DIVOPUS > SUB-UNIDAD
        // Se verifica SOLO por sub_unidad (nombre). El DNI no bloquea.
        // =====================================================================
        $check = $conexion->prepare(
            "SELECT 
                a.nombres_completos, 
                a.fecha_subida,
                a.region_policial,
                a.divopus,
                a.sub_unidad,
                a.estado_revision
             FROM cmn_anexos_fase1 a
             WHERE TRIM(a.sub_unidad) = ?
             LIMIT 1"
        );

        if ($check) {
            $check->bind_param("s", $sub_unidad);
            $check->execute();
            $res_check = $check->get_result();
            if ($res_row = $res_check->fetch_assoc()) {
                $est_rev = (int)$res_row['estado_revision'];
                // REGLA: Si está OBSERVADO (2), permitimos re-subir (has_annex = false)
                if ($est_rev !== 2) {
                    $has_annex = true;
                    $annex_details = [
                        'responsable'     => $res_row['nombres_completos'],
                        'fecha'           => date('d/m/Y H:i', strtotime($res_row['fecha_subida'])),
                        'region_policial' => $res_row['region_policial'],
                        'divopus'         => $res_row['divopus'],
                        'sub_unidad'      => $res_row['sub_unidad']
                    ];
                }
            }
            $check->close();
        }

        echo json_encode([
            'exists'        => true,
            'has_annex'     => $has_annex,
            'annex_details' => $annex_details,
            'finalizado'    => (!empty($data['archivo_pdf']) && $data['estado'] == 0),
            'estado'        => (int)$data['estado'],
            'data'          => [
                'grado'           => $data['grado'],
                'cip'             => $data['cip'],
                'apellidos'       => $data['apellidos'],
                'nombres'         => $data['nombres'],
                'correo'          => $data['correo'],
                'celular'         => $data['celular'],
                'region_policial' => $data['region_policial'],
                'divpol_divopus'  => $data['divpol_divopus'],
                'sub_unidad'      => $sub_unidad
            ]
        ]);
    } else {
        echo json_encode(['exists' => false]);
    }
} catch (Exception $e) {
    echo json_encode(['exists' => false, 'error' => $e->getMessage()]);
}
