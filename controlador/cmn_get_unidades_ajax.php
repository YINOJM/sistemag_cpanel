<?php
// controlador/cmn_get_unidades_ajax.php
require_once "../modelo/conexion.php";

header('Content-Type: application/json');

$op = $_GET['op'] ?? '';

// Usamos las tablas institucionales: regiones_policiales, divisiones_policiales, sub_unidades_policiales
try {
    switch ($op) {
        case 'regiones':
            $sql = "SELECT nombre_region FROM regiones_policiales WHERE estado = 1 ORDER BY nombre_region";
            $res = $conexion->query($sql);
            $data = [];
            if ($res) {
                while ($row = $res->fetch_assoc()) {
                    $data[] = $row['nombre_region'];
                }
            }
            echo json_encode($data);
            break;

        case 'divisiones':
            $regionNome = $_GET['region'] ?? '';
            $stmt = $conexion->prepare("
                SELECT d.nombre_division 
                FROM divisiones_policiales d
                INNER JOIN regiones_policiales r ON d.id_region = r.id_region
                WHERE r.nombre_region = ? AND d.estado = 1 
                ORDER BY d.nombre_division
            ");
            $stmt->bind_param("s", $regionNome);
            $stmt->execute();
            $res = $stmt->get_result();
            $data = [];
            while ($row = $res->fetch_assoc()) {
                $data[] = $row['nombre_division'];
            }
            echo json_encode($data);
            break;

        case 'unidades':
            $divisionNome = $_GET['division'] ?? '';
            $stmt = $conexion->prepare("
                SELECT s.id_subunidad, s.nombre_subunidad 
                FROM sub_unidades_policiales s
                INNER JOIN divisiones_policiales d ON s.id_division = d.id_division
                WHERE d.nombre_division = ? AND s.estado = 1 
                ORDER BY s.nombre_subunidad
            ");
            $stmt->bind_param("s", $divisionNome);
            $stmt->execute();
            $res = $stmt->get_result();
            $data = [];
            while ($row = $res->fetch_assoc()) {
                $data[] = ["id" => $row['id_subunidad'], "nombre" => $row['nombre_subunidad']];
            }
            echo json_encode($data);
            break;

        default:
            echo json_encode(['error' => 'Operación no válida']);
            break;
    }
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
