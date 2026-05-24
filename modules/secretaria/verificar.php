<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../includes/functions.php';
requireRole('secretaria');

$uid       = currentUserId();
$pageTitle = 'Verificar Resultados';
$buscar    = sanitize($_GET['q'] ?? '');

// Buscar por habilitación ID o hoja de ruta ID
$habId  = (int)($_GET['hab'] ?? 0);
$hojaId = (int)($_GET['hoja'] ?? 0);

$habDetalle = null;
if ($habId) {
    $s = db()->prepare("SELECT h.*,hdr.codigo,hdr.modalidad,CONCAT(uc.nombre,' ',uc.apellido) consultor, uc.clave_unica, uc.email c_email, CONCAT(ut.nombre,' ',ut.apellido) tecnico, rai.razon_social,rai.nit,rai.categoria_rai,rai.municipio,rai.departamento,rai.representante_nombre FROM habilitaciones_ambientales h JOIN hojas_de_ruta hdr ON h.hoja_ruta_id=hdr.id JOIN usuarios uc ON hdr.consultor_id=uc.id LEFT JOIN usuarios ut ON hdr.tecnico_id=ut.id LEFT JOIN formularios_rai rai ON rai.hoja_ruta_id=hdr.id WHERE h.id=?");
    $s->execute([$habId]);
    $habDetalle = $s->fetch();
}

// Buscar resultados
$results = [];
if ($buscar) {
    $like = "%$buscar%";
    $q = db()->prepare("SELECT h.*,hdr.codigo,CONCAT(uc.nombre,' ',uc.apellido) consultor, rai.razon_social FROM habilitaciones_ambientales h JOIN hojas_de_ruta hdr ON h.hoja_ruta_id=hdr.id JOIN usuarios uc ON hdr.consultor_id=uc.id LEFT JOIN formularios_rai rai ON rai.hoja_ruta_id=hdr.id WHERE hdr.codigo LIKE ? OR rai.razon_social LIKE ? OR rai.nit LIKE ? OR h.numero_certificado LIKE ? ORDER BY h.fecha_registro DESC LIMIT 30");
    $q->execute([$like,$like,$like,$like]);
    $results = $q->fetchAll();
} else {
    $q = db()->query("SELECT h.*,hdr.codigo,CONCAT(uc.nombre,' ',uc.apellido) consultor, rai.razon_social FROM habilitaciones_ambientales h JOIN hojas_de_ruta hdr ON h.hoja_ruta_id=hdr.id JOIN usuarios uc ON hdr.consultor_id=uc.id LEFT JOIN formularios_rai rai ON rai.hoja_ruta_id=hdr.id ORDER BY h.fecha_registro DESC LIMIT 30");
    $results = $q->fetchAll();
}

require_once __DIR__ . '/../../includes/dashboard_header.php';
?>

<!-- Buscador -->
<div class="table-card p-3 mb-4">
  <form method="GET" class="d-flex gap-2">
    <input name="q" class="form-control" placeholder="Buscar por código HDR, empresa, NIT o N° certificado..."
           value="<?= e($buscar) ?>" style="max-width:450px">
    <button type="submit" class="btn btn-primary"><i class="fas fa-search me-1"></i>Buscar</button>
    <a href="?" class="btn btn-outline-secondary">Limpiar</a>
  </form>
</div>

