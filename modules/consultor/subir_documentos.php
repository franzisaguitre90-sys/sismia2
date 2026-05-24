<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../includes/functions.php';
requireRole('consultor');

$uid       = currentUserId();
$pageTitle = 'Subir Documentos';
$error = '';
$success = '';

// Hojas activas del consultor
$misHojas = db()->prepare("SELECT id,codigo,modalidad,estado FROM hojas_de_ruta WHERE consultor_id=? AND estado NOT IN ('rechazado','finalizado') ORDER BY fecha_ingreso DESC");
$misHojas->execute([$uid]);
$misHojas = $misHojas->fetchAll();

$hojaId = (int)($_GET['hoja'] ?? $_POST['hoja_id'] ?? 0);
$hoja   = null;

if ($hojaId) {
    $sh = db()->prepare("SELECT * FROM hojas_de_ruta WHERE id=? AND consultor_id=?");
    $sh->execute([$hojaId, $uid]);
    $hoja = $sh->fetch();
}

// Subida de archivo
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $hoja && isset($_FILES['archivo'])) {
    $file      = $_FILES['archivo'];
    $tipoDocId = (int)($_POST['tipo_documento_id'] ?? 0);
    $desc      = sanitize($_POST['descripcion'] ?? $file['name']);

    if ($file['error'] !== UPLOAD_ERR_OK) {
        $error = 'Error al subir el archivo. Verifique el tamaño y formato.';
    } elseif ($file['size'] > MAX_FILE_SIZE) {
        $error = 'El archivo supera el tamaño máximo permitido (20MB).';
    } else {
        $allowed = ['pdf','jpg','jpeg','png','doc','docx'];
        $ext     = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        $allowedMimes = [
            'application/pdf', 
            'image/jpeg', 
            'image/png', 
            'application/msword', 
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
        ];

        // Specific restriction for Plano de ubicacion
        $isPlano = false;
        $tipoNombre = db()->query("SELECT nombre FROM tipos_documento WHERE id=".(int)$tipoDocId)->fetchColumn();
        if ($tipoNombre && stripos($tipoNombre, 'plano') !== false) {
            $isPlano = true;
        }

        if (!in_array($ext, $allowed) || !in_array($mime, $allowedMimes)) {
            $error = "Formato o contenido no permitido. Use: " . implode(', ', $allowed);
        } elseif ($isPlano && !in_array($ext, ['pdf','png','doc','docx'])) {
            $error = "Para el Plano de Ubicación y Distribución solo se permite formato PDF, PNG o DOC/DOCX.";
        } else {
            $res = uploadDoc($file, $hojaId, $uid, $tipoDocId ?: null, $desc);
            if ($res['ok']) {
                $success = "Documento cargado exitosamente.";
                audit($uid, 'SUBIR_DOC', 'consultor', "Doc subido a HDR $hojaId", $res['id']);
                // Notificar al técnico si está asignado
                if ($hoja['tecnico_id']) {
                    notificar($hoja['tecnico_id'], 'Nuevo Documento Cargado', "El consultor subió un documento a la Hoja de Ruta {$hoja['codigo']}.", 'info', 'fas fa-file-upload', 'hoja_ruta', $hojaId);
                }
            } else {
                $error = $res['error'];
            }
        }
    }
}

// Eliminar documento
if (isset($_GET['del']) && $hoja) {
    $delId = (int)$_GET['del'];
    $sd = db()->prepare("SELECT * FROM documentos WHERE id=? AND hoja_ruta_id=? AND subido_por=? AND estado='pendiente'");
    $sd->execute([$delId, $hojaId, $uid]);
    $doc = $sd->fetch();
    if ($doc) {
        $fullPath = __DIR__ . '/../../' . $doc['archivo_ruta'];
        if (file_exists($fullPath)) unlink($fullPath);
        db()->prepare("DELETE FROM documentos WHERE id=?")->execute([$delId]);
        $success = 'Documento eliminado.';
    }
}

// Documentos cargados
$docs = [];
$tipos = db()->query("SELECT * FROM tipos_documento WHERE activo=1 ORDER BY orden")->fetchAll();
if ($hoja) {
    $sd = db()->prepare("SELECT d.*, td.nombre tipo_nombre FROM documentos d LEFT JOIN tipos_documento td ON d.tipo_documento_id=td.id WHERE d.hoja_ruta_id=? ORDER BY d.fecha_subida DESC");
    $sd->execute([$hojaId]);
    $docs = $sd->fetchAll();
}

require_once __DIR__ . '/../../includes/dashboard_header.php';
?>

