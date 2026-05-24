<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../includes/functions.php';
requireRole('director');

$uid       = currentUserId();
$pageTitle = 'Gestión de Hojas de Ruta';
$filtroEstado = sanitize($_GET['estado'] ?? '');
$buscar       = sanitize($_GET['q'] ?? '');

$where = "WHERE 1=1";
if ($filtroEstado) $where .= " AND hdr.estado='$filtroEstado'";
if ($buscar) $where .= " AND (hdr.codigo LIKE '%$buscar%' OR uc.nombre LIKE '%$buscar%' OR uc.apellido LIKE '%$buscar%')";

$hojas = db()->query("
    SELECT hdr.*, CONCAT(uc.nombre,' ',uc.apellido) consultor,
           CONCAT(ut.nombre,' ',ut.apellido) tecnico,
           rai.razon_social, rai.categoria_rai,
           (SELECT COUNT(*) FROM documentos d WHERE d.hoja_ruta_id=hdr.id) total_docs
    FROM hojas_de_ruta hdr
    JOIN usuarios uc ON hdr.consultor_id=uc.id
    LEFT JOIN usuarios ut ON hdr.tecnico_id=ut.id
    LEFT JOIN formularios_rai rai ON rai.hoja_ruta_id=hdr.id
    $where ORDER BY hdr.fecha_ingreso DESC LIMIT 100
")->fetchAll();

// Ver detalle de una hoja
$ver = null;
if (!empty($_GET['ver'])) {
    $sv = db()->prepare("
        SELECT hdr.*, CONCAT(uc.nombre,' ',uc.apellido) consultor, CONCAT(ut.nombre,' ',ut.apellido) tecnico
        FROM hojas_de_ruta hdr
        JOIN usuarios uc ON hdr.consultor_id=uc.id
        LEFT JOIN usuarios ut ON hdr.tecnico_id=ut.id
        WHERE hdr.id=?
    ");
    $sv->execute([(int)$_GET['ver']]);
    $ver = $sv->fetch();
}

require_once __DIR__ . '/../../includes/dashboard_header.php';
?>

<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
  <h5 class="fw-bold mb-0"><i class="fas fa-route me-2"></i>Todas las Hojas de Ruta</h5>
  <div class="d-flex gap-2">
    <a href="/SMIA2/modules/director/asignar.php" class="btn btn-sm btn-warning text-dark fw-bold">
      <i class="fas fa-user-check me-1"></i>Asignar Pendientes
    </a>
  </div>
</div>

<!-- Filtros -->
<div class="table-card p-3 mb-4">
  <form method="GET" class="d-flex gap-2 flex-wrap align-items-end">
    <div>
      <label class="form-label small fw-semibold mb-1">Buscar</label>
      <input name="q" class="form-control form-control-sm" placeholder="Código o consultor..." value="<?= e($buscar) ?>" style="width:200px">
    </div>
    <div>
      <label class="form-label small fw-semibold mb-1">Estado</label>
      <select name="estado" class="form-select form-select-sm" style="width:160px">
        <option value="">Todos</option>
        <?php foreach (['ingresado','asignado','en_revision','observado','aprobado','rechazado','finalizado'] as $est): ?>
        <option value="<?= $est ?>" <?= $filtroEstado===$est?'selected':'' ?>><?= ucfirst(str_replace('_',' ',$est)) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <button type="submit" class="btn btn-primary btn-sm">Filtrar</button>
    <a href="?" class="btn btn-outline-secondary btn-sm">Limpiar</a>
  </form>
</div>

<?php if ($ver): ?>
<!-- DETALLE -->
<div class="table-card p-4 mb-4">
  <div class="d-flex justify-content-between mb-3">
    <h5 class="mb-0"><code class="text-primary"><?= e($ver['codigo']) ?></code> <?= estadoBadge($ver['estado']) ?></h5>
    <a href="?" class="btn btn-sm btn-outline-secondary"><i class="fas fa-times"></i></a>
  </div>
  <div class="row g-3 mb-3">
    <div class="col-md-3"><strong class="text-muted d-block small">Consultor</strong><?= e($ver['consultor']) ?></div>
    <div class="col-md-3"><strong class="text-muted d-block small">Técnico</strong><?= $ver['tecnico']?e($ver['tecnico']):'Sin asignar' ?></div>
    <div class="col-md-3"><strong class="text-muted d-block small">Modalidad</strong><?= $ver['modalidad']==='nuevo_rai'?'Nuevo RAI':'RAI Asignado' ?></div>
    <div class="col-md-3"><strong class="text-muted d-block small">Prioridad</strong><?= prioridadBadge($ver['prioridad']) ?></div>
    <div class="col-md-3"><strong class="text-muted d-block small">Ingresado</strong><?= fdate($ver['fecha_ingreso']) ?></div>
    <div class="col-md-3"><strong class="text-muted d-block small">Asignado</strong><?= fdate($ver['fecha_asignacion']) ?></div>
    <div class="col-md-3"><strong class="text-muted d-block small">Fecha Límite</strong><?= $ver['fecha_limite']?fdate($ver['fecha_limite'],'d/m/Y'):'—' ?></div>
    <div class="col-md-3"><strong class="text-muted d-block small">Finalizado</strong><?= fdate($ver['fecha_finalizacion']) ?></div>
    <?php if ($ver['observaciones_director']): ?><div class="col-12"><strong class="text-muted d-block small">Obs. Director</strong><?= e($ver['observaciones_director']) ?></div><?php endif; ?>
    <?php if ($ver['observaciones_tecnico']): ?><div class="col-12"><strong class="text-muted d-block small">Obs. Técnico</strong><?= e($ver['observaciones_tecnico']) ?></div><?php endif; ?>
  </div>
  <!-- Historial -->
  <h6 class="fw-bold">Historial de Estados</h6>
  <?php foreach (getHistorial($ver['id']) as $h): ?>
  <div class="d-flex gap-2 mb-2 small">
    <i class="fas fa-circle-dot" style="color:<?= $h['color_primary'] ?>;margin-top:2px"></i>
    <div>
      <?= $h['estado_anterior']?estadoBadge($h['estado_anterior']).' → ':'' ?><?= estadoBadge($h['estado_nuevo']) ?>
      <span class="text-muted ms-2"><?= e($h['nombre'].' '.$h['apellido']) ?> · <?= fdate($h['fecha']) ?></span>
      <?php if ($h['observaciones']): ?><div class="text-muted"><?= e($h['observaciones']) ?></div><?php endif; ?>
    </div>
  </div>
  <?php endforeach; ?>
  <div class="mt-3 d-flex gap-2">
    <?php if (in_array($ver['estado'],['ingresado','asignado'])): ?>
    <a href="/SMIA2/modules/director/asignar.php?id=<?= $ver['id'] ?>" class="btn btn-sm btn-warning text-dark">Asignar/Reasignar</a>
    <?php endif; ?>
  </div>
</div>
<?php endif; ?>

<!-- TABLA -->
<div class="table-card">
  <div class="table-header">
    <span class="fw-semibold text-muted small"><?= count($hojas) ?> resultado(s)</span>
  </div>
  <div class="table-responsive">
    <table class="table table-hover mb-0">
      <thead><tr>
        <th>Código HDR</th><th>Empresa</th><th>Cat.</th><th>Consultor</th>
        <th>Técnico</th><th>Estado</th><th>Prioridad</th>
        <th>Ingreso</th><th>Límite</th><th>Docs</th><th>Acciones</th>
      </tr></thead>
      <tbody>
      <?php if (empty($hojas)): ?>
      <tr><td colspan="11" class="text-center text-muted py-4">Sin resultados</td></tr>
      <?php endif; ?>
      <?php foreach ($hojas as $h):
        $dias = diasRestantes($h['fecha_limite']);
        $rowClass = ($dias!==null && $dias<=3 && !in_array($h['estado'],['finalizado','rechazado'])) ? 'table-danger' : '';
      ?>
      <tr class="<?= $rowClass ?>">
        <td><code class="text-primary fw-bold small"><?= e($h['codigo']) ?></code></td>
        <td class="small"><?= $h['razon_social']?e($h['razon_social']):'—' ?></td>
        <td><?php if ($h['categoria_rai']): ?><span class="badge bg-<?= ['A'=>'danger','B'=>'warning','C'=>'success'][$h['categoria_rai']]??'secondary' ?>">Cat.<?= $h['categoria_rai'] ?></span><?php endif; ?></td>
        <td class="small"><?= e($h['consultor']) ?></td>
        <td class="small"><?= $h['tecnico']?e($h['tecnico']):'<span class="text-warning fw-bold">Sin asignar</span>' ?></td>
        <td><?= estadoBadge($h['estado']) ?></td>
        <td><?= prioridadBadge($h['prioridad']) ?></td>
        <td class="small"><?= fdate($h['fecha_ingreso'],'d/m/Y') ?></td>
        <td class="small <?= $dias!==null&&$dias<=3?'text-danger fw-bold':($dias!==null&&$dias<=7?'text-warning fw-bold':'') ?>">
          <?= $h['fecha_limite']?fdate($h['fecha_limite'],'d/m/Y').($dias!==null?" ({$dias}d)":''):'—' ?>
        </td>
        <td class="text-center"><span class="badge bg-light text-dark"><?= $h['total_docs'] ?></span></td>
        <td>
          <div class="d-flex gap-1">
            <a href="?ver=<?= $h['id'] ?>" class="btn btn-xs btn-outline-secondary" style="font-size:.72rem;padding:.2rem .5rem"><i class="fas fa-eye"></i></a>
            <?php if ($h['estado']==='ingresado'): ?>
            <a href="/SMIA2/modules/director/asignar.php?id=<?= $h['id'] ?>" class="btn btn-xs btn-warning" style="font-size:.72rem;padding:.2rem .5rem">Asignar</a>
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
