<?php

// config_pac.php
date_default_timezone_set('America/Lima');
require_once '../modelo/conexion.php';
require_once '../modelo/segmentacion_util.php'; // ← usamos getTotalPac()

/* Normalizar conexión y charset, igual que en segmentacion_listado.php */
if (isset($conn) && $conn instanceof mysqli) {
    // ok
} elseif (isset($conexion) && $conexion instanceof mysqli) {
    $conn = $conexion;
} elseif (isset($mysqli) && $mysqli instanceof mysqli) {
    $conn = $mysqli;
} else {
    die("Error: la conexión a BD no existe");
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$conn->set_charset('utf8mb4');

/* Cargar lista completa (descendente por año) */
$rows = [];
$res = $conn->query("SELECT anio, total_pac, updated_at, modo FROM config_pac ORDER BY anio DESC");
if ($res) {
    while ($r = $res->fetch_assoc()) {
        $rows[] = $r;
    }
    $res->close();
}

/**
 * Helper: obtener PAC real calculado por año
 * Usa la misma función global que el resto del sistema (solo programados)
 */
function pacCalculadoPorAnio(mysqli $conn, int $anio): float {
    return getTotalPac($conn, $anio); // suma cuantía de programados del año
}

// Para mostrar mensajes
$msg = isset($_GET['msg']) ? $_GET['msg'] : '';
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Configurar Total PAC</title>
  <link rel="stylesheet" href="../public/bootstrap5/css/bootstrap.min.css">
  <link rel="stylesheet" href="../public/fontawesome/css/all.min.css">
  <style>
    .badge-soft { background:#f1f3f5; color:#495057; }
    .table-sm td, .table-sm th { padding:.4rem .5rem; }
  </style>
</head>
<body class="bg-light">

<div class="container py-4">
  <div class="d-flex align-items-center mb-3">
    <h4 class="mb-0"><i class="fas fa-coins me-2"></i> Administración de Total PAC por Año</h4>
    <div class="ms-auto">
      <!-- Volver al listado de segmentación -->
      <a href="segmentacion_listado.php" class="btn btn-outline-secondary">
        <i class="fas fa-arrow-left"></i> Volver
      </a>
    </div>
  </div>

  <?php if ($msg === 'guardado'): ?>
    <div class="alert alert-success py-2">Total PAC guardado/actualizado correctamente.</div>
  <?php elseif ($msg === 'eliminado'): ?>
    <div class="alert alert-warning py-2">Registro eliminado.</div>
  <?php elseif ($msg === 'recalculado'): ?>
    <div class="alert alert-info py-2">Porcentajes y categorías recalculados para el año indicado.</div>
  <?php elseif ($msg === 'error'): ?>
    <div class="alert alert-danger py-2">Ocurrió un error al procesar la solicitud.</div>
  <?php endif; ?>

  <div class="row g-3">
    <div class="col-lg-5">
      <div class="card shadow-sm">
        <div class="card-header py-2">
          <strong>Registrar / Actualizar Total PAC</strong>
        </div>
        <div class="card-body">
          <form action="../controlador/controlador_config_pac.php" method="post" class="row g-3">
            <input type="hidden" name="accion" value="guardar" />
            <div class="col-12">
              <label class="form-label">Año</label>
              <input type="number" name="anio" id="anio" class="form-control" placeholder="Ej. 2025" required>
            </div>
            <div class="col-12">
              <label class="form-label">Total PAC (S/.)</label>
              <input type="number" step="0.01" name="total_pac" id="total_pac" class="form-control" placeholder="Ej. 7896174.12" required>
            </div>
            <div class="col-12">
              <button class="btn btn-primary">
                <i class="fas fa-save"></i> Guardar
              </button>
              <button type="button" id="btn-borrar" class="btn btn-outline-danger ms-2">
                <i class="fas fa-trash"></i> Eliminar
              </button>
              <button type="button" id="btn-recalcular" class="btn btn-outline-info ms-2">
                <i class="fas fa-sync"></i> Recalcular porcentajes
              </button>
            </div>
          </form>

          <!-- Formularios auxiliares (Eliminar / Recalcular) -->
          <form id="frm-borrar" action="../controlador/controlador_config_pac.php" method="post" class="d-none">
            <input type="hidden" name="accion" value="eliminar">
            <input type="hidden" name="anio" id="del_anio">
          </form>
          <form id="frm-recalcular" action="../controlador/controlador_config_pac.php" method="post" class="d-none">
            <input type="hidden" name="accion" value="recalcular">
            <input type="hidden" name="anio" id="rc_anio">
          </form>

          <hr>
          <div class="small text-muted">
            <i class="fas fa-info-circle me-1"></i>
            <b>Nota:</b> el Total PAC es único por año. Si ya existe, este formulario lo actualizará.
          </div>
        </div>
      </div>
    </div>

    <div class="col-lg-7">
      <div class="card shadow-sm">
        <div class="card-header py-2">
          <strong>Totales registrados</strong>
        </div>
        <div class="card-body">
          <div class="table-responsive">
            <table class="table table-sm table-striped align-middle">
              <thead class="table-light">
                <tr>
                  <th style="width:90px">Año</th>
                  <th class="text-end">Total PAC registrado (S/)</th>
                  <th class="text-end">10% (S/)</th>
                  <th class="text-end">Total PAC anual (S/)</th>

                  <th>Modo</th>
                  <th>Actualizado</th>
                  <th style="width:80px"></th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($rows)): ?>
                  <tr><td colspan="7" class="text-center text-muted">Sin registros</td></tr>
                <?php else: ?>
                  <?php foreach ($rows as $r):
                    $anioRow = (int)$r['anio'];
                    $tpac    = (float)$r['total_pac'];                 // valor en config_pac
                    $p10     = round($tpac * 0.10, 2);                 // 10% de lo configurado
                    $pacCalc = pacCalculadoPorAnio($conn, $anioRow);   // PAC real (programados)
                  ?>
                  <tr>
                    <td><span class="badge bg-secondary"><?= htmlspecialchars($anioRow) ?></span></td>
                    <td class="text-end">S/ <?= number_format($tpac, 2) ?></td>
                    <td class="text-end">
                      <span class="badge badge-soft">S/ <?= number_format($p10, 2) ?></span>
                    </td>
                    <td class="text-end <?= ($pacCalc > $tpac ? 'text-danger':'') ?>">
                      S/ <?= number_format($pacCalc, 2) ?>
                    </td>
                    <td><?= htmlspecialchars($r['modo']) ?></td>
                    <td><small><?= htmlspecialchars($r['updated_at']) ?></small></td>
                    <td class="text-end">
                      <button class="btn btn-sm btn-outline-primary btn-fill"
                              data-anio="<?= $anioRow ?>"
                              data-total="<?= $tpac ?>">
                        <i class="fas fa-pen"></i>
                      </button>
                    </td>
                  </tr>
                  <?php endforeach; ?>  
                <?php endif; ?>
              </tbody>
            </table>
          </div>
          <div class="small text-muted">
    <i class="fas fa-exclamation-triangle text-warning me-1"></i>
    La comparación entre el Total PAC registrado y el Total PAC anual permite verificar la consistencia de la programación anual.
</div>

        </div>
      </div>
    </div>
  </div>
</div>

<script src="../public/js/jquery.min.js"></script>
<script>
  // Cargar fila en el formulario
  $('.btn-fill').on('click', function(){
    const anio  = $(this).data('anio');
    const total = $(this).data('total');
    $('#anio').val(anio);
    $('#total_pac').val(total);
    $('html,body').animate({scrollTop:0}, 200);
  });

  // Eliminar
  $('#btn-borrar').on('click', function(){
    const anio = parseInt($('#anio').val(),10);
    if(!anio){ alert('Primero escribe/selecciona un año.'); return; }
    if(confirm('¿Eliminar configuración del año '+anio+'?')) {
      $('#del_anio').val(anio);
      $('#frm-borrar').submit();
    }
  });

  // Recalcular
  $('#btn-recalcular').on('click', function(){
    const anio = parseInt($('#anio').val(),10);
    if(!anio){ alert('Primero escribe/selecciona un año.'); return; }
    if(confirm('¿Recalcular porcentajes y categorías (Alta/Baja, Rutinario, etc.) para '+anio+'?')) {
      $('#rc_anio').val(anio);
      $('#frm-recalcular').submit();
    }
  });
</script>
</body>
</html>
