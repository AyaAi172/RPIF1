<?php
require_once __DIR__ . "/../admin/includes/db.php";
require_once __DIR__ . "/../admin/includes/auth.php";

requireLogin();

$title = "Measurements";
require_once __DIR__ . "/../admin/includes/header.php";
?>
<h1 class="h3">Measurements</h1>
<p class="text-muted">Coming next: filter by station and date/time, show table.</p>
<?php require_once __DIR__ . "/../admin/includes/footer.php";
 ?>
