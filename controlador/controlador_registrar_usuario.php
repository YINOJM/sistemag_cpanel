<!--VAMOS VALIDAR EL BOTON REGISTRAR-->

<?php

if (!empty($_POST["btnregistrar"])) {
  if (!empty($_POST["txtnombre"]) and !empty($_POST["txtapellido"]) and !empty($_POST["txtusuario"]) and !empty($_POST["txtpassword"]) and !empty($_POST["txtemail"]) and !empty($_POST["txttipo"]) and !empty($_POST["txtdni"])) {
    $nombre = $_POST["txtnombre"];
    $apellido = $_POST["txtapellido"];
    $email = $_POST["txtemail"];
    $usuario = $_POST["txtusuario"];
    $password = md5($_POST["txtpassword"]);
    $tipo = $_POST["txttipo"];
    $dni = $_POST["txtdni"];
    $rol = $_POST["txtrol"] ?? 'Usuario';
    $idGrado = $_POST["txtgrado"];
    $idOficina = null; // Redundante, se usa id_subunidad ahora
    $idSubunidad = $_POST["txtsubunidad"] ?? null;

    // Obtener nombre del grado para compatibilidad
    $stmtGrado = $conexion->prepare("SELECT nombre_grado FROM mae_grados WHERE id_grado = ?");
    $stmtGrado->bind_param("i", $idGrado);
    $stmtGrado->execute();
    $resGrado = $stmtGrado->get_result();
    $nombreGrado = ($resGrado->num_rows > 0) ? $resGrado->fetch_object()->nombre_grado : '';

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
      $sql = $conexion->query(" select count(*) as 'total'  from usuario where usuario='$usuario' or correo='$email' ");

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

        <!-- Y PROCEDEMOS A REGISTRAR -->
            <?php } else {
        // Registrar con Grado, Oficina y Subunidad + Auditoría (id_creador)
        $idCreador = $_SESSION['id'] ?? 1;
        $stmt = $conexion->prepare("INSERT INTO usuario(nombre, apellido, tipo_documento, dni, correo, usuario, password, rol, id_grado, grado, id_oficina, id_subunidad, id_creador) 
                                VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssssssisiii", $nombre, $apellido, $tipo, $dni, $email, $usuario, $password, $rol, $idGrado, $nombreGrado, $idOficina, $idSubunidad, $idCreador);

        if ($stmt->execute()) {
          // Obtener el ID del usuario recién creado
          $nuevoIdUsuario = $conexion->insert_id;

          // ASIGNAR PERMISOS POR DEFECTO para usuarios normales (no Administradores)
          if ($rol !== 'Administrador' && $rol !== 'Super Administrador') {
            // 1. NUEVO SISTEMA RBAC: Asignar Plantilla "Usuario Estándar" (ID 1)
            require_once __DIR__ . '/../modelo/PermisosModelo.php';
            $permisosModelo = new PermisosModelo();
            // El ID 1 corresponde a la plantilla 'Usuario Estándar' con es_default = 1
            $permisosModelo->asignarPlantilla($nuevoIdUsuario, 1, $_SESSION['id'] ?? 1, 'Asignación automática al registrar');

            // --- AUDITORÍA (Protección cPanel) ---
            $checkLog = $conexion->query("SHOW TABLES LIKE 'log_actividad'");
            if ($checkLog && $checkLog->num_rows > 0) {
                $idEjecutorLog = $_SESSION['id'] ?? 1;
                $accionAdd = "REGISTRAR";
                $detalleAdd = "Nuevo usuario registrado (ID: $nuevoIdUsuario)";
                $stmtLogAdd = $conexion->prepare("INSERT INTO log_actividad (id_ejecutor, id_afectado, accion, detalle) VALUES (?, ?, ?, ?)");
                $stmtLogAdd->bind_param("iiss", $idEjecutorLog, $nuevoIdUsuario, $accionAdd, $detalleAdd);
                $stmtLogAdd->execute();
            }
            // -------------------------------------

            // 2. SISTEMA VIEJO: Mantener compatibilidad por ahora
            require_once __DIR__ . '/../config/permisos_defecto.php';
            if (isset($PERMISOS_POR_DEFECTO) && is_array($PERMISOS_POR_DEFECTO)) {
                $stmtPermiso = $conexion->prepare("INSERT INTO permisos_usuarios (id_usuario, modulo, accion, permitido) VALUES (?, ?, ?, 1)");
                foreach ($PERMISOS_POR_DEFECTO as $permiso) {
                    $stmtPermiso->bind_param("iss", $nuevoIdUsuario, $permiso[0], $permiso[1]);
                    $stmtPermiso->execute();
                }
            }
          }
          ?>
                    <script>
            document.addEventListener("DOMContentLoaded", function() {
                Swal.fire({
                    icon: "success",
                    title: "¡Registrado!",
                    text: "El usuario se ha registrado correctamente con permisos básicos.",
                    timer: 2000,
                    showConfirmButton: false
                });
            });   
          </script>

                <?php } else { ?>

          <script>
            document.addEventListener("DOMContentLoaded", function() {
                Swal.fire({
                    icon: "error",
                    title: "Error al Registrar",
                    text: "Hubo un error al intentar registrar al usuario.",
                    confirmButtonColor: "#3085d6"
                });
            });   
          </script>

                <?php }
      }
    }

  } else { ?>
    <!--PARA QUE NOS MUESTRE QUE LOS CAMPOS ESTAN VACÍOS-->
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

  <!-- CÓDIGO PARA QUE NO SE DUPLIQUE EL REGISTRO-->
  <SCRipt>
    setTimeout(() => {
      window.history.replaceState(null, null, window.location.pathname);

    }, 0);
  </SCRipt>

<?php }


