<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function e(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

function isLoggedIn(): bool {
    return isset($_SESSION['user_id']);
}

function isAdmin(): bool {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function requireLogin(): void {
    if (!isLoggedIn()) {
        header("Location: /RPIF1/public/login.php");
        exit();
    }
}

function requireAdmin(): void {
    if (!isLoggedIn() || !isAdmin()) {
        header("Location: /RPIF1/user/welcome.php");
        exit();
    }
}
