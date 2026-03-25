<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (isset($_POST['btnmodificar'])) {
    require_once("../modelo/conexion.php");

    $id = intval($_POST['txtid'] ?? 0);
    $nombre = trim($_POST['txtnombre'] ?? '');
    $telefono = trim($_POST['txttelefono'] ?? '');
    $ubicacion = trim($_POST['txtubicacion'] ?? '');
    $ruc = trim($_POST['txtruc'] ?? '');

    if ($id > 0 && $nombre !== '') {
        $sql = $conexion->prepare("UPDATE empresa SET nombre=?, telefono=?, ubicacion=?, ruc=? WHERE id_empresa=?");
        $sql->bind_param("ssssi", $nombre, $telefono, $ubicacion, $ruc, $id);

        if ($sql->execute()) {
            header("Location: ../vista/acerca.php?ok=1");
            exit();
        } else {
            header("Location: ../vista/acerca.php?error=1&msg=" . urlencode($sql->error));
            exit();
        }
    } else {
        header("Location: ../vista/acerca.php?vacio=1");
        exit();
    }
}
?>
