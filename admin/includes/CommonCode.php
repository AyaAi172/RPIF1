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

if (!function_exists("hasThemeColumn")) {
    function hasThemeColumn($conn) {
        static $hasTheme = null;

        if ($hasTheme !== null) {
            return $hasTheme;
        }

        $sql = "
            SELECT 1
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'users'
              AND COLUMN_NAME = 'theme'
            LIMIT 1
        ";

        $res = mysqli_query($conn, $sql);
        $hasTheme = ($res && mysqli_fetch_assoc($res)) ? true : false;
        return $hasTheme;
    }
}

if (!function_exists("hasTable")) {
    function hasTable($conn, $tableName) {
        static $tableCache = [];

        if (isset($tableCache[$tableName])) {
            return $tableCache[$tableName];
        }

        $sql = "
            SELECT 1
            FROM information_schema.TABLES
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = ?
            LIMIT 1
        ";

        $stmt = mysqli_prepare($conn, $sql);
        if (!$stmt) {
            $tableCache[$tableName] = false;
            return false;
        }

        mysqli_stmt_bind_param($stmt, "s", $tableName);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $tableCache[$tableName] = ($res && mysqli_fetch_assoc($res)) ? true : false;
        return $tableCache[$tableName];
    }
}

if (!function_exists("getThemePreference")) {
    function getThemePreference($conn) {
        $allowedThemes = ["light", "dark"];

        if (isset($_SESSION["theme"]) && in_array($_SESSION["theme"], $allowedThemes, true)) {
            return $_SESSION["theme"];
        }

        if (isset($_COOKIE["pif_theme"]) && in_array($_COOKIE["pif_theme"], $allowedThemes, true)) {
            return $_COOKIE["pif_theme"];
        }

        if (isset($_SESSION["user_id"]) && hasThemeColumn($conn)) {
            $stmt = mysqli_prepare($conn, "SELECT theme FROM users WHERE user_id = ?");
            if (!$stmt) {
                return "light";
            }
            mysqli_stmt_bind_param($stmt, "i", $_SESSION["user_id"]);
            mysqli_stmt_execute($stmt);
            $res = mysqli_stmt_get_result($stmt);
            if ($row = mysqli_fetch_assoc($res)) {
                $theme = $row["theme"] ?? "light";
                if (!in_array($theme, $allowedThemes, true)) {
                    $theme = "light";
                }

                $_SESSION["theme"] = $theme;
                setcookie("pif_theme", $theme, time() + (60 * 60 * 24 * 365), "/");
                return $theme;
            }
        }

        return "light";
    }
}

if (!function_exists("saveThemePreference")) {
    function saveThemePreference($conn, $theme) {
        $theme = ($theme === "dark") ? "dark" : "light";

        $_SESSION["theme"] = $theme;
        setcookie("pif_theme", $theme, time() + (60 * 60 * 24 * 365), "/");

        if (isset($_SESSION["user_id"]) && hasThemeColumn($conn)) {
            $stmt = mysqli_prepare($conn, "UPDATE users SET theme = ? WHERE user_id = ?");
            if (!$stmt) {
                return;
            }
            mysqli_stmt_bind_param($stmt, "si", $theme, $_SESSION["user_id"]);
            mysqli_stmt_execute($stmt);
        }
    }
}

if (!function_exists("esc")) {
    function esc($text) {
        return htmlspecialchars($text ?? "", ENT_QUOTES, "UTF-8");
    }
}

if (!function_exists("e")) {
    function e($text) {
        return esc($text);
    }
}

function toSqlDateTime($value) {
    $value = trim((string)$value);
    if ($value === "") {
        return "";
    }

    return str_replace("T", " ", $value) . ":00";
}

// Return current user info from database (used in account.php)
function getCurrentUser($conn) {
    if (!isset($_SESSION["user_id"])) {
        return null;
    }

    $id = (int)$_SESSION["user_id"];

    $sql = hasThemeColumn($conn)
        ? "SELECT user_id, username, full_name, email, role, theme FROM users WHERE user_id=?"
        : "SELECT user_id, username, full_name, email, role FROM users WHERE user_id=?";

    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);

    return mysqli_fetch_assoc($res); // returns array or null
}
