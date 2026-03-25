<?php
// sidebar.php - VERSIÓN DINÁMICA PROFESIONAL
require_once __DIR__ . "/../../modelo/conexion.php";

// 1. Obtener todos los módulos activos ordenados
$sqlModulos = "SELECT * FROM modulos WHERE activo = 1 ORDER BY orden ASC";
$resModulos = $conexion->query($sqlModulos);

$menuData = [
    'OPERATIVOS' => [],
    'AUXILIARES' => [],
    'UTILITARIOS' => [],
    'SISTEMA' => [],
    'OTROS' => []
];

while ($m = $resModulos->fetch_assoc()) {
    // FILTRO: Ocultar módulos que no queremos mostrar aunque estén activos en BD
    $slugFiltro = strtolower($m['slug'] ?? '');
    if (in_array($slugFiltro, ['destinos', 'ubicacion', 'ubicaciones', 'acerca']) || strpos($slugFiltro, 'acerca') !== false) continue;

    $cat = strtoupper($m['categoria'] ?? 'OTROS');
    if (!isset($menuData[$cat])) $cat = 'OTROS';
    $menuData[$cat][] = $m;
}

$current_uri = $_SERVER['REQUEST_URI'];
$base_url = (defined('BASE_URL') ? BASE_URL : '/');
// Asegurar que termine en barra solo si no es la raíz
if ($base_url !== '/' && substr($base_url, -1) !== '/') $base_url .= '/';
?>

