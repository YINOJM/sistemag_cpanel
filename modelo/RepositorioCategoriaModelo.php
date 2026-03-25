<?php
// modelo/RepositorioCategoriaModelo.php

require_once __DIR__ . '/conexion.php';

class RepositorioCategoriaModelo
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
     * Listar todas las categorías activas
     */
    public function listar()
    {
        $sql = "SELECT * FROM repositorio_categorias 
                WHERE estado = 1 
                ORDER BY orden ASC, nombre ASC";
        $result = $this->db->query($sql);
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Listar categorías con conteo de documentos
     */
    public function listarConConteo()
    {
        $sql = "SELECT c.*, 
                COUNT(d.id) as total_documentos
                FROM repositorio_categorias c
                LEFT JOIN repositorio_documentos d ON c.nombre COLLATE utf8mb4_unicode_ci = d.categoria COLLATE utf8mb4_unicode_ci AND d.estado = 1
                WHERE c.estado = 1
                GROUP BY c.id
                ORDER BY c.orden ASC, c.nombre ASC";
        $result = $this->db->query($sql);
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Obtener categoría por ID
     */
    public function obtenerPorId($id)
    {
        $sql = "SELECT * FROM repositorio_categorias WHERE id = ? AND estado = 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    /**
     * Crear nueva categoría
     */
    public function crear($datos)
    {
        // Verificar si ya existe
        $sqlCheck = "SELECT id FROM repositorio_categorias WHERE nombre = ?";
        $stmtCheck = $this->db->prepare($sqlCheck);
        $stmtCheck->bind_param("s", $datos['nombre']);
        $stmtCheck->execute();
        if ($stmtCheck->get_result()->num_rows > 0) {
            return ['status' => false, 'msg' => 'Ya existe una categoría con ese nombre'];
        }

        $sql = "INSERT INTO repositorio_categorias (nombre, descripcion, color, orden) 
                VALUES (?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param(
            "sssi",
            $datos['nombre'],
            $datos['descripcion'],
            $datos['color'],
            $datos['orden']
        );

        if ($stmt->execute()) {
            return ['status' => true, 'msg' => 'Categoría creada exitosamente'];
        } else {
            return ['status' => false, 'msg' => 'Error al crear la categoría'];
        }
    }

    /**
     * Actualizar categoría
     */
    public function actualizar($id, $datos)
    {
        // Obtener datos antiguos primero
        $categoriaAntigua = $this->obtenerPorId($id);
        if (!$categoriaAntigua) {
            return ['status' => false, 'msg' => 'Categoría no encontrada'];
        }

        // Verificar si el nuevo nombre ya existe (excepto el actual)
        $sqlCheck = "SELECT id FROM repositorio_categorias WHERE nombre = ? AND id != ?";
        $stmtCheck = $this->db->prepare($sqlCheck);
        $stmtCheck->bind_param("si", $datos['nombre'], $id);
        $stmtCheck->execute();
        if ($stmtCheck->get_result()->num_rows > 0) {
            return ['status' => false, 'msg' => 'Ya existe otra categoría con ese nombre'];
        }

        $sql = "UPDATE repositorio_categorias 
                SET nombre = ?, descripcion = ?, color = ?, orden = ?
                WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param(
            "sssii",
            $datos['nombre'],
            $datos['descripcion'],
            $datos['color'],
            $datos['orden'],
            $id
        );

        if ($stmt->execute()) {
            // Actualizar documentos que usan esta categoría
            if ($categoriaAntigua['nombre'] !== $datos['nombre']) {
                $sqlUpdate = "UPDATE repositorio_documentos SET categoria = ? WHERE categoria = ?";
                $stmtUpdate = $this->db->prepare($sqlUpdate);
                $stmtUpdate->bind_param("ss", $datos['nombre'], $categoriaAntigua['nombre']);
                $stmtUpdate->execute();
            }

            return ['status' => true, 'msg' => 'Categoría actualizada exitosamente'];
        } else {
            return ['status' => false, 'msg' => 'Error al actualizar la categoría'];
        }
    }

    /**
     * Eliminar categoría (solo si no tiene documentos)
     */
    public function eliminar($id)
    {
        // Verificar si tiene documentos
        $sqlCheck = "SELECT COUNT(*) as total FROM repositorio_documentos 
                     WHERE categoria COLLATE utf8mb4_unicode_ci = (SELECT nombre FROM repositorio_categorias WHERE id = ?) COLLATE utf8mb4_unicode_ci
                     AND estado = 1";
        $stmtCheck = $this->db->prepare($sqlCheck);
        $stmtCheck->bind_param("i", $id);
        $stmtCheck->execute();
        $result = $stmtCheck->get_result()->fetch_assoc();

        if ($result['total'] > 0) {
            return ['status' => false, 'msg' => 'No se puede eliminar. La categoría tiene ' . $result['total'] . ' documento(s) asociado(s)'];
        }

        $sql = "UPDATE repositorio_categorias SET estado = 0 WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            return ['status' => true, 'msg' => 'Categoría eliminada exitosamente'];
        } else {
            return ['status' => false, 'msg' => 'Error al eliminar la categoría'];
        }
    }
    /**
     * Cambiar orden de categoría
     */
    public function cambiarOrden($id, $direccion)
    {
        // 1. Obtener todas las categorías ordenadas actualmente
        $categorias = $this->listar();
        
        // 2. Encontrar el índice actual
        $indiceActual = -1;
        foreach ($categorias as $index => $cat) {
            if ($cat['id'] == $id) {
                $indiceActual = $index;
                break;
            }
        }

        if ($indiceActual === -1) {
            return ['status' => false, 'msg' => 'Categoría no encontrada'];
        }

        // 3. Determinar el índice de intercambio
        $indiceIntercambio = -1;
        if ($direccion === 'up') {
            $indiceIntercambio = $indiceActual - 1;
        } else if ($direccion === 'down') {
            $indiceIntercambio = $indiceActual + 1;
        }

        // Validar límites
        if ($indiceIntercambio < 0 || $indiceIntercambio >= count($categorias)) {
            return ['status' => false, 'msg' => 'No se puede mover más en esa dirección'];
        }

        // 4. Intercambiar en el array
        $temp = $categorias[$indiceActual];
        $categorias[$indiceActual] = $categorias[$indiceIntercambio];
        $categorias[$indiceIntercambio] = $temp;

        // 5. Guardar el nuevo orden en la BD
        $sql = "UPDATE repositorio_categorias SET orden = ? WHERE id = ?";
        $stmt = $this->db->prepare($sql);

        $success = true;
        foreach ($categorias as $index => $cat) {
            $nuevoOrden = $index + 1; // 1-based index
            $stmt->bind_param("ii", $nuevoOrden, $cat['id']);
            if (!$stmt->execute()) {
                $success = false;
            }
        }

        if ($success) {
            return ['status' => true, 'msg' => 'Orden actualizado'];
        } else {
            return ['status' => false, 'msg' => 'Error al guardar el nuevo orden'];
        }
    }
}
