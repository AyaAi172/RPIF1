<?php
require_once $_SERVER["DOCUMENT_ROOT"] . "/RPIF1/admin/includes/auth.php";
if (isLoggedIn()) {
  header("Location: /RPIF1/user/welcome.php");
} else {
  header("Location: /RPIF1/public/login.php");
}
exit();
