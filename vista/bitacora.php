<?php
require_once __DIR__ . '/../modelo/conexion.php';

// Seguridad: Solo super administradores o usuarios con permiso de BITACORA
if (!isset($_SESSION['rol']) || ($_SESSION['rol'] !== 'Super Administrador' && !isset($_SESSION['permisos']['BITACORA']['VER']))) {
    echo "<script>alert('Acceso Denegado: No tienes permisos para ver la bitácora.'); window.location.href='" . (defined('BASE_URL') ? BASE_URL : '/sistem_job/') . "vista/inicio.php';</script>";
    exit();
}

   require('../modelo/bitacora_model.php');
   $model = new BitacoraModel();
   $logs = $model->getLogs();
?>

<?php require('./layout/topbar.php'); ?>
<?php require('./layout/sidebar.php'); ?>

<div class="page-content">

    <h4 class="text-center text-secondary mb-4">
        <i class="fa-solid fa-shield-halved"></i> BITÁCORA DE AUDITORÍA
    </h4>

    <div class="alert alert-info text-center" role="alert" style="font-size: 0.9rem;">
        <i class="fa-solid fa-circle-info"></i> Se muestran los últimos <strong>500</strong> eventos registrados.
    </div>

    <div class="table-responsive">
        <table class="table table-bordered table-striped w-100" id="tablaBitacora" style="font-size: 0.95rem;">
          <thead class="bg-dark text-white">
            <tr>
              <th scope="col" style="width: 5%">ID</th>
              <th scope="col" style="width: 15%">FECHA/HORA</th>
              <th scope="col" style="width: 15%">USUARIO</th>
              <th scope="col" style="width: 15%">ACCIÓN</th>
              <th scope="col">DETALLE</th>
              <th scope="col" style="width: 10%">IP</th>
            </tr>
          </thead>
          <tbody>
          <?php while($row = $logs->fetch_object()){ ?>
           <tr>
              <td><?= $row->id ?></td>
              <td class="fw-bold text-primary"><?= $row->fecha_hora ?></td>
              <td>
                  <span class="badge bg-secondary text-white">
                      <i class="fa-solid fa-user"></i> <?= htmlspecialchars($row->usuario) ?>
                  </span>
              </td>
              <td class="fw-bold">
                <?php 
                    $cls = match($row->accion) {
                        'ELIMINAR SEGMENTACION' => 'text-danger',
                        'CREAR SEGMENTACION' => 'text-success',
                        'EDITAR SEGMENTACION' => 'text-warning',
                        default => 'text-dark'
                    };
                ?>
                <span class="<?= $cls ?>"><?= htmlspecialchars($row->accion) ?></span>
              </td>
              <td class="small text-muted"><?= htmlspecialchars($row->detalle) ?></td>
              <td class="small"><?= $row->ip ?></td>
           </tr>
          <?php } ?>
          </tbody>
        </table>
    </div>

</div>

<?php require('./layout/footer.php'); ?>

<script>
$(document).ready(function() {
    if ( ! $.fn.DataTable.isDataTable( '#tablaBitacora' ) ) {
        $('#tablaBitacora').DataTable({
            "language": { "url": "//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json" },
            "order": [[0, "desc"]],
            "pageLength": 25
        });
    }
});
</script>
