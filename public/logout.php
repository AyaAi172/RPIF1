<?php
require_once $_SERVER["DOCUMENT_ROOT"] . "/RPIF1/admin/includes/CommonCode.php";
session_unset();
session_destroy();
header("Location: /RPIF1/public/login.php");
exit();
?> 