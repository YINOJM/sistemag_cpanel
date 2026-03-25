<?php
// controlador/login.php

if (!empty($_POST["btningresar"])) {
    // Usamos __DIR__ para que la ruta sea absoluta y no falle en cPanel
    require_once __DIR__ . "/../modelo/conexion.php";

    $usuario = trim($_POST["usuario"] ?? "");
    $password = trim($_POST["password"] ?? "");

    if ($usuario === "" || $password === "") {
        $_SESSION['alert_login'] = "Debe completar todos los campos.";
        $redir = (defined('BASE_URL') ? BASE_URL : '/') . "vista/login/login.php";
        header("Location: $redir");
        exit();
    }

    // Consulta optimizada (Case-insensitive por defecto en MySQL para mayor usabilidad)
    $stmt = $conexion->prepare("SELECT * FROM usuario WHERE usuario = ? LIMIT 1");
    $stmt->bind_param("s", $usuario);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado->num_rows === 0) {
        $_SESSION['alert_login'] = "El usuario no existe.";
        $redir = (defined('BASE_URL') ? BASE_URL : '/') . "vista/login/login.php";
        header("Location: $redir");
        exit();
    }

    $datos = $resultado->fetch_object();
    $hash = $datos->password;

    $verificado = password_verify($password, $hash) || md5($password) === $hash || $password === $hash;

    if (!$verificado) {
        $_SESSION['alert_login'] = "Usuario o contraseña incorrecta.";
        $redir = (defined('BASE_URL') ? BASE_URL : '/') . "vista/login/login.php";
        header("Location: $redir");
        exit();
    }

    // VERIFICAR ESTADO (Solo 'Activo' puede entrar)
    if (!isset($datos->estado) || $datos->estado !== 'Activo') {
        $_SESSION['alert_login'] = "Su cuenta no está activa. Contacte al administrador para su aprobación.";
        $redir = (defined('BASE_URL') ? BASE_URL : '/') . "vista/login/login.php";
        header("Location: $redir");
        exit();
    }

    // Login correcto
    $_SESSION["id"] = $datos->id_usuario;
    $_SESSION["nombre"] = htmlspecialchars($datos->nombre, ENT_QUOTES, 'UTF-8');
    $_SESSION["apellido"] = htmlspecialchars($datos->apellido, ENT_QUOTES, 'UTF-8');
    $_SESSION["rol"] = $datos->rol;

    // DATOS DE OFICINA - Cargar nombre de oficina si existe
    $_SESSION["id_oficina"] = $datos->id_oficina;
    $_SESSION["nombre_oficina"] = null;

    if (!empty($datos->id_oficina)) {
        $stmtOficina = $conexion->prepare("SELECT nombre_destino FROM mae_destinos WHERE id_destino = ?");
        $stmtOficina->bind_param("i", $datos->id_oficina);
        $stmtOficina->execute();
        $resOficina = $stmtOficina->get_result();
        if ($rowOficina = $resOficina->fetch_object()) {
            $_SESSION["nombre_oficina"] = htmlspecialchars($rowOficina->nombre_destino, ENT_QUOTES, 'UTF-8');
        }
    }

    // DATOS DE UNIDAD POLICIAL (JERARQUÍA UBIGEO)
    $_SESSION["id_subunidad"] = $datos->id_subunidad ?? null;
    $_SESSION["nombre_region"] = '';
    $_SESSION["nombre_division"] = '';
    $_SESSION["nombre_subunidad"] = '';

    if (!empty($datos->id_subunidad)) {
        $stmtUnidad = $conexion->prepare("
            SELECT s.nombre_subunidad, d.nombre_division, r.nombre_region 
            FROM sub_unidades_policiales s
            INNER JOIN divisiones_policiales d ON s.id_division = d.id_division
            INNER JOIN regiones_policiales r ON d.id_region = r.id_region
            WHERE s.id_subunidad = ?
        ");
        $stmtUnidad->bind_param("i", $datos->id_subunidad);
        $stmtUnidad->execute();
        $resUnidad = $stmtUnidad->get_result();
        if ($rowUnidad = $resUnidad->fetch_object()) {
            $_SESSION["nombre_subunidad"] = htmlspecialchars($rowUnidad->nombre_subunidad, ENT_QUOTES, 'UTF-8');
            $_SESSION["nombre_division"] = htmlspecialchars($rowUnidad->nombre_division, ENT_QUOTES, 'UTF-8');
            $_SESSION["nombre_region"] = htmlspecialchars($rowUnidad->nombre_region, ENT_QUOTES, 'UTF-8');
        }
    }

    // DATOS DE GRADO
    $_SESSION["grado"] = $datos->grado ? htmlspecialchars($datos->grado, ENT_QUOTES, 'UTF-8') : '';
    $_SESSION["dni"] = $datos->dni ? htmlspecialchars($datos->dni, ENT_QUOTES, 'UTF-8') : '';

    $_SESSION["login_at"] = time();
    $_SESSION["last_activity"] = time(); // Inicializar contador de inactividad

    // CARGAR PERMISOS EN SESIÓN
    $_SESSION['permisos'] = [];

    require_once __DIR__ . "/../modelo/PermisosModelo.php";
    $permisosModelo = new PermisosModelo();

    if ($datos->rol === 'Super Administrador') {
        // Cargar TODOS los permisos para Super Admin dinámicamente
        $resAll = $conexion->query("
            SELECT m.slug as modulo, a.slug as accion 
            FROM modulos m 
            CROSS JOIN acciones a 
            WHERE m.activo = 1
        ");
        
        $permisosTemp = [];
        while ($row = $resAll->fetch_assoc()) {
            $permisosTemp[strtoupper($row['modulo'])][strtoupper($row['accion'])] = true;
        }
        $_SESSION['permisos'] = $permisosTemp;
        
    } else {
        // Cargar permisos desde BD usando el Modelo (que maneja plantillas y overrides)
        $permisosTemp = $permisosModelo->obtenerPermisosUsuario($datos->id_usuario);
        
        // El modelo ya devuelve un array estructurado [modulo][accion] = true
        $permisosFinal = [];
        if (is_array($permisosTemp)) {
            foreach ($permisosTemp as $mod => $acciones) {
                if (is_array($acciones)) {
                    foreach ($acciones as $acc => $val) {
                        $permisosFinal[strtoupper($mod)][strtoupper($acc)] = $val;
                    }
                }
            }
        }
        $_SESSION['permisos'] = $permisosFinal;
    }

    // Marcar como primera visita para mostrar bienvenida
    $_SESSION['primera_visita'] = true;

    header("Location: " . (defined('BASE_URL') ? BASE_URL : '/') . "vista/inicio.php");
    exit();
}
?>