<?php
require_once __DIR__ . "/../admin/includes/db.php";
require_once __DIR__ . "/../admin/includes/auth.php";
require_once __DIR__ . "/../admin/includes/csrf.php";
require_once __DIR__ . "/../admin/includes/header.php";


session_unset();
session_destroy();

header("Location: /RPIF1/public/login.php");
exit();
require_once __DIR__ . "/../admin/includes/footer.php";
    ?>