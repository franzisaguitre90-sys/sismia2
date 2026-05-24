<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../includes/functions.php';
requireRole('tecnico');

$uid       = currentUserId();
$pageTitle = 'Revisar Documentos';
$success = '';
$error   = '';

// Revisar un documento
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $docId  = (int)($_POST['doc_id'] ?? 0);
    $estado = sanitize($_POST['estado'] ?? '');
    $obs    = sanitize($_POST['observaciones'] ?? '');

    if ($docId && in_array($estado, ['aprobado','rechazado','observado'])) {
        // Verificar que el documento pertenece a un trámite asignado al técnico
        $sd = db()->prepare("SELECT d.*, hdr.consultor_id FROM documentos d JOIN hojas_de_ruta hdr ON d.hoja_ruta_id=hdr.id WHERE d.id=? AND hdr.tecnico_id=?");
        $sd->execute([$docId, $uid]);
        $doc = $sd->fetch();
        if ($doc) {
            db()->prepare("UPDATE documentos SET estado=?,observaciones=?,revisado_por=?,fecha_revision=NOW() WHERE id=?")->execute([$estado,$obs?:null,$uid,$docId]);

            // Notificar al consultor
            $hdrCode = db()->prepare("SELECT codigo FROM hojas_de_ruta WHERE id=?")->execute([$doc['hoja_ruta_id']]) ? db()->query("SELECT codigo FROM hojas_de_ruta WHERE id={$doc['hoja_ruta_id']}")->fetchColumn() : '';
            notificar($doc['consultor_id'], "Documento $estado", "El documento '{$doc['nombre_descripcion']}' fue $estado en la Hoja de Ruta $hdrCode.", $estado==='aprobado'?'success':'warning', 'fas fa-file-alt', 'hoja_ruta', $doc['hoja_ruta_id']);

            updateKPI($uid);
            $success = "Documento marcado como <strong>$estado</strong>.";
        }
    }
}

// Hoja de ruta seleccionada
$hojaId = (int)($_GET['hoja'] ?? $_GET['doc_hoja'] ?? 0);
$docId  = (int)($_GET['doc'] ?? 0);

