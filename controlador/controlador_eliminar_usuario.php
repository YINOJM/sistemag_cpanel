<?php

if (!empty($_GET["id"])) {
    $id = $_GET["id"];

    // VERIFICACIÓN DE PERMISOS
    require_once dirname(__DIR__) . '/modelo/PermisosModelo.php';
    $permisosModelo = new PermisosModelo();

    // Verificar si el usuario que intenta eliminar tiene permiso (o es Super Admin)
    // Nota: controlamos $_SESSION['id'] que es quien ejecuta la acción
    $tienePermiso = $permisosModelo->tienePermiso($_SESSION['id'], 'USUARIOS', 'ELIMINAR');
    $esSuperAdmin = (isset($_SESSION['rol']) && $_SESSION['rol'] === 'Super Administrador');

    if (!$esSuperAdmin && !$tienePermiso) { ?>
        <script>
            document.addEventListener("DOMContentLoaded", function() {
                Swal.fire({
                    icon: "error",
                    title: "Acceso Denegado",
                    text: "No tienes permiso para eliminar usuarios.",
                    confirmButtonColor: "#3085d6"
                });
            });   
        </script>
        <script>
            setTimeout(() => {
                window.history.replaceState(null, null, window.location.pathname);
            }, 0);
        </script>
    <?php
        // Detener ejecución si no hay permiso
    } else {

        // Verificar si el usuario A ELIMINAR es Super Administrador
        $check = $conexion->query("SELECT rol FROM usuario WHERE id_usuario=$id");
        $usuarioEliminar = ($check && $check->num_rows > 0) ? $check->fetch_object() : null;

        if ($usuarioEliminar && $usuarioEliminar->rol === 'Super Administrador') { ?>
            <script>
                document.addEventListener("DOMContentLoaded", function() {
                    Swal.fire({
                        icon: "error",
                        title: "Acción Inválida",
                        text: "No se puede eliminar al Super Administrador.",
                        confirmButtonColor: "#3085d6"
                    });
                });   
            </script>
            <script>
                setTimeout(() => {
                    window.history.replaceState(null, null, window.location.pathname);
                }, 0);
            </script>
        <?php } else {

            // --- AUDITORIA (Protegida contra tablas inexistentes) ---
            $idEjecutor = $_SESSION['id'] ?? 0;
            $accion = "ELIMINAR";
            $detalle = "Usuario eliminado definitivamente (ID: $id)";
            $validaLog = $conexion->query("SHOW TABLES LIKE 'log_actividad'");
            if ($validaLog->num_rows > 0) {
                $stmtLog = $conexion->prepare("INSERT INTO log_actividad (id_ejecutor, id_afectado, accion, detalle) VALUES (?, ?, ?, ?)");
                $stmtLog->bind_param("iiss", $idEjecutor, $id, $accion, $detalle);
                $stmtLog->execute();
            }
            // -----------------

            $sql = $conexion->query("delete from usuario where id_usuario=$id ");
            if ($sql == true) { ?>
                <script>
                    document.addEventListener("DOMContentLoaded", function() {
                        Swal.fire({
                            icon: "success",
                            title: "¡Eliminado!",
                            text: "Usuario eliminado correctamente.",
                            timer: 1500,
                            showConfirmButton: false
                        }).then(() => {
                            window.location.href = "usuario.php";
                        });
                    });   
                </script>

            <?php } else { ?>

                <script>
                    document.addEventListener("DOMContentLoaded", function() {
                        Swal.fire({
                            icon: "error",
                            title: "Error al Eliminar",
                            text: "Hubo un problema al intentar eliminar este usuario.",
                            confirmButtonColor: "#3085d6"
                        });
                    });   
                </script>

            <?php } ?>

            <!-- CÓDIGO PARA QUE NO SE SIGA ELIMINANDO-->
            <SCRipt>
                setTimeout(() => {
                    window.history.replaceState(null, null, window.location.pathname);

                }, 0);
            </SCRipt>



        <?php }
    }
}
?>