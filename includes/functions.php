<?php
require_once __DIR__ . '/../config/database.php';

// ── Generadores de códigos ──────────────────────────────────

function generateHojaRutaCode(): string {
    $year = date('Y');
    $stmt = db()->prepare("SELECT COUNT(*) FROM hojas_de_ruta WHERE YEAR(fecha_ingreso)=?");
    $stmt->execute([$year]);
    $seq = (int)$stmt->fetchColumn() + 1;
    return sprintf("HDR-%s-%06d", $year, $seq);
}

function generateClaveUnica(string $prefix = 'SMIA'): string {
    do {
        $key = $prefix . '-' . strtoupper(bin2hex(random_bytes(4)));
        $s = db()->prepare("SELECT COUNT(*) FROM usuarios WHERE clave_unica=?");
        $s->execute([$key]);
    } while ((int)$s->fetchColumn() > 0);
    return $key;
}

// ── Notificaciones ──────────────────────────────────────────

function notificar(int $userId, string $titulo, string $msg, string $tipo = 'info',
                   string $icono = 'fas fa-bell', string $refTipo = null,
                   int $refId = null, string $url = null): void {
    db()->prepare(
        "INSERT INTO notificaciones (usuario_id,titulo,mensaje,tipo,icono,referencia_tipo,referencia_id,accion_url)
         VALUES (?,?,?,?,?,?,?,?)"
    )->execute([$userId, $titulo, $msg, $tipo, $icono, $refTipo, $refId, $url]);
}

function countUnread(int $userId): int {
    $s = db()->prepare("SELECT COUNT(*) FROM notificaciones WHERE usuario_id=? AND leida=0");
    $s->execute([$userId]);
    return (int)$s->fetchColumn();
}

function getUnread(int $userId, int $limit = 10): array {
    $s = db()->prepare(
        "SELECT * FROM notificaciones WHERE usuario_id=? AND leida=0
         ORDER BY fecha_creacion DESC LIMIT ?"
    );
    $s->execute([$userId, $limit]);
    return $s->fetchAll();
}

function markNotifRead(int $notifId, int $userId): void {
    db()->prepare(
        "UPDATE notificaciones SET leida=1, fecha_lectura=NOW() WHERE id=? AND usuario_id=?"
    )->execute([$notifId, $userId]);
}

function markAllRead(int $userId): void {
    db()->prepare(
        "UPDATE notificaciones SET leida=1, fecha_lectura=NOW() WHERE usuario_id=? AND leida=0"
    )->execute([$userId]);
}

// ── Alarmas de vencimiento ──────────────────────────────────

function checkAlarms(): void {
    $stmt = db()->query(
        "SELECT hdr.*, uc.nombre as c_nombre, ut.id as t_id
         FROM hojas_de_ruta hdr
         JOIN usuarios uc ON hdr.consultor_id = uc.id
         LEFT JOIN usuarios ut ON hdr.tecnico_id = ut.id
         WHERE hdr.estado NOT IN ('finalizado','rechazado')
           AND hdr.fecha_limite IS NOT NULL
           AND hdr.alerta_enviada = 0
           AND DATEDIFF(hdr.fecha_limite, NOW()) <= hdr.dias_alerta
           AND DATEDIFF(hdr.fecha_limite, NOW()) >= 0"
    );
    foreach ($stmt->fetchAll() as $h) {
        $dias = max(0, (int)((strtotime($h['fecha_limite']) - time()) / 86400));
        $msg  = "La Hoja de Ruta {$h['codigo']} vence en $dias día(s). Revise el estado del trámite.";
        notificar($h['consultor_id'], '⚠️ Alerta de Vencimiento', $msg, 'alarm', 'fas fa-exclamation-triangle', 'hoja_ruta', $h['id']);
        if ($h['tecnico_id']) {
            notificar($h['tecnico_id'], '⚠️ Alerta de Vencimiento', $msg, 'alarm', 'fas fa-exclamation-triangle', 'hoja_ruta', $h['id']);
        }
        db()->prepare("UPDATE hojas_de_ruta SET alerta_enviada=1 WHERE id=?")->execute([$h['id']]);
    }
}

