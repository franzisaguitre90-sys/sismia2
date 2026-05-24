<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole('secretaria');

$uid       = currentUserId();
$pageTitle = 'Dashboard Secretaria';

// Estadísticas
$stats = db()->query("
    SELECT COUNT(*) total,
           SUM(estado='aprobado') aprobados,
           SUM(estado='finalizado') finalizados,
           SUM(estado='rechazado') rechazados,
           (SELECT COUNT(*) FROM habilitaciones_ambientales WHERE tipo_resultado='habilitado') habilitados,
           (SELECT COUNT(*) FROM habilitaciones_ambientales WHERE tipo_resultado='no_habilitado') no_habilitados
    FROM hojas_de_ruta
")->fetch();

// Trámites aprobados pendientes de habilitación final
$pendHab = db()->query("
    SELECT hdr.*, CONCAT(uc.nombre,' ',uc.apellido) consultor, CONCAT(ut.nombre,' ',ut.apellido) tecnico,
           rai.numero_rai, rai.razon_social, rai.categoria_rai,
           (SELECT COUNT(*) FROM habilitaciones_ambientales h WHERE h.hoja_ruta_id=hdr.id) tiene_hab
    FROM hojas_de_ruta hdr
    JOIN usuarios uc ON hdr.consultor_id=uc.id
    LEFT JOIN usuarios ut ON hdr.tecnico_id=ut.id
    LEFT JOIN formularios_rai rai ON rai.hoja_ruta_id=hdr.id AND rai.estado_formulario='aprobado'
    WHERE hdr.estado='aprobado'
    ORDER BY hdr.fecha_revision_fin DESC LIMIT 10
")->fetchAll();

// Últimas habilitaciones registradas por esta secretaria
$ultHab = db()->query("
    SELECT h.*, hdr.codigo, CONCAT(uc.nombre,' ',uc.apellido) consultor,
           rai.razon_social, rai.categoria_rai
    FROM habilitaciones_ambientales h
    JOIN hojas_de_ruta hdr ON h.hoja_ruta_id=hdr.id
    JOIN usuarios uc ON hdr.consultor_id=uc.id
    LEFT JOIN formularios_rai rai ON rai.hoja_ruta_id=hdr.id
    ORDER BY h.fecha_registro DESC LIMIT 8
")->fetchAll();

require_once __DIR__ . '/../includes/dashboard_header.php';
?>

<!-- Stats Row -->
<div class="row g-3 mb-4">
  <?php
  $cards = [
    ['icon'=>'fas fa-folder','color'=>'#4a148c','label'=>'Total Trámites','val'=>$stats['total']??0,'bg'=>'#f3e5f5'],
    ['icon'=>'fas fa-thumbs-up','color'=>'#1b5e20','label'=>'Aprobados x Técnico','val'=>$stats['aprobados']??0,'bg'=>'#e8f5e9'],
    ['icon'=>'fas fa-certificate','color'=>'#1a237e','label'=>'Habilitados (RAI)','val'=>$stats['habilitados']??0,'bg'=>'#e8eaf6'],
    ['icon'=>'fas fa-times-circle','color'=>'#c62828','label'=>'No Habilitados','val'=>$stats['no_habilitados']??0,'bg'=>'#ffebee'],
    ['icon'=>'fas fa-flag-checkered','color'=>'#263238','label'=>'Finalizados','val'=>$stats['finalizados']??0,'bg'=>'#eceff1'],
    ['icon'=>'fas fa-ban','color'=>'#d32f2f','label'=>'Rechazados','val'=>$stats['rechazados']??0,'bg'=>'#ffcdd2'],
  ];
  foreach ($cards as $c): ?>
  <div class="col-6 col-md-4 col-lg-2">
    <div class="stat-card" style="border-left-color:<?= $c['color'] ?>">
      <div class="stat-icon" style="background:<?= $c['bg'] ?>;color:<?= $c['color'] ?>"><i class="<?= $c['icon'] ?>"></i></div>
      <div class="stat-num"><?= $c['val'] ?></div>
      <div class="stat-label"><?= $c['label'] ?></div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<!-- Acciones rápidas -->
<div class="row g-3 mb-4">
  <div class="col-12">
    <div class="d-flex flex-wrap gap-2">
      <a href="/SMIA2/modules/secretaria/habilitaciones.php" class="btn btn-primary px-4 py-2 fw-bold">
        <i class="fas fa-clipboard-check me-2"></i>Trámites para Habilitar
      </a>
      <a href="/SMIA2/modules/secretaria/registro_final.php" class="btn btn-outline-primary px-4 py-2">
        <i class="fas fa-certificate me-2"></i>Registro de Habilitaciones
      </a>
      <a href="/SMIA2/modules/secretaria/verificar.php" class="btn btn-outline-secondary px-4 py-2">
        <i class="fas fa-search me-2"></i>Verificar Resultados
      </a>
    </div>
  </div>
</div>

<!-- Trámites Aprobados – pendientes de habilitación -->
<div class="table-card mb-4">
  <div class="table-header">
    <h6 class="fw-bold mb-0">
      <i class="fas fa-clipboard-list me-2 text-primary"></i>Trámites Aprobados – Pendientes de Habilitación
    </h6>
    <a href="/SMIA2/modules/secretaria/habilitaciones.php" class="btn btn-sm btn-primary">Ver todos</a>
  </div>
  <div class="table-responsive">
    <table class="table table-hover mb-0">
      <thead><tr>
        <th>Código HDR</th><th>Empresa (RAI)</th><th>Cat.</th><th>Consultor</th>
        <th>Técnico</th><th>Estado Hab.</th><th>Acciones</th>
      </tr></thead>
      <tbody>
      <?php if (empty($pendHab)): ?>
      <tr><td colspan="7" class="text-center py-5 text-muted">
        <i class="fas fa-check-circle fa-3x mb-3 d-block text-success"></i>Sin trámites pendientes de habilitación
      </td></tr>
      <?php endif; ?>
      <?php foreach ($pendHab as $h): ?>
      <tr>
        <td><code class="text-purple fw-bold" style="color:#6a1b9a"><?= e($h['codigo']) ?></code></td>
        <td class="small"><?= $h['razon_social'] ? e($h['razon_social']) : '<span class="text-muted">Sin formulario RAI</span>' ?></td>
        <td><?php if ($h['categoria_rai']): $catC=['A'=>'danger','B'=>'warning','C'=>'success'][$h['categoria_rai']]??'secondary'; ?>
          <span class="badge bg-<?= $catC ?>">Cat. <?= $h['categoria_rai'] ?></span><?php endif; ?></td>
        <td class="small"><?= e($h['consultor']) ?></td>
        <td class="small"><?= $h['tecnico'] ? e($h['tecnico']) : '—' ?></td>
        <td>
          <?= $h['tiene_hab'] ? '<span class="badge bg-success">Habilitado</span>' : '<span class="badge bg-warning text-dark">Pendiente</span>' ?>
        </td>
        <td>
          <?php if (!$h['tiene_hab']): ?>
          <a href="/SMIA2/modules/secretaria/registro_final.php?hoja=<?= $h['id'] ?>" class="btn btn-sm btn-primary">
            <i class="fas fa-certificate me-1"></i>Registrar
          </a>
          <?php else: ?>
          <a href="/SMIA2/modules/secretaria/verificar.php?hoja=<?= $h['id'] ?>" class="btn btn-sm btn-outline-secondary">Ver</a>
          <?php endif; ?>
        </td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Últimas habilitaciones -->
<div class="table-card">
  <div class="table-header">
    <h6 class="fw-bold mb-0"><i class="fas fa-history me-2 text-success"></i>Últimas Habilitaciones Registradas</h6>
    <a href="/SMIA2/modules/secretaria/registro_final.php" class="btn btn-sm btn-success">Ver historial</a>
  </div>
  <div class="table-responsive">
    <table class="table table-hover mb-0">
      <thead><tr>
        <th>Código HDR</th><th>Empresa</th><th>Resultado</th><th>N° Certificado</th>
        <th>Fecha Emisión</th><th>Vencimiento</th><th>Ver</th>
      </tr></thead>
      <tbody>
      <?php if (empty($ultHab)): ?>
      <tr><td colspan="7" class="text-center text-muted py-4">Sin habilitaciones registradas</td></tr>
      <?php else: ?>
      <?php foreach ($ultHab as $h): ?>
      <tr>
        <td><code class="small"><?= e($h['codigo']) ?></code></td>
        <td class="small"><?= $h['razon_social'] ? e($h['razon_social']) : '—' ?></td>
        <td><?= estadoBadge($h['tipo_resultado']) ?></td>
        <td class="small fw-semibold"><?= $h['numero_certificado'] ? e($h['numero_certificado']) : '—' ?></td>
        <td class="small"><?= $h['fecha_emision'] ? fdate($h['fecha_emision'],'d/m/Y') : '—' ?></td>
        <td class="small">
          <?php if ($h['fecha_vencimiento']):
            $dv = diasRestantes($h['fecha_vencimiento']);
            $vc = $dv !== null && $dv < 30 ? 'text-danger fw-bold' : '';
            echo "<span class='$vc'>".fdate($h['fecha_vencimiento'],'d/m/Y')."</span>";
          else: echo '—'; endif; ?>
        </td>
        <td><a href="/SMIA2/modules/secretaria/verificar.php?hab=<?= $h['id'] ?>" class="btn btn-xs btn-outline-primary" style="font-size:.75rem;padding:.2rem .6rem">Ver</a></td>
      </tr>
      <?php endforeach; ?>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