<!-- Detalle de habilitación -->
<?php if ($habDetalle): ?>
<div class="table-card p-4 mb-4">
  <div class="d-flex justify-content-between mb-3">
    <div>
      <h5 class="mb-1">
        <code style="color:#6a1b9a"><?= e($habDetalle['codigo']) ?></code>
        &nbsp; <?= estadoBadge($habDetalle['tipo_resultado']) ?>
      </h5>
      <p class="text-muted small mb-0"><?= $habDetalle['razon_social']?e($habDetalle['razon_social']):'—' ?> · Cat. <?= $habDetalle['categoria_rai']??'—' ?></p>
    </div>
    <a href="?" class="btn btn-sm btn-outline-secondary"><i class="fas fa-times"></i></a>
  </div>

  <div class="row g-4">
    <div class="col-md-4">
      <div class="p-3 border rounded-3">
        <h6 class="fw-bold text-primary mb-3"><i class="fas fa-certificate me-1"></i>Certificación</h6>
        <dl class="row small mb-0">
          <dt class="col-6 text-muted">N° RAI Otorgado</dt><dd class="col-6 fw-bold"><?= $habDetalle['numero_rai_otorgado']?e($habDetalle['numero_rai_otorgado']):'—' ?></dd>
          <dt class="col-6 text-muted">N° Certificado</dt><dd class="col-6"><?= $habDetalle['numero_certificado']?e($habDetalle['numero_certificado']):'—' ?></dd>
          <dt class="col-6 text-muted">N° Resolución</dt><dd class="col-6"><?= $habDetalle['numero_resolucion']?e($habDetalle['numero_resolucion']):'—' ?></dd>
          <dt class="col-6 text-muted">Fecha Emisión</dt><dd class="col-6"><?= $habDetalle['fecha_emision']?fdate($habDetalle['fecha_emision'],'d/m/Y'):'—' ?></dd>
          <dt class="col-6 text-muted">Vencimiento</dt><dd class="col-6">
            <?php if ($habDetalle['fecha_vencimiento']):
              $dv = diasRestantes($habDetalle['fecha_vencimiento']);
              echo "<span class='".($dv!==null&&$dv<30?'text-danger fw-bold':'')."'>".fdate($habDetalle['fecha_vencimiento'],'d/m/Y')."</span>";
              if ($dv!==null&&$dv<30) echo " <span class='badge bg-danger'>Próximo</span>";
            else: echo '—'; endif; ?>
          </dd>
          <dt class="col-6 text-muted">Aprobado por</dt><dd class="col-6"><?= $habDetalle['aprobado_por_nombre']?e($habDetalle['aprobado_por_nombre']):'—' ?></dd>
        </dl>
      </div>
    </div>
    <div class="col-md-4">
      <div class="p-3 border rounded-3">
        <h6 class="fw-bold text-success mb-3"><i class="fas fa-user-graduate me-1"></i>Consultor</h6>
        <dl class="row small mb-0">
          <dt class="col-6 text-muted">Nombre</dt><dd class="col-6"><?= e($habDetalle['consultor']) ?></dd>
          <dt class="col-6 text-muted">Email</dt><dd class="col-6"><?= e($habDetalle['c_email']) ?></dd>
          <dt class="col-6 text-muted">Clave Única</dt><dd class="col-6"><code class="fw-bold"><?= $habDetalle['clave_unica']?e($habDetalle['clave_unica']):'—' ?></code></dd>
          <dt class="col-6 text-muted">Técnico</dt><dd class="col-6"><?= $habDetalle['tecnico']?e($habDetalle['tecnico']):'—' ?></dd>
          <dt class="col-6 text-muted">Modalidad</dt><dd class="col-6"><?= $habDetalle['modalidad']==='nuevo_rai'?'Nuevo RAI':'RAI Asignado' ?></dd>
        </dl>
      </div>
    </div>
    <div class="col-md-4">
      <div class="p-3 border rounded-3">
        <h6 class="fw-bold text-warning mb-3"><i class="fas fa-building me-1"></i>Empresa</h6>
        <dl class="row small mb-0">
          <dt class="col-5 text-muted">Empresa</dt><dd class="col-7"><?= $habDetalle['razon_social']?e($habDetalle['razon_social']):'—' ?></dd>
          <dt class="col-5 text-muted">NIT</dt><dd class="col-7"><?= $habDetalle['nit']?e($habDetalle['nit']):'—' ?></dd>
          <dt class="col-5 text-muted">Categoría</dt><dd class="col-7">Cat. <?= $habDetalle['categoria_rai']??'—' ?></dd>
          <dt class="col-5 text-muted">Municipio</dt><dd class="col-7"><?= $habDetalle['municipio']?e($habDetalle['municipio'].', '.$habDetalle['departamento']):'—' ?></dd>
          <dt class="col-5 text-muted">Representante</dt><dd class="col-7"><?= $habDetalle['representante_nombre']?e($habDetalle['representante_nombre']):'—' ?></dd>
        </dl>
      </div>
    </div>
  </div>

  <?php if ($habDetalle['condiciones_especiales']): ?>
  <div class="alert alert-warning rounded-3 mt-3 small"><strong>Condiciones Especiales:</strong> <?= e($habDetalle['condiciones_especiales']) ?></div>
  <?php endif; ?>
  <?php if ($habDetalle['motivo_no_habilitacion']): ?>
  <div class="alert alert-danger rounded-3 mt-3 small"><strong>Motivo de No Habilitación:</strong> <?= e($habDetalle['motivo_no_habilitacion']) ?></div>
  <?php endif; ?>
  <?php if ($habDetalle['observaciones_finales']): ?>
  <div class="alert alert-secondary rounded-3 mt-3 small"><strong>Observaciones:</strong> <?= e($habDetalle['observaciones_finales']) ?></div>
  <?php endif; ?>
