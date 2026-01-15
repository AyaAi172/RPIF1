<?php
require_once __DIR__ . "/../includes/auth.php";
if (isLoggedIn()) {
  header("Location: /RPIF1/user/welcome.php");
} else {
  header("Location: /RPIF1/public/login.php");
}
exit();
