<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../includes/functions.php';
requireRole('secretaria');

$uid       = currentUserId();
$pageTitle = 'Registro de Habilitación Ambiental';
$success = '';
$error   = '';

$hojaId = (int)($_GET['hoja'] ?? 0);
$hoja   = null;
$rai    = null;

if ($hojaId) {
    $sh = db()->prepare("
        SELECT hdr.*, CONCAT(uc.nombre,' ',uc.apellido) consultor, uc.id consultor_id_usr, uc.clave_unica, uc.email consultor_email,
               CONCAT(ut.nombre,' ',ut.apellido) tecnico
        FROM hojas_de_ruta hdr
        JOIN usuarios uc ON hdr.consultor_id=uc.id
        LEFT JOIN usuarios ut ON hdr.tecnico_id=ut.id
        WHERE hdr.id=? AND hdr.estado='aprobado'
    ");
    $sh->execute([$hojaId]);
    $hoja = $sh->fetch();

    if ($hoja) {
        $sr = db()->prepare("SELECT * FROM formularios_rai WHERE hoja_ruta_id=?");
        $sr->execute([$hojaId]);
        $rai = $sr->fetch();
    }
}

// Procesar habilitación
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $hoja && $rai) {
    $tipo            = sanitize($_POST['tipo_resultado'] ?? 'habilitado');
    $numCert         = sanitize($_POST['numero_certificado'] ?? '');
    $numResol        = sanitize($_POST['numero_resolucion'] ?? '');
    $numRaiOtorgado  = sanitize($_POST['numero_rai_otorgado'] ?? '');
    $fechaEmision    = sanitize($_POST['fecha_emision'] ?? '');
    $vigencia        = max(1, (int)($_POST['vigencia_anios'] ?? 1));
    $condiciones     = sanitize($_POST['condiciones_especiales'] ?? '');
    $obsFinales      = sanitize($_POST['observaciones_finales'] ?? '');
    $motivo          = sanitize($_POST['motivo_no_habilitacion'] ?? '');
    $aprobPor        = sanitize($_POST['aprobado_por_nombre'] ?? '');

    $fechaVenc = $fechaEmision && $tipo === 'habilitado'
        ? date('Y-m-d', strtotime("$fechaEmision +$vigencia years"))
        : null;

    try {
        // Insertar habilitación
        db()->prepare(
            "INSERT INTO habilitaciones_ambientales
             (hoja_ruta_id,formulario_rai_id,secretaria_id,tipo_resultado,numero_certificado,
              numero_resolucion,numero_rai_otorgado,fecha_emision,fecha_vencimiento,vigencia_anios,
              condiciones_especiales,observaciones_finales,motivo_no_habilitacion,aprobado_por_nombre)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)"
        )->execute([$hojaId,$rai['id'],$uid,$tipo,$numCert?:null,$numResol?:null,$numRaiOtorgado?:null,
                    $fechaEmision?:null,$fechaVenc,$vigencia,$condiciones?:null,$obsFinales?:null,$motivo?:null,$aprobPor?:null]);

        // Actualizar RAI con número otorgado
        if ($numRaiOtorgado) db()->prepare("UPDATE formularios_rai SET numero_rai=?,estado_formulario='aprobado' WHERE id=?")->execute([$numRaiOtorgado,$rai['id']]);

        // Finalizar hoja de ruta
        db()->prepare("UPDATE hojas_de_ruta SET estado='finalizado',secretaria_id=?,fecha_finalizacion=NOW() WHERE id=?")->execute([$uid,$hojaId]);
        registrarEstado($hojaId,'aprobado','finalizado',$uid,"Habilitación registrada por secretaria. Tipo: $tipo");

        // Generar y asignar clave única al consultor si no tiene
        $claveUnica = $hoja['clave_unica'];
        if (!$claveUnica) {
            $claveUnica = generateClaveUnica();
            db()->prepare("UPDATE usuarios SET clave_unica=? WHERE id=?")->execute([$claveUnica,$hoja['consultor_id']]);
        }

        // Notificar al consultor
        $msgConsultor = $tipo === 'habilitado'
            ? "¡Felicitaciones! Su trámite {$hoja['codigo']} fue HABILITADO. Número RAI: $numRaiOtorgado. Su clave única de acceso es: $claveUnica"
            : "Su trámite {$hoja['codigo']} no fue habilitado. Motivo: $motivo";
        notificar($hoja['consultor_id'], $tipo==='habilitado'?'¡Trámite Habilitado!':'Trámite No Habilitado', $msgConsultor, $tipo==='habilitado'?'success':'danger', $tipo==='habilitado'?'fas fa-certificate':'fas fa-times-circle', 'hoja_ruta', $hojaId);

        // Notificar a directores
        $dirs = db()->query("SELECT id FROM usuarios WHERE rol_id=(SELECT id FROM roles WHERE slug='director') AND activo=1")->fetchAll();
        foreach ($dirs as $d) notificar($d['id'], "HDR Finalizada: {$hoja['codigo']}", "Trámite finalizado como $tipo por la secretaria.", 'info', 'fas fa-flag-checkered', 'hoja_ruta', $hojaId);

        audit($uid,'REGISTRAR_HAB','secretaria',"HDR {$hoja['codigo']} habilitada como $tipo",$hojaId);
        updateKPI($hoja['tecnico_id'] ?? 0);

        $success = "Habilitación registrada exitosamente. <br>
                    <strong>Tipo:</strong> ".strtoupper($tipo)." <br>
                    <strong>Clave Única del Consultor:</strong> <code class='fs-5'>$claveUnica</code>";
    } catch (PDOException $e) {
        $error = 'Error al registrar la habilitación: ' . $e->getMessage();
    }
}

