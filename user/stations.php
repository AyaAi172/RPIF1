<?php
require_once __DIR__ . "/../admin/includes/auth.php";
requireLogin();

$title = "Stations";
require_once __DIR__ . "/../admin/includes/header.php";
?>
<h1 class="h3">Stations</h1>
<p class="text-muted">Coming next: list/edit stations + register by serial number.</p>
<?php require_once __DIR__ . "/../admin/includes/footer.php"; ?>
