<?php
//segmentacion.php
declare(strict_types=1);

date_default_timezone_set('America/Lima');

$embed      = isset($_GET['embed']);           // si viene en modal
$anioActual = isset($_GET['anio']) ? (int)$_GET['anio'] : (int)date('Y');
$fechaHoy   = date('d/m/Y');

require_once '../modelo/conexion.php';
require_once '../modelo/segmentacion_util.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
if ($conn instanceof mysqli) {
    $conn->set_charset('utf8mb4');
}

/* =========================
   TOTAL PAC / UMBRAL 10 %
   ========================= */
$totalPac = 0.00;
if (function_exists('getTotalPac')) {
    $totalPac = (float)getTotalPac($conn, $anioActual);
} else {
    // Fallback por si acaso
    $sql = "SELECT ROUND(COALESCE(SUM(cuantia),0),2) AS total
            FROM segmentacion
            WHERE anio = ?";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("i", $anioActual);
        if ($stmt->execute()) {
            $stmt->bind_result($totalReal);
            if ($stmt->fetch()) {
                $totalPac = (float)$totalReal;
            }
        }
        $stmt->close();
    }
}
$umbral10 = round($totalPac * 0.10, 2);

/* =========================
   MENSAJES DE ERROR BACKEND
   ========================= */
/* =========================
   MENSAJES DE ERROR BACKEND
   ========================= */
   $errCode = $_GET['err'] ?? '';
   $errMsg  = '';
   
   switch ($errCode) {
       case 'faltan':
           $errMsg = 'Complete los campos obligatorios: Referencia PAC, Objeto de contratación y Descripción.';
           break;
   
       case 'tipo':
           $errMsg = 'Debe seleccionar un Tipo de Proceso válido.';
           break;
   
       case 'items0':
           $errMsg = 'Si marca que el procedimiento tiene ítems, al menos un ítem debe tener descripción y un monto mayor que 0.';
           break;
   
       case 'cuantia0':
           $errMsg = 'Ingrese una cuantía mayor que 0 o active la opción "¿El procedimiento tiene ítems?".';
           break;
   
       case 'pac0':
           $errMsg = 'No se ha configurado el Total PAC para el año seleccionado en la tabla config_pac.';
           break;
   
       case 'dup':
           // Error por clave única (anio + ref_pac) duplicada
           $errMsg = 'No se pudo registrar la segmentación. '
                   . 'Ya existe un procedimiento de selección registrado con el mismo año y N.° de referencia PAC. '
                   . 'Verifique la numeración o edite el registro existente.';
           break;
   
       case 'ex':
           // Error genérico sin mostrar el mensaje técnico de MySQL
           $errMsg = 'Se produjo un error inesperado al registrar la segmentación. '
                   . 'Intente nuevamente y, si el problema persiste, comuníquelo al área de sistemas.';
           break;

           case 'refinv':
            $errMsg = 'El N.° de referencia PAC debe ser un número entre 1 y 999.';
            break;
    
   
   }
   

?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Segmentación de Bienes y Servicios</title>

  <link rel="stylesheet" href="../public/bootstrap5/css/bootstrap.min.css" />
  <link rel="stylesheet" href="../public/fontawesome/css/all.min.css" />
  <script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
  <script>if(!window.jQuery){document.write('<script src="../public/js/jquery-3.6.0.min.js"><\/script>')}</script>

  <!-- Opcional: Bootstrap JS (si el padre no lo carga) -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<style>
