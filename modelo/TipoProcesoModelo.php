<?php
require_once __DIR__ . '/conexion.php';

class TipoProcesoModelo
{
    private $conn;

    public function __construct()
    {
        global $conn;
        $this->conn = $conn;
    }

    public function listar()
    {
        $sql = "SELECT * FROM tipo_proceso ORDER BY nombre ASC";
        $result = $this->conn->query($sql);
        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        return $data;
    }

    public function registrar($nombre)
    {
        $stmt = $this->conn->prepare("INSERT INTO tipo_proceso (nombre, estado) VALUES (?, 1)");
        $stmt->bind_param("s", $nombre);
        return $stmt->execute();
    }

    public function actualizar($id, $nombre)
    {
        $stmt = $this->conn->prepare("UPDATE tipo_proceso SET nombre = ? WHERE id = ?");
        $stmt->bind_param("si", $nombre, $id);
        return $stmt->execute();
    }

    public function cambiarEstado($id, $estado)
    {
        $stmt = $this->conn->prepare("UPDATE tipo_proceso SET estado = ? WHERE id = ?");
        $stmt->bind_param("ii", $estado, $id);
        return $stmt->execute();
    }

    public function eliminar($id)
    {
        // Verificar si está en uso
        $check = $this->conn->prepare("SELECT COUNT(*) as total FROM segmentacion WHERE tipo_proceso_id = ?");
        $check->bind_param("i", $id);
        $check->execute();
        $res = $check->get_result()->fetch_assoc();

        if ($res['total'] > 0) {
            return false; // No eliminar si está en uso
        }

        $stmt = $this->conn->prepare("DELETE FROM tipo_proceso WHERE id = ?");
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }
}
