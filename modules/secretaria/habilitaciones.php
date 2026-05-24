<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../includes/functions.php';
requireRole('secretaria');

$uid       = currentUserId();
$pageTitle = 'Trámites para Habilitar';

// Trámites aprobados que necesitan habilitación
$tramites = db()->query("
    SELECT hdr.*,
           CONCAT(uc.nombre,' ',uc.apellido) consultor,
           uc.email consultor_email, uc.clave_unica,
           CONCAT(ut.nombre,' ',ut.apellido) tecnico,
           rai.id rai_id, rai.razon_social, rai.nit, rai.categoria_rai,
           rai.representante_nombre, rai.representante_email,
           rai.municipio, rai.departamento,
           (SELECT COUNT(*) FROM habilitaciones_ambientales h WHERE h.hoja_ruta_id=hdr.id) tiene_habilitacion
    FROM hojas_de_ruta hdr
    JOIN usuarios uc ON hdr.consultor_id=uc.id
    LEFT JOIN usuarios ut ON hdr.tecnico_id=ut.id
    LEFT JOIN formularios_rai rai ON rai.hoja_ruta_id=hdr.id
    WHERE hdr.estado='aprobado'
    ORDER BY hdr.fecha_revision_fin ASC
")->fetchAll();

// Ver detalle
$ver = null;
if (!empty($_GET['ver'])) {
    foreach ($tramites as $t) {
        if ($t['id'] == (int)$_GET['ver']) { $ver = $t; break; }
    }
}

require_once __DIR__ . '/../../includes/dashboard_header.php';
?>

<div class="d-flex justify-content-between mb-4 align-items-center">
  <h5 class="fw-bold mb-0"><i class="fas fa-clipboard-check me-2 text-primary"></i>Trámites Aprobados – Pendientes de Habilitación RAI</h5>
  <span class="badge bg-primary px-3 py-2"><?= count(array_filter($tramites, fn($t)=>!$t['tiene_habilitacion'])) ?> pendientes</span>
</div>

<?php if ($ver): ?>
<div class="alert alert-info rounded-3 mb-3">
  <div class="d-flex justify-content-between">
    <div>
      Visualizando: <code><?= e($ver['codigo']) ?></code>
      <a href="/SMIA2/modules/secretaria/registro_final.php?hoja=<?= $ver['id'] ?>" class="btn btn-sm btn-primary ms-3">
        <i class="fas fa-certificate me-1"></i>Registrar Habilitación
      </a>
    </div>
    <a href="?" class="btn btn-sm btn-outline-secondary"><i class="fas fa-times"></i></a>
  </div>
</div>

<div class="row g-4 mb-4">
  <div class="col-md-6">
    <div class="table-card p-3">
      <h6 class="fw-bold mb-3 text-primary"><i class="fas fa-building me-2"></i>Datos de la Empresa</h6>
      <dl class="row small mb-0">
        <dt class="col-5 text-muted">Razón Social</dt><dd class="col-7"><?= $ver['razon_social']?e($ver['razon_social']):'—' ?></dd>
        <dt class="col-5 text-muted">NIT</dt><dd class="col-7"><?= $ver['nit']?e($ver['nit']):'—' ?></dd>
        <dt class="col-5 text-muted">Categoría RAI</dt><dd class="col-7">
          <?php if ($ver['categoria_rai']): $catC=['A'=>'danger','B'=>'warning','C'=>'success'][$ver['categoria_rai']]??'secondary'; ?>
          <span class="badge bg-<?= $catC ?>">Categoría <?= $ver['categoria_rai'] ?></span>
          <?php else: echo '—'; endif; ?>
        </dd>
        <dt class="col-5 text-muted">Municipio</dt><dd class="col-7"><?= $ver['municipio'] ? e($ver['municipio']) . ', ' . e($ver['departamento']) : '—' ?></dd>
        <dt class="col-5 text-muted">Representante</dt><dd class="col-7"><?= $ver['representante_nombre']?e($ver['representante_nombre']):'—' ?></dd>
      </dl>
    </div>
  </div>
  <div class="col-md-6">
    <div class="table-card p-3">
      <h6 class="fw-bold mb-3 text-success"><i class="fas fa-user-graduate me-2"></i>Datos del Consultor</h6>
      <dl class="row small mb-0">
        <dt class="col-5 text-muted">Consultor</dt><dd class="col-7"><?= e($ver['consultor']) ?></dd>
        <dt class="col-5 text-muted">Email</dt><dd class="col-7"><?= e($ver['consultor_email']) ?></dd>
        <dt class="col-5 text-muted">Clave Única</dt><dd class="col-7">
          <?= $ver['clave_unica']?"<code class='fw-bold'>{$ver['clave_unica']}</code>":'<span class="badge bg-warning text-dark">Por asignar</span>' ?>
        </dd>
        <dt class="col-5 text-muted">Técnico</dt><dd class="col-7"><?= $ver['tecnico']?e($ver['tecnico']):'—' ?></dd>
        <dt class="col-5 text-muted">Modalidad</dt><dd class="col-7"><?= $ver['modalidad']==='nuevo_rai'?'Nuevo RAI':'RAI Asignado' ?></dd>
      </dl>
    </div>
  </div>
</div>
<?php endif; ?>

<!-- Tabla de trámites -->
<div class="table-card">
  <div class="table-responsive">
    <table class="table table-hover mb-0">
      <thead><tr>
        <th>Código HDR</th><th>Empresa</th><th>Cat.</th><th>Consultor</th>
        <th>Técnico</th><th>Aprobado</th><th>Clave Única</th><th>Habilitación</th><th>Acción</th>
      </tr></thead>
      <tbody>
      <?php if (empty($tramites)): ?>
      <tr><td colspan="9" class="text-center py-5 text-muted">
        <i class="fas fa-check-double fa-3x text-success mb-3 d-block"></i>Sin trámites pendientes de habilitación
      </td></tr>
      <?php endif; ?>
      <?php foreach ($tramites as $t): ?>
      <tr class="<?= $t['tiene_habilitacion']?'table-success':'' ?>">
        <td><code class="fw-bold" style="color:#6a1b9a"><?= e($t['codigo']) ?></code></td>
        <td class="small"><?= $t['razon_social']?e($t['razon_social']):'<span class="text-muted">Sin RAI</span>' ?></td>
        <td><?php if ($t['categoria_rai']): ?><span class="badge bg-<?= ['A'=>'danger','B'=>'warning','C'=>'success'][$t['categoria_rai']]??'secondary' ?>">Cat.<?= $t['categoria_rai'] ?></span><?php endif; ?></td>
        <td class="small"><?= e($t['consultor']) ?></td>
        <td class="small"><?= $t['tecnico']?e($t['tecnico']):'—' ?></td>
        <td class="small"><?= fdate($t['fecha_revision_fin']??$t['fecha_asignacion'],'d/m/Y') ?></td>
        <td>
          <?php if ($t['clave_unica']): ?>
          <code class="small"><?= e($t['clave_unica']) ?></code>
          <?php else: ?>
          <span class="badge bg-warning text-dark">Sin clave</span>
          <?php endif; ?>
        </td>
        <td>
          <?= $t['tiene_habilitacion']?'<span class="badge bg-success"><i class="fas fa-check me-1"></i>Registrada</span>':'<span class="badge bg-warning text-dark">Pendiente</span>' ?>
        </td>
        <td>
          <div class="d-flex gap-1">
            <a href="?ver=<?= $t['id'] ?>" class="btn btn-xs btn-outline-secondary" style="font-size:.72rem;padding:.2rem .5rem"><i class="fas fa-eye"></i></a>
            <?php if (!$t['tiene_habilitacion'] && $t['rai_id']): ?>
            <a href="/SMIA2/modules/secretaria/registro_final.php?hoja=<?= $t['id'] ?>" class="btn btn-xs btn-primary" style="font-size:.72rem;padding:.2rem .5rem">
              <i class="fas fa-certificate me-1"></i>Habilitar
            </a>
            <?php endif; ?>
          </div>
        </td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/../../includes/dashboard_footer.php'; ?>
