<?php
require_once "conexion.php";

require_once "conexion.php";

class ModeloUnidadesPoliciales
{

    static public function mdlTruncate()
    {
        global $conexion;
        return $conexion->query("TRUNCATE TABLE unidades_policiales_ref");
    }

    static public function mdlInsertar($region, $unidad_superior, $sub_unidad, $tipo_cpnp)
    {
        global $conexion;
        $stmt = $conexion->prepare("INSERT INTO unidades_policiales_ref (region, unidad_superior, sub_unidad, tipo_cpnp) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $region, $unidad_superior, $sub_unidad, $tipo_cpnp);

        if ($stmt->execute()) {
            return "ok";
        } else {
            return "error";
        }
    }

    static public function mdlBuscar($termino)
    {
        global $conexion;
        $termino = "%" . $termino . "%";
        $stmt = $conexion->prepare("
            SELECT * FROM unidades_policiales_ref 
            WHERE region LIKE ? 
            OR unidad_superior LIKE ? 
            OR sub_unidad LIKE ?
            LIMIT 20
        ");
        $stmt->bind_param("sss", $termino, $termino, $termino);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    static public function mdlObtenerTodo()
    {
        global $conexion;
        $res = $conexion->query("SELECT * FROM unidades_policiales_ref ORDER BY region, unidad_superior, sub_unidad");
        return $res->fetch_all(MYSQLI_ASSOC);
    }
}
