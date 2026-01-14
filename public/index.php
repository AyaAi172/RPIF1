<?php
require_once __DIR__ . "/../admin/includes/auth.php";

if (isLoggedIn()) {
    header("Location: /RPIF1/user/welcome.php");
    exit();
}
header("Location: /RPIF1/public/login.php");
exit();
