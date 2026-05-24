<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../includes/functions.php';
requireRole('consultor');

$uid       = currentUserId();
$pageTitle = 'Mis Trámites';

$filtroEstado = sanitize($_GET['estado'] ?? '');

$where = "WHERE hdr.consultor_id=$uid";
if ($filtroEstado) $where .= " AND hdr.estado=" . db()->quote($filtroEstado);

$hojas = db()->query("
    SELECT hdr.*,
           CONCAT(ut.nombre,' ',ut.apellido) tecnico,
           rai.razon_social, rai.categoria_rai, rai.estado_formulario,
           (SELECT COUNT(*) FROM documentos d WHERE d.hoja_ruta_id=hdr.id) total_docs,
           (SELECT COUNT(*) FROM documentos d WHERE d.hoja_ruta_id=hdr.id AND d.estado='rechazado') docs_rechazados
    FROM hojas_de_ruta hdr
    LEFT JOIN usuarios ut ON hdr.tecnico_id=ut.id
    LEFT JOIN formularios_rai rai ON rai.hoja_ruta_id=hdr.id
    $where ORDER BY hdr.fecha_ingreso DESC
")->fetchAll();

// Ver detalle
$detalle = null;
if (!empty($_GET['id'])) {
    $s = db()->prepare("SELECT hdr.*, CONCAT(ut.nombre,' ',ut.apellido) tecnico FROM hojas_de_ruta hdr LEFT JOIN usuarios ut ON hdr.tecnico_id=ut.id WHERE hdr.id=? AND hdr.consultor_id=?");
    $s->execute([(int)$_GET['id'], $uid]);
    $detalle = $s->fetch();
}

require_once __DIR__ . '/../../includes/dashboard_header.php';
?>

<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
  <h5 class="fw-bold mb-0"><i class="fas fa-list-alt me-2 text-danger"></i>Mis Trámites RAI</h5>
  <div class="d-flex gap-2 flex-wrap">
    <a href="?estado=" class="btn btn-sm <?= !$filtroEstado?'btn-danger':'btn-outline-secondary' ?>">Todos</a>
    <?php foreach (['ingresado','asignado','en_revision','observado','aprobado','finalizado','rechazado'] as $est): ?>
    <a href="?estado=<?= $est ?>" class="btn btn-sm <?= $filtroEstado===$est?'btn-primary':'btn-outline-secondary' ?>">
      <?= ucfirst(str_replace('_',' ',$est)) ?>
    </a>
    <?php endforeach; ?>
    <a href="/SMIA2/modules/consultor/nueva_hoja_ruta.php" class="btn btn-sm btn-danger"><i class="fas fa-plus me-1"></i>Nueva</a>
  </div>
</div>

<?php if ($detalle): ?>
<!-- Panel de detalle -->
<div class="table-card p-4 mb-4">
  <div class="d-flex align-items-center justify-content-between mb-3">
    <h5 class="mb-0"><i class="fas fa-route me-2"></i><code class="text-danger"><?= e($detalle['codigo']) ?></code></h5>
    <a href="?" class="btn btn-sm btn-outline-secondary"><i class="fas fa-times me-1"></i>Cerrar</a>
  </div>
  <div class="row g-3 mb-3">
    <div class="col-md-3"><strong class="small text-muted d-block">Estado</strong><?= estadoBadge($detalle['estado']) ?></div>
    <div class="col-md-3"><strong class="small text-muted d-block">Modalidad</strong><?= $detalle['modalidad']==='nuevo_rai'?'Nuevo RAI':'RAI Asignado' ?></div>
    <div class="col-md-3"><strong class="small text-muted d-block">Prioridad</strong><?= prioridadBadge($detalle['prioridad']) ?></div>
    <div class="col-md-3"><strong class="small text-muted d-block">Técnico</strong><?= $detalle['tecnico']?e($detalle['tecnico']):'Sin asignar' ?></div>
    <div class="col-md-3"><strong class="small text-muted d-block">Ingresado</strong><?= fdate($detalle['fecha_ingreso']) ?></div>
    <div class="col-md-3"><strong class="small text-muted d-block">Fecha Límite</strong><?= $detalle['fecha_limite']?fdate($detalle['fecha_limite'],'d/m/Y'):'—' ?></div>
    <div class="col-md-6"><strong class="small text-muted d-block">Obs. Técnico</strong><?= $detalle['observaciones_tecnico']?e($detalle['observaciones_tecnico']):'—' ?></div>
  </div>

  <!-- Historial -->
  <h6 class="fw-bold mt-3 mb-2">Historial de Estados</h6>
  <?php $hist = getHistorial($detalle['id']); ?>
  <div class="timeline">
    <?php foreach ($hist as $h): ?>
    <div class="d-flex gap-3 mb-3">
      <div class="timeline-dot" style="background:<?= $h['color_primary'] ?>"></div>
      <div>
        <div class="fw-semibold small">
          <?= $h['estado_anterior']?estadoBadge($h['estado_anterior']).'<i class="fas fa-arrow-right mx-1 text-muted small"></i>':'' ?>
          <?= estadoBadge($h['estado_nuevo']) ?>
        </div>
        <div class="text-muted" style="font-size:.78rem"><?= e($h['nombre'].' '.$h['apellido']) ?> · <?= $h['rol_nombre'] ?> · <?= fdate($h['fecha']) ?></div>
        <?php if ($h['observaciones']): ?><div class="small text-muted mt-1"><?= e($h['observaciones']) ?></div><?php endif; ?>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- Documentos -->
  <h6 class="fw-bold mt-3 mb-2">Documentos</h6>
  <?php
  $docsDet = db()->prepare("SELECT d.*,td.nombre tipo_nombre FROM documentos d LEFT JOIN tipos_documento td ON d.tipo_documento_id=td.id WHERE d.hoja_ruta_id=? ORDER BY d.fecha_subida");
  $docsDet->execute([$detalle['id']]);
  $docsDet = $docsDet->fetchAll();
  ?>
  <?php if (empty($docsDet)): ?><p class="text-muted small">Sin documentos cargados.</p><?php endif; ?>
  <div class="row g-2">
    <?php foreach ($docsDet as $d): $stBadge = ['pendiente'=>'secondary','aprobado'=>'success','rechazado'=>'danger','observado'=>'warning text-dark'][$d['estado']]??'secondary'; ?>
    <div class="col-md-6">
      <div class="d-flex align-items-center gap-2 p-2 border rounded-3 small">
        <i class="fas fa-file-pdf text-danger"></i>
        <div class="flex-1 min-w-0">
          <div class="text-truncate fw-semibold"><?= e($d['nombre_descripcion']) ?></div>
          <div class="text-muted" style="font-size:.72rem"><?= e($d['tipo_nombre']??'—') ?></div>
        </div>
        <span class="badge bg-<?= $stBadge ?> flex-shrink-0"><?= ucfirst($d['estado']) ?></span>
        <a href="/SMIA2/<?= $d['archivo_ruta'] ?>" target="_blank" class="btn btn-xs btn-outline-primary flex-shrink-0" style="font-size:.72rem;padding:.15rem .4rem"><i class="fas fa-eye"></i></a>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <div class="mt-3 d-flex gap-2">
    <a href="/SMIA2/modules/consultor/formulario_rai.php?hoja=<?= $detalle['id'] ?>" class="btn btn-outline-primary btn-sm"><i class="fas fa-wpforms me-1"></i>Formulario RAI</a>
    <a href="/SMIA2/modules/consultor/subir_documentos.php?hoja=<?= $detalle['id'] ?>" class="btn btn-outline-warning btn-sm"><i class="fas fa-upload me-1"></i>Subir Docs</a>
  </div>
</div>
<?php endif; ?>

<!-- Tabla de trámites -->
<div class="table-card">
  <div class="table-responsive">
    <table class="table table-hover mb-0">
      <thead><tr>
        <th>Código HDR</th><th>Empresa</th><th>Cat.</th><th>Modalidad</th><th>Prioridad</th>
        <th>Estado</th><th>Form. RAI</th><th>Técnico</th><th>Docs</th>
        <th>Ingreso</th><th>Límite</th><th>Acciones</th>
      </tr></thead>
      <tbody>
      <?php if (empty($hojas)): ?>
      <tr><td colspan="12" class="text-center py-5 text-muted">
        <i class="fas fa-folder-open fa-3x mb-3 d-block"></i>
        No tiene trámites<?= $filtroEstado?" con estado '$filtroEstado'":'registrados' ?>
      </td></tr>
      <?php endif; ?>
      <?php foreach ($hojas as $h):
        $dias = diasRestantes($h['fecha_limite']);
        $diasClass = $dias!==null && $dias<=3?'text-danger fw-bold':($dias!==null&&$dias<=7?'text-warning fw-bold':'');
      ?>
      <tr class="<?= ($h['estado']==='observado'||$h['docs_rechazados']>0)?'table-warning':'' ?>">
        <td><code class="text-danger fw-bold small"><?= e($h['codigo']) ?></code></td>
        <td class="small"><?= $h['razon_social']?e($h['razon_social']):'<span class="text-muted">Sin RAI</span>' ?></td>
        <td><?php if ($h['categoria_rai']): $catC=[1=>'danger',2=>'warning',3=>'success',4=>'info'][$h['categoria_rai']]??'secondary'; ?><span class="badge bg-<?= $catC ?>">Cat. <?= $h['categoria_rai'] ?></span><?php endif; ?></td>
        <td><span class="badge <?= $h['modalidad']==='nuevo_rai'?'bg-danger':'bg-secondary' ?>"><?= $h['modalidad']==='nuevo_rai'?'Nuevo':'Asignado' ?></span></td>
        <td><?= prioridadBadge($h['prioridad']) ?></td>
        <td><?= estadoBadge($h['estado']) ?></td>
        <td><?= $h['estado_formulario']?estadoBadge($h['estado_formulario']):'<span class="text-muted small">—</span>' ?></td>
        <td class="small"><?= $h['tecnico']?e($h['tecnico']):'<span class="text-muted">—</span>' ?></td>
        <td class="text-center">
          <?php if ($h['docs_rechazados']>0): ?><span class="badge bg-danger"><?= $h['docs_rechazados'] ?> rech.</span>
          <?php else: ?><span class="badge bg-light text-dark"><?= $h['total_docs'] ?></span><?php endif; ?>
        </td>
        <td class="small"><?= fdate($h['fecha_ingreso'],'d/m/Y') ?></td>
        <td class="<?= $diasClass ?> small"><?= $h['fecha_limite']?fdate($h['fecha_limite'],'d/m/Y').($dias!==null?" ({$dias}d)":''):'—' ?></td>
        <td>
          <div class="d-flex gap-1">
            <a href="?id=<?= $h['id'] ?>" class="btn btn-xs btn-outline-secondary" style="font-size:.72rem;padding:.2rem .5rem"><i class="fas fa-eye"></i></a>
            <a href="/SMIA2/modules/consultor/formulario_rai.php?hoja=<?= $h['id'] ?>" class="btn btn-xs btn-outline-primary" style="font-size:.72rem;padding:.2rem .5rem"><i class="fas fa-wpforms"></i></a>
            <a href="/SMIA2/modules/consultor/subir_documentos.php?hoja=<?= $h['id'] ?>" class="btn btn-xs btn-outline-warning" style="font-size:.72rem;padding:.2rem .5rem"><i class="fas fa-upload"></i></a>
          </div>
        </td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<style>
.timeline-dot{width:12px;height:12px;border-radius:50%;flex-shrink:0;margin-top:.3rem}
</style>

<?php require_once __DIR__ . '/../../includes/dashboard_footer.php'; ?>
