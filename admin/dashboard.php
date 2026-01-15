<?php
require_once $_SERVER["DOCUMENT_ROOT"] . "/RPIF1/admin/includes/CommonCode.php";
requireAdmin();
$title = "Admin Dashboard";
require_once PIF_ROOT . "/includes/header.php";
?>
<h1 class="h3">Admin Dashboard</h1>
<p class="text-muted">Use the admin menu to manage users, stations, and measurements.</p>
<?php require_once PIF_ROOT . "/includes/footer.php";
?>
