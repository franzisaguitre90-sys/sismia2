<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/session.php';

if (isLoggedIn()) {
    try {
        db()->prepare("UPDATE sesiones SET activa=0 WHERE usuario_id=? AND token=?")
            ->execute([currentUserId(), $_SESSION['token'] ?? '']);
    } catch (Exception $e) {}
}

session_destroy();
header("Location: /SMIA2/login.php");
exit;
