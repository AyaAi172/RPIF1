<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_check(): void {
    $posted = $_POST['csrf_token'] ?? '';
    $session = $_SESSION['csrf_token'] ?? '';
    if (!$posted || !$session || !hash_equals($session, $posted)) {
        die("Invalid CSRF token.");
    }
}
