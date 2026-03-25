<?php
// modelo/bitacora_model.php
require_once __DIR__ . '/conexion.php';

class BitacoraModel {
    private $conn;

    public function __construct() {
        global $conn;
        $this->conn = $conn;
    }

    public function getLogs() {
        $sql = "SELECT 
                   b.id,
                   b.fecha_hora,
                   b.accion,
                   b.detalle,
                   b.ip,
                   COALESCE(u.usuario, 'Sistema/Desconocido') AS usuario
                FROM bitacora b
                LEFT JOIN usuario u ON b.usuario_id = u.id_usuario
                ORDER BY b.fecha_hora DESC
                LIMIT 500";
        return $this->conn->query($sql);
    }
}