// ── Historial de estados ────────────────────────────────────

function registrarEstado(int $hojaId, ?string $anterior, string $nuevo, int $userId, string $obs = null): void {
    db()->prepare(
        "INSERT INTO historial_estados (hoja_ruta_id,estado_anterior,estado_nuevo,usuario_id,observaciones)
         VALUES (?,?,?,?,?)"
    )->execute([$hojaId, $anterior, $nuevo, $userId, $obs]);
}

function getHistorial(int $hojaId): array {
    $s = db()->prepare(
        "SELECT h.*, u.nombre, u.apellido, r.nombre as rol_nombre, r.color_primary
         FROM historial_estados h
         JOIN usuarios u ON h.usuario_id = u.id
         JOIN roles r ON u.rol_id = r.id
         WHERE h.hoja_ruta_id = ?
         ORDER BY h.fecha DESC"
    );
    $s->execute([$hojaId]);
    return $s->fetchAll();
}

// ── KPI técnico ─────────────────────────────────────────────

function updateKPI(int $tecnicoId): void {
    $mes  = (int)date('m');
    $anio = (int)date('Y');
    $s = db()->prepare(
        "SELECT COUNT(*) total,
                SUM(estado='finalizado') completados,
                SUM(estado='observado') observados,
                SUM(estado='rechazado') rechazados,
                SUM(estado NOT IN ('finalizado','rechazado','ingresado')) pendientes,
                AVG(DATEDIFF(IFNULL(fecha_finalizacion,NOW()),fecha_asignacion)) tiempo_prom,
                (SELECT COUNT(*) FROM documentos d JOIN hojas_de_ruta h2 ON d.hoja_ruta_id=h2.id
                 WHERE h2.tecnico_id=? AND MONTH(d.fecha_revision)=? AND YEAR(d.fecha_revision)=?
                   AND d.fecha_revision IS NOT NULL) docs_rev
         FROM hojas_de_ruta
         WHERE tecnico_id=? AND MONTH(IFNULL(fecha_asignacion,fecha_ingreso))=? AND YEAR(IFNULL(fecha_asignacion,fecha_ingreso))=?"
    );
    $s->execute([$tecnicoId, $mes, $anio, $tecnicoId, $mes, $anio]);
    $d = $s->fetch();
    $ef = $d['total'] > 0 ? round(($d['completados'] / $d['total']) * 100, 2) : 0;
    db()->prepare(
        "INSERT INTO kpi_tecnicos (tecnico_id,periodo_mes,periodo_anio,tramites_asignados,tramites_completados,
          tramites_observados,tramites_rechazados,tramites_pendientes,documentos_revisados,tiempo_promedio_dias,porcentaje_eficiencia)
         VALUES (?,?,?,?,?,?,?,?,?,?,?)
         ON DUPLICATE KEY UPDATE
          tramites_asignados=VALUES(tramites_asignados),tramites_completados=VALUES(tramites_completados),
          tramites_observados=VALUES(tramites_observados),tramites_rechazados=VALUES(tramites_rechazados),
          tramites_pendientes=VALUES(tramites_pendientes),documentos_revisados=VALUES(documentos_revisados),
          tiempo_promedio_dias=VALUES(tiempo_promedio_dias),porcentaje_eficiencia=VALUES(porcentaje_eficiencia)"
    )->execute([$tecnicoId,$mes,$anio,$d['total']??0,$d['completados']??0,$d['observados']??0,
                $d['rechazados']??0,$d['pendientes']??0,$d['docs_rev']??0,round($d['tiempo_prom']??0,2),$ef]);
}

// ── Auditoría ───────────────────────────────────────────────

function audit(int $userId, string $accion, string $modulo, string $desc = null, int $regId = null): void {
    db()->prepare(
        "INSERT INTO auditoria (usuario_id,username,accion,modulo,registro_id,descripcion,ip_address,user_agent)
         VALUES (?,?,?,?,?,?,?,?)"
    )->execute([
        $userId,
        $_SESSION['user']['username'] ?? 'system',
        $accion, $modulo, $regId, $desc,
        $_SERVER['REMOTE_ADDR'] ?? null,
        substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
    ]);
}