/* ========= Paleta y etiquetas de resultado ========= */
.res-rutinario   { background:#188E45!important; color:#fff!important; }
.res-critico     { background:#F1C40F!important; color:#111!important; }
.res-operacional { background:#00BBD4!important; color:#111!important; }
.res-estrategico { background:#D32F2F!important; color:#fff!important; }

.flag-baja { background:#E8F5E9!important; color:#1b5e20!important; }
.flag-alta { background:#FFE0B2!important; color:#e65100!important; }
.flag-bajo { background:#E3F2FD!important; color:#0d47a1!important; }
.flag-alto { background:#FFEBEE!important; color:#b71c1c!important; }

.badge-pill { border-radius:50rem; }
.table-items input{ min-width:120px; }

/* Formularios compactos */
.form-compact .form-label,
.compact .form-label{
  font-size:.9rem; margin-bottom:.25rem; font-weight:600; color:#39424e;
  min-height:1.7rem; display:flex; align-items:flex-end;
}
.form-compact .form-control,
.form-compact .form-select,
.compact .form-control,
.compact .form-select{
  font-size:.95rem; padding:.38rem .55rem; height:calc(2rem + 2px); line-height:1.2;
}
.form-compact textarea.form-control,
.compact textarea.form-control{ min-height:2.2rem; }
.form-compact .row,
.compact .row{ --bs-gutter-x:.75rem; --bs-gutter-y:.5rem; }

/* Variante más baja en modal */
.modal-tight .form-label{ margin-bottom:.30rem; font-size:.95rem; min-height:1.7rem; }
.modal-tight .form-control, .modal-tight .form-select{ height:2rem; padding:.32rem .55rem; }
.modal-tight textarea.form-control{ min-height:2.2rem; }
.modal-tight .row{ --bs-gutter-y:.30rem; }
.modal-tight .mb-3{ margin-bottom:.6rem!important; }

/* Tarjetas compactas */
.card.compact-card .card-header{ padding:.5rem .75rem; }
.card.compact-card .card-body{ padding:.75rem; }
.guide-card .table th, .guide-card .table td{ padding:.4rem .5rem; }

/* Modal fit */
.modal-fit .modal-dialog{ max-width:1200px; }
.modal-fit .modal-content{ display:flex; max-height:82vh; border-radius:.75rem; }
.modal-fit .modal-header, .modal-fit .modal-footer{ flex:0 0 auto; }
.modal-fit .modal-body{ flex:1 1 auto; overflow:auto; }
body.modal-open{ overflow:hidden; }

/* Barra de acciones fija */
.action-bar{
  position:sticky; bottom:0; z-index:5;
  display:flex; justify-content:space-between; align-items:center; gap:.75rem;
  padding:.75rem 0; background:#ffffffcc; backdrop-filter:blur(6px);
  border-top:1px solid #e9ecef; box-shadow:0 -4px 10px rgba(0,0,0,.04);
}
.action-bar .btn{ padding:.5rem 1rem; font-weight:600; }
@media (max-width:576px){
  .action-bar{ flex-direction:column; align-items:stretch; }
  .action-bar .btn{ width:100%; }
}

/* Botones */
.btn-cta{
  padding:.6rem 1.1rem; font-weight:700; border-radius:.75rem; letter-spacing:.2px;
  box-shadow:0 6px 12px rgba(13,110,253,.15);
  transition:box-shadow .2s, transform .15s;
}
.btn-cta i{ font-size:.95rem; }
.btn-cta:hover{ box-shadow:0 8px 16px rgba(13,110,253,.25); transform:translateY(-1px); }
.btn-cta:active{ transform:translateY(0); box-shadow:0 4px 8px rgba(13,110,253,.20); }
.btn-ghost{ background:#fff; border:1px solid #dee2e6; }

/* Switches */
.form-check.form-switch .form-check-input{ width:3rem; height:1.6rem; cursor:pointer; }
.form-check.form-switch .form-check-label{ font-size:1rem; margin-left:.5rem; cursor:pointer; user-select:none; }
.form-check.form-switch{ padding-left:3.2rem; min-height:2rem; }

/* Anchos útiles */
.col-shrink{ flex:0 0 auto; width:auto; max-width:100%; }
.input-xxs{ max-width:6.5rem; }
.input-xs { max-width:9rem; }
.pill-narrow{ max-width:180px; }

.text-end-input{ text-align:right; }

.input-group.input-group-sm > .form-control,
.input-group.input-group-sm > .input-group-text{
  padding:.25rem .5rem; height:2rem; font-size:.9rem;
}

/* Resultado color medio ancho (si quieres usarlo) */
.result-half{ background-color:#fff!important; position:relative; }
.result-half.res-rutinario{   background-image:linear-gradient(#188E45,#188E45); }
.result-half.res-critico{     background-image:linear-gradient(#F1C40F,#F1C40F); }
.result-half.res-operacional{ background-image:linear-gradient(#00BBD4,#00BBD4); }
.result-half.res-estrategico{ background-image:linear-gradient(#D32F2F,#D32F2F); }
.result-half{
  background-repeat:no-repeat;
  background-size:50% 100%;
  border:1px solid #e5e7eb;
  font-weight:600;
}

/* Banda de riesgos */
.risk-band{
  background:#f8fafc;
  border:1px solid #b9c7d8;
  border-radius:.75rem;
  padding:.6rem .75rem;
  box-shadow:0 2px 6px rgba(0,0,0,.05);
}
.risk-band .form-label{
  margin-bottom:.25rem;
  font-size:.9rem;
  font-weight:700;
  color:#39424e;
  display:flex; align-items:flex-end;
  min-height:1.4rem;
}

/* Selects de riesgo */
.risk-select{ transition: background-color .15s ease, border-color .15s ease; }
.risk-select.risk-no{
  background:#E3F2FD;
  color:#0d47a1; border-color:#cfe3fb;
}
.risk-select.risk-yes{
  background:#EEF2F7;
  color:#22324a;
  border-color:#D9E2EF;
}

@media (min-width: 768px){
  .risk-band .col{ display:flex; flex-direction:column; }
}
</style>
</head>
<body class="bg-light">

<div class="container-fluid mt-2 top-compact">

  <?php if ($errMsg !== ''): ?>
    <div class="alert alert-danger d-flex align-items-center" role="alert">
      <i class="fas fa-exclamation-circle me-2"></i>
      <div><?= $errMsg ?></div>
    </div>
  <?php endif; ?>

  <?php if ($totalPac <= 0): ?>
  <div class="alert alert-info">
    <i class="fas fa-info-circle me-2"></i>
    Aún no existen procedimientos registrados para el año <?= htmlspecialchars((string)$anioActual) ?>.
  </div>
<?php endif; ?>


  <form id="form_segmentacion"
        class="form-compact modal-form modal-tight"
        action="../controlador/controlador_registrar_segmentacion.php"
        method="POST">

    <input type="hidden" name="embed" value="<?= $embed ? 1 : 0 ?>">
    <input type="hidden" name="anio"  value="<?= (int)$anioActual ?>">
    <input type="hidden" id="totalPac_hidden"  value="<?= htmlspecialchars((string)$totalPac) ?>">
    <input type="hidden" id="umbral10_hidden"  value="<?= htmlspecialchars((string)$umbral10) ?>">

    <!-- FILA A -->
    <div class="row align-items-end gy-2">
      <div class="col-6 col-md-1">
        <label for="ref_pac" class="form-label">
          N° Ref.PAC
          <i class="fa-regular fa-circle-question ms-1" data-bs-toggle="tooltip"
             title="Número de referencia PAC (identificador interno del usuario)."></i>
        </label>
        <input type="text" class="form-control" id="ref_pac" name="ref_pac"
               inputmode="numeric" pattern="[0-9]{1,3}" maxlength="3" placeholder="000" required />
      </div>

      <div class="col-6 col-md-2">
        <label for="cmn" class="form-label">
          CMN <span class="text-danger">*</span>
          <i class="fa-regular fa-circle-question ms-1" data-bs-toggle="tooltip"
             title="Cuadro Multianual de Necesidades"></i>
        </label>
        <input type="text" class="form-control" id="cmn" name="cmn" 
               placeholder="Código" required />
      </div>

      <div class="col-12 col-md-3">
        <label for="objeto_contratacion" class="form-label">Objeto</label>
        <select class="form-select" id="objeto_contratacion" name="objeto_contratacion" required>
          <option value="BIENES">BIENES</option>
          <option value="SERVICIOS">SERVICIOS</option>
          <option value="CONSULTORÍA DE OBRAS">CONSULTORÍA DE OBRAS</option>
        </select>
      </div>

      <div class="col-12 col-md-3">
        <label for="tipo_proceso" class="form-label">Tipo de Proceso</label>
        <select class="form-select" id="tipo_proceso" name="tipo_proceso_id" required>
          <option value="">-- Seleccione --</option>
          <?php
            $sql = "SELECT id, nombre FROM tipo_proceso WHERE estado=1 ORDER BY nombre ASC";
            $res = $conn->query($sql);
            while ($row = $res->fetch_assoc()) {
                echo '<option value="'.$row['id'].'">'.htmlspecialchars($row['nombre']).'</option>';
            }
          ?>
        </select>
      </div>

      <div class="col-12 col-md-3">
        <label for="cuantia" class="form-label">Cuantía (S/.)</label>
        <input type="number" step="0.01" class="form-control text-end-input"
               id="cuantia" name="cuantia" required />
      </div>
    </div>

    <!-- FILA B -->
    <div class="row align-items-end gy-2 mt-1">
      <div class="col-6 col-md-1">
        <label for="porcentaje" class="form-label">Porcentaje</label>
        <div class="input-group input-group-sm">
          <input type="number" class="form-control text-end-input"
                 id="porcentaje" name="porcentaje" value="0" readonly />
          <span class="input-group-text">%</span>
        </div>
      </div>

      <div class="col-6 col-md-2">
        <label for="cuantia_categoria" class="form-label">Cuantía</label>
        <input type="text" class="form-control"
               id="cuantia_categoria" name="cuantia_categoria" readonly />
      </div>

      <div class="col-12 col-md-2">
        <label for="riesgo_categoria" class="form-label">Riesgo</label>
        <input type="text" class="form-control"
               id="riesgo_categoria" name="riesgo_categoria" readonly />
      </div>

      <div class="col-12 col-md-7">
        <label for="descripcion" class="form-label">Descripción / detalle</label>
        <textarea class="form-control" id="descripcion" name="descripcion" rows="1" required></textarea>
      </div>
    </div>

    <!-- RIESGOS -->
    <div class="risk-band row gx-3 gy-2 align-items-end mt-2">
      <div class="col">
        <label class="form-label" for="declarado_desierto">¿Se declaró desierto en los 2 últimos años?</label>
        <select class="form-select riesgo risk-select" id="declarado_desierto" name="declarado_desierto" required>
          <option value="No" selected>No</option>
          <option value="Si">Sí</option>
        </select>
      </div>
      <div class="col">
        <label class="form-label" for="pocos_postores">
          Promedio postores últimos 2 años: Bienes ≤ 3 o Servicios ≤ 2
        </label>
        <select class="form-select riesgo risk-select" id="pocos_postores" name="pocos_postores" required>
          <option value="No" selected>No</option>
          <option value="Si">Sí</option>
        </select>
      </div>
      <div class="col">
        <label class="form-label" for="mercado_limitado">¿Existe disponibilidad limitada en el mercado?</label>
        <select class="form-select riesgo risk-select" id="mercado_limitado" name="mercado_limitado" required>
          <option value="No" selected>No</option>
          <option value="Si">Sí</option>
        </select>
      </div>
    </div>

    <!-- Resultado -->
    <div class="mb-4 mt-2">
      <label for="resultado_segmentacion" class="form-label">Resultado de Segmentación</label>
      <input type="text" class="form-control" id="resultado_segmentacion"
             name="resultado_segmentacion" readonly />
    </div>

    <!-- LAYOUT: guía + switches/ítems -->
    <div class="row g-3 align-items-start mt-2">
      <!-- Guía -->
      <div class="col-12 col-lg-5 order-1 order-lg-2">
        <div class="card compact-card guide-card">
          <div class="card-header d-flex justify-content-between align-items-center">
            <strong>📌 Guía de Resultados de Segmentación</strong>
            <button id="btnGuiaToggle"
                    class="btn btn-sm btn-outline-secondary"
                    type="button"
                    data-bs-toggle="collapse"
                    data-bs-target="#guiaSeg"
                    aria-expanded="true"
                    aria-controls="guiaSeg">
              Mostrar / Ocultar
            </button>
          </div>
          <div id="guiaSeg" class="collapse show">
            <div class="card-body">
              <div class="table-responsive">
                <table class="table table-sm table-bordered text-center align-middle mb-2">
                  <thead class="table-secondary">
                    <tr>
                      <th style="width:33%">Cuantía (Alta/Baja)</th>
                      <th style="width:33%">Riesgo (Alto/Bajo)</th>
                      <th style="width:34%">Resultado</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr><td>Baja</td><td>Bajo</td><td class="res-rutinario">Rutinario</td></tr>
                    <tr><td>Baja</td><td>Alto</td><td class="res-critico">Crítico</td></tr>
                    <tr><td>Alta</td><td>Bajo</td><td class="res-operacional">Operacional</td></tr>
                    <tr><td>Alta</td><td>Alto</td><td class="res-estrategico">Estratégico</td></tr>
                  </tbody>
                </table>
              </div>
              <small class="text-muted">
                El resultado se determina automáticamente con <b>Cuantía</b> + <b>Riesgo</b>.
              </small>
            </div>
          </div>
        </div>
      </div>

      <!-- Switches / Ítems -->
      <div class="col-12 col-lg-7 order-2 order-lg-1">
        <div class="form-check form-switch mb-2">
          <input class="form-check-input" type="checkbox" id="tiene_items" name="tiene_items" value="1">
          <label class="form-check-label" for="tiene_items">
            <strong>¿El procedimiento tiene ítems?</strong>
          </label>
        </div>

        <div class="my-2">
          <label class="form-label d-block mb-1">
            ¿Procedimiento programado? <span class="text-danger">*</span>
          </label>
          <div class="btn-group" role="group" aria-label="Programado">
            <input type="radio" class="btn-check" name="es_programado" id="prog_si" value="1" autocomplete="off" required>
            <label class="btn btn-outline-success" for="prog_si">Sí</label>

            <input type="radio" class="btn-check" name="es_programado" id="prog_no" value="0" autocomplete="off" required>
            <label class="btn btn-outline-secondary" for="prog_no">No</label>
          </div>
          <div class="form-text">Primero elige si es Programado o No Programado.</div>
        </div>

        <!-- Ítems -->
        <div id="card_items" class="card mb-3" style="display:none;">
          <div class="card-header d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-2">
              <strong>Ítems del procedimiento</strong>
              <span class="badge bg-secondary badge-pill">
                Total Ítems: S/ <span id="items_total">0.00</span>
              </span>
            </div>
            <div class="d-flex align-items-center gap-2">
              <div class="form-check">
                <input class="form-check-input" type="checkbox" id="usar_total_items" checked disabled>
                <label class="form-check-label" for="usar_total_items">
                  Usar <strong>suma de ítems</strong> en Cuantía
                </label>
              </div>
              <button type="button" id="btnAddItem" class="btn btn-sm btn-outline-primary">
                <i class="fas fa-plus"></i> Agregar ítem
              </button>
            </div>
          </div>
          <div class="card-body p-0">
            <div class="table-responsive">
              <table class="table table-sm table-items mb-0">
                <thead class="table-light">
                  <tr>
                    <th style="width:65%">Descripción del Ítem</th>
                    <th style="width:25%">Monto (S/.)</th>
                    <th style="width:10%"></th>
                  </tr>
                </thead>
                <tbody id="items_tbody"></tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Barra de acciones -->
    <div class="action-bar d-flex justify-content-between align-items-center">
      <button type="submit" class="btn btn-primary btn-cta">
        <i class="fas fa-save me-2"></i> Registrar Segmentación
      </button>
      <button type="button" id="btnCerrarModal" class="btn btn-ghost">
        ← Cerrar
      </button>
    </div>

  </form>
</div>

<script>
// ===================== Helpers =====================
function esSi(valor){
  const v=(valor||'').toString().trim().toUpperCase().normalize('NFD').replace(/[\u0300-\u036f]/g,'');
  return v==='SI';
}

function pintarResultado(resultado){
  const $r = $('#resultado_segmentacion');
  $r.removeClass('res-rutinario res-critico res-operacional res-estrategico');
  if (resultado==='Rutinario')         $r.addClass('res-rutinario');
  else if (resultado==='Crítico')      $r.addClass('res-critico');
  else if (resultado==='Operacional')  $r.addClass('res-operacional');
  else if (resultado==='Estratégico')  $r.addClass('res-estrategico');
  $r.val(resultado);
}

function calcularSegmentacion() {
  const totalPac = parseFloat($('#totalPac_hidden').val()) || 0;
  const umbral10 = parseFloat($('#umbral10_hidden').val()) || 0;
  const cuantia  = parseFloat($('#cuantia').val()) || 0;

  const porcentaje = totalPac > 0 
  ? ((cuantia / totalPac) * 100).toFixed(2) 
  : '0.00';

  $('#porcentaje').val(porcentaje);

  const cuantiaCat = cuantia>umbral10 ? 'Alta' : 'Baja';
  $('#cuantia_categoria').val(cuantiaCat)
    .removeClass('flag-alta flag-baja')
    .addClass(cuantiaCat==='Alta' ? 'flag-alta' : 'flag-baja');

  const r1=$('#declarado_desierto').val(), r2=$('#pocos_postores').val(), r3=$('#mercado_limitado').val();
  const riesgo=(esSi(r1)||esSi(r2)||esSi(r3)) ? 'Alto' : 'Bajo';
  $('#riesgo_categoria').val(riesgo)
    .removeClass('flag-alto flag-bajo')
    .addClass(riesgo==='Alto' ? 'flag-alto' : 'flag-bajo');

  let resultado='Rutinario';
  if (cuantiaCat==='Baja' && riesgo==='Alto')       resultado='Crítico';
  else if (cuantiaCat==='Alta' && riesgo==='Bajo')  resultado='Operacional';
  else if (cuantiaCat==='Alta' && riesgo==='Alto')  resultado='Estratégico';

  pintarResultado(resultado);
}

// ===================== Ítems =====================
function esc(s){ return String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }

function itemRowTemplate(n, desc='', monto=0){
  const m = Number(monto||0).toFixed(2);
  return `
    <tr>
      <td>
        <div class="input-group">
          <span class="input-group-text item-no">Ítem ${n}</span>
          <input type="text" class="form-control item-desc" name="items_descripcion[]" placeholder="Descripción del ítem" value="${esc(desc)}">
        </div>
      </td>
      <td>
        <input type="number" step="0.01" min="0.01" class="form-control item-monto text-end" name="items_monto[]" value="${m}" placeholder="0.00">
      </td>
      <td class="text-end">
        <button type="button" class="btn btn-sm btn-outline-danger btnRemoveItem" title="Eliminar ítem">
          <i class="fas fa-trash"></i>
        </button>
      </td>
    </tr>`;
}

function renumerarItems(){
  let i=1;
  $('#items_tbody tr .item-no').each(function(){ this.textContent = `Ítem ${i++}`; });
}

function recalcItemsSum(){
  let total = 0;
  $('#items_tbody .item-monto').each(function(){
    const v = parseFloat($(this).val() || '0');
    if (v > 0) total += v;
  });
  $('#items_total').text(
    total.toLocaleString('es-PE', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
  );
  if ($('#tiene_items').is(':checked')) {
    $('#cuantia').val(total.toFixed(2)).trigger('input');
  }
  return total;
}

function validateItemsRequiredFields(showAlert = true){
  let ok = true, firstBad = null;
  $('#items_tbody tr').each(function(){
    const $d = $(this).find('.item-desc');
    const $m = $(this).find('.item-monto');
    const d  = ($d.val()||'').trim();
    const v  = parseFloat($m.val()||'0');

    $d.removeClass('is-invalid'); $m.removeClass('is-invalid');

    if ((d === '' && v > 0) || (d !== '' && v <= 0)) {
      ok = false;
      if (d === '') $d.addClass('is-invalid');
      if (v <= 0)  $m.addClass('is-invalid');
      if (!firstBad) firstBad = ($d.hasClass('is-invalid') ? $d[0] : $m[0]);
    }
  });

  if (!ok && showAlert && window.Swal) {
    Swal.fire({
      icon:'warning',
      title:'Completa los ítems',
      text:'Si agregas un ítem, ingresa descripción y un monto mayor a 0 (o elimina la fila vacía).',
      confirmButtonColor:'#0d6efd'
    }).then(()=> firstBad?.scrollIntoView({behavior:'smooth', block:'center'}));
  }
  return ok;
}

// ===================== Arranque =====================
$(function(){
  calcularSegmentacion();

  // Aplicar color inicial a cuantía y riesgo
$('#cuantia_categoria')
  .removeClass('flag-alta flag-baja')
  .addClass('flag-baja');

$('#riesgo_categoria')
  .removeClass('flag-alto flag-bajo')
  .addClass('flag-bajo');


  $('#cuantia').on('input change keyup', calcularSegmentacion);
  $('.riesgo').on('change', calcularSegmentacion);

  $('#tiene_items').on('change', function(){
    const on = $(this).is(':checked');
    if (on){
      $('#card_items').slideDown(150);
      $('#usar_total_items').prop('checked', true).prop('disabled', true);
      $('#cuantia').prop('readonly', true).val('0.00').trigger('input');
      if ($('#items_tbody tr').length===0) $('#items_tbody').append(itemRowTemplate(1));
      recalcItemsSum();
    } else {
      $('#card_items').slideUp(150);
      $('#usar_total_items').prop('checked', false).prop('disabled', true);
      $('#cuantia').prop('readonly', false);
    }
  });

  $('#btnAddItem').on('click', function(){
    const n = $('#items_tbody tr').length + 1;
    $('#items_tbody').append(itemRowTemplate(n));
  });

  $('#items_tbody').on('click', '.btnRemoveItem', function(){
    $(this).closest('tr').remove();
    renumerarItems();
    recalcItemsSum();
  });

  $('#items_tbody').on('input change', '.item-desc, .item-monto', function(){
    validateItemsRequiredFields(false);
    recalcItemsSum();
  });

  $('#form_segmentacion').on('submit', function(e){
    const $btn = $(this).find('button[type="submit"]');

    // 1) Desactivamos el botón apenas se intenta enviar
    $btn.prop('disabled', true).addClass('disabled');

    // 2) Validación cuando HAY ítems
    if ($('#tiene_items').is(':checked')) {
      const ok    = validateItemsRequiredFields(true);
      const total = recalcItemsSum();

      if (!ok || total <= 0) {
        // Error → cancelamos submit y reactivamos botón
        e.preventDefault();
        $btn.prop('disabled', false).removeClass('disabled');

        if (ok && window.Swal && total <= 0){
          Swal.fire({
            icon:'warning',
            title:'Faltan montos',
            text:'Agrega al menos un ítem con monto mayor a 0.',
            confirmButtonColor:'#0d6efd'
          });
        }
        return false;
      }

    // 3) Validación cuando NO hay ítems
    } else {
      const c = parseFloat($('#cuantia').val() || '0');
      if (c <= 0) {
        // Error → cancelamos submit y reactivamos botón
        e.preventDefault();
        $btn.prop('disabled', false).removeClass('disabled');

        if (window.Swal){
          Swal.fire({
            icon:'warning',
            title:'Cuantía requerida',
            text:'Ingrese una cuantía mayor a 0 o active "¿El procedimiento tiene ítems?"',
            confirmButtonColor:'#0d6efd'
          });
        } else {
          alert('Ingrese una cuantía mayor a 0 o active "¿El procedimiento tiene ítems?"');
        }
        return false;
      }
    }

    // 4) Si todo está OK, dejamos que el submit siga
    // El navegador redirige al controlador y ya no hace falta reactivar el botón.
    return true;
  });



  if ($('#tiene_items').is(':checked')) {
    $('#card_items').show();
    $('#cuantia').prop('readonly', true);
    if ($('#items_tbody tr').length === 0) $('#items_tbody').append(itemRowTemplate(1));
    recalcItemsSum();
  }
});

// colores de selects de riesgo
function pintarSelectRiesgo(el){
  const yes = esSi($(el).val());
  $(el).toggleClass('risk-yes', yes).toggleClass('risk-no', !yes);
}
$('.risk-select').each(function(){ pintarSelectRiesgo(this); });
$('.risk-select').on('change', function(){ pintarSelectRiesgo(this); });

// Guía collapse
document.addEventListener('DOMContentLoaded', function () {
  var guia = document.getElementById('guiaSeg');
  var btn  = document.getElementById('btnGuiaToggle');
  if (!guia || !btn) return;

  guia.classList.add('show');
  try {
    if (window.bootstrap && typeof bootstrap.Collapse?.getOrCreateInstance === 'function') {
      var collapse = bootstrap.Collapse.getOrCreateInstance(guia, { toggle: false });
      btn.addEventListener('click', function (ev) {
        ev.preventDefault(); collapse.toggle();
      });
      return;
    }
  } catch(e){}
  btn.addEventListener('click', function (ev) {
    ev.preventDefault(); guia.classList.toggle('show');
  });
});
</script>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<?php if (isset($_GET['ok'])): ?>
<script>
  const EMBED_OK = <?= $embed ? 'true' : 'false' ?>;
  Swal.fire({
    icon:'success',
    title:'Registrado',
    text:'Se registró correctamente',
    confirmButtonColor:'#0d6efd'
  }).then(() => {
    if (EMBED_OK) {
      window.parent.postMessage({ type: 'SEGMENTACION_GUARDADA' }, '*');
    }
    const url = new URL(location.href);
    url.searchParams.delete('ok');
    history.replaceState({}, '', url.pathname + (url.searchParams.toString() ? ('?' + url.searchParams) : ''));
  });
</script>
<?php endif; ?>

<script>
  const EMBED = <?= $embed ? 'true' : 'false' ?>;

  function closeParentModal() {
    try {
      const pwin = window.parent;
      if (!pwin || pwin === window) return;
      const pdoc = pwin.document;
      const openModalEl = pdoc.querySelector('.modal.show');
      if (openModalEl) {
        const Modal = pwin.bootstrap?.Modal;
        if (Modal) {
          (Modal.getInstance(openModalEl) || Modal.getOrCreateInstance(openModalEl)).hide();
        } else {
          openModalEl.querySelector('.btn-close,[data-bs-dismiss="modal"]')?.click();
        }
      }
    } catch (e) {
      console.warn('No se pudo cerrar el modal del padre:', e);
    }
  }

  document.getElementById('btnCerrarModal')?.addEventListener('click', function () {
    if (EMBED) {
      closeParentModal();
    } else {
      window.location.href = 'segmentacion_listado.php';
    }
  });

  (function autoCloseOnOk(){
    const hasOk = new URLSearchParams(location.search).has('ok');
    if (EMBED && hasOk) {
      window.parent.postMessage({ type: 'SEGMENTACION_GUARDADA' }, '*');
    }
  })();
</script>

</body>
</html>
