<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../includes/functions.php';
requireRole('tecnico');

$uid       = currentUserId();
$pageTitle = 'Mis Trámites';
$success = '';
$error   = '';

// Cambiar estado de un trámite
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $hojaId  = (int)($_POST['hoja_id'] ?? 0);
    $nuevoEst = sanitize($_POST['nuevo_estado'] ?? '');
    $obs      = sanitize($_POST['observaciones'] ?? '');

    $sh = db()->prepare("SELECT * FROM hojas_de_ruta WHERE id=? AND tecnico_id=?");
    $sh->execute([$hojaId, $uid]);
    $hoja = $sh->fetch();

    if ($hoja && in_array($nuevoEst, ['en_revision','observado','aprobado','rechazado'])) {
        $estadoAnt = $hoja['estado'];
        $extra = [];
        if ($nuevoEst === 'aprobado')  $extra = ['fecha_revision_fin' => date('Y-m-d H:i:s')];
        if ($nuevoEst === 'en_revision') $extra = ['fecha_revision_inicio' => date('Y-m-d H:i:s')];
        if ($nuevoEst === 'rechazado') $extra = ['fecha_finalizacion' => date('Y-m-d H:i:s')];

        $updates  = "estado=?, observaciones_tecnico=?";
        $bindings = [$nuevoEst, $obs ?: null];
        foreach ($extra as $col => $val) { $updates .= ", $col=?"; $bindings[] = $val; }
        $bindings[] = $hojaId;
        db()->prepare("UPDATE hojas_de_ruta SET $updates WHERE id=?")->execute($bindings);

        registrarEstado($hojaId, $estadoAnt, $nuevoEst, $uid, $obs);
        updateKPI($uid);

        // Notificar al consultor
        $msgs = ['en_revision'=>'está siendo revisada','observado'=>'tiene observaciones que debe corregir','aprobado'=>'fue aprobada por el técnico','rechazado'=>'fue rechazada'];
        notificar($hoja['consultor_id'], "Trámite: ".ucfirst($nuevoEst), "Su hoja de ruta {$hoja['codigo']} {$msgs[$nuevoEst]}.", $nuevoEst==='aprobado'?'success':($nuevoEst==='rechazado'?'danger':'warning'), 'fas fa-file-alt', 'hoja_ruta', $hojaId);

        // Notificar al director
        $dirs = db()->query("SELECT id FROM usuarios WHERE rol_id=(SELECT id FROM roles WHERE slug='director') AND activo=1")->fetchAll();
        foreach ($dirs as $d) notificar($d['id'], "HDR {$hoja['codigo']}: $nuevoEst", "El técnico cambió el estado a $nuevoEst.", 'info', 'fas fa-info-circle', 'hoja_ruta', $hojaId);

        audit($uid, "CAMBIO_ESTADO_HDR", 'tecnico', "HDR {$hoja['codigo']}: $estadoAnt → $nuevoEst", $hojaId);
        $success = "Estado actualizado a <strong>$nuevoEst</strong> correctamente.";
    }
}

$filtroEst = sanitize($_GET['estado'] ?? '');
$where = "WHERE hdr.tecnico_id=$uid";
if ($filtroEst) $where .= " AND hdr.estado='$filtroEst'";

