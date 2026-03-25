<?php
// 1. CARGAR CONEXIÓN Y ENTORNO (Centralizado.)
require_once __DIR__ . "/../modelo/conexion.php";

if (empty($_SESSION['nombre']) && empty($_SESSION['apellido'])) {
    header('Location: ' . (defined('BASE_URL') ? BASE_URL : '/sistem_job/') . 'vista/login/login.php');
    exit;
}

// Seguridad: Solo super administradores o usuarios con permiso explícito de CONFIGURACION
if (!isset($_SESSION['rol']) || ($_SESSION['rol'] !== 'Super Administrador' && !isset($_SESSION['permisos']['CONFIGURACION']['VER']))) {
    // Redirigir a inicio o mostrar error
    echo "<script>alert('Acceso Denegado: No tienes permisos para gestionar usuarios.'); window.location.href='inicio.php';</script>";
    exit();
}

// 2. PROCESAMIENTO DE ACCIONES (Antes del HTML)
include "../controlador/controlador_modificar_usuario.php";
include "../controlador/controlador_registrar_usuario.php";
include "../controlador/controlador_eliminar_usuario.php";
require_once "../modelo/PermisosModelo.php";

?>




<!-- primero se carga el topbar -->
<?php require('./layout/topbar.php'); ?>
<!-- luego se carga el sidebar -->
<?php require('./layout/sidebar.php'); ?>

