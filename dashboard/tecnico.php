<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole('tecnico');

$uid       = currentUserId();
$pageTitle = 'Dashboard Técnico';
updateKPI($uid);

// Mis estadísticas
$mis = db()->prepare("
    SELECT COUNT(*) total,
           SUM(estado='en_revision') en_revision, SUM(estado='observado') observados,
           SUM(estado='aprobado') aprobados, SUM(estado='finalizado') finalizados,
           SUM(estado='rechazado') rechazados,
           SUM(estado NOT IN ('finalizado','rechazado')) pendientes
    FROM hojas_de_ruta WHERE tecnico_id=?
");
$mis->execute([$uid]);
$mis = $mis->fetch();

// Mis trámites activos
$activos = db()->prepare("
    SELECT hdr.*, CONCAT(u.nombre,' ',u.apellido) consultor
    FROM hojas_de_ruta hdr JOIN usuarios u ON hdr.consultor_id=u.id
    WHERE hdr.tecnico_id=? AND hdr.estado NOT IN ('finalizado','rechazado')
    ORDER BY FIELD(hdr.prioridad,'urgente','alta','media','baja'), hdr.fecha_limite ASC LIMIT 8
");
$activos->execute([$uid]);
$activos = $activos->fetchAll();

// Mis turnos próximos
$turnos = db()->prepare("
    SELECT * FROM turnos WHERE tecnico_id=? AND fecha >= CURDATE()
    ORDER BY fecha, hora_inicio LIMIT 5
");
$turnos->execute([$uid]);
$turnos = $turnos->fetchAll();

// Recordatorios del día
$hoy = db()->prepare("
    SELECT * FROM recordatorios
    WHERE usuario_id=? AND DATE(fecha_recordatorio)=CURDATE() AND completado=0
");
$hoy->execute([$uid]);
$hoy = $hoy->fetchAll();

// Mis documentos pendientes de revisión
$docsPend = db()->prepare("
    SELECT d.*, hdr.codigo, CONCAT(u.nombre,' ',u.apellido) consultor
    FROM documentos d
    JOIN hojas_de_ruta hdr ON d.hoja_ruta_id=hdr.id
    JOIN usuarios u ON hdr.consultor_id=u.id
    WHERE hdr.tecnico_id=? AND d.estado='pendiente'
    ORDER BY d.fecha_subida DESC LIMIT 6
");
$docsPend->execute([$uid]);
$docsPend = $docsPend->fetchAll();

// KPI del mes actual
$kpi = db()->prepare("SELECT * FROM kpi_tecnicos WHERE tecnico_id=? AND periodo_mes=MONTH(NOW()) AND periodo_anio=YEAR(NOW())");
$kpi->execute([$uid]);
$kpi = $kpi->fetch();

require_once __DIR__ . '/../includes/dashboard_header.php';
?>

<!-- Stats Row -->
<div class="row g-3 mb-4">
  <?php
  $cards = [
    ['icon'=>'fas fa-folder-open','color'=>'#1a237e','label'=>'Total Asignados','val'=>$mis['total']??0,'bg'=>'#e8eaf6'],
    ['icon'=>'fas fa-search','color'=>'#7b1fa2','label'=>'En Revisión','val'=>$mis['en_revision']??0,'bg'=>'#f3e5f5'],
    ['icon'=>'fas fa-exclamation','color'=>'#f57c00','label'=>'Observados','val'=>$mis['observados']??0,'bg'=>'#fff3e0'],
    ['icon'=>'fas fa-check-circle','color'=>'#2e7d32','label'=>'Aprobados','val'=>$mis['aprobados']??0,'bg'=>'#e8f5e9'],
    ['icon'=>'fas fa-flag-checkered','color'=>'#263238','label'=>'Finalizados','val'=>$mis['finalizados']??0,'bg'=>'#eceff1'],
    ['icon'=>'fas fa-hourglass-half','color'=>'#ff6f00','label'=>'Pendientes','val'=>$mis['pendientes']??0,'bg'=>'#fff8e1'],
  ];
  foreach ($cards as $c): ?>
  <div class="col-md-4 col-lg-2">
    <div class="stat-card" style="border-left-color:<?= $c['color'] ?>">
      <div class="stat-icon" style="background:<?= $c['bg'] ?>;color:<?= $c['color'] ?>"><i class="<?= $c['icon'] ?>"></i></div>
      <div class="stat-num"><?= $c['val'] ?></div>
      <div class="stat-label"><?= $c['label'] ?></div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<div class="row g-4 mb-4">
  <!-- KPI del mes -->
  <div class="col-lg-4">
    <div class="table-card p-3 h-100">
      <h6 class="fw-bold mb-3"><i class="fas fa-trophy me-2 text-warning"></i>Mi KPI – <?= date('F Y') ?></h6>
      <?php if ($kpi): ?>
      <div class="mb-3">
        <div class="d-flex justify-content-between mb-1"><span class="small">Eficiencia General</span><strong><?= $kpi['porcentaje_eficiencia'] ?>%</strong></div>
        <div class="progress" style="height:10px;border-radius:5px">
          <?php $ef = $kpi['porcentaje_eficiencia']; $efC = $ef>=80?'bg-success':($ef>=50?'bg-warning':'bg-danger'); ?>
          <div class="progress-bar <?= $efC ?>" style="width:<?= $ef ?>%"></div>
        </div>
      </div>
      <div class="row g-2 text-center">
        <div class="col-6"><div class="p-2 bg-light rounded"><div class="fw-bold text-success"><?= $kpi['tramites_completados'] ?></div><div class="small text-muted">Completados</div></div></div>
        <div class="col-6"><div class="p-2 bg-light rounded"><div class="fw-bold text-warning"><?= $kpi['tramites_pendientes'] ?></div><div class="small text-muted">Pendientes</div></div></div>
        <div class="col-6"><div class="p-2 bg-light rounded"><div class="fw-bold text-primary"><?= $kpi['documentos_revisados'] ?></div><div class="small text-muted">Docs Revisados</div></div></div>
        <div class="col-6"><div class="p-2 bg-light rounded"><div class="fw-bold text-secondary"><?= $kpi['tiempo_promedio_dias'] ?></div><div class="small text-muted">Días Prom.</div></div></div>
      </div>
      <?php else: ?>
      <div class="text-center text-muted py-4 small">Sin datos este mes</div>
      <?php endif; ?>
      <a href="/SMIA2/modules/tecnico/mis_tramites.php" class="btn btn-success w-100 mt-3 btn-sm">
        <i class="fas fa-folder me-1"></i>Ver mis trámites
      </a>
    </div>
  </div>

  <!-- Sistema de Turnos -->
  <div class="col-lg-4">
    <div class="table-card p-0 h-100">
      <div class="table-header"><h6 class="fw-bold mb-0"><i class="fas fa-calendar-alt me-2 text-primary"></i>Sistema de Turnos</h6>
        <a href="/SMIA2/modules/tecnico/turnos.php" class="btn btn-sm btn-outline-primary">Gestionar</a>
      </div>
      <?php if (empty($turnos)): ?>
      <div class="text-center py-4 text-muted small p-3"><i class="fas fa-calendar-times fa-2x mb-2 d-block"></i>Sin turnos programados</div>
      <?php else: ?>
      <div class="p-3">
        <?php foreach ($turnos as $t): ?>
        <div class="d-flex align-items-center gap-3 mb-3 p-2 bg-light rounded">
          <div class="text-center" style="min-width:48px">
            <div class="fw-bold text-primary" style="font-size:.85rem"><?= date('d',strtotime($t['fecha'])) ?></div>
            <div class="small text-muted"><?= date('M',strtotime($t['fecha'])) ?></div>
          </div>
          <div>
            <div class="fw-semibold small"><?= date('H:i',strtotime($t['hora_inicio'])) ?> – <?= date('H:i',strtotime($t['hora_fin'])) ?></div>
            <div class="text-muted" style="font-size:.75rem">Capacidad: <?= $t['tramites_asignados'] ?>/<?= $t['capacidad_tramites'] ?></div>
          </div>
          <?php $stClass = ['programado'=>'secondary','activo'=>'success','completado'=>'dark','cancelado'=>'danger']; ?>
          <span class="badge bg-<?= $stClass[$t['estado']]??'secondary' ?> ms-auto"><?= ucfirst($t['estado']) ?></span>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Recordatorios del día -->
  <div class="col-lg-4">
    <div class="table-card p-0 h-100">
      <div class="table-header"><h6 class="fw-bold mb-0"><i class="fas fa-bell me-2 text-warning"></i>Recordatorios de Hoy</h6>
        <a href="/SMIA2/modules/tecnico/recordatorios.php" class="btn btn-sm btn-outline-warning">Ver todos</a>
      </div>
      <?php if (empty($hoy)): ?>
      <div class="text-center py-4 text-muted small p-3"><i class="fas fa-check-circle fa-2x mb-2 d-block text-success"></i>Sin recordatorios para hoy</div>
      <?php else: ?>
      <div class="p-3">
        <?php foreach ($hoy as $r): ?>
        <div class="alert alert-warning rounded-3 py-2 px-3 mb-2" role="alert">
          <div class="fw-semibold small"><?= e($r['titulo']) ?></div>
          <?php if ($r['descripcion']): ?><div class="text-muted" style="font-size:.78rem"><?= e($r['descripcion']) ?></div><?php endif; ?>
          <div class="small"><?= date('H:i',strtotime($r['fecha_recordatorio'])) ?></div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- Mis trámites activos -->
<div class="table-card mb-4">
  <div class="table-header">
    <h6 class="fw-bold mb-0"><i class="fas fa-tasks me-2 text-success"></i>Mis Trámites Activos</h6>
    <a href="/SMIA2/modules/tecnico/mis_tramites.php" class="btn btn-sm btn-success">Ver todos</a>
  </div>
  <div class="table-responsive">
    <table class="table table-hover mb-0">
      <thead><tr><th>Código HDR</th><th>Consultor</th><th>Modalidad</th><th>Prioridad</th><th>Estado</th><th>Fecha Límite</th><th>Docs Pendientes</th><th>Acción</th></tr></thead>
      <tbody>
      <?php if (empty($activos)): ?>
      <tr><td colspan="8" class="text-center text-muted py-4">Sin trámites activos asignados</td></tr>
      <?php endif; ?>
      <?php foreach ($activos as $h):
        $docsP = db()->prepare("SELECT COUNT(*) FROM documentos WHERE hoja_ruta_id=? AND estado='pendiente'");
        $docsP->execute([$h['id']]);
        $nDocs = $docsP->fetchColumn();
        $dias  = diasRestantes($h['fecha_limite']);
        $diasClass = $dias !== null && $dias <= 3 ? 'text-danger fw-bold' : ($dias !== null && $dias <= 7 ? 'text-warning fw-bold' : '');
      ?>
      <tr>
        <td><code class="text-success fw-bold"><?= e($h['codigo']) ?></code></td>
        <td class="small"><?= e($h['consultor']) ?></td>
        <td><?= $h['modalidad']==='nuevo_rai' ? '<span class="badge bg-primary">Nuevo</span>' : '<span class="badge bg-secondary">Asignado</span>' ?></td>
        <td><?= prioridadBadge($h['prioridad']) ?></td>
        <td><?= estadoBadge($h['estado']) ?></td>
        <td class="<?= $diasClass ?> small">
          <?= $h['fecha_limite'] ? fdate($h['fecha_limite'],'d/m/Y').($dias!==null?" ($dias d.)":'') : '—' ?>
        </td>
        <td class="text-center">
          <?php if ($nDocs > 0): ?>
          <span class="badge bg-warning text-dark"><?= $nDocs ?> doc.</span>
          <?php else: ?><span class="text-muted small">—</span><?php endif; ?>
        </td>
        <td>
          <a href="/SMIA2/modules/tecnico/mis_tramites.php?id=<?= $h['id'] ?>" class="btn btn-sm btn-outline-success">Revisar</a>
        </td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Documentos pendientes de revisión -->
<?php if (!empty($docsPend)): ?>
<div class="table-card">
  <div class="table-header">
    <h6 class="fw-bold mb-0"><i class="fas fa-file-alt me-2 text-warning"></i>Documentos Pendientes de Revisión</h6>
    <a href="/SMIA2/modules/tecnico/revisar_doc.php" class="btn btn-sm btn-warning text-dark">Ver todos</a>
  </div>
  <div class="table-responsive">
    <table class="table table-hover mb-0">
      <thead><tr><th>Código HDR</th><th>Documento</th><th>Consultor</th><th>Subido</th><th>Acción</th></tr></thead>
      <tbody>
      <?php foreach ($docsPend as $d): ?>
      <tr>
        <td><code class="small"><?= e($d['codigo']) ?></code></td>
        <td class="small"><?= e($d['nombre_descripcion']) ?></td>
        <td class="small"><?= e($d['consultor']) ?></td>
        <td class="small"><?= fdate($d['fecha_subida'],'d/m/Y H:i') ?></td>
        <td><a href="/SMIA2/modules/tecnico/revisar_doc.php?doc=<?= $d['id'] ?>" class="btn btn-xs btn-outline-warning" style="font-size:.75rem;padding:.2rem .6rem">Revisar</a></td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
