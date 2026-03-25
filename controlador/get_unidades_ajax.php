<?php
/**
 * AJAX Controller to fetch hierarchical police units
 */
require_once "../modelo/conexion.php";
require_once "../modelo/UnidadesPoliciales.php";

header('Content-Type: application/json');

if (!isset($_GET['action'])) {
    echo json_encode(['error' => 'No action specified']);
    exit;
}

$modelo = new UnidadesPoliciales($conexion);
$action = $_GET['action'];

try {
    switch ($action) {
        case 'get_regiones':
            echo json_encode($modelo->obtenerRegiones());
            break;

        case 'get_divisiones':
            $id_region = isset($_GET['id_region']) ? (int)$_GET['id_region'] : null;
            if (!$id_region) {
                echo json_encode([]);
            } else {
                echo json_encode($modelo->obtenerDivisiones($id_region));
            }
            break;

        case 'get_subunidades':
            $id_division = isset($_GET['id_division']) ? (int)$_GET['id_division'] : null;
            if (!$id_division) {
                echo json_encode([]);
            } else {
                echo json_encode($modelo->obtenerSubUnidades($id_division));
            }
            break;

        default:
            echo json_encode(['error' => 'Invalid action']);
            break;
    }
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
