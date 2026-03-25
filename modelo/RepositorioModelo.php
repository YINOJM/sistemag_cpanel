<?php
// modelo/RepositorioModelo.php
declare(strict_types=1);

require_once __DIR__ . '/conexion.php';

class RepositorioModelo
{
    private $db;

    public function __construct()
    {
        global $conexion;

        if (!isset($conexion) || !$conexion) {
            throw new Exception("No se pudo establecer la conexión a la base de datos");
        }

        $this->db = $conexion;
    }

    /**
     * Listar documentos con filtros opcionales
     */
    public function listar($anio = null, $categoria = null, $busqueda = null)
    {
        $sql = "SELECT r.*, u.nombre as nombre_usuario 
                FROM repositorio_documentos r
                LEFT JOIN usuario u ON r.usuario_subida = u.id_usuario
                WHERE r.estado = 1";

        $params = [];
        $types = "";

        if ($anio) {
            $sql .= " AND r.anio = ?";
            $params[] = $anio;
            $types .= "i";
        }

        if ($categoria) {
            $sql .= " AND r.categoria = ?";
            $params[] = $categoria;
            $types .= "s";
        }

        if ($busqueda) {
            $sql .= " AND (r.nombre_archivo LIKE ? OR r.descripcion LIKE ?)";
            $busquedaLike = "%{$busqueda}%";
            $params[] = $busquedaLike;
            $params[] = $busquedaLike;
            $types .= "ss";
        }

        $sql .= " ORDER BY r.fecha_subida DESC";

        if (!empty($params)) {
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $result = $stmt->get_result();
        } else {
            $result = $this->db->query($sql);
        }

        return $result->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Obtener documento por ID
     */
    public function obtenerPorId(int $id)
    {
        $sql = "SELECT r.*, u.nombre as nombre_usuario 
                FROM repositorio_documentos r
                LEFT JOIN usuario u ON r.usuario_subida = u.id_usuario
                WHERE r.id = ? AND r.estado = 1";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    /**
     * Registrar nuevo documento
     */
    public function registrar(array $datos)
    {
        $sql = "INSERT INTO repositorio_documentos (
                    nombre_archivo, nombre_sistema, ruta_archivo, anio, categoria,
                    descripcion, tipo_archivo, extension, tamano, usuario_subida
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param(
            "sssississi",
            $datos['nombre_archivo'],
            $datos['nombre_sistema'],
            $datos['ruta_archivo'],
            $datos['anio'],
            $datos['categoria'],
            $datos['descripcion'],
            $datos['tipo_archivo'],
            $datos['extension'],
            $datos['tamano'],
            $datos['usuario_subida']
        );

        if ($stmt->execute()) {
            return [
                'status' => true,
                'msg' => 'Documento subido correctamente',
                'id' => $this->db->insert_id
            ];
        } else {
            return [
                'status' => false,
                'msg' => 'Error al registrar documento: ' . $stmt->error
            ];
        }
    }

    /**
     * Actualizar información de documento
     */
    public function actualizar(int $id, array $datos)
    {
        $sql = "UPDATE repositorio_documentos SET
                    categoria = ?,
                    descripcion = ?
                WHERE id = ? AND estado = 1";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param(
            "ssi",
            $datos['categoria'],
            $datos['descripcion'],
            $id
        );

        if ($stmt->execute()) {
            return [
                'status' => true,
                'msg' => 'Documento actualizado correctamente'
            ];
        } else {
            return [
                'status' => false,
                'msg' => 'Error al actualizar: ' . $stmt->error
            ];
        }
    }

    /**
     * Eliminar documento (soft delete)
     */
    public function eliminar(int $id)
    {
        $sql = "UPDATE repositorio_documentos SET estado = 0 WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            return [
                'status' => true,
                'msg' => 'Documento eliminado correctamente'
            ];
        } else {
            return [
                'status' => false,
                'msg' => 'Error al eliminar: ' . $stmt->error
            ];
        }
    }

    /**
     * Obtener años disponibles
     */
    public function obtenerAnios()
    {
        $sql = "SELECT DISTINCT anio FROM repositorio_documentos 
                WHERE estado = 1 ORDER BY anio DESC";
        $result = $this->db->query($sql);
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Obtener categorías disponibles
     */
    public function obtenerCategorias()
    {
        $sql = "SELECT DISTINCT categoria FROM repositorio_documentos 
                WHERE estado = 1 AND categoria IS NOT NULL 
                ORDER BY categoria ASC";
        $result = $this->db->query($sql);
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Obtener estadísticas
     */
    public function obtenerEstadisticas()
    {
        $sql = "SELECT 
                    COUNT(*) as total_documentos,
                    SUM(tamano) as espacio_total,
                    COUNT(DISTINCT anio) as total_anios,
                    COUNT(DISTINCT categoria) as total_categorias
                FROM repositorio_documentos
                WHERE estado = 1";

        $result = $this->db->query($sql);
        return $result->fetch_assoc();
    }

    /**
     * Obtener documentos por año
     */
    public function obtenerPorAnio(int $anio)
    {
        $sql = "SELECT r.*, u.nombre as nombre_usuario 
                FROM repositorio_documentos r
                LEFT JOIN usuario u ON r.usuario_subida = u.id_usuario
                WHERE r.anio = ? AND r.estado = 1
                ORDER BY r.fecha_subida DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $anio);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }
}
