<?php
// csrf.php: simple protection against fake form submissions
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function csrfToken() {
    if (!isset($_SESSION["csrf"])) {
        $_SESSION["csrf"] = bin2hex(random_bytes(16));
    }
    return $_SESSION["csrf"];
}

function checkCsrf() {
    $posted = $_POST["csrf"] ?? "";
    $saved  = $_SESSION["csrf"] ?? "";
    if ($posted === "" || $saved === "" || !hash_equals($saved, $posted)) {
        die("CSRF check failed.");
    }
}
