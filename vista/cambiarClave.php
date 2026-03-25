<?php
session_start();

// Evitar Caché
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// 1. CARGAR CONEXIÓN Y ENTORNO PRIMERO
require_once __DIR__ . '/../modelo/conexion.php';

if (empty($_SESSION['nombre']) && empty($_SESSION['apellido'])) {
  header('Location: ' . (defined('BASE_URL') ? BASE_URL : '/sistem_job/') . 'vista/login/login.php');
  exit();
}

$id = (int) ($_SESSION['id'] ?? 0);

// require('../modelo/conexion.php'); // Ya incluido arriba

// ==============================
// Consultar usuario (solo para validar id)
// ==============================
$usuario = null;
if ($id > 0) {
  $res = $conexion->query("SELECT id_usuario FROM usuario WHERE id_usuario = $id");
  $usuario = $res ? $res->fetch_object() : null;
}

require('./layout/topbar.php');
require('./layout/sidebar.php');
?>

<style>
  ul li:nth-child(6),
  .activo {
    background: rgb(11, 150, 214) !important;
  }
</style>

<div class="page-content p-4">
  <h4 class="text-center text-secondary mb-4">CAMBIAR CONTRASEÑA</h4>

  <div class="row justify-content-center">
    <div class="col-lg-10">

      <form action="../controlador/controlador_cambiar_clave.php" method="POST" class="row g-3">

        <input type="hidden" name="txtid" value="<?= htmlspecialchars($id) ?>">

        <div class="col-md-6">
          <input type="password" name="txtclaveactual" class="form-control form-control-lg"
            placeholder="Contraseña actual" required>
        </div>

        <div class="col-md-6">
          <input type="password" name="txtclavenueva" class="form-control form-control-lg"
            placeholder="Nueva contraseña" required>
        </div>

        <div class="col-12 text-end mt-3">
          <button type="submit" name="btnmodificar" class="btn btn-primary btn-lg">
            <i class="fa-solid fa-sync me-1"></i> Modificar
          </button>
        </div>

      </form>

    </div>
  </div>
</div>

<?php require('./layout/footer.php'); ?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<?php if (isset($_SESSION['flash'])): ?>
  <script>
    Swal.fire({
      icon: "<?= $_SESSION['flash']['tipo'] ?>",
      title: "<?= $_SESSION['flash']['msg'] ?>",
      confirmButtonColor: "#0d6efd"
    });
  </script>
  <?php unset($_SESSION['flash']); endif; ?>