// Mis trámites con documentos pendientes
$tramitesConDocs = db()->prepare("
    SELECT hdr.id, hdr.codigo, hdr.prioridad, hdr.estado,
           CONCAT(uc.nombre,' ',uc.apellido) consultor,
           COUNT(d.id) docs_total,
           SUM(d.estado='pendiente') docs_pendientes,
           SUM(d.estado='aprobado') docs_aprobados
    FROM hojas_de_ruta hdr
    JOIN usuarios uc ON hdr.consultor_id=uc.id
    JOIN documentos d ON d.hoja_ruta_id=hdr.id
    WHERE hdr.tecnico_id=? AND hdr.estado NOT IN ('rechazado')
    GROUP BY hdr.id HAVING docs_pendientes > 0
    ORDER BY FIELD(hdr.prioridad,'urgente','alta','media','baja')
");
$tramitesConDocs->execute([$uid]);
$tramitesConDocs = $tramitesConDocs->fetchAll();

// Documentos de la hoja seleccionada
$docs = [];
if ($hojaId) {
    $sh = db()->prepare("SELECT codigo FROM hojas_de_ruta WHERE id=? AND tecnico_id=?");
    $sh->execute([$hojaId,$uid]);
    $codigoHoja = $sh->fetchColumn();
    $sd = db()->prepare("SELECT d.*, td.nombre tipo_nombre FROM documentos d LEFT JOIN tipos_documento td ON d.tipo_documento_id=td.id WHERE d.hoja_ruta_id=? ORDER BY d.estado='pendiente' DESC, d.fecha_subida");
    $sd->execute([$hojaId]);
    $docs = $sd->fetchAll();
}

require_once __DIR__ . '/../../includes/dashboard_header.php';
?>

<?php if ($success): ?><div class="alert alert-success rounded-3 mb-3"><i class="fas fa-check-circle me-2"></i><?= $success ?></div><?php endif; ?>

<div class="row g-4">
  <!-- Lista de trámites con documentos pendientes -->
  <div class="col-lg-4">
    <div class="table-card">
      <div class="table-header">
        <h6 class="fw-bold mb-0"><i class="fas fa-inbox me-2 text-warning"></i>Trámites con Docs Pendientes</h6>
      </div>
      <?php if (empty($tramitesConDocs)): ?>
      <div class="text-center py-4 text-muted p-3"><i class="fas fa-check-circle fa-2x text-success mb-2 d-block"></i>Sin documentos pendientes de revisión</div>
      <?php else: ?>
      <div class="list-group list-group-flush">
        <?php foreach ($tramitesConDocs as $t): ?>
        <a href="?hoja=<?= $t['id'] ?>" class="list-group-item list-group-item-action <?= $hojaId===$t['id']?'active':'' ?>">
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <code class="<?= $hojaId===$t['id']?'text-white':'text-success' ?> fw-bold small"><?= e($t['codigo']) ?></code>
              <div class="small <?= $hojaId===$t['id']?'text-white-75':'text-muted' ?>"><?= e($t['consultor']) ?></div>
            </div>
            <div class="text-end">
              <span class="badge bg-warning text-dark"><?= $t['docs_pendientes'] ?> pend.</span>
              <div class="small <?= $hojaId===$t['id']?'text-white-50':'text-muted' ?>"><?= prioridadBadge($t['prioridad']) ?></div>
            </div>
          </div>
        </a>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Documentos del trámite seleccionado -->
  <div class="col-lg-8">
    <?php if (!$hojaId): ?>
    <div class="table-card p-5 text-center">
      <i class="fas fa-hand-pointer fa-3x text-muted mb-3"></i>
      <p class="text-muted">Seleccione un trámite de la lista para revisar sus documentos.</p>
    </div>
    <?php else: ?>
    <div class="table-card">
      <div class="table-header">
        <h6 class="fw-bold mb-0"><i class="fas fa-file-alt me-2"></i>Documentos de <code><?= e($codigoHoja??'—') ?></code></h6>
        <a href="/SMIA2/modules/tecnico/mis_tramites.php?id=<?= $hojaId ?>" class="btn btn-sm btn-outline-success">Ver Trámite</a>
      </div>
      <?php if (empty($docs)): ?>
      <div class="text-center py-4 text-muted">Sin documentos cargados.</div>
      <?php else: ?>
      <div class="p-3">
        <?php foreach ($docs as $d):
          $stBadge = ['pendiente'=>'warning text-dark','en_revision'=>'info text-dark','aprobado'=>'success','rechazado'=>'danger','observado'=>'warning text-dark'][$d['estado']]??'secondary';
          $ext = strtolower(pathinfo($d['archivo_nombre_original'], PATHINFO_EXTENSION));
          $icon = match($ext) { 'pdf'=>'fa-file-pdf text-danger', 'jpg','jpeg','png'=>'fa-file-image text-primary', default=>'fa-file text-muted' };
        ?>
        <div class="border rounded-3 p-3 mb-3 <?= $d['estado']==='pendiente'?'border-warning bg-warning-subtle':'' ?>">
          <div class="d-flex align-items-start gap-3">
            <i class="fas <?= $icon ?> fa-2x flex-shrink-0"></i>
            <div class="flex-1">
              <div class="fw-semibold"><?= e($d['nombre_descripcion']) ?></div>
              <div class="text-muted small"><?= $d['tipo_nombre']?e($d['tipo_nombre']):'Sin tipo' ?> &nbsp;·&nbsp; <?= e($d['archivo_nombre_original']) ?> &nbsp;·&nbsp; <?= round($d['archivo_size']/1024,1) ?> KB</div>
              <div class="text-muted small"><?= fdate($d['fecha_subida'],'d/m/Y H:i') ?></div>
              <?php if ($d['observaciones']): ?><div class="alert alert-warning py-1 px-2 mt-1 mb-0 small"><?= e($d['observaciones']) ?></div><?php endif; ?>
            </div>
            <div class="d-flex flex-column gap-1 align-items-end flex-shrink-0">
              <span class="badge bg-<?= $stBadge ?>"><?= ucfirst($d['estado']) ?></span>
              <a href="/SMIA2/<?= $d['archivo_ruta'] ?>" target="_blank" class="btn btn-sm btn-outline-primary"><i class="fas fa-eye me-1"></i>Ver</a>
            </div>
          </div>

          <!-- Formulario de revisión (solo si pendiente) -->
          <?php if ($d['estado']==='pendiente'): ?>
          <form method="POST" class="mt-3 d-flex gap-2 flex-wrap align-items-end">
            <input type="hidden" name="doc_id" value="<?= $d['id'] ?>">
            <input type="hidden" name="doc_hoja" value="<?= $hojaId ?>">
            <div class="flex-1" style="min-width:200px">
              <label class="form-label small fw-semibold mb-1">Observaciones</label>
              <input name="observaciones" class="form-control form-control-sm" placeholder="Motivo de rechazo/observación...">
            </div>
            <input type="hidden" name="estado" id="estDoc<?= $d['id'] ?>" value="">
            <button type="submit" class="btn btn-sm btn-success" onclick="document.getElementById('estDoc<?= $d['id'] ?>').value='aprobado'"><i class="fas fa-check me-1"></i>Aprobar</button>
            <button type="submit" class="btn btn-sm btn-warning text-dark" onclick="document.getElementById('estDoc<?= $d['id'] ?>').value='observado'"><i class="fas fa-eye me-1"></i>Observar</button>
            <button type="submit" class="btn btn-sm btn-danger" onclick="document.getElementById('estDoc<?= $d['id'] ?>').value='rechazado';return confirm('¿Rechazar este documento?')"><i class="fas fa-times me-1"></i>Rechazar</button>
          </form>
          <?php endif; ?>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>
    <?php endif; ?>
  </div>
</div>

<?php require_once __DIR__ . '/../../includes/dashboard_footer.php'; ?>
