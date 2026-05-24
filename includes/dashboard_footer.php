<?php // dashboard_footer.php ?>
  </main><!-- /content-area -->
</div><!-- /main-wrap -->

<!-- NOTIFICATION DRAWER -->
<div class="notif-drawer" id="notifDrawer">
  <div class="notif-drawer-header">
    <h6 class="mb-0 fw-bold"><i class="fas fa-bell me-2"></i>Notificaciones</h6>
    <button class="btn btn-sm btn-outline-secondary rounded-pill" onclick="markAllReadFn()">Marcar todas leídas</button>
  </div>
  <div class="notif-list" id="notifList">
    <div class="text-center text-muted py-4 small">Cargando...</div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
// Sidebar toggle
const sidebar = document.getElementById('sidebar');
const mainWrap = document.getElementById('mainWrap');
document.getElementById('sidebarToggle').addEventListener('click', () => {
  sidebar.classList.toggle('open');
  if (window.innerWidth > 768) {
    mainWrap.style.marginLeft = sidebar.classList.contains('open') || !sidebar.classList.contains('collapsed') ? '' : '0';
    sidebar.classList.toggle('collapsed');
  }
});

// Notification drawer
const drawer = document.getElementById('notifDrawer');
document.getElementById('notifToggle').addEventListener('click', () => {
  drawer.classList.toggle('open');
  if (drawer.classList.contains('open')) loadNotifs();
});

async function loadNotifs() {
  try {
    const r = await fetch('/SMIA2/api/notificaciones.php?action=get');
    const d = await r.json();
    const list = document.getElementById('notifList');
    if (!d.data || d.data.length === 0) {
      list.innerHTML = '<div class="text-center text-muted py-4 small"><i class="fas fa-check-circle fa-2x mb-2 d-block text-success"></i>Sin notificaciones nuevas</div>';
      return;
    }
    list.innerHTML = d.data.map(n => `
      <div class="notif-item ${n.leida=='0'?'unread':''} ${n.tipo}" onclick="readNotif(${n.id})">
        <div class="d-flex gap-2">
          <i class="${n.icono} mt-1 text-${n.tipo==='alarm'?'warning':n.tipo}" style="width:16px"></i>
          <div class="flex-1">
            <div class="fw-semibold small">${n.titulo}</div>
            <div class="text-muted" style="font-size:.8rem">${n.mensaje.substring(0,120)}</div>
            <div class="notif-time mt-1">${n.fecha_creacion}</div>
          </div>
        </div>
      </div>`).join('');
  } catch(e) {}
}

async function readNotif(id) {
  await fetch(`/SMIA2/api/notificaciones.php?action=read&id=${id}`);
  loadNotifs();
  document.querySelector('.notif-badge')?.remove();
}

async function markAllReadFn() {
  await fetch('/SMIA2/api/notificaciones.php?action=read_all');
  loadNotifs();
  document.querySelector('.notif-badge')?.remove();
}

// Auto-refresh notifs every 60s
setInterval(loadNotifs, 60000);

// Close drawer on outside click
document.addEventListener('click', e => {
  if (!drawer.contains(e.target) && !document.getElementById('notifToggle').contains(e.target)) {
    drawer.classList.remove('open');
  }
});
</script>
</body>
</html>
