<?php
// auth.php: session + simple login helpers
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function isLoggedIn() {
    return isset($_SESSION["user_id"]);
}

function isAdmin() {
    return isset($_SESSION["role"]) && $_SESSION["role"] === "admin";
}

function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: /RPIF1/public/login.php");
        exit();
    }
}

function requireAdmin() {
    if (!isLoggedIn() || !isAdmin()) {
        header("Location: /RPIF1/user/welcome.php");
        exit();
    }
}

function esc($text) {
    return htmlspecialchars($text, ENT_QUOTES, "UTF-8");
}
