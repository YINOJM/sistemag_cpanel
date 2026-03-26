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
    $stmt = $conexion->prepare("SELECT * FROM cmn_responsables WHERE dni = ? ORDER BY id DESC LIMIT 1");
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

        // Determinar qué fase estamos verificando
        $fase = isset($_GET['fase']) ? (int)$_GET['fase'] : 1;
        $tabla = "cmn_anexos_fase1";
        if ($fase === 2) $tabla = "cmn_anexos_fase2";
        if ($fase === 3) $tabla = "cmn_anexos_fase3";

        // =====================================================================
        // REGLA DE UNICIDAD: El Anexo es ÚNICO por SUB-UNIDAD en cada Fase.
        // =====================================================================
        $check = $conexion->prepare(
            "SELECT 
                a.nombres_completos, 
                a.fecha_subida,
                a.region_policial,
                a.divopus,
                a.sub_unidad,
                a.estado_revision
             FROM $tabla a
             WHERE (TRIM(a.sub_unidad) = ? OR TRIM(a.sub_unidad) = ?)
             LIMIT 1"
        );

        if ($check) {
            // Buscamos tanto por el nombre exacto como por el nombre del padrón por si hay variaciones de espacios
            $check->bind_param("ss", $sub_unidad, $data['sub_unidad_especifica']);
            $check->execute();
            $res_check = $check->get_result();
            if ($res_row = $res_check->fetch_assoc()) {
                $est_rev = isset($res_row['estado_revision']) ? (int)$res_row['estado_revision'] : 0;
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
                'cargo'           => $data['cargo'],
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
