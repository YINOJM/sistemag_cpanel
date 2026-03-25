<?php
// modelo/CapacitacionModelo.php
require_once 'conexion.php';

class CapacitacionModelo {
    private $conn;

    public function __construct() {
        global $conn;
        $this->conn = $conn;
    }

    public function listar($estado = 'activo') {
        $sql = "SELECT * FROM capacitaciones WHERE estado = ? ORDER BY fecha_creacion DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("s", $estado);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function obtenerPorId($id) {
        $sql = "SELECT * FROM capacitaciones WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    public function registrar($datos) {
        $sql = "INSERT INTO capacitaciones (titulo, descripcion, url_video, archivo_adjunto) VALUES (?, ?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ssss", 
            $datos['titulo'], 
            $datos['descripcion'], 
            $datos['url_video'], 
            $datos['archivo_adjunto']
        );
        
        if ($stmt->execute()) {
            return ['status' => true, 'msg' => 'Capacitación registrada correctamente'];
        } else {
            return ['status' => false, 'msg' => 'Error al registrar: ' . $this->conn->error];
        }
    }

    public function actualizar($id, $datos) {
        $sql = "UPDATE capacitaciones SET titulo = ?, descripcion = ?, url_video = ?, archivo_adjunto = ? WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ssssi", 
            $datos['titulo'], 
            $datos['descripcion'], 
            $datos['url_video'], 
            $datos['archivo_adjunto'],
            $id
        );

        if ($stmt->execute()) {
            return ['status' => true, 'msg' => 'Capacitación actualizada correctamente'];
        } else {
            return ['status' => false, 'msg' => 'Error al actualizar: ' . $this->conn->error];
        }
    }

    public function eliminar($id) {
        // Soft delete (inactivar)
        //$sql = "UPDATE capacitaciones SET estado = 'inactivo' WHERE id = ?";
        // O Hard delete? El usuario pidió eliminar. Haremos Hard Delete para limpiar
        $sql = "DELETE FROM capacitaciones WHERE id = ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            return ['status' => true, 'msg' => 'Capacitación eliminada correctamente'];
        } else {
            return ['status' => false, 'msg' => 'Error al eliminar: ' . $this->conn->error];
        }
    }
}
?>
