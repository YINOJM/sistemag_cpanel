<?php
// 1. CARGAR CONEXIÓN Y ENTORNO (Centralizado)
require_once __DIR__ . '/../modelo/conexion.php';

// Evitar Caché
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

if (empty($_SESSION['nombre']) && empty($_SESSION['apellido'])) {
    header('Location: ' . (defined('BASE_URL') ? BASE_URL : '/sistem_job/') . 'vista/login/login.php');
    exit();
}

$id = (int) ($_SESSION['id'] ?? 0);

// ===============================================
// 1️⃣ Conexión y procesamiento del POST ANTES DEL HTML
// require('../modelo/conexion.php'); // Ya incluido arriba
require('../controlador/controlador_modificar_perfil.php');

// ===============================================
// 2️⃣ Consulta para mostrar datos actuales del usuario
// ===============================================
$usuarioActual = null;
if ($id > 0) {
  // Consulta actualizada con JOINs para traer datos de la unidad policial
  $sql = "
    SELECT u.id_usuario, u.nombre, u.apellido, u.usuario,
           g.nombre_grado,
           s.nombre_subunidad,
           d.nombre_division,
           r.nombre_region
    FROM usuario u 
    LEFT JOIN mae_grados g ON u.id_grado = g.id_grado
    LEFT JOIN sub_unidades_policiales s ON u.id_subunidad = s.id_subunidad
    LEFT JOIN divisiones_policiales d ON s.id_division = d.id_division
    LEFT JOIN regiones_policiales r ON d.id_region = r.id_region
    WHERE u.id_usuario = $id
  ";
  $res = $conexion->query($sql);
  $usuarioActual = $res ? $res->fetch_object() : null;
}

// ===============================================
// 3️⃣ Carga de layout
// ===============================================
require('./layout/topbar.php');
require('./layout/sidebar.php');
?>

<style>
  ul li:nth-child(5),
  .activo {
    background: rgb(11, 150, 214) !important;
  }
</style>

<!-- =============================================== -->
<!-- CONTENIDO PRINCIPAL -->
<!-- =============================================== -->
<div class="page-content p-4">
  <h4 class="text-center text-secondary mb-4">PERFIL DE USUARIO</h4>

  <div class="row justify-content-center">
    <div class="col-lg-10">
      
      <!-- CARD DE INFORMACIÓN -->
      <div class="card shadow-sm border-0 mb-4">
          <div class="card-header bg-light">
              <h6 class="m-0 text-primary fw-bold"><i class="fas fa-id-card-clip me-2"></i>Información Institucional</h6>
          </div>
          <div class="card-body">
              <div class="row g-3">
                   <div class="col-md-6">
                      <label class="small text-muted fw-bold">Grado / Cargo</label>
                      <input type="text" class="form-control bg-light" value="<?= htmlspecialchars($usuarioActual->nombre_grado ?? 'Sin asignar') ?>" readonly>
                  </div>
                  <div class="col-md-6">
                      <label class="small text-muted fw-bold">Región Policial</label>
                      <input type="text" class="form-control bg-light" value="<?= htmlspecialchars($usuarioActual->nombre_region ?? 'Sin asignar') ?>" readonly>
                  </div>
                   <div class="col-md-6">
                      <label class="small text-muted fw-bold">División Policial</label>
                      <input type="text" class="form-control bg-light" value="<?= htmlspecialchars($usuarioActual->nombre_division ?? 'Sin asignar') ?>" readonly>
                  </div>
                   <div class="col-md-6">
                      <label class="small text-muted fw-bold">Sub-Unidad (Ubicación Actual)</label>
                      <input type="text" class="form-control bg-light" value="<?= htmlspecialchars($usuarioActual->nombre_subunidad ?? 'Sin asignar') ?>" readonly>
                  </div>
              </div>
          </div>
      </div>

      <!-- FORMULARIO DE EDICIÓN -->
      <div class="card shadow-sm border-0">
         <div class="card-header bg-light">
              <h6 class="m-0 text-primary fw-bold"><i class="fas fa-user-edit me-2"></i>Datos Personales y Acceso</h6>
          </div>
          <div class="card-body">
              <form method="POST" class="row g-3">
        
                <input type="hidden" name="txtid" value="<?= htmlspecialchars($usuarioActual->id_usuario ?? $id) ?>">
        
                <div class="col-md-6">
                  <label class="form-label small text-muted">Nombre</label>
                  <input type="text" name="txtnombre" class="form-control form-control-lg" placeholder="Nombre"
                    value="<?= htmlspecialchars($usuarioActual->nombre ?? '') ?>" required>
                </div>
        
                <div class="col-md-6">
                  <label class="form-label small text-muted">Apellido</label>
                  <input type="text" name="txtapellido" class="form-control form-control-lg" placeholder="Apellido"
                    value="<?= htmlspecialchars($usuarioActual->apellido ?? '') ?>" required>
                </div>
        
                <div class="col-md-6">
                  <label class="form-label small text-muted">Usuario (Login)</label>
                  <input type="text" name="txtusuario" class="form-control form-control-lg" placeholder="Usuario"
                    value="<?= htmlspecialchars($usuarioActual->usuario ?? '') ?>" required>
                </div>
        
                <div class="col-12 text-end mt-4">
                  <button type="submit" name="btnmodificar" value="ok" class="btn btn-primary btn-lg px-5">
                    <i class="fa-solid fa-pen-to-square me-1"></i> Actualizar Datos
                  </button>
                </div>
        
              </form>
          </div>
      </div>

    </div>
  </div>
</div>

<?php require('./layout/footer.php'); ?>

<!-- =============================================== -->
<!-- ALERTAS SWEETALERT -->
<!-- =============================================== -->

<?php if (isset($_GET['ok'])): ?>
  <script>
    Swal.fire({
      icon: 'success',
      title: 'Datos actualizados correctamente',
      timer: 1800,
      showConfirmButton: false
    });
  </script>
<?php elseif (isset($_GET['vacio'])): ?>
  <script>
    Swal.fire({
      icon: 'warning',
      title: 'Complete todos los campos obligatorios'
    });
  </script>
<?php elseif (isset($_GET['error'])): ?>
  <script>
    Swal.fire({
      icon: 'error',
      title: 'Error al actualizar los datos',
      text: '<?= isset($_GET['d']) ? htmlspecialchars($_GET["d"]) : "" ?>'
    });
  </script>
<?php endif; ?>

<!-- =============================================== -->
<!-- LIMPIA LA URL PARA EVITAR ALERTAS AL REFRESCAR -->
<!-- =============================================== -->
<script>
  if (window.history.replaceState) {
    const url = window.location.origin + window.location.pathname;
    // Espera 2 segundos después del alert para limpiar los parámetros
    setTimeout(() => {
      window.history.replaceState(null, null, url);
    }, 2000);
  }
</script>