<!-- Selector de hoja de ruta -->
<div class="table-card p-3 mb-4">
  <div class="d-flex align-items-center gap-3 flex-wrap">
    <label class="fw-bold mb-0 text-nowrap"><i class="fas fa-route me-1"></i>Hoja de Ruta:</label>
    <select class="form-select" style="max-width:350px" onchange="location.href='?hoja='+this.value">
      <option value="">— Seleccione una hoja de ruta —</option>
      <?php foreach ($misHojas as $h): ?>
      <option value="<?= $h['id'] ?>" <?= $hojaId===$h['id']?'selected':'' ?>>
        <?= e($h['codigo']) ?> — <?= $h['estado'] ?>
      </option>
      <?php endforeach; ?>
    </select>
  </div>
</div>

<?php if (!$hoja && $hojaId): ?>
<div class="alert alert-danger rounded-3">Hoja de ruta no encontrada o no tiene acceso.</div>
<?php elseif ($hoja): ?>

<?php if ($error): ?><div class="alert alert-danger rounded-3"><i class="fas fa-exclamation-circle me-2"></i><?= e($error) ?></div><?php endif; ?>
<?php if ($success): ?><div class="alert alert-success rounded-3"><i class="fas fa-check-circle me-2"></i><?= e($success) ?></div><?php endif; ?>

<!-- Info HDR -->
<div class="alert alert-info rounded-3 mb-4">
  <strong>Hoja de Ruta:</strong> <code><?= e($hoja['codigo']) ?></code>
  &nbsp;|&nbsp; <?= estadoBadge($hoja['estado']) ?>
  &nbsp;|&nbsp; <?= $hoja['modalidad']==='nuevo_rai'?'Nuevo RAI':'RAI Asignado' ?>
  <?php if ($hoja['observaciones_tecnico']): ?>
  <div class="mt-2 p-2 bg-warning-subtle rounded"><strong>Obs. Técnico:</strong> <?= e($hoja['observaciones_tecnico']) ?></div>
  <?php endif; ?>
</div>

