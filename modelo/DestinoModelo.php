<?php
require_once 'conexion.php';

class DestinoModelo
{
    private $db;

    public function __construct()
    {
        global $conexion;
        $this->db = $conexion;
    }

    public function listar($id_region = null, $id_division = null)
    {
        $where = [];
        if ($id_region) {
            $where[] = "id_region = " . intval($id_region);
        }
        if ($id_division) {
            $where[] = "id_division = " . intval($id_division);
        }

        $whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";
        
        // Usar la vista para obtener nombres de región y división si es necesario
        $sql = "SELECT * FROM vista_destinos_completa $whereClause ORDER BY orden ASC, nombre_destino ASC";
        $result = $this->db->query($sql);
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function obtener($id)
    {
        $stmt = $this->db->prepare("SELECT * FROM vista_destinos_completa WHERE id_destino = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function registrar($nombre, $orden = 999, $id_region = null, $id_division = null, $id_subunidad = null)
    {
        // Verificar si ya existe en la misma división (opcional, pero ayuda a evitar duplicados)
        $sqlCheck = "SELECT id_destino FROM mae_destinos WHERE nombre_destino = ?";
        $params = ["s", $nombre];
        if ($id_division) {
            $sqlCheck .= " AND id_division = ?";
            $params[0] .= "i";
            $params[] = $id_division;
        }
        
        $check = $this->db->prepare($sqlCheck);
        $check->bind_param(...$params);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            return ['status' => false, 'msg' => 'El destino ya existe en esta jurisdicción.'];
        }

        $stmt = $this->db->prepare("INSERT INTO mae_destinos (nombre_destino, activo, orden, id_region, id_division, id_subunidad) VALUES (?, 1, ?, ?, ?, ?)");
        $stmt->bind_param("siiiii", $nombre, $orden, $id_region, $id_division, $id_subunidad);

        if ($stmt->execute()) {
            return ['status' => true, 'msg' => 'Destino registrado correctamente.'];
        } else {
            return ['status' => false, 'msg' => 'Error al registrar destino.'];
        }
    }

    public function actualizar($id, $nombre, $activo, $orden = 999, $id_region = null, $id_division = null, $id_subunidad = null)
    {
        // Verificar duplicados (excluyendo el actual)
        $sqlCheck = "SELECT id_destino FROM mae_destinos WHERE nombre_destino = ? AND id_destino != ?";
        $params = ["si", $nombre, $id];
        if ($id_division) {
            $sqlCheck .= " AND id_division = ?";
            $params[0] .= "i";
            $params[] = $id_division;
        }

        $check = $this->db->prepare($sqlCheck);
        $check->bind_param(...$params);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            return ['status' => false, 'msg' => 'El nombre del destino ya existe en esta jurisdicción.'];
        }

        $stmt = $this->db->prepare("UPDATE mae_destinos SET nombre_destino = ?, activo = ?, orden = ?, id_region = ?, id_division = ?, id_subunidad = ? WHERE id_destino = ?");
        $stmt->bind_param("siiiiii", $nombre, $activo, $orden, $id_region, $id_division, $id_subunidad, $id);

        if ($stmt->execute()) {
            return ['status' => true, 'msg' => 'Destino actualizado correctamente.'];
        } else {
            return ['status' => false, 'msg' => 'Error al actualizar destino.'];
        }
    }

    public function eliminar($id)
    {
        // Verificar si está en uso en documentos (foreign key check usually handles this but good to catch)
        $check = $this->db->prepare("SELECT id_documento FROM documentos WHERE id_destino = ? LIMIT 1");
        $check->bind_param("i", $id);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            return ['status' => false, 'msg' => 'No se puede eliminar: El destino está asociado a documentos existentes.'];
        }

        $stmt = $this->db->prepare("DELETE FROM mae_destinos WHERE id_destino = ?");
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            return ['status' => true, 'msg' => 'Destino eliminado correctamente.'];
        } else {
            return ['status' => false, 'msg' => 'Error al eliminar destino.'];
        }
    }
}
