<?php
// Requiere: $pageTitle, session iniciada, rol autenticado
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/functions.php';

$u            = currentUser();
$rol          = $u['rol'] ?? 'consultor';
$colorPri     = $u['color_primary']   ?? '#1a237e';
$colorSec     = $u['color_secondary'] ?? '#283593';
$colorAcc     = $u['color_accent']    ?? '#e8eaf6';
$unreadCount  = countUnread($u['id']);

checkAlarms();

$menus = [
    'admin' => [
        ['icon'=>'fas fa-tachometer-alt','label'=>'Dashboard','url'=>'/SMIA2/dashboard/admin.php'],
        ['icon'=>'fas fa-users','label'=>'Gestión Usuarios','url'=>'/SMIA2/modules/director/crear_tecnico.php'],
        ['icon'=>'fas fa-route','label'=>'Hojas de Ruta','url'=>'/SMIA2/modules/director/hojas_ruta.php'],
        ['icon'=>'fas fa-newspaper','label'=>'Noticias','url'=>'/SMIA2/modules/admin/noticias.php'],
    ],
    'director' => [
        ['icon'=>'fas fa-tachometer-alt','label'=>'Dashboard','url'=>'/SMIA2/dashboard/director.php'],
        ['icon'=>'fas fa-route','label'=>'Hojas de Ruta','url'=>'/SMIA2/modules/director/hojas_ruta.php'],
        ['icon'=>'fas fa-user-check','label'=>'Asignar Trámites','url'=>'/SMIA2/modules/director/asignar.php'],
        ['icon'=>'fas fa-chart-bar','label'=>'KPI Técnicos','url'=>'/SMIA2/modules/director/kpi.php'],
        ['icon'=>'fas fa-user-plus','label'=>'Crear Técnico','url'=>'/SMIA2/modules/director/crear_tecnico.php'],
    ],
    'tecnico' => [
        ['icon'=>'fas fa-tachometer-alt','label'=>'Dashboard','url'=>'/SMIA2/dashboard/tecnico.php'],
        ['icon'=>'fas fa-folder-open','label'=>'Mis Trámites','url'=>'/SMIA2/modules/tecnico/mis_tramites.php'],
        ['icon'=>'fas fa-file-alt','label'=>'Revisar Documentos','url'=>'/SMIA2/modules/tecnico/revisar_doc.php'],
        ['icon'=>'fas fa-calendar-alt','label'=>'Mis Turnos','url'=>'/SMIA2/modules/tecnico/turnos.php'],
        ['icon'=>'fas fa-bell','label'=>'Recordatorios','url'=>'/SMIA2/modules/tecnico/recordatorios.php'],
    ],
    'consultor' => [
        ['icon'=>'fas fa-tachometer-alt','label'=>'Dashboard','url'=>'/SMIA2/dashboard/consultor.php'],
        ['icon'=>'fas fa-plus-circle','label'=>'Nueva Hoja de Ruta','url'=>'/SMIA2/modules/consultor/nueva_hoja_ruta.php'],
        ['icon'=>'fas fa-wpforms','label'=>'Formulario RAI','url'=>'/SMIA2/modules/consultor/formulario_rai.php'],
        ['icon'=>'fas fa-upload','label'=>'Subir Documentos','url'=>'/SMIA2/modules/consultor/subir_documentos.php'],
        ['icon'=>'fas fa-list-alt','label'=>'Mis Trámites','url'=>'/SMIA2/modules/consultor/mis_tramites.php'],
    ],
    'secretaria' => [
        ['icon'=>'fas fa-tachometer-alt','label'=>'Dashboard','url'=>'/SMIA2/dashboard/secretaria.php'],
        ['icon'=>'fas fa-clipboard-check','label'=>'Trámites Aprobados','url'=>'/SMIA2/modules/secretaria/habilitaciones.php'],
        ['icon'=>'fas fa-certificate','label'=>'Habilitaciones RAI','url'=>'/SMIA2/modules/secretaria/registro_final.php'],
        ['icon'=>'fas fa-search','label'=>'Verificar Resultados','url'=>'/SMIA2/modules/secretaria/verificar.php'],
    ],
];
$menu = $menus[$rol] ?? $menus['consultor'];
$currentUrl = $_SERVER['REQUEST_URI'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= e($pageTitle ?? 'SMIA2') ?> | SMIA2</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js">
<style>
:root {
  --c-pri: <?= e($colorPri) ?>;
  --c-sec: <?= e($colorSec) ?>;
  --c-acc: <?= e($colorAcc) ?>;
  --sidebar-w: 260px;
}
*{box-sizing:border-box}
body{font-family:'Segoe UI',sans-serif;background:#f5f5f5;margin:0}

/* SIDEBAR */
.sidebar{position:fixed;top:0;left:0;height:100%;width:var(--sidebar-w);background:linear-gradient(180deg,var(--c-pri),var(--c-sec));z-index:1000;transition:transform .3s;display:flex;flex-direction:column}
.sidebar-brand{padding:1.5rem 1.25rem;border-bottom:1px solid rgba(255,255,255,.1);display:flex;align-items:center;gap:.75rem}
.sidebar-brand-icon{width:40px;height:40px;background:rgba(255,255,255,.15);border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.1rem;color:white;flex-shrink:0}
.sidebar-brand-text{color:white}
.sidebar-brand-text .title{font-size:1.1rem;font-weight:800;line-height:1}
.sidebar-brand-text .sub{font-size:.65rem;opacity:.6;text-transform:uppercase;letter-spacing:.8px}
.sidebar-user{padding:1rem 1.25rem;border-bottom:1px solid rgba(255,255,255,.1);display:flex;align-items:center;gap:.75rem}
.sidebar-avatar{width:40px;height:40px;border-radius:50%;background:rgba(255,255,255,.2);display:flex;align-items:center;justify-content:center;font-size:1rem;color:white;font-weight:700;flex-shrink:0}
.sidebar-user-info{flex:1;min-width:0}
.sidebar-user-info .name{color:white;font-size:.85rem;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.sidebar-user-info .role{color:rgba(255,255,255,.6);font-size:.7rem}
.sidebar-nav{padding:1rem 0;flex:1;overflow-y:auto}
.nav-item-side{padding:0 .75rem;margin-bottom:.15rem}
.nav-link-side{display:flex;align-items:center;gap:.75rem;padding:.7rem 1rem;border-radius:10px;color:rgba(255,255,255,.75);text-decoration:none;font-size:.875rem;transition:all .2s}
.nav-link-side:hover,.nav-link-side.active{background:rgba(255,255,255,.15);color:white}
.nav-link-side.active{background:rgba(255,255,255,.2);font-weight:600}
.nav-link-side i{width:18px;text-align:center;font-size:.9rem}
.sidebar-footer{padding:.75rem 1.25rem;border-top:1px solid rgba(255,255,255,.1)}
.sidebar-footer a{color:rgba(255,255,255,.6);text-decoration:none;font-size:.8rem;display:flex;align-items:center;gap:.5rem}
.sidebar-footer a:hover{color:white}

/* MAIN CONTENT */
.main-wrap{margin-left:var(--sidebar-w);display:flex;flex-direction:column;min-height:100vh;transition:margin .3s}
.topbar{background:white;height:64px;padding:0 1.5rem;display:flex;align-items:center;justify-content:space-between;box-shadow:0 1px 4px rgba(0,0,0,.08);position:sticky;top:0;z-index:500}
.topbar-left{display:flex;align-items:center;gap:.75rem}
.btn-toggle-sidebar{background:none;border:none;padding:.5rem;border-radius:8px;cursor:pointer;color:#555}
.btn-toggle-sidebar:hover{background:#f0f0f0}
.page-title-top{font-size:1rem;font-weight:700;color:#333}
.topbar-right{display:flex;align-items:center;gap.75rem}
.notif-btn{position:relative;background:none;border:none;width:40px;height:40px;border-radius:50%;display:flex;align-items:center;justify-content:center;cursor:pointer;color:#555}
.notif-btn:hover{background:#f5f5f5}
.notif-badge{position:absolute;top:4px;right:4px;background:var(--c-pri);color:white;border-radius:50%;width:18px;height:18px;font-size:.65rem;display:flex;align-items:center;justify-content:center;font-weight:700}
.content-area{flex:1;padding:1.5rem}

/* CARDS */
.stat-card{background:white;border-radius:16px;padding:1.5rem;box-shadow:0 2px 8px rgba(0,0,0,.06);border-left:4px solid var(--c-pri);height:100%;transition:transform .2s,box-shadow .2s}
.stat-card:hover{transform:translateY(-3px);box-shadow:0 8px 24px rgba(0,0,0,.12)}
.stat-icon{width:56px;height:56px;border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:1.5rem;margin-bottom:1rem}
.stat-num{font-size:2rem;font-weight:800;color:#333;line-height:1}
.stat-label{color:#777;font-size:.85rem;margin-top:.25rem}

/* TABLES */
.table-card{background:white;border-radius:16px;box-shadow:0 2px 8px rgba(0,0,0,.06);overflow:hidden}
.table-card .table{margin:0}
.table-card .table th{background:var(--c-acc);color:var(--c-pri);font-weight:700;font-size:.8rem;text-transform:uppercase;letter-spacing:.5px;border:none;padding:.9rem 1rem}
.table-card .table td{padding:.85rem 1rem;vertical-align:middle;font-size:.875rem;border-color:#f0f0f0}
.table-card .table tr:hover td{background:#fafafa}
.table-header{padding:1rem 1.5rem;display:flex;align-items:center;justify-content:space-between;border-bottom:1px solid #f0f0f0}

/* ALARM */
.alarm-bar{background:linear-gradient(135deg,#ff6f00,#e65100);color:white;padding:.5rem 1.5rem;font-size:.85rem;display:flex;align-items:center;gap:.5rem}

/* NOTIF DRAWER */
.notif-drawer{position:fixed;top:64px;right:0;width:360px;height:calc(100vh - 64px);background:white;box-shadow:-4px 0 20px rgba(0,0,0,.1);z-index:900;transform:translateX(100%);transition:transform .3s;display:flex;flex-direction:column}
.notif-drawer.open{transform:translateX(0)}
.notif-drawer-header{padding:1rem 1.5rem;border-bottom:1px solid #f0f0f0;display:flex;align-items:center;justify-content:space-between}
.notif-list{flex:1;overflow-y:auto;padding:.5rem}
.notif-item{padding:.75rem 1rem;border-radius:10px;margin-bottom:.25rem;cursor:pointer;transition:background .2s;border-left:3px solid transparent}
.notif-item:hover{background:#f5f5f5}
.notif-item.unread{background:#fafafa;border-left-color:var(--c-pri)}
.notif-item.alarm{border-left-color:#f57c00}
.notif-item.danger{border-left-color:#d32f2f}
.notif-item.success{border-left-color:#2e7d32}
.notif-time{font-size:.7rem;color:#999}

/* RESPONSIVE */
@media(max-width:768px){
  .sidebar{transform:translateX(-100%)}
  .sidebar.open{transform:translateX(0)}
  .main-wrap{margin-left:0}
  .notif-drawer{width:100%}
}
</style>
</head>
<body>

<!-- SIDEBAR -->
<aside class="sidebar" id="sidebar">
  <div class="sidebar-brand" style="flex-direction: column; text-align: center; gap: 0.5rem; padding-top: 1.5rem;">
    <img src="/SMIA2/utilitarios/ESC-ALTO-300x300.webp" alt="El Alto" style="width: 80px; height: auto; border-radius: 50%; background: white; padding: 4px; box-shadow: 0 4px 8px rgba(0,0,0,0.2);">
    <div class="sidebar-brand-text" style="width: 100%;">
      <div class="title" style="font-size: 1.3rem;">SMIA<span style="color:var(--c-acc)">2</span></div>
      <div class="sub" style="font-size: 0.75rem; letter-spacing: 1px;">Control Ambiental<br>Gobierno Autónomo Municipal de El Alto</div>
    </div>
  </div>
  <div class="sidebar-user">
    <div class="sidebar-avatar"><?= strtoupper($u['nombre'][0] ?? '?') ?></div>
    <div class="sidebar-user-info">
      <div class="name"><?= e($u['nombre'].' '.$u['apellido']) ?></div>
      <div class="role"><?= e($u['rol_nombre'] ?? ucfirst($rol)) ?></div>
    </div>
  </div>
  <nav class="sidebar-nav">
    <?php foreach ($menu as $item): ?>
    <div class="nav-item-side">
      <a href="<?= $item['url'] ?>" class="nav-link-side<?= (strpos($currentUrl, $item['url']) !== false) ? ' active' : '' ?>">
        <i class="<?= $item['icon'] ?>"></i>
        <span><?= $item['label'] ?></span>
      </a>
    </div>
    <?php endforeach; ?>
  </nav>
  <div class="sidebar-footer">
    <a href="/SMIA2/logout.php"><i class="fas fa-sign-out-alt"></i> Cerrar Sesión</a>
  </div>
</aside>

<!-- MAIN WRAP -->
<div class="main-wrap" id="mainWrap">
  <!-- TOPBAR -->
  <header class="topbar">
    <div class="topbar-left">
      <button class="btn-toggle-sidebar" id="sidebarToggle"><i class="fas fa-bars"></i></button>
      <span class="page-title-top"><?= e($pageTitle ?? 'Dashboard') ?></span>
    </div>
    <div class="topbar-right d-flex align-items-center gap-2">
      <button class="notif-btn" id="notifToggle" title="Notificaciones">
        <i class="fas fa-bell"></i>
        <?php if ($unreadCount > 0): ?>
        <span class="notif-badge"><?= min($unreadCount, 99) ?></span>
        <?php endif; ?>
      </button>
      <div class="d-none d-md-flex align-items-center gap-2">
        <div style="width:32px;height:32px;border-radius:50%;background:var(--c-pri);display:flex;align-items:center;justify-content:center;color:white;font-size:.8rem;font-weight:700">
          <?= strtoupper($u['nombre'][0] ?? '?') ?>
        </div>
        <span class="small fw-semibold"><?= e($u['nombre']) ?></span>
      </div>
    </div>
  </header>

  <!-- ALARM BAR para trámites próximos a vencer -->
  <?php
  if (in_array($rol, ['director','tecnico'])) {
    $urgentes = db()->prepare("SELECT COUNT(*) FROM hojas_de_ruta WHERE estado NOT IN ('finalizado','rechazado') AND fecha_limite IS NOT NULL AND DATEDIFF(fecha_limite,NOW()) <= 3 AND DATEDIFF(fecha_limite,NOW()) >= 0" . ($rol==='tecnico' ? " AND tecnico_id={$u['id']}" : ""));
    $urgentes->execute();
    $numUrgentes = $urgentes->fetchColumn();
    if ($numUrgentes > 0): ?>
  <div class="alarm-bar">
    <i class="fas fa-exclamation-triangle"></i>
    <strong>¡Alerta!</strong> <?= $numUrgentes ?> trámite(s) vencen en los próximos 3 días. Revise inmediatamente.
  </div>
  <?php endif; }
  ?>

  <!-- CONTENT AREA -->
  <main class="content-area">
