<?php
// modelo/InventarioModelo.php
declare(strict_types=1);

require_once __DIR__ . '/conexion.php';

class InventarioModelo
{
    private $db;

    public function __construct()
    {
        global $conexion;
        $this->db = $conexion;
    }

    // 1. Listar todos los items activos (con filtro opcional por año, oficina o subunidad)
    public function listar($anio = null, $idOficina = null, $idSubunidad = null)
    {
        $sql = "SELECT i.*, d.nombre_destino as destino_nombre, s.nombre_subunidad as subunidad_nombre 
                FROM inventario i 
                LEFT JOIN mae_destinos d ON i.id_oficina = d.id_destino
                LEFT JOIN sub_unidades_policiales s ON i.id_subunidad = s.id_subunidad
                WHERE i.estado = 1";

        $params = [];
        $types = "";

        if ($anio) {
            $sql .= " AND i.anio = ?";
            $params[] = (int) $anio;
            $types .= "i";
        }

        if ($idOficina) {
            $sql .= " AND i.id_oficina = ?";
            $params[] = (int) $idOficina;
            $types .= "i";
        }

        if ($idSubunidad) {
            $sql .= " AND i.id_subunidad = ?";
            $params[] = (int) $idSubunidad;
            $types .= "i";
        }

        $sql .= " ORDER BY i.id ASC";

        if (!empty($params)) {
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $result = $stmt->get_result();
            return $result->fetch_all(MYSQLI_ASSOC);
        }

        $result = $this->db->query($sql);
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    // 2. Obtener un item por ID
    public function obtenerPorId(int $id)
    {
        $sql = "SELECT * FROM inventario WHERE id = ? AND estado = 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    // 3. Registrar nuevo item
    public function registrar(array $datos)
    {
        $sql = "INSERT INTO inventario (
                    anio, codigo_inventario, descripcion, marca, serie, modelo,
                    tipo_bien, dimensiones, situacion, otras_caracteristicas, ubicacion_fisica,
                    estado_bien, color, cantidad, observaciones, usuario_responsable, id_oficina, id_subunidad
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        // Validar id_oficina (puede ser null)
        $idOficina = isset($datos['id_oficina']) && !empty($datos['id_oficina']) ? $datos['id_oficina'] : null;
        $idSubunidad = isset($datos['id_subunidad']) && !empty($datos['id_subunidad']) ? $datos['id_subunidad'] : null;

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param(
            "isssssssssssssssii",
            $datos['anio'],
            $datos['codigo_inventario'],
            $datos['descripcion'],
            $datos['marca'],
            $datos['serie'],
            $datos['modelo'],
            $datos['tipo_bien'],
            $datos['dimensiones'],
            $datos['situacion'],
            $datos['otras_caracteristicas'],
            $datos['ubicacion_fisica'],
            $datos['estado_bien'],
            $datos['color'],
            $datos['cantidad'],
            $datos['observaciones'],
            $datos['usuario_responsable'],
            $idOficina,
            $idSubunidad
        );

        if ($stmt->execute()) {
            return [
                'status' => true,
                'msg' => 'Item registrado correctamente',
                'id' => $this->db->insert_id
            ];
        } else {
            return [
                'status' => false,
                'msg' => 'Error al registrar: ' . $stmt->error
            ];
        }
    }

    // 4. Actualizar item existente
    public function actualizar(int $id, array $datos)
    {
        $sql = "UPDATE inventario SET
                    anio = ?,
                    codigo_inventario = ?,
                    descripcion = ?,
                    marca = ?,
                    serie = ?,
                    modelo = ?,
                    tipo_bien = ?,
                    dimensiones = ?,
                    situacion = ?,
                    otras_caracteristicas = ?,
                    ubicacion_fisica = ?,
                    estado_bien = ?,
                    color = ?,
                    cantidad = ?,
                    observaciones = ?,
                    usuario_responsable = ?,
                    id_oficina = ?,
                    id_subunidad = ?
                WHERE id = ? AND estado = 1";

        // Validar id_oficina (puede ser null)
        $idOficina = isset($datos['id_oficina']) && !empty($datos['id_oficina']) ? $datos['id_oficina'] : null;
        $idSubunidad = isset($datos['id_subunidad']) && !empty($datos['id_subunidad']) ? $datos['id_subunidad'] : null;

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param(
            "isssssssssssssssiii",
            $datos['anio'],
            $datos['codigo_inventario'],
            $datos['descripcion'],
            $datos['marca'],
            $datos['serie'],
            $datos['modelo'],
            $datos['tipo_bien'],
            $datos['dimensiones'],
            $datos['situacion'],
            $datos['otras_caracteristicas'],
            $datos['ubicacion_fisica'],
            $datos['estado_bien'],
            $datos['color'],
            $datos['cantidad'],
            $datos['observaciones'],
            $datos['usuario_responsable'],
            $idOficina,
            $idSubunidad,
            $id
        );

        if ($stmt->execute()) {
            return [
                'status' => true,
                'msg' => 'Item actualizado correctamente'
            ];
        } else {
            return [
                'status' => false,
                'msg' => 'Error al actualizar: ' . $stmt->error
            ];
        }
    }

    // 5. Eliminar (Soft Delete)
    public function eliminar(int $id)
    {
        $sql = "UPDATE inventario SET estado = 0 WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            return [
                'status' => true,
                'msg' => 'Item eliminado correctamente'
            ];
        } else {
            return [
                'status' => false,
                'msg' => 'Error al eliminar: ' . $stmt->error
            ];
        }
    }

    // 5.1 Eliminar todos los items de un año (Soft Delete)
    public function eliminarAnio(int $anio)
    {
        $sql = "UPDATE inventario SET estado = 0 WHERE anio = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $anio);

        if ($stmt->execute()) {
            return [
                'status' => true,
                'msg' => "Inventario del año $anio eliminado correctamente"
            ];
        } else {
            return [
                'status' => false,
                'msg' => 'Error al eliminar inventario del año: ' . $stmt->error
            ];
        }
    }

    // 6. Obtener estadísticas
    public function obtenerEstadisticas()
    {
        $sql = "SELECT 
                    estado_bien,
                    COUNT(*) as total
                FROM inventario
                WHERE estado = 1
                GROUP BY estado_bien";

        $result = $this->db->query($sql);
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    // 7. Verificar si código ya existe
    public function existeCodigo(string $codigo, int $idExcluir = 0)
    {
        $sql = "SELECT id FROM inventario WHERE codigo_inventario = ? AND estado = 1 AND id != ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("si", $codigo, $idExcluir);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->num_rows > 0;
    }
}