// Historial de habilitaciones
$habList = db()->query("
    SELECT h.*, hdr.codigo, CONCAT(uc.nombre,' ',uc.apellido) consultor, rai.razon_social
    FROM habilitaciones_ambientales h
    JOIN hojas_de_ruta hdr ON h.hoja_ruta_id=hdr.id
    JOIN usuarios uc ON hdr.consultor_id=uc.id
    LEFT JOIN formularios_rai rai ON rai.hoja_ruta_id=hdr.id
    ORDER BY h.fecha_registro DESC LIMIT 15
")->fetchAll();

require_once __DIR__ . '/../../includes/dashboard_header.php';
?>

<?php if ($error): ?><div class="alert alert-danger rounded-3 mb-3"><i class="fas fa-exclamation-circle me-2"></i><?= e($error) ?></div><?php endif; ?>
<?php if ($success): ?><div class="alert alert-success rounded-3 mb-4"><i class="fas fa-check-circle fa-lg me-2"></i><?= $success ?></div><?php endif; ?>

<div class="row g-4">
  <!-- Formulario de habilitación -->
  <?php if ($hoja && $rai && !$success): ?>
  <div class="col-lg-5">
    <div class="table-card p-4">
      <h5 class="fw-bold mb-3"><i class="fas fa-certificate me-2 text-primary"></i>Registrar Habilitación Ambiental</h5>
      <div class="alert alert-info rounded-3 py-2 mb-3 small">
        <strong>Hoja:</strong> <code><?= e($hoja['codigo']) ?></code>
        &nbsp;|&nbsp; <strong>Empresa:</strong> <?= $rai['razon_social']?e($rai['razon_social']):'—' ?>
        &nbsp;|&nbsp; <strong>Cat.:</strong> <?= $rai['categoria_rai']??'—' ?>
        <br><strong>Consultor:</strong> <?= e($hoja['consultor']) ?> &nbsp;|&nbsp; <strong>Técnico:</strong> <?= $hoja['tecnico']?e($hoja['tecnico']):'—' ?>
      </div>

      <form method="POST">
        <div class="mb-3">
          <label class="form-label fw-bold">Resultado de la Habilitación <span class="text-danger">*</span></label>
          <div class="d-flex gap-3">
            <div class="form-check">
              <input class="form-check-input" type="radio" name="tipo_resultado" id="habSi" value="habilitado" checked onchange="toggleHab(true)">
              <label class="form-check-label text-success fw-bold" for="habSi"><i class="fas fa-check-circle me-1"></i>HABILITADO</label>
            </div>
            <div class="form-check">
              <input class="form-check-input" type="radio" name="tipo_resultado" id="habNo" value="no_habilitado" onchange="toggleHab(false)">
              <label class="form-check-label text-danger fw-bold" for="habNo"><i class="fas fa-times-circle me-1"></i>NO HABILITADO</label>
            </div>
          </div>
        </div>

        <div id="secHabilitado">
          <div class="row g-2 mb-2">
            <div class="col-6"><label class="form-label fw-semibold small">N° RAI Otorgado</label>
              <input name="numero_rai_otorgado" class="form-control" placeholder="RAI-LP-2026-XXXXXX" style="text-transform:uppercase">
            </div>
            <div class="col-6"><label class="form-label fw-semibold small">N° Certificado</label>
              <input name="numero_certificado" class="form-control">
            </div>
          </div>
          <div class="row g-2 mb-2">
            <div class="col-6"><label class="form-label fw-semibold small">N° Resolución</label>
              <input name="numero_resolucion" class="form-control">
            </div>
            <div class="col-3"><label class="form-label fw-semibold small">Vigencia (años)</label>
              <input type="number" name="vigencia_anios" class="form-control" value="1" min="1" max="5">
            </div>
            <div class="col-3"><label class="form-label fw-semibold small">Fecha emisión</label>
              <input type="date" name="fecha_emision" class="form-control" value="<?= date('Y-m-d') ?>">
            </div>
          </div>
          <div class="mb-2"><label class="form-label fw-semibold small">Condiciones Especiales</label>
            <textarea name="condiciones_especiales" class="form-control" rows="2" placeholder="Condiciones para el mantenimiento de la habilitación..."></textarea>
          </div>
        </div>

        <div id="secNoHabilitado" style="display:none">
          <div class="mb-2"><label class="form-label fw-semibold small text-danger">Motivo de No Habilitación <span class="text-danger">*</span></label>
            <textarea name="motivo_no_habilitacion" class="form-control border-danger" rows="3" placeholder="Indique el motivo de rechazo de la habilitación ambiental..."></textarea>
          </div>
        </div>

        <div class="mb-2"><label class="form-label fw-semibold small">Observaciones Finales</label>
          <textarea name="observaciones_finales" class="form-control" rows="2"></textarea>
        </div>
        <div class="mb-3"><label class="form-label fw-semibold small">Aprobado por (Director)</label>
          <input name="aprobado_por_nombre" class="form-control" placeholder="Nombre del Director que aprueba">
        </div>

        <div class="d-grid">
          <button type="submit" class="btn btn-primary btn-lg fw-bold py-2"
                  onclick="return confirm('¿Confirma el registro de la habilitación? Esta acción finalizará el trámite.')">
            <i class="fas fa-certificate me-2"></i>Registrar Habilitación Definitiva
          </button>
        </div>
      </form>
    </div>
  </div>
  <?php else: ?>
  <div class="col-lg-5">
    <div class="table-card p-4">
      <h6 class="fw-bold"><i class="fas fa-search me-2"></i>Seleccionar Trámite</h6>
      <p class="text-muted small">Para registrar una habilitación, vaya a <a href="/SMIA2/modules/secretaria/habilitaciones.php">Trámites Aprobados</a> y seleccione uno.</p>
      <a href="/SMIA2/modules/secretaria/habilitaciones.php" class="btn btn-primary w-100"><i class="fas fa-clipboard-check me-2"></i>Ver Trámites Aprobados</a>
    </div>
  </div>
  <?php endif; ?>

  <!-- Historial de habilitaciones -->
  <div class="col-lg-7">
    <div class="table-card">
      <div class="table-header"><h6 class="fw-bold mb-0"><i class="fas fa-history me-2 text-success"></i>Historial de Habilitaciones</h6></div>
      <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead><tr><th>Código HDR</th><th>Empresa</th><th>Resultado</th><th>N° RAI</th><th>Vencimiento</th><th>Ver</th></tr></thead>
          <tbody>
          <?php if (empty($habList)): ?><tr><td colspan="6" class="text-center text-muted py-4">Sin habilitaciones registradas</td></tr><?php endif; ?>
          <?php foreach ($habList as $h): ?>
          <tr>
            <td><code class="small"><?= e($h['codigo']) ?></code></td>
            <td class="small"><?= $h['razon_social']?e($h['razon_social']):'—' ?></td>
            <td><?= estadoBadge($h['tipo_resultado']) ?></td>
            <td class="small fw-semibold"><?= $h['numero_rai_otorgado']?e($h['numero_rai_otorgado']):'—' ?></td>
            <td class="small">
              <?php if ($h['fecha_vencimiento']):
                $dv = diasRestantes($h['fecha_vencimiento']);
                echo "<span class='".($dv!==null&&$dv<30?'text-danger fw-bold':'')."'>".fdate($h['fecha_vencimiento'],'d/m/Y')."</span>";
              else: echo '—'; endif; ?>
            </td>
            <td><a href="/SMIA2/modules/secretaria/verificar.php?hab=<?= $h['id'] ?>" class="btn btn-xs btn-outline-primary" style="font-size:.72rem;padding:.2rem .5rem"><i class="fas fa-eye"></i></a></td>
          </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<script>
function toggleHab(show) {
  document.getElementById('secHabilitado').style.display = show ? 'block' : 'none';
  document.getElementById('secNoHabilitado').style.display = show ? 'none' : 'block';
}
</script>

<?php require_once __DIR__ . '/../../includes/dashboard_footer.php'; ?>
