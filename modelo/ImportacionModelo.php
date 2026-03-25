<?php
// modelo/ImportacionModelo.php
require_once 'conexion.php';

class ImportacionModelo
{
    private $conexion;

    public function __construct()
    {
        global $conexion;
        $this->conexion = $conexion;
    }

    /**
     * Obtiene todos los destinos para validación
     */
    public function obtenerTodosDestinos()
    {
        $sql = "SELECT id_destino, nombre_destino FROM mae_destinos ORDER BY nombre_destino";
        $result = $this->conexion->query($sql);
        $destinos = [];
        while ($row = $result->fetch_assoc()) {
            $destinos[] = $row;
        }
        return $destinos;
    }

    /**
     * Busca un destino por nombre (flexible, insensible a mayúsculas)
     */
    public function buscarDestinoPorNombre($nombre)
    {
        $sql = "SELECT id_destino FROM mae_destinos WHERE UPPER(nombre_destino) = UPPER(?) LIMIT 1";
        $stmt = $this->conexion->prepare($sql);
        $nombre = trim($nombre);
        $stmt->bind_param('s', $nombre);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        return $row ? $row['id_destino'] : null;
    }

    /**
     * Verifica si un número de documento ya existe
     */
    public function existeNumeroDocumento($anio, $tipo, $numero)
    {
        $sql = "SELECT COUNT(*) as total FROM documentos 
                WHERE anio = ? AND cod_tipo = ? AND num_correlativo = ?";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bind_param('isi', $anio, $tipo, $numero);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        return $row['total'] > 0;
    }

    /**
     * Inserta un documento
     */
    public function insertarDocumento($datos)
    {
        $sql = "INSERT INTO documentos (
                    anio, cod_tipo, num_correlativo, num_sufijo, num_completo,
                    asunto, id_destino, usuario_formulador, created_at,
                    estado, prioridad, demora, ht, se_solicita, observaciones
                ) VALUES (
                    ?, ?, ?, ?, ?,
                    ?, ?, ?, ?,
                    ?, ?, ?, ?, ?, ?
                )";

        $stmt = $this->conexion->prepare($sql);

        $estado = $datos['estado'] ?? 'PENDIENTE';
        $prioridad = $datos['prioridad'] ?? 'Normal';
        $demora = $datos['demora'] ?? 0;
        $se_solicita = $datos['se_solicita'] ?? '';

        $stmt->bind_param(
            'isisssissssisss',
            $datos['anio'],
            $datos['cod_tipo'],
            $datos['num_correlativo'],
            $datos['num_sufijo'],
            $datos['num_completo'],
            $datos['asunto'],
            $datos['id_destino'],
            $datos['usuario_formulador'],
            $datos['created_at'],
            $estado,
            $prioridad,
            $demora,
            $datos['ht'],
            $se_solicita,
            $datos['observaciones']
        );

        return $stmt->execute();
    }

    /**
     * Inicia una transacción
     */
    public function iniciarTransaccion()
    {
        return $this->conexion->begin_transaction();
    }

    /**
     * Confirma una transacción
     */
    public function confirmarTransaccion()
    {
        return $this->conexion->commit();
    }

    /**
     * Revierte una transacción
     */
    public function revertirTransaccion()
    {
        return $this->conexion->rollback();
    }
    /**
     * Elimina documentos por año y tipo (para re-importación limpia)
     */
    public function eliminarImportacionAnio($anio, $tipo)
    {
        // Solo eliminar si el estado es PENDIENTE o NORMAL (evitar eliminar documentos ya procesados si fuera el caso, 
        // pero el usuario pidió limpiar todo. Por seguridad borramos todo lo de ese tipo/año importado).
        
        $sql = "DELETE FROM documentos WHERE anio = ? AND cod_tipo = ?";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bind_param('is', $anio, $tipo);
        return $stmt->execute();
    }
}