$hojas = db()->query("
    SELECT hdr.*, CONCAT(uc.nombre,' ',uc.apellido) consultor,
           rai.razon_social, rai.categoria_rai, rai.estado_formulario,
           (SELECT COUNT(*) FROM documentos d WHERE d.hoja_ruta_id=hdr.id AND d.estado='pendiente') docs_pend,
           (SELECT COUNT(*) FROM documentos d WHERE d.hoja_ruta_id=hdr.id) docs_total
    FROM hojas_de_ruta hdr
    JOIN usuarios uc ON hdr.consultor_id=uc.id
    LEFT JOIN formularios_rai rai ON rai.hoja_ruta_id=hdr.id
    $where ORDER BY FIELD(hdr.prioridad,'urgente','alta','media','baja'), hdr.fecha_limite IS NULL, hdr.fecha_limite ASC
")->fetchAll();

$detalle = null;
if (!empty($_GET['id'])) {
    $sv = db()->prepare("SELECT hdr.*, CONCAT(uc.nombre,' ',uc.apellido) consultor FROM hojas_de_ruta hdr JOIN usuarios uc ON hdr.consultor_id=uc.id WHERE hdr.id=? AND hdr.tecnico_id=?");
    $sv->execute([(int)$_GET['id'], $uid]);
    $detalle = $sv->fetch();
}

require_once __DIR__ . '/../../includes/dashboard_header.php';
?>

<?php if ($error): ?><div class="alert alert-danger rounded-3 mb-3"><?= e($error) ?></div><?php endif; ?>
<?php if ($success): ?><div class="alert alert-success rounded-3 mb-3"><i class="fas fa-check-circle me-2"></i><?= $success ?></div><?php endif; ?>

<div class="d-flex justify-content-between mb-4 flex-wrap gap-2">
  <h5 class="fw-bold mb-0"><i class="fas fa-folder-open me-2 text-success"></i>Mis Trámites Asignados</h5>
  <div class="d-flex gap-2 flex-wrap">
    <?php foreach ([''=> 'Todos','en_revision'=>'En Revisión','observado'=>'Observados','aprobado'=>'Aprobados','rechazado'=>'Rechazados'] as $k=>$v): ?>
    <a href="?estado=<?= $k ?>" class="btn btn-sm <?= $filtroEst===$k?'btn-success':'btn-outline-secondary' ?>"><?= $v ?></a>
    <?php endforeach; ?>
  </div>
</div>

<?php if ($detalle): ?>
<!-- Panel de acción -->
<div class="table-card p-4 mb-4">
  <div class="d-flex justify-content-between mb-3">
    <div>
      <h5 class="mb-1"><code class="text-success"><?= e($detalle['codigo']) ?></code> <?= estadoBadge($detalle['estado']) ?></h5>
      <div class="text-muted small">Consultor: <?= e($detalle['consultor']) ?> | Prioridad: <?= prioridadBadge($detalle['prioridad']) ?></div>
    </div>
    <a href="?" class="btn btn-sm btn-outline-secondary"><i class="fas fa-times"></i></a>
  </div>

  <!-- Documentos del trámite -->
  <?php
  $docsDet = db()->prepare("SELECT d.*, td.nombre tipo_nombre FROM documentos d LEFT JOIN tipos_documento td ON d.tipo_documento_id=td.id WHERE d.hoja_ruta_id=? ORDER BY d.fecha_subida");
  $docsDet->execute([$detalle['id']]);
  $docsDet = $docsDet->fetchAll();
  ?>
  <h6 class="fw-bold mt-2 mb-2">Documentos del Trámite</h6>
  <?php if (empty($docsDet)): ?><p class="text-muted small">Sin documentos cargados por el consultor.</p>
  <?php else: ?>
  <div class="table-responsive mb-3">
    <table class="table table-sm">
      <thead><tr><th>Documento</th><th>Tipo</th><th>Fecha Subida</th><th>Estado</th><th>Ver</th></tr></thead>
      <tbody>
      <?php foreach ($docsDet as $d):
        $stBadge=['pendiente'=>'secondary','en_revision'=>'info text-dark','aprobado'=>'success','rechazado'=>'danger','observado'=>'warning text-dark'][$d['estado']]??'secondary';
      ?>
      <tr>
        <td class="small"><?= e($d['nombre_descripcion']) ?></td>
        <td class="small"><?= $d['tipo_nombre']?e($d['tipo_nombre']):'—' ?></td>
        <td class="small"><?= fdate($d['fecha_subida'],'d/m/Y H:i') ?></td>
        <td><span class="badge bg-<?= $stBadge ?>"><?= ucfirst($d['estado']) ?></span></td>
        <td><a href="/SMIA2/<?= $d['archivo_ruta'] ?>" target="_blank" class="btn btn-xs btn-outline-primary" style="font-size:.72rem;padding:.15rem .4rem"><i class="fas fa-eye"></i></a></td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>

  <!-- Cambiar estado -->
  <h6 class="fw-bold">Acción sobre el Trámite</h6>
  <form method="POST" class="row g-3">
    <input type="hidden" name="hoja_id" value="<?= $detalle['id'] ?>">
    <div class="col-md-4">
      <label class="form-label fw-semibold">Nuevo Estado <span class="text-danger">*</span></label>
      <select name="nuevo_estado" class="form-select" required>
        <option value="">— Seleccione —</option>
        <?php if ($detalle['estado']!=='en_revision'): ?><option value="en_revision">Iniciar Revisión</option><?php endif; ?>
        <?php if (in_array($detalle['estado'],['en_revision','asignado'])): ?>
        <option value="observado">Con Observaciones (solicitar correcciones)</option>
        <option value="aprobado">Aprobar Trámite</option>
        <option value="rechazado">Rechazar Trámite</option>
        <?php endif; ?>
      </select>
    </div>
    <div class="col-md-6">
      <label class="form-label fw-semibold">Observaciones / Justificación</label>
      <textarea name="observaciones" class="form-control" rows="2" placeholder="Detalle observaciones o motivo de la decisión..."></textarea>
    </div>
    <div class="col-md-2 d-flex align-items-end">
      <button type="submit" class="btn btn-success w-100 fw-bold"><i class="fas fa-save me-1"></i>Aplicar</button>
    </div>
  </form>

  <!-- Historial -->
  <h6 class="fw-bold mt-4 mb-2">Historial de Estados</h6>
  <?php foreach (getHistorial($detalle['id']) as $h): ?>
  <div class="d-flex gap-2 mb-2 small">
    <i class="fas fa-circle-dot" style="color:<?= $h['color_primary'] ?>;margin-top:2px"></i>
    <div><?= $h['estado_anterior']?estadoBadge($h['estado_anterior']).' → ':'' ?><?= estadoBadge($h['estado_nuevo']) ?>
    <span class="text-muted ms-2"><?= e($h['nombre'].' '.$h['apellido']) ?> · <?= fdate($h['fecha']) ?></span>
    <?php if ($h['observaciones']): ?><div class="text-muted"><?= e($h['observaciones']) ?></div><?php endif; ?>
    </div>
  </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<div class="table-card">
  <div class="table-responsive">
    <table class="table table-hover mb-0">
      <thead><tr>
        <th>Código HDR</th><th>Empresa</th><th>Cat.</th><th>Consultor</th>
        <th>Prioridad</th><th>Estado</th><th>Docs Pend.</th><th>Límite</th><th>Acción</th>
      </tr></thead>
      <tbody>
      <?php if (empty($hojas)): ?><tr><td colspan="9" class="text-center text-muted py-4">Sin trámites asignados</td></tr><?php endif; ?>
      <?php foreach ($hojas as $h):
        $dias = diasRestantes($h['fecha_limite']);
        $rowCls = ($dias!==null&&$dias<=3&&!in_array($h['estado'],['aprobado','rechazado']))?'table-danger':'';
      ?>
      <tr class="<?= $rowCls ?>">
        <td><code class="text-success fw-bold small"><?= e($h['codigo']) ?></code></td>
        <td class="small"><?= $h['razon_social']?e($h['razon_social']):'—' ?></td>
        <td><?php if ($h['categoria_rai']): ?><span class="badge bg-<?= ['A'=>'danger','B'=>'warning','C'=>'success'][$h['categoria_rai']]??'secondary' ?>">C<?= $h['categoria_rai'] ?></span><?php endif; ?></td>
        <td class="small"><?= e($h['consultor']) ?></td>
        <td><?= prioridadBadge($h['prioridad']) ?></td>
        <td><?= estadoBadge($h['estado']) ?></td>
        <td class="text-center">
          <?= $h['docs_pend']>0?"<span class='badge bg-warning text-dark'>{$h['docs_pend']}</span>":"<span class='badge bg-light text-muted'>{$h['docs_total']}</span>" ?>
        </td>
        <td class="small <?= ($dias!==null&&$dias<=3)?'text-danger fw-bold':($dias!==null&&$dias<=7?'text-warning fw-bold':'') ?>">
          <?= $h['fecha_limite']?fdate($h['fecha_limite'],'d/m/Y').($dias!==null?" ({$dias}d)":''):'—' ?>
        </td>
        <td>
          <div class="d-flex gap-1">
            <a href="?id=<?= $h['id'] ?>" class="btn btn-xs btn-success" style="font-size:.72rem;padding:.2rem .5rem">Revisar</a>
            <a href="/SMIA2/modules/tecnico/revisar_doc.php?hoja=<?= $h['id'] ?>" class="btn btn-xs btn-outline-warning" style="font-size:.72rem;padding:.2rem .5rem"><i class="fas fa-file-alt"></i></a>
          </div>
        </td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/../../includes/dashboard_footer.php'; ?>
