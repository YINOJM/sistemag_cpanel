<?php
require_once 'conexion.php';

class TipoBienModelo
{
    private $db;

    public function __construct()
    {
        global $conexion;
        $this->db = $conexion;
    }

    public function listar()
    {
        $sql = "SELECT * FROM mae_tipos_bien ORDER BY nombre ASC";
        $result = $this->db->query($sql);
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function obtener($id)
    {
        $stmt = $this->db->prepare("SELECT * FROM mae_tipos_bien WHERE id_tipo_bien = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function registrar($nombre)
    {
        $check = $this->db->prepare("SELECT id_tipo_bien FROM mae_tipos_bien WHERE nombre = ?");
        $check->bind_param("s", $nombre);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            return ['status' => false, 'msg' => 'El tipo de bien ya existe.'];
        }

        $stmt = $this->db->prepare("INSERT INTO mae_tipos_bien (nombre, estado) VALUES (?, 1)");
        $stmt->bind_param("s", $nombre);

        if ($stmt->execute()) {
            return ['status' => true, 'msg' => 'Tipo de bien registrado correctamente.', 'id' => $this->db->insert_id];
        } else {
            return ['status' => false, 'msg' => 'Error al registrar tipo de bien.'];
        }
    }

    public function actualizar($id, $nombre, $estado)
    {
        $check = $this->db->prepare("SELECT id_tipo_bien FROM mae_tipos_bien WHERE nombre = ? AND id_tipo_bien != ?");
        $check->bind_param("si", $nombre, $id);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            return ['status' => false, 'msg' => 'El nombre del tipo de bien ya existe.'];
        }

        $stmt = $this->db->prepare("UPDATE mae_tipos_bien SET nombre = ?, estado = ? WHERE id_tipo_bien = ?");
        $stmt->bind_param("sii", $nombre, $estado, $id);

        if ($stmt->execute()) {
            return ['status' => true, 'msg' => 'Tipo de bien actualizado correctamente.'];
        } else {
            return ['status' => false, 'msg' => 'Error al actualizar tipo de bien.'];
        }
    }

    public function eliminar($id)
    {
        // 1. Obtener el nombre del tipo antes de intentar borrarlo para revisar el inventario
        $stmt_nombre = $this->db->prepare("SELECT nombre FROM mae_tipos_bien WHERE id_tipo_bien = ?");
        $stmt_nombre->bind_param("i", $id);
        $stmt_nombre->execute();
        $res_nombre = $stmt_nombre->get_result()->fetch_assoc();
        
        if (!$res_nombre) {
            return ['status' => false, 'msg' => 'Tipo de bien no encontrado.'];
        }
        
        $nombre_tipo = $res_nombre['nombre'];

        // 2. Verificar si está en uso en la tabla inventario (comparando con la columna tipo_bien)
        $check = $this->db->prepare("SELECT id FROM inventario WHERE tipo_bien = ? LIMIT 1");
        $check->bind_param("s", $nombre_tipo);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            return ['status' => false, 'msg' => 'No se puede eliminar: Este tipo de bien ya está asignado a elementos en el inventario.'];
        }
        
        // 3. Proceder a eliminar
        $stmt = $this->db->prepare("DELETE FROM mae_tipos_bien WHERE id_tipo_bien = ?");
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            return ['status' => true, 'msg' => 'Tipo de bien eliminado correctamente.'];
        } else {
            return ['status' => false, 'msg' => 'Error al eliminar: ' . $this->db->error];
        }
    }
}
?>
