<?php
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => false,
        'httponly' => true,
        'samesite' => 'Strict',
    ]);
    session_start();
}

function isLoggedIn(): bool {
    return !empty($_SESSION['user_id']) && !empty($_SESSION['rol']);
}

function requireLogin(string $redirect = '/SMIA2/login.php'): void {
    if (!isLoggedIn()) {
        header("Location: $redirect");
        exit;
    }
}

function requireRole(string|array $roles, string $redirect = '/SMIA2/login.php'): void {
    requireLogin($redirect);
    $allowed = is_array($roles) ? $roles : [$roles];
    $allowed[] = 'admin';
    if (!in_array($_SESSION['rol'], $allowed, true)) {
        header("Location: /SMIA2/login.php?error=acceso_denegado");
        exit;
    }
}

function currentUserId(): int {
    return (int)($_SESSION['user_id'] ?? 0);
}

function currentUserRol(): string {
    return $_SESSION['rol'] ?? '';
}

function currentUser(): array {
    return $_SESSION['user'] ?? [];
}

function hasRole(string|array $roles): bool {
    $allowed = is_array($roles) ? $roles : [$roles];
    $allowed[] = 'admin';
    return in_array(currentUserRol(), $allowed, true);
}

function setFlash(string $type, string $msg): void {
    $_SESSION['flash'] = ['type' => $type, 'msg' => $msg];
}

function getFlash(): ?array {
    if (!empty($_SESSION['flash'])) {
        $f = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $f;
    }
    return null;
}
