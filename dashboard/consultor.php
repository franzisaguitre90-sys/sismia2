<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole('consultor');

$uid       = currentUserId();
$pageTitle = 'Dashboard Consultor';

// Mis hojas de ruta
$mis = db()->prepare("
    SELECT COUNT(*) total, SUM(estado='ingresado') ingresados,
           SUM(estado='asignado') asignados, SUM(estado='en_revision') en_revision,
           SUM(estado='observado') observados, SUM(estado='aprobado') aprobados,
           SUM(estado='finalizado') finalizados, SUM(estado='rechazado') rechazados
    FROM hojas_de_ruta WHERE consultor_id=?
");
$mis->execute([$uid]);
$mis = $mis->fetch();

// Mis últimas hojas de ruta
$hojas = db()->prepare("
    SELECT hdr.*, ut.nombre tecnico_nombre, ut.apellido tecnico_apellido,
           (SELECT COUNT(*) FROM documentos d WHERE d.hoja_ruta_id=hdr.id) total_docs,
           (SELECT COUNT(*) FROM documentos d WHERE d.hoja_ruta_id=hdr.id AND d.estado='pendiente') docs_pendientes
    FROM hojas_de_ruta hdr
    LEFT JOIN usuarios ut ON hdr.tecnico_id=ut.id
    WHERE hdr.consultor_id=? ORDER BY hdr.fecha_ingreso DESC LIMIT 10
");
$hojas->execute([$uid]);
$hojas = $hojas->fetchAll();

// Búsqueda de hoja de ruta por código
$busqueda = null;
if (!empty($_GET['buscar'])) {
    $codigo = strtoupper(sanitize($_GET['buscar']));
    $bs = db()->prepare("
        SELECT hdr.*, ut.nombre tecnico_nombre, ut.apellido tecnico_apellido,
               (SELECT COUNT(*) FROM documentos d WHERE d.hoja_ruta_id=hdr.id) total_docs
        FROM hojas_de_ruta hdr LEFT JOIN usuarios ut ON hdr.tecnico_id=ut.id
        WHERE hdr.consultor_id=? AND hdr.codigo=?
    ");
    $bs->execute([$uid, $codigo]);
    $busqueda = $bs->fetch();
}

require_once __DIR__ . '/../includes/dashboard_header.php';
?>

<!-- Alerta de trámites observados -->
<?php if ($mis['observados'] > 0): ?>
<div class="alert alert-warning d-flex align-items-center gap-3 mb-4 rounded-3">
  <i class="fas fa-exclamation-triangle fa-lg"></i>
  <div>Tiene <strong><?= $mis['observados'] ?></strong> trámite(s) con observaciones del técnico. Revise y corrija la documentación.
  <a href="/SMIA2/modules/consultor/mis_tramites.php?estado=observado" class="ms-2 btn btn-sm btn-warning">Ver observados</a></div>
</div>
<?php endif; ?>

<!-- Buscador de Hoja de Ruta -->
<div class="row mb-4">
  <div class="col-12">
    <div class="table-card p-3">
      <h6 class="fw-bold mb-3"><i class="fas fa-search me-2 text-primary"></i>Seguimiento de Hoja de Ruta por Código</h6>
      <form method="GET" class="d-flex gap-2">
        <input name="buscar" class="form-control" placeholder="Ingrese código HDR (ej: HDR-2026-000001)"
               value="<?= e($_GET['buscar']??'') ?>" style="max-width:350px">
        <button type="submit" class="btn btn-primary"><i class="fas fa-search me-1"></i>Buscar</button>
      </form>
      <?php if (isset($busqueda) && !$busqueda): ?>
      <div class="alert alert-info mt-3 mb-0 rounded-3"><i class="fas fa-info-circle me-2"></i>No se encontró la hoja de ruta con ese código.</div>
      <?php endif; ?>
      <?php if ($busqueda): ?>
      <div class="row g-3 mt-2">
        <div class="col-md-8">
          <div class="p-3 border rounded-3 bg-light">
            <div class="d-flex align-items-center justify-content-between mb-3">
              <h5 class="mb-0"><code class="text-primary"><?= e($busqueda['codigo']) ?></code></h5>
              <?= estadoBadge($busqueda['estado']) ?>
            </div>
            <div class="row g-2 small">
              <div class="col-6"><strong>Modalidad:</strong> <?= $busqueda['modalidad']==='nuevo_rai'?'Nuevo RAI':'RAI Asignado' ?></div>
              <div class="col-6"><strong>Prioridad:</strong> <?= prioridadBadge($busqueda['prioridad']) ?></div>
              <div class="col-6"><strong>Ingresado:</strong> <?= fdate($busqueda['fecha_ingreso']) ?></div>
              <div class="col-6"><strong>Técnico:</strong> <?= $busqueda['tecnico_nombre']?e($busqueda['tecnico_nombre'].' '.$busqueda['tecnico_apellido']):'Sin asignar' ?></div>
              <div class="col-6"><strong>Fecha Límite:</strong> <?= $busqueda['fecha_limite']?fdate($busqueda['fecha_limite'],'d/m/Y'):'—' ?></div>
              <div class="col-6"><strong>Documentos:</strong> <?= $busqueda['total_docs'] ?> archivos</div>
            </div>
            <?php if ($busqueda['observaciones_tecnico']): ?>
            <div class="alert alert-warning mt-2 mb-0 py-2 small"><strong>Obs. Técnico:</strong> <?= e($busqueda['observaciones_tecnico']) ?></div>
            <?php endif; ?>
          </div>
        </div>
        <div class="col-md-4">
          <div class="d-flex flex-column gap-2">
            <a href="/SMIA2/modules/consultor/subir_documentos.php?hoja=<?= $busqueda['id'] ?>" class="btn btn-primary"><i class="fas fa-upload me-1"></i>Subir Documentos</a>
            <a href="/SMIA2/modules/consultor/formulario_rai.php?hoja=<?= $busqueda['id'] ?>" class="btn btn-outline-primary"><i class="fas fa-wpforms me-1"></i>Ver Formulario RAI</a>
          </div>
        </div>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- Stats Row -->
<div class="row g-3 mb-4">
  <?php
  $cards = [
    ['icon'=>'fas fa-folder','color'=>'#bf360c','label'=>'Total Trámites','val'=>$mis['total']??0,'bg'=>'#fbe9e7'],
    ['icon'=>'fas fa-hourglass-start','color'=>'#1a237e','label'=>'Ingresados','val'=>$mis['ingresados']??0,'bg'=>'#e8eaf6'],
    ['icon'=>'fas fa-user-check','color'=>'#0288d1','label'=>'Asignados','val'=>$mis['asignados']??0,'bg'=>'#e1f5fe'],
    ['icon'=>'fas fa-search','color'=>'#f57c00','label'=>'En Revisión','val'=>$mis['en_revision']??0,'bg'=>'#fff3e0'],
    ['icon'=>'fas fa-exclamation','color'=>'#c62828','label'=>'Observados','val'=>$mis['observados']??0,'bg'=>'#ffebee'],
    ['icon'=>'fas fa-flag-checkered','color'=>'#2e7d32','label'=>'Finalizados','val'=>$mis['finalizados']??0,'bg'=>'#e8f5e9'],
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
    <h6 class="fw-bold text-muted mb-3">ACCIONES RÁPIDAS</h6>
    <div class="d-flex flex-wrap gap-2">
      <a href="/SMIA2/modules/consultor/nueva_hoja_ruta.php" class="btn btn-danger px-4 py-2 fw-bold">
        <i class="fas fa-plus-circle me-2"></i>Nueva Hoja de Ruta
      </a>
      <a href="/SMIA2/modules/consultor/formulario_rai.php" class="btn btn-outline-danger px-4 py-2">
        <i class="fas fa-wpforms me-2"></i>Completar Formulario RAI
      </a>
      <a href="/SMIA2/modules/consultor/subir_documentos.php" class="btn btn-outline-primary px-4 py-2">
        <i class="fas fa-upload me-2"></i>Subir Documentos
      </a>
      <a href="/SMIA2/modules/consultor/mis_tramites.php" class="btn btn-outline-secondary px-4 py-2">
        <i class="fas fa-list me-2"></i>Ver todos mis trámites
      </a>
    </div>
  </div>
</div>

<!-- Mis Hojas de Ruta -->
<div class="table-card">
  <div class="table-header">
    <h6 class="fw-bold mb-0"><i class="fas fa-route me-2 text-danger"></i>Mis Hojas de Ruta</h6>
    <a href="/SMIA2/modules/consultor/nueva_hoja_ruta.php" class="btn btn-sm btn-danger">
      <i class="fas fa-plus me-1"></i>Nueva
    </a>
  </div>
  <div class="table-responsive">
    <table class="table table-hover mb-0">
      <thead><tr>
        <th>Código HDR</th><th>Modalidad</th><th>Estado</th><th>Técnico</th>
        <th>Docs</th><th>Fecha Ingreso</th><th>Fecha Límite</th><th>Acciones</th>
      </tr></thead>
      <tbody>
      <?php if (empty($hojas)): ?>
      <tr><td colspan="8" class="text-center py-5 text-muted">
        <i class="fas fa-folder-open fa-3x mb-3 d-block"></i>
        No tiene trámites registrados.<br>
        <a href="/SMIA2/modules/consultor/nueva_hoja_ruta.php" class="btn btn-danger mt-2">Iniciar primer trámite</a>
      </td></tr>
      <?php endif; ?>
      <?php foreach ($hojas as $h):
        $dias = diasRestantes($h['fecha_limite']);
        $diasClass = $dias !== null && $dias <= 3 ? 'text-danger fw-bold' : ($dias !== null && $dias <= 7 ? 'text-warning fw-bold' : '');
      ?>
      <tr>
        <td><code class="text-danger fw-bold"><?= e($h['codigo']) ?></code></td>
        <td><?= $h['modalidad']==='nuevo_rai' ? '<span class="badge bg-danger">Nuevo RAI</span>' : '<span class="badge bg-secondary">RAI Asignado</span>' ?></td>
        <td><?= estadoBadge($h['estado']) ?></td>
        <td class="small"><?= $h['tecnico_nombre'] ? e($h['tecnico_nombre'].' '.$h['tecnico_apellido']) : '<span class="text-muted">Pendiente</span>' ?></td>
        <td class="text-center">
          <span class="badge <?= $h['docs_pendientes']>0?'bg-warning text-dark':'bg-success' ?>"><?= $h['total_docs'] ?> doc.</span>
        </td>
        <td class="small"><?= fdate($h['fecha_ingreso'],'d/m/Y') ?></td>
        <td class="<?= $diasClass ?> small">
          <?= $h['fecha_limite'] ? fdate($h['fecha_limite'],'d/m/Y').($dias!==null?" ($dias d.)":'') : '—' ?>
        </td>
        <td>
          <div class="d-flex gap-1">
            <a href="/SMIA2/modules/consultor/formulario_rai.php?hoja=<?= $h['id'] ?>" class="btn btn-xs btn-outline-primary" style="font-size:.72rem;padding:.2rem .5rem" title="RAI"><i class="fas fa-wpforms"></i></a>
            <a href="/SMIA2/modules/consultor/subir_documentos.php?hoja=<?= $h['id'] ?>" class="btn btn-xs btn-outline-warning" style="font-size:.72rem;padding:.2rem .5rem" title="Documentos"><i class="fas fa-upload"></i></a>
            <a href="/SMIA2/modules/consultor/mis_tramites.php?id=<?= $h['id'] ?>" class="btn btn-xs btn-outline-secondary" style="font-size:.72rem;padding:.2rem .5rem" title="Ver"><i class="fas fa-eye"></i></a>
          </div>
        </td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