<!-- inicio del contenido principal -->
<div class="page-content">

    <h4 class="text-center text-secondary">
        LISTA DE USUARIOS
    </h4>

    <?php
    // Instancia del modelo de permisos
    $permisosModelo = new PermisosModelo();
    // Verificar si tiene permiso de eliminar usuarios
    $puedeEliminar = $permisosModelo->tienePermiso($_SESSION['id'], 'USUARIOS', 'ELIMINAR');
    // Super Administrador siempre puede
    if ($_SESSION['rol'] === 'Super Administrador') {
        $puedeEliminar = true;
    }

    // Obtener Grados y Oficinas para los modales
    $sqlGrados = "SELECT id_grado, nombre_grado FROM mae_grados WHERE activo = 1 ORDER BY id_grado ASC";
    $resGradosMaster = $conexion->query($sqlGrados);
    $arrGrados = [];
    while ($g = $resGradosMaster->fetch_assoc()) {
        $arrGrados[] = $g;
    }

    $sqlOficinas = "SELECT id_destino, nombre_destino FROM mae_destinos WHERE activo = 1 ORDER BY nombre_destino ASC";
    $resOficinasMaster = $conexion->query($sqlOficinas);
    $arrOficinas = [];
    while ($o = $resOficinasMaster->fetch_assoc()) {
        $arrOficinas[] = $o;
    }

    // Consulta de usuarios mejorada con trazabilidad (id_creador)
    if ($_SESSION['rol'] === 'Super Administrador') {
        // El Super Admin ve TODO, incluyendo quién registró a cada uno
        $sql = $conexion->query("
            SELECT u.*, 
                   g.nombre_grado, 
                   s.nombre_subunidad,
                   d.nombre_division,
                   r.nombre_region,
                   c.nombre as nom_creador,
                   c.apellido as ape_creador
            FROM usuario u 
            LEFT JOIN mae_grados g ON u.id_grado = g.id_grado
            LEFT JOIN sub_unidades_policiales s ON u.id_subunidad = s.id_subunidad
            LEFT JOIN divisiones_policiales d ON s.id_division = d.id_division
            LEFT JOIN regiones_policiales r ON d.id_region = r.id_region
            LEFT JOIN usuario c ON u.id_creador = c.id_usuario
            ORDER BY u.id_usuario DESC
        ");
    } else {
        // Los Administradores NO ven a los usuarios de rango Super Administrador
        // Y NO ven los usuarios registrados directamente por el Super Administrador (id_creador = 1)
        // a menos que ellos mismos sean los creadores (aunque usualmente el admin no es el 1).
        $sql = $conexion->query("
            SELECT u.*, 
                   g.nombre_grado, 
                   s.nombre_subunidad,
                   d.nombre_division,
                   r.nombre_region
            FROM usuario u 
            LEFT JOIN mae_grados g ON u.id_grado = g.id_grado
            LEFT JOIN sub_unidades_policiales s ON u.id_subunidad = s.id_subunidad
            LEFT JOIN divisiones_policiales d ON s.id_division = d.id_division
            LEFT JOIN regiones_policiales r ON d.id_region = r.id_region
            WHERE u.rol != 'Super Administrador' 
            AND (u.id_creador != 1 OR u.id_creador IS NULL)
            ORDER BY u.id_usuario DESC
        ");
    }

    if (!$sql) {
        die("<div class='alert alert-danger'>Error SQL en la consulta de usuarios: " . $conexion->error . "</div></div></div>");
    }

    // Calcular estadísticas
    $totalUsuarios = $sql->num_rows;

    // Reiniciar el puntero del resultado para poder usarlo de nuevo
    $sql->data_seek(0);

    // Inicializar arrays para estadísticas
    $estadisticasRegion = [];
    $estadisticasDivision = [];
    $estadisticasRol = [];
    $estadisticasEstado = ['Activo' => 0, 'Inactivo' => 0];

    // Recorrer resultados para calcular estadísticas
    while ($row = $sql->fetch_object()) {
        // Por región
        $region = $row->nombre_region ?? 'Sin asignar';
        if (!isset($estadisticasRegion[$region])) {
            $estadisticasRegion[$region] = 0;
        }
        $estadisticasRegion[$region]++;

        // Por división
        $division = $row->nombre_division ?? 'Sin asignar';
        if (!isset($estadisticasDivision[$division])) {
            $estadisticasDivision[$division] = 0;
        }
        $estadisticasDivision[$division]++;

        // Por rol
        $rol = $row->rol;
        if (!isset($estadisticasRol[$rol])) {
            $estadisticasRol[$rol] = 0;
        }
        $estadisticasRol[$rol]++;

        // Por estado
        $estado = $row->estado;
        if (empty($estado) || $estado === '0') $estado = 'Inactivo';
        
        if (!isset($estadisticasEstado[$estado])) {
            $estadisticasEstado[$estado] = 0;
        }
        $estadisticasEstado[$estado]++;
    }

    // Ordenar por cantidad (mayor a menor)
    arsort($estadisticasRegion);
    arsort($estadisticasDivision);
    arsort($estadisticasRol);

    // Reiniciar el puntero de nuevo para la tabla
    $sql->data_seek(0);

    ?>

    <!-- PANEL DE ESTADÍSTICAS -->
    <div class="card mb-4 shadow-sm border-0">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center"
            style="cursor: pointer;" data-bs-toggle="collapse" data-bs-target="#collapseStats" aria-expanded="false">
            <h6 class="m-0 font-weight-bold text-primary">
                <i class="fa-solid fa-chart-pie me-2"></i> Estadísticas de Usuarios
            </h6>
            <i class="fa-solid fa-chevron-down text-secondary"></i>
        </div>
        <div class="collapse" id="collapseStats">
            <div class="card-body bg-light">
                <!-- Tarjetas principales -->
                <div class="row text-center mb-4">
                    <div class="col-md-3 mb-2">
                        <div class="card bg-primary text-white p-3 h-100 shadow-sm border-0">
                            <h3><?= $totalUsuarios ?></h3>
                            <small>Total Usuarios</small>
                        </div>
                    </div>
                    <div class="col-md-3 mb-2">
                        <div class="card bg-success text-white p-3 h-100 shadow-sm border-0">
                            <h3><?= $estadisticasEstado['Activo'] ?></h3>
                            <small>Activos</small>
                        </div>
                    </div>
                    <div class="col-md-3 mb-2">
                        <div class="card bg-secondary text-white p-3 h-100 shadow-sm border-0">
                            <h3><?= $estadisticasEstado['Inactivo'] ?></h3>
                            <small>Inactivos</small>
                        </div>
                    </div>
                    <div class="col-md-3 mb-2">
                        <div class="card bg-info text-white p-3 h-100 shadow-sm border-0">
                            <h3><?= count($estadisticasRegion) ?></h3>
                            <small>Regiones Registradas</small>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- Por Región -->
                    <div class="col-md-4 mb-3">
                        <h6 class="text-muted mb-3 font-weight-bold">📍 Top Regiones</h6>
                        <ul class="list-group list-group-flush small bg-transparent">
                            <?php
                            $count = 0;
                            foreach ($estadisticasRegion as $nombre => $cantidad):
                                if ($count++ >= 5)
                                    break; // Top 5
                                $porcentaje = ($totalUsuarios > 0) ? round(($cantidad / $totalUsuarios) * 100, 1) : 0;
                                ?>
                                <li
                                    class="list-group-item bg-transparent d-flex justify-content-between align-items-center px-0 py-1">
                                    <span class="text-truncate" style="max-width: 200px;"
                                        title="<?= $nombre ?>"><?= $nombre ?></span>
                                    <span class="badge bg-white text-dark border"><?= $cantidad ?>
                                        (<?= $porcentaje ?>%)</span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>

                    <!-- Por División -->
                    <div class="col-md-4 mb-3">
                        <h6 class="text-muted mb-3 font-weight-bold">🏢 Top Divisiones</h6>
                        <ul class="list-group list-group-flush small bg-transparent">
                            <?php
                            $count = 0;
                            foreach ($estadisticasDivision as $nombre => $cantidad):
                                if ($count++ >= 5)
                                    break;
                                $porcentaje = ($totalUsuarios > 0) ? round(($cantidad / $totalUsuarios) * 100, 1) : 0;
                                ?>
                                <li
                                    class="list-group-item bg-transparent d-flex justify-content-between align-items-center px-0 py-1">
                                    <span class="text-truncate" style="max-width: 200px;"
                                        title="<?= $nombre ?>"><?= $nombre ?></span>
                                    <span class="badge bg-white text-dark border"><?= $cantidad ?>
                                        (<?= $porcentaje ?>%)</span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>

                    <!-- Por Rol -->
                    <div class="col-md-4 mb-3">
                        <h6 class="text-muted mb-3 font-weight-bold">👔 Por Rol</h6>
                        <ul class="list-group list-group-flush small bg-transparent">
                            <?php foreach ($estadisticasRol as $nombre => $cantidad):
                                $porcentaje = ($totalUsuarios > 0) ? round(($cantidad / $totalUsuarios) * 100, 1) : 0;
                                ?>
                                <li
                                    class="list-group-item bg-transparent d-flex justify-content-between align-items-center px-0 py-1">
                                    <span><?= $nombre ?></span>
                                    <span class="badge bg-white text-dark border"><?= $cantidad ?>
                                        (<?= $porcentaje ?>%)</span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!--CREAMOS EL BOTON REGISTRAR-->

    <div class="text-right mb-2">

        <!-- Botón de registro (Ahora Modal) -->
        <button type="button" class="btn btn-primary btn-rounded mb-2" data-bs-toggle="modal" data-bs-target="#modalRegistrar">
            <i class="fa-solid fa-plus"></i> &nbsp;Registrar
        </button>
        
        <!-- Botón para PDF (Color INFO para no confundir con Excel) -->
        <div class="pdf-button d-inline-block mr-2">
            <a href="fpdf/ReporteUsuario.php" target="_blank" class="btn btn-info btn-rounded mb-2">
                <i class="fas fa-file-pdf"></i>
                &nbsp; Reportes
            </a>
        </div>

        <!-- Botón para Excel -->
        <div class="excel-button d-inline-block mr-2">
            <button type="button" id="btn-exportar-excel" class="btn btn-rounded mb-2"
                style="background: linear-gradient(135deg, #1d6f42 0%, #155a35 100%); color: white; border: none;">
                <i class="fas fa-file-excel"></i>
                &nbsp; Exportar Excel
            </button>
        </div>


        <!-- Botón: Manual de Usuario PDF -->
        <a href="../controlador/generar_manual_pdf.php" target="_blank" class="btn btn-outline-danger btn-rounded mb-2 shadow-sm" 
           title="Descargar Manual de Gestión de Usuarios y Permisos en PDF">
            <i class="fas fa-file-pdf"></i>
            &nbsp; Manual PDF
        </a>
    </div>


    <!-- *************************PARA GENERAR REPORTE PDF ************************* -->


    <!-- BARRA DE ACCIONES MASIVAS -->
    <div id="barra-acciones-masivas" class="alert alert-primary d-none mb-3"
        style="position: sticky; top: 0; z-index: 100;">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <i class="fa-solid fa-check-double"></i>
                <strong id="usuarios-seleccionados-texto">0 usuarios seleccionados</strong>
            </div>
            <div class="btn-group" role="group">
                <button type="button" class="btn btn-sm btn-primary" onclick="mostrarModalPlantillaMasiva()">
                    <i class="fa-solid fa-users-cog"></i> Asignar Plantilla
                </button>
                <button type="button" class="btn btn-sm btn-info" onclick="mostrarModalCopiarPermisos()">
                    <i class="fa-solid fa-copy"></i> Copiar Permisos
                </button>
                <button type="button" class="btn btn-sm btn-secondary" onclick="limpiarSeleccion()">
                    <i class="fa-solid fa-times"></i> Cancelar
                </button>
            </div>
        </div>
    </div>

    <table class="table table-bordered table-hover w-100" id="example">
        <thead>
            <tr>
                <th scope="col" class="text-center">
                    <div class="d-flex align-items-center justify-content-center gap-2">
                        <input type="checkbox" id="seleccionar-todos" class="form-check-input"
                            style="cursor: pointer; width: 20px; height: 20px; border: 2px solid white;">
                        <label for="seleccionar-todos" class="mb-0 fw-bold"
                            style="cursor: pointer; font-size: 0.85rem;">
                            Todos
                        </label>
                    </div>
                </th>
                <th scope="col">ID</th>
                <th scope="col">GRADO / CARGO</th>
                <th scope="col">NOMBRE</th>
                <th scope="col">APELLIDO</th>
                <th scope="col">REGIÓN POLICIAL</th> <!-- Nueva Columna -->
                <th scope="col">DIVISIÓN POLICIAL</th> <!-- Nueva Columna -->
                <th scope="col">SUB-UNIDAD</th> <!-- Renombrada -->
                <th scope="col">ROL</th>
                <th scope="col">ESTADO</th>
                <?php if ($_SESSION['rol'] === 'Super Administrador') { ?>
                    <th scope="col">REGISTRADO POR</th>
                <?php } ?>
                <th class="text-center">ACCIONES</th>
            </tr>
        </thead>
        <tbody>
            <?php


            while ($datos = $sql->fetch_object()) { ?>
                <tr>
                    <td class="text-center">
                        <?php if ($datos->rol !== 'Super Administrador') { ?>
                            <input type="checkbox" class="form-check-input checkbox-usuario" value="<?= $datos->id_usuario ?>"
                                data-nombre="<?= $datos->nombre ?> <?= $datos->apellido ?>">
                        <?php } ?>
                    </td>
                    <td><?= $datos->id_usuario ?> </td>
                    <td><?= $datos->nombre_grado ?></td>
                    <td><?= $datos->nombre ?></td>
                    <td><?= $datos->apellido ?>
                        <div class="text-muted" style="font-size: 0.65rem;">DNI: <?= $datos->dni ?></div>
                    </td>
                    <td><?= $datos->nombre_region ?? '<span class="text-muted">-</span>' ?></td> <!-- Región -->
                    <td><?= $datos->nombre_division ?? '<span class="text-muted">-</span>' ?></td> <!-- División -->
                    <td><?= $datos->nombre_subunidad ?? '<span class="text-muted">-</span>' ?></td> <!-- Sub-Unidad -->
                    <td>
                        <?php
                        if ($datos->rol === 'Super Administrador') {
                            $badge = 'bg-danger'; // O bg-dark, distintivo
                        } elseif ($datos->rol === 'Administrador') {
                            $badge = 'bg-primary';
                        } else {
                            $badge = 'bg-secondary';
                        }
                        ?>
                        <span class="badge <?= $badge ?>"><?= $datos->rol ?></span>
                    </td>
                    <td class="text-center">
                        <?php
                        // Asegurar que estado tenga valor (para usuarios antiguos antes del update)
                        $estado = !empty($datos->estado) ? $datos->estado : 'Activo';
                        $badgeEstado = $estado === 'Activo' ? 'bg-success' : 'bg-secondary';
                        ?>
                        <span class="badge <?= $badgeEstado ?>"
                            id="badge-estado-<?= $datos->id_usuario ?>"><?= $estado ?></span>
                    </td>

                    <?php if ($_SESSION['rol'] === 'Super Administrador') { ?>
                        <td class="small">
                            <?php if ($datos->nom_creador) { ?>
                                <div class="text-secondary fw-semibold" style="font-size: 0.7rem; line-height: 1.1;">
                                    <i class="fas fa-user-check me-1" style="opacity: 0.7;"></i> <?= $datos->nom_creador ?> <?= $datos->ape_creador ?>
                                </div>
                                <div class="text-muted mt-1" style="font-size: 0.65rem; opacity: 0.8;">
                                    <i class="far fa-clock me-1"></i> <?= date('d/m/Y H:i', strtotime($datos->fecha_registro)) ?>
                                </div>
                            <?php } else { ?>
                                <span class="text-muted italic" style="font-size: 0.7rem;">Sistema Anterior</span>
                            <?php } ?>
                        </td>
                    <?php } ?>

                    <td class="text-center" style="white-space: nowrap;">
                        <div class="btn-group" role="group" aria-label="Acciones de usuario">
                            <!-- BOTON EDITAR -->
                            <a href="" data-bs-toggle="modal" data-bs-target="#exampleModal<?= $datos->id_usuario ?>"
                                class="btn btn-warning btn-sm" title="Editar Usuario">
                                <i class="fa-solid fa-pen-to-square"></i>
                            </a>

                            <!-- BOTON PERMISOS -->
                            <button type="button" class="btn btn-info btn-sm btn-permisos"
                                data-id-usuario="<?= $datos->id_usuario ?>"
                                data-nombre-usuario="<?= $datos->nombre ?> <?= $datos->apellido ?>"
                                title="Gestionar Permisos">
                                <i class="fa-solid fa-shield-halved"></i>
                            </button>

                            <!-- BOTON ESTADO -->
                            <?php if ($datos->rol !== 'Super Administrador') { ?>
                                <button onclick="cambiarEstado(<?= $datos->id_usuario ?>)"
                                    class="btn btn-sm <?= $estado === 'Activo' ? 'btn-secondary' : 'btn-success' ?>"
                                    id="btn-estado-<?= $datos->id_usuario ?>"
                                    title="<?= $estado === 'Activo' ? 'Desactivar' : 'Activar' ?>">
                                    <i class="fa-solid <?= $estado === 'Activo' ? 'fa-user-slash' : 'fa-user-check' ?>"></i>
                                </button>
                            <?php } ?>

                            <!-- BOTON ELIMINAR -->
                            <?php if ($puedeEliminar) { ?>
                                <a href="usuario.php?id=<?= $datos->id_usuario ?>" onclick="advertencia(event)"
                                    class="btn btn-danger btn-sm" title="Eliminar Permanentemente">
                                    <i class="fa-solid fa-trash"></i>
                                </a>
                            <?php } ?>
                        </div>
                    </td>
                </tr>

                <!--  MODAL -->
                <!-- Button trigger modal -->

                <!-- MODAL EDITAR USUARIO -->
                <div class="modal fade" id="exampleModal<?= $datos->id_usuario ?>" tabindex="-1"
                    aria-labelledby="exampleModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header d-flex justify-content-between">
                                <h5 class="modal-title w-100" id="exampleModalLabel">Modificar Usuario</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body p-4">
                                <form action="" method="POST" class="needs-validation">
                                    <input type="hidden" name="txtid" value="<?= $datos->id_usuario ?>">
                                    <div class="row g-3">
                                        <!-- Identificación -->
                                        <div class="col-md-4">
                                            <label class="form-label fw-bold small text-secondary">
                                                <i class="fas fa-file-alt text-primary me-2"></i>Tipo de Documento
                                            </label>
                                            <select name="txttipo" class="form-control" required>
                                                <option value="">Seleccione...</option>
                                                <option value="DNI" <?= $datos->tipo_documento == 'DNI' ? 'selected' : '' ?>>DNI</option>
                                                <option value="RUC" <?= $datos->tipo_documento == 'RUC' ? 'selected' : '' ?>>RUC</option>
                                                <option value="PASAPORTE" <?= $datos->tipo_documento == 'PASAPORTE' ? 'selected' : '' ?>>PASAPORTE</option>
                                                <option value="CARNET EXT" <?= $datos->tipo_documento == 'CARNET EXT' ? 'selected' : '' ?>>CARNET EXT</option>
                                                <option value="CIP" <?= $datos->tipo_documento == 'CIP' ? 'selected' : '' ?>>CIP</option>
                                            </select>
                                        </div>

                                        <div class="col-md-8">
                                            <label class="form-label fw-bold small text-secondary">
                                                <i class="fas fa-id-card text-primary me-2"></i>N° Documento
                                            </label>
                                            <input type="text" class="form-control" name="txtdni"
                                                value="<?= $datos->dni ?>" required>
                                        </div>

                                        <div class="col-12">
                                            <label class="form-label fw-bold small text-secondary">
                                                <i class="fas fa-star text-primary me-2"></i>Grado / Cargo
                                            </label>
                                            <select name="txtgrado" class="form-control" required>
                                                <option value="">Seleccione Grado...</option>
                                                <?php foreach ($arrGrados as $grado) { ?>
                                                    <option value="<?= $grado['id_grado'] ?>"
                                                        <?= $datos->id_grado == $grado['id_grado'] ? 'selected' : '' ?>>
                                                        <?= $grado['nombre_grado'] ?>
                                                    </option>
                                                <?php } ?>
                                            </select>
                                        </div>

                                        <!-- MODULO JERARQUICO EN MODAL -->
                                        <div class="col-12 bg-light p-3 rounded" style="border: 1px solid #dee2e6;">
                                            <label class="form-label fw-bold small text-success">Ubicación Policial (Nuevo
                                                Módulo)</label>

                                            <?php
                                            // Obtener la jerarquía actual del usuario si existe
                                            $idRegActual = '';
                                            $idDivActual = '';
                                            $idSubActual = $datos->id_subunidad ?? '';
                                            if ($idSubActual) {
                                                $sqlHier = "SELECT s.id_division, d.id_region 
                                                             FROM sub_unidades_policiales s 
                                                             JOIN divisiones_policiales d ON s.id_division = d.id_division 
                                                             WHERE s.id_subunidad = $idSubActual";
                                                $resHier = $conexion->query($sqlHier);
                                                if ($h = $resHier->fetch_assoc()) {
                                                    $idRegActual = $h['id_region'];
                                                    $idDivActual = $h['id_division'];
                                                }
                                            }
                                            ?>

                                            <div class="row g-2">
                                                <div class="col-12">
                                                    <label class="small text-muted">1. Región</label>
                                                    <select class="form-control form-control-sm select2-region-edit"
                                                        data-id="<?= $datos->id_usuario ?>">
                                                        <option value="">Seleccione Región...</option>
                                                        <?php
                                                        $sqlReg = "SELECT id_region, nombre_region FROM regiones_policiales WHERE estado = 1 ORDER BY nombre_region";
                                                        $resReg = $conexion->query($sqlReg);
                                                        while ($r = $resReg->fetch_assoc()) { ?>
                                                            <option value="<?= $r['id_region'] ?>"
                                                                <?= $idRegActual == $r['id_region'] ? 'selected' : '' ?>>
                                                                <?= $r['nombre_region'] ?>
                                                            </option>
                                                        <?php } ?>
                                                    </select>
                                                </div>
                                                <div class="col-12">
                                                    <label class="small text-muted">2. División</label>
                                                    <select class="form-control form-control-sm select2-division-edit"
                                                        data-id="<?= $datos->id_usuario ?>" <?= !$idRegActual ? 'disabled' : '' ?>>
                                                        <option value="">Seleccione División...</option>
                                                        <?php if ($idRegActual) {
                                                            $sqlDiv = "SELECT id_division, nombre_division FROM divisiones_policiales WHERE id_region = $idRegActual AND estado = 1 ORDER BY nombre_division";
                                                            $resDiv = $conexion->query($sqlDiv);
                                                            while ($d = $resDiv->fetch_assoc()) { ?>
                                                                <option value="<?= $d['id_division'] ?>"
                                                                    <?= $idDivActual == $d['id_division'] ? 'selected' : '' ?>>
                                                                    <?= $d['nombre_division'] ?>
                                                                </option>
                                                            <?php }
                                                        } ?>
                                                    </select>
                                                </div>
                                                <div class="col-12">
                                                    <label class="small text-muted">3. Sub-Unidad</label>
                                                    <select name="txtsubunidad"
                                                        class="form-control form-control-sm select2-subunidad-edit"
                                                        data-id="<?= $datos->id_usuario ?>" <?= !$idDivActual ? 'disabled' : '' ?> required>
                                                        <option value="">Seleccione Unidad...</option>
                                                        <?php if ($idDivActual) {
                                                            $sqlSub = "SELECT id_subunidad, nombre_subunidad FROM sub_unidades_policiales WHERE id_division = $idDivActual AND estado = 1 ORDER BY nombre_subunidad";
                                                            $resSub = $conexion->query($sqlSub);
                                                            while ($s = $resSub->fetch_assoc()) { ?>
                                                                <option value="<?= $s['id_subunidad'] ?>"
                                                                    <?= $idSubActual == $s['id_subunidad'] ? 'selected' : '' ?>>
                                                                    <?= $s['nombre_subunidad'] ?>
                                                                </option>
                                                            <?php }
                                                        } ?>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- 3. Datos Personales -->
                                        <div class="col-md-6">
                                            <label class="form-label fw-bold small text-secondary">Nombre</label>
                                            <div class="input-group">
                                                <span class="input-group-text bg-light border-end-0"><i
                                                        class="fas fa-user text-primary"></i></span>
                                                <input type="text" class="form-control border-start-0 ps-0" name="txtnombre"
                                                    value="<?= $datos->nombre ?>" required>
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <label class="form-label fw-bold small text-secondary">Apellido</label>
                                            <div class="input-group">
                                                <span class="input-group-text bg-light border-end-0"><i
                                                        class="fas fa-user-tag text-primary"></i></span>
                                                <input type="text" class="form-control border-start-0 ps-0"
                                                    name="txtapellido" value="<?= $datos->apellido ?>" required>
                                            </div>
                                        </div>


                                        <div class="col-12">
                                            <label class="form-label fw-bold small text-secondary">Correo
                                                Electrónico</label>
                                            <div class="input-group">
                                                <span class="input-group-text bg-light border-end-0"><i
                                                        class="fas fa-envelope text-primary"></i></span>
                                                <input type="email" class="form-control" name="txtemail"
                                                    value="<?= $datos->correo ?>" required
                                                    pattern="[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}$"
                                                    title="Ingresa un correo válido (ej: usuario@dominio.com)">
                                            </div>
                                        </div>

                                        <!-- 4. Datos de Cuenta -->
                                        <div class="col-md-6">
                                            <label class="form-label fw-bold small text-secondary">Usuario</label>
                                            <div class="input-group">
                                                <span class="input-group-text bg-light border-end-0"><i
                                                        class="fas fa-id-card text-primary"></i></span>
                                                <input type="text" class="form-control border-start-0 ps-0"
                                                    name="txtusuario" value="<?= $datos->usuario ?>" required>
                                            </div>
                                        </div>

                                        <!-- CAMPO NUEVA CONTRASEÑA -->
                                        <div class="col-md-6">
                                            <label class="form-label fw-bold small text-secondary">Nueva Contraseña (Opcional)</label>
                                            <div class="input-group">
                                                <span class="input-group-text bg-light border-end-0"><i
                                                        class="fas fa-key text-primary"></i></span>
                                                <input type="password" class="form-control border-start-0 ps-0"
                                                    name="txtpassword" placeholder="Dejar en blanco para no cambiar">
                                            </div>
                                            <small class="text-muted" style="font-size: 0.7rem;">Si olvidó su clave, escriba una nueva.</small>
                                        </div>

                                        <div class="col-12">
                                            <label class="form-label fw-bold small text-secondary">
                                                <i class="fas fa-user-shield text-primary me-2"></i>Rol de Usuario
                                            </label>
                                            <select name="txtrol" class="form-control" required>
                                                <option value="Usuario" <?= $datos->rol == 'Usuario' ? 'selected' : '' ?>>
                                                    Usuario</option>
                                                <option value="Administrador" <?= $datos->rol == 'Administrador' ? 'selected' : '' ?>>Administrador</option>
                                                <!-- Solo mostrar opción de Super Administrador si el usuario actual lo es -->
                                                <option value="Super Administrador" <?= $datos->rol == 'Super Administrador' ? 'selected' : '' ?>>Super Administrador</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="d-flex justify-content-end gap-2 mt-4">
                                        <button type="button" class="btn btn-secondary btn-sm px-3"
                                            data-bs-dismiss="modal">Cancelar</button>
                                        <button type="submit" value="ok" name="btnmodificar"
                                            class="btn btn-primary btn-sm px-4 fw-bold shadow-sm">
                                            <i class="fas fa-save me-1"></i> Guardar Cambios
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>


            <?php } ?>
        </tbody>
    </table>

    <!-- MODAL REGISTRAR NUEVO USUARIO -->
    <div class="modal fade" id="modalRegistrar" tabindex="-1" aria-labelledby="modalRegistrarLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header d-flex justify-content-between" style="background-color: #00779e; color: white;">
                    <h5 class="modal-title w-100" id="modalRegistrarLabel"><i class="fas fa-user-plus me-2"></i>Registrar Nuevo Usuario</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <form action="" method="POST" class="needs-validation">
                        <div class="row g-3">
                            <!-- 1. Identificación y Grado (Prioridad) -->
                            <div class="col-md-4">
                                <label class="form-label fw-bold small text-secondary">
                                    <i class="fas fa-file-alt text-primary me-2"></i>Tipo Documento
                                </label>
                                <select name="txttipo" class="form-control" required>
                                    <option value="DNI">DNI</option>
                                    <option value="CIP">CIP</option>
                                    <option value="RUC">RUC</option>
                                    <option value="PASAPORTE">PASAPORTE</option>
                                </select>
                            </div>
                            <div class="col-md-8">
                                <label class="form-label fw-bold small text-secondary">
                                    <i class="fas fa-id-card text-primary me-2"></i>N° Documento
                                </label>
                                <input type="text" class="form-control" name="txtdni" required placeholder="8 dígitos">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold small text-secondary">
                                    <i class="fas fa-star text-primary me-2"></i>Grado / Cargo
                                </label>
                                <select name="txtgrado" class="form-control select2-registrar" required>
                                    <option value="">Seleccione Grado...</option>
                                    <?php foreach ($arrGrados as $grado) { ?>
                                        <option value="<?= $grado['id_grado'] ?>"><?= $grado['nombre_grado'] ?></option>
                                    <?php } ?>
                                </select>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label fw-bold small text-secondary">
                                    <i class="fas fa-user-shield text-primary me-2"></i>Rol de Usuario
                                </label>
                                <select name="txtrol" class="form-control" required>
                                    <option value="Usuario">Usuario</option>
                                    <option value="Administrador">Administrador</option>
                                    <?php if($_SESSION['rol'] === 'Super Administrador') { ?>
                                        <option value="Super Administrador">Super Administrador</option>
                                    <?php } ?>
                                </select>
                            </div>

                            <!-- 2. Ubicación Policial -->
                            <div class="col-12 bg-light p-3 rounded" style="border: 1px solid #dee2e6;">
                                <label class="form-label fw-bold small text-success"><i class="fas fa-map-marker-alt me-1"></i> Ubicación Policial (Geográfica)</label>
                                <div class="row g-2 mt-1">
                                    <div class="col-md-4">
                                        <label class="small text-muted">1. Región</label>
                                        <select class="form-control form-control-sm select2-region-registrar">
                                            <option value="">Seleccione Región...</option>
                                            <?php
                                            $sqlReg = "SELECT id_region, nombre_region FROM regiones_policiales WHERE estado = 1 ORDER BY nombre_region";
                                            $resReg = $conexion->query($sqlReg);
                                            while ($r = $resReg->fetch_assoc()) { ?>
                                                <option value="<?= $r['id_region'] ?>"><?= $r['nombre_region'] ?></option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="small text-muted">2. División</label>
                                        <select class="form-control form-control-sm select2-division-registrar" disabled>
                                            <option value="">Seleccione División...</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="small text-muted">3. Sub-Unidad</label>
                                        <select name="txtsubunidad" class="form-control form-control-sm select2-subunidad-registrar" disabled required>
                                            <option value="">Seleccione Unidad...</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- 3. Datos Personales -->
                            <div class="col-md-6">
                                <label class="form-label fw-bold small text-secondary">Nombre</label>
                                <input type="text" class="form-control" name="txtnombre" required placeholder="Juan Carlos">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold small text-secondary">Apellido</label>
                                <input type="text" class="form-control" name="txtapellido" required placeholder="Pérez Rojas">
                            </div>

                            <div class="col-12">
                                <label class="form-label fw-bold small text-secondary">Correo Electrónico (Será su Usuario)</label>
                                <input type="email" class="form-control" name="txtemail" id="email_registrar" required placeholder="ejemplo@correo.com">
                            </div>

                            <!-- 4. Datos de Cuenta -->
                            <div class="col-md-6">
                                <label class="form-label fw-bold small text-secondary">Usuario</label>
                                <input type="text" class="form-control" name="txtusuario" id="usuario_registrar" readonly required>
                                <small class="text-muted" style="font-size:0.7rem;">Se genera automáticamente desde el correo.</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold small text-secondary">Contraseña</label>
                                <input type="password" class="form-control" name="txtpassword" required placeholder="Mínimo 6 caracteres">
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2 mt-4">
                            <button type="button" class="btn btn-secondary btn-sm px-3" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" value="ok" name="btnregistrar" class="btn btn-primary btn-sm px-4 fw-bold shadow-sm">
                                <i class="fas fa-save me-1"></i> Registrar Ahora
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>


</div>
</div>
<!-- fin del contenido principal -->


<!-- Select2 -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link rel="stylesheet"
    href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
    $(document).ready(function () {
        console.log("--- Iniciando Script de Edición de Usuario ---");

        // Inicializar Select2 al abrir el modal (Compatibilidad Edición y Registro)
        $('.modal').on('shown.bs.modal', function () {
            console.log("Modal abierto, inicializando Select2...");
            $(this).find('.select2-edit, .select2-region-edit, .select2-division-edit, .select2-subunidad-edit, .select2-registrar, .select2-region-registrar, .select2-division-registrar, .select2-subunidad-registrar').select2({
                theme: 'bootstrap-5',
                dropdownParent: $(this),
                width: '100%',
                placeholder: 'Escriba para buscar...',
                allowClear: true,
                minimumResultsForSearch: 0,
                language: {
                    noResults: function () { return "No se encontraron resultados"; },
                    searching: function () { return "Buscando..."; }
                }
            });
        });

        // --- LÓGICA DE JERARQUÍA PARA EDICIÓN ---
        $(document).on('change', '.select2-region-edit', function () {
            var idRegion = $(this).val();
            var modal = $(this).closest('.modal');
            var $division = modal.find('.select2-division-edit');
            var $subunidad = modal.find('.select2-subunidad-edit');
            $division.empty().append('<option value="">Seleccione División...</option>').prop('disabled', true).trigger('change');
            $subunidad.empty().append('<option value="">Seleccione Unidad...</option>').prop('disabled', true).trigger('change');
            if (idRegion) {
                $.get('../controlador/get_unidades_ajax.php', { action: 'get_divisiones', id_region: idRegion }, function (data) {
                    if (data && data.length > 0) {
                        $.each(data, function (i, div) { $division.append('<option value="' + div.id_division + '">' + div.nombre_division + '</option>'); });
                        $division.prop('disabled', false).trigger('change');
                    }
                });
            }
        });

        $(document).on('change', '.select2-division-edit', function () {
            var idDivision = $(this).val();
            var modal = $(this).closest('.modal');
            var $subunidad = modal.find('.select2-subunidad-edit');
            $subunidad.empty().append('<option value="">Seleccione Unidad...</option>').prop('disabled', true).trigger('change');
            if (idDivision) {
                $.get('../controlador/get_unidades_ajax.php', { action: 'get_subunidades', id_division: idDivision }, function (data) {
                    if (data && data.length > 0) {
                        $.each(data, function (i, sub) { $subunidad.append('<option value="' + sub.id_subunidad + '">' + sub.nombre_subunidad + '</option>'); });
                        $subunidad.prop('disabled', false).trigger('change');
                    }
                });
            }
        });

        // --- LÓGICA DE JERARQUÍA PARA REGISTRO NUEVO ---
        $(document).on('change', '.select2-region-registrar', function () {
            var idRegion = $(this).val();
            var modal = $(this).closest('.modal');
            var $division = modal.find('.select2-division-registrar');
            var $subunidad = modal.find('.select2-subunidad-registrar');
            $division.empty().append('<option value="">Seleccione División...</option>').prop('disabled', true).trigger('change');
            $subunidad.empty().append('<option value="">Seleccione Unidad...</option>').prop('disabled', true).trigger('change');
            if (idRegion) {
                $.get('../controlador/get_unidades_ajax.php', { action: 'get_divisiones', id_region: idRegion }, function (data) {
                    if (data && data.length > 0) {
                        $.each(data, function (i, div) { $division.append('<option value="' + div.id_division + '">' + div.nombre_division + '</option>'); });
                        $division.prop('disabled', false).trigger('change');
                    }
                });
            }
        });

        $(document).on('change', '.select2-division-registrar', function () {
            var idDivision = $(this).val();
            var modal = $(this).closest('.modal');
            var $subunidad = modal.find('.select2-subunidad-registrar');
            $subunidad.empty().append('<option value="">Seleccione Unidad...</option>').prop('disabled', true).trigger('change');
            if (idDivision) {
                $.get('../controlador/get_unidades_ajax.php', { action: 'get_subunidades', id_division: idDivision }, function (data) {
                    if (data && data.length > 0) {
                        $.each(data, function (i, sub) { $subunidad.append('<option value="' + sub.id_subunidad + '">' + sub.nombre_subunidad + '</option>'); });
                        $subunidad.prop('disabled', false).trigger('change');
                    }
                });
            }
        });

        // Sincronizar correo con usuario en registro
        $('#email_registrar').on('input', function() {
            $('#usuario_registrar').val($(this).val());
        });
    });
</script>

<!-- DataTables CSS & JS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<!-- Estilos adicionales para el sistema de permisos -->
<style>
    /* Eliminar imagen de fondo y limpiar interfaz */
    body,
    .page-content,
    .main-wrapper,
    .page-wrapper {
        background-image: none !important;
        background-color: #f7f9fb !important;
        /* Gris muy claro profesional */
    }

    /* Estilo UNIFICADO para toda la cabecera de la tabla */
    #example thead th {
        background: linear-gradient(135deg, #00779e 0%, #005a7a 100%) !important;
        color: white !important;
        border-bottom: none;
        vertical-align: middle;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        font-size: 0.8rem;
        /* Un poco más pequeño el header también */
        padding: 0.6rem 0.5rem;
    }

    /* COMPACTAR el cuerpo de la tabla para ganar espacio */
    #example tbody td {
        font-size: 0.85rem;
        /* Letra más pequeña para los datos */
        padding: 0.35rem 0.5rem;
        /* Menos espacio vertical y horizontal */
        vertical-align: middle;
        /* Centrado vertical */
        line-height: 1.2;
        /* Líneas un poco más juntas en textos largos */
    }

    .bg-gradient-primary {
        background: linear-gradient(135deg, #00779e 0%, #005f7f 100%);
    }

    .hover-shadow:hover {
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        transition: all 0.2s ease;
    }

    .cursor-pointer {
        cursor: pointer;
    }

    .permiso-switch {
        width: 3em;
        height: 1.5em;
    }

    .accordion-button:not(.collapsed) {
        background-color: #f8f9fa;
        color: #00779e;
    }

    /* Estilos para tarjetas de plantillas */
    .plantilla-card {
        transition: all 0.3s ease;
        border: 2px solid transparent;
    }

    .plantilla-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
    }

    .plantilla-card.border-primary {
        border-color: #007bff !important;
        background-color: #f0f8ff;
    }

    /* Barra de acciones masivas */
    #barra-acciones-masivas {
        animation: slideDown 0.3s ease-out;
    }

    @keyframes slideDown {
        from {
            opacity: 0;
            transform: translateY(-20px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* Checkbox personalizado */
    .form-check-input:checked {
        background-color: #007bff;
        border-color: #007bff;
    }

    /* Mejoras para modales de SweetAlert2 */
    .swal2-popup {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    .swal2-html-container {
        max-height: 500px;
        overflow-y: auto;
    }

    /* NUEVOS ESTILOS PARA TABS COMPACTOS */
    .nav-tabs-sm .nav-link {
        font-size: 0.85rem;
        padding: 0.4rem 0.8rem;
    }

    .nav-tabs-sm .nav-link.active {
        background-color: #007bff;
        color: white;
        border-color: #007bff;
    }

    .nav-tabs-sm .nav-link:hover:not(.active) {
        background-color: #f8f9fa;
    }

    /* Switches de permisos */
    .form-check-input {
        cursor: pointer;
        width: 2.5em;
        height: 1.25em;
    }

    .form-check-label {
        cursor: pointer;
        user-select: none;
    }

    /* Tab content con scroll suave */
    .tab-content {
        background-color: #f8f9fa;
        border-radius: 0 0 0.25rem 0.25rem;
    }

    /* Botones en grupo más compactos */
    .btn-group .btn-sm {
        padding: 0.25rem 0.5rem;
        font-size: 0.875rem;
    }

    /* Modal de permisos optimizado */
    .modal-permisos-popup {
        border-radius: 1rem;
    }

    /* Animación de switches */
    .form-check-input {
        transition: all 0.2s ease;
    }

    .form-check-input:checked {
        transform: scale(1.05);
    }

    /* ESTILOS PARA BOTONES DE PLANTILLAS ACTIVOS */
    .btn-plantilla-rapida.active {
        font-weight: 800 !important;
        box-shadow: inset 0 3px 5px rgba(0, 0, 0, 0.125) !important;
        transform: translateY(1px) !important;
        border-width: 2px !important;
    }

    .btn-plantilla-rapida {
        transition: all 0.3s ease;
    }

    .btn-plantilla-rapida:hover {
        transform: translateY(-2px);
    }

    /* ============================================
       ESTILOS PERSONALIZADOS PARA DATATABLES
       ============================================ */

    /* Contenedor principal de DataTables */
    .dataTables_wrapper {
        padding: 15px;
        background: #f8f9fa;
        border-radius: 8px;
    }

    /* Selector de entradas (Show 10 entries) */
    .dataTables_length label {
        display: flex;
        align-items: center;
        gap: 10px;
        font-weight: 500;
        color: #495057;
    }

    .dataTables_length select {
        padding: 6px 30px 6px 12px;
        border: 2px solid #00779e;
        border-radius: 6px;
        background: white;
        color: #00779e;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .dataTables_length select:hover {
        border-color: #005a7a;
        box-shadow: 0 0 0 3px rgba(0, 119, 158, 0.1);
    }

    /* Campo de búsqueda */
    .dataTables_filter input {
        padding: 8px 15px;
        border: 2px solid #00779e;
        border-radius: 6px;
        background: white;
        transition: all 0.3s ease;
    }

    .dataTables_filter input:focus {
        outline: none;
        box-shadow: 0 0 0 4px rgba(0, 119, 158, 0.2);
    }

    /* Información de registros */
    .dataTables_info {
        color: #6c757d;
        font-weight: 500;
        background: linear-gradient(135deg, #e3f2fd 0%, #f8f9fa 100%);
        padding: 10px 15px;
        border-radius: 6px;
        border-left: 4px solid #00779e;
    }

    /* Paginación */
    .dataTables_paginate .paginate_button {
        padding: 8px 15px;
        margin: 0 2px;
        border: 2px solid #dee2e6;
        border-radius: 6px;
        background: white;
        color: #495057;
        font-weight: 500;
        transition: all 0.3s ease;
    }

    .dataTables_paginate .paginate_button:hover {
        background: #e9ecef;
        border-color: #00779e;
        color: #00779e;
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }

    .dataTables_paginate .paginate_button.current {
        background: linear-gradient(135deg, #00779e 0%, #005a7a 100%);
        border-color: #00779e;
        color: white;
        font-weight: 700;
        box-shadow: 0 4px 12px rgba(0, 119, 158, 0.3);
    }

    .dataTables_paginate .paginate_button.previous:hover,
    .dataTables_paginate .paginate_button.next:hover {
        background: #00779e;
        color: white;
    }
</style>

<!-- Script del sistema profesional de permisos -->
<script src="../public/js/permisos.js?v=<?= time() ?>"></script>

<!-- Funciones Globales -->
<script>
    function advertencia(e) {
        e.preventDefault();
        var url = e.currentTarget.getAttribute('href');

        Swal.fire({
            title: '¿Estás seguro?',
            text: "¡No podrás recuperar este usuario!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = url;
            }
        });
    }

    function cambiarEstado(id) {
        Swal.fire({
            title: '¿Cambiar estado?',
            text: "El usuario cambiará entre Activo/Inactivo",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Sí, cambiar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                fetch(`../controlador/controlador_estado_usuario.php?id=${id}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            // Actualizar UI
                            let badge = document.getElementById('badge-estado-' + id);
                            let btn = document.getElementById('btn-estado-' + id);

                            if (data.nuevo_estado === 'Activo') {
                                badge.className = 'badge bg-success';
                                badge.innerText = 'Activo';
                                btn.className = 'btn btn-sm btn-secondary';
                                btn.innerHTML = '<i class="fa-solid fa-user-slash"></i>';
                                btn.title = 'Desactivar';
                            } else {
                                badge.className = 'badge bg-secondary';
                                badge.innerText = 'Inactivo';
                                btn.className = 'btn btn-sm btn-success';
                                btn.innerHTML = '<i class="fa-solid fa-user-check"></i>';
                                btn.title = 'Activar';
                            }

                            Swal.fire('¡Actualizado!', data.msg, 'success');
                        } else {
                            Swal.fire('Error', data.msg, 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        Swal.fire('Error', 'Error de conexión', 'error');
                    });
            }
        })
    }
</script>

<!-- Inicialización de Funciones de Gestión -->
<script>
    $(document).ready(function () {
        // Seleccionar/Deseleccionar todos
        $('#seleccionar-todos').on('change', function () {
            const isChecked = $(this).prop('checked');
            $('.checkbox-usuario').prop('checked', isChecked);
            actualizarBarraAcciones();
        });

        // Actualizar al seleccionar checkbox individual
        $(document).on('change', '.checkbox-usuario', function () {
            actualizarBarraAcciones();

            // Actualizar estado del checkbox "seleccionar todos"
            const totalCheckboxes = $('.checkbox-usuario').length;
            const checkedCheckboxes = $('.checkbox-usuario:checked').length;
            $('#seleccionar-todos').prop('checked', totalCheckboxes === checkedCheckboxes);
        });
    });

    /**
     * Actualizar barra de acciones masivas
     */
    function actualizarBarraAcciones() {
        const seleccionados = $('.checkbox-usuario:checked').length;

        if (seleccionados > 0) {
            $('#barra-acciones-masivas').removeClass('d-none');
            $('#usuarios-seleccionados-texto').text(`${seleccionados} usuario${seleccionados > 1 ? 's' : ''} seleccionado${seleccionados > 1 ? 's' : ''}`);
        } else {
            $('#barra-acciones-masivas').addClass('d-none');
        }
    }

    /**
     * Limpiar selección
     */
    function limpiarSeleccion() {
        $('.checkbox-usuario').prop('checked', false);
        $('#seleccionar-todos').prop('checked', false);
        actualizarBarraAcciones();
    }

    /**
     * Obtener IDs de usuarios seleccionados
     */
    function obtenerUsuariosSeleccionados() {
        const usuarios = [];
        $('.checkbox-usuario:checked').each(function () {
            usuarios.push({
                id: $(this).val(),
                nombre: $(this).data('nombre')
            });
        });
        return usuarios;
    }

    /**
     * Mostrar modal de asignación masiva de plantillas
     */
    async function mostrarModalPlantillaMasiva() {
        const usuarios = obtenerUsuariosSeleccionados();

        if (usuarios.length === 0) {
            Swal.fire('Atención', 'Debes seleccionar al menos un usuario', 'warning');
            return;
        }

        // Cargar plantillas disponibles
        try {
            const response = await fetch('../controlador/PermisosControlador.php?op=obtener_plantillas');
            const data = await response.json();

            if (!data.success) {
                throw new Error('No se pudieron cargar las plantillas');
            }

            const plantillas = data.data;

            // Generar HTML de plantillas
            let plantillasHTML = '';
            plantillas.forEach(plantilla => {
                const colorClass = getColorPlantilla(plantilla.slug);
                const iconClass = getIconoPlantilla(plantilla.slug);

                plantillasHTML += `
                    <div class="col-md-3 col-sm-6 mb-2">
                        <div class="card plantilla-card h-100 cursor-pointer hover-shadow" 
                             onclick="seleccionarPlantilla(${plantilla.id}, this)"
                             data-plantilla-id="${plantilla.id}"
                             style="transition: all 0.3s ease;">
                            <div class="card-body text-center p-3">
                                <i class="fa-solid ${iconClass} fa-2x ${colorClass} mb-2"></i>
                                <h6 class="card-title mb-2" style="font-size: 0.9rem;">${plantilla.nombre}</h6>
                                <p class="card-text text-muted" style="font-size: 0.75rem; margin-bottom: 0.5rem;">${plantilla.descripcion}</p>
                                <span class="badge bg-secondary" style="font-size: 0.7rem;">Nivel ${plantilla.nivel_acceso}</span>
                            </div>
                        </div>
                    </div>
                `;
            });

            const html = `
                <div class="text-start">
                    <div class="alert alert-info py-2 mb-3">
                        <i class="fa-solid fa-info-circle"></i>
                        Se asignará la plantilla a <strong>${usuarios.length} usuario${usuarios.length > 1 ? 's' : ''}</strong>
                    </div>
                    
                    <h6 class="mb-2">Selecciona una plantilla:</h6>
                    <div class="row" id="plantillas-grid">
                        ${plantillasHTML}
                    </div>
                    
                    <input type="hidden" id="plantilla-seleccionada" value="">
                </div>
            `;

            Swal.fire({
                title: 'Asignar Plantilla Masiva',
                html: html,
                width: 900,
                showCancelButton: true,
                confirmButtonText: '<i class="fa-solid fa-check"></i> Asignar',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#6c757d',
                preConfirm: () => {
                    const plantillaId = document.getElementById('plantilla-seleccionada').value;
                    if (!plantillaId) {
                        Swal.showValidationMessage('Debes seleccionar una plantilla');
                        return false;
                    }
                    return plantillaId;
                }
            }).then(async (result) => {
                if (result.isConfirmed) {
                    await asignarPlantillaMasiva(usuarios.map(u => u.id), result.value);
                }
            });

        } catch (error) {
            console.error('Error:', error);
            Swal.fire('Error', 'No se pudieron cargar las plantillas', 'error');
        }
    }

    /**
     * Seleccionar plantilla en el modal
     */
    function seleccionarPlantilla(plantillaId, elemento) {
        // Remover selección anterior
        document.querySelectorAll('.plantilla-card').forEach(card => {
            card.classList.remove('border-primary', 'border-3');
        });

        // Marcar como seleccionada
        elemento.classList.add('border-primary', 'border-3');
        document.getElementById('plantilla-seleccionada').value = plantillaId;
    }

    /**
     * Asignar plantilla masiva
     */
    async function asignarPlantillaMasiva(usuariosIds, plantillaId) {
        Swal.fire({
            title: 'Asignando plantilla...',
            html: 'Por favor espera',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        try {
            const response = await fetch('../controlador/PermisosControlador.php?op=asignar_plantilla_masiva', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    usuarios: usuariosIds,
                    plantilla_id: plantillaId
                })
            });

            const data = await response.json();

            if (data.success) {
                Swal.fire({
                    icon: 'success',
                    title: '¡Plantilla Asignada!',
                    html: `
                        <p>Se asignó la plantilla correctamente a:</p>
                        <p><strong>${data.exitosos} de ${data.total} usuarios</strong></p>
                        ${data.fallidos > 0 ? `<p class="text-danger">Fallidos: ${data.fallidos}</p>` : ''}
                    `,
                    confirmButtonText: 'Aceptar'
                }).then(() => {
                    limpiarSeleccion();
                });
            } else {
                throw new Error(data.message || 'Error al asignar plantilla');
            }
        } catch (error) {
            console.error('Error:', error);
            Swal.fire('Error', error.message, 'error');
        }
    }

    /**
     * Mostrar modal de copiar permisos
     */
    async function mostrarModalCopiarPermisos() {
        const usuarios = obtenerUsuariosSeleccionados();

        if (usuarios.length < 2) {
            Swal.fire('Atención', 'Debes seleccionar al menos 2 usuarios (1 origen y 1 o más destino)', 'warning');
            return;
        }

        // Generar opciones de usuarios origen
        let opcionesHTML = '';
        usuarios.forEach(usuario => {
            opcionesHTML += `<option value="${usuario.id}">${usuario.nombre}</option>`;
        });

        const html = `
            <div class="text-start">
                <div class="alert alert-info">
                    <i class="fa-solid fa-info-circle"></i>
                    Los permisos del usuario origen se copiarán a los demás usuarios seleccionados
                </div>
                
                <div class="mb-3">
                    <label class="form-label fw-bold">Usuario Origen (copiar desde):</label>
                    <select id="usuario-origen" class="form-select">
                        <option value="">Seleccione...</option>
                        ${opcionesHTML}
                    </select>
                </div>
                
                <div class="alert alert-warning">
                    <i class="fa-solid fa-exclamation-triangle"></i>
                    Se copiarán <strong>todos los permisos</strong> (plantilla + personalizaciones) a 
                    <strong>${usuarios.length - 1} usuario(s)</strong>
                </div>
            </div>
        `;

        Swal.fire({
            title: 'Copiar Permisos',
            html: html,
            width: 600,
            showCancelButton: true,
            confirmButtonText: '<i class="fa-solid fa-copy"></i> Copiar',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#17a2b8',
            cancelButtonColor: '#6c757d',
            preConfirm: () => {
                const origenId = document.getElementById('usuario-origen').value;
                if (!origenId) {
                    Swal.showValidationMessage('Debes seleccionar un usuario origen');
                    return false;
                }
                return origenId;
            }
        }).then(async (result) => {
            if (result.isConfirmed) {
                const origenId = result.value;
                const destinoIds = usuarios.filter(u => u.id != origenId).map(u => u.id);
                await copiarPermisos(origenId, destinoIds);
            }
        });
    }

    /**
     * Copiar permisos
     */
    async function copiarPermisos(origenId, destinoIds) {
        Swal.fire({
            title: 'Copiando permisos...',
            html: 'Por favor espera',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        try {
            const response = await fetch('../controlador/PermisosControlador.php?op=copiar_permisos', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    usuario_origen_id: origenId,
                    usuarios_destino_ids: destinoIds
                })
            });

            const data = await response.json();

            if (data.success) {
                Swal.fire({
                    icon: 'success',
                    title: '¡Permisos Copiados!',
                    html: `
                        <p>Se copiaron los permisos correctamente a:</p>
                        <p><strong>${data.exitosos} de ${data.total} usuarios</strong></p>
                        ${data.fallidos > 0 ? `<p class="text-danger">Fallidos: ${data.fallidos}</p>` : ''}
                    `,
                    confirmButtonText: 'Aceptar'
                }).then(() => {
                    limpiarSeleccion();
                });
            } else {
                throw new Error(data.message || 'Error al copiar permisos');
            }
        } catch (error) {
            console.error('Error:', error);
            Swal.fire('Error', error.message, 'error');
        }
    }

    /**
     * Obtener color según plantilla
     */
    function getColorPlantilla(slug) {
        const colores = {
            'usuario_estandar': 'text-secondary',
            'solo_lectura': 'text-info',
            'editor': 'text-success',
            'gestor': 'text-warning',
            'administrador': 'text-danger'
        };
        return colores[slug] || 'text-primary';
    }

    /**
     * Obtener icono según plantilla
     */
    function getIconoPlantilla(slug) {
        const iconos = {
            'usuario_estandar': 'fa-user',
            'solo_lectura': 'fa-eye',
            'editor': 'fa-pen',
            'gestor': 'fa-gear',
            'administrador': 'fa-crown'
        };
        return iconos[slug] || 'fa-shield';
    }

    /**
     * Exportar tabla a Excel
     */
    document.getElementById('btn-exportar-excel').addEventListener('click', function () {
        // Obtener la tabla
        const table = document.getElementById('example');

        // Crear un libro de trabajo
        const wb = XLSX.utils.table_to_book(table, { sheet: "Usuarios" });

        // Generar nombre de archivo con fecha
        const fecha = new Date().toISOString().split('T')[0];
        const nombreArchivo = `usuarios_${fecha}.xlsx`;

        // Descargar el archivo
        XLSX.writeFile(wb, nombreArchivo);

        // Mostrar mensaje de éxito
        Swal.fire({
            icon: 'success',
            title: '¡Exportado!',
            text: `Se ha descargado el archivo ${nombreArchivo}`,
            timer: 2000,
            showConfirmButton: false
        });
    });


    /**
     * Hacer la tabla responsive
     */
    $(document).ready(function () {
        const isSuper = <?= ($_SESSION['rol'] === 'Super Administrador') ? 'true' : 'false' ?>;

        // Configurar DataTable con responsive
        const table = $('#example').DataTable({
            responsive: true,
            searching: true,
            autoWidth: false,
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json'
            },
            pageLength: 10,
            order: [[1, 'desc']], 
            columnDefs: [
                { 
                    orderable: false, 
                    targets: isSuper ? [0, 11] : [0, 10] 
                },
                { 
                    searchable: false, 
                    targets: isSuper ? [0, 10, 11] : [0, 10] 
                },
                { 
                    className: 'text-center', 
                    targets: isSuper ? [0, 1, 9, 10, 11] : [0, 1, 9, 10] 
                }
            ]
        });

        // Sincronizar búsqueda sensible con el ID del creador
        // (Esto es automático al poner searchable: false en la columna 10)

        // Verificar si hay mensaje de éxito en la URL (Movido aquí para centralizar JS)
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('msg') === 'permisos_ok') {
            Swal.fire({
                icon: 'success', 
                title: '¡Permisos Actualizados!',
                text: 'Los permisos se guardaron correctamente.',
                confirmButtonColor: '#3085d6'
            }).then(() => {
                window.history.replaceState({}, document.title, window.location.pathname);
            });
        }
    });

</script>

<!-- Librería XLSX para exportar a Excel -->
<script src="https://cdn.sheetjs.com/xlsx-0.20.1/package/dist/xlsx.full.min.js"></script>

<!-- Estilos Responsive Adicionales -->
<style>
    /* Responsive para móviles */
    @media (max-width: 768px) {

        /* Ocultar columnas menos importantes en móvil */
        #example th:nth-child(6),
        #example td:nth-child(6),
        #example th:nth-child(7),
        #example td:nth-child(7),
        #example th:nth-child(8),
        #example td:nth-child(8) {
            display: none;
        }

        /* Ajustar botones */
        .text-right {
            text-align: center !important;
        }

        .btn-rounded {
            font-size: 0.85rem;
            padding: 0.4rem 0.8rem;
        }

        /* Barra de acciones masivas */
        #barra-acciones-masivas .btn-group {
            flex-direction: column;
            width: 100%;
        }

        #barra-acciones-masivas .btn {
            width: 100%;
            margin-bottom: 5px;
        }

        /* Tabla más compacta */
        .table {
            font-size: 0.85rem;
        }

        .table th,
        .table td {
            padding: 0.5rem;
        }
    }

    /* Responsive para tablets */
    @media (max-width: 992px) {

        #example th:nth-child(6),
        #example td:nth-child(6) {
            display: none;
        }
    }
</style>

<?php require('./layout/footer.php'); ?>