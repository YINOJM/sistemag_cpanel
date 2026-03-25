<?php
require_once 'conexion.php';

class GradoModelo
{
    private $db;

    public function __construct()
    {
        global $conexion;
        $this->db = $conexion;
    }

    public function listar()
    {
        $sql = "SELECT * FROM mae_grados ORDER BY nombre_grado ASC";
        $result = $this->db->query($sql);
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function obtener($id)
    {
        $stmt = $this->db->prepare("SELECT * FROM mae_grados WHERE id_grado = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function registrar($nombre)
    {
        // Verificar si ya existe
        $check = $this->db->prepare("SELECT id_grado FROM mae_grados WHERE nombre_grado = ?");
        $check->bind_param("s", $nombre);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            return ['status' => false, 'msg' => 'El grado ya existe.'];
        }

        $stmt = $this->db->prepare("INSERT INTO mae_grados (nombre_grado, activo) VALUES (?, 1)");
        $stmt->bind_param("s", $nombre);

        if ($stmt->execute()) {
            return ['status' => true, 'msg' => 'Grado registrado correctamente.'];
        } else {
            return ['status' => false, 'msg' => 'Error al registrar grado.'];
        }
    }

    public function actualizar($id, $nombre, $activo)
    {
        // Verificar duplicados (excluyendo el actual)
        $check = $this->db->prepare("SELECT id_grado FROM mae_grados WHERE nombre_grado = ? AND id_grado != ?");
        $check->bind_param("si", $nombre, $id);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            return ['status' => false, 'msg' => 'El nombre del grado ya existe.'];
        }

        $stmt = $this->db->prepare("UPDATE mae_grados SET nombre_grado = ?, activo = ? WHERE id_grado = ?");
        $stmt->bind_param("sii", $nombre, $activo, $id);

        if ($stmt->execute()) {
            return ['status' => true, 'msg' => 'Grado actualizado correctamente.'];
        } else {
            return ['status' => false, 'msg' => 'Error al actualizar grado.'];
        }
    }

    public function eliminar($id)
    {
        // Verificar si está en uso en usuarios
        $check = $this->db->prepare("SELECT id_usuario FROM usuario WHERE id_grado = ? LIMIT 1");
        $check->bind_param("i", $id);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            return ['status' => false, 'msg' => 'No se puede eliminar: El grado está asignado a usuarios existentes.'];
        }

        $stmt = $this->db->prepare("DELETE FROM mae_grados WHERE id_grado = ?");
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            return ['status' => true, 'msg' => 'Grado eliminado correctamente.'];
        } else {
            return ['status' => false, 'msg' => 'Error al eliminar grado.'];
        }
    }
}
?>
