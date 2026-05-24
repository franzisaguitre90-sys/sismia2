<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../includes/functions.php';
requireRole('director');

$uid       = currentUserId();
$pageTitle = 'KPI Técnicos';

// Actualizar KPI de todos los técnicos
$tecnicos = db()->query("SELECT id FROM usuarios WHERE rol_id=(SELECT id FROM roles WHERE slug='tecnico') AND activo=1")->fetchAll();
foreach ($tecnicos as $t) updateKPI($t['id']);

// KPI por técnico (mes actual)
$kpis = db()->query("
    SELECT k.*, CONCAT(u.nombre,' ',u.apellido) tecnico,
           (SELECT COUNT(*) FROM hojas_de_ruta WHERE tecnico_id=k.tecnico_id AND estado='en_revision') en_revision
    FROM kpi_tecnicos k
    JOIN usuarios u ON k.tecnico_id=u.id
    WHERE k.periodo_mes=MONTH(NOW()) AND k.periodo_anio=YEAR(NOW())
    ORDER BY k.porcentaje_eficiencia DESC
")->fetchAll();

// Histórico 6 meses para un técnico específico
$tecSelec = (int)($_GET['tecnico'] ?? ($kpis[0]['tecnico_id'] ?? 0));
$historico = [];
if ($tecSelec) {
    $historico = db()->prepare("
        SELECT k.*, CONCAT(u.nombre,' ',u.apellido) tecnico
        FROM kpi_tecnicos k JOIN usuarios u ON k.tecnico_id=u.id
        WHERE k.tecnico_id=?
        ORDER BY k.periodo_anio, k.periodo_mes DESC LIMIT 6
    ");
    $historico->execute([$tecSelec]);
    $historico = $historico->fetchAll();
}

$meses = ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];

require_once __DIR__ . '/../../includes/dashboard_header.php';
?>

<div class="d-flex align-items-center justify-content-between mb-4">
  <h5 class="fw-bold mb-0"><i class="fas fa-chart-bar me-2 text-primary"></i>KPI Técnicos – <?= date('F Y') ?></h5>
  <span class="badge bg-primary px-3 py-2">Período actual</span>
</div>

<!-- Tarjetas de técnicos -->
<div class="row g-3 mb-4">
  <?php foreach ($kpis as $k):
    $ef = $k['porcentaje_eficiencia'];
    $efColor = $ef>=80?'success':($ef>=50?'warning':'danger');
  ?>
  <div class="col-md-6 col-lg-4">
    <div class="table-card p-3 h-100 <?= $tecSelec===$k['tecnico_id']?'border border-2 border-primary':'' ?>">
      <div class="d-flex justify-content-between mb-3">
        <div>
          <div class="fw-bold"><?= e($k['tecnico']) ?></div>
          <a href="?tecnico=<?= $k['tecnico_id'] ?>" class="btn btn-xs btn-outline-primary mt-1" style="font-size:.72rem;padding:.15rem .5rem">Ver historial</a>
        </div>
        <div class="text-center">
          <div class="fw-bold fs-4 text-<?= $efColor ?>"><?= $ef ?>%</div>
          <div class="text-muted" style="font-size:.7rem">Eficiencia</div>
        </div>
      </div>
      <div class="progress mb-3" style="height:8px">
        <div class="progress-bar bg-<?= $efColor ?>" style="width:<?= $ef ?>%"></div>
      </div>
      <div class="row g-2 text-center">
        <div class="col-3"><div class="p-1 bg-primary-subtle rounded"><div class="fw-bold small"><?= $k['tramites_asignados'] ?></div><div style="font-size:.65rem" class="text-muted">Total</div></div></div>
        <div class="col-3"><div class="p-1 bg-success-subtle rounded"><div class="fw-bold small text-success"><?= $k['tramites_completados'] ?></div><div style="font-size:.65rem" class="text-muted">Complet.</div></div></div>
        <div class="col-3"><div class="p-1 bg-warning-subtle rounded"><div class="fw-bold small text-warning"><?= $k['tramites_pendientes'] ?></div><div style="font-size:.65rem" class="text-muted">Pend.</div></div></div>
        <div class="col-3"><div class="p-1 bg-danger-subtle rounded"><div class="fw-bold small text-danger"><?= $k['tramites_rechazados'] ?></div><div style="font-size:.65rem" class="text-muted">Rechs.</div></div></div>
      </div>
      <div class="mt-2 small text-muted d-flex justify-content-between">
        <span><i class="fas fa-file-alt me-1"></i><?= $k['documentos_revisados'] ?> docs revisados</span>
        <span><i class="fas fa-clock me-1"></i><?= $k['tiempo_promedio_dias'] ?> días prom.</span>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
  <?php if (empty($kpis)): ?>
  <div class="col-12"><div class="alert alert-info">Sin datos de KPI para este período. Asegúrese de que los técnicos tengan trámites asignados.</div></div>
  <?php endif; ?>
</div>

<!-- Histórico del técnico seleccionado -->
<?php if ($tecSelec && !empty($historico)): ?>
<div class="table-card p-4">
  <h6 class="fw-bold mb-4"><i class="fas fa-history me-2"></i>Histórico de <?= e($historico[0]['tecnico']) ?> – Últimos 6 meses</h6>
  <div class="row g-4">
    <div class="col-lg-7">
      <canvas id="chartHistorico" height="200"></canvas>
    </div>
    <div class="col-lg-5">
      <div class="table-responsive">
        <table class="table table-sm">
          <thead><tr><th>Mes</th><th class="text-center">Asign.</th><th class="text-center">Compl.</th><th class="text-center">Efic.</th><th class="text-center">Días</th></tr></thead>
          <tbody>
          <?php foreach (array_reverse($historico) as $h): $ef=$h['porcentaje_eficiencia']; ?>
          <tr>
            <td class="small"><?= $meses[$h['periodo_mes']-1] ?> <?= $h['periodo_anio'] ?></td>
            <td class="text-center"><?= $h['tramites_asignados'] ?></td>
            <td class="text-center text-success fw-bold"><?= $h['tramites_completados'] ?></td>
            <td class="text-center">
              <span class="badge bg-<?= $ef>=80?'success':($ef>=50?'warning':'danger') ?>"><?= $ef ?>%</span>
            </td>
            <td class="text-center small"><?= $h['tiempo_promedio_dias'] ?></td>
          </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
<script>
new Chart(document.getElementById('chartHistorico'), {
  type: 'line',
  data: {
    labels: [<?= implode(',', array_map(fn($h) => '"'.$meses[$h['periodo_mes']-1].' '.$h['periodo_anio'].'"', array_reverse($historico))) ?>],
    datasets: [
      { label: 'Asignados', data: [<?= implode(',', array_column(array_reverse($historico),'tramites_asignados')) ?>], borderColor:'#1a237e', fill:false, tension:.3 },
      { label: 'Completados', data: [<?= implode(',', array_column(array_reverse($historico),'tramites_completados')) ?>], borderColor:'#2e7d32', fill:false, tension:.3 },
      { label: 'Eficiencia %', data: [<?= implode(',', array_column(array_reverse($historico),'porcentaje_eficiencia')) ?>], borderColor:'#f57c00', fill:false, tension:.3, yAxisID:'y2' },
    ]
  },
  options: {
    responsive:true,
    scales: {
      y: { beginAtZero:true, title:{display:true,text:'Trámites'} },
      y2: { position:'right', beginAtZero:true, max:100, title:{display:true,text:'Eficiencia %'} }
    }
  }
});
</script>
<?php endif; ?>

<?php require_once __DIR__ . '/../../includes/dashboard_footer.php'; ?>
