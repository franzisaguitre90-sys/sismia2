<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();

header('Content-Type: application/json');

$uid    = currentUserId();
$action = $_GET['action'] ?? 'get';

try {
    switch ($action) {
        case 'get':
            $notifs = getUnread($uid);
            $count  = countUnread($uid);
            echo json_encode(['ok' => true, 'count' => $count, 'items' => $notifs]);
            break;

        case 'read':
            $id = (int)($_GET['id'] ?? 0);
            if ($id) markNotifRead($id, $uid);
            echo json_encode(['ok' => true]);
            break;

        case 'read_all':
            markAllRead($uid);
            echo json_encode(['ok' => true]);
            break;

        case 'all':
            $page  = max(1, (int)($_GET['page'] ?? 1));
            $limit = 20;
            $off   = ($page - 1) * $limit;
            $s = db()->prepare("SELECT * FROM notificaciones WHERE usuario_id=? ORDER BY fecha_creacion DESC LIMIT ? OFFSET ?");
            $s->execute([$uid, $limit, $off]);
            $items = $s->fetchAll();
            $total = (int)db()->prepare("SELECT COUNT(*) FROM notificaciones WHERE usuario_id=?")->execute([$uid]) ?
                     db()->query("SELECT COUNT(*) FROM notificaciones WHERE usuario_id=$uid")->fetchColumn() : 0;
            echo json_encode(['ok' => true, 'items' => $items, 'total' => $total, 'page' => $page]);
            break;

        default:
            http_response_code(400);
            echo json_encode(['ok' => false, 'msg' => 'Acción no válida']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'msg' => 'Error interno']);
}