<aside id="sidebar" class="sidebar">
  <div class="main-title">Menú Principal</div>

  <div class="menu">

    <?php 
    // Función auxiliar para detectar si un enlace es el activo con precisión
    if (!function_exists('isUrlActive')) {
        function isUrlActive($current_uri, $enlace) {
            if (empty($enlace)) return false;
            $path = parse_url($current_uri, PHP_URL_PATH);
            $file = basename($path);
            $enlaceFile = basename($enlace);
            return $file === $enlaceFile;
        }
    }

    // Función auxiliar INTELIGENTE para estandarizar URLs sin romperse en cPanel
    if (!function_exists('getCleanUrl')) {
        function getCleanUrl($base, $enlace) {
            if (empty($enlace) || $enlace === '#' || strpos($enlace, 'javascript') !== false) return '#';
            
            // Limpiamos saltos y barras sucias
            $enlace = str_replace(['../', './'], '', $enlace);
            $enlace = ltrim($enlace, '/');
            
            // INTELIGENCIA: Si el enlace está mal registrado en la BD y no indica en qué carpeta está, 
            // asumimos forzosamente por defecto que el archivo pertenece a la carpeta "vista/"
            if (strpos($enlace, 'vista/') !== 0 && strpos($enlace, 'controlador/') !== 0) {
                 $enlace = 'vista/' . $enlace;
            }
            
            return $base . $enlace;
        }
    }

    ?>

    <!-- MÓDULOS OPERATIVOS -->
    <?php foreach ($menuData['OPERATIVOS'] as $m): ?>
      <?php if (userCan($m['slug'])): ?>
        <?php 
          $isActive = isUrlActive($current_uri, $m['enlace']);
          // Caso especial: Seguimiento tiene subpáginas
          if ($m['slug'] === 'seguimiento'): 
            $isSeguimientoActive = strpos($current_uri, 'seguimiento_pac.php') !== false || strpos($current_uri, 'seguimiento_menores.php') !== false;
        ?>
          <div class="has-submenu <?= $isSeguimientoActive ? 'active' : '' ?>" id="submenu-seguimiento">
            <a href="javascript:void(0);" class="submenu-toggle">
              <i class="fa-solid <?= $m['icono'] ?>"></i> <span><?= $m['nombre'] ?></span>
              <i class="fa-solid fa-chevron-down arrow"></i>
            </a>
            <div class="submenu">
              <a href="<?= $base_url ?>vista/seguimiento_pac.php"
                class="<?= strpos($current_uri, 'seguimiento_pac.php') !== false ? 'active-link' : '' ?>">
                <i class="fa-solid fa-file-signature"></i> <span>Procesos PAC</span>
              </a>
              <a href="<?= $base_url ?>vista/seguimiento_menores.php"
                class="<?= strpos($current_uri, 'seguimiento_menores.php') !== false ? 'active-link' : '' ?>">
                <i class="fa-solid fa-cart-shopping"></i> <span>Menores a 8 UIT</span>
              </a>
            </div>
          </div>
        <?php elseif ($m['slug'] === 'cmn'): 
            $isCmnActive = strpos($current_uri, 'cmn_listado.php') !== false || 
                           strpos($current_uri, 'cmn_identificacion.php') !== false ||
                           strpos($current_uri, 'cmn_clasificacion.php') !== false ||
                           strpos($current_uri, 'cmn_consolidacion.php') !== false;
        ?>
          <div class="has-submenu <?= $isCmnActive ? 'active' : '' ?>" id="submenu-cmn">
            <a href="javascript:void(0);" class="submenu-toggle">
              <i class="fa-solid <?= $m['icono'] ?>"></i> <span><?= $m['nombre'] ?></span>
              <i class="fa-solid fa-chevron-down arrow"></i>
            </a>
            <div class="submenu">
              <a href="<?= $base_url ?>vista/cmn_listado.php"
                class="<?= strpos($current_uri, 'cmn_listado.php') !== false ? 'active-link' : '' ?>">
                <i class="fa-solid fa-users-gear"></i> <span>Responsables</span>
              </a>
              <a href="<?= $base_url ?>vista/cmn_identificacion.php"
                class="<?= strpos($current_uri, 'cmn_identificacion.php') !== false ? 'active-link' : '' ?>">
                <i class="fa-solid fa-magnifying-glass-chart"></i> <span>Fase Identificación</span>
              </a>
              <a href="<?= $base_url ?>vista/cmn_clasificacion.php"
                class="<?= strpos($current_uri, 'cmn_clasificacion.php') !== false ? 'active-link' : '' ?>">
                <i class="fa-solid fa-tags"></i> <span>Fase Clasificación</span>
              </a>
              <a href="<?= $base_url ?>vista/cmn_consolidacion.php"
                class="<?= strpos($current_uri, 'cmn_consolidacion.php') !== false ? 'active-link' : '' ?>">
                <i class="fa-solid fa-box-archive"></i> <span>Fase Consolidación</span>
              </a>
            </div>
          </div>
               <?php else: ?>
          <?php 
            // 🚨 FORZAR ENLACE INICIO Y DASHBOARD DESDE LA BASE DE DATOS
            $raw_enlace = $m['enlace'] ?? '';
            $slugVar = strtolower($m['slug'] ?? '');
            
            if ($slugVar === 'inicio') {
                $raw_enlace = 'vista/inicio.php';
            } elseif ($slugVar === 'dashboard') {
                $raw_enlace = 'controlador/dashboard.php';
            } elseif (empty($raw_enlace) || $raw_enlace === '#') {
                $raw_enlace = '#';
            }
            
            $enlace_final = getCleanUrl($base_url, $raw_enlace); 
          ?>
          <a href="<?= $enlace_final ?>"
            class="<?= $isActive ? 'active-link' : '' ?>">
            <i class="fa-solid <?= $m['icono'] ?>"></i> <span><?= $m['nombre'] ?></span>
          </a>
        <?php endif; ?>

      <?php endif; ?>
    <?php endforeach; ?>

    <!-- MÓDULOS AUXILIARES -->
    <?php 
      $canSeeAux = false;
      foreach ($menuData['AUXILIARES'] as $m) { if (userCan($m['slug'])) { $canSeeAux = true; break; } }
      if ($canSeeAux): 
        $isAnyAuxActive = false;
        foreach ($menuData['AUXILIARES'] as $m) { if (isUrlActive($current_uri, $m['enlace'])) { $isAnyAuxActive = true; break; } }
    ?>
      <div class="has-submenu <?= $isAnyAuxActive ? 'active' : '' ?>" id="submenu-auxiliares">
        <a href="javascript:void(0);" class="submenu-toggle">
          <i class="fa-solid fa-puzzle-piece"></i> <span>Módulos Auxiliares</span>
          <i class="fa-solid fa-chevron-down arrow"></i>
        </a>
                <div class="submenu">
          <?php foreach ($menuData['AUXILIARES'] as $m): ?>
            <?php if (userCan($m['slug'])): ?>
              <?php
                $raw_enlace_aux = $m['enlace'] ?? '';
                $slugAux = strtolower($m['slug'] ?? '');
                
                // 🚨 FORZAR ENLACE REPOSITORIO (Si en BD está vacío)
                if ($slugAux === 'repositorio') {
                    $raw_enlace_aux = 'vista/repositorio.php';
                }
                
                $enlace_final_aux = getCleanUrl($base_url, $raw_enlace_aux);
              ?>
              <a href="<?= $enlace_final_aux ?>"
                class="<?= isUrlActive($current_uri, $raw_enlace_aux) ? 'active-link' : '' ?>">
                <i class="fa-solid <?= $m['icono'] ?>"></i> <span><?= $m['nombre'] ?></span>
              </a>
            <?php endif; ?>
          <?php endforeach; ?>
        </div>

      </div>
    <?php endif; ?>

    <!-- MÓDULOS UTILITARIOS -->
    <?php 
      $canSeeUtils = false;
      foreach ($menuData['UTILITARIOS'] as $m) { if (userCan($m['slug'])) { $canSeeUtils = true; break; } }
      if ($canSeeUtils): 
        $isAnyUtilActive = false;
        foreach ($menuData['UTILITARIOS'] as $m) { if (isUrlActive($current_uri, $m['enlace'])) { $isAnyUtilActive = true; break; } }
    ?>
      <div class="has-submenu <?= $isAnyUtilActive ? 'active' : '' ?>" id="submenu-utils">
        <a href="javascript:void(0);" class="submenu-toggle">
          <i class="fa-solid fa-screwdriver-wrench"></i> <span>Utilitarios</span>
          <i class="fa-solid fa-chevron-down arrow"></i>
        </a>
        <div class="submenu">
          <?php foreach ($menuData['UTILITARIOS'] as $m): ?>
            <?php if (userCan($m['slug'])): ?>
              <a href="<?= getCleanUrl($base_url, $m['enlace'] ?? '') ?>"
                class="<?= isUrlActive($current_uri, $m['enlace']) ? 'active-link' : '' ?>">
                <i class="fa-solid <?= $m['icono'] ?>"></i> <span><?= $m['nombre'] ?></span>
              </a>
            <?php endif; ?>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endif; ?>

    <!-- MÓDULOS DE SISTEMA / CONFIGURACIÓN -->
    <?php 
      $canSeeSys = false;
      foreach ($menuData['SISTEMA'] as $m) { if (userCan($m['slug'])) { $canSeeSys = true; break; } }
      if ($canSeeSys): 
        $isAnySysActive = false;
        foreach ($menuData['SISTEMA'] as $m) { if (isUrlActive($current_uri, $m['enlace'])) { $isAnySysActive = true; break; } }
    ?>
      <div class="has-submenu <?= $isAnySysActive ? 'active' : '' ?>" id="submenu-config">
        <a href="javascript:void(0);" class="submenu-toggle">
          <i class="fa-solid fa-gears"></i> <span>Configuración</span>
          <i class="fa-solid fa-chevron-down arrow"></i>
        </a>
        <div class="submenu">
          <?php foreach ($menuData['SISTEMA'] as $m): ?>
            <?php if (userCan($m['slug'])): ?>
              <a href="<?= getCleanUrl($base_url, $m['enlace'] ?? '') ?>"
                class="<?= isUrlActive($current_uri, $m['enlace']) ? 'active-link' : '' ?>">
                <i class="fa-solid <?= $m['icono'] ?>"></i> <span><?= $m['nombre'] ?></span>
              </a>
            <?php endif; ?>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endif; ?>

    <!-- OTROS MÓDULOS (Nuevos módulos automáticos) -->
    <?php foreach ($menuData['OTROS'] as $m): ?>
      <?php if (userCan($m['slug'])): ?>
          <a href="<?= getCleanUrl($base_url, $m['enlace'] ?? '') ?>"
            class="<?= isUrlActive($current_uri, $m['enlace']) ? 'active-link' : '' ?>">
          <i class="fa-solid <?= $m['icono'] ?>"></i> <span><?= $m['nombre'] ?></span>
        </a>
      <?php endif; ?>
    <?php endforeach; ?>

  </div>
</aside>

<script>
  document.addEventListener('DOMContentLoaded', function () {
    const toggles = document.querySelectorAll('.submenu-toggle');

    toggles.forEach(toggle => {
      const parent = toggle.parentElement;
      const hasActiveChild = parent.querySelector('.active-link') !== null;

      if (hasActiveChild) {
        parent.classList.add('active');
      }

      toggle.addEventListener('click', function (e) {
        e.preventDefault();
        const isActive = parent.classList.contains('active');

        // Cerrar otros con cadencia (gestionado por CSS)
        document.querySelectorAll('.has-submenu').forEach(other => {
          if (other !== parent && other.classList.contains('active')) {
            other.classList.remove('active');
          }
        });

        // Alternar actual
        parent.classList.toggle('active');
      });
    });
  });
</script>