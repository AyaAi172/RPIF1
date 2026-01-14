<?php
require_once __DIR__ . "/../admin/includes/auth.php";

session_unset();
session_destroy();

header("Location: /RPIF1/public/login.php");
exit();