</div>
<?php endif; ?>

<!-- Tabla de resultados -->
<div class="table-card">
  <div class="table-header">
    <h6 class="fw-bold mb-0"><i class="fas fa-list me-2"></i>Registro de Habilitaciones (<?= count($results) ?>)</h6>
  </div>
  <div class="table-responsive">
    <table class="table table-hover mb-0">
      <thead><tr>
        <th>Código HDR</th><th>Empresa</th><th>Resultado</th><th>N° RAI</th>
        <th>N° Certificado</th><th>Emisión</th><th>Vencimiento</th><th>Consultor</th><th>Ver</th>
      </tr></thead>
      <tbody>
      <?php if (empty($results)): ?><tr><td colspan="9" class="text-center text-muted py-4">Sin resultados</td></tr><?php endif; ?>
      <?php foreach ($results as $r):
        $dv = $r['fecha_vencimiento'] ? diasRestantes($r['fecha_vencimiento']) : null;
      ?>
      <tr class="<?= $r['tipo_resultado']==='habilitado'?'table-success-subtle':($r['tipo_resultado']==='no_habilitado'?'table-danger-subtle':'') ?>">
        <td><code class="small"><?= e($r['codigo']) ?></code></td>
        <td class="small"><?= $r['razon_social']?e($r['razon_social']):'—' ?></td>
        <td><?= estadoBadge($r['tipo_resultado']) ?></td>
        <td class="small fw-bold"><?= $r['numero_rai_otorgado']?e($r['numero_rai_otorgado']):'—' ?></td>
        <td class="small"><?= $r['numero_certificado']?e($r['numero_certificado']):'—' ?></td>
        <td class="small"><?= $r['fecha_emision']?fdate($r['fecha_emision'],'d/m/Y'):'—' ?></td>
        <td class="small <?= $dv!==null&&$dv<30?'text-danger fw-bold':'' ?>">
          <?= $r['fecha_vencimiento']?fdate($r['fecha_vencimiento'],'d/m/Y').' '.($dv!==null&&$dv<30?"<span class='badge bg-danger'>Próximo</span>":''):'—' ?>
        </td>
        <td class="small"><?= e($r['consultor']) ?></td>
        <td><a href="?hab=<?= $r['id'] ?>" class="btn btn-xs btn-outline-primary" style="font-size:.72rem;padding:.2rem .5rem"><i class="fas fa-eye"></i></a></td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/../../includes/dashboard_footer.php'; ?>
