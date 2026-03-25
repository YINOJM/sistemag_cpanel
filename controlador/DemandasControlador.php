<?php
// controlador/DemandasControlador.php

// 1. CARGAR CONEXIÓN Y ENTORNO (Centralizado)
require_once __DIR__ . '/../modelo/conexion.php';
require_once "../modelo/DemandasModelo.php";

// session_start(); removido porque lo maneja conexion.php -> sesion_config.php

$op = isset($_GET["op"]) ? $_GET["op"] : "";

switch ($op) {
 case 'listar':
        // --- NUEVO: Recibir el año desde la petición (por defecto null) ---
        $anio = isset($_GET['anio']) ? $_GET['anio'] : null;
        
        // Pasamos el año al modelo
        $rspta = DemandasModelo::listar($anio);
        $data = [];
        foreach ($rspta as $reg) {
            $estadoStr = '';
            if($reg['estado'] == 'Pendiente') {
                $estadoStr = '<span class="badge bg-warning text-dark"><i class="fas fa-clock fs-10 pb-1 pr-1"></i> Pendiente</span>';
            } else if($reg['estado'] == 'Aprobado') {
                $estadoStr = '<span class="badge bg-success"><i class="fas fa-check-circle fs-10 pb-1 pr-1"></i> Aprobado</span>';
            } else {
                $estadoStr = '<span class="badge bg-danger"><i class="fas fa-times-circle fs-10 pb-1 pr-1"></i> Rechazado</span>';
            }
            
        $mi_rol = strtoupper(trim($_SESSION['rol'] ?? ''));
$es_admin = ($mi_rol === 'SUPER ADMINISTRADOR' || $mi_rol === 'SUPER ADMINISTRATOR');

$puedeEditar = ($es_admin || isset($_SESSION['permisos']['DEMANDAS_PRESUPUESTALES']['EDITAR']));
$puedeEliminar = ($es_admin || isset($_SESSION['permisos']['DEMANDAS_PRESUPUESTALES']['ELIMINAR']));

            
           $botones = '<div class="d-flex justify-content-center gap-1">
                <button class="btn-acc-pro btn-acc-view" onclick="verDetalle('.$reg['id_demanda'].')" title="Ver Detalles"><i class="fas fa-eye"></i></button>';
if ($puedeEditar) {
    $botones .= '<button class="btn-acc-pro btn-acc-edit" onclick="editarDemanda('.$reg['id_demanda'].')" title="Editar Demanda"><i class="fas fa-edit"></i></button>';
}
if ($puedeEliminar) {
    $botones .= '<button class="btn-acc-pro btn-acc-del" onclick="eliminarDemanda('.$reg['id_demanda'].')" title="Eliminar Demanda"><i class="fas fa-trash"></i></button>';
}
$botones .= '</div>';

            
$data[] = array(
    "0" => $reg['id_demanda'],
    "1" => '<div class="text-clamp-2 pe-cursor" title="'.htmlspecialchars($reg['descripcion_general']).'" data-bs-toggle="tooltip">'.$reg['descripcion_general'].'</div>',
    "2" => $reg['cui'] ? $reg['cui'] : '-',
    "3" => $reg['nro_expediente'] ? $reg['nro_expediente'] : '-',
    "4" => number_format($reg['total_presupuesto'], 2, '.', ','),
    "5" => '<span class="text-nowrap">'.date("d/m/Y H:i", strtotime($reg['fecha_registro'])).'</span>',
    "6" => $estadoStr,
    "7" => $botones
);

        }
        echo json_encode([
            "sEcho" => 1,
            "iTotalRecords" => count($data),
            "iTotalDisplayRecords" => count($data),
            "aaData" => $data
        ]);
        break;

    case 'guardar':
     $mi_rol = strtoupper(trim($_SESSION['rol'] ?? ''));
if ($mi_rol !== 'SUPER ADMINISTRADOR' && $mi_rol !== 'SUPER ADMINISTRATOR' && !isset($_SESSION['permisos']['DEMANDAS_PRESUPUESTALES']['CREAR'])) {

            echo json_encode(['status' => false, 'msg' => 'Acceso denegado.']);
            exit();
        }
        
        $inputJSON = file_get_contents('php://input');
        $input = json_decode($inputJSON, TRUE); 
        
        if(!$input) {
            echo json_encode(['status' => false, 'msg' => 'Datos inválidos.']);
            exit();
        }

        $cui = isset($input["cui"]) ? trim($input["cui"]) : "";
        $nro_expediente = isset($input["nro_expediente"]) ? trim($input["nro_expediente"]) : "";
        $descripcion_general = isset($input["descripcion_general"]) ? trim($input["descripcion_general"]) : "";
        $total_presupuesto = isset($input["total_presupuesto"]) ? floatval($input["total_presupuesto"]) : 0.00;
        $itemsData = isset($input['items']) ? $input['items'] : [];
        
        if (empty($descripcion_general) || empty($itemsData)) {
            echo json_encode(['status' => false, 'msg' => 'Falta la descripción general o no ha agregado ítems.']);
            exit();
        }
        
        $datosCabecera = [
            'cui' => $cui,
            'nro_expediente' => $nro_expediente,
            'descripcion_general' => $descripcion_general,
            'id_unidad' => null,
            'total_presupuesto' => $total_presupuesto,
            'estado' => 'Pendiente',
            'id_usuario' => $_SESSION['id']
        ];
        
        $datosItems = [];
        $nro_item = 1;
        
        foreach ($itemsData as $it) {
            if(empty($it['descripcion_item']) || empty($it['prestaciones'])) continue;

            $itemFormatted = [
                'nro_item' => $nro_item,
                'descripcion_item' => trim($it['descripcion_item']),
                'prestaciones' => []
            ];
            
            foreach ($it['prestaciones'] as $p) {
                if(empty($p['descripcion_prestacion'])) continue;

                $itemFormatted['prestaciones'][] = [
                    'descripcion' => trim($p['descripcion_prestacion']),
                    'unidad_medida' => trim($p['unidad_medida']),
                    'cantidad' => floatval($p['cantidad']),
                    'precio_unitario' => floatval($p['precio_unitario']),
                    'precio_total' => floatval($p['precio_total'])
                ];
            }
            
            if(!empty($itemFormatted['prestaciones'])) {
                $datosItems[] = $itemFormatted;
                $nro_item++;
            }
        }
        
        if(empty($datosItems)) {
            echo json_encode(['status' => false, 'msg' => 'Debe registrar al menos un ítem con sus prestaciones.']);
            exit();
        }
        
        $rspta = DemandasModelo::guardarDemanda($datosCabecera, $datosItems);
        echo json_encode($rspta);
        break;

    case 'actualizar':
      $mi_rol = strtoupper(trim($_SESSION['rol'] ?? ''));
if ($mi_rol !== 'SUPER ADMINISTRADOR' && $mi_rol !== 'SUPER ADMINISTRATOR' && !isset($_SESSION['permisos']['DEMANDAS_PRESUPUESTALES']['EDITAR'])) {

            echo json_encode(['status' => false, 'msg' => 'Acceso denegado.']);
            exit();
        }
        
        $inputJSON = file_get_contents('php://input');
        $input = json_decode($inputJSON, TRUE); 
        
        if(!$input) {
            echo json_encode(['status' => false, 'msg' => 'Datos inválidos.']);
            exit();
        }

        $id_demanda = isset($input["id_demanda"]) ? intval($input["id_demanda"]) : 0;
        $cui = isset($input["cui"]) ? trim($input["cui"]) : "";
        $nro_expediente = isset($input["nro_expediente"]) ? trim($input["nro_expediente"]) : "";
        $estado = isset($input["estado"]) ? trim($input["estado"]) : "Pendiente";
        $descripcion_general = isset($input["descripcion_general"]) ? trim($input["descripcion_general"]) : "";
        $total_presupuesto = isset($input["total_presupuesto"]) ? floatval($input["total_presupuesto"]) : 0.00;
        $itemsData = isset($input['items']) ? $input['items'] : [];
        
        if (empty($id_demanda) || empty($descripcion_general) || empty($itemsData)) {
            echo json_encode(['status' => false, 'msg' => 'Falta la descripción general o no ha agregado ítems.']);
            exit();
        }
        
        // Cabecera sin cambiar creador
        $datosCabecera = [
            'cui' => $cui,
            'nro_expediente' => $nro_expediente,
            'descripcion_general' => $descripcion_general,
            'total_presupuesto' => $total_presupuesto,
            'estado' => $estado
        ];
        
        $datosItems = [];
        $nro_item = 1;
        
        foreach ($itemsData as $it) {
            if(empty($it['descripcion_item']) || empty($it['prestaciones'])) continue;

            $itemFormatted = [
                'nro_item' => $nro_item,
                'descripcion_item' => trim($it['descripcion_item']),
                'prestaciones' => []
            ];
            
            foreach ($it['prestaciones'] as $p) {
                if(empty($p['descripcion_prestacion'])) continue;

                $itemFormatted['prestaciones'][] = [
                    'descripcion' => trim($p['descripcion_prestacion']),
                    'unidad_medida' => trim($p['unidad_medida']),
                    'cantidad' => floatval($p['cantidad']),
                    'precio_unitario' => floatval($p['precio_unitario']),
                    'precio_total' => floatval($p['precio_total'])
                ];
            }
            
            if(!empty($itemFormatted['prestaciones'])) {
                $datosItems[] = $itemFormatted;
                $nro_item++;
            }
        }
        
        if(empty($datosItems)) {
            echo json_encode(['status' => false, 'msg' => 'Debe registrar al menos un ítem con sus prestaciones.']);
            exit();
        }
        
        $rspta = DemandasModelo::actualizarDemanda($id_demanda, $datosCabecera, $datosItems);
        echo json_encode($rspta);
        break;
        
    case 'ver_detalle':
        $id = isset($_POST["id_demanda"]) ? $_POST["id_demanda"] : "";
        if(empty($id)) {
            echo json_encode(["status" => false, "msg" => "Demanda no encontrada"]);
            exit;
        }
        
        $demanda = DemandasModelo::obtenerDemanda($id);
        $items_prestaciones = DemandasModelo::obtenerItemsConPrestaciones($id);
        
        echo json_encode([
            "status" => true,
            "demanda" => $demanda,
            "items" => $items_prestaciones
        ]);
        break;
        
    case 'eliminar':
        $mi_rol = strtoupper(trim($_SESSION['rol'] ?? ''));
if ($mi_rol !== 'SUPER ADMINISTRADOR' && $mi_rol !== 'SUPER ADMINISTRATOR' && !isset($_SESSION['permisos']['DEMANDAS_PRESUPUESTALES']['ELIMINAR'])) {

            echo json_encode(['status' => false, 'msg' => 'No tiene permisos para eliminar.']);
            exit();
        }
        $id = isset($_POST["id_demanda"]) ? $_POST["id_demanda"] : "";
        if(empty($id)) {
            echo json_encode(["status" => false, "msg" => "Demanda no encontrada"]);
            exit;
        }
        $rspta = DemandasModelo::eliminarDemanda($id);
        echo json_encode($rspta);
        break;
        case 'obtener_anios':
        $anios = DemandasModelo::obtenerAnios();
        echo json_encode($anios);
        break;
}
