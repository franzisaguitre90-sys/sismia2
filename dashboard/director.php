<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole('director');

$uid      = currentUserId();
$pageTitle = 'Dashboard Director';

// Estadísticas globales
$stats = db()->query("
    SELECT
      COUNT(*) total,
      SUM(estado='ingresado') ingresados,
      SUM(estado='asignado') asignados,
      SUM(estado='en_revision') en_revision,
      SUM(estado='aprobado') aprobados,
      SUM(estado='rechazado') rechazados,
      SUM(estado='finalizado') finalizados,
      SUM(estado='observado') observados
    FROM hojas_de_ruta
")->fetch();

// Últimas hojas de ruta
$ultimas = db()->query("
    SELECT hdr.*, CONCAT(u.nombre,' ',u.apellido) consultor, ut.nombre tecnico_nombre, ut.apellido tecnico_apellido
    FROM hojas_de_ruta hdr
    JOIN usuarios u ON hdr.consultor_id=u.id
    LEFT JOIN usuarios ut ON hdr.tecnico_id=ut.id
    ORDER BY hdr.fecha_ingreso DESC LIMIT 8
")->fetchAll();

// Pendientes de asignación
$pendientes = db()->query("
    SELECT hdr.*, CONCAT(u.nombre,' ',u.apellido) consultor
    FROM hojas_de_ruta hdr JOIN usuarios u ON hdr.consultor_id=u.id
    WHERE hdr.estado='ingresado' ORDER BY hdr.fecha_ingreso ASC LIMIT 5
")->fetchAll();

// KPI por técnico
$kpiTecnicos = db()->query("
    SELECT u.id, CONCAT(u.nombre,' ',u.apellido) tecnico,
           COUNT(h.id) total,
           SUM(h.estado='finalizado') completados,
           SUM(h.estado NOT IN ('finalizado','rechazado')) pendientes,
           ROUND(AVG(DATEDIFF(IFNULL(h.fecha_finalizacion,NOW()),h.fecha_asignacion)),1) dias_prom
    FROM usuarios u
    JOIN roles r ON u.rol_id=r.id
    LEFT JOIN hojas_de_ruta h ON h.tecnico_id=u.id
    WHERE r.slug='tecnico' AND u.activo=1
    GROUP BY u.id ORDER BY completados DESC
")->fetchAll();

// Datos para gráfico mensual
$porMes = db()->query("
    SELECT DATE_FORMAT(fecha_ingreso,'%b %Y') mes, COUNT(*) total,
           SUM(estado='finalizado') finalizados
    FROM hojas_de_ruta
    WHERE fecha_ingreso >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(fecha_ingreso,'%Y-%m') ORDER BY fecha_ingreso
")->fetchAll();

require_once __DIR__ . '/../includes/dashboard_header.php';
?>

<!-- Stats Row -->
<div class="row g-3 mb-4">
  <?php
  $cards = [
    ['icon'=>'fas fa-inbox','color'=>'#1a237e','label'=>'Total Trámites','val'=>$stats['total']??0,'bg'=>'#e8eaf6'],
    ['icon'=>'fas fa-clock','color'=>'#ff6f00','label'=>'Por Asignar','val'=>$stats['ingresados']??0,'bg'=>'#fff3e0'],
    ['icon'=>'fas fa-user-check','color'=>'#0288d1','label'=>'Asignados','val'=>$stats['asignados']??0,'bg'=>'#e1f5fe'],
    ['icon'=>'fas fa-search','color'=>'#7b1fa2','label'=>'En Revisión','val'=>$stats['en_revision']??0,'bg'=>'#f3e5f5'],
    ['icon'=>'fas fa-check-circle','color'=>'#2e7d32','label'=>'Aprobados','val'=>$stats['aprobados']??0,'bg'=>'#e8f5e9'],
    ['icon'=>'fas fa-flag-checkered','color'=>'#263238','label'=>'Finalizados','val'=>$stats['finalizados']??0,'bg'=>'#eceff1'],
  ];
  foreach ($cards as $c): ?>
  <div class="col-md-4 col-lg-2">
    <div class="stat-card" style="border-left-color:<?= $c['color'] ?>">
      <div class="stat-icon" style="background:<?= $c['bg'] ?>;color:<?= $c['color'] ?>">
        <i class="<?= $c['icon'] ?>"></i>
      </div>
      <div class="stat-num"><?= $c['val'] ?></div>
      <div class="stat-label"><?= $c['label'] ?></div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<div class="row g-4 mb-4">
  <!-- Gráfico mensual -->
  <div class="col-lg-7">
    <div class="table-card p-3">
      <h6 class="fw-bold mb-3"><i class="fas fa-chart-bar me-2 text-primary"></i>Trámites por Mes (últimos 6 meses)</h6>
      <canvas id="chartMes" height="180"></canvas>
    </div>
  </div>

  <!-- Pendientes de asignación -->
  <div class="col-lg-5">
    <div class="table-card">
      <div class="table-header">
        <h6 class="fw-bold mb-0"><i class="fas fa-exclamation-circle me-2 text-warning"></i>Pendientes de Asignar</h6>
        <a href="/SMIA2/modules/director/asignar.php" class="btn btn-sm btn-primary">Ver todos</a>
      </div>
      <?php if (empty($pendientes)): ?>
      <div class="text-center py-4 text-muted"><i class="fas fa-check-circle fa-2x text-success mb-2 d-block"></i>Sin trámites pendientes</div>
      <?php else: ?>
      <div class="list-group list-group-flush">
        <?php foreach ($pendientes as $p): ?>
        <div class="list-group-item d-flex align-items-center justify-content-between py-3">
          <div>
            <div class="fw-semibold small"><?= e($p['codigo']) ?></div>
            <div class="text-muted" style="font-size:.78rem"><?= e($p['consultor']) ?></div>
            <div class="text-muted" style="font-size:.72rem"><?= fdate($p['fecha_ingreso'],'d/m/Y') ?></div>
          </div>
          <a href="/SMIA2/modules/director/asignar.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-outline-primary">Asignar</a>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- KPI Técnicos -->
<div class="row g-4 mb-4">
  <div class="col-12">
    <div class="table-card">
      <div class="table-header">
        <h6 class="fw-bold mb-0"><i class="fas fa-chart-line me-2 text-success"></i>KPI por Técnico – Período Actual</h6>
        <a href="/SMIA2/modules/director/kpi.php" class="btn btn-sm btn-success">Ver detalles</a>
      </div>
      <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead><tr>
            <th>Técnico</th><th class="text-center">Asignados</th><th class="text-center">Completados</th>
            <th class="text-center">Pendientes</th><th class="text-center">Días Prom.</th><th class="text-center">Eficiencia</th>
          </tr></thead>
          <tbody>
          <?php if (empty($kpiTecnicos)): ?>
          <tr><td colspan="6" class="text-center text-muted py-4">No hay técnicos registrados</td></tr>
          <?php else: ?>
          <?php foreach ($kpiTecnicos as $k):
            $ef = $k['total'] > 0 ? round(($k['completados']/$k['total'])*100) : 0;
            $efColor = $ef >= 80 ? '#2e7d32' : ($ef >= 50 ? '#f57c00' : '#c62828');
          ?>
          <tr>
            <td><div class="fw-semibold"><?= e($k['tecnico']) ?></div></td>
            <td class="text-center"><?= $k['total'] ?></td>
            <td class="text-center"><span class="badge bg-success"><?= $k['completados']??0 ?></span></td>
            <td class="text-center"><span class="badge bg-warning text-dark"><?= $k['pendientes']??0 ?></span></td>
            <td class="text-center"><?= $k['dias_prom']??'—' ?> días</td>
            <td class="text-center">
              <div class="d-flex align-items-center gap-2">
                <div class="progress flex-1" style="height:6px">
                  <div class="progress-bar" style="width:<?= $ef ?>%;background:<?= $efColor ?>"></div>
                </div>
                <span class="small fw-bold" style="color:<?= $efColor ?>;width:36px"><?= $ef ?>%</span>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<!-- Últimos trámites -->
<div class="table-card">
  <div class="table-header">
    <h6 class="fw-bold mb-0"><i class="fas fa-list me-2"></i>Últimos Trámites Ingresados</h6>
    <a href="/SMIA2/modules/director/hojas_ruta.php" class="btn btn-sm btn-primary">Ver todos</a>
  </div>
  <div class="table-responsive">
    <table class="table table-hover mb-0">
      <thead><tr>
        <th>Código HDR</th><th>Consultor</th><th>Modalidad</th><th>Técnico</th>
        <th>Estado</th><th>Fecha Ingreso</th><th>Fecha Límite</th><th>Acción</th>
      </tr></thead>
      <tbody>
      <?php foreach ($ultimas as $h): ?>
      <tr>
        <td><code class="text-primary fw-bold"><?= e($h['codigo']) ?></code></td>
        <td><?= e($h['consultor']) ?></td>
        <td><?= $h['modalidad']==='nuevo_rai' ? '<span class="badge bg-primary">Nuevo RAI</span>' : '<span class="badge bg-secondary">RAI Asignado</span>' ?></td>
        <td><?= $h['tecnico_nombre'] ? e($h['tecnico_nombre'].' '.$h['tecnico_apellido']) : '<span class="text-muted">Sin asignar</span>' ?></td>
        <td><?= estadoBadge($h['estado']) ?></td>
        <td><?= fdate($h['fecha_ingreso'],'d/m/Y') ?></td>
        <td><?php
          if ($h['fecha_limite']) {
            $dias = diasRestantes($h['fecha_limite']);
            $cls = $dias <= 3 ? 'text-danger fw-bold' : ($dias <= 7 ? 'text-warning fw-bold' : '');
            echo "<span class='$cls'>".fdate($h['fecha_limite'],'d/m/Y')." ($dias d.)</span>";
          } else echo '—';
        ?></td>
        <td>
          <a href="/SMIA2/modules/director/hojas_ruta.php?ver=<?= $h['id'] ?>" class="btn btn-xs btn-outline-primary" style="font-size:.75rem;padding:.2rem .5rem">Ver</a>
          <?php if ($h['estado']==='ingresado'): ?>
          <a href="/SMIA2/modules/director/asignar.php?id=<?= $h['id'] ?>" class="btn btn-xs btn-warning" style="font-size:.75rem;padding:.2rem .5rem">Asignar</a>
          <?php endif; ?>
        </td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<script>
// Chart mensual
const ctx = document.getElementById('chartMes').getContext('2d');
new Chart(ctx, {
  type: 'bar',
  data: {
    labels: [<?= implode(',', array_map(fn($r) => '"'.e($r['mes']).'"', $porMes)) ?>],
    datasets: [
      { label: 'Ingresados', data: [<?= implode(',', array_column($porMes,'total')) ?>], backgroundColor: '#1a237e', borderRadius: 6 },
      { label: 'Finalizados', data: [<?= implode(',', array_column($porMes,'finalizados')) ?>], backgroundColor: '#2e7d32', borderRadius: 6 },
    ]
  },
  options: { responsive: true, plugins: { legend: { position: 'top' } }, scales: { y: { beginAtZero: true } } }
});
</script>

<?php require_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
