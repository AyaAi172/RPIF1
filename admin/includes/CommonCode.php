<?php
// CommonCode.php
// One file included by ALL pages. It loads DB, auth, csrf, header/footer helpers.
// This prevents path problems and keeps the project consistent.

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Absolute filesystem root of RPIF1 (example: C:\xampp\htdocs\RPIF1)
define("PIF_ROOT", realpath(__DIR__ . "/..")); // admin folder
define("PIF_APP", realpath(__DIR__ . "/../..")); // RPIF1 folder

require_once PIF_ROOT . "/includes/db.php";
require_once PIF_ROOT . "/includes/auth.php";
require_once PIF_ROOT . "/includes/csrf.php";

// Safe echo
function e($text) {
    return htmlspecialchars($text ?? "", ENT_QUOTES, "UTF-8");
}

// Return current user info from database (used in account.php)
function getCurrentUser($conn) {
    if (!isset($_SESSION["user_id"])) {
        return null;
    }

    $id = (int)$_SESSION["user_id"];

    $stmt = mysqli_prepare($conn, "SELECT user_id, username, full_name, email, role FROM users WHERE user_id=?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);

    return mysqli_fetch_assoc($res); // returns array or null
}
