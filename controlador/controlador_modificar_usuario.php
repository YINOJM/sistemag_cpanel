<?php

if (!empty($_POST["btnmodificar"])) {
    if (!empty($_POST["txtnombre"]) and !empty($_POST["txtapellido"]) and !empty($_POST["txtusuario"]) and !empty($_POST["txtemail"]) and !empty($_POST["txttipo"]) and !empty($_POST["txtdni"])) {
        $nombre = $_POST["txtnombre"];
        $apellido = $_POST["txtapellido"];
        $usuario = $_POST["txtusuario"];
        $email = $_POST["txtemail"];
        $tipo = $_POST["txttipo"];
        $dni = $_POST["txtdni"];
        $id = $_POST["txtid"];
        /*VALIDAR FORMATO DE CORREO*/
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { ?>
            <script>
                document.addEventListener("DOMContentLoaded", function() {
                    Swal.fire({
                        icon: "error",
                        title: "Formato Inválido",
                        text: "El formato del correo es inválido.",
                        confirmButtonColor: "#3085d6"
                    });
                });
            </script>
        <?php } else {
            /*PARA VALIDAR QUE NO SE DUPLIQUE EL NOMBRE DE USUARIO O CORREO*/
            $sql = $conexion->query(" select count(*) as 'total'  from usuario where (usuario='$usuario' or correo='$email') and id_usuario!=$id");
        if ($sql->fetch_object()->total > 0) { ?>

            <script>
                document.addEventListener("DOMContentLoaded", function() {
                    Swal.fire({
                        icon: "error",
                        title: "Duplicado",
                        text: "El usuario o correo ya existe en el sistema.",
                        confirmButtonColor: "#3085d6"
                    });
                });
            </script>

        <?php } else {
            $rol = $_POST["txtrol"] ?? null;
            $idGrado = $_POST["txtgrado"];
            $idOficina = null; // Redundante
            $idSubunidad = $_POST["txtsubunidad"] ?? null;
            
            // Obtener nombre del grado
            $nombreGrado = '';
            if(!empty($idGrado)){
                $resG = $conexion->query("SELECT nombre_grado FROM mae_grados WHERE id_grado = $idGrado");
                if($resG && $rowG = $resG->fetch_object()){
                    $nombreGrado = $rowG->nombre_grado;
                }
            }

            $update_sql = "update usuario set nombre='$nombre', apellido='$apellido', usuario='$usuario', correo='$email', tipo_documento='$tipo', dni='$dni', id_grado='$idGrado', grado='$nombreGrado', id_oficina=" . ($idOficina ? "'$idOficina'" : "NULL") . ", id_subunidad=" . ($idSubunidad ? "'$idSubunidad'" : "NULL");
            
            // Si se envió una nueva contraseña, actualizarla
            if (!empty($_POST["txtpassword"])) {
                $newPass = md5($_POST["txtpassword"]);
                $update_sql .= ", password='$newPass'";
            }

            // Solo actualizar rol si se envió y no está vacío
            if (!empty($rol)) {
                $update_sql .= ", rol='$rol'";
            }
            
            $update_sql .= " where id_usuario=$id";
            
            $modificar = $conexion->query($update_sql);
        if ($modificar == true) { 
            // --- AUDITORIA (Protección cPanel) ---
            $checkLog = $conexion->query("SHOW TABLES LIKE 'log_actividad'");
            if ($checkLog && $checkLog->num_rows > 0) {
                $idEjecutor = $_SESSION['id'] ?? 0;
                $accion = "MODIFICAR";
                $detalle = "Datos del usuario actualizados (ID: $id)";
                $stmtLog = $conexion->prepare("INSERT INTO log_actividad (id_ejecutor, id_afectado, accion, detalle) VALUES (?, ?, ?, ?)");
                $stmtLog->bind_param("iiss", $idEjecutor, $id, $accion, $detalle);
                $stmtLog->execute();
            }
            // -----------------
        ?>

            <script>
                document.addEventListener("DOMContentLoaded", function() {
                    Swal.fire({
                        icon: "success",
                        title: "¡Actualizado!",
                        text: "El usuario se ha modificado correctamente.",
                        timer: 2000,
                        showConfirmButton: false
                    });
                });   
            </script>
            <?php
            // SI EL USUARIO MODIFICADO ES EL MISMO QUE ESTÁ LOGUEADO, ACTUALIZAR SESIÓN
             if (isset($_SESSION['id']) && $_SESSION['id'] == $id) {
                $_SESSION['nombre'] = $nombre;
                $_SESSION['apellido'] = $apellido;
                $_SESSION['dni'] = $dni;
                // Actualizar Grado
                $_SESSION['grado'] = $nombreGrado;
                
                // Actualizar Datos de Unidad (Subunidad)
                $_SESSION['id_subunidad'] = $idSubunidad;
                if ($idSubunidad) {
                    $sqlUnit = $conexion->query("
                        SELECT s.nombre_subunidad, d.nombre_division, r.nombre_region 
                        FROM sub_unidades_policiales s
                        INNER JOIN divisiones_policiales d ON s.id_division = d.id_division
                        INNER JOIN regiones_policiales r ON d.id_region = r.id_region
                        WHERE s.id_subunidad = $idSubunidad
                    ");
                    if ($rowU = $sqlUnit->fetch_object()) {
                        $_SESSION['nombre_subunidad'] = $rowU->nombre_subunidad;
                        $_SESSION['nombre_division'] = $rowU->nombre_division;
                        $_SESSION['nombre_region'] = $rowU->nombre_region;
                        // Forzar que 'nombre_oficina' se limpie si ahora usamos subunidad
                        $_SESSION['nombre_oficina'] = null; 
                    }
                } else {
                    $_SESSION['nombre_subunidad'] = '';
                    $_SESSION['nombre_division'] = '';
                    $_SESSION['nombre_region'] = '';
                }
             }
            ?>
          
        <?php } else { ?>
            <script>
                document.addEventListener("DOMContentLoaded", function() {
                    Swal.fire({
                        icon: "error",
                        title: "Error",
                        text: "Hubo un error al intentar modificar al usuario.",
                        confirmButtonColor: "#3085d6"
                    });
                });   
            </script>
     
        <?php } 
    }
    }
    
   } else { ?>

        <script>
            document.addEventListener("DOMContentLoaded", function() {
                Swal.fire({
                    icon: "warning",
                    title: "Campos Vacíos",
                    text: "Por favor, completa todos los campos obligatorios.",
                    confirmButtonColor: "#f39c12"
                });
            });   
        </script>
    

   <?php } ?>

   <!-- CÓDIGO PARA QUE NO SE DUPLIQUE-->
        <SCRipt>  
            setTimeout(() => {
                window.history.replaceState(null, null, window.location.pathname);

            }, 0);
            </SCRipt>
   
<?php }

?>