<div class="row g-4">
  <!-- Formulario de carga -->
  <div class="col-lg-5">
    <div class="table-card p-4">
      <h5 class="fw-bold mb-4"><i class="fas fa-upload me-2 text-danger"></i>Cargar Documento</h5>
      <form method="POST" enctype="multipart/form-data" id="uploadForm">
        <input type="hidden" name="hoja_id" value="<?= $hoja['id'] ?>">
        <div class="mb-3">
          <label class="form-label fw-semibold">Tipo de Documento</label>
          <select name="tipo_documento_id" class="form-select">
            <option value="">— Seleccione el tipo —</option>
            <?php foreach ($tipos as $t): ?>
            <?php if ($t['modalidad']==='ambos' || $t['modalidad']===$hoja['modalidad']): ?>
            <option value="<?= $t['id'] ?>"><?= e($t['nombre']) ?><?= $t['requerido']?' *':'' ?></option>
            <?php endif; ?>
            <?php endforeach; ?>
          </select>
          <div class="form-text text-danger">* = Documento obligatorio</div>
        </div>
        <div class="mb-3">
          <label class="form-label fw-semibold">Descripción del Documento</label>
          <input name="descripcion" class="form-control" placeholder="Ej: Formulario RAI firmado y sellado">
        </div>
        <div class="mb-3">
          <label class="form-label fw-semibold">Seleccionar Archivo <span class="text-danger">*</span></label>
          <div class="upload-zone" id="uploadZone">
            <input type="file" name="archivo" id="fileInput" class="d-none" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" required>
            <div id="uploadPrompt" onclick="document.getElementById('fileInput').click()">
              <i class="fas fa-cloud-upload-alt fa-2x text-muted mb-2"></i>
              <div class="text-muted">Haga clic o arrastre un archivo aquí</div>
              <div class="text-muted small">PDF, JPG, PNG, DOC – Máx. 20MB</div>
            </div>
            <div id="filePreview" class="d-none text-center">
              <i class="fas fa-file-check fa-2x text-success mb-1"></i>
              <div class="fw-semibold" id="fileName"></div>
              <div class="text-muted small" id="fileSize"></div>
              <button type="button" class="btn btn-xs btn-outline-danger mt-1" onclick="clearFile()" style="font-size:.75rem">Cambiar</button>
            </div>
          </div>
        </div>
        <div class="d-grid">
          <button type="submit" class="btn btn-danger fw-bold">
            <i class="fas fa-upload me-2"></i>Subir Documento
          </button>
        </div>
      </form>

      <!-- Checklist de documentos -->
      <div class="mt-4">
        <h6 class="fw-bold"><i class="fas fa-list-check me-1"></i>Checklist de Documentos</h6>
        <?php
        $tiposRequeridos = array_filter($tipos, fn($t) => $t['requerido'] && ($t['modalidad']==='ambos' || $t['modalidad']===$hoja['modalidad']));
        $tiposIdCargados = array_column($docs, 'tipo_documento_id');
        foreach ($tiposRequeridos as $t):
          $cargado = in_array($t['id'], $tiposIdCargados);
        ?>
        <div class="d-flex align-items-center gap-2 mb-1 small">
          <i class="fas fa-<?= $cargado?'check-circle text-success':'circle text-muted' ?>"></i>
          <span class="<?= $cargado?'text-success':'' ?>"><?= e($t['nombre']) ?></span>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <!-- Lista de documentos cargados -->
  <div class="col-lg-7">
    <div class="table-card">
      <div class="table-header">
        <h5 class="fw-bold mb-0"><i class="fas fa-folder-open me-2 text-warning"></i>Documentos Cargados (<?= count($docs) ?>)</h5>
      </div>
      <?php if (empty($docs)): ?>
      <div class="text-center py-5 text-muted">
        <i class="fas fa-file-upload fa-3x mb-3 d-block"></i>
        Aún no ha cargado documentos para esta hoja de ruta.
      </div>
      <?php else: ?>
      <div class="list-group list-group-flush">
        <?php foreach ($docs as $d):
          $stBadge = ['pendiente'=>'secondary','en_revision'=>'info text-dark','aprobado'=>'success','rechazado'=>'danger','observado'=>'warning text-dark'][$d['estado']]??'secondary';
          $ext = strtolower(pathinfo($d['archivo_nombre_original'], PATHINFO_EXTENSION));
          $icon = match($ext) { 'pdf'=>'fa-file-pdf text-danger', 'jpg','jpeg','png'=>'fa-file-image text-primary', 'doc','docx'=>'fa-file-word text-primary', default=>'fa-file text-secondary' };
        ?>
        <div class="list-group-item py-3">
          <div class="d-flex align-items-start gap-3">
            <i class="fas <?= $icon ?> fa-lg mt-1 flex-shrink-0"></i>
            <div class="flex-1 min-w-0">
              <div class="fw-semibold small text-truncate"><?= e($d['nombre_descripcion']) ?></div>
              <div class="text-muted" style="font-size:.75rem">
                <?= $d['tipo_nombre']?e($d['tipo_nombre']):'Sin tipo' ?>
                &nbsp;·&nbsp; <?= e($d['archivo_nombre_original']) ?>
                &nbsp;·&nbsp; <?= round($d['archivo_size']/1024,1) ?> KB
              </div>
              <div class="text-muted" style="font-size:.72rem"><?= fdate($d['fecha_subida'],'d/m/Y H:i') ?></div>
              <?php if ($d['observaciones']): ?>
              <div class="alert alert-warning py-1 px-2 mb-0 mt-1 small"><?= e($d['observaciones']) ?></div>
              <?php endif; ?>
            </div>
            <div class="d-flex flex-column gap-1 align-items-end flex-shrink-0">
              <span class="badge bg-<?= $stBadge ?>"><?= ucfirst($d['estado']) ?></span>
              <a href="/SMIA2/<?= $d['archivo_ruta'] ?>" target="_blank" class="btn btn-xs btn-outline-primary" style="font-size:.72rem;padding:.2rem .5rem">
                <i class="fas fa-eye"></i> Ver
              </a>
              <?php if ($d['estado']==='pendiente'): ?>
              <a href="?hoja=<?= $hojaId ?>&del=<?= $d['id'] ?>" class="btn btn-xs btn-outline-danger" style="font-size:.72rem;padding:.2rem .5rem"
                 onclick="return confirm('¿Eliminar este documento?')">
                <i class="fas fa-trash"></i>
              </a>
              <?php endif; ?>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php endif; ?>

<style>
.upload-zone{border:2px dashed #ddd;border-radius:12px;padding:2rem;text-align:center;cursor:pointer;transition:all .2s}
.upload-zone:hover,.upload-zone.dragover{border-color:var(--c-pri);background:var(--c-acc)}
</style>
<script>
const zone = document.getElementById('uploadZone');
const inp  = document.getElementById('fileInput');

inp?.addEventListener('change', showFile);
zone?.addEventListener('dragover', e => { e.preventDefault(); zone.classList.add('dragover'); });
zone?.addEventListener('dragleave', () => zone.classList.remove('dragover'));
zone?.addEventListener('drop', e => {
  e.preventDefault(); zone.classList.remove('dragover');
  if (e.dataTransfer.files.length) { inp.files = e.dataTransfer.files; showFile(); }
});

function showFile() {
  const f = inp.files[0];
  if (!f) return;
  document.getElementById('uploadPrompt').classList.add('d-none');
  document.getElementById('filePreview').classList.remove('d-none');
  document.getElementById('fileName').textContent = f.name;
  document.getElementById('fileSize').textContent = (f.size/1024).toFixed(1) + ' KB';
}

function clearFile() {
  inp.value = '';
  document.getElementById('uploadPrompt').classList.remove('d-none');
  document.getElementById('filePreview').classList.add('d-none');
}
</script>

<?php require_once __DIR__ . '/../../includes/dashboard_footer.php'; ?>
