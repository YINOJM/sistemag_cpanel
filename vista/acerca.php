<?php
session_start();
if (empty($_SESSION['nombre']) && empty($_SESSION['apellido'])) {
    header('location:login/login.php');
    exit();
}

require('../modelo/conexion.php');
require_once '../controlador/autocargar_permisos.php';
if (isset($conexion) && $conexion instanceof mysqli) {
    recargarPermisosUsuario($_SESSION['id'], $conexion);
}
?>

<style>
ul li:nth-child(5), .activo {
    background: rgb(11, 150, 214) !important;
}
</style>

<?php require('./layout/topbar.php'); ?>
<?php require('./layout/sidebar.php'); ?>

<div class="page-content p-4">
  <h4 class="text-center text-secondary mb-4">DATOS DE LA EMPRESA</h4>

  <?php $sql = $conexion->query("SELECT * FROM empresa"); ?>
  <div class="row justify-content-center">
    <div class="col-lg-10">
      <form action="../controlador/controlador_modificar_empresa.php" method="POST" class="row g-3">
        <?php while ($datos = $sql->fetch_object()): ?>
          <input type="hidden" name="txtid" value="<?= $datos->id_empresa ?>">

          <?php
          // Determinar si es administrador
          $isAdmin = (isset($_SESSION['rol']) && ($_SESSION['rol'] === 'Administrador' || $_SESSION['rol'] === 'Super Administrador'));
          $readonly = $isAdmin ? '' : 'readonly';
          ?>

          <div class="col-md-6">
            <input type="text" name="txtnombre" class="form-control form-control-lg"
                   placeholder="Razón Social" value="<?= htmlspecialchars($datos->nombre) ?>" required <?= $readonly ?>>
          </div>

          <div class="col-md-6">
            <input type="text" name="txttelefono" class="form-control form-control-lg"
                   placeholder="Teléfono" value="<?= htmlspecialchars($datos->telefono) ?>" <?= $readonly ?>>
          </div>

          <div class="col-md-6">
            <input type="text" name="txtubicacion" class="form-control form-control-lg"
                   placeholder="Ubicación" value="<?= htmlspecialchars($datos->ubicacion) ?>" <?= $readonly ?>>
          </div>

          <div class="col-md-6">
            <input type="text" name="txtruc" class="form-control form-control-lg"
                   placeholder="RUC" value="<?= htmlspecialchars($datos->ruc) ?>" <?= $readonly ?>>
          </div>

          <?php if ($isAdmin): ?>
          <div class="col-12 text-end mt-3">
            <button type="submit" name="btnmodificar" class="btn btn-primary btn-lg">
              <i class="fa-solid fa-pen-to-square me-1"></i> Modificar
            </button>
          </div>
          <?php endif; ?>
        <?php endwhile; ?>
      </form>
    </div>
  </div>
</div>

<?php require('./layout/footer.php'); ?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<?php if (isset($_GET['ok'])): ?>
<script>
Swal.fire({
  icon: 'success',
  title: 'Datos actualizados correctamente',
  showConfirmButton: false,
  timer: 1800
});
history.replaceState(null, '', 'acerca.php');
</script>

<?php elseif (isset($_GET['error'])): ?>
<script>
Swal.fire({
  icon: 'error',
  title: 'Error al actualizar los datos',
  text: '<?= htmlspecialchars($_GET["msg"] ?? "") ?>',
  confirmButtonColor: '#d33'
});
history.replaceState(null, '', 'acerca.php');
</script>

<?php elseif (isset($_GET['vacio'])): ?>
<script>
Swal.fire({
  icon: 'warning',
  title: 'Complete todos los campos requeridos',
  confirmButtonColor: '#f39c12'
});
history.replaceState(null, '', 'acerca.php');
</script>
<?php endif; ?>
