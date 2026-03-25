<?php
// modelo/ModuloModelo.php

require_once __DIR__ . '/conexion.php';

class ModuloModelo {
    private $conexion;

    public function __construct() {
        global $conexion;
        $this->conexion = $conexion;
    }

    public function listar() {
        $sql = "SELECT * FROM modulos ORDER BY orden ASC";
        $res = $this->conexion->query($sql);
        $data = [];
        while($row = $res->fetch_assoc()) {
            $data[] = $row;
        }
        return $data;
    }

    public function guardar($datos) {
        $id = $datos['id'] ?? null;
        $nombre = $datos['nombre'];
        $slug = $datos['slug'];
        $icono = $datos['icono'];
        $enlace = $datos['enlace'];
        $categoria = $datos['categoria'];
        $es_restringido = $datos['es_restringido'] ? 1 : 0;
        $orden = (int)$datos['orden'];
        $activo = $datos['activo'] ? 1 : 0;

        if ($id) {
            $sql = "UPDATE modulos SET nombre=?, slug=?, icono=?, enlace=?, categoria=?, es_restringido=?, orden=?, activo=?, updated_at=NOW() WHERE id=?";
            $stmt = $this->conexion->prepare($sql);
            $stmt->bind_param("ssssssiii", $nombre, $slug, $icono, $enlace, $categoria, $es_restringido, $orden, $activo, $id);
        } else {
            $sql = "INSERT INTO modulos (nombre, slug, icono, enlace, categoria, es_restringido, orden, activo, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
            $stmt = $this->conexion->prepare($sql);
            $stmt->bind_param("ssssssii", $nombre, $slug, $icono, $enlace, $categoria, $es_restringido, $orden, $activo);
        }

        return $stmt->execute();
    }

    public function eliminar($id) {
        $sql = "DELETE FROM modulos WHERE id = ?";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }

    public function toggleEstado($id) {
        $sql = "UPDATE modulos SET activo = !activo WHERE id = ?";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }
}