// ── Helpers UI ──────────────────────────────────────────────

function estadoBadge(string $estado): string {
    $map = [
        'ingresado'   => ['bg-secondary',          'Ingresado'],
        'asignado'    => ['bg-info text-dark',      'Asignado'],
        'en_revision' => ['bg-warning text-dark',   'En Revisión'],
        'observado'   => ['bg-orange text-white',   'Observado'],
        'aprobado'    => ['bg-success',             'Aprobado'],
        'rechazado'   => ['bg-danger',              'Rechazado'],
        'finalizado'  => ['bg-dark',                'Finalizado'],
        'borrador'    => ['bg-light text-dark',     'Borrador'],
        'enviado'     => ['bg-primary',             'Enviado'],
        'con_observaciones' => ['bg-warning text-dark','Con Obs.'],
        'habilitado'  => ['bg-success',             'Habilitado'],
        'no_habilitado'=> ['bg-danger',             'No Habilitado'],
        'con_condiciones' => ['bg-warning text-dark','Con Condiciones'],
    ];
    $class = $map[$estado][0] ?? 'bg-secondary';
    $label = $map[$estado][1] ?? ucfirst(str_replace('_',' ',$estado));
    $style = ($estado === 'observado') ? ' style="background:#f4511e"' : '';
    return "<span class='badge $class'$style>$label</span>";
}

function prioridadBadge(string $p): string {
    $map = ['baja'=>'bg-success','media'=>'bg-info text-dark','alta'=>'bg-warning text-dark','urgente'=>'bg-danger'];
    return "<span class='badge ".($map[$p]??'bg-secondary')."'>".ucfirst($p)."</span>";
}

function fdate(?string $d, string $fmt = 'd/m/Y H:i'): string {
    return $d ? date($fmt, strtotime($d)) : '—';
}

function diasRestantes(?string $fechaLimite): ?int {
    if (!$fechaLimite) return null;
    return (int)ceil((strtotime($fechaLimite) - time()) / 86400);
}

function e(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

function sanitize(string $s): string {
    return trim(strip_tags($s));
}

// ── Usuarios y roles ────────────────────────────────────────

function getTecnicos(): array {
    $s = db()->query("SELECT id, CONCAT(nombre,' ',apellido) nombre_completo FROM usuarios WHERE rol_id=(SELECT id FROM roles WHERE slug='tecnico') AND activo=1 ORDER BY nombre");
    return $s->fetchAll();
}

function getUserById(int $id): ?array {
    $s = db()->prepare("SELECT u.*,r.slug as rol,r.nombre as rol_nombre,r.color_primary,r.color_secondary,r.color_accent FROM usuarios u JOIN roles r ON u.rol_id=r.id WHERE u.id=?");
    $s->execute([$id]);
    return $s->fetch() ?: null;
}

// ── Upload de archivos ──────────────────────────────────────

function uploadDoc(array $file, int $hojaRutaId, int $userId, int $tipoDocId = null, string $desc = ''): array {
    $dir = UPLOAD_DIR . $hojaRutaId . '/';
    if (!is_dir($dir)) mkdir($dir, 0755, true);

    $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $safe = date('YmdHis') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $dest = $dir . $safe;

    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        return ['ok' => false, 'error' => 'No se pudo guardar el archivo.'];
    }

    $relPath = 'uploads/' . $hojaRutaId . '/' . $safe;
    $hash    = hash_file('sha256', $dest);

    $stmt = db()->prepare(
        "INSERT INTO documentos (hoja_ruta_id,tipo_documento_id,nombre_descripcion,archivo_ruta,
          archivo_nombre_original,archivo_size,mime_type,hash_sha256,subido_por)
         VALUES (?,?,?,?,?,?,?,?,?)"
    );
    $stmt->execute([
        $hojaRutaId, $tipoDocId ?: null,
        $desc ?: $file['name'],
        $relPath, $file['name'], $file['size'],
        $file['type'], $hash, $userId,
    ]);

    return ['ok' => true, 'id' => (int)db()->lastInsertId(), 'ruta' => $relPath];
}
